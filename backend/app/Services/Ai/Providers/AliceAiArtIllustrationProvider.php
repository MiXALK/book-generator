<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\IllustrationGenerationProviderInterface;
use App\Services\Ai\Data\IllustrationGenerationInput;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Yandex AI Studio Alice AI ART image generation (OpenAI-compatible Images API).
 */
readonly class AliceAiArtIllustrationProvider implements IllustrationGenerationProviderInterface
{
    public function __construct(
        private string $apiKey,
        private string $folderId,
        private string $baseUrl,
        private string $model,
        private int $timeoutSeconds,
        private string $size,
    ) {}

    public function isConfigured(): bool
    {
        return $this->apiKey !== ''
            && $this->folderId !== ''
            && $this->baseUrl !== ''
            && $this->model !== '';
    }

    public function generateIllustration(IllustrationGenerationInput $input): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $url = rtrim($this->baseUrl, '/').'/images/generations';

            $response = Http::withHeaders([
                'Authorization' => 'Api-Key '.$this->apiKey,
            ])
                ->timeout($this->timeoutSeconds)
                ->connectTimeout($this->timeoutSeconds)
                ->acceptJson()
                ->post($url, [
                    'model' => $this->modelUri(),
                    'prompt' => $input->prompt,
                    'size' => $this->size,
                ]);

            if (! $response->successful()) {
                Log::warning('Alice AI ART illustration request failed', [
                    'status' => $response->status(),
                    'page_number' => $input->pageNumber,
                ]);

                return null;
            }

            $encoded = Arr::get($response->json(), 'data.0.b64_json');

            if (! is_string($encoded) || $encoded === '') {
                return null;
            }

            $binary = base64_decode($encoded, true);

            return $binary !== false ? $binary : null;
        } catch (Throwable $exception) {
            Log::warning('Alice AI ART illustration provider threw an exception', [
                'message' => $exception->getMessage(),
                'page_number' => $input->pageNumber,
            ]);

            return null;
        }
    }

    private function modelUri(): string
    {
        return 'art://'.$this->folderId.'/'.$this->model;
    }
}
