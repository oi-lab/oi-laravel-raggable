<?php

namespace OiLab\OiLaravelRaggable\VectorStores;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use OiLab\OiLaravelRaggable\Contracts\VectorStore;
use OiLab\OiLaravelRaggable\Models\Chunk;
use OiLab\OiLaravelRaggable\OiLaravelRaggable;

/**
 * Native Postgres vector store: nearest-neighbor search runs in the database
 * through pgvector's cosine-distance operator (`<=>`) against the HNSW index
 * created by the migration. Fast at scale; requires PostgreSQL with the pgvector
 * extension. The stored/query vector text form is produced by VectorCast.
 */
class PgvectorStore implements VectorStore
{
    public function nearest(array $vector, string $morphType, int $limit, float $maxDistance, ?Model $ignore = null): Collection
    {
        if ($vector === []) {
            return new Collection;
        }

        $chunkModel = OiLaravelRaggable::chunkModel();
        $literal = '['.implode(',', array_map(static fn (float $v): float => $v, $vector)).']';

        return $chunkModel::query()
            ->select('*')
            ->selectRaw('vector <=> ? as similarity_distance', [$literal])
            ->whereHas('embedding', function (Builder $query) use ($morphType, $ignore): void {
                $query->where('embeddable_type', $morphType);

                if ($ignore !== null) {
                    $query->whereNot(function (Builder $query) use ($ignore): void {
                        $query->where('embeddable_type', $ignore->getMorphClass())
                            ->where('embeddable_id', $ignore->getKey());
                    });
                }
            })
            ->whereRaw('vector <=> ? <= ?', [$literal, $maxDistance])
            ->orderByRaw('vector <=> ?', [$literal])
            ->with('embedding.embeddable')
            // Over-fetch: a single document owns several chunks, and callers
            // collapse to parents afterwards.
            ->take($limit * 4)
            ->get()
            ->map(function (Chunk $chunk): Chunk {
                $chunk->similarity_distance = (float) $chunk->similarity_distance;

                return $chunk;
            });
    }
}
