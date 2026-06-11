<?php

namespace App\Repositories\Eloquent;

use App\Enums\AgeRange;
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
                $matchingRanges = array_map(
                    fn (AgeRange $range) => $range->value,
                    array_filter(AgeRange::cases(), fn (AgeRange $range) => $range->contains($age)),
                );

                $query->where(function ($inner) use ($matchingRanges) {
                    $inner->whereNull('age_range');

                    if ($matchingRanges !== []) {
                        $inner->orWhereIn('age_range', $matchingRanges);
                    }
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
