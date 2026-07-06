# OI Laravel Raggable — AI Context

Make any Eloquent model semantically searchable. A model becomes embeddable by implementing the `Embeddable` contract and using the `HasEmbedding` concern; the package then embeds its content into vectors, keeps them fresh on save, and answers similarity/RAG queries. The embedder and the vector store are both pluggable.

## Core Concepts

- **`Embeddable` contract** (`OiLab\OiLaravelRaggable\Contracts\Embeddable`) — `toEmbeddingText(): string` and `embeddableAttributes(): list<string>`.
- **`HasEmbedding` concern** (`OiLab\OiLaravelRaggable\Concerns\HasEmbedding`) — adds the `embedding()` morphOne relation, a `similar()` helper, `needsEmbeddingRefresh()`, and the `embeddingTextFrom()` cleaner; boots an observer that dispatches a re-embed job on save and drops the vector on (force) delete.
- **`Embedding` model** — table `raggable_embeddings`. Document header per model instance: `content_hash`, `content`, centroid `vector`, `provider`, `model`, `generated_at`; polymorphic `embeddable`; `hasMany` chunks.
- **`Chunk` model** — table `raggable_chunks`. UUID id, `embedding_id`, `content`, `vector`, `metadata`, `chunk_index`, `token_count`. Search runs at the chunk level; results set a non-persisted `similarity_distance` (cosine, 0 = identical).
- **`Embedder` contract** — `embed(array $texts): EmbeddingResult`. Default `LaravelAiEmbedder` wraps `laravel/ai`. Swap via `oi-laravel-raggable.embedder`.
- **`VectorStore` contract** — `nearest(...)`. `DatabaseVectorStore` (portable JSON + in-PHP cosine, any DB incl. SQLite) or `PgvectorStore` (native pgvector + HNSW). Selected by `oi-laravel-raggable.driver`.
- **`EmbeddingService`** — `embed()`, `embedMany()`, `vectorFor()`. Chunks text, batches embedder calls, stores header + chunks in a transaction.
- **`SimilarityService`** — `similarTo(Model, targetClass, limit, maxDistance)` and `similarToText(text, targetClass, ...)`.
- **`OiLaravelRaggable`** static resolver — every configurable class/model/setting (`embeddingModel()`, `chunkModel()`, `embedderClass()`, `vectorStoreClass()`, `driver()`, `dimensions()`, `embeddables()`, …). Never hardcode the model `::class`; resolve through it.
- Vectors are stored via `VectorCast` as a bracketed list (`[0.1,0.2,...]`) — valid JSON for the `database` driver and valid pgvector text input for the `pgvector` driver, so models are driver-agnostic.

## Public API

```php
use OiLab\OiLaravelRaggable\Concerns\HasEmbedding;
use OiLab\OiLaravelRaggable\Contracts\Embeddable;

class Document extends Model implements Embeddable
{
    use HasEmbedding;

    public function toEmbeddingText(): string
    {
        return $this->embeddingTextFrom([$this->title, $this->body]);
    }

    public function embeddableAttributes(): array
    {
        return ['title', 'body'];
    }
}

// Related content (same type by default); results carry ->similarity_distance
$related = $document->similar(limit: 5);

// Free-text retrieval (RAG entry point)
use OiLab\OiLaravelRaggable\Services\SimilarityService;
$hits = app(SimilarityService::class)->similarToText('reset my password', Document::class, limit: 8);

// Force (re)generate one model
app(\OiLab\OiLaravelRaggable\Services\EmbeddingService::class)->embed($document, force: true);
```

## Commands

```bash
php artisan raggable:embed                 # queue re-embeds for all registered models
php artisan raggable:embed documents       # only the 'documents' registry key
php artisan raggable:embed --sync          # generate inline (dev / small corpora)
php artisan raggable:embed --fresh         # ignore the content hash and regenerate
```

## Configuration

Config file `config/oi-laravel-raggable.php` (key `oi-laravel-raggable`):

- `driver` — `database` (default) or `pgvector`. Env `RAGGABLE_DRIVER`.
- `dimensions` — MUST equal the embedding model output size; set before migrating. Env `RAGGABLE_DIMENSIONS`.
- `auto_refresh` — re-embed on save. Env `RAGGABLE_AUTO_REFRESH`.
- `queue` — queue for `GenerateEmbeddingJob`. Env `RAGGABLE_QUEUE`.
- `embedder` — `Embedder` implementation class.
- `similarity.max_distance` / `similarity.limit` — Env `RAGGABLE_MAX_DISTANCE`, `RAGGABLE_LIMIT`.
- `chunk.*` — chunking budget. Env `RAGGABLE_CHUNK_*`.
- `models.embedding` / `models.chunk` — override the models.
- `stores.database` / `stores.pgvector` — override the vector stores.
- `embeddables` — `['key' => Model::class]` registry for `raggable:embed`.

## Host Integration Checklist

1. `composer require oi-lab/oi-laravel-raggable`; ensure an `Embedder` is usable (configure `laravel/ai` in `config/ai.php`, or set a custom `embedder`).
2. Set `oi-laravel-raggable.dimensions` to the embedding model's output size **before** migrating.
3. `php artisan vendor:publish --tag=oi-laravel-raggable-config` (optional) and `php artisan migrate`.
4. On each searchable model: `implements Embeddable` + `use HasEmbedding`; implement `toEmbeddingText()` and `embeddableAttributes()`.
5. Run a queue worker on the configured `queue` (or set `auto_refresh` off and index via `raggable:embed`).
6. Register models under `embeddables` and run `raggable:embed` to backfill.
7. For scale on PostgreSQL: set `RAGGABLE_DRIVER=pgvector`, migrate, then `raggable:embed --fresh`.

## Updating the AI Skill

After changing this package's behavior, update this stub and re-sync:

```bash
composer sync-ai-skills
# in host apps:
php artisan oi:skills oilab-laravel-raggable --project
```
