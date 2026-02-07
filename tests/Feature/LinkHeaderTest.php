<?php

declare(strict_types=1);

it('adds link header to html responses', function () {
    \Illuminate\Support\Facades\Route::middleware('web')->get('/web-about', function () {
        return '<html><head><title>About</title></head><body><main><h1>About</h1></main></body></html>';
    });

    $response = $this->get('/web-about');

    $response->assertStatus(200);
    $response->assertHeader('Link');

    $link = $response->headers->get('Link');
    expect($link)->toContain('.md');
    expect($link)->toContain('rel="alternate"');
    expect($link)->toContain('type="text/markdown"');
});

it('does not add link header to markdown responses', function () {
    $response = $this->get('/about.md');

    $response->assertStatus(200);
    $response->assertHeader('X-LLM-Ready', 'true');
    $response->assertHeaderMissing('Link');
});

it('does not add link header for excluded routes', function () {
    \Illuminate\Support\Facades\Route::middleware('web')->get('/admin/web-dashboard', function () {
        return '<html><head><title>Admin</title></head><body><main><h1>Admin</h1></main></body></html>';
    });

    $response = $this->get('/admin/web-dashboard');

    $response->assertStatus(200);
    $response->assertHeaderMissing('Link');
});

it('does not add link header when discovery is disabled', function () {
    config()->set('llm-ready.discovery.link_header', false);

    \Illuminate\Support\Facades\Route::middleware('web')->get('/web-about-no-header', function () {
        return '<html><head><title>About</title></head><body><main><h1>About</h1></main></body></html>';
    });

    $response = $this->get('/web-about-no-header');

    $response->assertStatus(200);
    $response->assertHeaderMissing('Link');
});

it('does not add link header when package is disabled', function () {
    config()->set('llm-ready.enabled', false);

    // When package is disabled, the middleware still runs but DiscoveryService returns null
    // Need a route that works without the package
    \Illuminate\Support\Facades\Route::middleware('web')->get('/test-disabled', function () {
        return '<html><head><title>Test</title></head><body><p>Test</p></body></html>';
    });

    $response = $this->get('/test-disabled');

    $response->assertHeaderMissing('Link');
});

it('does not add link header on error responses', function () {
    $response = $this->get('/non-existent-page');

    $response->assertStatus(404);
    $response->assertHeaderMissing('Link');
});
