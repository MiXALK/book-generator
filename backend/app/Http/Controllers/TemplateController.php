<?php

namespace App\Http\Controllers;

use App\Enums\AgeRange;
use App\Repositories\Contracts\StoryGoalRepositoryInterface;
use Illuminate\Http\JsonResponse;

class TemplateController extends Controller
{
    public function __construct(
        private readonly StoryGoalRepositoryInterface $storyGoals,
    ) {}

    public function catalog(): JsonResponse
    {
        return response()->json([
            'goals' => $this->storyGoals->listForCatalog(),
            'age_ranges' => AgeRange::catalog(),
        ]);
    }
}
