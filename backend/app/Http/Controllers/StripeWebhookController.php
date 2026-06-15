<?php

namespace App\Http\Controllers;

use App\Services\StripeBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly StripeBillingService $billing,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        try {
            $this->billing->handleWebhook(
                $request->getContent(),
                $request->header('Stripe-Signature'),
            );
        } catch (SignatureVerificationException|UnexpectedValueException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 400);
        }

        return response()->json([
            'received' => true,
        ]);
    }
}
