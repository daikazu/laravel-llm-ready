<?php

declare(strict_types=1);

namespace Daikazu\LaravelLlmReady\Http\Controllers;

use Daikazu\LaravelLlmReady\Services\SitemapGeneratorService;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

final class LlmsTxtController extends Controller
{
    public function __construct(
        private readonly SitemapGeneratorService $sitemapGenerator,
    ) {}

    /**
     * Serve the llms.txt sitemap.
     */
    public function __invoke(): Response
    {
        $cacheKey = Config::get('llm-ready.cache.prefix', 'llm_ready') . ':sitemap';
        $cacheTtl = Config::get('llm-ready.llms_txt.cache_ttl', 60);

        $content = Cache::remember($cacheKey, now()->addMinutes($cacheTtl), fn (): string => $this->sitemapGenerator->generate());

        return new Response(
            content: $content,
            status: 200,
            headers: [
                'Content-Type'  => 'text/markdown; charset=UTF-8',
                'X-LLM-Ready'   => 'true',
                'Cache-Control' => 'public, max-age=' . ($cacheTtl * 60),
            ]
        );
    }
}
