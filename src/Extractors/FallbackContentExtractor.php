<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Extractors;

use Daikazu\LaravelLlmReady\Contracts\ContentExtractorInterface;
use Daikazu\LaravelLlmReady\Extractors\Concerns\ExtractsInnerHtml;
use DOMDocument;

/**
 * Fallback extractor that simply returns the entire body content.
 * Use this when CSS selectors don't work for your site structure.
 */
final readonly class FallbackContentExtractor implements ContentExtractorInterface
{
    use ExtractsInnerHtml;

    public function extract(DOMDocument $dom, array $contentSelectors, array $ignoreSelectors): string
    {
        // Note: This extractor ignores selectors and just returns body content
        return $this->extractBodyContent($dom);
    }
}
