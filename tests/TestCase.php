<?php

namespace OiLab\OiLaravelRaggable\Tests;

use OiLab\OiLaravelAi\OiLaravelAiServiceProvider;
use OiLab\OiLaravelRaggable\Contracts\Embedder;
use OiLab\OiLaravelRaggable\OiLaravelRaggableServiceProvider;
use OiLab\OiLaravelRaggable\Tests\Fixtures\Models\AgentRun;
use OiLab\OiLaravelRaggable\Tests\Fixtures\Models\Project;
use OiLab\OiLaravelRaggable\Tests\Fixtures\Support\FakeEmbedder;
use OiLab\OiLaravelSettings\OiLaravelSettingsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends Orchestra
{
    protected FakeEmbedder $embedder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->embedder = new FakeEmbedder;
        $this->app->instance(Embedder::class, $this->embedder);
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            OiLaravelSettingsServiceProvider::class,
            OiLaravelAiServiceProvider::class,
            OiLaravelRaggableServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('oi-laravel-raggable.driver', 'database');
        $app['config']->set('oi-laravel-raggable.dimensions', 3);

        // The AI package foreign-keys ai_requests to these host models.
        $app['config']->set('oi-laravel-ai.models.project', Project::class);
        $app['config']->set('oi-laravel-ai.models.agent_run', AgentRun::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/database/migrations');
    }
}
