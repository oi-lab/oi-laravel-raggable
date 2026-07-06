---
title: Installation
description: How to install OI Laravel Raggable, set the vector dimensions, and migrate
section: getting-started
order: 2
---

# Installation

## Via Composer

```bash
composer require oi-lab/oi-laravel-raggable
```

The package auto-discovers its service provider — no manual registration required.

## Configure an embedder

By default the package embeds through `laravel/ai`. Configure a provider and model in `config/ai.php` (Mistral, OpenAI, Voyage, …) and set the matching API key in `.env`. To use a different provider entirely, implement the [`Embedder` contract](../advanced/drivers.md) and point `oi-laravel-raggable.embedder` at it.

## Set the vector dimensions (before migrating)

`oi-laravel-raggable.dimensions` must equal the **exact** output size of your embedding model. The migration reads it to size the vector column, so set it first.

| Model | Dimensions |
|-------|------------|
| `mistral-embed` | 1024 |
| `text-embedding-3-small` | 1536 |
| `text-embedding-3-large` | 3072 |
| `voyage-3` / `voyage-3-lite` | 1024 |

```dotenv
RAGGABLE_DIMENSIONS=1024
```

## Publish the configuration (optional)

```bash
php artisan vendor:publish --tag=oi-laravel-raggable-config
```

The config is also merged automatically, so defaults exist without publishing.

## Migrate

```bash
php artisan migrate
```

This creates the polymorphic `raggable_embeddings` and `raggable_chunks` tables. On the default `database` driver they are portable across SQLite/MySQL/Postgres; on the `pgvector` driver the migration also enables the extension and creates native `vector` columns with HNSW indexes.

## Next steps

- [Make a model embeddable](../usage/making-models-embeddable.md)
- [Search for similar content](../usage/similarity-search.md)
