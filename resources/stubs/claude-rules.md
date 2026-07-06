# OI Laravel Raggable

Use the `oi-laravel-raggable` package to make any Eloquent model semantically searchable. A model
becomes embeddable by implementing `OiLab\OiLaravelRaggable\Contracts\Embeddable` and using the
`OiLab\OiLaravelRaggable\Concerns\HasEmbedding` trait (declare `toEmbeddingText()` and
`embeddableAttributes()`). Saving the model re-embeds it on a queue when embeddable attributes change;
query related content with `$model->similar()` or free text with `SimilarityService::similarToText()`.
The embedder (`Embedder` contract, default `laravel/ai`) and vector store (`database` or `pgvector`
driver) are both pluggable; resolve models/classes through the `OiLaravelRaggable` static resolver.
Backfill with `php artisan raggable:embed`.

- IMPORTANT: Activate `oilab-laravel-raggable` when adding semantic/vector/similarity search, embeddings,
  "related content"/"you may also like", or RAG retrieval to a Laravel model, when making a model
  embeddable, when tuning chunking or the cosine `max_distance`, or when switching the storage driver to
  pgvector in this application.
