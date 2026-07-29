<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\IllustrationGenerationProviderInterface;
use App\Services\Ai\Providers\AliceAiArtIllustrationProvider;
use App\Services\Ai\Providers\OpenAiCompatibleIllustrationProvider;
use App\Services\Ai\Providers\YandexArtIllustrationProvider;
use InvalidArgumentException;

readonly class IllustrationGenerationProviderFactory
{
    public function make(): IllustrationGenerationProviderInterface
    {
        $driver = $this->resolveDriver((string) config('services.ai_image.driver', 'yandexart'));
        $preset = $this->presetForDriver($driver);

        $apiKey = (string) config('services.ai_image.api_key');
        $timeout = $this->presetInt($preset, 'timeout');
        $size = $this->presetString($preset, 'size');

        return match ($driver) {
            'aliceaiart' => new AliceAiArtIllustrationProvider(
                apiKey: $apiKey,
                folderId: (string) config('services.ai_image.folder_id'),
                baseUrl: $this->presetString($preset, 'base_url'),
                model: $this->presetString($preset, 'model'),
                timeoutSeconds: $timeout,
                size: $size !== '' ? $size : '666x832',
            ),
            'yandexart' => new YandexArtIllustrationProvider(
                apiKey: $apiKey,
                folderId: (string) config('services.ai_image.folder_id'),
                baseUrl: $this->presetString($preset, 'base_url'),
                operationsUrl: $this->presetString($preset, 'operations_url'),
                model: $this->presetString($preset, 'model'),
                timeoutSeconds: $timeout,
                pollIntervalSeconds: $this->presetPollInterval($preset),
                aspectRatio: $this->presetAspectRatio($preset),
            ),
            'openai' => new OpenAiCompatibleIllustrationProvider(
                apiKey: $apiKey,
                baseUrl: $this->presetString($preset, 'base_url'),
                model: $this->presetString($preset, 'model'),
                timeoutSeconds: $timeout,
                size: $size !== '' ? $size : '1024x1024',
            ),
            default => throw new InvalidArgumentException("Unsupported AI image driver [{$driver}]."),
        };
    }

    private function resolveDriver(string $driver): string
    {
        $drivers = config('services.ai_image.drivers', []);

        if (is_array($drivers) && isset($drivers[$driver])) {
            return $driver;
        }

        if (is_array($drivers)) {
            foreach ($drivers as $key => $preset) {
                if (! is_array($preset)) {
                    continue;
                }

                $model = $preset['model'] ?? null;

                if (is_string($model) && $model === $driver) {
                    return (string) $key;
                }
            }
        }

        return $driver;
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

    /**
     * @param  array<string, mixed>  $preset
     */
    private function presetPollInterval(array $preset): int
    {
        if (! isset($preset['poll_interval_seconds']) || ! is_int($preset['poll_interval_seconds'])) {
            return 2;
        }

        return $preset['poll_interval_seconds'];
    }

    /**
     * @param  array<string, mixed>  $preset
     * @return array{widthRatio: string, heightRatio: string}
     */
    private function presetAspectRatio(array $preset): array
    {
        $aspectRatio = $preset['aspect_ratio'] ?? null;

        if (! is_array($aspectRatio)) {
            return ['widthRatio' => '1', 'heightRatio' => '1'];
        }

        $widthRatio = $aspectRatio['widthRatio'] ?? '1';
        $heightRatio = $aspectRatio['heightRatio'] ?? '1';

        return [
            'widthRatio' => is_string($widthRatio) ? $widthRatio : '1',
            'heightRatio' => is_string($heightRatio) ? $heightRatio : '1',
        ];
    }
}
