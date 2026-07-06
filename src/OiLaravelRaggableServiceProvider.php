<?php

namespace OiLab\OiLaravelRaggable;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use OiLab\OiLaravelRaggable\Console\Commands\EmbedCommand;
use OiLab\OiLaravelRaggable\Console\Commands\InstallAiSkillCommand;
use OiLab\OiLaravelRaggable\Contracts\Embedder;
use OiLab\OiLaravelRaggable\Contracts\VectorStore;

class OiLaravelRaggableServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/oi-laravel-raggable.php',
            'oi-laravel-raggable'
        );

        $this->app->bind(Embedder::class, fn (Application $app): Embedder => $app->make(OiLaravelRaggable::embedderClass()));
        $this->app->bind(VectorStore::class, fn (Application $app): VectorStore => $app->make(OiLaravelRaggable::vectorStoreClass()));
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                EmbedCommand::class,
                InstallAiSkillCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/oi-laravel-raggable.php' => config_path('oi-laravel-raggable.php'),
            ], 'oi-laravel-raggable-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'oi-laravel-raggable-migrations');

            $this->publishes([
                __DIR__.'/../resources/stubs/ai-skill.md' => base_path('.claude/skills/oilab-laravel-raggable/SKILL.md'),
            ], 'oi-laravel-raggable-skill');
        }
    }
}
