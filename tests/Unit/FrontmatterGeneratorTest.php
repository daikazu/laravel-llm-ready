<?php

declare(strict_types=1);

use Daikazu\LaravelLlmReady\Support\FrontmatterGenerator;

it('generates frontmatter with all fields', function () {
    $generator = new FrontmatterGenerator;

    $html = '<html><head><title>Test Page</title><meta name="description" content="A test description"></head><body></body></html>';

    $dom = new DOMDocument;
    $dom->loadHTML($html);

    $config = [
        'include_title'         => true,
        'include_description'   => true,
        'include_url'           => true,
        'include_last_modified' => true,
    ];

    $result = $generator->generate($dom, 'https://example.com/test', $config);

    expect($result)->toContain('---');
    // YAML may quote strings with spaces
    expect($result)->toMatch('/title:\s+[\'"]?Test Page[\'"]?/');
    expect($result)->toMatch('/description:\s+[\'"]?A test description[\'"]?/');
    expect($result)->toMatch('/url:\s+[\'"]?https:\/\/example\.com\/test[\'"]?/');
    expect($result)->toContain('last_modified:');
});

it('skips fields when configured', function () {
    $generator = new FrontmatterGenerator;

    $html = '<html><head><title>Test Page</title></head><body></body></html>';

    $dom = new DOMDocument;
    $dom->loadHTML($html);

    $config = [
        'include_title'         => true,
        'include_description'   => false,
        'include_url'           => false,
        'include_last_modified' => false,
    ];

    $result = $generator->generate($dom, 'https://example.com/test', $config);

    expect($result)->toMatch('/title:\s+[\'"]?Test Page[\'"]?/');
    expect($result)->not->toContain('url:');
    expect($result)->not->toContain('last_modified:');
});

it('falls back to h1 for title', function () {
    $generator = new FrontmatterGenerator;

    $html = '<html><head></head><body><h1>Page Heading</h1></body></html>';

    $dom = new DOMDocument;
    $dom->loadHTML($html);

    $config = ['include_title' => true];

    $result = $generator->generate($dom, 'https://example.com/test', $config);

    expect($result)->toMatch('/title:\s+[\'"]?Page Heading[\'"]?/');
});

it('generates error frontmatter', function () {
    $generator = new FrontmatterGenerator;

    $result = $generator->generateError('https://example.com/missing', 404, 'Page not found');

    expect($result)->toContain('---');
    expect($result)->toMatch('/title:\s+[\'"]?Page Not Found[\'"]?/');
    expect($result)->toContain('status: 404');
    expect($result)->toMatch('/error:\s+[\'"]?Page not found[\'"]?/');
});

it('includes custom fields', function () {
    $generator = new FrontmatterGenerator;

    $html = '<html><head><title>Test</title></head><body></body></html>';

    $dom = new DOMDocument;
    $dom->loadHTML($html);

    $config = [
        'include_title' => true,
        'custom_fields' => [
            'site_name' => 'My Website',
            'author'    => 'John Doe',
        ],
    ];

    $result = $generator->generate($dom, 'https://example.com/test', $config);

    expect($result)->toMatch('/site_name:\s+[\'"]?My Website[\'"]?/');
    expect($result)->toMatch('/author:\s+[\'"]?John Doe[\'"]?/');
});
