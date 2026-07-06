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

        if ($driver === 'pgvector') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        }

        Schema::create('raggable_embeddings', function (Blueprint $table) use ($driver): void {
            $table->id();
            $table->morphs('embeddable');
            $table->string('content_hash', 64);
            $table->longText('content');

            // Document-level centroid (mean of the chunk vectors). Filled once
            // every chunk has been embedded, so it stays nullable. On the
            // pgvector driver it is added as a native vector column below.
            if ($driver !== 'pgvector') {
                $table->json('vector')->nullable();
            }

            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['embeddable_type', 'embeddable_id']);
        });

        if ($driver === 'pgvector') {
            DB::statement("ALTER TABLE raggable_embeddings ADD COLUMN vector vector({$dimensions})");
            // HNSW index for approximate nearest-neighbor search with cosine
            // distance: no training step and better recall than ivfflat here.
            DB::statement('CREATE INDEX raggable_embeddings_vector_hnsw_idx ON raggable_embeddings USING hnsw (vector vector_cosine_ops)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('raggable_embeddings');
    }
};
