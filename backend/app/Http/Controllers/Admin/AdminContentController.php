<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBookTemplateRequest;
use App\Http\Requests\Admin\StoreLayoutTemplateRequest;
use App\Http\Requests\Admin\StoreStoryGoalRequest;
use App\Http\Requests\Admin\StoreStoryPromptRatingRequest;
use App\Http\Requests\Admin\StoreStoryPromptRequest;
use App\Http\Requests\Admin\UpdateBookTemplateRequest;
use App\Http\Requests\Admin\UpdateLayoutTemplateRequest;
use App\Http\Requests\Admin\UpdateStoryGoalRequest;
use App\Http\Requests\Admin\UpdateStoryPromptRequest;
use App\Repositories\Contracts\Admin\AdminContentRepositoryInterface;
use App\Services\ContentPreviewService;
use App\Services\ContentPublicationService;
use App\Services\PromptQualityService;
use Illuminate\Http\JsonResponse;

class AdminContentController extends Controller
{
    public function __construct(
        private readonly AdminContentRepositoryInterface $content,
        private readonly ContentPublicationService $publication,
        private readonly ContentPreviewService $preview,
        private readonly PromptQualityService $promptQuality,
    ) {}

    public function reviewQueue(): JsonResponse
    {
        return response()->json([
            'items' => $this->content->listReviewQueue(),
        ]);
    }

    public function listGoals(): JsonResponse
    {
        return response()->json([
            'items' => $this->content->listGoals(),
        ]);
    }

    public function storeGoal(StoreStoryGoalRequest $request): JsonResponse
    {
        $goal = $this->content->createGoal($request->validated());

        return response()->json(['item' => $goal], 201);
    }

    public function showGoal(int $id): JsonResponse
    {
        return response()->json([
            'item' => $this->content->findGoal($id),
        ]);
    }

    public function updateGoal(UpdateStoryGoalRequest $request, int $id): JsonResponse
    {
        $goal = $this->content->findGoal($id);
        $updated = $this->content->updateGoal($goal, $request->validated());

        return response()->json(['item' => $updated]);
    }

    public function destroyGoal(int $id): JsonResponse
    {
        $goal = $this->content->findGoal($id);
        $this->content->deleteGoal($goal);

        return response()->json(['message' => 'Goal deleted.']);
    }

    public function listTemplates(): JsonResponse
    {
        return response()->json([
            'items' => $this->content->listTemplates(),
        ]);
    }

    public function storeTemplate(StoreBookTemplateRequest $request): JsonResponse
    {
        $attributes = array_merge($request->validated(), [
            'publication_status' => PublicationStatus::Draft,
            'version' => 1,
            'is_active' => $request->boolean('is_active', false),
        ]);

        $template = $this->content->createTemplate($attributes);

        return response()->json(['item' => $template], 201);
    }

    public function showTemplate(int $id): JsonResponse
    {
        return response()->json([
            'item' => $this->content->findTemplate($id),
        ]);
    }

    public function updateTemplate(UpdateBookTemplateRequest $request, int $id): JsonResponse
    {
        $template = $this->content->findTemplate($id);
        $updated = $this->content->updateTemplate($template, $request->validated());

        return response()->json(['item' => $updated]);
    }

    public function destroyTemplate(int $id): JsonResponse
    {
        $template = $this->content->findTemplate($id);
        $this->content->deleteTemplate($template);

        return response()->json(['message' => 'Template deleted.']);
    }

    public function submitTemplateReview(int $id): JsonResponse
    {
        $template = $this->content->findTemplate($id);
        $updated = $this->publication->submitBookTemplateForReview($template);

        return response()->json(['item' => $updated]);
    }

    public function publishTemplate(int $id): JsonResponse
    {
        $template = $this->content->findTemplate($id);
        $updated = $this->publication->publishBookTemplate($template);

        return response()->json(['item' => $updated]);
    }

    public function previewTemplate(int $id): JsonResponse
    {
        $template = $this->content->findTemplate($id);

        return response()->json($this->preview->previewBookTemplate($template));
    }

    public function listPrompts(): JsonResponse
    {
        return response()->json([
            'items' => $this->content->listPrompts(),
        ]);
    }

    public function storePrompt(StoreStoryPromptRequest $request): JsonResponse
    {
        $attributes = array_merge($request->validated(), [
            'publication_status' => PublicationStatus::Draft,
            'version' => 1,
            'quality_score' => 0,
            'rating_count' => 0,
            'usage_count' => 0,
            'is_active' => $request->boolean('is_active', false),
        ]);

        $prompt = $this->content->createPrompt($attributes);

        return response()->json(['item' => $prompt], 201);
    }

    public function showPrompt(int $id): JsonResponse
    {
        return response()->json([
            'item' => $this->content->findPrompt($id),
        ]);
    }

    public function updatePrompt(UpdateStoryPromptRequest $request, int $id): JsonResponse
    {
        $prompt = $this->content->findPrompt($id);
        $updated = $this->content->updatePrompt($prompt, $request->validated());

        return response()->json(['item' => $updated]);
    }

    public function destroyPrompt(int $id): JsonResponse
    {
        $prompt = $this->content->findPrompt($id);
        $this->content->deletePrompt($prompt);

        return response()->json(['message' => 'Prompt deleted.']);
    }

    public function submitPromptReview(int $id): JsonResponse
    {
        $prompt = $this->content->findPrompt($id);
        $updated = $this->publication->submitStoryPromptForReview($prompt);

        return response()->json(['item' => $updated]);
    }

    public function publishPrompt(int $id): JsonResponse
    {
        $prompt = $this->content->findPrompt($id);
        $updated = $this->publication->publishStoryPrompt($prompt);

        return response()->json(['item' => $updated]);
    }

    public function storePromptRating(StoreStoryPromptRatingRequest $request, int $id): JsonResponse
    {
        $prompt = $this->content->findPrompt($id);
        $userId = $request->user()?->id;

        $this->content->createPromptRating(
            $prompt,
            $userId,
            (int) $request->input('rating'),
            $request->input('notes'),
        );

        $updated = $this->promptQuality->recalculateScore($prompt);

        return response()->json(['item' => $updated], 201);
    }

    public function previewPrompt(int $id): JsonResponse
    {
        $prompt = $this->content->findPrompt($id);

        return response()->json($this->preview->previewStoryPrompt($prompt));
    }

    public function listLayouts(): JsonResponse
    {
        return response()->json([
            'items' => $this->content->listLayouts(),
        ]);
    }

    public function storeLayout(StoreLayoutTemplateRequest $request): JsonResponse
    {
        $attributes = array_merge($request->validated(), [
            'publication_status' => PublicationStatus::Draft,
            'version' => 1,
            'is_active' => $request->boolean('is_active', false),
        ]);

        $layout = $this->content->createLayout($attributes);

        return response()->json(['item' => $layout], 201);
    }

    public function showLayout(int $id): JsonResponse
    {
        return response()->json([
            'item' => $this->content->findLayout($id),
        ]);
    }

    public function updateLayout(UpdateLayoutTemplateRequest $request, int $id): JsonResponse
    {
        $layout = $this->content->findLayout($id);
        $updated = $this->content->updateLayout($layout, $request->validated());

        return response()->json(['item' => $updated]);
    }

    public function destroyLayout(int $id): JsonResponse
    {
        $layout = $this->content->findLayout($id);
        $this->content->deleteLayout($layout);

        return response()->json(['message' => 'Layout deleted.']);
    }

    public function submitLayoutReview(int $id): JsonResponse
    {
        $layout = $this->content->findLayout($id);
        $updated = $this->publication->submitLayoutForReview($layout);

        return response()->json(['item' => $updated]);
    }

    public function publishLayout(int $id): JsonResponse
    {
        $layout = $this->content->findLayout($id);
        $updated = $this->publication->publishLayout($layout);

        return response()->json(['item' => $updated]);
    }

    public function previewLayout(int $id): JsonResponse
    {
        $layout = $this->content->findLayout($id);

        return response()->json($this->preview->previewLayout($layout));
    }

    public function qualityConfig(): JsonResponse
    {
        return response()->json([
            'min_quality_score' => config('services.content.prompt_min_quality_score'),
            'min_rating_count' => config('services.content.prompt_min_rating_count'),
        ]);
    }
}
