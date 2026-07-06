<?php

namespace OiLab\OiLaravelRaggable\Contracts;

use OiLab\OiLaravelRaggable\Data\EmbeddingResult;

/**
 * Turns text into vectors. Implement this to plug any embedding provider
 * (Laravel AI, a raw HTTP client, a local model, or a fake for tests).
 */
interface Embedder
{
    /**
     * Embed a batch of texts, returning one vector per input in order.
     *
     * @param  list<string>  $texts
     */
    public function embed(array $texts): EmbeddingResult;
}
