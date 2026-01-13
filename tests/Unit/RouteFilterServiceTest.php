<?php

declare(strict_types=1);

use Daikazu\LaravelLlmReady\Services\RouteFilterService;

beforeEach(function () {
    config()->set('llm-ready.exclude_patterns', [
        '/admin/*',
        '/api/*',
        '*/login',
    ]);
});

it('identifies .md urls as processable', function () {
    $service = new RouteFilterService;

    expect($service->shouldProcess('/about.md'))->toBeTrue();
    expect($service->shouldProcess('/contact-us.md'))->toBeTrue();
    expect($service->shouldProcess('/products/item.md'))->toBeTrue();
});

it('rejects non-.md urls', function () {
    $service = new RouteFilterService;

    expect($service->shouldProcess('/about'))->toBeFalse();
    expect($service->shouldProcess('/about.html'))->toBeFalse();
    expect($service->shouldProcess('/about.json'))->toBeFalse();
});

it('excludes admin routes', function () {
    $service = new RouteFilterService;

    expect($service->shouldProcess('/admin/dashboard.md'))->toBeFalse();
    expect($service->shouldProcess('/admin/users.md'))->toBeFalse();
});

it('excludes api routes', function () {
    $service = new RouteFilterService;

    expect($service->shouldProcess('/api/v1/users.md'))->toBeFalse();
    expect($service->shouldProcess('/api/products.md'))->toBeFalse();
});

it('excludes login routes with wildcard', function () {
    $service = new RouteFilterService;

    expect($service->shouldProcess('/login.md'))->toBeFalse();
    expect($service->shouldProcess('/admin/login.md'))->toBeFalse();
});

it('strips .md extension correctly', function () {
    $service = new RouteFilterService;

    expect($service->stripMdExtension('/about.md'))->toBe('/about');
    expect($service->stripMdExtension('/contact-us.md'))->toBe('/contact-us');
    expect($service->stripMdExtension('/about'))->toBe('/about');
});
