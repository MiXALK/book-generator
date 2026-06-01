<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface StoryGoalRepositoryInterface
{
    public function listForCatalog(): Collection;
}
