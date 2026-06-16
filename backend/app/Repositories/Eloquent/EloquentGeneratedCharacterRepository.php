<?php

namespace App\Repositories\Eloquent;

use App\Models\ChildProfile;
use App\Models\GeneratedCharacter;
use App\Repositories\Contracts\GeneratedCharacterRepositoryInterface;

class EloquentGeneratedCharacterRepository implements GeneratedCharacterRepositoryInterface
{
    public function findForChildProfile(ChildProfile $profile): ?GeneratedCharacter
    {
        return GeneratedCharacter::query()
            ->where('child_profile_id', $profile->id)
            ->first();
    }

    public function findById(int $characterId): ?GeneratedCharacter
    {
        return GeneratedCharacter::query()->find($characterId);
    }

    public function create(array $attributes): GeneratedCharacter
    {
        return GeneratedCharacter::query()->create($attributes);
    }

    public function update(GeneratedCharacter $character, array $attributes): void
    {
        $character->update($attributes);
    }
}
