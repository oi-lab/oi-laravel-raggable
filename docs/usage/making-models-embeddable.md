---
title: Making Models Embeddable
description: Implement the Embeddable contract, use the HasEmbedding trait, and understand automatic refresh
section: usage
order: 2
---

# Making Models Embeddable

Any Eloquent model becomes searchable by implementing `Embeddable` and using `HasEmbedding`.

```php
use Illuminate\Database\Eloquent\Model;
use OiLab\OiLaravelRaggable\Concerns\HasEmbedding;
use OiLab\OiLaravelRaggable\Contracts\Embeddable;

class Document extends Model implements Embeddable
{
    use HasEmbedding;

    public function toEmbeddingText(): string
    {
        return $this->embeddingTextFrom([$this->title, $this->summary, $this->body]);
    }

    public function embeddableAttributes(): array
    {
        return ['title', 'summary', 'body'];
    }
}
```

## `toEmbeddingText()`

Returns the text that represents the model. The `embeddingTextFrom()` helper joins fragments, strips HTML tags, normalizes whitespace, and drops empty parts — pass it the fields that carry meaning.

## `embeddableAttributes()`

The attributes whose change should trigger a re-embed. **Keep this list tight** — include only meaning-changing columns so you avoid needless re-embeds (and provider cost) when unrelated fields change.

## Automatic refresh

With `auto_refresh` enabled (the default), saving the model dispatches a `GenerateEmbeddingJob` on the configured queue whenever:

- the model was just created, or
- one of its `embeddableAttributes()` changed.

The job re-embeds the model, but the `EmbeddingService` skips the provider call when the content hash is unchanged, so unchanged content is free. Run a queue worker on the configured queue (default `default`), or turn `auto_refresh` off and index explicitly with [`raggable:embed`](similarity-search.md#backfilling).

## Deletion

Deleting a model drops its embedding and chunks. If the model uses `SoftDeletes`, the vector is kept until a **force** delete, so restored models stay searchable.

## Helpers added by the trait

| Method | Description |
|--------|-------------|
| `embedding()` | The `morphOne` relation to the `Embedding` header |
| `similar(int $limit, ?float $maxDistance, ?string $targetClass)` | Models most similar to this one |
| `needsEmbeddingRefresh()` | Whether the stored hash differs from the current text |
| `embeddingContentHash()` | SHA-256 of the current embedding text |
