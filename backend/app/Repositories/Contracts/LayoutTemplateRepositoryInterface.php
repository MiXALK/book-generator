<?php

namespace App\Repositories\Contracts;

use App\Models\LayoutTemplate;
use Illuminate\Support\Collection;

interface LayoutTemplateRepositoryInterface
{
    public function findRandomActiveByCategory(string $category): ?LayoutTemplate;

    public function listRandomActiveByCategory(string $category, int $limit): Collection;

    public function findRandomActive(): ?LayoutTemplate;
}
