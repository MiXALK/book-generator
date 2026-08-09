<?php

namespace App\Services;

use App\Exceptions\TransientGenerationException;
use App\Jobs\GenerateBookIllustrationsJob;
use App\Jobs\GenerateBookPageIllustrationJob;
use App\Models\BookGeneration;
use App\Models\BookPage;
use App\Models\ChildProfile;
use App\Models\GeneratedCharacter;
use App\Models\UploadedPhoto;
use App\Repositories\Contracts\BookGenerationRepositoryInterface;
use App\Repositories\Contracts\BookPageRepositoryInterface;
use App\Repositories\Contracts\GeneratedCharacterRepositoryInterface;
use App\Repositories\Contracts\UploadedPhotoRepositoryInterface;
use App\Services\Ai\CharacterBibleComposer;
use App\Services\Ai\Contracts\CharacterAppearanceProviderInterface;
use App\Services\Ai\Contracts\IllustrationGenerationProviderInterface;
use App\Services\Ai\Data\IllustrationGenerationInput;
use App\Services\Ai\IllustrationPromptComposer;
use Throwable;

readonly class IllustrationGenerationService
{
    public function __construct(
        private BookGenerationRepositoryInterface $bookGenerations,
        private BookPageRepositoryInterface $bookPages,
        private UploadedPhotoRepositoryInterface $uploadedPhotos,
        private GeneratedCharacterRepositoryInterface $generatedCharacters,
        private BookIllustrationStorageService $illustrationStorage,
        private CharacterAppearanceProviderInterface $appearanceProvider,
        private IllustrationGenerationProviderInterface $illustrationProvider,
        private IllustrationPromptComposer $promptComposer,
        private CharacterBibleComposer $characterBibleComposer,
        private BookGenerationObservabilityService $observability,
        private BookGenerationCostService $costTracking,
        private AiOperationQuotaService $aiQuotas,
    ) {}

    public function shouldGenerateIllustrations(): bool
    {
        return $this->illustrationProvider->isConfigured();
    }

    public function queueGeneration(BookGeneration $generation): void
    {
        $this->bookGenerations->updateIllustrationStatus($generation, 'queued');
        GenerateBookIllustrationsJob::dispatch($generation->id);
    }

    public function retryGeneration(BookGeneration $generation): void
    {
        $this->bookGenerations->updateIllustrationStatus($generation, 'queued');
        $this->bookGenerations->updateStatus($generation, 'processing');
        GenerateBookIllustrationsJob::dispatch($generation->id);
    }

    public function runForGeneration(int $generationId): void
    {
        $generation = $this->bookGenerations->findWithPagesForIllustration($generationId);

        if ($generation === null) {
            return;
        }

        $correlationId = $generation->correlation_id ?? $this->observability->newCorrelationId();

        $this->observability->withContext($generationId, $correlationId, function () use ($generation, $generationId, $correlationId) {
            try {
                $this->bookGenerations->updateIllustrationStatus($generation, 'processing');
                $this->bookGenerations->updateStatus($generation, 'processing');

                $character = $this->characterForGeneration($generation);

                if ($character === null) {
                    $this->failGeneration($generation, 'Character profile is missing for illustration generation.');

                    return;
                }

                if (! $this->hasPhotoProcessingConsent($generation)) {
                    $this->failGeneration($generation, 'Parental consent is required before photo processing.');

                    return;
                }

                if (! $this->prepareCharacterAppearance($generation, $character)) {
                    return;
                }

                $this->observability->logStage(
                    $generationId,
                    $correlationId,
                    'image',
                    'Illustration generation started',
                );

                $missingPages = $this->bookPages->listMissingGeneratedImages($generationId);

                if ($missingPages->isEmpty()) {
                    $this->completeIfAllPagesReady($generation, $correlationId);

                    return;
                }

                foreach ($missingPages as $page) {
                    GenerateBookPageIllustrationJob::dispatch($generationId, $page->id);
                }
            } catch (TransientGenerationException $exception) {
                $this->observability->logStage(
                    $generationId,
                    $correlationId,
                    'image',
                    'Illustration generation attempt failed; queue will retry',
                    ['message' => $exception->getMessage()],
                    'warning',
                );

                throw $exception;
            } catch (Throwable $exception) {
                $this->observability->logStage(
                    $generationId,
                    $correlationId,
                    'image',
                    'Illustration generation attempt failed with unexpected error',
                    ['message' => $exception->getMessage()],
                    'error',
                );

                throw new TransientGenerationException($exception->getMessage(), 0, $exception);
            }
        });
    }

    public function runForPage(int $generationId, int $pageId): void
    {
        $generation = $this->bookGenerations->findWithUser($generationId);
        $page = $this->bookPages->findForGenerationWithLayout($generationId, $pageId);

        if ($generation === null || $page === null) {
            return;
        }

        $correlationId = $generation->correlation_id ?? $this->observability->newCorrelationId();

        $this->observability->withContext($generationId, $correlationId, function () use ($generation, $page, $generationId, $correlationId) {
            try {
                if (! $this->pageNeedsGeneratedImage($page)) {
                    $this->completeIfAllPagesReady($generation, $correlationId);

                    return;
                }

                $this->bookGenerations->updateIllustrationStatus($generation, 'processing');
                $this->bookGenerations->updateStatus($generation, 'processing');

                $character = $this->characterForGeneration($generation);

                if ($character === null) {
                    $this->failGeneration($generation, 'Character profile is missing for illustration generation.');

                    return;
                }

                if (! $this->hasPhotoProcessingConsent($generation)) {
                    $this->failGeneration($generation, 'Parental consent is required before photo processing.');

                    return;
                }

                $imageMeasured = $this->observability->measure(
                    fn () => $this->generatePageIllustration($generation, $page, $character),
                );

                if ($generation->user !== null && $imageMeasured['result'] === true) {
                    $this->aiQuotas->recordImageGenerations($generation->user, 1);
                }

                $this->observability->recordLatencyMetrics($generation, [
                    'image_duration_ms' => ((int) $generation->image_duration_ms) + $imageMeasured['duration_ms'],
                ]);
                $this->observability->logStage(
                    $generationId,
                    $correlationId,
                    'image',
                    'Page illustration generation completed',
                    [
                        'page_number' => $page->page_number,
                        'image_duration_ms' => $imageMeasured['duration_ms'],
                    ],
                );

                $this->completeIfAllPagesReady($generation, $correlationId);
            } catch (TransientGenerationException $exception) {
                $this->observability->logStage(
                    $generationId,
                    $correlationId,
                    'image',
                    'Page illustration generation attempt failed; queue will retry',
                    [
                        'page_number' => $page->page_number,
                        'message' => $exception->getMessage(),
                    ],
                    'warning',
                );

                throw $exception;
            } catch (Throwable $exception) {
                $this->observability->logStage(
                    $generationId,
                    $correlationId,
                    'image',
                    'Page illustration generation attempt failed with unexpected error',
                    [
                        'page_number' => $page->page_number,
                        'message' => $exception->getMessage(),
                    ],
                    'error',
                );

                throw new TransientGenerationException($exception->getMessage(), 0, $exception);
            }
        });
    }

    public function failAfterExhaustedRetries(int $generationId, ?Throwable $exception): void
    {
        $generation = $this->bookGenerations->findWithPagesForIllustration($generationId);

        if ($generation === null || in_array($generation->status, ['completed', 'failed'], true)) {
            return;
        }

        $message = $exception?->getMessage() ?: 'Illustration generation failed after retries.';
        $this->failGeneration($generation, $message);
    }

    public function failPageAfterExhaustedRetries(int $generationId, int $pageId, ?Throwable $exception): void
    {
        $generation = $this->bookGenerations->findWithUser($generationId);
        $page = $this->bookPages->findForGenerationWithLayout($generationId, $pageId);

        if ($generation === null || $page === null || $generation->status === 'completed') {
            return;
        }

        $message = $exception?->getMessage() ?: 'Illustration generation failed after retries.';
        $this->bookGenerations->updateIllustrationStatus(
            $generation,
            'failed',
            'Page '.$page->page_number.': '.$message,
        );
    }

    public function resolveOrCreateCharacter(
        ChildProfile $profile,
        string $childName,
        int $childAge,
        string $childGender,
        ?UploadedPhoto $photo,
    ): GeneratedCharacter {
        $existing = $this->generatedCharacters->findForChildProfile($profile);
        $reuseExisting = $photo !== null;

        $styleBible = $this->characterBibleComposer->compose(
            $childName,
            $childAge,
            $childGender,
            $existing,
            $reuseExisting,
        );

        if ($existing !== null) {
            if ($photo !== null) {
                $attributes = [
                    'uploaded_photo_id' => $photo->id,
                    'style_bible' => $styleBible,
                ];

                if ((int) $existing->uploaded_photo_id !== (int) $photo->id) {
                    $attributes['appearance_profile'] = null;
                }

                $this->generatedCharacters->update($existing, $attributes);
            } elseif (
                $existing->uploaded_photo_id === null
                && $existing->style_bible !== $styleBible
            ) {
                $this->generatedCharacters->update($existing, [
                    'style_bible' => $styleBible,
                ]);
            }

            return $existing->fresh() ?? $existing;
        }

        return $this->generatedCharacters->create([
            'child_profile_id' => $profile->id,
            'uploaded_photo_id' => $photo?->id,
            'style_bible' => $styleBible,
            'appearance_profile' => null,
        ]);
    }

    public function finalizeWithoutProvider(BookGeneration $generation, ?UploadedPhoto $photo): void
    {
        $this->consumeUploadedPhoto($generation);
        $this->bookGenerations->updateIllustrationStatus($generation, 'completed', null);
        $this->bookGenerations->updateStatus($generation, 'completed');
        $this->observability->notifyBookReady($generation);
    }

    private function generatePageIllustration(
        BookGeneration $generation,
        BookPage $page,
        GeneratedCharacter $character,
    ): bool {
        if (! $this->pageNeedsGeneratedImage($page)) {
            return false;
        }

        $prompt = $this->promptComposer->composePagePrompt(
            $character->style_bible,
            $page->text,
            $this->maxPromptLength(),
        );

        $input = new IllustrationGenerationInput(
            prompt: $prompt,
            pageNumber: $page->page_number,
        );

        $binary = $this->illustrationProvider->generateIllustration($input);

        if ($binary === null) {
            throw new TransientGenerationException(
                'Illustration provider failed for page '.$page->page_number,
            );
        }

        $path = $this->illustrationStorage->storeGeneratedImage(
            $generation->id,
            $page->page_number,
            $binary,
        );

        if ($path === null) {
            throw new TransientGenerationException(
                'Failed to store illustration for page '.$page->page_number,
            );
        }

        $this->bookPages->updateImageUrl($page->id, $path);
        $this->costTracking->recordStorageBytes($generation, strlen($binary));
        $this->costTracking->recordImageGenerations($generation, 1);

        return true;
    }

    private function completeIfAllPagesReady(BookGeneration $generation, string $correlationId): void
    {
        if ($this->bookPages->countMissingGeneratedImages($generation->id) > 0) {
            return;
        }

        $fresh = $this->bookGenerations->findWithUser($generation->id);

        if ($fresh === null || $fresh->status === 'completed') {
            return;
        }

        $this->consumeUploadedPhoto($fresh);
        $this->bookGenerations->updateIllustrationStatus($fresh, 'completed', null);
        $this->bookGenerations->updateStatus($fresh, 'completed');
        $this->observability->logStage(
            $fresh->id,
            $correlationId,
            'image',
            'Illustration generation completed',
        );
        $this->observability->notifyBookReady($fresh);
    }

    private function characterForGeneration(BookGeneration $generation): ?GeneratedCharacter
    {
        if ($generation->generated_character_id === null) {
            return null;
        }

        return $this->generatedCharacters->findById((int) $generation->generated_character_id);
    }

    private function prepareCharacterAppearance(
        BookGeneration $generation,
        GeneratedCharacter $character,
    ): bool {
        if ($generation->uploaded_photo_id === null || trim((string) $character->appearance_profile) !== '') {
            return true;
        }

        if (! $this->appearanceProvider->isConfigured()) {
            $this->failGeneration($generation, 'Character appearance provider is not configured.');

            return false;
        }

        $photo = $this->uploadedPhotos->findForUser(
            (int) $generation->user_id,
            (int) $generation->uploaded_photo_id,
        );
        $storedImage = $photo !== null
            ? $this->illustrationStorage->readPrivateImage($photo->storage_path)
            : null;

        if ($storedImage === null) {
            throw new TransientGenerationException('Uploaded character photo could not be read.');
        }

        $appearance = $this->appearanceProvider->describe(
            $storedImage['binary'],
            $storedImage['content_type'],
        );

        if ($appearance === null || trim($appearance) === '') {
            throw new TransientGenerationException('Character appearance analysis failed.');
        }

        $styleBible = $this->characterBibleComposer->compose(
            (string) $generation->child_name,
            (int) $generation->child_age,
            (string) $generation->child_gender,
            $character,
            false,
            $appearance,
        );
        $normalizedAppearance = trim((string) preg_replace('/\s+/u', ' ', $appearance));

        $this->generatedCharacters->update($character, [
            'appearance_profile' => $normalizedAppearance,
            'style_bible' => $styleBible,
        ]);

        return true;
    }

    private function pageNeedsGeneratedImage(BookPage $page): bool
    {
        $path = $page->getAttributes()['image_url'] ?? null;

        return ! is_string($path) || $path === '' || str_ends_with($path, '.svg');
    }

    private function consumeUploadedPhoto(BookGeneration $generation): void
    {
        if ($generation->uploaded_photo_id === null) {
            return;
        }

        $photo = $this->uploadedPhotos->findForUser(
            (int) $generation->user_id,
            (int) $generation->uploaded_photo_id,
        );

        if ($photo === null) {
            return;
        }

        $this->illustrationStorage->deleteUploadedPhoto($photo->storage_path);
        $this->uploadedPhotos->markDeleted($photo);
    }

    private function failGeneration(BookGeneration $generation, string $message): void
    {
        $this->bookGenerations->updateIllustrationStatus($generation, 'failed', $message);
        $this->bookGenerations->updateStatus($generation, 'failed');
    }

    private function maxPromptLength(): int
    {
        $driver = (string) config('services.ai_image.driver', 'yandexart');
        $configured = config('services.ai_image.drivers.'.$driver.'.max_prompt_length');

        if (! is_numeric($configured)) {
            return 500;
        }

        return min(500, max(1, (int) $configured));
    }

    private function hasPhotoProcessingConsent(BookGeneration $generation): bool
    {
        if ($generation->uploaded_photo_id === null) {
            return true;
        }

        $photo = $this->uploadedPhotos->findForUser(
            (int) $generation->user_id,
            (int) $generation->uploaded_photo_id,
        );

        return $photo !== null;
    }
}
