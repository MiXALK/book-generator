<?php

namespace App\Repositories\Eloquent;

use App\Models\UploadedPhoto;
use App\Repositories\Contracts\UploadedPhotoRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentUploadedPhotoRepository implements UploadedPhotoRepositoryInterface
{
    public function create(array $attributes): UploadedPhoto
    {
        return UploadedPhoto::query()->create($attributes);
    }

    public function findPendingForUser(int $userId, int $photoId): ?UploadedPhoto
    {
        return UploadedPhoto::query()
            ->whereKey($photoId)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->first();
    }

    public function findForUser(int $userId, int $photoId): ?UploadedPhoto
    {
        return UploadedPhoto::query()
            ->whereKey($photoId)
            ->where('user_id', $userId)
            ->first();
    }

    public function attachChildProfile(UploadedPhoto $photo, int $childProfileId): void
    {
        $photo->update(['child_profile_id' => $childProfileId]);
    }

    public function markConsumed(UploadedPhoto $photo): void
    {
        $photo->update(['status' => 'consumed']);
    }

    public function markDeleted(UploadedPhoto $photo): void
    {
        $photo->update(['status' => 'deleted']);
    }

    public function listPendingOlderThan(\DateTimeInterface $cutoff): Collection
    {
        return UploadedPhoto::query()
            ->where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->get();
    }

    public function listForUser(int $userId): Collection
    {
        return UploadedPhoto::query()
            ->where('user_id', $userId)
            ->get();
    }
}
