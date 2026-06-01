<?php

namespace App\Repositories\Eloquent;

use App\Models\LayoutTemplate;
use App\Repositories\Contracts\LayoutTemplateRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentLayoutTemplateRepository implements LayoutTemplateRepositoryInterface
{
    public function findRandomActiveByCategory(string $category): ?LayoutTemplate
    {
        return LayoutTemplate::query()
            ->where('is_active', true)
            ->where('category', $category)
            ->inRandomOrder()
            ->first();
    }

    public function listRandomActiveByCategory(string $category, int $limit): Collection
    {
        return LayoutTemplate::query()
            ->where('is_active', true)
            ->where('category', $category)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function findRandomActive(): ?LayoutTemplate
    {
        return LayoutTemplate::query()
            ->where('is_active', true)
            ->inRandomOrder()
            ->first();
    }
}
