<?php

use OiLab\OiLaravelRaggable\Embedders\LaravelAiEmbedder;
use OiLab\OiLaravelRaggable\Models\Chunk;
use OiLab\OiLaravelRaggable\Models\Embedding;
use OiLab\OiLaravelRaggable\VectorStores\DatabaseVectorStore;
use OiLab\OiLaravelRaggable\VectorStores\PgvectorStore;

return [

    /*
    |--------------------------------------------------------------------------
    | User model
    |--------------------------------------------------------------------------
    */

    'user_model' => 'App\Models\User',

    /*
    |--------------------------------------------------------------------------
    | Storage driver
    |--------------------------------------------------------------------------
    |
    | How embedding vectors are stored and searched.
    |
    | - "database": portable JSON storage with an in-PHP cosine search. Works on
    |   any database driver (including SQLite), needs no extension, and is the
    |   right default for small to medium corpora.
    | - "pgvector": native Postgres `vector` columns with HNSW indexes for fast
    |   approximate nearest-neighbor search at scale. Requires PostgreSQL with
    |   the pgvector extension available.
    |
    */

    'driver' => env('RAGGABLE_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Vector dimensions
    |--------------------------------------------------------------------------
    |
    | MUST equal the output size of the configured embedding model, and MUST be
    | set before migrating (the pgvector migration reads it):
    |
    |   mistral-embed            -> 1024
    |   text-embedding-3-small   -> 1536
    |   text-embedding-3-large   -> 3072
    |   voyage-3 / voyage-3-lite -> 1024
    |
    */

    'dimensions' => (int) env('RAGGABLE_DIMENSIONS', 1024),

    /*
    |--------------------------------------------------------------------------
    | Automatic refresh
    |--------------------------------------------------------------------------
    |
    | When true, saving an embeddable model whose embeddable attributes changed
    | dispatches a job to refresh its vector. Turn off to control indexing
    | entirely through the `raggable:embed` command.
    |
    */

    'auto_refresh' => (bool) env('RAGGABLE_AUTO_REFRESH', true),

    /*
    |--------------------------------------------------------------------------
    | Queue
    |--------------------------------------------------------------------------
    |
    | The queue the embedding-generation job is pushed onto.
    |
    */

    'queue' => env('RAGGABLE_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Embedder
    |--------------------------------------------------------------------------
    |
    | The class that turns text into vectors. Must implement the Embedder
    | contract. The default wraps the Laravel AI SDK (provider and model are
    | resolved from config/ai.php). Swap it to plug any provider.
    |
    */

    'embedder' => LaravelAiEmbedder::class,

    /*
    |--------------------------------------------------------------------------
    | Similarity
    |--------------------------------------------------------------------------
    */

    'similarity' => [
        // Cosine distance threshold above which two contents are not considered
        // related (0 = identical, 1 = orthogonal). Calibrate after the first
        // real backfill by inspecting result quality.
        'max_distance' => (float) env('RAGGABLE_MAX_DISTANCE', 0.5),
        'limit' => (int) env('RAGGABLE_LIMIT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chunking
    |--------------------------------------------------------------------------
    |
    | Long content is split into overlapping chunks before embedding so a single
    | record never exceeds the provider token limit and each vector stays
    | focused. Token counts are estimated locally (~ characters / divisor).
    |
    */

    'chunk' => [
        'max_tokens' => (int) env('RAGGABLE_CHUNK_MAX_TOKENS', 1000),
        'overlap_tokens' => (int) env('RAGGABLE_CHUNK_OVERLAP_TOKENS', 120),
        'hard_limit_tokens' => (int) env('RAGGABLE_CHUNK_HARD_LIMIT_TOKENS', 6000),
        'chars_per_token' => (float) env('RAGGABLE_CHUNK_CHARS_PER_TOKEN', 3.5),
        'max_inputs_per_request' => (int) env('RAGGABLE_MAX_INPUTS_PER_REQUEST', 128),
        'request_token_budget' => (int) env('RAGGABLE_REQUEST_TOKEN_BUDGET', 16000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | The Eloquent models backing the polymorphic embeddings/chunks tables.
    | Override to extend them in the host application.
    |
    */

    'models' => [
        'embedding' => Embedding::class,
        'chunk' => Chunk::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Vector stores
    |--------------------------------------------------------------------------
    |
    | The store implementation used for each driver. Both implement the
    | VectorStore contract.
    |
    */

    'stores' => [
        'database' => DatabaseVectorStore::class,
        'pgvector' => PgvectorStore::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Embeddable registry
    |--------------------------------------------------------------------------
    |
    | Maps a short key to each embeddable model class. `raggable:embed` uses this
    | registry to know what to backfill; omit the argument to embed everything.
    |
    |   'documents' => \App\Models\Document::class,
    |
    */

    'embeddables' => [
        //
    ],

];
