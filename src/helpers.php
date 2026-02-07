<?php

declare(strict_types=1);

use Daikazu\LaravelLlmReady\Services\DiscoveryService;

if (! function_exists('llmReadyUrl')) {
    /**
     * Get the LLM-ready markdown URL for the current request.
     */
    function llmReadyUrl(): ?string
    {
        return app(DiscoveryService::class)->markdownUrl();
    }
}
