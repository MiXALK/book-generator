<?php

namespace App\Repositories\Contracts;

use App\Models\ChildProfile;
use App\Models\GeneratedCharacter;

interface GeneratedCharacterRepositoryInterface
{
    public function findForChildProfile(ChildProfile $profile): ?GeneratedCharacter;

    public function findById(int $characterId): ?GeneratedCharacter;

    public function create(array $attributes): GeneratedCharacter;

    public function update(GeneratedCharacter $character, array $attributes): void;
}
