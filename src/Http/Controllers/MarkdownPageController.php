<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Http\Controllers;

use Daikazu\LaravelLlmReady\Services\MarkdownConverterService;
use Daikazu\LaravelLlmReady\Services\RouteFilterService;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

final class MarkdownPageController extends Controller
{
    public function __construct(
        private readonly MarkdownConverterService $converter,
        private readonly RouteFilterService $routeFilter,
    ) {}

    /**
     * Handle requests for .md URLs.
     */
    public function __invoke(Request $request, string $path = ''): Response
    {
        // Handle index.md paths:
        // - "index" or "" -> "/"
        // - "blog/index" -> "/blog"
        // - "blog/posts/index" -> "/blog/posts"
        if ($path === 'index' || $path === '') {
            $originalPath = '/';
        } elseif (str_ends_with($path, '/index')) {
            // Nested index: blog/index -> /blog
            $originalPath = '/' . substr($path, 0, -6);
        } else {
            $originalPath = '/' . $path;
        }

        // Check if this route is excluded
        if ($this->routeFilter->shouldExclude($originalPath)) {
            abort(404);
        }

        // Create a sub-request to get the original HTML page
        // Use full URL to preserve host for internal link generation
        $fullUri = $request->getSchemeAndHttpHost() . $originalPath;
        $subRequest = Request::create(
            uri: $fullUri,
            method: 'GET',
            server: $request->server->all(),
        );

        // Copy cookies and session from original request
        $subRequest->cookies = $request->cookies;
        $subRequest->headers->replace($request->headers->all());

        if ($request->hasSession()) {
            $subRequest->setLaravelSession($request->session());
        }

        // Get the kernel and handle the sub-request
        /** @var Kernel $kernel */
        $kernel = app(Kernel::class);
        $originalResponse = $kernel->handle($subRequest);

        // Follow redirects (e.g., /blog -> /blog/)
        $maxRedirects = 5;
        $redirectCount = 0;
        while ($originalResponse->isRedirect() && $redirectCount < $maxRedirects) {
            $redirectUrl = $originalResponse->headers->get('Location');
            if ($redirectUrl === null) {
                break;
            }

            // Parse the redirect URL to get the path
            $parsedUrl = parse_url($redirectUrl);
            $redirectPath = $parsedUrl['path'] ?? '/';

            // Update the original path for frontmatter
            $originalPath = $redirectPath;

            // Create new sub-request for redirect target
            $fullRedirectUri = $request->getSchemeAndHttpHost() . $redirectPath;
            $subRequest = Request::create(
                uri: $fullRedirectUri,
                method: 'GET',
                server: $request->server->all(),
            );
            $subRequest->cookies = $request->cookies;
            $subRequest->headers->replace($request->headers->all());
            if ($request->hasSession()) {
                $subRequest->setLaravelSession($request->session());
            }

            $originalResponse = $kernel->handle($subRequest);
            $redirectCount++;
        }

        // Build the full original URL for frontmatter
        $originalUrl = $request->getSchemeAndHttpHost() . $originalPath;

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
