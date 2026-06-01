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
        return $generation->load('bookPages.layoutTemplate', 'bookTemplate', 'storyPrompt');
    }

    public function listForUser(int $userId): Collection
    {
        return BookGeneration::query()
            ->where('user_id', $userId)
            ->with(['bookTemplate:id,title', 'bookPages:id,book_generation_id,page_number,text,image_url'])
            ->latest()
            ->get();
    }
}
