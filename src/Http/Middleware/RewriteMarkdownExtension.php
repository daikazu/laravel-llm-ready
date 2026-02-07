<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Http\Middleware;

use Closure;
use Daikazu\LaravelLlmReady\Services\MarkdownConverterService;
use Daikazu\LaravelLlmReady\Services\RouteFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Global middleware that intercepts .md URLs before routing,
 * rewrites the request path to the original URL, and converts
 * the response to markdown.
 */
final readonly class RewriteMarkdownExtension
{
    public function __construct(
        private RouteFilterService $routeFilter,
        private MarkdownConverterService $converter,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();

        // Only handle URLs ending in .md
        if (! str_ends_with($path, '.md')) {
            return $next($request);
        }

        // Check if package is enabled
        if (! Config::get('llm-ready.enabled', true)) {
            return $next($request);
        }

        // Resolve the original path by stripping .md
        $originalPath = $this->resolveOriginalPath($path);

        // Check if this route is excluded
        if ($this->routeFilter->shouldExclude($originalPath)) {
            return $this->markdownNotFoundResponse($request, $originalPath);
        }

        // Rewrite the request URI so routing matches the original path
        $request->server->set('REQUEST_URI', $originalPath . ($request->getQueryString() ? '?' . $request->getQueryString() : ''));
        $request->initialize(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            [],
            $request->server->all(),
            $request->getContent(),
        );

        // Signal downstream middleware (InterceptMarkdownRequests) to convert the response
        $request->attributes->set('llm-ready.markdown', true);

        // Build the original URL for frontmatter
        $originalUrl = $request->getSchemeAndHttpHost() . $originalPath;

        try {
            $originalResponse = $next($request);
        } catch (NotFoundHttpException) {
            return $this->markdownNotFoundResponse($request, $originalPath);
        }

        // If InterceptMarkdownRequests already converted (route is in web group), pass through
        if ($originalResponse->headers->get('X-LLM-Ready') === 'true') {
            return $originalResponse;
        }

        // Convert the response to markdown
        $markdown = $this->converter->convert($originalResponse, $originalUrl);

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
            ],
        );
    }

    /**
     * Resolve the original path by stripping the .md extension and handling index paths.
     */
    private function resolveOriginalPath(string $path): string
    {
        // Strip .md extension
        $stripped = substr($path, 0, -3);

        // Handle index.md -> /
        if ($stripped === '/index' || $stripped === '') {
            return '/';
        }

        // Handle nested index: /blog/index -> /blog
        if (str_ends_with($stripped, '/index')) {
            return substr($stripped, 0, -6);
        }

        return $stripped;
    }

    /**
     * Return a markdown-formatted 404 response.
     */
    private function markdownNotFoundResponse(Request $request, string $originalPath): Response
    {
        $originalUrl = $request->getSchemeAndHttpHost() . $originalPath;
        $markdown = $this->converter->generateErrorMarkdown($originalUrl, 404);

        return new Response(
            content: $markdown,
            status: 404,
            headers: [
                'Content-Type' => 'text/markdown; charset=UTF-8',
                'X-LLM-Ready'  => 'true',
            ],
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
