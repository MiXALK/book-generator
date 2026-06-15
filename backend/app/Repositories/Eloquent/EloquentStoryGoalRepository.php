<?php

namespace App\Repositories\Eloquent;

use App\Models\BookTemplate;
use App\Models\StoryGoal;
use App\Repositories\Contracts\StoryGoalRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentStoryGoalRepository implements StoryGoalRepositoryInterface
{
    public function listForCatalog(bool $hasPaidAccess): Collection
    {
        return StoryGoal::query()
            ->with(['bookTemplate:id,story_goal_id,is_free,is_active'])
            ->whereHas('bookTemplate', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'description'])
            ->map(function (StoryGoal $goal) use ($hasPaidAccess) {
                $template = $goal->bookTemplate;
                $isFree = $template instanceof BookTemplate ? $template->is_free : true;

                return [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'description' => $goal->description,
                    'is_locked' => ! $hasPaidAccess && ! $isFree,
                ];
            })
            ->values();
    }
}
