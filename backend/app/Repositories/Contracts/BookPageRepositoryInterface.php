<?php

namespace App\Repositories\Contracts;

use App\Models\BookGeneration;
use App\Models\BookPage;
use Illuminate\Support\Collection;

interface BookPageRepositoryInterface
{
    public function createMany(BookGeneration $generation, array $pages): void;

    public function updateImageUrl(int $pageId, ?string $imagePath): void;

    /**
     * @return Collection<int, BookPage>
     */
    public function listMissingGeneratedImages(int $generationId): Collection;

    public function findForGenerationWithLayout(int $generationId, int $pageId): ?BookPage;

    public function countMissingGeneratedImages(int $generationId): int;
}
