<?php

namespace Daikazu\LaravelLlmReady\Tests;

use Daikazu\LaravelLlmReady\LaravelLlmReadyServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Daikazu\\LaravelLlmReady\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelLlmReadyServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        config()->set('app.url', 'https://example.com');

        // Configure LLM Ready exclusions for testing
        config()->set('llm-ready.exclude_patterns', ['/admin/*', '/api/*']);
    }

    protected function defineRoutes($router)
    {
        // Define test routes for feature tests
        Route::get('/about', function () {
            return '<html><head><title>About Us</title></head><body><main><h1>About Us</h1><p>Welcome to our site.</p></main></body></html>';
        });

        Route::get('/contact', function () {
            return '<html><head><title>Contact</title></head><body><main><h1>Contact</h1><p>Get in touch.</p></main></body></html>';
        });

        Route::get('/admin/dashboard', function () {
            return '<html><head><title>Admin Dashboard</title></head><body><main><h1>Dashboard</h1></main></body></html>';
        });
    }
}
