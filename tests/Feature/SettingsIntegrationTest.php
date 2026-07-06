<?php

use OiLab\OiLaravelRaggable\Contracts\SettingStore;
use OiLab\OiLaravelRaggable\OiLaravelRaggable;
use OiLab\OiLaravelRaggable\Stores\OiLaravelSettingsStore;

it('auto-detects the oi-laravel-settings store adapter', function () {
    expect(app(SettingStore::class))->toBeInstanceOf(OiLaravelSettingsStore::class);
});

it('falls back to config when a setting is unset', function () {
    config()->set('oi-laravel-raggable.similarity.max_distance', 0.42);

    expect(OiLaravelRaggable::maxDistance())->toBe(0.42);
});

it('reads a tunable value from the setting store, overriding config', function () {
    config()->set('oi-laravel-raggable.similarity.max_distance', 0.42);

    app(SettingStore::class)->set('similarity.max_distance', 0.9, 'Max distance', 'float');

    expect(OiLaravelRaggable::maxDistance())->toBe(0.9);
});

it('resolves the embedding model name from the setting store', function () {
    expect(OiLaravelRaggable::embeddingModelName())->toBeNull();

    app(SettingStore::class)->set('embedding.model', 'text-embedding-3-small', 'Model', 'string');

    expect(OiLaravelRaggable::embeddingModelName())->toBe('text-embedding-3-small');
});

it('reads auto_refresh from the setting store', function () {
    app(SettingStore::class)->set('auto_refresh', false, 'Auto refresh', 'boolean');

    expect(OiLaravelRaggable::autoRefresh())->toBeFalse();
});
