<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Package
    |--------------------------------------------------------------------------
    |
    | When disabled, .md URLs will not be intercepted and will 404 normally.
    |
    */

    'enabled' => env('LLM_READY_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for converted markdown pages.
    | Set 'enabled' to false to disable caching entirely.
    | TTL is in minutes.
    |
    */

    'cache' => [
        'enabled' => env('LLM_READY_CACHE_ENABLED', true),
        'ttl'     => env('LLM_READY_CACHE_TTL', 1440), // 24 hours in minutes
        'prefix'  => 'llm_ready',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Selectors
    |--------------------------------------------------------------------------
    |
    | CSS selectors used to identify main content areas.
    | These are tried in order - the first match is used.
    | Common patterns: 'main', 'article', '.content', '#main-content'
    |
    */

    'content_selectors' => [
        'main',
        'article',
        '[role="main"]',
        '.content',
        '.post-content',
        '.entry-content',
        '#content',
        '#main-content',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Selectors
    |--------------------------------------------------------------------------
    |
    | CSS selectors for elements to remove before conversion.
    | Navigation, footers, sidebars, scripts, and ads are typically removed.
    |
    */

    'ignore_selectors' => [
        'nav',
        'header',
        'footer',
        'aside',
        '.sidebar',
        '.navigation',
        '.menu',
        '.breadcrumb',
        '.breadcrumbs',
        '.advertisement',
        '.ad',
        '.ads',
        '.comments',
        '.comment-form',
        '.social-share',
        '.related-posts',
        'script',
        'style',
        'noscript',
        'iframe',
        'form',
        'button',
        '[role="navigation"]',
        '[role="banner"]',
        '[role="contentinfo"]',
        '[aria-hidden="true"]',
    ],

    /*
    |--------------------------------------------------------------------------
    | Eyebrow Selectors
    |--------------------------------------------------------------------------
    |
    | CSS selectors for "eyebrow" text - small labels that appear above headings.
    | These will be formatted as emphasized text (*EYEBROW*) in the markdown.
    | Auto-detection is also applied as a fallback for common patterns.
    |
    */

    'eyebrow_selectors' => [
        '.eyebrow',
        '.overline',
        '.kicker',
        '.super-title',
        '.pre-title',
        '.pre-heading',
        '.subtitle',
        '.tagline',
        '[class*="eyebrow"]',
        '[class*="overline"]',
        '[class*="kicker"]',
    ],

    /*
    |--------------------------------------------------------------------------
    | Eyebrow Auto-Detection
    |--------------------------------------------------------------------------
    |
    | Enable auto-detection of eyebrow text based on common patterns:
    | - Short uppercase text in spans before headings
    | - Small badge/chip/label elements
    | - Elements with small font sizes
    |
    */

    'eyebrow_auto_detect' => true,

    /*
    |--------------------------------------------------------------------------
    | Route Exclusion Patterns
    |--------------------------------------------------------------------------
    |
    | URL patterns that should NOT be converted to markdown.
    | Supports wildcard patterns using fnmatch() syntax.
    | Example: '/admin/*' excludes all admin routes.
    |
    */

    'exclude_patterns' => [
        '/admin/*',
        '/api/*',
        '/livewire/*',
        '/_ignition/*',
        '/telescope/*',
        '/horizon/*',
        '/pulse/*',
        '*/login',
        '*/logout',
        '*/register',
        '*/password/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | llms.txt Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the /llms.txt endpoint following the llmstxt.org spec.
    | See: https://llmstxt.org
    |
    | The llms.txt file provides LLMs with structured information about your
    | site and available Markdown content.
    |
    */

    'llms_txt' => [
        'enabled'   => env('LLM_READY_LLMS_TXT_ENABLED', true),
        'cache_ttl' => env('LLM_READY_LLMS_TXT_CACHE_TTL', 60), // 1 hour in minutes

        // H1 Title (required by spec) - defaults to app name
        'title' => env('LLM_READY_LLMS_TXT_TITLE'),

        // Blockquote summary - brief description of your site/project
        'summary' => env('LLM_READY_LLMS_TXT_SUMMARY'),

        // Detailed description paragraphs (array of strings)
        'description' => [
            // 'This site provides documentation for...',
            // 'Our main features include...',
        ],

        // Custom sections with curated links
        // Each section has a title (H2) and array of links
        // Links can be strings (URL only) or arrays with 'url' and 'description'
        'sections' => [
            // Example:
            // 'Documentation' => [
            //     ['url' => '/docs/getting-started.md', 'description' => 'Quick start guide'],
            //     ['url' => '/docs/api-reference.md', 'description' => 'Complete API documentation'],
            // ],
            // 'Blog' => [
            //     '/blog/latest-updates.md',
            //     '/blog/tutorials.md',
            // ],
        ],

        // Auto-generate a section with all discovered routes
        'auto_section' => [
            'enabled'             => true,
            'title'               => 'Pages', // H2 title for auto-generated section
            'include_in_optional' => false, // Wrap in "## Optional" section
        ],

        // Optional section - content that can be skipped for shorter context
        // Set to true to wrap auto-generated links in ## Optional
        'optional_section' => [
            'enabled' => false,
            'title'   => 'Optional',
            'content' => [
                // Links or text to include in optional section
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontmatter Configuration
    |--------------------------------------------------------------------------
    |
    | Configure what metadata to include in the YAML frontmatter.
    |
    */

    'frontmatter' => [
        'include_title'         => true,
        'include_description'   => true,
        'include_url'           => true,
        'include_last_modified' => true,
        'custom_fields'         => [
            // 'site_name' => config('app.name'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Extractor
    |--------------------------------------------------------------------------
    |
    | The class responsible for extracting main content from HTML.
    | You can create your own implementation of ContentExtractorInterface.
    |
    */

    'extractor' => \Daikazu\LaravelLlmReady\Extractors\DefaultContentExtractor::class,

];
