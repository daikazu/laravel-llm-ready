<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Services;

use Illuminate\Support\Facades\Config;

final readonly class RouteFilterService
{
    /**
     * Check if a URL should be excluded from markdown conversion.
     */
    public function shouldExclude(string $url): bool
    {
        $patterns = Config::get('llm-ready.exclude_patterns', []);

        // Normalize URL - remove domain and ensure leading slash
        $path = parse_url($url, PHP_URL_PATH);

        // Handle parse_url returning null/false for malformed URLs
        if ($path === null || $path === false) {
            $path = $url;
        }

        if (! str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        foreach ($patterns as $pattern) {
            if ($this->matchesPattern($path, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a URL should be processed (is a valid .md request).
     */
    public function shouldProcess(string $url): bool
    {
        if (! str_ends_with($url, '.md')) {
            return false;
        }

        return ! $this->shouldExclude($this->stripMdExtension($url));
    }

    /**
     * Strip the .md extension from a URL.
     */
    public function stripMdExtension(string $url): string
    {
        if (str_ends_with($url, '.md')) {
            return substr($url, 0, -3);
        }

        return $url;
    }

    /**
     * Match a URL path against a pattern.
     * Supports fnmatch() style wildcards.
     * Note: /* matches all nested paths (not just one level)
     */
    private function matchesPattern(string $path, string $pattern): bool
    {
        // Normalize pattern - ensure leading slash
        if (! str_starts_with($pattern, '/') && ! str_starts_with($pattern, '*')) {
            $pattern = '/' . $pattern;
        }

        // Convert /* to match nested paths as well
        // /api/* should match /api/foo and /api/foo/bar
        if (str_ends_with($pattern, '/*')) {
            $prefix = substr($pattern, 0, -2);
            if (str_starts_with($path, $prefix . '/') || $path === $prefix) {
                return true;
            }
        }

        // Use fnmatch for glob-style matching (without FNM_PATHNAME to allow * to match /)
        return fnmatch($pattern, $path, FNM_CASEFOLD);
    }
}
