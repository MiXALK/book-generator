<?php

namespace App\Repositories\Contracts;

use App\Models\UploadedPhoto;

interface UploadedPhotoRepositoryInterface
{
    public function create(array $attributes): UploadedPhoto;

    public function findPendingForUser(int $userId, int $photoId): ?UploadedPhoto;

    public function findForUser(int $userId, int $photoId): ?UploadedPhoto;

    public function attachChildProfile(UploadedPhoto $photo, int $childProfileId): void;

    public function markConsumed(UploadedPhoto $photo): void;

    public function markDeleted(UploadedPhoto $photo): void;

    /**
     * @return Collection<int, UploadedPhoto>
     */
    public function listPendingOlderThan(\DateTimeInterface $cutoff): Collection;

    /**
     * @return Collection<int, UploadedPhoto>
     */
    public function listForUser(int $userId): Collection;
}
