<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\IllustrationGenerationProviderInterface;
use App\Services\Ai\Providers\OpenAiCompatibleIllustrationProvider;
use InvalidArgumentException;

readonly class IllustrationGenerationProviderFactory
{
    public function make(): IllustrationGenerationProviderInterface
    {
        $driver = (string) config('services.ai_image.driver', 'openai');
        $preset = $this->presetForDriver($driver);

        $apiKey = (string) config('services.ai_image.api_key');
        $baseUrl = $this->presetString($preset, 'base_url');
        $model = $this->presetString($preset, 'model');
        $timeout = $this->presetInt($preset, 'timeout');
        $size = $this->presetString($preset, 'size');

        return new OpenAiCompatibleIllustrationProvider(
            apiKey: $apiKey,
            baseUrl: $baseUrl,
            model: $model,
            timeoutSeconds: $timeout,
            size: $size !== '' ? $size : '1024x1024',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function presetForDriver(string $driver): array
    {
        $drivers = config('services.ai_image.drivers', []);

        if (! is_array($drivers) || ! isset($drivers[$driver]) || ! is_array($drivers[$driver])) {
            throw new InvalidArgumentException("Unsupported AI image driver [{$driver}].");
        }

        return $drivers[$driver];
    }

    /**
     * @param  array<string, mixed>  $preset
     */
    private function presetString(array $preset, string $key): string
    {
        if (! isset($preset[$key]) || ! is_string($preset[$key])) {
            return '';
        }

        return $preset[$key];
    }

    /**
     * @param  array<string, mixed>  $preset
     */
    private function presetInt(array $preset, string $key): int
    {
        if (! isset($preset[$key]) || ! is_int($preset[$key])) {
            throw new InvalidArgumentException("AI image driver preset is missing [{$key}].");
        }

        return $preset[$key];
    }
}
