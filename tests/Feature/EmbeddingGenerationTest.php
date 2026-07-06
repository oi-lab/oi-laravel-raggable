<?php

use OiLab\OiLaravelRaggable\Models\Chunk;
use OiLab\OiLaravelRaggable\Models\Embedding;
use OiLab\OiLaravelRaggable\Tests\Fixtures\Models\Document;

it('generates an embedding and chunks when an embeddable model is created', function () {
    $document = Document::create(['title' => 'Cats', 'body' => 'The cat sat. A cat purrs.']);

    $document->refresh();

    expect($document->embedding)->not->toBeNull()
        ->and($document->embedding->content_hash)->toBe(hash('sha256', $document->toEmbeddingText()))
        ->and($document->embedding->provider)->toBe('fake')
        ->and(Chunk::where('embedding_id', $document->embedding->id)->count())->toBeGreaterThan(0)
        ->and($document->embedding->vector)->toBeArray();
});

it('skips regeneration when a non-embeddable attribute changes', function () {
    $document = Document::create(['title' => 'Cats', 'body' => 'cat cat']);
    $callsAfterCreate = $this->embedder->calls;

    $document->update(['status' => 'archived']);

    expect($this->embedder->calls)->toBe($callsAfterCreate);
});

it('regenerates when an embeddable attribute changes', function () {
    $document = Document::create(['title' => 'Cats', 'body' => 'cat cat']);
    $callsAfterCreate = $this->embedder->calls;

    $document->update(['body' => 'cat cat cat dog']);

    expect($this->embedder->calls)->toBe($callsAfterCreate + 1)
        ->and($document->fresh()->embedding->content_hash)->toBe(hash('sha256', $document->toEmbeddingText()));
});

it('does not embed a model with empty text', function () {
    $document = Document::create(['title' => '', 'body' => '']);

    expect($document->embedding()->exists())->toBeFalse()
        ->and($this->embedder->calls)->toBe(0);
});

it('drops the embedding when the model is deleted', function () {
    $document = Document::create(['title' => 'Cats', 'body' => 'cat cat']);
    expect(Embedding::count())->toBe(1);

    $document->delete();

    expect(Embedding::count())->toBe(0)
        ->and(Chunk::count())->toBe(0);
});
