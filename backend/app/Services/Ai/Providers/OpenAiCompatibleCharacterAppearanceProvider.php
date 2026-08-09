<?php

namespace App\Services\Ai\Providers;

use App\Services\Ai\Contracts\CharacterAppearanceProviderInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class OpenAiCompatibleCharacterAppearanceProvider implements CharacterAppearanceProviderInterface
{
    private const ANALYSIS_PROMPT = 'Describe only stable visible traits of the child for consistent storybook illustrations. '.
        'Return JSON {"appearance":"..."} in English, at most 70 characters. Include face shape, skin tone, eyes, '.
        'hair color and hairstyle, and distinctive visible non-sensitive features. Do not identify the person or infer ethnicity.';

    public function __construct(
        private string $apiKey,
        private string $baseUrl,
        private string $model,
        private int $timeoutSeconds,
    ) {}

    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->baseUrl !== '' && $this->model !== '';
    }

    public function describe(string $imageBinary, string $mimeType): ?string
    {
        if (! $this->isConfigured() || $imageBinary === '' || ! str_starts_with($mimeType, 'image/')) {
            return null;
        }

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeoutSeconds)
                ->connectTimeout($this->timeoutSeconds)
                ->acceptJson()
                ->post(rtrim($this->baseUrl, '/').'/chat/completions', [
                    'model' => $this->model,
                    'messages' => [[
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => self::ANALYSIS_PROMPT],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:'.$mimeType.';base64,'.base64_encode($imageBinary),
                                ],
                            ],
                        ],
                    ]],
                    'max_tokens' => 120,
                    'temperature' => 0,
                    'response_format' => ['type' => 'json_object'],
                    'enable_thinking' => false,
                ]);

            if (! $response->successful()) {
                Log::warning('Character appearance provider request failed', [
                    'status' => $response->status(),
                    'base_url' => $this->baseUrl,
                    'model' => $this->model,
                ]);

                return null;
            }

            $content = trim((string) Arr::get($response->json(), 'choices.0.message.content', ''));
            $decoded = json_decode($content, true);
            $appearance = is_array($decoded) ? ($decoded['appearance'] ?? null) : null;

            if (! is_string($appearance) || trim($appearance) === '') {
                return null;
            }

            return trim($appearance);
        } catch (Throwable $exception) {
            Log::warning('Character appearance provider exception', [
                'message' => $exception->getMessage(),
                'base_url' => $this->baseUrl,
                'model' => $this->model,
            ]);

            return null;
        }
    }
}
