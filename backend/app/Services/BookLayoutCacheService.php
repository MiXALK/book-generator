<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

readonly class BookLayoutCacheService
{
    public function catalogVersion(): int
    {
        return app(TemplateCatalogCacheService::class)->version();
    }

    /**
     * @return list<string>|null
     */
    public function getPageTexts(string $storyText): ?array
    {
        $key = $this->cacheKey($storyText);
        $cached = Cache::get($key);

        if (! is_array($cached)) {
            return null;
        }

        return array_values(array_filter($cached, fn ($text) => is_string($text)));
    }

    /**
     * @param  list<string>  $pageTexts
     */
    public function putPageTexts(string $storyText, array $pageTexts): void
    {
        $ttl = (int) config('services.scaling.layout_cache_ttl_seconds', 86400);

        if ($ttl <= 0) {
            return;
        }

        Cache::put($this->cacheKey($storyText), array_values($pageTexts), $ttl);
    }

    private function cacheKey(string $storyText): string
    {
        $storyHash = hash('sha256', $storyText);
        $version = $this->catalogVersion();

        return "layout:text:{$storyHash}:v{$version}";
    }
}
