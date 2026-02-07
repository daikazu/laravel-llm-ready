<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Http\Middleware;

use Closure;
use Daikazu\LaravelLlmReady\Services\DiscoveryService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class AddLinkHeader
{
    public function __construct(
        private DiscoveryService $discovery,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Skip if already a markdown response
        if ($response->headers->get('X-LLM-Ready') === 'true') {
            return $response;
        }

        // Skip non-HTML responses
        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        // Skip error responses
        if ($response->getStatusCode() >= 400) {
            return $response;
        }

        $linkHeader = $this->discovery->linkHeaderValue($request);

        if ($linkHeader !== null) {
            $response->headers->set('Link', $linkHeader);
        }

        return $response;
    }
}
