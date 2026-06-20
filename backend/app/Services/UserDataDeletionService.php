<?php

namespace App\Services;

use App\Models\BookGeneration;
use App\Models\UploadedPhoto;
use App\Models\User;
use App\Repositories\Contracts\BookGenerationRepositoryInterface;
use App\Repositories\Contracts\UploadedPhotoRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;

readonly class UserDataDeletionService
{
    public function __construct(
        private BookGenerationRepositoryInterface $bookGenerations,
        private UploadedPhotoRepositoryInterface $uploadedPhotos,
        private UserRepositoryInterface $users,
        private BookIllustrationStorageService $illustrationStorage,
        private StripeBillingService $stripeBilling,
    ) {}

    public function deleteBookForUser(User $user, int $generationId): bool
    {
        $generation = $this->bookGenerations->findForUserById($user->id, $generationId);

        if ($generation === null) {
            return false;
        }

        $this->purgeGenerationStorage($generation);
        $this->bookGenerations->delete($generation);

        return true;
    }

    public function deleteAccount(User $user): void
    {
        $this->stripeBilling->cancelSubscriptionIfActive($user);
        $this->purgeAllUserStorage($user);
        $this->users->delete($user);
    }

    /**
     * @return int Number of pending photos purged
     */
    public function purgeExpiredPendingPhotos(): int
    {
        $hours = (int) config('services.privacy.pending_photo_retention_hours', 24);
        $cutoff = now()->subHours($hours);
        $photos = $this->uploadedPhotos->listPendingOlderThan($cutoff);

        return $this->purgeUploadedPhotos($photos);
    }

    /**
     * @return int Number of failed generations purged
     */
    public function purgeExpiredFailedGenerations(): int
    {
        $days = (int) config('services.privacy.failed_generation_retention_days', 7);
        $cutoff = now()->subDays($days);
        $generations = $this->bookGenerations->listFailedOlderThan($cutoff);
        $purged = 0;

        foreach ($generations as $generation) {
            $this->purgeGenerationStorage($generation);
            $this->bookGenerations->delete($generation);
            $purged++;
        }

        return $purged;
    }

    private function purgeAllUserStorage(User $user): void
    {
        $photos = $this->uploadedPhotos->listForUser($user->id);
        $this->purgeUploadedPhotos($photos);

        $generations = $this->bookGenerations->listForUser($user->id);

        foreach ($generations as $generation) {
            $this->purgeGenerationStorage($generation);
        }

        $this->illustrationStorage->deleteUserPrivatePrefix($user->id);
    }

    private function purgeGenerationStorage(BookGeneration $generation): void
    {
        $paths = $this->bookGenerations->listImagePathsForGeneration($generation->id);

        foreach ($paths as $path) {
            $this->illustrationStorage->deleteObject($path);
        }

        $this->illustrationStorage->deleteGenerationPrefix($generation->id);

        if ($generation->uploaded_photo_id !== null) {
            $photo = $this->uploadedPhotos->findForUser(
                (int) $generation->user_id,
                (int) $generation->uploaded_photo_id,
            );

            if ($photo !== null && $photo->status !== 'deleted') {
                $this->illustrationStorage->deleteUploadedPhoto($photo->storage_path);
                $this->uploadedPhotos->markDeleted($photo);
            }
        }
    }

    /**
     * @param  Collection<int, UploadedPhoto>  $photos
     */
    private function purgeUploadedPhotos(Collection $photos): int
    {
        $purged = 0;

        foreach ($photos as $photo) {
            if ($photo->status === 'deleted') {
                continue;
            }

            $this->illustrationStorage->deleteUploadedPhoto($photo->storage_path);
            $this->uploadedPhotos->markDeleted($photo);
            $purged++;
        }

        return $purged;
    }
}
