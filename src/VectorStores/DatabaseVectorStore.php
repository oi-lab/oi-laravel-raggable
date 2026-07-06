<?php

namespace OiLab\OiLaravelRaggable\VectorStores;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use OiLab\OiLaravelRaggable\Contracts\VectorStore;
use OiLab\OiLaravelRaggable\Models\Chunk;
use OiLab\OiLaravelRaggable\OiLaravelRaggable;

/**
 * Portable vector store: vectors are stored as JSON and cosine distance is
 * computed in PHP. Works on any database driver (including SQLite) with no
 * extension, which makes it the right default and keeps the package testable
 * everywhere. Prefer the pgvector store for large corpora.
 */
class DatabaseVectorStore implements VectorStore
{
    public function nearest(array $vector, string $morphType, int $limit, float $maxDistance, ?Model $ignore = null): Collection
    {
        if ($vector === []) {
            return new Collection;
        }

        $chunkModel = OiLaravelRaggable::chunkModel();

        return $chunkModel::query()
            ->whereHas('embedding', function (Builder $query) use ($morphType, $ignore): void {
                $query->where('embeddable_type', $morphType);

                if ($ignore !== null) {
                    $query->whereNot(function (Builder $query) use ($ignore): void {
                        $query->where('embeddable_type', $ignore->getMorphClass())
                            ->where('embeddable_id', $ignore->getKey());
                    });
                }
            })
            ->with('embedding.embeddable')
            ->get()
            ->map(function (Chunk $chunk) use ($vector): Chunk {
                $chunk->similarity_distance = $this->cosineDistance($vector, $chunk->vector ?? []);

                return $chunk;
            })
            ->filter(fn (Chunk $chunk): bool => $chunk->similarity_distance <= $maxDistance)
            ->sortBy('similarity_distance')
            // Over-fetch: a single document owns several chunks, and callers
            // collapse to parents afterwards.
            ->take($limit * 4)
            ->values();
    }

    /**
     * Cosine distance (0 = identical, 1 = orthogonal, 2 = opposite). Returns the
     * maximum distance when either vector is empty or has zero magnitude.
     *
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    protected function cosineDistance(array $a, array $b): float
    {
        $length = min(count($a), count($b));

        if ($length === 0) {
            return 2.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $length; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 2.0;
        }

        return 1.0 - ($dot / (sqrt($normA) * sqrt($normB)));
    }
}
