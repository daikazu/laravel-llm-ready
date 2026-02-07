<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;

it('clears all caches by default', function () {
    Cache::put('llm_ready:sitemap', 'cached-sitemap', 60);
    Cache::put('llm_ready:page:' . md5('https://example.com/about'), 'cached-page', 60);

    $this->artisan('llm-ready:clear-cache')
        ->expectsOutputToContain('LLM Ready cache cleared')
        ->assertExitCode(0);

    expect(Cache::get('llm_ready:sitemap'))->toBeNull();
});

it('clears cache for a specific url', function () {
    $url = 'https://example.com/about';
    $cacheKey = 'llm_ready:page:' . md5($url);
    Cache::put($cacheKey, 'cached-page', 60);

    $this->artisan('llm-ready:clear-cache', ['--url' => $url])
        ->expectsOutputToContain("Cache cleared for URL: {$url}")
        ->assertExitCode(0);

    expect(Cache::get($cacheKey))->toBeNull();
});

it('clears only sitemap cache with --sitemap flag', function () {
    Cache::put('llm_ready:sitemap', 'cached-sitemap', 60);
    $pageKey = 'llm_ready:page:' . md5('https://example.com/about');
    Cache::put($pageKey, 'cached-page', 60);

    $this->artisan('llm-ready:clear-cache', ['--sitemap' => true])
        ->expectsOutputToContain('Sitemap cache cleared')
        ->assertExitCode(0);

    expect(Cache::get('llm_ready:sitemap'))->toBeNull();
    expect(Cache::get($pageKey))->toBe('cached-page');
});
