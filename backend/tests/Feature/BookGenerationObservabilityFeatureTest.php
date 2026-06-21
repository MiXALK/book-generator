<?php

namespace Tests\Feature;

use App\Exceptions\TransientGenerationException;
use App\Jobs\GenerateBookIllustrationsJob;
use App\Models\BookGeneration;
use App\Models\BookPage;
use App\Models\BookTemplate;
use App\Models\ChildProfile;
use App\Models\GeneratedCharacter;
use App\Models\StoryGoal;
use App\Models\UploadedPhoto;
use App\Models\User;
use App\Notifications\BookReadyNotification;
use App\Services\Ai\Contracts\IllustrationGenerationProviderInterface;
use App\Services\IllustrationGenerationService;
use Database\Seeders\LayoutTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class BookGenerationObservabilityFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_generation_persists_correlation_id_and_latency_metrics(): void
    {
        Storage::fake('s3');
        $this->seed(LayoutTemplateSeeder::class);
        config(['services.ai_text.api_key' => '']);

        $this->createFreeUser('obs-user-token');
        $this->createGoalAndTemplate();

        $response = $this->withToken('obs-user-token')->postJson('/api/books/generate', [
            'child_name' => 'Маша',
            'age' => 5,
            'goal' => 'Делиться игрушками',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('generation.status', 'completed');

        $generation = BookGeneration::query()->first();
        $this->assertNotNull($generation);
        $this->assertNotNull($generation->correlation_id);
        $this->assertTrue(Str::isUuid($generation->correlation_id));
        $this->assertNotNull($generation->layout_duration_ms);
        $this->assertGreaterThanOrEqual(0, $generation->layout_duration_ms);
    }

    public function test_book_ready_notification_is_sent_when_generation_completes(): void
    {
        Notification::fake();
        Storage::fake('s3');
        $this->seed(LayoutTemplateSeeder::class);
        config(['services.ai_text.api_key' => '']);

        $this->createFreeUser('notify-user-token');
        $this->createGoalAndTemplate();

        $response = $this->withToken('notify-user-token')->postJson('/api/books/generate', [
            'child_name' => 'Маша',
            'age' => 5,
            'goal' => 'Делиться игрушками',
        ]);

        $response->assertCreated();

        $user = User::query()->where('api_token', 'notify-user-token')->first();
        $this->assertNotNull($user);

        Notification::assertSentTo($user, BookReadyNotification::class);
    }

    public function test_illustration_job_uses_configured_retry_policy(): void
    {
        config([
            'services.observability.job_max_attempts' => 4,
            'services.observability.job_backoff_seconds' => [10, 20, 40],
        ]);

        $job = new GenerateBookIllustrationsJob(42);

        $this->assertSame(4, $job->tries);
        $this->assertSame([10, 20, 40], $job->backoff);
        $this->assertSame(['book-generation', 'generation:42'], $job->tags());
    }

    public function test_transient_illustration_failures_are_rethrown_for_queue_retry(): void
    {
        Storage::fake('s3');

        $user = User::query()->create([
            'name' => 'Paid User',
            'email' => 'retry@example.com',
            'password' => bcrypt('password'),
            'plan' => 'paid',
            'subscription_status' => 'active',
            'api_token' => 'retry-token',
            'api_token_expires_at' => now()->addDay(),
        ]);

        $profile = ChildProfile::query()->create([
            'user_id' => $user->id,
            'child_name' => 'Маша',
            'child_age' => 5,
        ]);

        $photo = UploadedPhoto::query()->create([
            'user_id' => $user->id,
            'child_profile_id' => $profile->id,
            'storage_path' => 'photos/test.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'width' => 100,
            'height' => 100,
            'parental_consent_at' => now(),
            'status' => 'pending',
        ]);

        $character = GeneratedCharacter::query()->create([
            'child_profile_id' => $profile->id,
            'uploaded_photo_id' => $photo->id,
            'style_bible' => 'test bible',
        ]);

        $goal = StoryGoal::query()->create([
            'name' => 'Делиться игрушками',
            'description' => 'Sharing goal',
        ]);

        $template = BookTemplate::query()->create([
            'title' => 'Делимся',
            'story_goal_id' => $goal->id,
            'description' => 'Sharing template',
            'is_free' => true,
            'template_type' => 'story',
            'is_active' => true,
        ]);

        $generation = BookGeneration::query()->create([
            'user_id' => $user->id,
            'book_template_id' => $template->id,
            'child_profile_id' => $profile->id,
            'uploaded_photo_id' => $photo->id,
            'generated_character_id' => $character->id,
            'child_name' => 'Маша',
            'child_age' => 5,
            'child_goal' => 'Делиться игрушками',
            'status' => 'processing',
            'illustration_status' => 'queued',
            'correlation_id' => (string) Str::uuid(),
        ]);

        BookPage::query()->create([
            'book_generation_id' => $generation->id,
            'page_number' => 1,
            'text' => 'Тестовая страница',
            'image_url' => 'generations/'.$generation->id.'/page-1.svg',
        ]);

        $provider = Mockery::mock(IllustrationGenerationProviderInterface::class);
        $provider->shouldReceive('isConfigured')->andReturn(true);
        $provider->shouldReceive('generateIllustration')->once()->andReturn(null);
        $this->app->instance(IllustrationGenerationProviderInterface::class, $provider);

        Log::spy();

        try {
            $this->app->make(IllustrationGenerationService::class)->runForGeneration($generation->id);
            $this->fail('Expected TransientGenerationException was not thrown.');
        } catch (TransientGenerationException) {
            // expected
        }

        Log::shouldHaveReceived('log')
            ->withArgs(function (string $level, string $message, array $context): bool {
                return $level === 'warning'
                    && str_contains($message, 'queue will retry')
                    && isset($context['correlation_id'], $context['generation_id'], $context['stage']);
            });
    }

    public function test_illustration_job_marks_generation_failed_after_retries_exhausted(): void
    {
        $user = User::query()->create([
            'name' => 'Paid User',
            'email' => 'failed@example.com',
            'password' => bcrypt('password'),
            'plan' => 'paid',
            'subscription_status' => 'active',
            'api_token' => 'failed-token',
            'api_token_expires_at' => now()->addDay(),
        ]);

        $goal = StoryGoal::query()->create([
            'name' => 'Делиться игрушками',
            'description' => 'Sharing goal',
        ]);

        $template = BookTemplate::query()->create([
            'title' => 'Делимся',
            'story_goal_id' => $goal->id,
            'description' => 'Sharing template',
            'is_free' => true,
            'template_type' => 'story',
            'is_active' => true,
        ]);

        $generation = BookGeneration::query()->create([
            'user_id' => $user->id,
            'book_template_id' => $template->id,
            'child_name' => 'Маша',
            'child_age' => 5,
            'child_goal' => 'Делиться игрушками',
            'status' => 'processing',
            'illustration_status' => 'processing',
            'correlation_id' => (string) Str::uuid(),
        ]);

        $job = new GenerateBookIllustrationsJob($generation->id);
        $job->failed(new TransientGenerationException('Provider unavailable'));

        $generation->refresh();
        $this->assertSame('failed', $generation->status);
        $this->assertSame('failed', $generation->illustration_status);
        $this->assertSame('Provider unavailable', $generation->error_message);
    }

    private function createFreeUser(string $token): void
    {
        User::query()->create([
            'name' => 'Free User',
            'email' => $token.'@example.com',
            'password' => bcrypt('password'),
            'plan' => 'free',
            'subscription_status' => 'inactive',
            'api_token' => $token,
            'api_token_expires_at' => now()->addDay(),
        ]);
    }

    private function createGoalAndTemplate(): void
    {
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
    }
}
