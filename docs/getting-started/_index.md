---
title: Introduction
description: Discover OI Laravel Raggable and what it can do for your project
section: getting-started
order: 1
---

# OI Laravel Raggable

OI Laravel Raggable makes **any** Eloquent model semantically searchable. Add a contract and a trait to a model, describe the text that represents it, and the package embeds that content into vectors, keeps them fresh as the model changes, and answers similarity and RAG-retrieval queries.

## Why use this package?

Adding semantic search usually means gluing together an embedding provider, a vector column, a background refresh strategy, and a nearest-neighbor query — repeated per model and per project. This package centralizes all of it:

- One `HasEmbedding` trait makes **any** model embeddable.
- Vectors refresh **automatically** on save, and only when the content actually changed.
- The **embedder** and the **vector store** are both pluggable, so it runs on any database out of the box and scales to PostgreSQL + pgvector without touching your models.
- Long content is **chunked** so it never exceeds the provider token limit.

## The core objects

| Object | Role |
|--------|------|
| `Embeddable` | Contract a searchable model implements (`toEmbeddingText`, `embeddableAttributes`) |
| `HasEmbedding` | Trait that wires the relation, auto-refresh, and the `similar()` helper |
| `Embedding` | Document header per model instance (hash, content, centroid vector) |
| `Chunk` | A searchable slice of content with its own vector |
| `Embedder` | Turns text into vectors (pluggable; default `laravel/ai`) |
| `VectorStore` | Runs nearest-neighbor search (`database` or `pgvector`) |

## What it looks like

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

$document->similar(limit: 5); // Collection<Document> with ->similarity_distance
```

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13
- An embedder (the `laravel/ai` default, or your own)

## Next steps

Follow the [Installation](installation.md) guide to add the package to your project.
