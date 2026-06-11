<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateBookRequest;
use App\Repositories\Contracts\BookGenerationRepositoryInterface;
use App\Repositories\Contracts\BookTemplateRepositoryInterface;
use App\Services\BookGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookGenerationController extends Controller
{
    public function __construct(
        private readonly BookGenerationService $generationService,
        private readonly BookGenerationRepositoryInterface $bookGenerations,
        private readonly BookTemplateRepositoryInterface $bookTemplates,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->bookGenerations->listForUser($request->user()->id)
            ->map(fn ($generation) => $this->generationService->formatForApi($generation));

        return response()->json([
            'items' => $items,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $generation = $this->bookGenerations->findForUserById($request->user()->id, $id);

        if ($generation === null) {
            return response()->json([
                'message' => 'Book not found.',
            ], 404);
        }

        return response()->json([
            'generation' => $this->generationService->formatForApi($generation),
        ]);
    }

    public function generate(GenerateBookRequest $request): JsonResponse
    {
        $user = $request->user();
        $template = $this->bookTemplates->findActiveByStoryGoalName($request->goal());

        if ($user->plan === 'free' && ! $template->is_free) {
            return response()->json([
                'message' => 'This template is available only for paid users.',
            ], 403);
        }

        $this->generationService->ensureGenerationLimit($user);
        $generation = $this->generationService->generate(
            $user,
            $template,
            $request->childName(),
            $request->age(),
            $request->goal(),
        );

        return response()->json([
            'message' => 'Book generation completed.',
            'generation' => $generation,
        ], 201);
    }
}
