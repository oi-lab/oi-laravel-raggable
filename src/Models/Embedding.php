<?php

namespace OiLab\OiLaravelRaggable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OiLab\OiLaravelRaggable\Casts\VectorCast;
use OiLab\OiLaravelRaggable\Data\EmbeddingData;
use OiLab\OiLaravelRaggable\Database\Factories\EmbeddingFactory;
use OiLab\OiLaravelRaggable\OiLaravelRaggable;

/**
 * Document header for an embeddable model's text content. The searchable
 * vectors live in the related chunks, so long content is split below the
 * provider token limit while staying searchable; the header keeps a
 * document-level centroid and a content hash to skip unchanged regenerations.
 */
class Embedding extends Model
{
    /** @use HasFactory<EmbeddingFactory> */
    use HasFactory;

    protected $table = 'raggable_embeddings';

    /** @var list<string> */
    protected $fillable = [
        'embeddable_type',
        'embeddable_id',
        'content_hash',
        'content',
        'vector',
        'provider',
        'model',
        'generated_at',
    ];

    public function embeddable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<Chunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(OiLaravelRaggable::chunkModel(), 'embedding_id');
    }

    public function toData(): EmbeddingData
    {
        return EmbeddingData::fromModel($this);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'vector' => VectorCast::class,
        ];
    }

    protected static function newFactory(): EmbeddingFactory
    {
        return EmbeddingFactory::new();
    }
}
