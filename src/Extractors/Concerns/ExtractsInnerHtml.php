<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Extractors\Concerns;

use DOMDocument;
use DOMNode;

trait ExtractsInnerHtml
{
    /**
     * Get the inner HTML content of a DOM node.
     */
    private function getInnerHtml(DOMDocument $dom, DOMNode $node): string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $saved = $dom->saveHTML($child);

            if ($saved === false) {
                continue;
            }

            $html .= $saved;
        }

        return trim($html);
    }

    /**
     * Extract content from the body element.
     */
    private function extractBodyContent(DOMDocument $dom): string
    {
        $bodies = $dom->getElementsByTagName('body');

        if ($bodies->length === 0) {
            return '';
        }

        $body = $bodies->item(0);

        if ($body === null) {
            return '';
        }

        return $this->getInnerHtml($dom, $body);
    }
}
