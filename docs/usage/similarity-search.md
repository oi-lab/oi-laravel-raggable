---
title: Similarity Search
description: Find related models, retrieve from free text for RAG, and backfill a corpus
section: usage
order: 3
---

# Similarity Search

Every result carries a `similarity_distance` attribute — the cosine distance to the query (0 = identical). Results are pre-filtered by `oi-laravel-raggable.similarity.max_distance`.

## Related content

```php
// Same type by default
$related = $document->similar(limit: 5);

// Search another embeddable type
$relatedArticles = $document->similar(limit: 5, targetClass: Article::class);

$related->first()->similarity_distance; // e.g. 0.08
```

## Free-text retrieval (RAG entry point)

```php
use OiLab\OiLaravelRaggable\Services\SimilarityService;

$hits = app(SimilarityService::class)
    ->similarToText('How do I reset my password?', Document::class, limit: 8);
```

`similarToText()` embeds the query and returns the closest models — the retrieval step of a RAG pipeline. Feed `$hits` (their `content`, or the parent models) into your generation prompt.

## Generating and refreshing explicitly

```php
use OiLab\OiLaravelRaggable\Services\EmbeddingService;

$service = app(EmbeddingService::class);

$service->embed($document);              // generate or refresh (skips if hash unchanged)
$service->embed($document, force: true); // always regenerate
$service->embedMany($documents);         // returns the number written
```

## Backfilling

Register your embeddable models, then run the command:

```php
// config/oi-laravel-raggable.php
'embeddables' => [
    'documents' => \App\Models\Document::class,
    'articles'  => \App\Models\Article::class,
],
```

```bash
php artisan raggable:embed                 # queue re-embeds for every registered model
php artisan raggable:embed documents       # only the 'documents' key
php artisan raggable:embed --sync          # generate inline (dev / small corpora)
php artisan raggable:embed --fresh         # ignore the content hash and regenerate
php artisan raggable:embed --chunk=250     # models fetched per database chunk
```

Without `--sync`, the command queues `GenerateEmbeddingJob`s — run a worker on the configured queue to process them.
