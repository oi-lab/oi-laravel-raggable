<?php

namespace OiLab\OiLaravelRaggable\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use OiLab\OiLaravelRaggable\Models\Embedding;

/**
 * @extends Factory<Embedding>
 */
class EmbeddingFactory extends Factory
{
    protected $model = Embedding::class;

    public function definition(): array
    {
        $content = $this->faker->paragraph();

        return [
            'embeddable_type' => 'embeddable',
            'embeddable_id' => $this->faker->unique()->numberBetween(1, 100000),
            'content_hash' => hash('sha256', $content),
            'content' => $content,
            'vector' => $this->vector(),
            'provider' => 'fake',
            'model' => 'fake-embed',
            'generated_at' => now(),
        ];
    }

    /**
     * @return list<float>
     */
    private function vector(): array
    {
        return array_map(
            fn (): float => $this->faker->randomFloat(6, -1, 1),
            range(1, 3),
        );
    }
}
