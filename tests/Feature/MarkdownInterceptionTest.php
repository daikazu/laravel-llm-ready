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
