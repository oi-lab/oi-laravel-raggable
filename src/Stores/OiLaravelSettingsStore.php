<?php

namespace OiLab\OiLaravelRaggable\Stores;

use OiLab\OiLaravelRaggable\Contracts\SettingStore;
use OiLab\OiLaravelSettings\SettingsManager;

/**
 * {@see SettingStore} adapter backed by oi-lab/oi-laravel-settings.
 *
 * Wired automatically when that package is installed, so the runtime-tunable
 * Raggable settings (cosine threshold, result limit, auto-refresh, embedding
 * model) are persisted in the shared, scoped, typed setting store. The Raggable
 * scope maps directly onto a settings scope (null = global).
 */
class OiLaravelSettingsStore implements SettingStore
{
    public function __construct(protected SettingsManager $settings) {}

    public function get(string $key, ?string $scope = null): mixed
    {
        return $this->settings->get($key, scope: $scope);
    }

    public function set(string $key, mixed $value, string $label, string $type = 'string', ?string $scope = null): void
    {
        $this->settings->set($key, $value, type: $type, label: $label, scope: $scope);
    }

    public function forget(string $key, ?string $scope = null): void
    {
        $this->settings->delete($key, scope: $scope);
    }
}
