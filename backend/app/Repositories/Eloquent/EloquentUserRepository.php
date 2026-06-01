<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findByApiToken(string $token): ?User
    {
        return User::query()->where('api_token', $token)->first();
    }

    public function findByGoogleIdOrEmail(string $googleId, string $email): ?User
    {
        return User::query()
            ->where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();
    }

    public function createFromGoogle(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function updateGoogleProfile(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user;
    }

    public function updateApiToken(User $user, string $token, \DateTimeInterface $expiresAt): User
    {
        $user->update([
            'api_token' => $token,
            'api_token_expires_at' => $expiresAt,
        ]);

        return $user;
    }

    public function clearApiToken(User $user): void
    {
        $user->update([
            'api_token' => null,
            'api_token_expires_at' => null,
        ]);
    }

    public function updateLanguage(User $user, string $language): User
    {
        $user->update(['language' => $language]);

        return $user;
    }
}
