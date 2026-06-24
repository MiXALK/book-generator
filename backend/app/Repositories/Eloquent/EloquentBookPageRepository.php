<?php

namespace App\Repositories\Eloquent;

use App\Models\BookGeneration;
use App\Models\BookPage;
use App\Repositories\Contracts\BookPageRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentBookPageRepository implements BookPageRepositoryInterface
{
    public function createMany(BookGeneration $generation, array $pages): void
    {
        foreach ($pages as $page) {
            BookPage::query()->create([
                'book_generation_id' => $generation->id,
                'layout_template_id' => $page['layout_template_id'],
                'page_number' => $page['page_number'],
                'text' => $page['text'],
                'image_url' => $page['image_url'] ?? null,
            ]);
        }
    }

    public function updateImageUrl(int $pageId, ?string $imagePath): void
    {
        BookPage::query()
            ->whereKey($pageId)
            ->update(['image_url' => $imagePath]);
    }

    public function listMissingGeneratedImages(int $generationId): Collection
    {
        return BookPage::query()
            ->where('book_generation_id', $generationId)
            ->where(fn (Builder $query) => $this->missingGeneratedImageQuery($query))
            ->orderBy('page_number')
            ->with('layoutTemplate')
            ->get();
    }

    public function findForGenerationWithLayout(int $generationId, int $pageId): ?BookPage
    {
        return BookPage::query()
            ->where('book_generation_id', $generationId)
            ->whereKey($pageId)
            ->with('layoutTemplate')
            ->first();
    }

    public function countMissingGeneratedImages(int $generationId): int
    {
        return BookPage::query()
            ->where('book_generation_id', $generationId)
            ->where(fn (Builder $query) => $this->missingGeneratedImageQuery($query))
            ->count();
    }

    private function missingGeneratedImageQuery(Builder $query): void
    {
        $query->whereNull('image_url')
            ->orWhere('image_url', 'like', '%.svg');
    }
}
