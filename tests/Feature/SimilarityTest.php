<?php

use OiLab\OiLaravelRaggable\Services\SimilarityService;
use OiLab\OiLaravelRaggable\Tests\Fixtures\Models\Document;

beforeEach(function () {
    $this->cat1 = Document::create(['title' => 'Cats one', 'body' => 'cat cat cat']);
    $this->cat2 = Document::create(['title' => 'Cats two', 'body' => 'cat cat']);
    $this->dog = Document::create(['title' => 'Dogs', 'body' => 'dog dog dog']);
});

it('finds models similar to a source model, excluding the source itself', function () {
    $similar = $this->cat1->similar();

    expect($similar->pluck('id')->all())->toBe([$this->cat2->id])
        ->and($similar->first()->similarity_distance)->toBeLessThan(0.5);
});

it('finds models similar to an ad hoc text query', function () {
    $hits = app(SimilarityService::class)->similarToText('a happy cat', Document::class);

    expect($hits->pluck('id')->sort()->values()->all())
        ->toBe(collect([$this->cat1->id, $this->cat2->id])->sort()->values()->all())
        ->and($hits->pluck('id')->all())->not->toContain($this->dog->id);
});

it('returns an empty collection for a blank query', function () {
    $hits = app(SimilarityService::class)->similarToText('   ', Document::class);

    expect($hits)->toBeEmpty();
});

it('ranks the closest model first', function () {
    $hits = app(SimilarityService::class)->similarToText('dog', Document::class);

    expect($hits->first()->id)->toBe($this->dog->id);
});
