<?php

namespace OiLab\OiLaravelRaggable;

use OiLab\OiLaravelRaggable\Contracts\Embedder;
use OiLab\OiLaravelRaggable\Contracts\SettingStore;
use OiLab\OiLaravelRaggable\Contracts\VectorStore;
use OiLab\OiLaravelRaggable\Embedders\LaravelAiEmbedder;
use OiLab\OiLaravelRaggable\Models\Chunk;
use OiLab\OiLaravelRaggable\Models\Embedding;
use OiLab\OiLaravelRaggable\Stores\OiLaravelSettingsStore;
use OiLab\OiLaravelRaggable\VectorStores\DatabaseVectorStore;
use OiLab\OiLaravelRaggable\VectorStores\PgvectorStore;
use OiLab\OiLaravelSettings\SettingsManager;

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
        return (bool) static::setting('auto_refresh', config('oi-laravel-raggable.auto_refresh', true));
    }

    public static function queue(): string
    {
        return config('oi-laravel-raggable.queue', 'default');
    }

    public static function maxDistance(): float
    {
        return (float) static::setting('similarity.max_distance', config('oi-laravel-raggable.similarity.max_distance', 0.5));
    }

    public static function limit(): int
    {
        return (int) static::setting('similarity.limit', config('oi-laravel-raggable.similarity.limit', 20));
    }

    public static function trackUsage(): bool
    {
        return (bool) config('oi-laravel-raggable.track_usage', true);
    }

    /**
     * The provider passed to the embedder, or null for the SDK default.
     */
    public static function embeddingProvider(): ?string
    {
        $value = static::setting('embedding.provider', config('oi-laravel-raggable.embedding.provider'));

        return ($value === null || $value === '') ? null : (string) $value;
    }

    /**
     * The embedding model name passed to the embedder, or null for the SDK
     * default. Distinct from embeddingModel(), which resolves the Eloquent
     * Embedding class.
     */
    public static function embeddingModelName(): ?string
    {
        $value = static::setting('embedding.model', config('oi-laravel-raggable.embedding.model'));

        return ($value === null || $value === '') ? null : (string) $value;
    }

    /**
     * The setting store implementation class, auto-detecting the
     * oi-laravel-settings adapter when that package is installed.
     *
     * @return class-string<SettingStore>|null
     */
    public static function settingStoreClass(): ?string
    {
        $implementation = config('oi-laravel-raggable.setting_store');

        if ($implementation === null && class_exists(SettingsManager::class)) {
            $implementation = OiLaravelSettingsStore::class;
        }

        return $implementation;
    }

    /**
     * The scope (e.g. team id) settings are read/written under; null = global.
     */
    public static function scope(): ?string
    {
        $binding = config('oi-laravel-raggable.context_binding');

        if ($binding !== null && app()->bound($binding)) {
            $value = app($binding);

            return $value === null ? null : (string) $value;
        }

        return null;
    }

    /**
     * Read a runtime-tunable setting from the store, falling back to config.
     */
    public static function setting(string $key, mixed $default): mixed
    {
        if (! app()->bound(SettingStore::class)) {
            return $default;
        }

        /** @var SettingStore|null $store */
        $store = app(SettingStore::class);

        if ($store === null) {
            return $default;
        }

        $value = $store->get($key, static::scope());

        return $value ?? $default;
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
