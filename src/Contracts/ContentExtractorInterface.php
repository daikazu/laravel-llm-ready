<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Contracts;

use DOMDocument;

interface ContentExtractorInterface
{
    /**
     * Extract main content from an HTML document.
     *
     * @param  DOMDocument  $dom  The parsed HTML document
     * @param  array<string>  $contentSelectors  CSS selectors to find main content
     * @param  array<string>  $ignoreSelectors  CSS selectors for elements to remove
     * @return string The extracted HTML content
     */
    public function extract(DOMDocument $dom, array $contentSelectors, array $ignoreSelectors): string;
}
