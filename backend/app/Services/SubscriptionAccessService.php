<?php

namespace App\Services;

use App\Models\BookTemplate;
use App\Models\User;

readonly class SubscriptionAccessService
{
    public const int FREE_MONTHLY_LIMIT = 3;

    public const int PAID_MONTHLY_LIMIT = 10;

    public function hasActivePaidAccess(User $user): bool
    {
        return $user->plan === 'paid' && $user->subscription_status === 'active';
    }

    public function monthlyLimit(User $user): int
    {
        if ($this->hasActivePaidAccess($user)) {
            return self::PAID_MONTHLY_LIMIT;
        }

        return self::FREE_MONTHLY_LIMIT;
    }

    public function canAccessTemplate(User $user, BookTemplate $template): bool
    {
        if ($template->is_free) {
            return true;
        }

        return $this->hasActivePaidAccess($user);
    }

    public function canUploadPhoto(User $user): bool
    {
        return $this->hasActivePaidAccess($user);
    }
}
