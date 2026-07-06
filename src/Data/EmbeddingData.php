<?php

namespace OiLab\OiLaravelRaggable\Data;

use Illuminate\Support\Carbon;
use OiLab\OiLaravelRaggable\Models\Embedding;
use Spatie\LaravelData\Data;

class EmbeddingData extends Data
{
    /**
     * @param  list<ChunkData>|null  $chunks
     */
    public function __construct(
        public readonly int $id,
        public readonly string $embeddableType,
        public readonly int|string $embeddableId,
        public readonly string $contentHash,
        public readonly ?string $provider = null,
        public readonly ?string $model = null,
        public readonly ?Carbon $generatedAt = null,
        public readonly ?array $chunks = null,
    ) {}

    public static function fromModel(Embedding $embedding): self
    {
        return new self(
            id: $embedding->id,
            embeddableType: $embedding->embeddable_type,
            embeddableId: $embedding->embeddable_id,
            contentHash: $embedding->content_hash,
            provider: $embedding->provider,
            model: $embedding->model,
            generatedAt: $embedding->generated_at,
            chunks: $embedding->relationLoaded('chunks')
                ? $embedding->chunks->map(fn ($chunk) => $chunk->toData())->all()
                : null,
        );
    }
}
