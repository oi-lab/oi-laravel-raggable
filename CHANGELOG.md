# Changelog

All notable changes to `oi-lab/oi-laravel-raggable` will be documented in this file.

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
