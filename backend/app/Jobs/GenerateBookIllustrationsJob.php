<?php

namespace App\Jobs;

use App\Services\IllustrationGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateBookIllustrationsJob implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public int $timeout = 10800;

    /**
     * @var list<int>
     */
    public array $backoff;

    public function __construct(public int $generationId)
    {
        $this->onQueue('generation-image');
        $this->tries = (int) config('services.observability.job_max_attempts', 3);
        $this->backoff = config('services.observability.job_backoff_seconds', [30, 120, 300]);
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return [
            'book-generation',
            'generation:'.$this->generationId,
        ];
    }

    public function handle(IllustrationGenerationService $illustrationGeneration): void
    {
        $illustrationGeneration->runForGeneration($this->generationId);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Illustration generation job exhausted retries', [
            'generation_id' => $this->generationId,
            'message' => $exception?->getMessage(),
        ]);

        app(IllustrationGenerationService::class)->failAfterExhaustedRetries(
            $this->generationId,
            $exception,
        );
    }
}
