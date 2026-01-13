<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Support;

use DOMDocument;
use DOMXPath;
use Symfony\Component\Yaml\Yaml;

final readonly class FrontmatterGenerator
{
    /**
     * Generate YAML frontmatter from HTML document and URL.
     */
    public function generate(DOMDocument $dom, string $url, array $config): string
    {
        $frontmatter = [];

        if ($config['include_title'] ?? true) {
            $title = $this->extractTitle($dom);

            if ($title !== '') {
                $frontmatter['title'] = $title;
            }
        }

        if ($config['include_description'] ?? true) {
            $description = $this->extractDescription($dom);

            if ($description !== '') {
                $frontmatter['description'] = $description;
            }
        }

        if ($config['include_url'] ?? true) {
            $frontmatter['url'] = $url;
        }

        if ($config['include_last_modified'] ?? true) {
            $frontmatter['last_modified'] = now()->toIso8601String();
        }

        // Add any custom fields from config
        if (! empty($config['custom_fields'])) {
            foreach ($config['custom_fields'] as $key => $value) {
                if ($value !== null && $value !== '') {
                    $frontmatter[$key] = $value;
                }
            }
        }

        if ($frontmatter === []) {
            return '';
        }

        return "---\n" . Yaml::dump($frontmatter, 2, 2) . "---\n\n";
    }

    /**
     * Generate error frontmatter for 404 responses.
     */
    public function generateError(string $url, int $statusCode, string $message): string
    {
        $frontmatter = [
            'title'        => 'Page Not Found',
            'url'          => $url,
            'status'       => $statusCode,
            'error'        => $message,
            'generated_at' => now()->toIso8601String(),
        ];

        return "---\n" . Yaml::dump($frontmatter, 2, 2) . "---\n\n";
    }

    private function extractTitle(DOMDocument $dom): string
    {
        // Try <title> tag first
        $titles = $dom->getElementsByTagName('title');

        if ($titles->length > 0) {
            $title = $titles->item(0)->textContent ?? '';

            if ($title !== '') {
                return $this->cleanText($title);
            }
        }

        // Try <h1> as fallback
        $h1s = $dom->getElementsByTagName('h1');

        if ($h1s->length > 0) {
            $h1 = $h1s->item(0)->textContent ?? '';

            if ($h1 !== '') {
                return $this->cleanText($h1);
            }
        }

        // Try og:title meta tag
        $xpath = new DOMXPath($dom);
        $ogTitles = $xpath->query('//meta[@property="og:title"]/@content');

        if ($ogTitles !== false && $ogTitles->length > 0) {
            $ogTitle = $ogTitles->item(0)->nodeValue ?? '';

            if ($ogTitle !== '') {
                return $this->cleanText($ogTitle);
            }
        }

        return '';
    }

    private function extractDescription(DOMDocument $dom): string
    {
        $xpath = new DOMXPath($dom);

        // Try meta description
        $descriptions = $xpath->query('//meta[@name="description"]/@content');

        if ($descriptions !== false && $descriptions->length > 0) {
            $description = $descriptions->item(0)->nodeValue ?? '';

            if ($description !== '') {
                return $this->cleanText($description);
            }
        }

        // Try og:description
        $ogDescriptions = $xpath->query('//meta[@property="og:description"]/@content');

        if ($ogDescriptions !== false && $ogDescriptions->length > 0) {
            $ogDescription = $ogDescriptions->item(0)->nodeValue ?? '';

            if ($ogDescription !== '') {
                return $this->cleanText($ogDescription);
            }
        }

        return '';
    }

    private function cleanText(string $text): string
    {
        // Remove extra whitespace and normalize
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }
}
