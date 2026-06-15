<?php

namespace Tests\Feature;

use App\Models\BookTemplate;
use App\Models\StoryGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionAccessFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_marks_paid_goals_as_locked_for_free_users(): void
    {
        $goal = StoryGoal::query()->create([
            'name' => 'Управлять эмоциями',
            'description' => 'Premium goal',
        ]);

        BookTemplate::query()->create([
            'title' => 'Спокойные чувства',
            'story_goal_id' => $goal->id,
            'description' => 'Premium template',
            'is_free' => false,
            'template_type' => 'story',
            'is_active' => true,
        ]);

        $user = User::query()->create([
            'name' => 'Free User',
            'email' => 'free@example.com',
            'password' => bcrypt('password'),
            'plan' => 'free',
            'subscription_status' => 'inactive',
            'api_token' => 'free-user-token',
            'api_token_expires_at' => now()->addDay(),
        ]);

        $response = $this->withToken('free-user-token')->getJson('/api/templates/catalog');

        $response->assertOk();
        $response->assertJsonPath('monthly_limit', 3);
        $response->assertJsonPath('has_paid_access', false);
        $response->assertJsonFragment([
            'name' => 'Управлять эмоциями',
            'is_locked' => true,
        ]);
    }

    public function test_free_user_cannot_generate_paid_template(): void
    {
        $goal = StoryGoal::query()->create([
            'name' => 'Дружить и общаться',
            'description' => 'Premium goal',
        ]);

        BookTemplate::query()->create([
            'title' => 'Дружная компания',
            'story_goal_id' => $goal->id,
            'description' => 'Premium template',
            'is_free' => false,
            'template_type' => 'story',
            'is_active' => true,
        ]);

        User::query()->create([
            'name' => 'Free User',
            'email' => 'free2@example.com',
            'password' => bcrypt('password'),
            'plan' => 'free',
            'subscription_status' => 'inactive',
            'api_token' => 'free-user-token-2',
            'api_token_expires_at' => now()->addDay(),
        ]);

        $response = $this->withToken('free-user-token-2')->postJson('/api/books/generate', [
            'child_name' => 'Маша',
            'age' => 5,
            'goal' => 'Дружить и общаться',
        ]);

        $response->assertForbidden();
    }
}
