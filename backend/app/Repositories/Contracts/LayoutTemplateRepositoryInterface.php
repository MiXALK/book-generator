<?php

namespace App\Repositories\Contracts;

use App\Models\LayoutTemplate;
use Illuminate\Support\Collection;

interface LayoutTemplateRepositoryInterface
{
    public function findById(int $id): ?LayoutTemplate;

    public function listRandomActive(int $limit): Collection;

    public function findRandomActive(): ?LayoutTemplate;
}
