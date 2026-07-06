<?php

namespace OiLab\OiLaravelRaggable\Tests\Fixtures\Support;

use OiLab\OiLaravelRaggable\Contracts\Embedder;
use OiLab\OiLaravelRaggable\Data\EmbeddingResult;

/**
 * Deterministic test embedder: each axis counts occurrences of a keyword, so
 * texts about the same topic land close together without calling a real
 * provider. It also records how many times it was invoked, to assert that the
 * content-hash short-circuit avoids needless calls.
 */
class FakeEmbedder implements Embedder
{
    public int $calls = 0;

    /** @var list<string> */
    public array $embeddedTexts = [];

    /** @var list<string> */
    private array $axes = ['cat', 'dog', 'bird'];

    public function embed(array $texts): EmbeddingResult
    {
        $this->calls++;

        $vectors = [];

        foreach ($texts as $text) {
            $this->embeddedTexts[] = $text;
            $vectors[] = $this->vector($text);
        }

        return new EmbeddingResult(vectors: $vectors, provider: 'fake', model: 'fake-embed');
    }

    /**
     * @return list<float>
     */
    private function vector(string $text): array
    {
        $lower = mb_strtolower($text);

        $vector = array_map(
            fn (string $axis): float => (float) substr_count($lower, $axis),
            $this->axes,
        );

        // Avoid a zero vector so cosine distance stays defined.
        if (array_sum($vector) === 0.0) {
            return array_fill(0, count($this->axes), 0.001);
        }

        return $vector;
    }
}
