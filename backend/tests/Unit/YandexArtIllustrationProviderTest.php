<?php

namespace Tests\Unit;

use App\Services\Ai\Data\IllustrationGenerationInput;
use App\Services\Ai\IllustrationGenerationProviderFactory;
use App\Services\Ai\Providers\OpenAiCompatibleIllustrationProvider;
use App\Services\Ai\Providers\YandexArtIllustrationProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YandexArtIllustrationProviderTest extends TestCase
{
    public function test_is_not_configured_without_api_key(): void
    {
        $provider = $this->makeProvider(apiKey: '');

        $this->assertFalse($provider->isConfigured());
    }

    public function test_is_not_configured_without_folder_id(): void
    {
        $provider = $this->makeProvider(folderId: '');

        $this->assertFalse($provider->isConfigured());
    }

    public function test_generate_illustration_returns_null_when_not_configured(): void
    {
        $provider = $this->makeProvider(apiKey: '');

        $result = $provider->generateIllustration($this->sampleInput());

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_generate_illustration_submits_async_request_and_polls_operation(): void
    {
        $imageBinary = 'fake-jpeg-bytes';
        $encodedImage = base64_encode($imageBinary);

        Http::fake([
            'llm.api.cloud.yandex.net/foundationModels/v1/imageGenerationAsync' => Http::response(['id' => 'op-123']),
            'llm.api.cloud.yandex.net/operations/*' => Http::response([
                'id' => 'op-123',
                'done' => true,
                'response' => [
                    'image' => $encodedImage,
                ],
            ]),
        ]);

        $provider = $this->makeProvider();

        $result = $provider->generateIllustration($this->sampleInput());

        $this->assertSame($imageBinary, $result);

        Http::assertSent(function ($request): bool {
            if ($request->method() !== 'POST') {
                return false;
            }

            if ($request->url() !== 'https://llm.api.cloud.yandex.net/foundationModels/v1/imageGenerationAsync') {
                return false;
            }

            $body = $request->data();

            return ($body['modelUri'] ?? null) === 'art://b1gtestfolder/yandex-art/latest'
                && ($body['messages'][0]['text'] ?? null) === 'A cozy forest scene.'
                && ($body['messages'][0]['weight'] ?? null) === 1
                && ($body['messages'][1]['text'] ?? null) === 'text, letters, words, caption, title, watermark, signature, typography, Cyrillic, Latin'
                && ($body['messages'][1]['weight'] ?? null) === -1
                && ($body['generationOptions']['aspectRatio']['widthRatio'] ?? null) === '4'
                && ($body['generationOptions']['aspectRatio']['heightRatio'] ?? null) === '5'
                && $request->hasHeader('Authorization', 'Api-Key test-image-key');
        });

        Http::assertSent(function ($request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://llm.api.cloud.yandex.net/operations/op-123';
        });
    }

    public function test_factory_resolves_yandexart_driver(): void
    {
        config()->set('services.ai_image.driver', 'yandexart');
        config()->set('services.ai_image.api_key', 'test-key');
        config()->set('services.ai_image.folder_id', 'b1gtestfolder');

        $provider = $this->app->make(IllustrationGenerationProviderFactory::class)->make();

        $this->assertInstanceOf(YandexArtIllustrationProvider::class, $provider);
        $this->assertTrue($provider->isConfigured());
    }

    public function test_factory_yandexart_driver_uses_documented_endpoint_and_model(): void
    {
        Http::fake([
            'llm.api.cloud.yandex.net/foundationModels/v1/imageGenerationAsync' => Http::response(['id' => 'op-123']),
            'llm.api.cloud.yandex.net/operations/*' => Http::response([
                'id' => 'op-123',
                'done' => true,
                'response' => [
                    'image' => base64_encode('fake-jpeg-bytes'),
                ],
            ]),
        ]);

        config()->set('services.ai_image.driver', 'yandexart');
        config()->set('services.ai_image.api_key', 'test-key');
        config()->set('services.ai_image.folder_id', 'b1gtestfolder');

        $provider = $this->app->make(IllustrationGenerationProviderFactory::class)->make();

        $provider->generateIllustration($this->sampleInput());

        Http::assertSent(function ($request): bool {
            if ($request->method() !== 'POST') {
                return false;
            }

            $body = $request->data();

            return $request->url() === 'https://llm.api.cloud.yandex.net/foundationModels/v1/imageGenerationAsync'
                && ($body['modelUri'] ?? null) === 'art://b1gtestfolder/yandex-art/latest';
        });

        Http::assertSent(function ($request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://llm.api.cloud.yandex.net/operations/op-123';
        });
    }

    public function test_factory_resolves_openai_driver(): void
    {
        config()->set('services.ai_image.driver', 'openai');
        config()->set('services.ai_image.api_key', 'test-key');

        $provider = $this->app->make(IllustrationGenerationProviderFactory::class)->make();

        $this->assertInstanceOf(OpenAiCompatibleIllustrationProvider::class, $provider);
        $this->assertTrue($provider->isConfigured());
    }

    public function test_factory_resolves_driver_when_model_name_is_configured_by_mistake(): void
    {
        config()->set('services.ai_image.driver', 'yandex-art/latest');
        config()->set('services.ai_image.api_key', 'test-key');
        config()->set('services.ai_image.folder_id', 'b1gtestfolder');

        $provider = $this->app->make(IllustrationGenerationProviderFactory::class)->make();

        $this->assertInstanceOf(YandexArtIllustrationProvider::class, $provider);
        $this->assertTrue($provider->isConfigured());
    }

    private function makeProvider(string $apiKey = 'test-image-key', string $folderId = 'b1gtestfolder'): YandexArtIllustrationProvider
    {
        return new YandexArtIllustrationProvider(
            apiKey: $apiKey,
            folderId: $folderId,
            baseUrl: 'https://llm.api.cloud.yandex.net/foundationModels/v1',
            operationsUrl: 'https://llm.api.cloud.yandex.net/operations',
            model: 'yandex-art/latest',
            timeoutSeconds: 30,
            pollIntervalSeconds: 0,
            aspectRatio: ['widthRatio' => '4', 'heightRatio' => '5'],
        );
    }

    private function sampleInput(): IllustrationGenerationInput
    {
        return new IllustrationGenerationInput(
            prompt: 'A cozy forest scene.',
            pageNumber: 1,
        );
    }
}
