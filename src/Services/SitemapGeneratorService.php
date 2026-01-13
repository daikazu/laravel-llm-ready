<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Services;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Config;

final readonly class SitemapGeneratorService
{
    public function __construct(
        private Router $router,
        private RouteFilterService $routeFilter,
    ) {}

    /**
     * Generate the llms.txt content following the llmstxt.org spec.
     *
     * @see https://llmstxt.org
     */
    public function generate(): string
    {
        $config = Config::get('llm-ready.llms_txt', []);
        $baseUrl = rtrim((string) config('app.url', ''), '/');
        $lines = [];

        // H1 Title (required by spec)
        $title = $config['title'] ?? config('app.name', 'Website');
        $lines[] = "# {$title}";
        $lines[] = '';

        // Blockquote summary
        if (! empty($config['summary'])) {
            $lines[] = "> {$config['summary']}";
            $lines[] = '';
        }

        // Note about .md URLs for LLMs
        $lines[] = 'All pages on this site are available in markdown format for LLM consumption.';
        $lines[] = 'Append `.md` to any URL or add `?format=md` to get the markdown version.';
        $lines[] = '';

        // Detailed description paragraphs
        if (! empty($config['description']) && is_array($config['description'])) {
            foreach ($config['description'] as $paragraph) {
                $lines[] = $paragraph;
                $lines[] = '';
            }
        }

        // Custom curated sections
        if (! empty($config['sections']) && is_array($config['sections'])) {
            foreach ($config['sections'] as $sectionTitle => $links) {
                $lines[] = "## {$sectionTitle}";
                $lines[] = '';
                $lines = array_merge($lines, $this->formatLinks($links, $baseUrl));
                $lines[] = '';
            }
        }

        // Auto-generated section with discovered routes
        $autoSection = $config['auto_section'] ?? ['enabled' => true];
        if ($autoSection['enabled'] ?? true) {
            $routes = $this->getEligibleRoutes();

            if ($routes !== []) {
                $sectionTitle = $autoSection['title'] ?? 'Pages';

                // Check if should be wrapped in Optional section
                if ($autoSection['include_in_optional'] ?? false) {
                    $lines[] = '## Optional';
                    $lines[] = '';
                    $lines[] = "### {$sectionTitle}";
                } else {
                    $lines[] = "## {$sectionTitle}";
                }
                $lines[] = '';

                foreach ($routes as $routeUri) {
                    $url = $baseUrl . '/' . ltrim($routeUri, '/');
                    // Use original URL (not .md) so LLMs recommend correct URLs to users
                    $url = rtrim($url, '/') ?: $baseUrl;
                    $lines[] = "- [{$this->routeToLabel($routeUri)}]({$url})";
                }
                $lines[] = '';
            }
        }

        // Optional section with custom content
        $optionalSection = $config['optional_section'] ?? ['enabled' => false];
        if (($optionalSection['enabled'] ?? false) && ! empty($optionalSection['content'])) {
            $optionalTitle = $optionalSection['title'] ?? 'Optional';
            $lines[] = "## {$optionalTitle}";
            $lines[] = '';
            $lines = array_merge($lines, $this->formatLinks($optionalSection['content'], $baseUrl));
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Format an array of links into markdown format.
     *
     * @param  array<int|string, string|array{url: string, description?: string}>  $links
     * @return array<string>
     */
    private function formatLinks(array $links, string $baseUrl): array
    {
        $formatted = [];

        foreach ($links as $link) {
            if (is_string($link)) {
                // Simple URL string
                $url = $this->normalizeUrl($link, $baseUrl);
                $label = $this->urlToLabel($link);
                $formatted[] = "- [{$label}]({$url})";
            } else {
                // Array with url and optional description
                $url = $this->normalizeUrl($link['url'], $baseUrl);
                $label = $link['description'] ?? $this->urlToLabel($link['url']);
                $formatted[] = "- [{$label}]({$url})";
            }
        }

        return $formatted;
    }

    /**
     * Normalize a URL, making it absolute if needed.
     */
    private function normalizeUrl(string $url, string $baseUrl): string
    {
        // Already absolute
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        // Relative URL - make absolute
        return $baseUrl . '/' . ltrim($url, '/');
    }

    /**
     * Convert a URL to a human-readable label.
     */
    private function urlToLabel(string $url): string
    {
        // Remove .md extension
        $url = preg_replace('/\.md$/', '', $url) ?? $url;

        // Get the last path segment
        $parts = explode('/', trim($url, '/'));
        $label = end($parts) ?: 'Home';

        // Convert to title case
        return $this->formatLabel($label);
    }

    /**
     * Convert a route URI to a human-readable label.
     */
    private function routeToLabel(string $uri): string
    {
        if ($uri === '/' || $uri === '') {
            return 'Home';
        }

        // Get the last path segment
        $parts = explode('/', trim($uri, '/'));
        $label = end($parts) ?: 'Home';

        return $this->formatLabel($label);
    }

    /**
     * Format a slug into a readable label.
     */
    private function formatLabel(string $slug): string
    {
        // Replace dashes/underscores with spaces
        $label = str_replace(['-', '_'], ' ', $slug);

        // Title case
        return ucwords($label);
    }

    /**
     * Get all routes eligible for markdown conversion.
     *
     * @return array<string>
     */
    private function getEligibleRoutes(): array
    {
        $eligibleRoutes = [];

        /** @var iterable<Route> $routes */
        $routes = $this->router->getRoutes();

        foreach ($routes as $route) {
            // Only GET routes
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();

            // Skip routes with parameters (dynamic routes)
            if (str_contains($uri, '{')) {
                continue;
            }

            // Skip if excluded by pattern
            if ($this->routeFilter->shouldExclude('/' . ltrim($uri, '/'))) {
                continue;
            }

            // Skip internal Laravel routes
            if ($this->isInternalRoute($uri)) {
                continue;
            }

            $eligibleRoutes[] = $uri;
        }

        // Sort alphabetically
        sort($eligibleRoutes);

        return array_unique($eligibleRoutes);
    }

    /**
     * Check if a route is an internal Laravel route that shouldn't be listed.
     */
    private function isInternalRoute(string $uri): bool
    {
        $internalPrefixes = [
            '_ignition',
            '_debugbar',
            'sanctum',
            'livewire',
            'up', // health check
            'llms.txt', // don't list ourselves
        ];

        foreach ($internalPrefixes as $prefix) {
            if (str_starts_with($uri, $prefix)) {
                return true;
            }
        }

        // Skip .md routes (our own generated routes)
        if (str_ends_with($uri, '.md')) {
            return true;
        }

        // Skip common static/meta files
        $staticFiles = [
            'robots.txt',
            'sitemap.xml',
            'sitemap.txt',
            'favicon.ico',
            'manifest.json',
            'browserconfig.xml',
            'ads.txt',
            'security.txt',
            '.well-known',
        ];
        return array_any($staticFiles, fn ($file): bool => $uri === $file || str_starts_with($uri, (string) $file));
    }
}
