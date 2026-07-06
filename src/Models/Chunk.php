<?php

namespace OiLab\OiLaravelRaggable\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OiLab\OiLaravelRaggable\Casts\VectorCast;
use OiLab\OiLaravelRaggable\Data\ChunkData;
use OiLab\OiLaravelRaggable\Database\Factories\ChunkFactory;
use OiLab\OiLaravelRaggable\OiLaravelRaggable;

/**
 * A single embedded slice of an embeddable model's content. Nearest-neighbor
 * similarity runs at the chunk level and is mapped back to the parent document
 * through the embedding relation. Search results set the non-persisted
 * `similarity_distance` attribute.
 *
 * @property float|null $similarity_distance
 */
class Chunk extends Model
{
    /** @use HasFactory<ChunkFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'raggable_chunks';

    public $incrementing = false;

    protected $keyType = 'string';

    /** @var list<string> */
    protected $fillable = [
        'embedding_id',
        'content',
        'vector',
        'metadata',
        'chunk_index',
        'token_count',
    ];

    /**
     * @return BelongsTo<Embedding, $this>
     */
    public function embedding(): BelongsTo
    {
        return $this->belongsTo(OiLaravelRaggable::embeddingModel(), 'embedding_id');
    }

    public function toData(): ChunkData
    {
        return ChunkData::fromModel($this);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vector' => VectorCast::class,
            'metadata' => 'array',
            'chunk_index' => 'integer',
            'token_count' => 'integer',
        ];
    }

    protected static function newFactory(): ChunkFactory
    {
        return ChunkFactory::new();
    }
}
