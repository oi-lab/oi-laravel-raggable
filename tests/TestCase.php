<?php

namespace OiLab\OiLaravelRaggable\Tests;

use OiLab\OiLaravelRaggable\Contracts\Embedder;
use OiLab\OiLaravelRaggable\OiLaravelRaggableServiceProvider;
use OiLab\OiLaravelRaggable\Tests\Fixtures\Support\FakeEmbedder;
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
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/database/migrations');
    }
}
