<?php

namespace App\Repositories\Contracts;

use App\Models\ChildProfile;

interface ChildProfileRepositoryInterface
{
    public function findForUserByName(int $userId, string $childName): ?ChildProfile;

    public function findByIdForUser(int $userId, int $profileId): ?ChildProfile;

    public function create(array $attributes): ChildProfile;

    public function updateDemographics(ChildProfile $profile, int $childAge, string $childGender): void;
}
