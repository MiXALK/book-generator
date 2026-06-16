<?php

namespace App\Services;

use App\Jobs\GenerateBookIllustrationsJob;
use App\Models\BookGeneration;
use App\Models\BookPage;
use App\Models\ChildProfile;
use App\Models\GeneratedCharacter;
use App\Models\LayoutTemplate;
use App\Models\UploadedPhoto;
use App\Repositories\Contracts\BookGenerationRepositoryInterface;
use App\Repositories\Contracts\BookPageRepositoryInterface;
use App\Repositories\Contracts\GeneratedCharacterRepositoryInterface;
use App\Repositories\Contracts\UploadedPhotoRepositoryInterface;
use App\Services\Ai\CharacterBibleComposer;
use App\Services\Ai\Contracts\IllustrationGenerationProviderInterface;
use App\Services\Ai\Data\IllustrationGenerationInput;
use App\Services\Ai\IllustrationPromptComposer;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class IllustrationGenerationService
{
    public function __construct(
        private BookGenerationRepositoryInterface       $bookGenerations,
        private BookPageRepositoryInterface             $bookPages,
        private UploadedPhotoRepositoryInterface        $uploadedPhotos,
        private GeneratedCharacterRepositoryInterface   $generatedCharacters,
        private BookIllustrationStorageService          $illustrationStorage,
        private IllustrationGenerationProviderInterface $illustrationProvider,
        private IllustrationPromptComposer              $promptComposer,
        private CharacterBibleComposer                  $characterBibleComposer,
    ) {}

    public function shouldGenerateIllustrations(?UploadedPhoto $photo): bool
    {
        return $photo !== null && $this->illustrationProvider->isConfigured();
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

        $this->bookGenerations->updateIllustrationStatus($generation, 'processing');
        $this->bookGenerations->updateStatus($generation, 'processing');

        $character = $generation->generated_character_id !== null
            ? $this->generatedCharacters->findById((int) $generation->generated_character_id)
            : null;

        if ($character === null) {
            $this->failGeneration($generation, 'Character profile is missing for illustration generation.');

            return;
        }

        try {
            foreach ($generation->bookPages as $page) {
                if (! $page instanceof BookPage) {
                    continue;
                }

                $layoutTemplate = $page->layoutTemplate;
                $category = $layoutTemplate instanceof LayoutTemplate
                    ? (string) $layoutTemplate->category
                    : 'content';
                $prompt = $this->promptComposer->composePagePrompt(
                    $character->style_bible,
                    $page->text,
                    $category,
                    $page->page_number,
                    $generation->child_name,
                );

                $input = new IllustrationGenerationInput(
                    prompt: $prompt,
                    childName: $generation->child_name,
                    childAge: $generation->child_age,
                    pageCategory: $category,
                    pageNumber: $page->page_number,
                );

                $binary = $this->illustrationProvider->generateIllustration($input);

                if ($binary === null) {
                    $this->failGeneration($generation, 'Illustration provider failed for page '.$page->page_number);

                    return;
                }

                $path = $this->illustrationStorage->storeGeneratedImage(
                    $generation->id,
                    $page->page_number,
                    $binary,
                );

                if ($path === null) {
                    $this->failGeneration($generation, 'Failed to store illustration for page '.$page->page_number);

                    return;
                }

                $this->bookPages->updateImageUrl($page->id, $path);
            }

            $this->consumeUploadedPhoto($generation);
            $this->bookGenerations->updateIllustrationStatus($generation, 'completed', null);
            $this->bookGenerations->updateStatus($generation, 'completed');
        } catch (Throwable $exception) {
            Log::warning('Illustration generation failed', [
                'generation_id' => $generation->id,
                'message' => $exception->getMessage(),
            ]);

            $this->failGeneration($generation, 'Illustration generation failed.');
        }
    }

    public function resolveOrCreateCharacter(
        ChildProfile $profile,
        string $childName,
        int $childAge,
        ?UploadedPhoto $photo,
    ): GeneratedCharacter {
        $existing = $this->generatedCharacters->findForChildProfile($profile);

        $styleBible = $this->characterBibleComposer->compose($childName, $childAge, $existing);

        if ($existing !== null) {
            if ($photo !== null) {
                $this->generatedCharacters->update($existing, [
                    'uploaded_photo_id' => $photo->id,
                    'style_bible' => $styleBible,
                ]);
            }

            return $existing->fresh() ?? $existing;
        }

        return $this->generatedCharacters->create([
            'child_profile_id' => $profile->id,
            'uploaded_photo_id' => $photo?->id,
            'style_bible' => $styleBible,
        ]);
    }

    public function finalizeWithoutProvider(BookGeneration $generation, ?UploadedPhoto $photo): void
    {
        $this->consumeUploadedPhoto($generation);
        $this->bookGenerations->updateIllustrationStatus($generation, 'completed', null);
        $this->bookGenerations->updateStatus($generation, 'completed');
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
}
