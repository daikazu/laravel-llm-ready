<?php

declare(strict_types=1);

use Daikazu\LaravelLlmReady\Services\MarkdownConverterService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    Config::set('llm-ready.cache.enabled', false);
});

it('converts a successful html response to markdown', function () {
    $converter = app(MarkdownConverterService::class);

    $html = '<html><head><title>Test Page</title></head><body><main><h1>Test Page</h1><p>Hello world.</p></main></body></html>';
    $response = new Response($html, 200);

    $result = $converter->convert($response, 'https://example.com/test');

    expect($result)->toContain('---');
    expect($result)->toContain('title:');
    expect($result)->toContain('Test Page');
    expect($result)->toContain('# Test Page');
    expect($result)->toContain('Hello world.');
});

it('generates error markdown for 404 responses', function () {
    $converter = app(MarkdownConverterService::class);

    $response = new Response('Not Found', 404);

    $result = $converter->convert($response, 'https://example.com/missing');

    expect($result)->toContain('Page Not Found');
    expect($result)->toContain('status: 404');
});

it('generates error markdown for 500 responses', function () {
    $converter = app(MarkdownConverterService::class);

    $result = $converter->generateErrorMarkdown('https://example.com/error', 500, 'Server failure');

    expect($result)->toContain('# Server Error');
    expect($result)->toContain('Server failure');
});

it('generates error markdown for other status codes', function () {
    $converter = app(MarkdownConverterService::class);

    $result = $converter->generateErrorMarkdown('https://example.com/error', 403, 'Forbidden');

    expect($result)->toContain('# Error 403');
    expect($result)->toContain('Forbidden');
});

it('handles empty response content', function () {
    $converter = app(MarkdownConverterService::class);

    $response = new Response('', 200);

    $result = $converter->convert($response, 'https://example.com/empty');

    expect($result)->toContain('Server Error');
    expect($result)->toContain('Empty response received');
});

it('returns cached markdown when cache is enabled', function () {
    Config::set('llm-ready.cache.enabled', true);
    $converter = app(MarkdownConverterService::class);
    $url = 'https://example.com/cached';

    $cacheKey = $converter->getCacheKey($url);
    Cache::put($cacheKey, 'cached markdown content', 60);

    $response = new Response('<html><body>ignored</body></html>', 200);
    $result = $converter->convert($response, $url);

    expect($result)->toBe('cached markdown content');
});

it('caches the result when cache is enabled', function () {
    Config::set('llm-ready.cache.enabled', true);
    $converter = app(MarkdownConverterService::class);
    $url = 'https://example.com/to-cache';

    $html = '<html><head><title>Cached</title></head><body><main><h1>Cached</h1><p>Content.</p></main></body></html>';
    $response = new Response($html, 200);

    $converter->convert($response, $url);

    $cacheKey = $converter->getCacheKey($url);
    expect(Cache::get($cacheKey))->not->toBeNull();
});

it('clears cache for a specific url', function () {
    $converter = app(MarkdownConverterService::class);
    $url = 'https://example.com/page';

    $cacheKey = $converter->getCacheKey($url);
    Cache::put($cacheKey, 'cached', 60);

    $converter->clearCache($url);

    expect(Cache::get($cacheKey))->toBeNull();
});

it('clears all cache entries', function () {
    $converter = app(MarkdownConverterService::class);

    Cache::put('llm_ready:sitemap', 'sitemap-data', 60);

    $converter->clearCache();

    expect(Cache::get('llm_ready:sitemap'))->toBeNull();
});

it('generates correct cache keys', function () {
    $converter = app(MarkdownConverterService::class);

    $key = $converter->getCacheKey('https://example.com/test');

    expect($key)->toBe('llm_ready:page:' . md5('https://example.com/test'));
});

it('handles pages with no extractable content', function () {
    $converter = app(MarkdownConverterService::class);

    // HTML with no main/article/content element — body only has nav/footer
    $html = '<html><head><title>Empty</title></head><body><nav>Nav only</nav></body></html>';
    $response = new Response($html, 200);

    $result = $converter->convert($response, 'https://example.com/empty-content');

    // Fallback extractor returns body content, which after removing nav will be empty
    // or the content will still convert — either way it should not crash
    expect($result)->toBeString();
    expect($result)->toContain('---');
});
