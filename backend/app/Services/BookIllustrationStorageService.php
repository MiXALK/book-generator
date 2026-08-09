<?php

namespace App\Services;

use App\Models\BookGeneration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

readonly class BookIllustrationStorageService
{
    public function storeUploadedPhoto(int $userId, string $binary, string $extension): string
    {
        $filename = Str::uuid()->toString().'.'.$extension;
        $path = "private/users/{$userId}/photos/{$filename}";

        Storage::disk('s3')->put($path, $binary, [
            'ContentType' => $this->mimeForExtension($extension),
            'visibility' => 'private',
        ]);

        return $path;
    }

    public function deleteUploadedPhoto(string $path): void
    {
        $this->deleteObject($path);
    }

    public function deleteObject(string $path): void
    {
        try {
            Storage::disk('s3')->delete($path);
        } catch (Throwable $exception) {
            Log::warning('Failed to delete storage object', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function deleteGenerationPrefix(int $generationId): void
    {
        try {
            Storage::disk('s3')->deleteDirectory("books/{$generationId}");
        } catch (Throwable $exception) {
            Log::warning('Failed to delete generation storage prefix', [
                'generation_id' => $generationId,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function deleteUserPrivatePrefix(int $userId): void
    {
        try {
            Storage::disk('s3')->deleteDirectory("private/users/{$userId}");
        } catch (Throwable $exception) {
            Log::warning('Failed to delete user private storage prefix', [
                'user_id' => $userId,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function storeGeneratedImage(int $generationId, int $pageNumber, string $binary): ?string
    {
        $extension = $this->extensionForBinary($binary);
        $path = "books/{$generationId}/page-{$pageNumber}.{$extension}";

        try {
            Storage::disk('s3')->put($path, $binary, [
                'ContentType' => $this->resolveContentType($binary, $path),
                'visibility' => 'private',
            ]);

            return $path;
        } catch (Throwable $exception) {
            Log::warning('Failed to store generated illustration', [
                'generation_id' => $generationId,
                'page_number' => $pageNumber,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function storePlaceholder(int $generationId, int $pageNumber): ?string
    {
        $path = "books/{$generationId}/page-{$pageNumber}.svg";

        try {
            Storage::disk('s3')->put(
                $path,
                $this->svgForPage($pageNumber),
                [
                    'ContentType' => 'image/svg+xml',
                    'visibility' => 'private',
                ],
            );

            return $path;
        } catch (Throwable $exception) {
            Log::warning('Failed to store book page illustration', [
                'generation_id' => $generationId,
                'page_number' => $pageNumber,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function resolveUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (preg_match('#^books/(\d+)/page-(\d+)\.[a-z0-9]+$#', $path, $matches) === 1) {
            $ttlMinutes = (int) config('services.privacy.signed_url_ttl_minutes', 60);

            return URL::temporarySignedRoute('books.page-image', now()->addMinutes($ttlMinutes), [
                'id' => (int) $matches[1],
                'page' => (int) $matches[2],
            ]);
        }

        return null;
    }

    /**
     * @return array{binary: string, content_type: string}|null
     */
    public function readForResponse(string $path): ?array
    {
        try {
            $disk = Storage::disk('s3');

            if (! $disk->exists($path)) {
                return null;
            }

            $binary = $disk->get($path);

            return [
                'binary' => $binary,
                'content_type' => $this->resolveContentType($binary, $path),
            ];
        } catch (Throwable $exception) {
            Log::warning('Failed to read illustration from storage', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array{binary: string, content_type: string}|null
     */
    public function readPrivateImage(string $path): ?array
    {
        try {
            $disk = Storage::disk('s3');

            if (! $disk->exists($path)) {
                return null;
            }

            $binary = $disk->get($path);

            return [
                'binary' => $binary,
                'content_type' => $this->resolveContentType($binary, $path),
            ];
        } catch (Throwable $exception) {
            Log::warning('Failed to read private image from storage', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function signedUrlTtlSeconds(): int
    {
        return (int) config('services.privacy.signed_url_ttl_minutes', 60) * 60;
    }

    public function resolveGenerationImageUrls(BookGeneration $generation): void
    {
        foreach ($generation->bookPages as $page) {
            $storedPath = $page->getAttributes()['image_url'] ?? null;

            if (! is_string($storedPath) || $storedPath === '') {
                continue;
            }

            $page->setAttribute('image_url', $this->resolveUrl($storedPath));
        }
    }

    private function svgForPage(int $pageNumber): string
    {
        $primary = '#2563eb';
        $secondary = '#60a5fa';
        $accent = '#93c5fd';

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 640" role="img" aria-label="Page {$pageNumber} illustration">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$primary}"/>
      <stop offset="55%" stop-color="{$secondary}"/>
      <stop offset="100%" stop-color="{$accent}"/>
    </linearGradient>
  </defs>
  <rect width="800" height="640" fill="url(#bg)"/>
  <circle cx="180" cy="160" r="72" fill="rgba(255,255,255,0.22)"/>
  <circle cx="620" cy="420" r="110" fill="rgba(255,255,255,0.16)"/>
  <rect x="120" y="360" width="220" height="140" rx="28" fill="rgba(255,255,255,0.18)"/>
  <text x="400" y="330" text-anchor="middle" font-family="Arial, sans-serif" font-size="42" fill="rgba(255,255,255,0.92)">Page {$pageNumber}</text>
</svg>
SVG;
    }

    private function mimeForExtension(string $extension): string
    {
        return match ($extension) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    private function extensionForBinary(string $binary): string
    {
        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'jpg';
        }

        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'png';
        }

        return 'jpg';
    }

    private function resolveContentType(string $binary, string $path): string
    {
        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }

        if (str_ends_with($path, '.svg')) {
            return 'image/svg+xml';
        }

        return 'application/octet-stream';
    }
}
