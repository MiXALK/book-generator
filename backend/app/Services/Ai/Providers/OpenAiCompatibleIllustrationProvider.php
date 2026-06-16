<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\IllustrationGenerationProviderInterface;
use App\Services\Ai\Data\IllustrationGenerationInput;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Works with OpenAI-compatible image generation APIs (OpenAI DALL-E, etc.).
 */
readonly class OpenAiCompatibleIllustrationProvider implements IllustrationGenerationProviderInterface
{
    public function __construct(
        private string $apiKey,
        private string $baseUrl,
        private string $model,
        private int $timeoutSeconds,
        private string $size,
    ) {}

    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->baseUrl !== '' && $this->model !== '';
    }

    public function generateIllustration(IllustrationGenerationInput $input): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $imagesUrl = rtrim($this->baseUrl, '/').'/images/generations';

            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeoutSeconds)
                ->connectTimeout($this->timeoutSeconds)
                ->acceptJson()
                ->post($imagesUrl, [
                    'model' => $this->model,
                    'prompt' => $input->prompt,
                    'size' => $this->size,
                    'n' => 1,
                    'response_format' => 'b64_json',
                ]);

            if (! $response->successful()) {
                Log::warning('Illustration provider request failed', [
                    'status' => $response->status(),
                    'base_url' => $this->baseUrl,
                    'model' => $this->model,
                    'page_number' => $input->pageNumber,
                ]);

                return null;
            }

            $responseData = $response->json();
            $encoded = Arr::get($responseData, 'data.0.b64_json');

            if (! is_string($encoded) || $encoded === '') {
                return null;
            }

            $binary = base64_decode($encoded, true);

            if ($binary === false) {
                return null;
            }

            return $binary;
        } catch (Throwable $exception) {
            Log::warning('Illustration provider threw an exception', [
                'message' => $exception->getMessage(),
                'page_number' => $input->pageNumber,
            ]);

            return null;
        }
    }
}
