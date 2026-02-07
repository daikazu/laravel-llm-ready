<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

final readonly class DiscoveryService
{
    public function __construct(
        private RouteFilterService $routeFilter,
    ) {}

    /**
     * Get the markdown URL for the current or given request.
     */
    public function markdownUrl(?Request $request = null): ?string
    {
        if (! Config::get('llm-ready.enabled', true)) {
            return null;
        }

        $request ??= request();

        $path = '/' . ltrim($request->path(), '/');

        if ($this->routeFilter->shouldExclude($path)) {
            return null;
        }

        // Root path maps to /index.md
        $mdPath = $path === '/' ? '/index.md' : $path . '.md';

        return $request->getSchemeAndHttpHost() . $mdPath;
    }

    /**
     * Get the Link header value for markdown discovery.
     */
    public function linkHeaderValue(?Request $request = null): ?string
    {
        if (! Config::get('llm-ready.discovery.link_header', true)) {
            return null;
        }

        $url = $this->markdownUrl($request);

        if ($url === null) {
            return null;
        }

        return '<' . $url . '>; rel="alternate"; type="text/markdown"';
    }

    /**
     * Get an HTML link tag for markdown discovery.
     */
    public function linkTag(?Request $request = null): string
    {
        $url = $this->markdownUrl($request);

        if ($url === null) {
            return '';
        }

        return '<link rel="alternate" type="text/markdown" href="' . e($url) . '">';
    }
}
