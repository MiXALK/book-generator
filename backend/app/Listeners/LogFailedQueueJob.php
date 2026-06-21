<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

class LogFailedQueueJob
{
    public function handle(JobFailed $event): void
    {
        $payload = $event->job->payload();
        $jobName = $payload['displayName'] ?? 'unknown';
        $context = [
            'job' => $jobName,
            'queue' => $event->job->getQueue(),
            'connection' => $event->connectionName,
            'exception' => $event->exception->getMessage(),
        ];

        $command = $payload['data']['command'] ?? null;

        if (is_string($command) && preg_match('/generationId";i:(\d+)/', $command, $matches) === 1) {
            $context['generation_id'] = (int) $matches[1];
        }

        Log::error('Queue job failed', $context);
    }
}
