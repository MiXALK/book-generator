<?php

namespace App\Repositories\Eloquent;

use App\Enums\PublicationStatus;
use App\Models\BookTemplate;
use App\Repositories\Contracts\BookTemplateRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentBookTemplateRepository implements BookTemplateRepositoryInterface
{
    public function listActiveForCatalog(): Collection
    {
        return BookTemplate::query()
            ->with('storyGoal:id,description')
            ->where('is_active', true)
            ->where('publication_status', PublicationStatus::Published)
            ->orderBy('title')
            ->get(['id', 'title', 'is_free', 'story_goal_id']);
    }

    public function findActiveById(int $id): BookTemplate
    {
        return BookTemplate::query()
            ->with('storyGoal:id,description')
            ->where('id', $id)
            ->where('is_active', true)
            ->where('publication_status', PublicationStatus::Published)
            ->firstOrFail();
    }

    public function findActiveByStoryGoalName(string $goalName): BookTemplate
    {
        return BookTemplate::query()
            ->with('storyGoal:id,name,description')
            ->where('is_active', true)
            ->where('publication_status', PublicationStatus::Published)
            ->whereHas('storyGoal', function ($query) use ($goalName) {
                $query->where('name', $goalName);
            })
            ->firstOrFail();
    }
}
