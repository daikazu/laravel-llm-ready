<?php

declare(strict_types=1);

it('serves llms.txt endpoint', function () {
    $response = $this->get('/llms.txt');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
    $response->assertHeader('X-LLM-Ready', 'true');
});

it('lists available routes in llms.txt', function () {
    $response = $this->get('/llms.txt');

    $content = $response->getContent();

    // Should contain the base URL and mention .md format
    expect($content)->toContain('https://example.com');
    // The llms.txt explains how to get markdown versions
    expect($content)->toContain('.md');
});

it('excludes admin routes from llms.txt', function () {
    $response = $this->get('/llms.txt');

    $content = $response->getContent();

    expect($content)->not->toContain('/admin/');
});
