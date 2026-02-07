# Changelog

All notable changes to `laravel-llm-ready` will be documented in this file.

## v1.2.0 - 2026-02-07

### What's New

#### LLM Markdown Discovery

Three complementary mechanisms for LLMs and crawlers to discover that a markdown version of a page exists:

- **Automatic `Link` header** — Every HTML response includes a `Link` header pointing to the `.md` version (`rel="alternate"; type="text/markdown"`). Enabled by default, configurable via `LLM_READY_LINK_HEADER` env variable.
- **`@llmReady` Blade directive** — Drop into your `<head>` to render a `<link rel="alternate" type="text/markdown">` tag for crawler discovery.
- **`llmReadyUrl()` helper function** — Global helper for programmatic use in SEO middleware (e.g., `romanzipp/laravel-seo`).

#### New Files

- `DiscoveryService` — Shared URL resolution service
- `AddLinkHeader` middleware — Automatically adds Link header to web responses
- `helpers.php` — Global `llmReadyUrl()` function

#### Configuration

New `discovery.link_header` config option (defaults to `true`).

**Full Changelog**: https://github.com/daikazu/laravel-llm-ready/compare/v1.1.0...v1.2.0

## v1.1.0 - 2026-02-07

### What's Changed

#### Bug Fixes

- Fix 404 on `.md` URLs with dynamic route parameters (e.g. `/blog/{slug}`) (#2)

#### Improvements

- Replace catch-all routes with global `RewriteMarkdownExtension` middleware for more reliable `.md` URL handling
- Expand test coverage from 66.6% to 90.9% (93 tests, 200 assertions)

#### Breaking Changes

- Removed `MarkdownPageController` — `.md` requests are now handled entirely via middleware

**Full Changelog**: https://github.com/daikazu/laravel-llm-ready/compare/v1.0.1...v1.1.0

## v1.0.1 - 2026-01-13

**Full Changelog**: https://github.com/daikazu/laravel-llm-ready/compare/v1.0.0...v1.0.1

Update Symfony versions
Fix test
