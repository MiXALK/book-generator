<?php

namespace App\Repositories\Contracts;

use App\Models\BookGeneration;
use Illuminate\Support\Collection;

interface BookGenerationRepositoryInterface
{
    public function countForUserInCurrentMonth(int $userId): int;

    public function create(array $attributes): BookGeneration;

    public function updateStatus(BookGeneration $generation, string $status): void;

    public function updateIllustrationStatus(BookGeneration $generation, ?string $status, ?string $errorMessage = null): void;

    public function updatePersonalization(BookGeneration $generation, array $attributes): void;

    public function findForUserIllustrationRetry(int $userId, int $generationId): ?BookGeneration;

    public function findWithPagesForIllustration(int $generationId): ?BookGeneration;

    public function loadForApi(BookGeneration $generation): BookGeneration;

    public function listForUser(int $userId): Collection;

    public function findForUserById(int $userId, int $generationId): ?BookGeneration;

    public function delete(BookGeneration $generation): void;

    /**
     * @return list<string>
     */
    public function listImagePathsForGeneration(int $generationId): array;

    /**
     * @return Collection<int, BookGeneration>
     */
    public function listFailedOlderThan(\DateTimeInterface $cutoff): Collection;
}
