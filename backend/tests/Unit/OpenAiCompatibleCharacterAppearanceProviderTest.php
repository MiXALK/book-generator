<?php

namespace Tests\Unit;

use App\Services\Ai\CharacterAppearanceProviderFactory;
use App\Services\Ai\Providers\OpenAiCompatibleCharacterAppearanceProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiCompatibleCharacterAppearanceProviderTest extends TestCase
{
    public function test_is_not_configured_without_api_key(): void
    {
        $provider = $this->makeProvider(apiKey: '');

        $this->assertFalse($provider->isConfigured());
        $this->assertNull($provider->describe('private-image', 'image/jpeg'));
        Http::assertNothingSent();
    }

    public function test_sends_base64_image_to_qwen_and_parses_appearance(): void
    {
        Http::fake([
            'maas.aliyuncs.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'appearance' => 'oval face, green eyes, long curly black hair',
                        ]),
                    ],
                ]],
            ]),
        ]);

        $provider = $this->makeProvider();
        $result = $provider->describe('private-image', 'image/jpeg');

        $this->assertSame('oval face, green eyes, long curly black hair', $result);
        Http::assertSent(function ($request): bool {
            $body = $request->data();
            $imageUrl = $body['messages'][0]['content'][1]['image_url']['url'] ?? null;

            return $request->url() === 'https://maas.aliyuncs.com/compatible-mode/v1/chat/completions'
                && ($body['model'] ?? null) === 'qwen3.6-flash'
                && ($body['enable_thinking'] ?? null) === false
                && $imageUrl === 'data:image/jpeg;base64,'.base64_encode('private-image');
        });
    }

    public function test_factory_reuses_qwen_text_configuration(): void
    {
        config([
            'services.ai_text.api_key' => 'qwen-key',
            'services.ai_text.drivers.qwen.base_url' => 'https://maas.aliyuncs.com/compatible-mode/v1',
            'services.ai_text.drivers.qwen.model' => 'qwen3.6-flash',
        ]);

        $provider = $this->app->make(CharacterAppearanceProviderFactory::class)->make();

        $this->assertInstanceOf(OpenAiCompatibleCharacterAppearanceProvider::class, $provider);
        $this->assertTrue($provider->isConfigured());
    }

    public function test_returns_null_for_unsuccessful_response(): void
    {
        Http::fake([
            'maas.aliyuncs.com/*' => Http::response(['choices' => []], 422),
        ]);

        $this->assertNull($this->makeProvider()->describe('private-image', 'image/png'));
    }

    private function makeProvider(
        string $apiKey = 'test-key',
    ): OpenAiCompatibleCharacterAppearanceProvider {
        return new OpenAiCompatibleCharacterAppearanceProvider(
            apiKey: $apiKey,
            baseUrl: 'https://maas.aliyuncs.com/compatible-mode/v1',
            model: 'qwen3.6-flash',
            timeoutSeconds: 30,
        );
    }
}
