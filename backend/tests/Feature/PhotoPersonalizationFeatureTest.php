<?php

namespace Tests\Feature;

use App\Jobs\GenerateBookIllustrationsJob;
use App\Models\BookTemplate;
use App\Models\StoryGoal;
use App\Models\User;
use Database\Seeders\LayoutTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
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
        $this->seed(LayoutTemplateSeeder::class);
        config([
            'services.ai_text.api_key' => '',
            'services.ai_image.api_key' => 'test-image-key',
        ]);

        $goal = StoryGoal::query()->create([
            'name' => 'Делиться игрушками',
            'description' => 'Sharing goal',
        ]);

        BookTemplate::query()->create([
            'title' => 'Делимся',
            'story_goal_id' => $goal->id,
            'description' => 'Sharing template',
            'is_free' => true,
            'template_type' => 'story',
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
            'goal' => 'Делиться игрушками',
            'uploaded_photo_id' => $photoId,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('generation.status', 'processing');
        $response->assertJsonPath('generation.illustration_status', 'queued');

        Queue::assertPushed(GenerateBookIllustrationsJob::class);
    }
}
