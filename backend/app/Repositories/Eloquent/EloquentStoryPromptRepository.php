<?php

namespace App\Repositories\Eloquent;

use App\Models\StoryPrompt;
use App\Repositories\Contracts\StoryPromptRepositoryInterface;

class EloquentStoryPromptRepository implements StoryPromptRepositoryInterface
{
    public function findBestForGeneration(string $language, int $age, string $goal): ?StoryPrompt
    {
        $strategies = [
            fn () => $this->queryCandidates($language, $age, $goal),
            fn () => $this->queryCandidates($language, null, $goal),
            fn () => $this->queryCandidates($language, $age, null),
            fn () => $this->queryCandidates($language, null, null),
        ];

        foreach ($strategies as $strategy) {
            $prompt = $strategy()->first();

            if ($prompt !== null) {
                return $prompt;
            }
        }

        return null;
    }

    private function queryCandidates(string $language, ?int $age, ?string $goal)
    {
        return StoryPrompt::query()
            ->where('is_active', true)
            ->where('language', $language)
            ->when($goal !== null, function ($query) use ($goal) {
                $query->where(function ($inner) use ($goal) {
                    $inner->whereNull('story_goal_id')
                        ->orWhereHas('storyGoal', function ($goalQuery) use ($goal) {
                            $goalQuery->where('name', $goal);
                        });
                });
            })
            ->when($age !== null, function ($query) use ($age) {
                $query->where(function ($inner) use ($age) {
                    $inner->whereNull('age_range_id')
                        ->orWhereHas('ageRange', function ($rangeQuery) use ($age) {
                            $rangeQuery->where('min_age', '<=', $age)->where('max_age', '>=', $age);
                        });
                });
            })
            ->orderByDesc('quality_score')
            ->orderByDesc('rating_count');
    }

    public function incrementUsageCount(StoryPrompt $prompt): void
    {
        $prompt->increment('usage_count');
    }
}
