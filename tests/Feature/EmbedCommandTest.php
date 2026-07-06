<?php

use OiLab\OiLaravelRaggable\Models\Embedding;
use OiLab\OiLaravelRaggable\Tests\Fixtures\Models\Document;

beforeEach(function () {
    config()->set('oi-laravel-raggable.auto_refresh', false);
    config()->set('oi-laravel-raggable.embeddables', ['documents' => Document::class]);
});

it('backfills embeddings for registered models synchronously', function () {
    Document::create(['title' => 'Cats', 'body' => 'cat cat']);
    Document::create(['title' => 'Dogs', 'body' => 'dog dog']);

    expect(Embedding::count())->toBe(0);

    $this->artisan('raggable:embed', ['--sync' => true])
        ->assertSuccessful();

    expect(Embedding::count())->toBe(2);
});

it('fails on an unknown target', function () {
    $this->artisan('raggable:embed', ['targets' => ['unknown']])
        ->assertFailed();
});

it('warns when nothing is registered', function () {
    config()->set('oi-laravel-raggable.embeddables', []);

    $this->artisan('raggable:embed')
        ->expectsOutputToContain('No embeddable models registered')
        ->assertSuccessful();
});
