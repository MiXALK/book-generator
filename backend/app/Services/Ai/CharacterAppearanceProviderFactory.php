<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\CharacterAppearanceProviderInterface;
use App\Services\Ai\Providers\OpenAiCompatibleCharacterAppearanceProvider;

readonly class CharacterAppearanceProviderFactory
{
    public function make(): CharacterAppearanceProviderInterface
    {
        return new OpenAiCompatibleCharacterAppearanceProvider(
            apiKey: (string) config('services.ai_text.api_key'),
            baseUrl: (string) config('services.ai_text.drivers.qwen.base_url'),
            model: (string) config('services.ai_text.drivers.qwen.model'),
            timeoutSeconds: (int) config('services.ai_text.drivers.qwen.timeout', 90),
        );
    }
}
