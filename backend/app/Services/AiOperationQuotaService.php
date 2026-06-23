<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;

readonly class AiOperationQuotaService
{
    public function ensureCanGenerateText(User $user): void
    {
        $limit = (int) config('services.scaling.ai_text_daily_limit', 20);
        $count = $this->dailyCount($user->id, 'text');

        if ($count >= $limit) {
            throw new HttpResponseException(response()->json([
                'message' => 'Daily AI text generation limit reached. Please try again tomorrow.',
                'limit' => $limit,
            ], 429));
        }
    }

    public function ensureCanGenerateImages(User $user, int $pageCount): void
    {
        $limit = (int) config('services.scaling.ai_image_daily_limit', 50);
        $count = $this->dailyCount($user->id, 'image');

        if ($count + $pageCount > $limit) {
            throw new HttpResponseException(response()->json([
                'message' => 'Daily AI illustration limit reached. Please try again tomorrow.',
                'limit' => $limit,
            ], 429));
        }
    }

    public function recordTextGeneration(User $user): void
    {
        $this->incrementDailyCount($user->id, 'text');
    }

    public function recordImageGenerations(User $user, int $count): void
    {
        if ($count <= 0) {
            return;
        }

        $this->incrementDailyCount($user->id, 'image', $count);
    }

    private function dailyCount(int $userId, string $operation): int
    {
        $value = Cache::get($this->dailyKey($userId, $operation));

        if (! is_int($value)) {
            return 0;
        }

        return $value;
    }

    private function incrementDailyCount(int $userId, string $operation, int $amount = 1): void
    {
        $key = $this->dailyKey($userId, $operation);
        $expiresAt = now()->endOfDay();

        if (! Cache::has($key)) {
            Cache::put($key, $amount, $expiresAt);

            return;
        }

        Cache::increment($key, $amount);
    }

    private function dailyKey(int $userId, string $operation): string
    {
        $date = now()->toDateString();

        return "ai-quota:{$operation}:{$userId}:{$date}";
    }
}
