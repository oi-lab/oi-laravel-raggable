<?php

namespace OiLab\OiLaravelRaggable\Services;

/**
 * Splits long embedding source text into overlapping chunks that each stay
 * below the provider token limit. Token counts are estimated locally
 * (~ characters / chars_per_token) since providers only report usage after the
 * call. Splitting also keeps each vector focused on a narrower span of text,
 * which improves similarity precision for long documents.
 */
class TextChunker
{
    /**
     * Break the given text into ordered chunks.
     *
     * @return list<array{content: string, token_count: int, index: int}>
     */
    public function chunk(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            return [];
        }

        $maxTokens = max(1, (int) config('oi-laravel-raggable.chunk.max_tokens', 1000));
        $overlapTokens = max(0, (int) config('oi-laravel-raggable.chunk.overlap_tokens', 120));

        $current = [];
        $currentTokens = 0;
        $chunks = [];

        foreach ($this->atomize($text) as $atom) {
            if ($current !== [] && $currentTokens + $atom['tokens'] > $maxTokens) {
                $chunks[] = $this->materialize($current);
                $current = $this->overlapTail($current, $overlapTokens);
                $currentTokens = array_sum(array_column($current, 'tokens'));
            }

            $current[] = $atom;
            $currentTokens += $atom['tokens'];
        }

        if ($current !== []) {
            $chunks[] = $this->materialize($current);
        }

        return array_values(array_map(
            fn (array $chunk, int $index): array => [...$chunk, 'index' => $index],
            $chunks,
            array_keys($chunks),
        ));
    }

    /**
     * Estimate the number of provider tokens in a piece of text.
     */
    public function estimateTokens(string $text): int
    {
        $charsPerToken = (float) config('oi-laravel-raggable.chunk.chars_per_token', 3.5);

        if ($charsPerToken <= 0) {
            $charsPerToken = 3.5;
        }

        return (int) max(1, (int) ceil(mb_strlen(trim($text)) / $charsPerToken));
    }

    /**
     * Split the text into sentence-sized atoms, each guaranteed to fit under the
     * hard token limit so a single oversized sentence never blows the provider
     * request.
     *
     * @return list<array{text: string, tokens: int}>
     */
    private function atomize(string $text): array
    {
        $hardLimit = max(1, (int) config('oi-laravel-raggable.chunk.hard_limit_tokens', 6000));
        $pieces = preg_split('/(?<=[.!?…。])\s+|\n+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [$text];

        $atoms = [];

        foreach ($pieces as $piece) {
            $piece = trim($piece);

            if ($piece === '') {
                continue;
            }

            foreach ($this->enforceHardLimit($piece, $hardLimit) as $segment) {
                $atoms[] = ['text' => $segment, 'tokens' => $this->estimateTokens($segment)];
            }
        }

        return $atoms;
    }

    /**
     * Hard-split a piece that exceeds the token limit into fixed character
     * windows, used as a last resort when no natural boundary is available.
     *
     * @return list<string>
     */
    private function enforceHardLimit(string $text, int $hardLimit): array
    {
        if ($this->estimateTokens($text) <= $hardLimit) {
            return [$text];
        }

        $charsPerToken = (float) config('oi-laravel-raggable.chunk.chars_per_token', 3.5);
        $window = max(1, (int) floor($hardLimit * ($charsPerToken > 0 ? $charsPerToken : 3.5)));

        $segments = [];
        $length = mb_strlen($text);

        for ($offset = 0; $offset < $length; $offset += $window) {
            $segments[] = trim(mb_substr($text, $offset, $window));
        }

        return array_values(array_filter($segments, fn (string $segment): bool => $segment !== ''));
    }

    /**
     * Join accumulated atoms into a chunk payload.
     *
     * @param  list<array{text: string, tokens: int}>  $atoms
     * @return array{content: string, token_count: int}
     */
    private function materialize(array $atoms): array
    {
        $content = trim(implode(' ', array_column($atoms, 'text')));

        return ['content' => $content, 'token_count' => $this->estimateTokens($content)];
    }

    /**
     * Take the trailing atoms of a chunk to prepend as overlap to the next one,
     * staying within the overlap token budget. Atoms larger than the budget are
     * not carried over.
     *
     * @param  list<array{text: string, tokens: int}>  $atoms
     * @return list<array{text: string, tokens: int}>
     */
    private function overlapTail(array $atoms, int $overlapTokens): array
    {
        if ($overlapTokens <= 0) {
            return [];
        }

        $tail = [];
        $tokens = 0;

        foreach (array_reverse($atoms) as $atom) {
            if ($atom['tokens'] > $overlapTokens || $tokens + $atom['tokens'] > $overlapTokens) {
                break;
            }

            array_unshift($tail, $atom);
            $tokens += $atom['tokens'];
        }

        return $tail;
    }
}
