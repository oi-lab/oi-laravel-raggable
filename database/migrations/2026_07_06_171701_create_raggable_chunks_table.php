<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = config('oi-laravel-raggable.driver', 'database');
        $dimensions = (int) config('oi-laravel-raggable.dimensions', 1024);

        Schema::create('raggable_chunks', function (Blueprint $table) use ($driver): void {
            $table->uuid('id')->primary();
            // The embeddings table uses a bigint auto-increment primary key, so
            // the foreign key must be a bigint (not a UUID) to match it.
            $table->foreignId('embedding_id')->constrained('raggable_embeddings')->cascadeOnDelete();
            $table->longText('content');
            $table->json('metadata')->nullable();
            $table->integer('chunk_index')->default(0);
            $table->integer('token_count')->nullable();

            // Named "vector" rather than "embedding" to avoid colliding with the
            // belongsTo embedding() relation on the Chunk model.
            if ($driver !== 'pgvector') {
                $table->json('vector')->nullable();
            }

            $table->timestamps();

            $table->index(['embedding_id', 'chunk_index']);
        });

        if ($driver === 'pgvector') {
            DB::statement("ALTER TABLE raggable_chunks ADD COLUMN vector vector({$dimensions})");
            // HNSW index: similarity runs over the per-chunk vectors.
            DB::statement('CREATE INDEX raggable_chunks_vector_hnsw_idx ON raggable_chunks USING hnsw (vector vector_cosine_ops)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('raggable_chunks');
    }
};
