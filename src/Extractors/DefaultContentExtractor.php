<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Extractors;

use Daikazu\LaravelLlmReady\Contracts\ContentExtractorInterface;
use Daikazu\LaravelLlmReady\Extractors\Concerns\ExtractsInnerHtml;
use DOMDocument;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Throwable;

final readonly class DefaultContentExtractor implements ContentExtractorInterface
{
    use ExtractsInnerHtml;

    private CssSelectorConverter $cssConverter;

    public function __construct()
    {
        $this->cssConverter = new CssSelectorConverter;
    }

    public function extract(DOMDocument $dom, array $contentSelectors, array $ignoreSelectors): string
    {
        $xpath = new DOMXPath($dom);

        // First, remove all ignored elements from the entire document
        $this->removeIgnoredElements($xpath, $ignoreSelectors);

        // Try each content selector until we find matching content
        foreach ($contentSelectors as $selector) {
            $content = $this->extractBySelector($dom, $xpath, $selector);

            if ($content !== '') {
                return $content;
            }
        }

        // Fallback: return body content if no selector matched
        return $this->extractBodyContent($dom);
    }

    private function removeIgnoredElements(DOMXPath $xpath, array $ignoreSelectors): void
    {
        foreach ($ignoreSelectors as $selector) {
            try {
                $xpathQuery = $this->cssConverter->toXPath($selector);
                $nodes = $xpath->query($xpathQuery);

                if (! $nodes instanceof DOMNodeList) {
                    continue;
                }

                // Collect nodes first, then remove (to avoid modifying during iteration)
                $nodesToRemove = [];
                foreach ($nodes as $node) {
                    $nodesToRemove[] = $node;
                }

                foreach ($nodesToRemove as $node) {
                    $node->parentNode?->removeChild($node);
                }
            } catch (Throwable) {
                // Invalid selector - skip it
                continue;
            }
        }
    }

    private function extractBySelector(DOMDocument $dom, DOMXPath $xpath, string $selector): string
    {
        try {
            $xpathQuery = $this->cssConverter->toXPath($selector);
            /** @var DOMNodeList<DOMNode>|false $nodes */
            $nodes = $xpath->query($xpathQuery);

            if ($nodes === false || $nodes->length === 0) {
                return '';
            }

            // Get the first matching node's inner HTML
            $node = $nodes->item(0);

            if ($node === null) {
                return '';
            }

            return $this->getInnerHtml($dom, $node);
        } catch (Throwable) {
            return '';
        }
    }
}
