<?php

namespace App\Repositories\Eloquent;

use App\Models\AgeRange;
use App\Repositories\Contracts\AgeRangeRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentAgeRangeRepository implements AgeRangeRepositoryInterface
{
    public function listForCatalog(): Collection
    {
        return AgeRange::query()
            ->orderBy('min_age')
            ->get(['id', 'label', 'min_age', 'max_age']);
    }
}
