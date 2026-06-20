<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Subscription;
use Stripe\Webhook;
use Throwable;
use UnexpectedValueException;

readonly class StripeBillingService
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    public function isConfigured(): bool
    {
        $secret = (string) config('services.stripe.secret');
        $priceId = (string) config('services.stripe.price_id');

        return $secret !== '' && $priceId !== '';
    }

    public function createCheckoutSession(User $user): string
    {
        $this->bootstrapStripe();

        $customerId = $this->resolveCustomerId($user);

        $session = Session::create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'client_reference_id' => (string) $user->id,
            'line_items' => [
                [
                    'price' => config('services.stripe.price_id'),
                    'quantity' => 1,
                ],
            ],
            'success_url' => config('services.stripe.success_url'),
            'cancel_url' => config('services.stripe.cancel_url'),
        ]);

        return $session->url;
    }

    public function createPortalSession(User $user): string
    {
        $this->bootstrapStripe();

        if ($user->stripe_customer_id === null || $user->stripe_customer_id === '') {
            throw new UnexpectedValueException('Stripe customer is not configured for this user.');
        }

        $session = \Stripe\BillingPortal\Session::create([
            'customer' => $user->stripe_customer_id,
            'return_url' => config('services.stripe.portal_return_url'),
        ]);

        return $session->url;
    }

    public function cancelSubscriptionIfActive(User $user): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $subscriptionId = $user->stripe_subscription_id;

        if ($subscriptionId === null || $subscriptionId === '') {
            return;
        }

        $this->bootstrapStripe();

        try {
            Subscription::cancel($subscriptionId);
        } catch (Throwable $exception) {
            Log::warning('Failed to cancel Stripe subscription during account deletion', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function handleWebhook(string $payload, ?string $signatureHeader): void
    {
        $this->bootstrapStripe();

        $webhookSecret = (string) config('services.stripe.webhook_secret');

        if ($webhookSecret === '') {
            throw new UnexpectedValueException('Stripe webhook secret is not configured.');
        }

        if ($signatureHeader === null || $signatureHeader === '') {
            throw new UnexpectedValueException('Stripe signature header is missing.');
        }

        try {
            $event = Webhook::constructEvent($payload, $signatureHeader, $webhookSecret);
        } catch (SignatureVerificationException|UnexpectedValueException $exception) {
            Log::warning('Stripe webhook verification failed', [
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event->data->object),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event->data->object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
            default => null,
        };
    }

    private function bootstrapStripe(): void
    {
        Stripe::setApiKey((string) config('services.stripe.secret'));
    }

    private function resolveCustomerId(User $user): string
    {
        if ($user->stripe_customer_id !== null && $user->stripe_customer_id !== '') {
            return $user->stripe_customer_id;
        }

        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => (string) $user->id,
            ],
        ]);

        $this->users->updateStripeCustomerId($user, $customer->id);

        return $customer->id;
    }

    private function handleCheckoutSessionCompleted(object $session): void
    {
        $user = $this->resolveUserFromCheckoutSession($session);

        if ($user === null) {
            return;
        }

        if (is_string($session->customer ?? null) && $session->customer !== '') {
            $this->users->updateStripeCustomerId($user, $session->customer);
        }

        if (is_string($session->subscription ?? null) && $session->subscription !== '') {
            $this->activateSubscription($user, $session->subscription);
        }
    }

    private function handleSubscriptionUpdated(object $subscription): void
    {
        $user = $this->resolveUserFromSubscription($subscription);

        if ($user === null) {
            return;
        }

        if ($this->isSubscriptionActive($subscription)) {
            $this->users->updateSubscription($user, 'paid', 'active', (string) $subscription->id);

            return;
        }

        $this->users->updateSubscription($user, 'free', 'inactive', null);
    }

    private function handleSubscriptionDeleted(object $subscription): void
    {
        $user = $this->resolveUserFromSubscription($subscription);

        if ($user === null) {
            return;
        }

        $this->users->updateSubscription($user, 'free', 'inactive', null);
    }

    private function activateSubscription(User $user, string $subscriptionId): void
    {
        $this->users->updateSubscription($user, 'paid', 'active', $subscriptionId);
    }

    private function resolveUserFromCheckoutSession(object $session): ?User
    {
        if (is_string($session->client_reference_id ?? null) && $session->client_reference_id !== '') {
            $user = $this->users->findById((int) $session->client_reference_id);

            if ($user !== null) {
                return $user;
            }
        }

        if (is_string($session->customer ?? null) && $session->customer !== '') {
            return $this->users->findByStripeCustomerId($session->customer);
        }

        return null;
    }

    private function resolveUserFromSubscription(object $subscription): ?User
    {
        if (is_string($subscription->id ?? null) && $subscription->id !== '') {
            $user = $this->users->findByStripeSubscriptionId($subscription->id);

            if ($user !== null) {
                return $user;
            }
        }

        if (is_string($subscription->customer ?? null) && $subscription->customer !== '') {
            return $this->users->findByStripeCustomerId($subscription->customer);
        }

        return null;
    }

    private function isSubscriptionActive(object $subscription): bool
    {
        $status = (string) ($subscription->status ?? '');

        return in_array($status, ['active', 'trialing'], true);
    }
}
