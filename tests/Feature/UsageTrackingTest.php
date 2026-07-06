<?php

use OiLab\OiLaravelAi\Models\AiRequest;
use OiLab\OiLaravelRaggable\Tests\Fixtures\Models\Document;

it('records embedding usage through oi-laravel-ai', function () {
    Document::create(['title' => 'Cats', 'body' => 'cat cat cat']);

    $request = AiRequest::query()->first();

    expect(AiRequest::count())->toBeGreaterThan(0)
        ->and($request->tokens_input)->toBeGreaterThan(0)
        ->and($request->prompt_type)->toStartWith('embedding:');
});

it('does not record usage when tracking is disabled', function () {
    config()->set('oi-laravel-raggable.track_usage', false);

    Document::create(['title' => 'Dogs', 'body' => 'dog dog']);

    expect(AiRequest::count())->toBe(0);
});
