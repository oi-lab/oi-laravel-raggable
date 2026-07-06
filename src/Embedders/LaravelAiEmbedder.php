<?php

namespace OiLab\OiLaravelRaggable\Embedders;

use Laravel\Ai\Embeddings;
use OiLab\OiLaravelRaggable\Contracts\Embedder;
use OiLab\OiLaravelRaggable\Data\EmbeddingResult;

/**
 * Default embedder: turns text into vectors through the Laravel AI SDK. The
 * provider and model are resolved from config/ai.php (`default_for_embeddings`),
 * so switching providers is a config change with no code change here.
 */
class LaravelAiEmbedder implements Embedder
{
    public function embed(array $texts): EmbeddingResult
    {
        if ($texts === []) {
            return new EmbeddingResult(vectors: []);
        }

        $response = Embeddings::for($texts)->generate();

        $vectors = [];

        foreach ($response->embeddings as $vector) {
            $vectors[] = array_map(static fn ($v): float => (float) $v, (array) $vector);
        }

        return new EmbeddingResult(
            vectors: $vectors,
            provider: (string) ($response->meta->provider ?? ''),
            model: (string) ($response->meta->model ?? ''),
        );
    }
}
