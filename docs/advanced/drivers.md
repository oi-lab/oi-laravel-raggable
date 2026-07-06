---
title: Drivers & Extension Points
description: Swap the embedder, choose a vector store, and scale to pgvector
section: advanced
order: 2
---

# Drivers & Extension Points

Both the embedding provider and the storage engine are resolved through contracts, so you can replace either without touching your models. Everything is wired through the `OiLaravelRaggable` static resolver, which reads config.

## Custom embedder

Implement the `Embedder` contract and point config at your class:

```php
use OiLab\OiLaravelRaggable\Contracts\Embedder;
use OiLab\OiLaravelRaggable\Data\EmbeddingResult;

class MyEmbedder implements Embedder
{
    public function embed(array $texts): EmbeddingResult
    {
        // Return one vector per input, in the same order.
        return new EmbeddingResult(
            vectors: $vectors,      // list<list<float>>
            provider: 'mine',
            model: 'my-model',
        );
    }
}
```

```php
// config/oi-laravel-raggable.php
'embedder' => \App\Ai\MyEmbedder::class,
```

The default `LaravelAiEmbedder` wraps `laravel/ai`; provider and model come from `config/ai.php`. In tests, bind a fake embedder to the `Embedder` contract so no real API calls happen.

## Vector stores

The active store is chosen by `driver`:

- **`database` (`DatabaseVectorStore`)** — stores vectors as JSON and computes cosine distance in PHP. Works on any database (including SQLite), needs no extension, and keeps the package testable everywhere. Ideal for small to medium corpora.
- **`pgvector` (`PgvectorStore`)** — stores native `vector` columns and runs nearest-neighbor search in PostgreSQL through the `<=>` cosine operator against an HNSW index. Fast at scale.

Both implement the `VectorStore` contract, so you can register your own under `stores.<driver>` and select it with `driver`.

## Scaling to pgvector

1. Use a PostgreSQL connection with the `vector` extension available (e.g. the `pgvector/pgvector` Docker image).
2. Set `RAGGABLE_DRIVER=pgvector` and the correct `RAGGABLE_DIMENSIONS`.
3. Run `php artisan migrate` — the migration enables the extension, creates native `vector` columns, and builds HNSW cosine indexes.
4. Backfill with `php artisan raggable:embed --fresh`.

> **Dimensions are load-bearing.** They must match the embedding model exactly. Changing the model or dimensions later means recreating the vector columns and indexes, then re-running `raggable:embed --fresh`.

## How storage stays driver-agnostic

Vectors are cast (via `VectorCast`) to a bracketed list such as `[0.1,0.2,0.3]` — which is simultaneously valid JSON for the `database` driver and a valid pgvector text input for the `pgvector` driver. The same `Embedding` and `Chunk` models therefore work unchanged across both.
