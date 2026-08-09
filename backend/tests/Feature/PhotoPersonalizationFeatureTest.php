<?php

namespace Tests\Feature;

use App\Jobs\AssembleBookLayoutJob;
use App\Jobs\GenerateBookIllustrationsJob;
use App\Jobs\GenerateBookPageIllustrationJob;
use App\Jobs\GenerateBookTextJob;
use App\Models\BookGeneration;
use App\Models\BookTemplate;
use App\Models\ChildProfile;
use App\Models\StoryGoal;
use App\Models\UploadedPhoto;
use App\Models\User;
use App\Services\BookGenerationService;
use App\Services\IllustrationGenerationService;
use Database\Seeders\LayoutTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\TestImageFactory;
use Tests\TestCase;

class PhotoPersonalizationFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.book_photo.min_width' => 8,
            'services.book_photo.min_height' => 8,
        ]);

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_free_user_cannot_upload_photo(): void
    {
        Storage::fake('s3');

        User::query()->create([
            'name' => 'Free User',
            'email' => 'free-photo@example.com',
            'password' => bcrypt('password'),
            'plan' => 'free',
            'subscription_status' => 'inactive',
            'api_token' => 'free-photo-token',
            'api_token_expires_at' => now()->addDay(),
        ]);

        $file = TestImageFactory::jpeg('child.jpg');

        $response = $this->withToken('free-photo-token')->post('/api/photos/upload', [
            'photo' => $file,
            'parental_consent' => '1',
        ]);

        $response->assertForbidden();
    }

    public function test_paid_user_can_upload_photo_with_parental_consent(): void
    {
        Storage::fake('s3');

        User::query()->create([
            'name' => 'Paid User',
            'email' => 'paid-photo@example.com',
            'password' => bcrypt('password'),
            'plan' => 'paid',
            'subscription_status' => 'active',
            'api_token' => 'paid-photo-token',
            'api_token_expires_at' => now()->addDay(),
        ]);

        $file = TestImageFactory::jpeg('child.jpg');

        $response = $this->withToken('paid-photo-token')->post('/api/photos/upload', [
            'photo' => $file,
            'parental_consent' => '1',
            'child_name' => 'Маша',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('uploaded_photo.width', 10);
        $this->assertDatabaseHas('uploaded_photos', [
            'status' => 'pending',
        ]);
    }

    public function test_photo_upload_requires_parental_consent(): void
    {
        Storage::fake('s3');

        User::query()->create([
            'name' => 'Paid User',
            'email' => 'paid-photo2@example.com',
            'password' => bcrypt('password'),
            'plan' => 'paid',
            'subscription_status' => 'active',
            'api_token' => 'paid-photo-token-2',
            'api_token_expires_at' => now()->addDay(),
        ]);

        $file = TestImageFactory::jpeg('child.jpg');

        $response = $this->withToken('paid-photo-token-2')->post('/api/photos/upload', [
            'photo' => $file,
            'parental_consent' => '0',
        ], [
            'Accept' => 'application/json',
        ]);

        $response->assertUnprocessable();
    }

    public function test_paid_generation_with_photo_queues_illustration_job_when_provider_configured(): void
    {
        Storage::fake('s3');
        Queue::fake();
        Http::fake(function ($request) {
            $content = $request->data()['messages'][0]['content'] ?? null;
            $result = is_array($content)
                ? ['appearance' => 'oval face, green eyes, long curly black hair']
                : ['story' => 'Маша нашла игрушку. Она поделилась ею с другом.'];

            return Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode($result),
                    ],
                ]],
            ]);
        });
        $this->seed(LayoutTemplateSeeder::class);
        config([
            'services.ai_text.api_key' => 'test-qwen-key',
            'services.ai_image.api_key' => 'test-image-key',
            'services.ai_image.folder_id' => 'b1gtestfolder',
        ]);

        $goal = StoryGoal::query()->create([
            'name' => 'Делиться игрушками',
            'description' => 'Sharing goal',
        ]);

        BookTemplate::query()->create([
            'title' => 'Делимся',
            'story_goal_id' => $goal->id,
            'is_free' => true,
            'is_active' => true,
        ]);

        User::query()->create([
            'name' => 'Paid User',
            'email' => 'paid-gen@example.com',
            'password' => bcrypt('password'),
            'plan' => 'paid',
            'subscription_status' => 'active',
            'api_token' => 'paid-gen-token',
            'api_token_expires_at' => now()->addDay(),
        ]);

        $upload = $this->withToken('paid-gen-token')->post('/api/photos/upload', [
            'photo' => TestImageFactory::jpeg('child.jpg'),
            'parental_consent' => '1',
            'child_name' => 'Маша',
        ]);

        $photoId = $upload->json('uploaded_photo.id');

        $response = $this->withToken('paid-gen-token')->postJson('/api/books/generate', [
            'child_name' => 'Маша',
            'age' => 5,
            'child_gender' => 'girl',
            'goal' => 'Делиться игрушками',
            'uploaded_photo_id' => $photoId,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('generation.status', 'processing');
        $response->assertJsonPath('generation.illustration_status', 'queued');

        Queue::assertPushed(GenerateBookTextJob::class);

        Queue::pushed(GenerateBookTextJob::class)->each(
            fn (GenerateBookTextJob $job) => $job->handle(app(BookGenerationService::class)),
        );

        Queue::pushed(AssembleBookLayoutJob::class)->each(
            fn (AssembleBookLayoutJob $job) => $job->handle(app(BookGenerationService::class)),
        );

        Queue::assertPushed(GenerateBookIllustrationsJob::class);

        Queue::pushed(GenerateBookIllustrationsJob::class)->each(
            fn (GenerateBookIllustrationsJob $job) => $job->handle(app(IllustrationGenerationService::class)),
        );

        Queue::assertPushed(GenerateBookPageIllustrationJob::class);
        $this->assertDatabaseHas('generated_characters', [
            'appearance_profile' => 'oval face, green eyes, long curly black hair',
        ]);
        Http::assertSentCount(2);

        Queue::pushed(GenerateBookIllustrationsJob::class)->each(
            fn (GenerateBookIllustrationsJob $job) => $job->handle(app(IllustrationGenerationService::class)),
        );

        Http::assertSentCount(2);

        $profile = ChildProfile::query()->firstOrFail();
        $replacementPath = "private/users/{$profile->user_id}/photos/replacement.jpg";
        $replacementBinary = "\xFF\xD8\xFFreplacement-image";
        Storage::disk('s3')->put($replacementPath, $replacementBinary);
        $replacementPhoto = UploadedPhoto::query()->create([
            'user_id' => $profile->user_id,
            'child_profile_id' => $profile->id,
            'storage_path' => $replacementPath,
            'mime_type' => 'image/jpeg',
            'file_size_bytes' => strlen($replacementBinary),
            'width' => 10,
            'height' => 10,
            'parental_consent_at' => now(),
            'status' => 'pending',
        ]);
        $service = app(IllustrationGenerationService::class);
        $character = $service->resolveOrCreateCharacter(
            $profile,
            'Маша',
            5,
            'girl',
            $replacementPhoto,
        );

        $this->assertNull($character->appearance_profile);

        $generation = BookGeneration::query()->firstOrFail();
        $generation->update(['uploaded_photo_id' => $replacementPhoto->id]);
        $service->runForGeneration($generation->id);

        $this->assertSame(
            'oval face, green eyes, long curly black hair',
            $character->fresh()?->appearance_profile,
        );
        Http::assertSentCount(3);
    }

    public function test_free_generation_without_upload_queues_illustrations_from_default_character(): void
    {
        Storage::fake('s3');
        Queue::fake();
        $this->seed(LayoutTemplateSeeder::class);
        config([
            'services.ai_text.api_key' => '',
            'services.ai_image.api_key' => 'test-image-key',
            'services.ai_image.folder_id' => 'b1gtestfolder',
        ]);

        $goal = StoryGoal::query()->create([
            'name' => 'Делиться игрушками',
            'description' => 'Sharing goal',
        ]);

        BookTemplate::query()->create([
            'title' => 'Делимся',
            'story_goal_id' => $goal->id,
            'is_free' => true,
            'is_active' => true,
        ]);

        User::query()->create([
            'name' => 'Free User',
            'email' => 'free-illustrated@example.com',
            'password' => bcrypt('password'),
            'plan' => 'free',
            'subscription_status' => 'inactive',
            'api_token' => 'free-illustrated-token',
            'api_token_expires_at' => now()->addDay(),
        ]);

        $response = $this->withToken('free-illustrated-token')->postJson('/api/books/generate', [
            'child_name' => 'Маша',
            'age' => 5,
            'child_gender' => 'girl',
            'goal' => 'Делиться игрушками',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('generation.status', 'processing');
        $response->assertJsonPath('generation.illustration_status', 'queued');

        $this->assertDatabaseHas('child_profiles', [
            'child_name' => 'Маша',
            'child_gender' => 'girl',
        ]);
        $this->assertDatabaseHas('book_generations', [
            'child_name' => 'Маша',
            'child_gender' => 'girl',
            'uploaded_photo_id' => null,
        ]);
        $this->assertDatabaseHas('generated_characters', [
            'uploaded_photo_id' => null,
        ]);

        Queue::pushed(GenerateBookTextJob::class)->each(
            fn (GenerateBookTextJob $job) => $job->handle(app(BookGenerationService::class)),
        );

        Queue::pushed(AssembleBookLayoutJob::class)->each(
            fn (AssembleBookLayoutJob $job) => $job->handle(app(BookGenerationService::class)),
        );

        Queue::assertPushed(GenerateBookIllustrationsJob::class);

        Queue::pushed(GenerateBookIllustrationsJob::class)->each(
            fn (GenerateBookIllustrationsJob $job) => $job->handle(app(IllustrationGenerationService::class)),
        );

        Queue::assertPushed(GenerateBookPageIllustrationJob::class);
    }
}
