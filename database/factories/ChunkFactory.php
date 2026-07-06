<?php

namespace OiLab\OiLaravelRaggable\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use OiLab\OiLaravelRaggable\Models\Chunk;
use OiLab\OiLaravelRaggable\Models\Embedding;

/**
 * @extends Factory<Chunk>
 */
class ChunkFactory extends Factory
{
    protected $model = Chunk::class;

    public function definition(): array
    {
        $content = $this->faker->sentence();

        return [
            'embedding_id' => Embedding::factory(),
            'content' => $content,
            'vector' => array_map(
                fn (): float => $this->faker->randomFloat(6, -1, 1),
                range(1, 3),
            ),
            'metadata' => null,
            'chunk_index' => 0,
            'token_count' => (int) ceil(mb_strlen($content) / 3.5),
        ];
    }
}
