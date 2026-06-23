<?php

namespace App\Http\Controllers;

use App\Enums\AgeRange;
use App\Services\SubscriptionAccessService;
use App\Services\TemplateCatalogCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    public function __construct(
        private readonly TemplateCatalogCacheService $catalogCache,
        private readonly SubscriptionAccessService $subscriptionAccess,
    ) {}

    public function catalog(Request $request): JsonResponse
    {
        $user = $request->user();
        $hasPaidAccess = $this->subscriptionAccess->hasActivePaidAccess($user);

        return response()->json([
            'goals' => $this->catalogCache->listGoalsForCatalog($hasPaidAccess),
            'age_ranges' => AgeRange::catalog(),
            'monthly_limit' => $this->subscriptionAccess->monthlyLimit($user),
            'has_paid_access' => $hasPaidAccess,
        ]);
    }
}
