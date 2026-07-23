<?php

namespace App\Repositories\Eloquent;

use App\Enums\PublicationStatus;
use App\Models\LayoutTemplate;
use App\Repositories\Contracts\LayoutTemplateRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentLayoutTemplateRepository implements LayoutTemplateRepositoryInterface
{
    public function findById(int $id): ?LayoutTemplate
    {
        return LayoutTemplate::query()->find($id);
    }

    public function listRandomActive(int $limit): Collection
    {
        return LayoutTemplate::query()
            ->where('is_active', true)
            ->where('publication_status', PublicationStatus::Published)
            ->inRandomOrder()
            ->limit(max(0, $limit))
            ->get();
    }

    public function findRandomActive(): ?LayoutTemplate
    {
        return LayoutTemplate::query()
            ->where('is_active', true)
            ->where('publication_status', PublicationStatus::Published)
            ->inRandomOrder()
            ->first();
    }
}
