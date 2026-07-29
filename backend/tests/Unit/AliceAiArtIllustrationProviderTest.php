<?php

namespace Tests\Unit;

use App\Services\Ai\Data\IllustrationGenerationInput;
use App\Services\Ai\IllustrationGenerationProviderFactory;
use App\Services\Ai\Providers\AliceAiArtIllustrationProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AliceAiArtIllustrationProviderTest extends TestCase
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

    public function test_generate_illustration_uses_images_api_and_returns_binary(): void
    {
        $imageBinary = 'fake-png-bytes';

        Http::fake([
            'ai.api.cloud.yandex.net/v1/images/generations' => Http::response([
                'data' => [
                    ['b64_json' => base64_encode($imageBinary)],
                ],
            ]),
        ]);

        $result = $this->makeProvider()->generateIllustration($this->sampleInput());

        $this->assertSame($imageBinary, $result);

        Http::assertSent(function ($request): bool {
            $body = $request->data();

            return $request->method() === 'POST'
                && $request->url() === 'https://ai.api.cloud.yandex.net/v1/images/generations'
                && ($body['model'] ?? null) === 'art://b1gtestfolder/aliceai-image-art-3.0'
                && ($body['prompt'] ?? null) === 'A cozy forest scene.'
                && ($body['size'] ?? null) === '1024x1536'
                && ! isset($body['response_format'])
                && $request->hasHeader('Authorization', 'Api-Key test-image-key');
        });
    }

    public function test_generate_illustration_returns_null_for_failed_request(): void
    {
        Http::fake([
            'ai.api.cloud.yandex.net/v1/images/generations' => Http::response([], 429),
        ]);

        $result = $this->makeProvider()->generateIllustration($this->sampleInput());

        $this->assertNull($result);
    }

    public function test_generate_illustration_returns_null_for_invalid_image(): void
    {
        Http::fake([
            'ai.api.cloud.yandex.net/v1/images/generations' => Http::response([
                'data' => [
                    ['b64_json' => 'not-valid-base64!'],
                ],
            ]),
        ]);

        $result = $this->makeProvider()->generateIllustration($this->sampleInput());

        $this->assertNull($result);
    }

    public function test_generate_illustration_returns_null_when_request_throws(): void
    {
        Http::fake(function (): never {
            throw new ConnectionException('Request timed out.');
        });

        $result = $this->makeProvider()->generateIllustration($this->sampleInput());

        $this->assertNull($result);
    }

    public function test_factory_resolves_alice_ai_art_driver(): void
    {
        config()->set('services.ai_image.driver', 'aliceaiart');
        config()->set('services.ai_image.api_key', 'test-key');
        config()->set('services.ai_image.folder_id', 'b1gtestfolder');

        $provider = $this->app->make(IllustrationGenerationProviderFactory::class)->make();

        $this->assertInstanceOf(AliceAiArtIllustrationProvider::class, $provider);
        $this->assertTrue($provider->isConfigured());
    }

    public function test_factory_resolves_alice_ai_art_model_name_as_driver(): void
    {
        config()->set('services.ai_image.driver', 'aliceai-image-art-3.0');
        config()->set('services.ai_image.api_key', 'test-key');
        config()->set('services.ai_image.folder_id', 'b1gtestfolder');

        $provider = $this->app->make(IllustrationGenerationProviderFactory::class)->make();

        $this->assertInstanceOf(AliceAiArtIllustrationProvider::class, $provider);
        $this->assertTrue($provider->isConfigured());
    }

    private function makeProvider(
        string $apiKey = 'test-image-key',
        string $folderId = 'b1gtestfolder',
    ): AliceAiArtIllustrationProvider {
        return new AliceAiArtIllustrationProvider(
            apiKey: $apiKey,
            folderId: $folderId,
            baseUrl: 'https://ai.api.cloud.yandex.net/v1',
            model: 'aliceai-image-art-3.0',
            timeoutSeconds: 30,
            size: '1024x1536',
        );
    }

    private function sampleInput(): IllustrationGenerationInput
    {
        return new IllustrationGenerationInput(
            prompt: 'A cozy forest scene.',
            childName: 'Anna',
            childAge: 5,
            pageNumber: 1,
        );
    }
}
