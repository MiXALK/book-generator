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
                    ->with('layoutTemplate:id,key,category,ratio_profile,text_position'),
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
}
