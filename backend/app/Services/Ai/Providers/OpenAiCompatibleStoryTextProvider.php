<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\StoryTextGenerationProviderInterface;
use App\Services\Ai\Data\StoryTextGenerationInput;
use App\Services\Ai\StoryTextPromptComposer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Works with any OpenAI-compatible chat completions API (DeepSeek, OpenAI, etc.).
 */
readonly class OpenAiCompatibleStoryTextProvider implements StoryTextGenerationProviderInterface
{
    public function __construct(
        private string $apiKey,
        private string $baseUrl,
        private string $model,
        private int $timeoutSeconds,
        private StoryTextPromptComposer $promptComposer,
    ) {}

    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->baseUrl !== '' && $this->model !== '';
    }

    public function generatePages(StoryTextGenerationInput $input): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeoutSeconds)
                ->acceptJson()
                ->post(rtrim($this->baseUrl, '/').'/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $this->promptComposer->systemMessage()],
                        ['role' => 'user', 'content' => $this->promptComposer->userMessage($input)],
                    ],
                    'temperature' => 0.7,
                ]);

            if (! $response->successful()) {
                Log::warning('Story text provider request failed', [
                    'status' => $response->status(),
                    'base_url' => $this->baseUrl,
                    'model' => $this->model,
                ]);

                return null;
            }

            $content = (string) Arr::get($response->json(), 'choices.0.message.content', '');
            $decoded = json_decode($content, true);

            if (! is_array($decoded) || ! isset($decoded['pages']) || ! is_array($decoded['pages'])) {
                return null;
            }

            $pages = [];
            foreach ($decoded['pages'] as $page) {
                if (is_string($page) && trim($page) !== '') {
                    $pages[] = trim($page);
                }
            }

            return $pages !== [] ? $pages : null;
        } catch (Throwable $exception) {
            Log::warning('Story text provider exception', [
                'message' => $exception->getMessage(),
                'base_url' => $this->baseUrl,
                'model' => $this->model,
            ]);

            return null;
        }
    }
}
