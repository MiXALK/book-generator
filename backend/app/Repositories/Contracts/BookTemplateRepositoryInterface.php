<?php

namespace App\Repositories\Contracts;

use App\Models\BookTemplate;
use Illuminate\Support\Collection;

interface BookTemplateRepositoryInterface
{
    public function listActiveForCatalog(): Collection;

    public function findActiveById(int $id): BookTemplate;

    public function findActiveByStoryGoalName(string $goalName): BookTemplate;
}
