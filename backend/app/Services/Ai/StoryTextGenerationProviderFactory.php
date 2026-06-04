<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\StoryTextGenerationProviderInterface;
use App\Services\Ai\Providers\OpenAiCompatibleStoryTextProvider;
use InvalidArgumentException;

readonly class StoryTextGenerationProviderFactory
{
    public function __construct(private StoryTextPromptComposer $promptComposer) {}

    public function make(): StoryTextGenerationProviderInterface
    {
        $driver = (string) config('services.ai_text.driver', 'deepseek');
        $preset = $this->presetForDriver($driver);

        $apiKey = (string) config('services.ai_text.api_key');
        $baseUrl = $this->presetString($preset, 'base_url');
        $model = $this->presetString($preset, 'model');
        $timeout = $this->presetInt($preset, 'timeout');

        return new OpenAiCompatibleStoryTextProvider(
            apiKey: $apiKey,
            baseUrl: $baseUrl,
            model: $model,
            timeoutSeconds: $timeout,
            promptComposer: $this->promptComposer,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function presetForDriver(string $driver): array
    {
        $drivers = config('services.ai_text.drivers', []);

        if (! is_array($drivers)) {
            throw new InvalidArgumentException("Unsupported AI text driver [{$driver}].");
        }

        if (! isset($drivers[$driver])) {
            throw new InvalidArgumentException("Unsupported AI text driver [{$driver}].");
        }

        $preset = $drivers[$driver];

        if (! is_array($preset)) {
            throw new InvalidArgumentException("Unsupported AI text driver [{$driver}].");
        }

        return $preset;
    }

    /**
     * @param  array<string, mixed>  $preset
     */
    private function presetString(array $preset, string $key): string
    {
        if (! isset($preset[$key])) {
            return '';
        }

        $value = $preset[$key];

        if (! is_string($value)) {
            return '';
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $preset
     */
    private function presetInt(array $preset, string $key): int
    {
        if (! isset($preset[$key])) {
            throw new InvalidArgumentException("AI text driver preset is missing [{$key}].");
        }

        $value = $preset[$key];

        if (! is_int($value)) {
            throw new InvalidArgumentException("AI text driver preset [{$key}] must be an integer.");
        }

        return $value;
    }
}
