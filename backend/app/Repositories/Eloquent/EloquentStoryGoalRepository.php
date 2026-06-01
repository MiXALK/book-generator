<?php

namespace App\Repositories\Eloquent;

use App\Models\StoryGoal;
use App\Repositories\Contracts\StoryGoalRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentStoryGoalRepository implements StoryGoalRepositoryInterface
{
    public function listForCatalog(): Collection
    {
        return StoryGoal::query()
            ->orderBy('name')
            ->get(['id', 'name', 'description']);
    }
}
