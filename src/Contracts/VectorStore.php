<?php

namespace OiLab\OiLaravelRaggable\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use OiLab\OiLaravelRaggable\Models\Chunk;

/**
 * Performs nearest-neighbor search over the chunk vectors. Implementations back
 * the configured driver (portable in-PHP cosine or native pgvector).
 */
interface VectorStore
{
    /**
     * Return the chunks nearest to $vector among embeddings of $morphType,
     * each carrying a `similarity_distance` attribute (cosine distance,
     * 0 = identical) and filtered by $maxDistance. Over-fetching is expected:
     * a single document owns several chunks, so callers collapse to parents.
     *
     * @param  list<float>  $vector
     * @return Collection<int, Chunk>
     */
    public function nearest(array $vector, string $morphType, int $limit, float $maxDistance, ?Model $ignore = null): Collection;
}
