<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByApiToken(string $token): ?User;

    public function findByGoogleIdOrEmail(string $googleId, string $email): ?User;

    public function findByStripeCustomerId(string $stripeCustomerId): ?User;

    public function findByStripeSubscriptionId(string $stripeSubscriptionId): ?User;

    public function createFromGoogle(array $attributes): User;

    public function updateGoogleProfile(User $user, array $attributes): User;

    public function updateApiToken(User $user, string $token, \DateTimeInterface $expiresAt): User;

    public function clearApiToken(User $user): void;

    public function updateLanguage(User $user, string $language): User;

    public function updateStripeCustomerId(User $user, string $stripeCustomerId): User;

    public function updateSubscription(
        User $user,
        string $plan,
        string $subscriptionStatus,
        ?string $stripeSubscriptionId,
    ): User;

    public function delete(User $user): void;
}
