<?php

namespace App\Services;

use App\Models\UploadedPhoto;
use App\Models\User;
use App\Repositories\Contracts\ChildProfileRepositoryInterface;
use App\Repositories\Contracts\UploadedPhotoRepositoryInterface;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;

readonly class ChildPhotoUploadService
{
    public function __construct(
        private UploadedPhotoRepositoryInterface $uploadedPhotos,
        private ChildProfileRepositoryInterface $childProfiles,
        private BookIllustrationStorageService $illustrationStorage,
        private SubscriptionAccessService $subscriptionAccess,
    ) {}

    public function upload(User $user, UploadedFile $file, bool $parentalConsent, ?string $childName = null): UploadedPhoto
    {
        if (! $this->subscriptionAccess->canUploadPhoto($user)) {
            throw new HttpResponseException(response()->json([
                'message' => 'Photo upload is available only for active Premium subscribers.',
            ], 403));
        }

        if (! $parentalConsent) {
            throw new HttpResponseException(response()->json([
                'message' => 'Parental consent is required before uploading a child photo.',
            ], 422));
        }

        $this->validateFile($file);

        $dimensions = $this->readDimensions($file);
        $extension = $this->extensionForMime($file->getMimeType() ?? '');
        $storagePath = $this->illustrationStorage->storeUploadedPhoto(
            $user->id,
            $file->getContent(),
            $extension,
        );

        $childProfileId = null;

        if ($childName !== null && trim($childName) !== '') {
            $profile = $this->childProfiles->findForUserByName($user->id, trim($childName));

            if ($profile === null) {
                $profile = $this->childProfiles->create([
                    'user_id' => $user->id,
                    'child_name' => trim($childName),
                ]);
            }

            $childProfileId = $profile->id;
        }

        return $this->uploadedPhotos->create([
            'user_id' => $user->id,
            'child_profile_id' => $childProfileId,
            'storage_path' => $storagePath,
            'mime_type' => (string) $file->getMimeType(),
            'file_size_bytes' => $file->getSize(),
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'parental_consent_at' => now(),
            'status' => 'pending',
        ]);
    }

    private function validateFile(UploadedFile $file): void
    {
        $maxKb = (int) config('services.book_photo.max_kb', 5120);
        $allowedMimes = config('services.book_photo.allowed_mimes', []);
        $minWidth = (int) config('services.book_photo.min_width', 256);
        $minHeight = (int) config('services.book_photo.min_height', 256);
        $maxWidth = (int) config('services.book_photo.max_width', 4096);
        $maxHeight = (int) config('services.book_photo.max_height', 4096);

        if ($file->getSize() > $maxKb * 1024) {
            throw new HttpResponseException(response()->json([
                'message' => "Photo must be at most {$maxKb} KB.",
            ], 422));
        }

        $mime = (string) $file->getMimeType();

        if (! is_array($allowedMimes) || ! in_array($mime, $allowedMimes, true)) {
            throw new HttpResponseException(response()->json([
                'message' => 'Photo must be a JPEG, PNG, or WebP image.',
            ], 422));
        }

        $dimensions = $this->readDimensions($file);

        if ($dimensions['width'] < $minWidth || $dimensions['height'] < $minHeight) {
            throw new HttpResponseException(response()->json([
                'message' => "Photo must be at least {$minWidth}x{$minHeight} pixels.",
            ], 422));
        }

        if ($dimensions['width'] > $maxWidth || $dimensions['height'] > $maxHeight) {
            throw new HttpResponseException(response()->json([
                'message' => "Photo must be at most {$maxWidth}x{$maxHeight} pixels.",
            ], 422));
        }
    }

    /**
     * @return array{width: int, height: int}
     */
    private function readDimensions(UploadedFile $file): array
    {
        $size = @getimagesize($file->getPathname());

        if ($size === false) {
            throw new HttpResponseException(response()->json([
                'message' => 'Unable to read image dimensions.',
            ], 422));
        }

        return [
            'width' => (int) $size[0],
            'height' => (int) $size[1],
        ];
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };
    }
}
