<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\AgeRangeRepositoryInterface;
use App\Repositories\Contracts\StoryGoalRepositoryInterface;
use Illuminate\Http\JsonResponse;

class TemplateController extends Controller
{
    public function __construct(
        private readonly StoryGoalRepositoryInterface $storyGoals,
        private readonly AgeRangeRepositoryInterface $ageRanges,
    ) {}

    public function catalog(): JsonResponse
    {
        return response()->json([
            'goals' => $this->storyGoals->listForCatalog(),
            'age_ranges' => $this->ageRanges->listForCatalog(),
        ]);
    }
}
