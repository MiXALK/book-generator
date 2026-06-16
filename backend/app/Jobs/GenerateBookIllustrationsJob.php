<?php

namespace App\Jobs;

use App\Services\IllustrationGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateBookIllustrationsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $generationId)
    {
        $this->onQueue('generation-image');
    }

    public function handle(IllustrationGenerationService $illustrationGeneration): void
    {
        $illustrationGeneration->runForGeneration($this->generationId);
    }
}
