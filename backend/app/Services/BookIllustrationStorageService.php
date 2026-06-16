<?php

namespace App\Services;

use App\Models\BookGeneration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
        try {
            Storage::disk('s3')->delete($path);
        } catch (Throwable $exception) {
            Log::warning('Failed to delete uploaded child photo', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function storeGeneratedImage(int $generationId, int $pageNumber, string $binary): ?string
    {
        $path = "books/{$generationId}/page-{$pageNumber}.png";

        try {
            Storage::disk('s3')->put($path, $binary, [
                'ContentType' => 'image/png',
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

    public function storePlaceholder(int $generationId, int $pageNumber, string $category): ?string
    {
        $path = "books/{$generationId}/page-{$pageNumber}.svg";

        try {
            Storage::disk('s3')->put(
                $path,
                $this->svgForCategory($category, $pageNumber),
                ['ContentType' => 'image/svg+xml'],
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

        try {
            return Storage::disk('s3')->temporaryUrl($path, now()->addDay());
        } catch (Throwable $exception) {
            try {
                return Storage::disk('s3')->url($path);
            } catch (Throwable $fallbackException) {
                Log::warning('Failed to resolve illustration URL', [
                    'path' => $path,
                    'message' => $fallbackException->getMessage(),
                ]);

                return null;
            }
        }
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

    private function svgForCategory(string $category, int $pageNumber): string
    {
        $palette = match ($category) {
            'cover' => ['#7c3aed', '#a78bfa', '#c4b5fd'],
            'ending' => ['#059669', '#34d399', '#6ee7b7'],
            default => ['#2563eb', '#60a5fa', '#93c5fd'],
        };

        [$primary, $secondary, $accent] = $palette;
        $label = htmlspecialchars(strtoupper($category), ENT_QUOTES, 'UTF-8');

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 640" role="img" aria-label="{$label} illustration">
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
}
