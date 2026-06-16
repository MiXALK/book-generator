<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadChildPhotoRequest;
use App\Services\ChildPhotoUploadService;
use Illuminate\Http\JsonResponse;

class ChildPhotoController extends Controller
{
    public function __construct(
        private readonly ChildPhotoUploadService $photoUploadService,
    ) {}

    public function upload(UploadChildPhotoRequest $request): JsonResponse
    {
        $file = $request->file('photo');

        if ($file === null) {
            return response()->json([
                'message' => 'Photo file is required.',
            ], 422);
        }

        $photo = $this->photoUploadService->upload(
            $request->user(),
            $file,
            $request->parentalConsent(),
            $request->childName(),
        );

        return response()->json([
            'message' => 'Photo uploaded successfully.',
            'uploaded_photo' => [
                'id' => $photo->id,
                'width' => $photo->width,
                'height' => $photo->height,
            ],
        ], 201);
    }
}
