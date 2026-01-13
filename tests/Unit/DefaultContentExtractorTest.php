<?php

declare(strict_types=1);

use Daikazu\LaravelLlmReady\Extractors\DefaultContentExtractor;

it('extracts content using css selector', function () {
    $extractor = new DefaultContentExtractor;

    $html = '<html><body><nav>Navigation</nav><main><h1>Title</h1><p>Content</p></main><footer>Footer</footer></body></html>';

    $dom = new DOMDocument;
    $dom->loadHTML($html, LIBXML_NOERROR);

    $result = $extractor->extract($dom, ['main'], []);

    expect($result)->toContain('<h1>Title</h1>');
    expect($result)->toContain('<p>Content</p>');
    expect($result)->not->toContain('Navigation');
    expect($result)->not->toContain('Footer');
});

it('removes ignored elements', function () {
    $extractor = new DefaultContentExtractor;

    $html = '<html><body><main><h1>Title</h1><nav>Nav in main</nav><p>Content</p></main></body></html>';

    $dom = new DOMDocument;
    $dom->loadHTML($html, LIBXML_NOERROR);

    $result = $extractor->extract($dom, ['main'], ['nav']);

    expect($result)->toContain('<h1>Title</h1>');
    expect($result)->toContain('<p>Content</p>');
    expect($result)->not->toContain('Nav in main');
});

it('falls back to body if no selector matches', function () {
    $extractor = new DefaultContentExtractor;

    $html = '<html><body><div><h1>Title</h1><p>Content</p></div></body></html>';

    $dom = new DOMDocument;
    $dom->loadHTML($html, LIBXML_NOERROR);

    $result = $extractor->extract($dom, ['article'], []);

    expect($result)->toContain('<h1>Title</h1>');
    expect($result)->toContain('<p>Content</p>');
});

it('tries selectors in order', function () {
    $extractor = new DefaultContentExtractor;

    $html = '<html><body><article><p>Article content</p></article><main><p>Main content</p></main></body></html>';

    $dom = new DOMDocument;
    $dom->loadHTML($html, LIBXML_NOERROR);

    // article should be found first
    $result = $extractor->extract($dom, ['article', 'main'], []);

    expect($result)->toContain('Article content');
    expect($result)->not->toContain('Main content');
});

it('handles class selectors', function () {
    $extractor = new DefaultContentExtractor;

    $html = '<html><body><div class="content"><h1>Title</h1></div><div class="sidebar">Sidebar</div></body></html>';

    $dom = new DOMDocument;
    $dom->loadHTML($html, LIBXML_NOERROR);

    $result = $extractor->extract($dom, ['.content'], ['.sidebar']);

    expect($result)->toContain('<h1>Title</h1>');
    expect($result)->not->toContain('Sidebar');
});
