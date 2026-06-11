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
 * Works with any OpenAI-compatible chat completions API (Qwen, DeepSeek, OpenAI, etc.).
 */
readonly class OpenAiCompatibleStoryTextProvider implements StoryTextGenerationProviderInterface
{
    /**
     * @param  array<string, mixed>  $requestExtras
     */
    public function __construct(
        private string $apiKey,
        private string $baseUrl,
        private string $model,
        private int $timeoutSeconds,
        private StoryTextPromptComposer $promptComposer,
        private array $requestExtras = [],
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
            $chatCompletionsUrl = rtrim($this->baseUrl, '/').'/chat/completions';

            $messages = [
                ['role' => 'system', 'content' => $this->promptComposer->systemMessage()],
                ['role' => 'user', 'content' => $this->promptComposer->userMessage($input)],
            ];

            $payload = array_merge([
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => 500,
                'temperature' => 0.8,
            ], $this->requestExtras);

            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeoutSeconds)
                ->connectTimeout($this->timeoutSeconds)
                ->acceptJson()
                ->post($chatCompletionsUrl, $payload);

            if (! $response->successful()) {
                Log::warning('Story text provider request failed', [
                    'status' => $response->status(),
                    'base_url' => $this->baseUrl,
                    'model' => $this->model,
                ]);

                return null;
            }

            $responseData = $response->json();
            $content = (string) Arr::get($responseData, 'choices.0.message.content', '');
            $decoded = json_decode($content, true);

            if (! is_array($decoded) || ! isset($decoded['pages']) || ! is_array($decoded['pages'])) {
                return null;
            }

            $pages = [];
            foreach ($decoded['pages'] as $page) {
                if (! is_string($page)) {
                    continue;
                }

                $trimmedPage = trim($page);

                if ($trimmedPage === '') {
                    continue;
                }

                $pages[] = $trimmedPage;
            }

            if ($pages === []) {
                return null;
            }

            return $pages;
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
