<?php

namespace OiLab\OiLaravelRaggable\Contracts;

/**
 * Bridge to the host application's settings storage. The package never persists
 * settings itself; it delegates to the implementation bound through
 * `config('oi-laravel-raggable.setting_store')` (auto-detected to the
 * oi-lab/oi-laravel-settings adapter when that package is installed).
 */
interface SettingStore
{
    /**
     * Resolve a setting value, preferring the scoped value and falling back to
     * the global one. Returns null when unset so callers fall back to config.
     */
    public function get(string $key, ?string $scope = null): mixed;

    /**
     * Create or update a setting.
     */
    public function set(string $key, mixed $value, string $label, string $type = 'string', ?string $scope = null): void;

    /**
     * Remove a setting so reads fall back to config.
     */
    public function forget(string $key, ?string $scope = null): void;
}
