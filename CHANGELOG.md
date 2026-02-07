# Changelog

All notable changes to `laravel-llm-ready` will be documented in this file.

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
