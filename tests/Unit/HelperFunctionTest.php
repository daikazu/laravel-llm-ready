<?php

declare(strict_types=1);

use Illuminate\Http\Request;

it('has llmReadyUrl function defined', function () {
    expect(function_exists('llmReadyUrl'))->toBeTrue();
});

it('returns a markdown url for the current request', function () {
    config()->set('llm-ready.enabled', true);
    config()->set('llm-ready.exclude_patterns', ['/admin/*']);

    // Bind a fake request so the helper has context
    $request = Request::create('https://example.com/about');
    app()->instance('request', $request);

    $url = llmReadyUrl();

    expect($url)->toBe('https://example.com/about.md');
});

it('returns null when package is disabled', function () {
    config()->set('llm-ready.enabled', false);

    $request = Request::create('https://example.com/about');
    app()->instance('request', $request);

    expect(llmReadyUrl())->toBeNull();
});

it('returns null for excluded routes', function () {
    config()->set('llm-ready.enabled', true);
    config()->set('llm-ready.exclude_patterns', ['/admin/*']);

    $request = Request::create('https://example.com/admin/dashboard');
    app()->instance('request', $request);

    expect(llmReadyUrl())->toBeNull();
});
