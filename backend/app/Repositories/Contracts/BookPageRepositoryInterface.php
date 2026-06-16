<?php

namespace App\Repositories\Contracts;

use App\Models\BookGeneration;

interface BookPageRepositoryInterface
{
    public function createMany(BookGeneration $generation, array $pages): void;

    public function updateImageUrl(int $pageId, ?string $imagePath): void;
}
