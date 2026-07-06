<?php

namespace OiLab\OiLaravelRaggable\Embedders;

use Laravel\Ai\Embeddings;
use OiLab\OiLaravelRaggable\Contracts\Embedder;
use OiLab\OiLaravelRaggable\Data\EmbeddingResult;
use OiLab\OiLaravelRaggable\OiLaravelRaggable;

/**
 * Default embedder: turns text into vectors through the Laravel AI SDK. The
 * provider and model come from the Raggable settings (falling back to
 * config/ai.php when unset), so switching the embedding model is a config or
 * setting change with no code change here.
 */
class LaravelAiEmbedder implements Embedder
{
    public function embed(array $texts): EmbeddingResult
    {
        if ($texts === []) {
            return new EmbeddingResult(vectors: []);
        }

        $response = Embeddings::for($texts)->generate(
            OiLaravelRaggable::embeddingProvider(),
            OiLaravelRaggable::embeddingModelName(),
        );

        $vectors = [];

        foreach ($response->embeddings as $vector) {
            $vectors[] = array_map(static fn ($v): float => (float) $v, (array) $vector);
        }

        return new EmbeddingResult(
            vectors: $vectors,
            provider: (string) ($response->meta->provider ?? ''),
            model: (string) ($response->meta->model ?? ''),
            promptTokens: (int) $response->tokens,
        );
    }
}
