<?php

namespace OiLab\OiLaravelRaggable\Contracts;

/**
 * Implemented by any Eloquent model that should be semantically searchable.
 * Combine with the HasEmbedding concern to wire the relation and auto-refresh.
 */
interface Embeddable
{
    /**
     * The text representation of the model used to generate its embedding.
     */
    public function toEmbeddingText(): string;

    /**
     * The attributes whose change should trigger an embedding refresh.
     *
     * @return list<string>
     */
    public function embeddableAttributes(): array;
}
