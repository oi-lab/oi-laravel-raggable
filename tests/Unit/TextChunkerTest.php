<?php

use OiLab\OiLaravelRaggable\Services\TextChunker;

it('returns no chunks for empty text', function () {
    expect((new TextChunker)->chunk('   '))->toBe([]);
});

it('keeps short text in a single chunk', function () {
    $chunks = (new TextChunker)->chunk('One short sentence.');

    expect($chunks)->toHaveCount(1)
        ->and($chunks[0]['index'])->toBe(0)
        ->and($chunks[0]['content'])->toBe('One short sentence.');
});

it('splits long text into ordered, indexed chunks', function () {
    config()->set('oi-laravel-raggable.chunk.max_tokens', 5);
    config()->set('oi-laravel-raggable.chunk.overlap_tokens', 0);

    $text = collect(range(1, 20))
        ->map(fn (int $i): string => "This is sentence number {$i}.")
        ->implode(' ');

    $chunks = (new TextChunker)->chunk($text);

    expect(count($chunks))->toBeGreaterThan(1)
        ->and(array_column($chunks, 'index'))->toBe(range(0, count($chunks) - 1));
});

it('estimates tokens from text length', function () {
    config()->set('oi-laravel-raggable.chunk.chars_per_token', 4);

    expect((new TextChunker)->estimateTokens('12345678'))->toBe(2);
});
