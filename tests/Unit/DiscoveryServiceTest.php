<?php

declare(strict_types=1);

use Daikazu\LaravelLlmReady\Services\DiscoveryService;
use Illuminate\Http\Request;

beforeEach(function () {
    config()->set('llm-ready.enabled', true);
    config()->set('llm-ready.discovery.link_header', true);
    config()->set('llm-ready.exclude_patterns', ['/admin/*', '/api/*']);
});

it('builds a markdown url for a standard page', function () {
    $service = app(DiscoveryService::class);
    $request = Request::create('https://example.com/about');

    expect($service->markdownUrl($request))->toBe('https://example.com/about.md');
});

it('maps root path to index.md', function () {
    $service = app(DiscoveryService::class);
    $request = Request::create('https://example.com/');

    expect($service->markdownUrl($request))->toBe('https://example.com/index.md');
});

it('returns null when package is disabled', function () {
    config()->set('llm-ready.enabled', false);

    $service = app(DiscoveryService::class);
    $request = Request::create('https://example.com/about');

    expect($service->markdownUrl($request))->toBeNull();
});

it('returns null for excluded routes', function () {
    $service = app(DiscoveryService::class);
    $request = Request::create('https://example.com/admin/dashboard');

    expect($service->markdownUrl($request))->toBeNull();
});

it('builds link header value when enabled', function () {
    $service = app(DiscoveryService::class);
    $request = Request::create('https://example.com/about');

    expect($service->linkHeaderValue($request))
        ->toBe('<https://example.com/about.md>; rel="alternate"; type="text/markdown"');
});

it('returns null link header when config is disabled', function () {
    config()->set('llm-ready.discovery.link_header', false);

    $service = app(DiscoveryService::class);
    $request = Request::create('https://example.com/about');

    expect($service->linkHeaderValue($request))->toBeNull();
});

it('returns null link header for excluded routes', function () {
    $service = app(DiscoveryService::class);
    $request = Request::create('https://example.com/admin/dashboard');

    expect($service->linkHeaderValue($request))->toBeNull();
});

it('builds a link tag for a standard page', function () {
    $service = app(DiscoveryService::class);
    $request = Request::create('https://example.com/about');

    expect($service->linkTag($request))
        ->toBe('<link rel="alternate" type="text/markdown" href="https://example.com/about.md">');
});

it('returns empty link tag for excluded routes', function () {
    $service = app(DiscoveryService::class);
    $request = Request::create('https://example.com/admin/dashboard');

    expect($service->linkTag($request))->toBe('');
});

it('returns empty link tag when package is disabled', function () {
    config()->set('llm-ready.enabled', false);

    $service = app(DiscoveryService::class);
    $request = Request::create('https://example.com/about');

    expect($service->linkTag($request))->toBe('');
});
