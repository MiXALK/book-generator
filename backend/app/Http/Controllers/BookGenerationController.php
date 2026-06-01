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
        return response()->json([
            'items' => $this->bookGenerations->listForUser($request->user()->id),
        ]);
    }

    public function generate(GenerateBookRequest $request): JsonResponse
    {
        $user = $request->user();
        $template = $this->bookTemplates->findActiveById($request->bookTemplateId());

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
