<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMText;
use DOMXPath;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Throwable;

final readonly class HtmlPreprocessor
{
    private CssSelectorConverter $cssConverter;

    public function __construct()
    {
        $this->cssConverter = new CssSelectorConverter;
    }

    /**
     * Preprocess HTML to mark eyebrow elements before markdown conversion.
     */
    public function markEyebrows(DOMDocument $dom, array $eyebrowSelectors, bool $autoDetect = true): void
    {
        $xpath = new DOMXPath($dom);

        // First, mark elements matching configured selectors
        foreach ($eyebrowSelectors as $selector) {
            $this->markElementsBySelector($dom, $xpath, $selector);
        }

        // Then auto-detect common eyebrow patterns
        if ($autoDetect) {
            $this->autoDetectEyebrows($dom, $xpath);
        }
    }

    /**
     * Mark elements matching a CSS selector as eyebrows.
     */
    private function markElementsBySelector(DOMDocument $dom, DOMXPath $xpath, string $selector): void
    {
        try {
            $xpathQuery = $this->cssConverter->toXPath($selector);
            /** @var DOMNodeList<DOMNode>|false $nodes */
            $nodes = $xpath->query($xpathQuery);

            if ($nodes === false) {
                return;
            }

            foreach ($nodes as $node) {
                if ($node instanceof DOMElement) {
                    $this->wrapAsEyebrow($dom, $node);
                }
            }
        } catch (Throwable) {
            // Invalid selector - skip
        }
    }

    /**
     * Auto-detect eyebrow elements based on common patterns.
     */
    private function autoDetectEyebrows(DOMDocument $dom, DOMXPath $xpath): void
    {
        // Pattern 1: Short uppercase text in span/div immediately before h1-h6
        $this->detectEyebrowsBeforeHeadings($dom, $xpath);

        // Pattern 2: Elements with badge/chip/label/tag classes
        $badgeSelectors = [
            '[class*="badge"]',
            '[class*="chip"]',
            '[class*="label"]',
            '[class*="tag"]',
            '[class*="category"]',
        ];

        foreach ($badgeSelectors as $selector) {
            try {
                $xpathQuery = $this->cssConverter->toXPath($selector);
                /** @var DOMNodeList<DOMNode>|false $nodes */
                $nodes = $xpath->query($xpathQuery);

                if ($nodes === false) {
                    continue;
                }

                foreach ($nodes as $node) {
                    if ($node instanceof DOMElement && $this->looksLikeEyebrow($node)) {
                        $this->wrapAsEyebrow($dom, $node);
                    }
                }
            } catch (Throwable) {
                continue;
            }
        }
    }

    /**
     * Detect short text elements immediately before headings.
     */
    private function detectEyebrowsBeforeHeadings(DOMDocument $dom, DOMXPath $xpath): void
    {
        // Find all headings
        /** @var DOMNodeList<DOMNode>|false $headings */
        $headings = $xpath->query('//h1|//h2|//h3|//h4|//h5|//h6');

        if ($headings === false) {
            return;
        }

        foreach ($headings as $heading) {
            // Check the previous sibling
            $prev = $heading->previousSibling;

            // Skip text nodes (whitespace)
            while ($prev instanceof DOMText && trim($prev->nodeValue ?? '') === '') {
                $prev = $prev->previousSibling;
            }

            if ($prev instanceof DOMElement && $this->looksLikeEyebrow($prev)) {
                $this->wrapAsEyebrow($dom, $prev);
            }
        }
    }

    /**
     * Check if an element looks like an eyebrow based on content patterns.
     */
    private function looksLikeEyebrow(DOMElement $element): bool
    {
        // Skip if already marked
        if ($element->getAttribute('data-llm-eyebrow') === 'true') {
            return false;
        }

        // Skip if any child is already marked as eyebrow
        $children = $element->getElementsByTagName('*');
        foreach ($children as $child) {
            if ($child->getAttribute('data-llm-eyebrow') === 'true') {
                return false;
            }
        }

        $text = trim($element->textContent ?? '');

        // Must have text
        if ($text === '') {
            return false;
        }

        // Must be short (eyebrows are typically 1-3 words)
        if (mb_strlen($text) > 40) {
            return false;
        }

        // Should not contain links or complex content
        if ($element->getElementsByTagName('a')->length > 0) {
            return false;
        }

        // Bonus points for uppercase text
        $isUppercase = mb_strtoupper($text) === $text && preg_match('/[A-Z]/', $text);

        // Bonus points for certain tag names
        $tagName = strtolower($element->tagName);
        $isEyebrowTag = in_array($tagName, ['span', 'div', 'p', 'small'], true);

        // Bonus points for single word or short phrase
        $wordCount = str_word_count($text);
        $isShort = $wordCount <= 3;

        // Must meet at least 2 criteria
        $score = ($isUppercase ? 1 : 0) + ($isEyebrowTag ? 1 : 0) + ($isShort ? 1 : 0);

        return $score >= 2;
    }

    /**
     * Wrap an element's content to mark it as an eyebrow.
     * Replaces the element with a <p><em>text</em></p> to ensure block-level separation.
     */
    private function wrapAsEyebrow(DOMDocument $dom, DOMElement $element): void
    {
        // Skip if already marked
        if ($element->getAttribute('data-llm-eyebrow') === 'true') {
            return;
        }

        $text = trim($element->textContent ?? '');
        if ($text === '') {
            return;
        }

        // Mark the element
        $element->setAttribute('data-llm-eyebrow', 'true');

        // Create a paragraph with emphasized text for block-level separation
        $p = $dom->createElement('p');
        $p->setAttribute('data-llm-eyebrow', 'true');
        $em = $dom->createElement('em');
        $em->textContent = $text;
        $p->appendChild($em);

        // Replace the original element with the new paragraph
        $element->parentNode?->replaceChild($p, $element);
    }
}
