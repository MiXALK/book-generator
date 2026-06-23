<?php

namespace Tests\Feature;

use App\Models\BookGeneration;
use App\Models\BookTemplate;
use App\Models\StoryGoal;
use App\Models\User;
use App\Services\TemplateCatalogCacheService;
use Database\Seeders\LayoutTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class Stage10ScalingFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function test_idempotency_key_replays_existing_generation_without_duplicate_work(): void
    {
        Cache::flush();
        $this->seed(LayoutTemplateSeeder::class);
        config(['services.ai_text.api_key' => '']);

        $this->createUser('stage10-idempotency-token');
        $this->createGoalAndTemplate();

        $payload = [
            'child_name' => 'Маша',
            'age' => 5,
            'goal' => 'Делиться игрушками',
        ];

        $first = $this->withToken('stage10-idempotency-token')
            ->withHeader('Idempotency-Key', 'same-key-123')
            ->postJson('/api/books/generate', $payload);

        $first->assertCreated();

        $second = $this->withToken('stage10-idempotency-token')
            ->withHeader('Idempotency-Key', 'same-key-123')
            ->postJson('/api/books/generate', $payload);

        $second->assertOk();
        $second->assertJsonPath('generation.id', $first->json('generation.id'));
        $this->assertSame(1, BookGeneration::query()->count());
    }

    public function test_catalog_is_cached_until_publication_bumps_version(): void
    {
        Cache::flush();
        config(['services.scaling.catalog_cache_ttl_seconds' => 3600]);
        $this->createGoalAndTemplate();

        $this->createUser('stage10-catalog-token');

        $this->withToken('stage10-catalog-token')->getJson('/api/templates/catalog')->assertOk();
        $this->assertTrue(Cache::has('catalog:goals:v1:free'));

        app(TemplateCatalogCacheService::class)->bumpVersion();

        $this->withToken('stage10-catalog-token')->getJson('/api/templates/catalog')->assertOk();

        $version = app(TemplateCatalogCacheService::class)->version();
        $this->assertSame(2, $version);
        $this->assertNotNull(Cache::get('catalog:goals:v2:free'));
    }

    public function test_generation_persists_cost_breakdown_after_layout(): void
    {
        Cache::flush();
        $this->seed(LayoutTemplateSeeder::class);
        config(['services.ai_text.api_key' => '']);

        $this->createUser('stage10-cost-token');
        $this->createGoalAndTemplate();

        $response = $this->withToken('stage10-cost-token')->postJson('/api/books/generate', [
            'child_name' => 'Маша',
            'age' => 5,
            'goal' => 'Делиться игрушками',
        ]);

        $response->assertCreated();

        $generation = BookGeneration::query()->first();
        $this->assertNotNull($generation);
        $this->assertNotNull($generation->input_fingerprint);
        $this->assertNotNull($generation->story_text);
        $this->assertSame('completed', $generation->status);
        $this->assertTrue(
            is_array($generation->cost_breakdown) || $generation->cost_breakdown === null,
        );
    }

    private function createUser(string $token): User
    {
        return User::query()->create([
            'name' => 'Stage 10 User',
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
        $goal = StoryGoal::query()->firstOrCreate(
            ['name' => 'Делиться игрушками'],
            ['description' => 'Free goal'],
        );

        BookTemplate::query()->firstOrCreate(
            ['story_goal_id' => $goal->id],
            [
                'title' => 'Щедрый друг',
                'description' => 'Free template',
                'is_free' => true,
                'template_type' => 'story',
                'is_active' => true,
            ],
        );
    }
}
