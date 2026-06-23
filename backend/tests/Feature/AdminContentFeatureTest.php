<?php

namespace Tests\Feature;

use App\Enums\PublicationStatus;
use App\Enums\UserRole;
use App\Models\BookTemplate;
use App\Models\LayoutTemplate;
use App\Models\StoryGoal;
use App\Models\StoryPrompt;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContentFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(string $token = 'admin-token'): User
    {
        return User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'plan' => 'free',
            'subscription_status' => 'inactive',
            'role' => UserRole::Admin,
            'api_token' => $token,
            'api_token_expires_at' => now()->addDay(),
        ]);
    }

    private function createUser(string $token = 'user-token'): User
    {
        return User::query()->create([
            'name' => 'Regular User',
            'email' => 'user@example.com',
            'password' => bcrypt('password'),
            'plan' => 'free',
            'subscription_status' => 'inactive',
            'role' => UserRole::User,
            'api_token' => $token,
            'api_token_expires_at' => now()->addDay(),
        ]);
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $this->createUser();

        $response = $this->withToken('user-token')->getJson('/api/admin/goals');

        $response->assertForbidden();
    }

    public function test_admin_can_crud_goals_and_templates(): void
    {
        $this->createAdmin();

        $goalResponse = $this->withToken('admin-token')->postJson('/api/admin/goals', [
            'name' => 'Test Goal',
            'description' => 'Goal description',
        ]);

        $goalResponse->assertCreated();
        $goalId = $goalResponse->json('item.id');

        $templateResponse = $this->withToken('admin-token')->postJson('/api/admin/templates', [
            'title' => 'Test Template',
            'description' => 'Template description',
            'is_free' => true,
            'template_type' => 'story',
            'story_goal_id' => $goalId,
        ]);

        $templateResponse->assertCreated();
        $templateId = $templateResponse->json('item.id');
        $this->assertSame('draft', $templateResponse->json('item.publication_status'));

        $publishResponse = $this->withToken('admin-token')->postJson("/api/admin/templates/{$templateId}/publish");
        $publishResponse->assertOk();
        $this->assertSame('published', $publishResponse->json('item.publication_status'));
        $this->assertTrue($publishResponse->json('item.is_active'));
    }

    public function test_prompt_publish_requires_quality_threshold(): void
    {
        $this->createAdmin();

        $promptResponse = $this->withToken('admin-token')->postJson('/api/admin/prompts', [
            'title' => 'Test Prompt',
            'prompt_text' => 'Write a story about {name}.',
            'language' => 'ru',
        ]);

        $promptResponse->assertCreated();
        $promptId = $promptResponse->json('item.id');

        $publishResponse = $this->withToken('admin-token')->postJson("/api/admin/prompts/{$promptId}/publish");
        $publishResponse->assertStatus(422);

        $this->withToken('admin-token')->postJson("/api/admin/prompts/{$promptId}/ratings", [
            'rating' => 5,
            'notes' => 'Excellent prompt',
        ])->assertCreated();

        $publishResponse = $this->withToken('admin-token')->postJson("/api/admin/prompts/{$promptId}/publish");
        $publishResponse->assertOk();
        $this->assertSame('published', $publishResponse->json('item.publication_status'));
    }

    public function test_review_queue_lists_pending_items(): void
    {
        $this->createAdmin();
        $this->seed(DatabaseSeeder::class);

        $template = BookTemplate::query()->firstOrFail();
        $template->update(['publication_status' => PublicationStatus::PendingReview]);

        $response = $this->withToken('admin-token')->getJson('/api/admin/review-queue');

        $response->assertOk();
        $response->assertJsonFragment([
            'type' => 'book_template',
            'id' => $template->id,
        ]);
    }

    public function test_unpublished_templates_are_hidden_from_catalog(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->createUser('catalog-token');

        BookTemplate::query()->update([
            'publication_status' => PublicationStatus::Draft,
            'is_active' => false,
        ]);

        $response = $this->withToken('catalog-token')->getJson('/api/templates/catalog');

        $response->assertOk();
        $response->assertJsonCount(0, 'goals');
    }

    public function test_template_preview_returns_sample_pages(): void
    {
        $this->createAdmin();
        $this->seed(DatabaseSeeder::class);

        $template = BookTemplate::query()->firstOrFail();

        $response = $this->withToken('admin-token')->getJson("/api/admin/templates/{$template->id}/preview");

        $response->assertOk();
        $response->assertJsonPath('type', 'book_template');
        $response->assertJsonCount(3, 'pages');
    }

    public function test_layout_version_snapshot_on_publish(): void
    {
        $this->createAdmin();

        $layoutResponse = $this->withToken('admin-token')->postJson('/api/admin/layouts', [
            'key' => 'test_layout',
            'title' => 'Test Layout',
            'category' => 'content',
            'ratio_profile' => '80_20',
            'text_position' => 'bottom',
            'sort_order' => 99,
        ]);

        $layoutResponse->assertCreated();
        $layoutId = $layoutResponse->json('item.id');

        $publishResponse = $this->withToken('admin-token')->postJson("/api/admin/layouts/{$layoutId}/publish");
        $publishResponse->assertOk();

        $layout = LayoutTemplate::query()->findOrFail($layoutId);
        $this->assertDatabaseHas('layout_template_versions', [
            'layout_template_id' => $layoutId,
            'version' => $layout->version,
        ]);
    }
}
