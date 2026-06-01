<?php

namespace App\Repositories\Eloquent;

use App\Models\StoryPrompt;
use App\Repositories\Contracts\StoryPromptRepositoryInterface;

class EloquentStoryPromptRepository implements StoryPromptRepositoryInterface
{
    public function findBestForGeneration(string $language, int $age, string $goal): ?StoryPrompt
    {
        return StoryPrompt::query()
            ->where('is_active', true)
            ->where('language', $language)
            ->where(function ($inner) use ($goal) {
                $inner->whereNull('story_goal_id')
                    ->orWhereHas('storyGoal', function ($goalQuery) use ($goal) {
                        $goalQuery->where('name', $goal);
                    });
            })
            ->where(function ($inner) use ($age) {
                $inner->whereNull('age_range_id')
                    ->orWhereHas('ageRange', function ($rangeQuery) use ($age) {
                        $rangeQuery->where('min_age', '<=', $age)->where('max_age', '>=', $age);
                    });
            })
            ->orderByDesc('quality_score')
            ->orderByDesc('rating_count')
            ->first();
    }

    public function incrementUsageCount(StoryPrompt $prompt): void
    {
        $prompt->increment('usage_count');
    }
}
