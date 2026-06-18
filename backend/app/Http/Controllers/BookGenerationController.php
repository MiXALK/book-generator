<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateBookRequest;
use App\Models\BookPage;
use App\Repositories\Contracts\BookGenerationRepositoryInterface;
use App\Repositories\Contracts\BookTemplateRepositoryInterface;
use App\Services\BookGenerationService;
use App\Services\BookIllustrationStorageService;
use App\Services\IllustrationGenerationService;
use App\Services\SubscriptionAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class BookGenerationController extends Controller
{
    public function __construct(
        private readonly BookGenerationService $generationService,
        private readonly BookIllustrationStorageService $illustrationStorage,
        private readonly IllustrationGenerationService $illustrationGeneration,
        private readonly BookGenerationRepositoryInterface $bookGenerations,
        private readonly BookTemplateRepositoryInterface $bookTemplates,
        private readonly SubscriptionAccessService $subscriptionAccess,
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

        if (! $this->subscriptionAccess->canAccessTemplate($user, $template)) {
            return response()->json([
                'message' => 'This template is available only for active Premium subscribers.',
            ], 403);
        }

        $lock = Cache::lock('book-generation:'.$user->id, 120);

        if (! $lock->get()) {
            return response()->json([
                'message' => 'A book generation is already in progress. Please wait.',
            ], 429);
        }

        try {
            $this->generationService->ensureGenerationLimit($user);
            $generation = $this->generationService->generate(
                $user,
                $template,
                $request->childName(),
                $request->age(),
                $request->goal(),
                $request->uploadedPhotoId(),
            );
        } finally {
            $lock->release();
        }

        return response()->json([
            'message' => 'Book generation completed.',
            'generation' => $generation,
        ], 201);
    }

    public function retryIllustrations(Request $request, int $id): JsonResponse
    {
        $generation = $this->bookGenerations->findForUserIllustrationRetry($request->user()->id, $id);

        if ($generation === null) {
            return response()->json([
                'message' => 'Illustration retry is not available for this book.',
            ], 422);
        }

        $this->illustrationGeneration->retryGeneration($generation);

        $fresh = $this->bookGenerations->findForUserById($request->user()->id, $id);

        if ($fresh === null) {
            return response()->json([
                'message' => 'Book not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Illustration generation restarted.',
            'generation' => $this->generationService->formatForApi($fresh),
        ]);
    }

    public function pageImage(int $id, int $page): Response
    {
        $bookPage = BookPage::query()
            ->where('book_generation_id', $id)
            ->where('page_number', $page)
            ->first();

        if ($bookPage === null) {
            abort(404);
        }

        $path = $bookPage->getAttributes()['image_url'] ?? null;

        if (! is_string($path) || $path === '') {
            abort(404);
        }

        $payload = $this->illustrationStorage->readForResponse($path);

        if ($payload === null) {
            abort(404);
        }

        return response($payload['binary'], 200, [
            'Content-Type' => $payload['content_type'],
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
