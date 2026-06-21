<?php

namespace App\Services;

use App\Models\StoryPrompt;
use App\Repositories\Contracts\Admin\AdminContentRepositoryInterface;
use Illuminate\Http\Exceptions\HttpResponseException;

readonly class PromptQualityService
{
    public function __construct(
        private AdminContentRepositoryInterface $adminContent,
    ) {}

    public function recalculateScore(StoryPrompt $prompt): StoryPrompt
    {
        $prompt->load('ratings');

        $ratings = $prompt->ratings;

        if ($ratings->isEmpty()) {
            return $this->adminContent->updatePrompt($prompt, [
                'quality_score' => 0,
                'rating_count' => 0,
            ]);
        }

        $average = round($ratings->avg('rating'), 2);

        return $this->adminContent->updatePrompt($prompt, [
            'quality_score' => $average,
            'rating_count' => $ratings->count(),
        ]);
    }

    public function meetsActivationThreshold(StoryPrompt $prompt): bool
    {
        $minScore = (float) config('services.content.prompt_min_quality_score');
        $minCount = (int) config('services.content.prompt_min_rating_count');

        return $prompt->rating_count >= $minCount
            && (float) $prompt->quality_score >= $minScore;
    }

    public function ensurePublishable(StoryPrompt $prompt): void
    {
        if (! $this->meetsActivationThreshold($prompt)) {
            throw new HttpResponseException(response()->json([
                'message' => 'Prompt does not meet minimum quality threshold for publication.',
                'min_quality_score' => config('services.content.prompt_min_quality_score'),
                'min_rating_count' => config('services.content.prompt_min_rating_count'),
                'current_quality_score' => $prompt->quality_score,
                'current_rating_count' => $prompt->rating_count,
            ], 422));
        }
    }
}
