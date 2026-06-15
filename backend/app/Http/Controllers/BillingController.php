<?php

namespace App\Http\Controllers;

use App\Services\StripeBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use UnexpectedValueException;

class BillingController extends Controller
{
    public function __construct(
        private readonly StripeBillingService $billing,
    ) {}

    public function checkout(Request $request): JsonResponse
    {
        if (! $this->billing->isConfigured()) {
            return response()->json([
                'message' => 'Billing is not configured.',
            ], 503);
        }

        $url = $this->billing->createCheckoutSession($request->user());

        return response()->json([
            'url' => $url,
        ]);
    }

    public function portal(Request $request): JsonResponse
    {
        if (! $this->billing->isConfigured()) {
            return response()->json([
                'message' => 'Billing is not configured.',
            ], 503);
        }

        try {
            $url = $this->billing->createPortalSession($request->user());
        } catch (UnexpectedValueException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'url' => $url,
        ]);
    }
}
