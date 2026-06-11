<?php

namespace App\Repositories\Eloquent;

use App\Models\BookTemplate;
use App\Repositories\Contracts\BookTemplateRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentBookTemplateRepository implements BookTemplateRepositoryInterface
{
    public function listActiveForCatalog(): Collection
    {
        return BookTemplate::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'description', 'is_free', 'template_type']);
    }

    public function findActiveById(int $id): BookTemplate
    {
        return BookTemplate::query()
            ->where('id', $id)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function findActiveByStoryGoalName(string $goalName): BookTemplate
    {
        return BookTemplate::query()
            ->where('is_active', true)
            ->whereHas('storyGoal', function ($query) use ($goalName) {
                $query->where('name', $goalName);
            })
            ->firstOrFail();
    }

    public function getOrderedScenes(BookTemplate $template): Collection
    {
        return $template->templateScenes()->orderBy('scene_number')->get();
    }
}
