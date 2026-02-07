<?php

declare(strict_types=1);

use Daikazu\LaravelLlmReady\Services\SitemapGeneratorService;
use Illuminate\Support\Facades\Config;

it('generates llms.txt with title from config', function () {
    Config::set('llm-ready.llms_txt.title', 'My Test Site');

    $generator = app(SitemapGeneratorService::class);
    $result = $generator->generate();

    expect($result)->toContain('# My Test Site');
});

it('falls back to app name for title', function () {
    Config::set('llm-ready.llms_txt.title', null);
    Config::set('app.name', 'Test App');

    $generator = app(SitemapGeneratorService::class);
    $result = $generator->generate();

    expect($result)->toContain('# Test App');
});

it('includes summary as blockquote', function () {
    Config::set('llm-ready.llms_txt.summary', 'A great website for testing.');

    $generator = app(SitemapGeneratorService::class);
    $result = $generator->generate();

    expect($result)->toContain('> A great website for testing.');
});

it('includes description paragraphs', function () {
    Config::set('llm-ready.llms_txt.description', [
        'First paragraph about the site.',
        'Second paragraph with more details.',
    ]);

    $generator = app(SitemapGeneratorService::class);
    $result = $generator->generate();

    expect($result)->toContain('First paragraph about the site.');
    expect($result)->toContain('Second paragraph with more details.');
});

it('includes custom curated sections with string links', function () {
    Config::set('llm-ready.llms_txt.sections', [
        'Documentation' => [
            '/docs/getting-started',
            '/docs/api-reference',
        ],
    ]);

    $generator = app(SitemapGeneratorService::class);
    $result = $generator->generate();

    expect($result)->toContain('## Documentation');
    expect($result)->toContain('Getting Started');
    expect($result)->toContain('Api Reference');
});

it('includes custom curated sections with array links', function () {
    Config::set('llm-ready.llms_txt.sections', [
        'Resources' => [
            ['url' => '/help', 'description' => 'Help Center'],
        ],
    ]);

    $generator = app(SitemapGeneratorService::class);
    $result = $generator->generate();

    expect($result)->toContain('## Resources');
    expect($result)->toContain('[Help Center]');
});

it('wraps auto section in optional when configured', function () {
    Config::set('llm-ready.llms_txt.auto_section', [
        'enabled'             => true,
        'title'               => 'All Pages',
        'include_in_optional' => true,
    ]);

    $generator = app(SitemapGeneratorService::class);
    $result = $generator->generate();

    expect($result)->toContain('## Optional');
    expect($result)->toContain('### All Pages');
});

it('skips auto section when disabled', function () {
    Config::set('llm-ready.llms_txt.auto_section', [
        'enabled' => false,
    ]);

    $generator = app(SitemapGeneratorService::class);
    $result = $generator->generate();

    expect($result)->not->toContain('## Pages');
});

it('includes optional section when enabled', function () {
    Config::set('llm-ready.llms_txt.optional_section', [
        'enabled' => true,
        'title'   => 'Extra Resources',
        'content' => [
            '/extras/faq',
        ],
    ]);

    $generator = app(SitemapGeneratorService::class);
    $result = $generator->generate();

    expect($result)->toContain('## Extra Resources');
    expect($result)->toContain('Faq');
});

it('normalizes relative urls to absolute', function () {
    Config::set('app.url', 'https://example.com');
    Config::set('llm-ready.llms_txt.sections', [
        'Links' => [
            '/relative/path',
            'https://external.com/absolute',
        ],
    ]);

    $generator = app(SitemapGeneratorService::class);
    $result = $generator->generate();

    expect($result)->toContain('https://example.com/relative/path');
    expect($result)->toContain('https://external.com/absolute');
});

it('skips dynamic routes with parameters', function () {
    $generator = app(SitemapGeneratorService::class);
    $result = $generator->generate();

    // blog/{slug} route is defined in TestCase but should be skipped
    expect($result)->not->toContain('{slug}');
});

it('always includes md format explanation', function () {
    $generator = app(SitemapGeneratorService::class);
    $result = $generator->generate();

    expect($result)->toContain('Append `.md` to any URL');
    expect($result)->toContain('?format=md');
});
