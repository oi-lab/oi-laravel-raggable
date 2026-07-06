<?php

namespace OiLab\OiLaravelRaggable\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use OiLab\OiLaravelRaggable\Contracts\Embeddable;
use OiLab\OiLaravelRaggable\Jobs\GenerateEmbeddingJob;
use OiLab\OiLaravelRaggable\Models\Embedding;
use OiLab\OiLaravelRaggable\OiLaravelRaggable;
use OiLab\OiLaravelRaggable\Services\SimilarityService;

/**
 * Makes an Embeddable model semantically searchable: it exposes the embedding
 * relation, keeps the vector fresh by dispatching a generation job whenever the
 * embeddable attributes change, and adds a `similar()` query helper.
 *
 * @phpstan-require-implements Embeddable
 *
 * @property-read Embedding|null $embedding
 */
trait HasEmbedding
{
    protected static function bootHasEmbedding(): void
    {
        static::saved(function (Model $model): void {
            /** @var Model&Embeddable $model */
            if (! OiLaravelRaggable::autoRefresh()) {
                return;
            }

            if ($model->wasRecentlyCreated || $model->wasChanged($model->embeddableAttributes())) {
                Bus::dispatch(new GenerateEmbeddingJob($model));
            }
        });

        static::deleted(function (Model $model): void {
            // Soft deletes keep the row; only drop the vector when the model is
            // really gone so restored models stay searchable.
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                $model->embedding()->delete();
            }
        });
    }

    /**
     * @return MorphOne<Embedding, $this>
     */
    public function embedding(): MorphOne
    {
        return $this->morphOne(OiLaravelRaggable::embeddingModel(), 'embeddable');
    }

    /**
     * Hash of the current embedding source text, used to skip regeneration when
     * the content did not change.
     */
    public function embeddingContentHash(): string
    {
        return hash('sha256', $this->toEmbeddingText());
    }

    public function needsEmbeddingRefresh(): bool
    {
        $embedding = $this->embedding;

        return $embedding === null || $embedding->content_hash !== $this->embeddingContentHash();
    }

    /**
     * The models most semantically similar to this one. By default it searches
     * models of the same type; pass $targetClass to search another type.
     *
     * @param  class-string<Model>|null  $targetClass
     * @return Collection<int, Model>
     */
    public function similar(int $limit = 20, ?float $maxDistance = null, ?string $targetClass = null): Collection
    {
        /** @var Model&Embeddable $this */
        return app(SimilarityService::class)->similarTo($this, $targetClass ?? static::class, $limit, $maxDistance);
    }

    /**
     * Join text fragments into a clean embedding source: markup stripped and
     * whitespace normalized, empty fragments dropped.
     *
     * @param  list<string|null>  $parts
     */
    protected function embeddingTextFrom(array $parts): string
    {
        return collect($parts)
            ->map(fn (?string $part): string => trim((string) preg_replace('/\s+/', ' ', strip_tags((string) $part))))
            ->filter(fn (string $part): bool => $part !== '')
            ->implode("\n");
    }
}
