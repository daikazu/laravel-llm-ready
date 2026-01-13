<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Services;

use Daikazu\LaravelLlmReady\Contracts\ContentExtractorInterface;
use Daikazu\LaravelLlmReady\Support\FrontmatterGenerator;
use Daikazu\LaravelLlmReady\Support\HtmlPreprocessor;
use Daikazu\LaravelLlmReady\Support\MarkdownCleaner;
use DOMDocument;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\HtmlConverter;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class MarkdownConverterService
{
    public function __construct(
        private ContentExtractorInterface $extractor,
        private FrontmatterGenerator $frontmatterGenerator,
        private HtmlPreprocessor $htmlPreprocessor,
        private MarkdownCleaner $markdownCleaner,
    ) {}

    /**
     * Convert an HTTP response to markdown.
     */
    public function convert(Response $response, string $originalUrl): string
    {
        // Check cache first
        if ($this->isCacheEnabled()) {
            $cacheKey = $this->getCacheKey($originalUrl);
            $cached = Cache::get($cacheKey);

            if ($cached !== null) {
                return $cached;
            }
        }

        // Handle error responses
        if ($response->getStatusCode() >= 400) {
            return $this->generateErrorMarkdown($originalUrl, $response->getStatusCode());
        }

        // Convert the response
        $html = $response->getContent();

        if ($html === false || $html === '') {
            return $this->generateErrorMarkdown($originalUrl, 500, 'Empty response received');
        }

        try {
            $markdown = $this->convertHtmlToMarkdown($html, $originalUrl);

            // Cache the result
            if ($this->isCacheEnabled()) {
                $cacheKey = $this->getCacheKey($originalUrl);
                $ttl = Config::get('llm-ready.cache.ttl', 1440);
                Cache::put($cacheKey, $markdown, now()->addMinutes($ttl));
            }

            return $markdown;
        } catch (Throwable $e) {
            Log::warning('LLM Ready: Failed to convert HTML to markdown', [
                'url'   => $originalUrl,
                'error' => $e->getMessage(),
            ]);

            return $this->generateErrorMarkdown($originalUrl, 500, 'Conversion failed');
        }
    }

    /**
     * Generate error markdown for failed requests.
     */
    public function generateErrorMarkdown(string $url, int $statusCode, string $message = 'Page not found'): string
    {
        $frontmatter = $this->frontmatterGenerator->generateError($url, $statusCode, $message);

        $content = match ($statusCode) {
            404     => "# Page Not Found\n\nThe requested page could not be found at this URL.\n",
            500     => "# Server Error\n\nAn error occurred while processing this page: {$message}\n",
            default => "# Error {$statusCode}\n\n{$message}\n",
        };

        return $frontmatter . $content;
    }

    /**
     * Clear cache for a specific URL or all URLs.
     */
    public function clearCache(?string $url = null): void
    {
        $prefix = Config::get('llm-ready.cache.prefix', 'llm_ready');

        if ($url !== null) {
            $cacheKey = $this->getCacheKey($url);
            Cache::forget($cacheKey);

            return;
        }

        // Clear all LLM Ready cache entries
        // Note: This requires cache tags or manual tracking
        // For now, we'll use a simple approach with the cache prefix
        Cache::forget("{$prefix}:sitemap");

        // Log that manual cache clearing may be needed for individual entries
        Log::info('LLM Ready: Cache cleared. Note: Individual page caches may need manual clearing.');
    }

    /**
     * Get cache key for a URL.
     */
    public function getCacheKey(string $url): string
    {
        $prefix = Config::get('llm-ready.cache.prefix', 'llm_ready');
        $hash = md5($url);

        return "{$prefix}:page:{$hash}";
    }

    private function convertHtmlToMarkdown(string $html, string $url): string
    {
        // Parse HTML into DOM
        $dom = new DOMDocument;

        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);

        // Add UTF-8 encoding hint
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        libxml_clear_errors();

        // Generate frontmatter
        $frontmatterConfig = Config::get('llm-ready.frontmatter', []);
        $frontmatter = $this->frontmatterGenerator->generate($dom, $url, $frontmatterConfig);

        // Preprocess HTML to mark eyebrow elements
        $eyebrowSelectors = Config::get('llm-ready.eyebrow_selectors', []);
        $eyebrowAutoDetect = Config::get('llm-ready.eyebrow_auto_detect', true);
        $this->htmlPreprocessor->markEyebrows($dom, $eyebrowSelectors, $eyebrowAutoDetect);

        // Extract main content
        $contentSelectors = Config::get('llm-ready.content_selectors', ['main', 'article', '.content']);
        $ignoreSelectors = Config::get('llm-ready.ignore_selectors', ['nav', 'footer', 'script', 'style']);
        $extractedHtml = $this->extractor->extract($dom, $contentSelectors, $ignoreSelectors);

        if ($extractedHtml === '') {
            return $frontmatter . "# No Content\n\nNo main content could be extracted from this page.\n";
        }

        // Convert HTML to Markdown with table support
        $converter = new HtmlConverter([
            'strip_tags'              => true,
            'remove_nodes'            => 'script style',
            'hard_break'              => true,
            'header_style'            => 'atx',
            'strip_placeholder_links' => true,
        ]);

        // Add table converter for GFM-style tables (adds to existing default converters)
        $converter->getEnvironment()->addConverter(new TableConverter);

        $markdown = $converter->convert($extractedHtml);

        // Clean up the markdown
        $markdown = $this->markdownCleaner->clean($markdown);

        return $frontmatter . $markdown;
    }

    private function isCacheEnabled(): bool
    {
        return Config::get('llm-ready.cache.enabled', true);
    }
}
