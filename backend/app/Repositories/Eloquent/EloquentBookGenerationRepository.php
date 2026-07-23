<?php

namespace App\Repositories\Eloquent;

use App\Models\BookGeneration;
use App\Repositories\Contracts\BookGenerationRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentBookGenerationRepository implements BookGenerationRepositoryInterface
{
    public function countForUserInCurrentMonth(int $userId): int
    {
        return BookGeneration::query()
            ->where('user_id', $userId)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    public function create(array $attributes): BookGeneration
    {
        return BookGeneration::query()->create($attributes);
    }

    public function updateStatus(BookGeneration $generation, string $status): void
    {
        $generation->update(['status' => $status]);
    }

    public function updateIllustrationStatus(BookGeneration $generation, ?string $status, ?string $errorMessage = null): void
    {
        $generation->update([
            'illustration_status' => $status,
            'error_message' => $errorMessage,
        ]);
    }

    public function updateLatencyMetrics(BookGeneration $generation, array $metrics): void
    {
        $generation->update($metrics);
    }

    public function findWithUser(int $generationId): ?BookGeneration
    {
        return BookGeneration::query()
            ->with('user')
            ->find($generationId);
    }

    public function updatePersonalization(BookGeneration $generation, array $attributes): void
    {
        $generation->update($attributes);
    }

    public function findForUserIllustrationRetry(int $userId, int $generationId): ?BookGeneration
    {
        return BookGeneration::query()
            ->where('user_id', $userId)
            ->whereKey($generationId)
            ->where('illustration_status', 'failed')
            ->with(['bookPages' => fn ($query) => $query->orderBy('page_number')->with('layoutTemplate')])
            ->first();
    }

    public function findWithPagesForIllustration(int $generationId): ?BookGeneration
    {
        return BookGeneration::query()
            ->with(['bookPages' => fn ($query) => $query->orderBy('page_number')->with('layoutTemplate')])
            ->find($generationId);
    }

    public function loadForApi(BookGeneration $generation): BookGeneration
    {
        return $generation->load([
            'bookTemplate',
            'storyPrompt',
            'bookPages' => fn ($query) => $query
                ->orderBy('page_number')
                ->with('layoutTemplate'),
        ]);
    }

    public function listForUser(int $userId): Collection
    {
        return BookGeneration::query()
            ->where('user_id', $userId)
            ->with([
                'bookTemplate:id,title',
                'bookPages' => fn ($query) => $query
                    ->select(['id', 'book_generation_id', 'page_number', 'text', 'image_url', 'layout_template_id'])
                    ->orderBy('page_number')
                    ->with('layoutTemplate:id,key,text_position'),
            ])
            ->latest()
            ->get();
    }

    public function findForUserById(int $userId, int $generationId): ?BookGeneration
    {
        return BookGeneration::query()
            ->where('user_id', $userId)
            ->whereKey($generationId)
            ->first();
    }

    public function findByUserAndIdempotencyKey(int $userId, string $idempotencyKey): ?BookGeneration
    {
        return BookGeneration::query()
            ->where('user_id', $userId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    public function updateCostMetrics(BookGeneration $generation, array $metrics): void
    {
        $generation->update($metrics);
    }

    public function updateStoryText(BookGeneration $generation, string $storyText): void
    {
        $generation->update(['story_text' => $storyText]);
    }

    public function generationHasPages(int $generationId): bool
    {
        return BookGeneration::query()
            ->whereKey($generationId)
            ->whereHas('bookPages')
            ->exists();
    }

    public function delete(BookGeneration $generation): void
    {
        $generation->delete();
    }

    public function listImagePathsForGeneration(int $generationId): array
    {
        $generation = BookGeneration::query()
            ->whereKey($generationId)
            ->with(['bookPages' => fn ($query) => $query->select(['id', 'book_generation_id', 'image_url'])])
            ->first();

        if ($generation === null) {
            return [];
        }

        return $generation->bookPages
            ->map(fn ($page) => $page->getAttributes()['image_url'] ?? null)
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->values()
            ->all();
    }

    public function listFailedOlderThan(\DateTimeInterface $cutoff): Collection
    {
        return BookGeneration::query()
            ->where('status', 'failed')
            ->where('created_at', '<', $cutoff)
            ->get();
    }
}
