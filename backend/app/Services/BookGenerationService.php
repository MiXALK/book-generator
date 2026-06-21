<?php

namespace App\Services;

use App\Models\BookGeneration;
use App\Models\BookTemplate;
use App\Models\ChildProfile;
use App\Models\GeneratedCharacter;
use App\Models\StoryPrompt;
use App\Models\UploadedPhoto;
use App\Models\User;
use App\Repositories\Contracts\BookGenerationRepositoryInterface;
use App\Repositories\Contracts\BookPageRepositoryInterface;
use App\Repositories\Contracts\ChildProfileRepositoryInterface;
use App\Repositories\Contracts\LayoutTemplateRepositoryInterface;
use App\Repositories\Contracts\StoryPromptRepositoryInterface;
use App\Repositories\Contracts\UploadedPhotoRepositoryInterface;
use App\Services\Ai\Contracts\StoryTextGenerationProviderInterface;
use App\Services\Ai\Data\StoryTextGenerationInput;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookGenerationService
{
    private const string DEFAULT_PROMPT_TEXT = 'Напиши цельную добрую детскую сказку про {name} (возраст {age}) с целью {goal}. '.
        'Один мягкий сюжетный поворот, безопасный финал.';

    public function __construct(
        private readonly BookGenerationRepositoryInterface $bookGenerations,
        private readonly BookPageRepositoryInterface $bookPages,
        private readonly StoryPromptRepositoryInterface $storyPrompts,
        private readonly LayoutTemplateRepositoryInterface $layoutTemplates,
        private readonly StoryTextGenerationProviderInterface $storyTextProvider,
        private readonly BookIllustrationStorageService $illustrationStorage,
        private readonly StoryPaginator $storyPaginator,
        private readonly SubscriptionAccessService $subscriptionAccess,
        private readonly ChildProfileRepositoryInterface $childProfiles,
        private readonly UploadedPhotoRepositoryInterface $uploadedPhotos,
        private readonly IllustrationGenerationService $illustrationGeneration,
        private readonly BookGenerationObservabilityService $observability,
    ) {}

    public function formatForApi(BookGeneration $generation): BookGeneration
    {
        $loaded = $this->bookGenerations->loadForApi($generation);
        $this->illustrationStorage->resolveGenerationImageUrls($loaded);

        return $loaded;
    }

    public function ensureGenerationLimit(User $user): void
    {
        $limit = $this->subscriptionAccess->monthlyLimit($user);
        $count = $this->bookGenerations->countForUserInCurrentMonth($user->id);

        if ($count >= $limit) {
            throw new HttpResponseException(response()->json([
                'message' => 'Monthly generation limit reached.',
                'limit' => $limit,
            ], 422));
        }
    }

    public function generate(
        User $user,
        BookTemplate $template,
        string $childName,
        int $age,
        string $goal,
        ?int $uploadedPhotoId = null,
    ): BookGeneration {
        $photo = $this->resolveUploadedPhoto($user, $uploadedPhotoId);
        $prompt = $this->selectPrompt($user, $age, $goal);

        return DB::transaction(function () use ($user, $template, $childName, $age, $goal, $prompt, $photo) {
            $correlationId = $this->observability->newCorrelationId();
            $profile = $this->resolveChildProfile($user, $childName, $age, $photo);
            $character = $photo !== null
                ? $this->illustrationGeneration->resolveOrCreateCharacter($profile, $childName, $age, $photo)
                : null;

            $generation = $this->createGeneration(
                $user,
                $template,
                $childName,
                $age,
                $goal,
                $prompt,
                $profile,
                $photo,
                $character,
                $correlationId,
            );

            return $this->observability->withContext($generation->id, $correlationId, function () use (
                $generation,
                $correlationId,
                $childName,
                $age,
                $goal,
                $prompt,
                $photo,
            ) {
                $this->observability->logStage(
                    $generation->id,
                    $correlationId,
                    'generation',
                    'Book generation started',
                );

                $built = $this->buildPages($generation->id, $correlationId, $childName, $age, $goal, $prompt);
                $pages = $this->attachPlaceholderIllustrations($generation->id, $built['pages'], $built['layouts']);

                $this->bookPages->createMany($generation, $pages);

                if ($photo !== null && $this->illustrationGeneration->shouldGenerateIllustrations($photo)) {
                    $this->bookGenerations->updateStatus($generation, 'processing');
                    $this->illustrationGeneration->queueGeneration($generation);
                } elseif ($photo !== null) {
                    $this->illustrationGeneration->finalizeWithoutProvider($generation, $photo);
                } else {
                    $this->bookGenerations->updateStatus($generation, 'completed');
                    $this->observability->logStage(
                        $generation->id,
                        $correlationId,
                        'generation',
                        'Book generation completed',
                        [
                            'text_duration_ms' => $built['text_duration_ms'],
                            'layout_duration_ms' => $built['layout_duration_ms'],
                        ],
                    );
                    $this->observability->notifyBookReady($generation);
                }

                $this->recordPromptUsage($prompt);

                return $this->formatForApi($generation);
            });
        });
    }

    private function resolveUploadedPhoto(User $user, ?int $uploadedPhotoId): ?UploadedPhoto
    {
        if ($uploadedPhotoId === null) {
            return null;
        }

        if (! $this->subscriptionAccess->canUploadPhoto($user)) {
            throw new HttpResponseException(response()->json([
                'message' => 'Photo personalization is available only for active Premium subscribers.',
            ], 403));
        }

        $photo = $this->uploadedPhotos->findPendingForUser($user->id, $uploadedPhotoId);

        if ($photo === null) {
            throw new HttpResponseException(response()->json([
                'message' => 'Uploaded photo not found or already used.',
            ], 422));
        }

        return $photo;
    }

    private function resolveChildProfile(User $user, string $childName, int $age, ?UploadedPhoto $photo): ChildProfile
    {
        $profile = $this->childProfiles->findForUserByName($user->id, $childName);

        if ($profile === null) {
            $profile = $this->childProfiles->create([
                'user_id' => $user->id,
                'child_name' => $childName,
                'child_age' => $age,
            ]);
        } else {
            $this->childProfiles->updateAge($profile, $age);
        }

        if ($photo !== null && $photo->child_profile_id === null) {
            $this->uploadedPhotos->attachChildProfile($photo, $profile->id);
        }

        return $profile;
    }

    private function createGeneration(
        User $user,
        BookTemplate $template,
        string $childName,
        int $age,
        string $goal,
        ?StoryPrompt $prompt,
        ChildProfile $profile,
        ?UploadedPhoto $photo,
        ?GeneratedCharacter $character,
        string $correlationId,
    ): BookGeneration {
        return $this->bookGenerations->create([
            'user_id' => $user->id,
            'book_template_id' => $template->id,
            'story_prompt_id' => $prompt?->id,
            'child_profile_id' => $profile->id,
            'uploaded_photo_id' => $photo?->id,
            'generated_character_id' => $character?->id,
            'child_name' => $childName,
            'child_age' => $age,
            'child_goal' => $goal,
            'prompt_snapshot' => $prompt?->prompt_text,
            'book_template_snapshot' => [
                'id' => $template->id,
                'version' => $template->version,
                'title' => $template->title,
                'description' => $template->description,
                'is_free' => $template->is_free,
                'template_type' => $template->template_type,
            ],
            'status' => 'processing',
            'illustration_status' => $photo !== null ? 'queued' : null,
            'correlation_id' => $correlationId,
        ]);
    }

    private function recordPromptUsage(?StoryPrompt $prompt): void
    {
        if ($prompt) {
            $this->storyPrompts->incrementUsageCount($prompt);
        }
    }

    private function selectPrompt(User $user, int $age, string $goal): ?StoryPrompt
    {
        return $this->storyPrompts->findBestForGeneration($user->language ?? 'ru', $age, $goal);
    }

    private function buildPages(
        int $generationId,
        string $correlationId,
        string $name,
        int $age,
        string $goal,
        ?StoryPrompt $prompt,
    ): array {
        $textMeasured = $this->observability->measure(
            fn () => $this->resolveStoryText($generationId, $correlationId, $name, $age, $goal, $prompt),
        );
        $storyText = $textMeasured['result'];

        $layoutMeasured = $this->observability->measure(function () use ($storyText) {
            $pageTexts = $this->storyPaginator->paginate($storyText);
            $pageCount = count($pageTexts);
            $layouts = $this->pickLayouts($pageCount);

            $pages = [];
            foreach ($pageTexts as $index => $text) {
                $pages[] = [
                    'page_number' => $index + 1,
                    'layout_template_id' => $layouts[$index]?->id,
                    'text' => $text,
                ];
            }

            return [
                'pages' => $pages,
                'layouts' => $layouts,
            ];
        });

        $generation = $this->bookGenerations->findWithUser($generationId);

        if ($generation !== null) {
            $this->observability->recordLatencyMetrics($generation, [
                'text_duration_ms' => $textMeasured['duration_ms'],
                'layout_duration_ms' => $layoutMeasured['duration_ms'],
            ]);
        }

        $this->observability->logStage(
            $generationId,
            $correlationId,
            'layout',
            'Story text and layout assembly completed',
            [
                'text_duration_ms' => $textMeasured['duration_ms'],
                'layout_duration_ms' => $layoutMeasured['duration_ms'],
            ],
        );

        return [
            'pages' => $layoutMeasured['result']['pages'],
            'layouts' => $layoutMeasured['result']['layouts'],
            'text_duration_ms' => $textMeasured['duration_ms'],
            'layout_duration_ms' => $layoutMeasured['duration_ms'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     */
    private function attachPlaceholderIllustrations(int $generationId, array $pages, Collection $layouts): array
    {
        foreach ($pages as $index => $page) {
            $layout = $layouts[$index] ?? null;
            $category = is_object($layout) && isset($layout->category)
                ? (string) $layout->category
                : 'content';
            $pageNumber = (int) $page['page_number'];
            $pages[$index]['image_url'] = $this->illustrationStorage->storePlaceholder(
                $generationId,
                $pageNumber,
                $category,
            );
        }

        return $pages;
    }

    private function resolveStoryText(
        int $generationId,
        string $correlationId,
        string $name,
        int $age,
        string $goal,
        ?StoryPrompt $prompt,
    ): string {
        $story = $this->generateStoryWithAi($generationId, $correlationId, $name, $age, $goal, $prompt);

        if ($story !== null && trim($story) !== '') {
            return $story;
        }

        return $this->buildFallbackStory($name, $age, $goal);
    }

    private function generateStoryWithAi(
        int $generationId,
        string $correlationId,
        string $name,
        int $age,
        string $goal,
        ?StoryPrompt $prompt,
    ): ?string {
        if (! $this->storyTextProvider->isConfigured()) {
            return null;
        }

        $promptText = $this->resolvePromptText($prompt, $name, $age, $goal);

        $input = new StoryTextGenerationInput(
            promptText: $promptText,
            childName: $name,
            childAge: $age,
            childGoal: $goal,
        );

        $story = $this->storyTextProvider->generateStory($input);

        if ($story === null) {
            $this->observability->logStage(
                $generationId,
                $correlationId,
                'text',
                'Story text generation returned no story; using fallback text',
                ['story_prompt_id' => $prompt?->id],
                'warning',
            );
        } else {
            $this->observability->logStage(
                $generationId,
                $correlationId,
                'text',
                'Story text generation completed',
                ['story_prompt_id' => $prompt?->id],
            );
        }

        return $story;
    }

    private function resolvePromptText(?StoryPrompt $prompt, string $name, int $age, string $goal): string
    {
        if ($prompt === null) {
            $template = self::DEFAULT_PROMPT_TEXT;
        } else {
            $template = $prompt->prompt_text;
        }

        return strtr($template, [
            '{name}' => $name,
            '{age}' => (string) $age,
            '{goal}' => $goal,
        ]);
    }

    private function buildFallbackStory(string $name, int $age, string $goal): string
    {
        $sentences = [];
        $sentenceCount = 8;
        $midpoint = (int) ceil($sentenceCount / 2);

        for ($pageNumber = 1; $pageNumber <= $sentenceCount; $pageNumber++) {
            $sentences[] = $this->fallbackText($name, $age, $goal, $pageNumber, $sentenceCount, $midpoint);
        }

        return implode(' ', $sentences);
    }

    private function fallbackText(string $name, int $age, string $goal, int $pageNumber, int $totalPages, int $midpoint): string
    {
        return match (true) {
            $pageNumber === 1 => "{$name}, {$age}, начинает историю о цели: {$goal}.",
            $pageNumber === $midpoint => "Неожиданно сюжет меняется, но {$name} не сдается.",
            $pageNumber === $totalPages => "{$name} достигает цели {$goal} и радуется финалу.",
            default => "{$name} делает шаг к цели {$goal} и становится смелее.",
        };
    }

    private function pickLayouts(int $pagesCount): Collection
    {
        $cover = $this->layoutTemplates->findRandomActiveByCategory('cover');
        $ending = $this->layoutTemplates->findRandomActiveByCategory('ending');
        $content = $this->layoutTemplates->listRandomActiveByCategory('content', max(0, $pagesCount - 2));

        $layouts = collect();

        if ($cover) {
            $layouts->push($cover);
        }

        $layouts = $layouts->merge($content);

        if ($ending) {
            $layouts->push($ending);
        }

        while ($layouts->count() < $pagesCount) {
            $fallback = $this->layoutTemplates->findRandomActive();
            if (! $fallback) {
                break;
            }
            $layouts->push($fallback);
        }

        return $layouts->take($pagesCount)->values();
    }
}
