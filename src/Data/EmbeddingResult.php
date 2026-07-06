<?php

namespace OiLab\OiLaravelRaggable\Data;

use Spatie\LaravelData\Data;

/**
 * The result of an Embedder call: one vector per input text, plus the provider
 * and model that produced them.
 */
class EmbeddingResult extends Data
{
    /**
     * @param  list<list<float>>  $vectors
     */
    public function __construct(
        public readonly array $vectors,
        public readonly string $provider = '',
        public readonly string $model = '',
    ) {}

    /**
     * The first vector, or an empty list when nothing was embedded.
     *
     * @return list<float>
     */
    public function first(): array
    {
        return $this->vectors[0] ?? [];
    }
}
