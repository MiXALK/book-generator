<?php

namespace App\Jobs;

use App\Services\IllustrationGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateBookPageIllustrationJob implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public int $timeout = 10800;

    /**
     * @var list<int>
     */
    public array $backoff;

    public function __construct(
        public int $generationId,
        public int $pageId,
    ) {
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
            'page:'.$this->pageId,
        ];
    }

    public function handle(IllustrationGenerationService $illustrationGeneration): void
    {
        $illustrationGeneration->runForPage($this->generationId, $this->pageId);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Page illustration generation job exhausted retries', [
            'generation_id' => $this->generationId,
            'page_id' => $this->pageId,
            'message' => $exception?->getMessage(),
        ]);

        app(IllustrationGenerationService::class)->failPageAfterExhaustedRetries(
            $this->generationId,
            $this->pageId,
            $exception,
        );
    }
}
