<?php

declare(strict_types=1);

it('intercepts .md requests and returns markdown', function () {
    $response = $this->get('/about.md');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    $response->assertHeader('X-LLM-Ready', 'true');
});

it('includes frontmatter in response', function () {
    $response = $this->get('/about.md');

    $content = $response->getContent();

    expect($content)->toContain('---');
    expect($content)->toContain('title:');
    expect($content)->toContain('url:');
});

it('converts html to markdown', function () {
    $response = $this->get('/about.md');

    $content = $response->getContent();

    // Should contain markdown heading (from h1)
    expect($content)->toContain('# About Us');
});

it('passes through non-.md requests', function () {
    $response = $this->get('/about');

    $response->assertStatus(200);

    $content = $response->getContent();

    // Should return raw HTML, not markdown
    expect($content)->toContain('<html>');
});

it('returns 404 markdown for missing pages', function () {
    $response = $this->get('/non-existent-page.md');

    $response->assertStatus(404);
    $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');

    $content = $response->getContent();

    expect($content)->toContain('Page Not Found');
    expect($content)->toContain('status: 404');
});

it('excludes admin routes from .md conversion', function () {
    $response = $this->get('/admin/dashboard.md');

    // Should return 404 because it's excluded
    $response->assertStatus(404);
});

it('handles dynamic route parameters with .md extension', function () {
    $response = $this->get('/blog/my-post.md');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    $response->assertHeader('X-LLM-Ready', 'true');

    $content = $response->getContent();

    expect($content)->toContain('# My Post');
    expect($content)->toContain('Blog post content.');
});

it('does not affect dynamic route HTML responses', function () {
    $response = $this->get('/blog/my-post');

    $response->assertStatus(200);

    $content = $response->getContent();

    expect($content)->toContain('<html>');
    expect($content)->toContain('<h1>My Post</h1>');
});

it('converts response via format=md query parameter', function () {
    // Register a route in the web middleware group for this test
    \Illuminate\Support\Facades\Route::middleware('web')->get('/web-page', function () {
        return '<html><head><title>Web Page</title></head><body><main><h1>Web Page</h1><p>Content here.</p></main></body></html>';
    });

    $response = $this->get('/web-page?format=md');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    $response->assertHeader('X-LLM-Ready', 'true');

    $content = $response->getContent();

    expect($content)->toContain('# Web Page');
    expect($content)->toContain('Content here.');
});

it('does not convert when package is disabled', function () {
    config()->set('llm-ready.enabled', false);

    $response = $this->get('/about.md');

    // When disabled, .md URL passes through unmodified — likely 404 since no route matches
    $response->assertStatus(404);
});

it('handles index.md as root path', function () {
    // Define a root route for this test
    \Illuminate\Support\Facades\Route::get('/', function () {
        return '<html><head><title>Home</title></head><body><main><h1>Home</h1><p>Welcome home.</p></main></body></html>';
    });

    $response = $this->get('/index.md');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');

    $content = $response->getContent();

    expect($content)->toContain('# Home');
});

it('excludes admin routes from format=md conversion', function () {
    $response = $this->get('/admin/dashboard?format=md');

    // Excluded routes pass through without conversion
    $response->assertStatus(200);

    $content = $response->getContent();

    expect($content)->toContain('<html>');
});

it('returns cache-control header on markdown responses', function () {
    config()->set('llm-ready.cache.enabled', true);
    config()->set('llm-ready.cache.ttl', 60);

    $response = $this->get('/about.md');

    $response->assertStatus(200);
    $response->assertHeader('Cache-Control');

    $cacheControl = $response->headers->get('Cache-Control');
    expect($cacheControl)->toContain('public');
});

it('returns no-cache header when caching is disabled', function () {
    config()->set('llm-ready.cache.enabled', false);

    $response = $this->get('/about.md');

    $response->assertStatus(200);

    $cacheControl = $response->headers->get('Cache-Control');
    expect($cacheControl)->toContain('no-cache');
});
