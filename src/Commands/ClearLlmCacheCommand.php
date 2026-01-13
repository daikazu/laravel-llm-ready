<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Commands;

use Daikazu\LaravelLlmReady\Services\MarkdownConverterService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

final class ClearLlmCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'llm-ready:clear-cache
                            {--url= : Clear cache for a specific URL only}
                            {--sitemap : Clear only the sitemap cache}';

    /**
     * The console command description.
     */
    protected $description = 'Clear the LLM Ready markdown cache';

    public function __construct(
        private readonly MarkdownConverterService $converter,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $url = $this->option('url');
        $sitemapOnly = $this->option('sitemap');

        if ($sitemapOnly) {
            $this->clearSitemapCache();
            $this->components->info('Sitemap cache cleared successfully.');

            return self::SUCCESS;
        }

        if ($url !== null) {
            $this->converter->clearCache($url);
            $this->components->info("Cache cleared for URL: {$url}");

            return self::SUCCESS;
        }

        // Clear all caches
        $this->clearSitemapCache();
        $this->converter->clearCache();

        $this->components->info('LLM Ready cache cleared.');
        $this->components->warn('Note: Page caches are keyed by URL hash. Use --url to clear specific pages.');

        return self::SUCCESS;
    }

    private function clearSitemapCache(): void
    {
        $prefix = Config::get('llm-ready.cache.prefix', 'llm_ready');
        Cache::forget("{$prefix}:sitemap");
    }
}
