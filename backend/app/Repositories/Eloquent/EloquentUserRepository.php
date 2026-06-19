<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::query()->find($id);
    }

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

    public function findByStripeCustomerId(string $stripeCustomerId): ?User
    {
        return User::query()
            ->where('stripe_customer_id', $stripeCustomerId)
            ->first();
    }

    public function findByStripeSubscriptionId(string $stripeSubscriptionId): ?User
    {
        return User::query()
            ->where('stripe_subscription_id', $stripeSubscriptionId)
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

    public function updateStripeCustomerId(User $user, string $stripeCustomerId): User
    {
        $user->update([
            'stripe_customer_id' => $stripeCustomerId,
        ]);

        return $user;
    }

    public function updateSubscription(
        User $user,
        string $plan,
        string $subscriptionStatus,
        ?string $stripeSubscriptionId,
    ): User {
        $user->update([
            'plan' => $plan,
            'subscription_status' => $subscriptionStatus,
            'stripe_subscription_id' => $stripeSubscriptionId,
        ]);

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
