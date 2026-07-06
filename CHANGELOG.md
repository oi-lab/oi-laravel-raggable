# Changelog

All notable changes to `oi-lab/oi-laravel-raggable` will be documented in this file.

## [1.1.0] - 2026-07-06

### Added

- **Runtime-tunable settings** via `oi-lab/oi-laravel-settings`: `similarity.max_distance`, `similarity.limit`, `auto_refresh`, and the embedding provider/model are resolved through a `SettingStore` (auto-detected adapter) with config fallback.
- **Embedding usage tracking** via `oi-lab/oi-laravel-ai`: each embedding request is recorded as an `ai_requests` row (token count, linked to the AI catalog when known) through a pluggable `UsageRecorder`; toggle with `RAGGABLE_TRACK_USAGE`.
- The default embedder now resolves its provider/model from settings and reports token usage.

### Changed

- Now requires `oi-lab/oi-laravel-ai` and `oi-lab/oi-laravel-settings`.
- Minimum Laravel raised to 12 (matching `oi-lab/oi-laravel-ai`).

## [1.0.0] - 2026-07-06

### Added

- `Embeddable` contract + `HasEmbedding` concern to make any Eloquent model semantically searchable.
- Automatic, incremental re-embedding on save (content-hash skip) via a queued `GenerateEmbeddingJob`.
- Pluggable embedder through the `Embedder` contract, with a `laravel/ai`-backed `LaravelAiEmbedder` default.
- Pluggable vector storage through the `VectorStore` contract: a portable `DatabaseVectorStore` (JSON + in-PHP cosine, any database) and a `PgvectorStore` (native pgvector columns + HNSW).
- Polymorphic `raggable_embeddings` / `raggable_chunks` tables with driver-aware migrations.
- Overlapping token-bounded chunking (`TextChunker`) so long content never exceeds the provider limit.
- `EmbeddingService` (generate/refresh/backfill) and `SimilarityService` (`similarTo`, `similarToText`) plus a `similar()` model helper.
- `raggable:embed` backfill command driven by a config registry.
- `spatie/laravel-data` DTOs (`EmbeddingData`, `ChunkData`, `EmbeddingResult`) and a static resolver for all configurable classes.
- Support for PHP 8.2–8.4 and Laravel 11, 12, and 13; 21 tests covering generation, similarity, the command, chunking, and the vector cast.
