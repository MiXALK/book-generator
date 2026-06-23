<?php

namespace App\Jobs;

use App\Services\BookGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AssembleBookLayoutJob implements ShouldQueue
{
    use Queueable;

    public int $tries;

    public int $timeout;

    /**
     * @var list<int>
     */
    public array $backoff;

    public function __construct(public int $generationId)
    {
        $this->onQueue('generation-layout');
        $this->tries = (int) config('services.observability.job_max_attempts', 3);
        $this->timeout = (int) config('services.scaling.layout_job_timeout_seconds', 300);
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
            'stage:layout',
        ];
    }

    public function handle(BookGenerationService $generationService): void
    {
        $generationService->runLayoutAssembly($this->generationId);
    }
}
