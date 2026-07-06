<?php

namespace OiLab\OiLaravelRaggable;

use OiLab\OiLaravelRaggable\Contracts\Embedder;
use OiLab\OiLaravelRaggable\Contracts\VectorStore;
use OiLab\OiLaravelRaggable\Embedders\LaravelAiEmbedder;
use OiLab\OiLaravelRaggable\Models\Chunk;
use OiLab\OiLaravelRaggable\Models\Embedding;
use OiLab\OiLaravelRaggable\VectorStores\DatabaseVectorStore;
use OiLab\OiLaravelRaggable\VectorStores\PgvectorStore;

/**
 * Static resolver for the package's configurable classes and settings, so host
 * applications can subclass models, swap the embedder or the vector store, and
 * tune behavior entirely through config.
 */
class OiLaravelRaggable
{
    public static function userModel(): string
    {
        return config('oi-laravel-raggable.user_model', 'App\\Models\\User');
    }

    /**
     * @return class-string<Embedding>
     */
    public static function embeddingModel(): string
    {
        return config('oi-laravel-raggable.models.embedding', Embedding::class);
    }

    /**
     * @return class-string<Chunk>
     */
    public static function chunkModel(): string
    {
        return config('oi-laravel-raggable.models.chunk', Chunk::class);
    }

    public static function driver(): string
    {
        return config('oi-laravel-raggable.driver', 'database');
    }

    public static function dimensions(): int
    {
        return (int) config('oi-laravel-raggable.dimensions', 1024);
    }

    public static function autoRefresh(): bool
    {
        return (bool) config('oi-laravel-raggable.auto_refresh', true);
    }

    public static function queue(): string
    {
        return config('oi-laravel-raggable.queue', 'default');
    }

    public static function maxDistance(): float
    {
        return (float) config('oi-laravel-raggable.similarity.max_distance', 0.5);
    }

    public static function limit(): int
    {
        return (int) config('oi-laravel-raggable.similarity.limit', 20);
    }

    /**
     * @return class-string<Embedder>
     */
    public static function embedderClass(): string
    {
        return config('oi-laravel-raggable.embedder', LaravelAiEmbedder::class);
    }

    /**
     * @return class-string<VectorStore>
     */
    public static function vectorStoreClass(): string
    {
        $driver = static::driver();

        return config("oi-laravel-raggable.stores.{$driver}", match ($driver) {
            'pgvector' => PgvectorStore::class,
            default => DatabaseVectorStore::class,
        });
    }

    /**
     * The embeddable registry used by the `raggable:embed` command.
     *
     * @return array<string, class-string>
     */
    public static function embeddables(): array
    {
        return config('oi-laravel-raggable.embeddables', []);
    }
}
