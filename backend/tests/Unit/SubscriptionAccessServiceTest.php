<?php

namespace Tests\Unit;

use App\Models\BookTemplate;
use App\Models\User;
use App\Services\SubscriptionAccessService;
use Tests\TestCase;

class SubscriptionAccessServiceTest extends TestCase
{
    private SubscriptionAccessService $access;

    protected function setUp(): void
    {
        parent::setUp();

        $this->access = new SubscriptionAccessService;
    }

    public function test_free_user_has_three_book_monthly_limit(): void
    {
        $user = new User([
            'plan' => 'free',
            'subscription_status' => 'inactive',
        ]);

        $this->assertSame(3, $this->access->monthlyLimit($user));
    }

    public function test_active_paid_user_has_ten_book_monthly_limit(): void
    {
        $user = new User([
            'plan' => 'paid',
            'subscription_status' => 'active',
        ]);

        $this->assertSame(10, $this->access->monthlyLimit($user));
    }

    public function test_inactive_paid_user_is_treated_as_free_for_limits(): void
    {
        $user = new User([
            'plan' => 'paid',
            'subscription_status' => 'inactive',
        ]);

        $this->assertSame(3, $this->access->monthlyLimit($user));
        $this->assertFalse($this->access->hasActivePaidAccess($user));
    }

    public function test_free_user_can_access_free_template_only(): void
    {
        $user = new User([
            'plan' => 'free',
            'subscription_status' => 'inactive',
        ]);

        $freeTemplate = new BookTemplate(['is_free' => true]);
        $paidTemplate = new BookTemplate(['is_free' => false]);

        $this->assertTrue($this->access->canAccessTemplate($user, $freeTemplate));
        $this->assertFalse($this->access->canAccessTemplate($user, $paidTemplate));
    }

    public function test_active_paid_user_can_access_paid_template(): void
    {
        $user = new User([
            'plan' => 'paid',
            'subscription_status' => 'active',
        ]);

        $paidTemplate = new BookTemplate(['is_free' => false]);

        $this->assertTrue($this->access->canAccessTemplate($user, $paidTemplate));
    }
}
