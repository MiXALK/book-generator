<?php

namespace App\Repositories\Eloquent;

use App\Models\BookGeneration;
use App\Models\BookPage;
use App\Repositories\Contracts\BookPageRepositoryInterface;

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
}
