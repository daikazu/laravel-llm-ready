# Serve LLM-optimized markdown versions of your Laravel pages by appending .md to any URL.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/daikazu/laravel-llm-ready.svg?style=flat-square)](https://packagist.org/packages/daikazu/laravel-llm-ready)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/laravel-llm-ready/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/daikazu/laravel-llm-ready/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/daikazu/laravel-llm-ready/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/daikazu/laravel-llm-ready/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/daikazu/laravel-llm-ready.svg?style=flat-square)](https://packagist.org/packages/daikazu/laravel-llm-ready)


## Purpose

Large Language Models (LLMs) like ChatGPT and Claude work much better with clean markdown than parsing HTML. This package automatically converts your Laravel pages to markdown, making your site more accessible to AI crawlers, tools, and users who want to feed your content to LLMs.

## Features

- **Automatic Markdown Conversion**: Append `.md` to any URL or use `?format=md`
- **Smart Content Extraction**: Configurable CSS selectors find your main content
- **Table Support**: HTML tables converted to GitHub Flavored Markdown tables
- **Eyebrow Detection**: Automatically detects and formats eyebrow/label text
- **YAML Frontmatter**: Includes title, description, URL, and timestamps
- **Caching**: TTL-based caching with artisan command for manual invalidation
- **llms.txt Endpoint**: Spec-compliant sitemap at `/llms.txt` ([llmstxt.org](https://llmstxt.org))
- **Route Exclusions**: Pattern-based exclusions for admin, API, and other routes
- **Redirect Handling**: Automatically follows redirects (e.g., `/blog` -> `/blog/`)
- **Extensible**: Swap the content extractor with your own implementation

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x

## Installation

```bash
composer require daikazu/laravel-llm-ready
```

The package will auto-discover and register itself.

### Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=llm-ready-config
```

### Server Configuration

Your web server needs to pass `.md` requests to Laravel instead of trying to serve them as static files.

#### Apache

The default Laravel `.htaccess` should work. Ensure it contains:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

#### Nginx

Add this to your server block:

```nginx
location ~ \.md$ {
    try_files /index.php?$query_string =404;
    fastcgi_pass unix:/var/run/php/php-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root/index.php;
    include fastcgi_params;
}
```

#### Laravel Valet/Herd

Works out of the box - no configuration needed.

## Usage

### Accessing Markdown Versions

There are three ways to get the markdown version of a page:

```
# Method 1: Append .md extension
https://yoursite.com/about-us.md

# Method 2: Use ?format=md query parameter
https://yoursite.com/?format=md
https://yoursite.com/about-us?format=md

# Method 3: Use index.md for index pages
https://yoursite.com/index.md        # Home page
https://yoursite.com/blog/index.md   # Blog index
```

### llms.txt Endpoint

The `/llms.txt` endpoint follows the [llmstxt.org](https://llmstxt.org) specification, providing LLMs with structured information about your site:

```
https://yoursite.com/llms.txt
```

Example output:

```markdown
# Your Site Name

> A brief description of your site.

All pages on this site are available in markdown format for LLM consumption.
Append `.md` to any URL or add `?format=md` to get the markdown version.

## Documentation

- [Getting Started](https://yoursite.com/docs/getting-started)
- [API Reference](https://yoursite.com/docs/api)

## Pages

- [Home](https://yoursite.com)
- [About](https://yoursite.com/about)
- [Blog](https://yoursite.com/blog)
```

### Clear Cache

```bash
# Clear all LLM Ready caches
php artisan llm-ready:clear-cache

# Clear cache for a specific URL
php artisan llm-ready:clear-cache --url=https://yoursite.com/about-us

# Clear only the sitemap cache
php artisan llm-ready:clear-cache --sitemap
```

## Configuration

```php
// config/llm-ready.php

return [
    // Enable/disable the package
    'enabled' => env('LLM_READY_ENABLED', true),

    // Cache settings
    'cache' => [
        'enabled' => env('LLM_READY_CACHE_ENABLED', true),
        'ttl' => env('LLM_READY_CACHE_TTL', 1440), // 24 hours in minutes
        'prefix' => 'llm_ready',
    ],

    // CSS selectors to find main content (tried in order)
    'content_selectors' => [
        'main',
        'article',
        '[role="main"]',
        '.content',
    ],

    // Elements to remove before conversion
    'ignore_selectors' => [
        'nav',
        'header',
        'footer',
        'aside',
        '.sidebar',
        'script',
        'style',
    ],

    // Eyebrow/label text detection
    'eyebrow_selectors' => [
        '.eyebrow',
        '.overline',
        '.kicker',
        '[class*="eyebrow"]',
    ],
    'eyebrow_auto_detect' => true,

    // Routes to exclude from markdown conversion (supports nested paths)
    'exclude_patterns' => [
        '/admin/*',    // Matches /admin/users, /admin/settings/email, etc.
        '/api/*',      // Matches /api/v1/users, /api/webhooks/stripe, etc.
        '/livewire/*',
    ],

    // llms.txt configuration (follows llmstxt.org spec)
    'llms_txt' => [
        'enabled' => true,
        'cache_ttl' => 60,
        'title' => env('LLM_READY_LLMS_TXT_TITLE'), // Defaults to app name
        'summary' => env('LLM_READY_LLMS_TXT_SUMMARY'),
        'description' => [],
        'sections' => [
            // 'Documentation' => [
            //     ['url' => '/docs/intro.md', 'description' => 'Introduction'],
            // ],
        ],
        'auto_section' => [
            'enabled' => true,
            'title' => 'Pages',
        ],
    ],

    // YAML frontmatter settings
    'frontmatter' => [
        'include_title' => true,
        'include_description' => true,
        'include_url' => true,
        'include_last_modified' => true,
    ],

    // Custom content extractor class
    'extractor' => \Daikazu\LaravelLlmReady\Extractors\DefaultContentExtractor::class,
];
```

## Output Example

Requesting `/about-us.md` produces clean markdown:

```markdown
---
title: About Us - Your Company Name
description: Learn about our mission and team
url: https://yoursite.com/about-us
last_modified: 2024-01-15T10:30:00+00:00
---

# About Us

Welcome to our company! We're dedicated to...

## Our Mission

We believe in creating value for our customers through...

## Our Team

| Name | Role | Experience |
|------|------|------------|
| Jane Doe | CEO | 15 years |
| John Smith | CTO | 12 years |
```

## Configuring llms.txt

The llms.txt endpoint can be fully customized to provide curated information to LLMs:

```php
'llms_txt' => [
    'title' => 'Acme Documentation',
    'summary' => 'Complete documentation for the Acme platform.',
    'description' => [
        'Acme helps developers build better applications faster.',
        'Our documentation covers installation, configuration, and advanced usage.',
    ],
    'sections' => [
        'Getting Started' => [
            ['url' => '/docs/installation', 'description' => 'How to install Acme'],
            ['url' => '/docs/quickstart', 'description' => '5-minute quickstart guide'],
        ],
        'API Reference' => [
            ['url' => '/docs/api/authentication', 'description' => 'Authentication methods'],
            ['url' => '/docs/api/endpoints', 'description' => 'Available API endpoints'],
        ],
    ],
    'auto_section' => [
        'enabled' => true,
        'title' => 'All Pages',
        'include_in_optional' => false,
    ],
],
```

## Custom Content Extractor

Create your own extractor by implementing `ContentExtractorInterface`:

```php
<?php

namespace App\LlmReady;

use Daikazu\LaravelLlmReady\Contracts\ContentExtractorInterface;
use DOMDocument;

class CustomContentExtractor implements ContentExtractorInterface
{
    public function extract(DOMDocument $dom, array $contentSelectors, array $ignoreSelectors): string
    {
        // Your custom extraction logic
    }
}
```

Then update your config:

```php
'extractor' => \App\LlmReady\CustomContentExtractor::class,
```

## Environment Variables

```env
LLM_READY_ENABLED=true
LLM_READY_CACHE_ENABLED=true
LLM_READY_CACHE_TTL=1440
LLM_READY_LLMS_TXT_ENABLED=true
LLM_READY_LLMS_TXT_CACHE_TTL=60
LLM_READY_LLMS_TXT_TITLE="My Site"
LLM_READY_LLMS_TXT_SUMMARY="A brief description of my site."
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Mike Wall](https://github.com/daikazu)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
