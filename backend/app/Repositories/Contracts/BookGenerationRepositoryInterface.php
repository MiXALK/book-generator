<?php

namespace App\Repositories\Contracts;

use App\Models\BookGeneration;
use Illuminate\Support\Collection;

interface BookGenerationRepositoryInterface
{
    public function countForUserInCurrentMonth(int $userId): int;

    public function create(array $attributes): BookGeneration;

    public function updateStatus(BookGeneration $generation, string $status): void;

    public function loadForApi(BookGeneration $generation): BookGeneration;

    public function listForUser(int $userId): Collection;

    public function findForUserById(int $userId, int $generationId): ?BookGeneration;
}
