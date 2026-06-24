<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\IllustrationGenerationProviderInterface;
use App\Services\Ai\Data\IllustrationGenerationInput;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Yandex Cloud Foundation Models YandexART image generation (async REST API).
 */
readonly class YandexArtIllustrationProvider implements IllustrationGenerationProviderInterface
{
    /**
     * @param  array{widthRatio: string, heightRatio: string}  $aspectRatio
     */
    public function __construct(
        private string $apiKey,
        private string $folderId,
        private string $baseUrl,
        private string $operationsUrl,
        private string $model,
        private int $timeoutSeconds,
        private int $pollIntervalSeconds,
        private array $aspectRatio,
    ) {}

    public function isConfigured(): bool
    {
        return $this->apiKey !== ''
            && $this->folderId !== ''
            && $this->baseUrl !== ''
            && $this->operationsUrl !== ''
            && $this->model !== '';
    }

    public function generateIllustration(IllustrationGenerationInput $input): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $operationId = $this->startGeneration($input);

            if ($operationId === null) {
                return null;
            }

            return $this->waitForImage($operationId, $input->pageNumber);
        } catch (Throwable $exception) {
            Log::warning('YandexART illustration provider threw an exception', [
                'message' => $exception->getMessage(),
                'page_number' => $input->pageNumber,
            ]);

            return null;
        }
    }

    private function startGeneration(IllustrationGenerationInput $input): ?string
    {
        $url = rtrim($this->baseUrl, '/').'/imageGenerationAsync';

        $response = $this->httpClient()
            ->post($url, [
                'modelUri' => $this->modelUri(),
                'generationOptions' => [
                    'aspectRatio' => $this->aspectRatio,
                ],
                'messages' => [
                    [
                        'text' => $input->prompt,
                        'weight' => 1,
                    ],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('YandexART illustration request failed', [
                'status' => $response->status(),
                'page_number' => $input->pageNumber,
                'body' => $response->body(),
            ]);

            return null;
        }

        $operationId = Arr::get($response->json(), 'id');

        if (! is_string($operationId) || $operationId === '') {
            return null;
        }

        return $operationId;
    }

    private function waitForImage(string $operationId, int $pageNumber): ?string
    {
        $deadline = time() + $this->timeoutSeconds;
        $operationUrl = rtrim($this->operationsUrl, '/').'/'.$operationId;

        while (time() < $deadline) {
            $response = $this->httpClient()->get($operationUrl);

            if (! $response->successful()) {
                Log::warning('YandexART operation poll failed', [
                    'status' => $response->status(),
                    'operation_id' => $operationId,
                    'page_number' => $pageNumber,
                ]);

                return null;
            }

            $payload = $response->json();

            if (Arr::get($payload, 'error') !== null) {
                Log::warning('YandexART operation returned an error', [
                    'operation_id' => $operationId,
                    'page_number' => $pageNumber,
                ]);

                return null;
            }

            if (Arr::get($payload, 'done') !== true) {
                sleep($this->pollIntervalSeconds);

                continue;
            }

            $encoded = Arr::get($payload, 'response.image');

            if (! is_string($encoded) || $encoded === '') {
                return null;
            }

            $binary = base64_decode($encoded, true);

            if ($binary === false) {
                return null;
            }

            return $binary;
        }

        Log::warning('YandexART operation timed out', [
            'operation_id' => $operationId,
            'page_number' => $pageNumber,
        ]);

        return null;
    }

    private function modelUri(): string
    {
        return 'art://'.$this->folderId.'/'.$this->model;
    }

    private function httpClient(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Api-Key '.$this->apiKey,
        ])
            ->timeout($this->timeoutSeconds)
            ->connectTimeout($this->timeoutSeconds)
            ->acceptJson();
    }
}
