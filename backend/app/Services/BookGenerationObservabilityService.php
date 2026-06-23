<?php

namespace App\Services;

use App\Models\BookGeneration;
use App\Notifications\BookReadyNotification;
use App\Repositories\Contracts\BookGenerationRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

readonly class BookGenerationObservabilityService
{
    public function __construct(
        private BookGenerationRepositoryInterface $bookGenerations,
    ) {}

    public function newCorrelationId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function withContext(int $generationId, string $correlationId, callable $callback): mixed
    {
        Log::shareContext([
            'correlation_id' => $correlationId,
            'generation_id' => $generationId,
        ]);

        try {
            return $callback();
        } finally {
            Log::flushSharedContext();
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function logStage(
        int $generationId,
        string $correlationId,
        string $stage,
        string $message,
        array $context = [],
        string $level = 'info',
    ): void {
        Log::log($level, $message, array_merge([
            'correlation_id' => $correlationId,
            'generation_id' => $generationId,
            'stage' => $stage,
        ], $context));
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return array{result: TReturn, duration_ms: int}
     */
    public function measure(callable $callback): array
    {
        $startedAt = hrtime(true);
        $result = $callback();
        $durationMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);

        return [
            'result' => $result,
            'duration_ms' => $durationMs,
        ];
    }

    /**
     * @param  array<string, int|null>  $metrics
     */
    public function recordLatencyMetrics(BookGeneration $generation, array $metrics): void
    {
        $payload = [];

        foreach (['text_duration_ms', 'layout_duration_ms', 'image_duration_ms'] as $key) {
            if (array_key_exists($key, $metrics) && $metrics[$key] !== null) {
                $payload[$key] = $metrics[$key];
            }
        }

        if ($payload === []) {
            return;
        }

        $this->bookGenerations->updateLatencyMetrics($generation, $payload);
    }

    public function notifyBookReady(BookGeneration $generation): void
    {
        if (! config('services.observability.notify_on_book_ready', true)) {
            return;
        }

        $generation = $this->bookGenerations->findWithUser($generation->id);

        if ($generation === null || $generation->user === null) {
            return;
        }

        $generation->user->notify(new BookReadyNotification($generation));
    }
}
