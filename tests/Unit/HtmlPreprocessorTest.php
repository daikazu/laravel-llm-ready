<?php

declare(strict_types=1);

use Daikazu\LaravelLlmReady\Support\HtmlPreprocessor;

it('marks elements matching configured eyebrow selectors', function () {
    $preprocessor = new HtmlPreprocessor;

    $html = '<html><body><main><span class="eyebrow">FEATURED</span><h1>Main Title</h1></main></body></html>';
    $dom = new DOMDocument;
    $dom->loadHTML($html, LIBXML_NOERROR);

    $preprocessor->markEyebrows($dom, ['.eyebrow'], autoDetect: false);

    $output = $dom->saveHTML();
    expect($output)->toContain('data-llm-eyebrow="true"');
    expect($output)->toContain('<em>FEATURED</em>');
});

it('auto-detects eyebrow text before headings', function () {
    $preprocessor = new HtmlPreprocessor;

    $html = '<html><body><main><span>CATEGORY</span><h1>Article Title</h1></main></body></html>';
    $dom = new DOMDocument;
    $dom->loadHTML($html, LIBXML_NOERROR);

    $preprocessor->markEyebrows($dom, [], autoDetect: true);

    $output = $dom->saveHTML();
    expect($output)->toContain('data-llm-eyebrow="true"');
    expect($output)->toContain('<em>CATEGORY</em>');
});

it('does not mark long text as eyebrow', function () {
    $preprocessor = new HtmlPreprocessor;

    $html = '<html><body><main><span>This is a very long paragraph that should not be considered an eyebrow label</span><h1>Title</h1></main></body></html>';
    $dom = new DOMDocument;
    $dom->loadHTML($html, LIBXML_NOERROR);

    $preprocessor->markEyebrows($dom, [], autoDetect: true);

    $output = $dom->saveHTML();
    expect($output)->not->toContain('data-llm-eyebrow');
});

it('does not mark elements containing links as eyebrow', function () {
    $preprocessor = new HtmlPreprocessor;

    $html = '<html><body><main><span><a href="/link">TAG</a></span><h1>Title</h1></main></body></html>';
    $dom = new DOMDocument;
    $dom->loadHTML($html, LIBXML_NOERROR);

    $preprocessor->markEyebrows($dom, [], autoDetect: true);

    $output = $dom->saveHTML();
    expect($output)->not->toContain('data-llm-eyebrow');
});

it('auto-detects badge class elements', function () {
    $preprocessor = new HtmlPreprocessor;

    $html = '<html><body><main><span class="badge">NEW</span><h2>Feature</h2></main></body></html>';
    $dom = new DOMDocument;
    $dom->loadHTML($html, LIBXML_NOERROR);

    $preprocessor->markEyebrows($dom, [], autoDetect: true);

    $output = $dom->saveHTML();
    expect($output)->toContain('data-llm-eyebrow="true"');
});

it('skips auto-detection when disabled', function () {
    $preprocessor = new HtmlPreprocessor;

    $html = '<html><body><main><span>CATEGORY</span><h1>Title</h1></main></body></html>';
    $dom = new DOMDocument;
    $dom->loadHTML($html, LIBXML_NOERROR);

    $preprocessor->markEyebrows($dom, [], autoDetect: false);

    $output = $dom->saveHTML();
    expect($output)->not->toContain('data-llm-eyebrow');
});

it('does not double-mark already marked elements', function () {
    $preprocessor = new HtmlPreprocessor;

    $html = '<html><body><main><span class="eyebrow">FEATURED</span><h1>Title</h1></main></body></html>';
    $dom = new DOMDocument;
    $dom->loadHTML($html, LIBXML_NOERROR);

    // Mark twice
    $preprocessor->markEyebrows($dom, ['.eyebrow'], autoDetect: true);
    $preprocessor->markEyebrows($dom, ['.eyebrow'], autoDetect: true);

    $output = $dom->saveHTML();
    // Should only have one instance of the eyebrow text
    expect(substr_count($output, 'FEATURED'))->toBe(1);
});

it('handles empty text nodes between elements', function () {
    $preprocessor = new HtmlPreprocessor;

    // Whitespace text node between span and h1
    $html = '<html><body><main><span>LABEL</span>   <h1>Title</h1></main></body></html>';
    $dom = new DOMDocument;
    $dom->loadHTML($html, LIBXML_NOERROR);

    $preprocessor->markEyebrows($dom, [], autoDetect: true);

    $output = $dom->saveHTML();
    expect($output)->toContain('data-llm-eyebrow="true"');
});

it('handles invalid css selectors gracefully', function () {
    $preprocessor = new HtmlPreprocessor;

    $html = '<html><body><main><h1>Title</h1></main></body></html>';
    $dom = new DOMDocument;
    $dom->loadHTML($html, LIBXML_NOERROR);

    // Should not throw an exception
    $preprocessor->markEyebrows($dom, ['[invalid[selector'], autoDetect: false);

    $output = $dom->saveHTML();
    expect($output)->toContain('Title');
});
