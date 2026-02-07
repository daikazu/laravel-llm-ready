<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady;

use Daikazu\LaravelLlmReady\Commands\ClearLlmCacheCommand;
use Daikazu\LaravelLlmReady\Contracts\ContentExtractorInterface;
use Daikazu\LaravelLlmReady\Extractors\DefaultContentExtractor;
use Daikazu\LaravelLlmReady\Http\Controllers\LlmsTxtController;
use Daikazu\LaravelLlmReady\Http\Middleware\InterceptMarkdownRequests;
use Daikazu\LaravelLlmReady\Http\Middleware\RewriteMarkdownExtension;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelLlmReadyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('llm-ready')
            ->hasConfigFile()
            ->hasCommand(ClearLlmCacheCommand::class);
    }

    public function packageRegistered(): void
    {
        // Bind the content extractor interface to the configured implementation
        $this->app->bind(ContentExtractorInterface::class, function ($app) {
            $extractorClass = config('llm-ready.extractor', DefaultContentExtractor::class);

            if (! class_exists($extractorClass)) {
                throw new InvalidArgumentException(
                    "Invalid LLM Ready extractor class: {$extractorClass}"
                );
            }

            return $app->make($extractorClass);
        });
    }

    public function packageBooted(): void
    {
        if (! config('llm-ready.enabled', true)) {
            return;
        }

        /** @var Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);

        // Register global middleware to rewrite .md URLs before routing
        $kernel->pushMiddleware(RewriteMarkdownExtension::class);

        // Register web middleware for markdown response conversion
        $kernel->appendMiddlewareToGroup('web', InterceptMarkdownRequests::class);

        // Register llms.txt route
        if (config('llm-ready.llms_txt.enabled', true)) {
            Route::middleware('web')
                ->get('/llms.txt', LlmsTxtController::class)
                ->name('llm-ready.llms-txt');
        }
    }
}
