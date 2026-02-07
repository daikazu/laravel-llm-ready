<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Http\Middleware;

use Closure;
use Daikazu\LaravelLlmReady\Services\MarkdownConverterService;
use Daikazu\LaravelLlmReady\Services\RouteFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to convert responses to markdown.
 * Triggered by ?format=md query parameter or by the
 * RewriteMarkdownExtension middleware (for .md URLs).
 */
final readonly class InterceptMarkdownRequests
{
    public function __construct(
        private MarkdownConverterService $converter,
        private RouteFilterService $routeFilter,
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if package is enabled
        if (! Config::get('llm-ready.enabled', true)) {
            return $next($request);
        }

        // Handle ?format=md query parameter or .md extension (via RewriteMarkdownExtension)
        $isMarkdownRequest = $request->query('format') === 'md'
            || $request->attributes->get('llm-ready.markdown', false);

        if (! $isMarkdownRequest) {
            return $next($request);
        }

        $path = $request->path();

        // Check if this route is excluded
        if ($this->routeFilter->shouldExclude('/' . ltrim($path, '/'))) {
            return $next($request);
        }

        // Get the original response
        /** @var Response $originalResponse */
        $originalResponse = $next($request);

        // Build the full original URL for frontmatter
        $originalUrl = $request->getSchemeAndHttpHost() . '/' . ltrim($path, '/');

        // Convert to markdown
        $markdown = $this->converter->convert($originalResponse, $originalUrl);

        // Return markdown response with appropriate status code
        $statusCode = $originalResponse->getStatusCode() >= 400
            ? $originalResponse->getStatusCode()
            : 200;

        return new Response(
            content: $markdown,
            status: $statusCode,
            headers: [
                'Content-Type'  => 'text/markdown; charset=UTF-8',
                'X-LLM-Ready'   => 'true',
                'Cache-Control' => $this->getCacheControlHeader(),
            ]
        );
    }

    /**
     * Get Cache-Control header value based on config.
     */
    private function getCacheControlHeader(): string
    {
        if (Config::get('llm-ready.cache.enabled', true)) {
            $ttl = Config::get('llm-ready.cache.ttl', 1440) * 60;

            return "public, max-age={$ttl}";
        }

        return 'no-cache, no-store, must-revalidate';
    }
}
