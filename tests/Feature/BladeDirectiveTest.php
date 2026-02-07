<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('registers the llmReady blade directive', function () {
    $directives = Blade::getCustomDirectives();

    expect($directives)->toHaveKey('llmReady');
});

it('compiles the llmReady directive to php code', function () {
    $compiled = Blade::compileString('@llmReady');

    expect($compiled)->toContain('DiscoveryService');
    expect($compiled)->toContain('linkTag');
});

it('renders a link tag via the blade directive', function () {
    config()->set('llm-ready.enabled', true);
    config()->set('llm-ready.exclude_patterns', []);

    $request = \Illuminate\Http\Request::create('https://example.com/about');
    app()->instance('request', $request);

    $html = Blade::render('@llmReady');

    expect($html)->toContain('<link rel="alternate" type="text/markdown"');
    expect($html)->toContain('https://example.com/about.md');
});

it('renders empty when package is disabled', function () {
    config()->set('llm-ready.enabled', false);

    $request = \Illuminate\Http\Request::create('https://example.com/about');
    app()->instance('request', $request);

    $html = Blade::render('@llmReady');

    expect(trim($html))->toBe('');
});
