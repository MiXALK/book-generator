<?php

namespace App\Repositories\Eloquent;

use App\Models\ChildProfile;
use App\Repositories\Contracts\ChildProfileRepositoryInterface;

class EloquentChildProfileRepository implements ChildProfileRepositoryInterface
{
    public function findForUserByName(int $userId, string $childName): ?ChildProfile
    {
        return ChildProfile::query()
            ->where('user_id', $userId)
            ->where('child_name', $childName)
            ->first();
    }

    public function findByIdForUser(int $userId, int $profileId): ?ChildProfile
    {
        return ChildProfile::query()
            ->where('user_id', $userId)
            ->whereKey($profileId)
            ->first();
    }

    public function create(array $attributes): ChildProfile
    {
        return ChildProfile::query()->create($attributes);
    }

    public function updateDemographics(ChildProfile $profile, int $childAge, string $childGender): void
    {
        $profile->update([
            'child_age' => $childAge,
            'child_gender' => $childGender,
        ]);
    }
}
