<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function findByApiToken(string $token): ?User;

    public function findByGoogleIdOrEmail(string $googleId, string $email): ?User;

    public function createFromGoogle(array $attributes): User;

    public function updateGoogleProfile(User $user, array $attributes): User;

    public function updateApiToken(User $user, string $token, \DateTimeInterface $expiresAt): User;

    public function clearApiToken(User $user): void;

    public function updateLanguage(User $user, string $language): User;
}
