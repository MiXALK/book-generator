<?php

namespace App\Services;

use App\Jobs\AssembleBookLayoutJob;
use App\Jobs\GenerateBookTextJob;
use App\Models\BookGeneration;
use App\Models\BookTemplate;
use App\Models\ChildProfile;
use App\Models\GeneratedCharacter;
use App\Models\LayoutTemplate;
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
use App\Services\Ai\Data\StoryTextGenerationResult;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookGenerationService
{
    private const int ESTIMATED_ILLUSTRATION_PAGES = 8;

    private const int FALLBACK_SENTENCE_COUNT = 8;

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
        private readonly BookGenerationIdempotencyService $idempotency,
        private readonly BookLayoutCacheService $layoutCache,
        private readonly AiOperationQuotaService $aiQuotas,
        private readonly BookGenerationCostService $costTracking,
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
        string $childGender,
        string $goal,
        ?int $uploadedPhotoId = null,
        ?string $idempotencyKey = null,
    ): BookGeneration {
        $existing = $this->idempotency->findExisting($user, $idempotencyKey);

        if ($existing !== null) {
            return $this->formatForApi($existing);
        }

        $photo = $this->resolveUploadedPhoto($user, $uploadedPhotoId);
        $prompt = $this->selectPrompt($user, $age, $goal);
        $fingerprint = $this->idempotency->computeFingerprint(
            $user,
            $template,
            $childName,
            $age,
            $childGender,
            $goal,
            $prompt,
            $uploadedPhotoId,
            $idempotencyKey,
        );

        $this->ensureAiQuotas($user);

        $generation = $this->persistNewGeneration(
            $user,
            $template,
            $childName,
            $age,
            $childGender,
            $goal,
            $prompt,
            $photo,
            $idempotencyKey,
            $fingerprint,
        );

        return $this->startTextPipeline($generation);
    }

    public function runTextGeneration(int $generationId): void
    {
        $generation = $this->bookGenerations->findWithUser($generationId);

        if ($generation === null || $generation->story_text !== null) {
            return;
        }

        $correlationId = $this->correlationIdFor($generation);
        $prompt = $this->findPromptForGeneration($generation);

        $this->observability->withContext($generationId, $correlationId, function () use (
            $generation,
            $generationId,
            $correlationId,
            $prompt,
        ) {
            $this->logTextStageStarted($generationId, $correlationId);

            $textMeasured = $this->observability->measure(
                fn () => $this->resolveStoryText(
                    $generationId,
                    $correlationId,
                    $generation->child_name,
                    (int) $generation->child_age,
                    $generation->child_goal,
                    $prompt,
                ),
            );

            $this->persistTextGenerationResults($generation, $generationId, $correlationId, $textMeasured);

            AssembleBookLayoutJob::dispatch($generationId);
        });
    }

    public function runLayoutAssembly(int $generationId): void
    {
        $generation = $this->bookGenerations->findWithUser($generationId);

        if ($generation === null || ! is_string($generation->story_text) || $generation->story_text === '') {
            return;
        }

        if ($this->bookGenerations->generationHasPages($generationId)) {
            return;
        }

        $correlationId = $this->correlationIdFor($generation);
        $prompt = $this->findPromptForGeneration($generation);

        $this->observability->withContext($generationId, $correlationId, function () use (
            $generation,
            $generationId,
            $correlationId,
            $prompt,
        ) {
            $layoutMeasured = $this->observability->measure(
                fn () => $this->assemblePages($generation->story_text),
            );

            $this->persistLayoutResults($generation, $generationId, $correlationId, $layoutMeasured);
            $this->finalizeGeneration($generation, $generationId, $correlationId, $layoutMeasured['duration_ms']);
            $this->recordPromptUsage($prompt);
        });
    }

    private function ensureAiQuotas(User $user): void
    {
        if ($this->storyTextProvider->isConfigured()) {
            $this->aiQuotas->ensureCanGenerateText($user);
        }

        if ($this->illustrationGeneration->shouldGenerateIllustrations()) {
            $this->aiQuotas->ensureCanGenerateImages($user, self::ESTIMATED_ILLUSTRATION_PAGES);
        }
    }

    private function persistNewGeneration(
        User $user,
        BookTemplate $template,
        string $childName,
        int $age,
        string $childGender,
        string $goal,
        ?StoryPrompt $prompt,
        ?UploadedPhoto $photo,
        ?string $idempotencyKey,
        string $fingerprint,
    ): BookGeneration {
        return DB::transaction(function () use (
            $user,
            $template,
            $childName,
            $age,
            $childGender,
            $goal,
            $prompt,
            $photo,
            $idempotencyKey,
            $fingerprint,
        ) {
            $correlationId = $this->observability->newCorrelationId();
            $profile = $this->resolveChildProfile($user, $childName, $age, $childGender, $photo);
            $character = $this->illustrationGeneration->resolveOrCreateCharacter(
                $profile,
                $childName,
                $age,
                $childGender,
                $photo,
            );

            $generation = $this->createGeneration(
                $user,
                $template,
                $childName,
                $age,
                $childGender,
                $goal,
                $prompt,
                $profile,
                $photo,
                $character,
                $correlationId,
                $idempotencyKey,
                $fingerprint,
            );

            if ($idempotencyKey !== null && $idempotencyKey !== '') {
                $this->idempotency->remember($user, $idempotencyKey, $generation);
            }

            return $generation;
        });
    }

    private function startTextPipeline(BookGeneration $generation): BookGeneration
    {
        GenerateBookTextJob::dispatch($generation->id);
        $generation->refresh();

        return $this->formatForApi($generation);
    }

    private function correlationIdFor(BookGeneration $generation): string
    {
        return $generation->correlation_id ?? $this->observability->newCorrelationId();
    }

    private function findPromptForGeneration(BookGeneration $generation): ?StoryPrompt
    {
        if ($generation->story_prompt_id === null) {
            return null;
        }

        return $this->storyPrompts->findById((int) $generation->story_prompt_id);
    }

    private function logTextStageStarted(int $generationId, string $correlationId): void
    {
        $this->observability->logStage(
            $generationId,
            $correlationId,
            'text',
            'Story text generation started',
        );
    }

    /**
     * @param  array{result: array{story: string, used_ai: bool, prompt_tokens: int|null, completion_tokens: int|null}, duration_ms: int}  $textMeasured
     */
    private function persistTextGenerationResults(
        BookGeneration $generation,
        int $generationId,
        string $correlationId,
        array $textMeasured,
    ): void {
        $result = $textMeasured['result'];

        $this->bookGenerations->updateStoryText($generation, $result['story']);

        $this->observability->recordLatencyMetrics($generation, [
            'text_duration_ms' => $textMeasured['duration_ms'],
        ]);

        if ($result['used_ai'] && $generation->user !== null) {
            $this->aiQuotas->recordTextGeneration($generation->user);
        }

        $this->costTracking->recordTextTokens(
            $generation,
            $result['prompt_tokens'],
            $result['completion_tokens'],
        );

        $this->observability->logStage(
            $generationId,
            $correlationId,
            'text',
            'Story text generation completed',
            ['text_duration_ms' => $textMeasured['duration_ms']],
        );
    }

    /**
     * @param  array{result: array{pages: list<array<string, mixed>>, layouts: Collection<int, LayoutTemplate>}, duration_ms: int}  $layoutMeasured
     */
    private function persistLayoutResults(
        BookGeneration $generation,
        int $generationId,
        string $correlationId,
        array $layoutMeasured,
    ): void {
        $built = $layoutMeasured['result'];
        $pages = $this->attachPlaceholderIllustrations($generationId, $built['pages']);

        $this->bookPages->createMany($generation, $pages);

        $this->observability->recordLatencyMetrics($generation, [
            'layout_duration_ms' => $layoutMeasured['duration_ms'],
        ]);

        $this->costTracking->recordLayoutDuration($generation, $layoutMeasured['duration_ms']);

        $this->observability->logStage(
            $generationId,
            $correlationId,
            'layout',
            'Story text and layout assembly completed',
            ['layout_duration_ms' => $layoutMeasured['duration_ms']],
        );
    }

    private function finalizeGeneration(
        BookGeneration $generation,
        int $generationId,
        string $correlationId,
        int $layoutDurationMs,
    ): void {
        $photo = $this->resolvePhotoForGeneration($generation);

        if ($this->illustrationGeneration->shouldGenerateIllustrations()) {
            $this->bookGenerations->updateStatus($generation, 'processing');
            $this->illustrationGeneration->queueGeneration($generation);

            return;
        }

        if ($photo !== null) {
            $this->illustrationGeneration->finalizeWithoutProvider($generation, $photo);

            return;
        }

        $this->bookGenerations->updateStatus($generation, 'completed');
        $this->observability->logStage(
            $generationId,
            $correlationId,
            'generation',
            'Book generation completed',
            ['layout_duration_ms' => $layoutDurationMs],
        );
        $this->observability->notifyBookReady($generation);
    }

    private function resolvePhotoForGeneration(BookGeneration $generation): ?UploadedPhoto
    {
        if ($generation->uploaded_photo_id === null || $generation->user === null) {
            return null;
        }

        return $this->uploadedPhotos->findForUser(
            (int) $generation->user_id,
            (int) $generation->uploaded_photo_id,
        );
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

    private function resolveChildProfile(
        User $user,
        string $childName,
        int $age,
        string $childGender,
        ?UploadedPhoto $photo,
    ): ChildProfile {
        $profile = $this->childProfiles->findForUserByName($user->id, $childName);

        if ($profile === null) {
            $profile = $this->childProfiles->create([
                'user_id' => $user->id,
                'child_name' => $childName,
                'child_age' => $age,
                'child_gender' => $childGender,
            ]);
        } else {
            $this->childProfiles->updateDemographics($profile, $age, $childGender);
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
        string $childGender,
        string $goal,
        ?StoryPrompt $prompt,
        ChildProfile $profile,
        ?UploadedPhoto $photo,
        ?GeneratedCharacter $character,
        string $correlationId,
        ?string $idempotencyKey,
        string $fingerprint,
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
            'child_gender' => $childGender,
            'child_goal' => $goal,
            'prompt_snapshot' => $prompt?->prompt_text,
            'book_template_snapshot' => $this->templateSnapshot($template),
            'status' => 'processing',
            'illustration_status' => $this->illustrationGeneration->shouldGenerateIllustrations()
                ? 'queued'
                : null,
            'correlation_id' => $correlationId,
            'idempotency_key' => $idempotencyKey,
            'input_fingerprint' => $fingerprint,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function templateSnapshot(BookTemplate $template): array
    {
        $template->loadMissing('storyGoal');

        return [
            'id' => $template->id,
            'version' => $template->version,
            'title' => $template->title,
            'description' => $template->description,
            'is_free' => $template->is_free,
            'template_type' => $template->template_type,
        ];
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

    /**
     * @return array{pages: list<array<string, mixed>>, layouts: Collection<int, LayoutTemplate>}
     */
    private function assemblePages(string $storyText): array
    {
        $pageTexts = $this->resolvePageTexts($storyText);
        $layouts = $this->pickLayouts(count($pageTexts));

        return [
            'pages' => $this->buildPageRecords($pageTexts, $layouts),
            'layouts' => $layouts,
        ];
    }

    /**
     * @return list<string>
     */
    private function resolvePageTexts(string $storyText): array
    {
        $cachedPageTexts = $this->layoutCache->getPageTexts($storyText);

        if ($cachedPageTexts !== null && $cachedPageTexts !== []) {
            return $cachedPageTexts;
        }

        $pageTexts = $this->storyPaginator->paginate($storyText);
        $this->layoutCache->putPageTexts($storyText, $pageTexts);

        return $pageTexts;
    }

    /**
     * @param  list<string>  $pageTexts
     * @return list<array<string, mixed>>
     */
    private function buildPageRecords(array $pageTexts, Collection $layouts): array
    {
        $pages = [];

        foreach ($pageTexts as $index => $text) {
            $layout = $layouts[$index] ?? null;
            $pages[] = [
                'page_number' => $index + 1,
                'layout_template_id' => $layout?->id,
                'text' => $text,
            ];
        }

        return $pages;
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     */
    private function attachPlaceholderIllustrations(int $generationId, array $pages): array
    {
        foreach ($pages as $index => $page) {
            $pageNumber = (int) $page['page_number'];
            $pages[$index]['image_url'] = $this->illustrationStorage->storePlaceholder(
                $generationId,
                $pageNumber,
            );
        }

        return $pages;
    }

    /**
     * @return array{
     *     story: string,
     *     used_ai: bool,
     *     prompt_tokens: int|null,
     *     completion_tokens: int|null
     * }
     */
    private function resolveStoryText(
        int $generationId,
        string $correlationId,
        string $name,
        int $age,
        string $goal,
        ?StoryPrompt $prompt,
    ): array {
        $aiResult = $this->generateStoryWithAi($generationId, $correlationId, $name, $age, $goal, $prompt);

        if ($aiResult !== null && trim($aiResult->story) !== '') {
            return $this->storyTextFromAi($aiResult);
        }

        return $this->storyTextFromFallback($name, $age, $goal);
    }

    /**
     * @return array{
     *     story: string,
     *     used_ai: bool,
     *     prompt_tokens: int|null,
     *     completion_tokens: int|null
     * }
     */
    private function storyTextFromAi(StoryTextGenerationResult $result): array
    {
        return [
            'story' => $result->story,
            'used_ai' => true,
            'prompt_tokens' => $result->promptTokens,
            'completion_tokens' => $result->completionTokens,
        ];
    }

    /**
     * @return array{
     *     story: string,
     *     used_ai: bool,
     *     prompt_tokens: null,
     *     completion_tokens: null
     * }
     */
    private function storyTextFromFallback(string $name, int $age, string $goal): array
    {
        return [
            'story' => $this->buildFallbackStory($name, $age, $goal),
            'used_ai' => false,
            'prompt_tokens' => null,
            'completion_tokens' => null,
        ];
    }

    private function generateStoryWithAi(
        int $generationId,
        string $correlationId,
        string $name,
        int $age,
        string $goal,
        ?StoryPrompt $prompt,
    ): ?StoryTextGenerationResult {
        if (! $this->storyTextProvider->isConfigured()) {
            return null;
        }

        $input = new StoryTextGenerationInput(
            promptText: $this->resolvePromptText($prompt, $name, $age, $goal),
            childName: $name,
            childAge: $age,
            childGoal: $goal,
        );

        $result = $this->storyTextProvider->generateStory($input);

        if ($result === null) {
            $this->observability->logStage(
                $generationId,
                $correlationId,
                'text',
                'Story text generation returned no story; using fallback text',
                ['story_prompt_id' => $prompt?->id],
                'warning',
            );

            return null;
        }

        $this->observability->logStage(
            $generationId,
            $correlationId,
            'text',
            'Story text generation completed',
            ['story_prompt_id' => $prompt?->id],
        );

        return $result;
    }

    private function resolvePromptText(?StoryPrompt $prompt, string $name, int $age, string $goal): string
    {
        $template = $prompt->prompt_text ?? self::DEFAULT_PROMPT_TEXT;

        return strtr($template, [
            '{name}' => $name,
            '{age}' => (string) $age,
            '{goal}' => $goal,
        ]);
    }

    private function buildFallbackStory(string $name, int $age, string $goal): string
    {
        $sentenceCount = self::FALLBACK_SENTENCE_COUNT;
        $midpoint = (int) ceil($sentenceCount / 2);
        $sentences = [];

        for ($pageNumber = 1; $pageNumber <= $sentenceCount; $pageNumber++) {
            $sentences[] = $this->fallbackText($name, $age, $goal, $pageNumber, $sentenceCount, $midpoint);
        }

        return implode(' ', $sentences);
    }

    private function fallbackText(
        string $name,
        int $age,
        string $goal,
        int $pageNumber,
        int $totalPages,
        int $midpoint,
    ): string {
        return match (true) {
            $pageNumber === 1 => "{$name}, {$age}, начинает историю о цели: {$goal}.",
            $pageNumber === $midpoint => "Неожиданно сюжет меняется, но {$name} не сдается.",
            $pageNumber === $totalPages => "{$name} достигает цели {$goal} и радуется финалу.",
            default => "{$name} делает шаг к цели {$goal} и становится смелее.",
        };
    }

    private function pickLayouts(int $pagesCount): Collection
    {
        $layouts = $this->layoutTemplates->listRandomActive($pagesCount);

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
