<?php

namespace Tests\Unit;

use App\Services\Ai\Data\StoryTextGenerationInput;
use App\Services\Ai\Providers\OpenAiCompatibleStoryTextProvider;
use App\Services\Ai\StoryTextGenerationProviderFactory;
use App\Services\Ai\StoryTextPromptComposer;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiCompatibleStoryTextProviderTest extends TestCase
{
    public function test_is_not_configured_without_api_key(): void
    {
        $provider = $this->makeProvider(apiKey: '');

        $this->assertFalse($provider->isConfigured());
    }

    public function test_generate_story_returns_null_when_not_configured(): void
    {
        $provider = $this->makeProvider(apiKey: '');

        $result = $provider->generateStory($this->sampleInput());

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_generate_story_merges_request_extras_and_parses_json_response(): void
    {
        Http::fake([
            'dashscope-intl.aliyuncs.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'story' => 'Anna found a star. She shared it with friends.',
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $provider = $this->makeProvider(requestExtras: [
            'response_format' => ['type' => 'json_object'],
        ]);

        $result = $provider->generateStory($this->sampleInput());

        $this->assertSame('Anna found a star. She shared it with friends.', $result?->story);

        Http::assertSent(function ($request): bool {
            $body = $request->data();
            $expectedUrl = 'https://dashscope-intl.aliyuncs.com/compatible-mode/v1/chat/completions';

            if ($request->url() !== $expectedUrl) {
                return false;
            }

            $responseFormatType = $body['response_format']['type'] ?? null;

            if ($responseFormatType !== 'json_object') {
                return false;
            }

            $model = $body['model'] ?? null;

            return $model === 'qwen-turbo';
        });
    }

    public function test_factory_resolves_qwen_driver_with_request_extras(): void
    {
        config()->set('services.ai_text.driver', 'qwen');
        config()->set('services.ai_text.api_key', 'test-key');

        $provider = $this->app->make(StoryTextGenerationProviderFactory::class)->make();

        $this->assertInstanceOf(OpenAiCompatibleStoryTextProvider::class, $provider);
        $this->assertTrue($provider->isConfigured());
    }

    /**
     * @param  array<string, mixed>  $requestExtras
     */
    private function makeProvider(string $apiKey = 'test-key', array $requestExtras = []): OpenAiCompatibleStoryTextProvider
    {
        return new OpenAiCompatibleStoryTextProvider(
            apiKey: $apiKey,
            baseUrl: 'https://dashscope-intl.aliyuncs.com/compatible-mode/v1',
            model: 'qwen-turbo',
            timeoutSeconds: 30,
            promptComposer: new StoryTextPromptComposer,
            requestExtras: $requestExtras,
        );
    }

    private function sampleInput(): StoryTextGenerationInput
    {
        return new StoryTextGenerationInput(
            promptText: 'Write a short story.',
            childName: 'Anna',
            childAge: 5,
            childGoal: 'learn to share',
        );
    }
}
