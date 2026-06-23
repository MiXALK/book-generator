<?php

namespace App\Services;

use App\Repositories\Contracts\StoryGoalRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

readonly class TemplateCatalogCacheService
{
    private const string VERSION_KEY = 'catalog:version';

    public function __construct(
        private StoryGoalRepositoryInterface $storyGoals,
    ) {}

    public function version(): int
    {
        $version = Cache::get(self::VERSION_KEY);

        if (is_int($version)) {
            return $version;
        }

        if (is_string($version) && is_numeric($version)) {
            return (int) $version;
        }

        return 1;
    }

    public function bumpVersion(): void
    {
        if (! Cache::has(self::VERSION_KEY)) {
            Cache::forever(self::VERSION_KEY, 2);

            return;
        }

        Cache::increment(self::VERSION_KEY);
    }

    public function listGoalsForCatalog(bool $hasPaidAccess): Collection
    {
        $ttl = (int) config('services.scaling.catalog_cache_ttl_seconds', 3600);

        if ($ttl <= 0) {
            return $this->storyGoals->listForCatalog($hasPaidAccess);
        }

        $version = $this->version();
        $cacheKey = "catalog:goals:v{$version}:".($hasPaidAccess ? 'paid' : 'free');

        $items = Cache::remember($cacheKey, $ttl, function () use ($hasPaidAccess) {
            return $this->storyGoals->listForCatalog($hasPaidAccess)->values()->all();
        });

        return collect(is_array($items) ? $items : []);
    }
}
