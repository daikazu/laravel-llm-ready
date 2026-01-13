<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Support;

final readonly class MarkdownCleaner
{
    /**
     * Clean and normalize markdown output.
     */
    public function clean(string $markdown): string
    {
        // Normalize line endings first
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);

        // Remove any null characters or other control characters
        $markdown = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $markdown) ?? $markdown;

        // Fix headings that appear mid-line (e.g., "Text ### Heading" -> "## Heading")
        $markdown = $this->fixInlineHeadings($markdown);

        // Fix link formatting: [ text ](#url) -> [text](#url)
        $markdown = $this->fixLinkFormatting($markdown);

        // Fix excessive emphasis: ***text*** -> *text* for short labels
        $markdown = $this->fixExcessiveEmphasis($markdown);

        // Fix price formatting: $ 10 -> $10
        $markdown = $this->fixPriceFormatting($markdown);

        // Collapse multiple spaces into single space (except at line start)
        $markdown = preg_replace('/(?<!^)(?<!\n) {2,}/', ' ', $markdown) ?? $markdown;

        // Remove spaces at beginning of lines (except for code blocks and list indentation)
        $markdown = $this->fixLineStartSpaces($markdown);

        // Ensure proper spacing around headings
        $markdown = $this->normalizeHeadingSpacing($markdown);

        // Remove excessive blank lines (more than 2 consecutive)
        $markdown = preg_replace('/\n{3,}/', "\n\n", $markdown) ?? $markdown;

        // Remove trailing whitespace from each line
        $markdown = preg_replace('/[ \t]+$/m', '', $markdown) ?? $markdown;

        // Remove leading/trailing whitespace from the entire document
        $markdown = trim($markdown);

        // Ensure document ends with single newline
        $markdown .= "\n";

        return $markdown;
    }

    /**
     * Fix headings that appear mid-line.
     * "Pricing ### Flexible pricing plan" becomes:
     * "*Pricing*
     *
     * ## Flexible pricing plan"
     */
    private function fixInlineHeadings(string $markdown): string
    {
        // Match lines where heading markers appear after other text
        $markdown = preg_replace_callback(
            '/^(.+?)\s+(#{1,6})\s+(.+)$/m',
            function (array $matches): string {
                $beforeText = trim($matches[1]);
                $headingLevel = $matches[2];
                $headingText = trim($matches[3]);

                // If before text is short (eyebrow/label), format it as emphasized text
                if (strlen($beforeText) < 30 && ! str_contains($beforeText, '.')) {
                    // Format eyebrow as small caps style (uppercase = label)
                    if (strtoupper($beforeText) === $beforeText) {
                        return "*{$beforeText}*\n\n{$headingLevel} {$headingText}";
                    }

                    return "**{$beforeText}**\n\n{$headingLevel} {$headingText}";
                }

                // Keep both as separate lines
                return "{$beforeText}\n\n{$headingLevel} {$headingText}";
            },
            $markdown
        ) ?? $markdown;

        return $markdown;
    }

    /**
     * Fix excessive emphasis (bold+italic) on short text.
     * ***Text*** -> *Text*
     */
    private function fixExcessiveEmphasis(string $markdown): string
    {
        // Convert ***text*** to *text* for short labels (likely eyebrows)
        $markdown = preg_replace_callback(
            '/\*{3}([^*]+)\*{3}/',
            function (array $matches): string {
                $text = $matches[1];
                // Only simplify if it's short (eyebrow-like)
                if (mb_strlen($text) <= 40) {
                    return "*{$text}*";
                }

                return $matches[0];
            },
            $markdown
        ) ?? $markdown;

        // Also handle **text** -> *text* for uppercase labels that should be eyebrows
        $markdown = preg_replace_callback(
            '/^\*\*([A-Z][A-Z\s]{0,30})\*\*$/m',
            fn (array $matches): string => "*{$matches[1]}*",
            $markdown
        ) ?? $markdown;

        return $markdown;
    }

    /**
     * Fix link formatting with extra spaces.
     * [ Link Text ](#url) -> [Link Text](#url)
     * [ Link Text ]( #url ) -> [Link Text](#url)
     */
    private function fixLinkFormatting(string $markdown): string
    {
        // Fix spaces inside link text brackets
        $markdown = preg_replace('/\[\s+([^\]]+?)\s+\]/', '[$1]', $markdown) ?? $markdown;

        // Fix spaces inside URL parentheses
        $markdown = preg_replace('/\]\(\s+([^)]+?)\s+\)/', ']($1)', $markdown) ?? $markdown;

        // Fix space between ] and (
        $markdown = preg_replace('/\]\s+\(/', '](', $markdown) ?? $markdown;

        return $markdown;
    }

    /**
     * Fix price formatting.
     * $ 10 -> $10
     * $ 10.99 -> $10.99
     * 10 /mo -> 10/mo
     * $$ 10 -> $10 (fix double dollar signs)
     */
    private function fixPriceFormatting(string $markdown): string
    {
        // Fix double dollar signs first (from conversion artifacts)
        $markdown = preg_replace('/\${2,}(\d)/', '\$$1', $markdown) ?? $markdown;

        // Fix space after currency symbol
        $markdown = preg_replace('/\$\s+(\d)/', '\$$1', $markdown) ?? $markdown;

        // Fix space before /mo, /yr, /month, /year, etc.
        $markdown = preg_replace('/\s+\/(mo|yr|month|year|week|day)\b/i', '/$1', $markdown) ?? $markdown;

        return $markdown;
    }

    /**
     * Fix leading spaces on lines (preserve list indentation and code blocks).
     */
    private function fixLineStartSpaces(string $markdown): string
    {
        $lines = explode("\n", $markdown);
        $inCodeBlock = false;
        $result = [];

        foreach ($lines as $line) {
            // Track code blocks
            if (str_starts_with(trim($line), '```')) {
                $inCodeBlock = ! $inCodeBlock;
                $result[] = $line;

                continue;
            }

            // Don't modify code blocks
            if ($inCodeBlock) {
                $result[] = $line;

                continue;
            }

            // Preserve list indentation (lines starting with -, *, +, or numbers)
            if (preg_match('/^\s*([-*+]|\d+\.)\s/', $line)) {
                // Normalize list indentation to 2 spaces per level
                $trimmed = ltrim($line);
                $spaces = strlen($line) - strlen($trimmed);
                $indentLevel = (int) floor($spaces / 2);
                $result[] = str_repeat('  ', $indentLevel) . $trimmed;

                continue;
            }

            // Remove leading spaces from regular lines
            $result[] = ltrim($line);
        }

        return implode("\n", $result);
    }

    /**
     * Normalize spacing around headings.
     */
    private function normalizeHeadingSpacing(string $markdown): string
    {
        // Ensure blank line before headings (unless at start of document)
        $markdown = preg_replace('/(?<!\n)\n(#{1,6}\s)/m', "\n\n$1", $markdown) ?? $markdown;

        // Ensure single blank line after headings
        $markdown = preg_replace('/(#{1,6}\s[^\n]+)\n(?!\n)/', "$1\n\n", $markdown) ?? $markdown;

        // Fix double ## that might occur from the above
        $markdown = preg_replace('/^\s*(#{1,6})\s*(#{1,6})\s*/m', '$1 ', $markdown) ?? $markdown;

        return $markdown;
    }
}
