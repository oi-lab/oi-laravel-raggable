---
title: Configuration Reference
description: Every key of config/oi-laravel-raggable.php with its environment variable
section: configuration
order: 2
---

# Configuration Reference

Publish the file to customize it:

```bash
php artisan vendor:publish --tag=oi-laravel-raggable-config
```

## Storage

| Key | Env | Default | Description |
|-----|-----|---------|-------------|
| `driver` | `RAGGABLE_DRIVER` | `database` | `database` (portable JSON + in-PHP cosine, any DB) or `pgvector` (native Postgres vectors + HNSW). |
| `dimensions` | `RAGGABLE_DIMENSIONS` | `1024` | Vector size — **must equal the embedding model output**, and be set before migrating. |

## Indexing

| Key | Env | Default | Description |
|-----|-----|---------|-------------|
| `auto_refresh` | `RAGGABLE_AUTO_REFRESH` | `true` | Re-embed automatically when embeddable attributes change. |
| `queue` | `RAGGABLE_QUEUE` | `default` | Queue the `GenerateEmbeddingJob` runs on. |
| `embedder` | — | `LaravelAiEmbedder::class` | The `Embedder` implementation used to produce vectors. |

## Similarity

| Key | Env | Default | Description |
|-----|-----|---------|-------------|
| `similarity.max_distance` | `RAGGABLE_MAX_DISTANCE` | `0.5` | Cosine distance cutoff above which results are dropped (0 = identical, 1 = orthogonal). |
| `similarity.limit` | `RAGGABLE_LIMIT` | `20` | Default result limit. |

## Chunking

Long content is split into overlapping chunks before embedding. Token counts are estimated locally (~ characters / `chars_per_token`).

| Key | Env | Default |
|-----|-----|---------|
| `chunk.max_tokens` | `RAGGABLE_CHUNK_MAX_TOKENS` | `1000` |
| `chunk.overlap_tokens` | `RAGGABLE_CHUNK_OVERLAP_TOKENS` | `120` |
| `chunk.hard_limit_tokens` | `RAGGABLE_CHUNK_HARD_LIMIT_TOKENS` | `6000` |
| `chunk.chars_per_token` | `RAGGABLE_CHUNK_CHARS_PER_TOKEN` | `3.5` |
| `chunk.max_inputs_per_request` | `RAGGABLE_MAX_INPUTS_PER_REQUEST` | `128` |
| `chunk.request_token_budget` | `RAGGABLE_REQUEST_TOKEN_BUDGET` | `16000` |

## Overridable classes

| Key | Default | Description |
|-----|---------|-------------|
| `models.embedding` | `Embedding::class` | Document-header model. |
| `models.chunk` | `Chunk::class` | Chunk model. |
| `stores.database` | `DatabaseVectorStore::class` | Store for the `database` driver. |
| `stores.pgvector` | `PgvectorStore::class` | Store for the `pgvector` driver. |
| `user_model` | `App\Models\User` | Host user model. |

## Registry

| Key | Description |
|-----|-------------|
| `embeddables` | Maps a short key to each embeddable model class, used by `raggable:embed`. Example: `['documents' => \App\Models\Document::class]`. |

## Calibration

After the first real backfill, run a few representative queries and inspect the `similarity_distance` of the results. Set `RAGGABLE_MAX_DISTANCE` just below the point where relevance drops off — too high returns noise, too low hides good matches.
