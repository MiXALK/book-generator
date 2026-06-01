<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface AgeRangeRepositoryInterface
{
    public function listForCatalog(): Collection;
}
