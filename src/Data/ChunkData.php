<?php

namespace OiLab\OiLaravelRaggable\Data;

use OiLab\OiLaravelRaggable\Models\Chunk;
use Spatie\LaravelData\Data;

class ChunkData extends Data
{
    /**
     * @param  array<string, mixed>|null  $metadata
     * @param  list<float>|null  $vector
     */
    public function __construct(
        public readonly string $id,
        public readonly int $chunkIndex,
        public readonly string $content,
        public readonly ?int $tokenCount = null,
        public readonly ?array $metadata = null,
        public readonly ?array $vector = null,
        public readonly ?float $similarityDistance = null,
    ) {}

    public static function fromModel(Chunk $chunk): self
    {
        return new self(
            id: $chunk->id,
            chunkIndex: (int) $chunk->chunk_index,
            content: $chunk->content,
            tokenCount: $chunk->token_count,
            metadata: $chunk->metadata,
            vector: $chunk->vector,
            similarityDistance: $chunk->similarity_distance,
        );
    }
}
