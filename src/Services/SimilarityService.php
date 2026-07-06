<?php

namespace OiLab\OiLaravelRaggable\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use OiLab\OiLaravelRaggable\Contracts\Embeddable;
use OiLab\OiLaravelRaggable\Contracts\VectorStore;
use OiLab\OiLaravelRaggable\Models\Chunk;
use OiLab\OiLaravelRaggable\OiLaravelRaggable;

/**
 * Semantic similarity queries over the embeddings: given a source model (or an
 * ad hoc text), find the closest models of a target type by cosine distance.
 * similarToText() is the retrieval entry point of a RAG pipeline.
 */
class SimilarityService
{
    public function __construct(
        private readonly EmbeddingService $embeddings,
        private readonly VectorStore $store,
    ) {}

    /**
     * Find the models of $targetClass semantically closest to the source model.
     * Results carry a `similarity_distance` attribute (cosine distance,
     * 0 = identical) and are filtered by the optional max distance.
     *
     * @template TTarget of Model
     *
     * @param  class-string<TTarget>  $targetClass
     * @return Collection<int, TTarget>
     */
    public function similarTo(Model&Embeddable $source, string $targetClass, int $limit = 20, ?float $maxDistance = null): Collection
    {
        $embedding = $source->embedding ?? $this->embeddings->embed($source);

        if ($embedding === null) {
            return new Collection;
        }

        $vector = $embedding->vector;

        if (empty($vector)) {
            return new Collection;
        }

        return $this->nearest(
            $vector,
            $targetClass,
            $limit,
            $maxDistance,
            ignore: $source instanceof $targetClass ? $source : null,
        );
    }

    /**
     * Find the models of $targetClass semantically closest to an ad hoc text
     * (editorial theme, search query, RAG question...).
     *
     * @template TTarget of Model
     *
     * @param  class-string<TTarget>  $targetClass
     * @return Collection<int, TTarget>
     */
    public function similarToText(string $text, string $targetClass, int $limit = 20, ?float $maxDistance = null): Collection
    {
        if (trim($text) === '') {
            return new Collection;
        }

        $vector = $this->embeddings->vectorFor($text);

        if ($vector === []) {
            return new Collection;
        }

        return $this->nearest($vector, $targetClass, $limit, $maxDistance);
    }

    /**
     * @param  list<float>  $vector
     * @return Collection<int, Model>
     */
    protected function nearest(array $vector, string $targetClass, int $limit, ?float $maxDistance, ?Model $ignore = null): Collection
    {
        $maxDistance ??= OiLaravelRaggable::maxDistance();
        $morphAlias = (new $targetClass)->getMorphClass();

        $chunks = $this->store->nearest($vector, $morphAlias, $limit, $maxDistance, $ignore);

        return $this->resolveNearestModels($chunks, $limit);
    }

    /**
     * Collapse the matched chunks down to their parent models, keeping the best
     * (smallest) distance per model and the requested limit.
     *
     * @param  Collection<int, Chunk>  $chunks
     * @return Collection<int, Model>
     */
    protected function resolveNearestModels(Collection $chunks, int $limit): Collection
    {
        $byModel = [];

        foreach ($chunks as $chunk) {
            $model = $chunk->embedding?->embeddable;

            if ($model === null) {
                continue;
            }

            $key = $model->getMorphClass().':'.$model->getKey();
            $distance = (float) $chunk->similarity_distance;

            if (! isset($byModel[$key]) || $distance < $byModel[$key]['distance']) {
                $byModel[$key] = ['model' => $model, 'distance' => $distance];
            }
        }

        return collect($byModel)
            ->sortBy('distance')
            ->take($limit)
            ->map(function (array $row): Model {
                $row['model']->setAttribute('similarity_distance', $row['distance']);

                return $row['model'];
            })
            ->values();
    }
}
