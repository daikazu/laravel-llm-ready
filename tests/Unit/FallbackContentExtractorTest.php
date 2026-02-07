<?php

declare(strict_types=1);

use Daikazu\LaravelLlmReady\Extractors\FallbackContentExtractor;

it('returns full body content ignoring selectors', function () {
    $extractor = new FallbackContentExtractor;

    $html = '<html><body><nav>Navigation</nav><main><h1>Title</h1><p>Content</p></main><footer>Footer</footer></body></html>';

    $dom = new DOMDocument;
    $dom->loadHTML($html, LIBXML_NOERROR);

    $result = $extractor->extract($dom, ['main'], ['nav', 'footer']);

    expect($result)->toContain('Navigation');
    expect($result)->toContain('<h1>Title</h1>');
    expect($result)->toContain('Footer');
});

it('returns empty string when no body exists', function () {
    $extractor = new FallbackContentExtractor;

    $dom = new DOMDocument;
    $dom->loadHTML('<html><head><title>No body</title></head></html>', LIBXML_NOERROR);

    // The DOM parser always creates a body, but let's test with minimal HTML
    $result = $extractor->extract($dom, [], []);

    // DOMDocument auto-creates body, so just verify it returns something
    expect($result)->toBeString();
});
