<?php

namespace Tests\Feature;

use App\Models\BookGeneration;
use App\Models\BookPage;
use App\Models\BookTemplate;
use App\Models\StoryGoal;
use App\Models\UploadedPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivacyComplianceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_own_book_and_storage_assets(): void
    {
        Storage::fake('s3');

        $user = $this->createUser('privacy-book-token');
        $generation = $this->createBookForUser($user);
        $imagePath = "books/{$generation->id}/page-1.svg";

        Storage::disk('s3')->put($imagePath, '<svg></svg>', ['visibility' => 'private']);
        BookPage::query()->where('book_generation_id', $generation->id)->update(['image_url' => $imagePath]);

        $response = $this->withToken('privacy-book-token')->deleteJson("/api/books/{$generation->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('book_generations', ['id' => $generation->id]);
        Storage::disk('s3')->assertMissing($imagePath);
    }

    public function test_user_cannot_delete_another_users_book(): void
    {
        $owner = $this->createUser('privacy-owner-token');
        $other = $this->createUser('privacy-other-token', 'other@example.com');
        $generation = $this->createBookForUser($owner);

        $response = $this->withToken('privacy-other-token')->deleteJson("/api/books/{$generation->id}");

        $response->assertNotFound();
        $this->assertDatabaseHas('book_generations', ['id' => $generation->id]);
    }

    public function test_user_can_delete_account_with_confirmation(): void
    {
        Storage::fake('s3');

        $user = $this->createUser('privacy-delete-token');
        $generation = $this->createBookForUser($user);
        $imagePath = "books/{$generation->id}/page-1.svg";
        $photoPath = "private/users/{$user->id}/photos/test.jpg";

        Storage::disk('s3')->put($imagePath, '<svg></svg>', ['visibility' => 'private']);
        Storage::disk('s3')->put($photoPath, 'photo-bytes', ['visibility' => 'private']);

        UploadedPhoto::query()->create([
            'user_id' => $user->id,
            'storage_path' => $photoPath,
            'mime_type' => 'image/jpeg',
            'file_size_bytes' => 11,
            'width' => 256,
            'height' => 256,
            'parental_consent_at' => now(),
            'status' => 'pending',
        ]);

        $response = $this->withToken('privacy-delete-token')->deleteJson('/api/user', [
            'confirm' => true,
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('book_generations', ['id' => $generation->id]);
        Storage::disk('s3')->assertMissing($imagePath);
        Storage::disk('s3')->assertMissing($photoPath);
    }

    public function test_account_deletion_requires_confirmation(): void
    {
        $this->createUser('privacy-confirm-token');

        $response = $this->withToken('privacy-confirm-token')->deleteJson('/api/user', [
            'confirm' => false,
        ]);

        $response->assertUnprocessable();
        $this->assertDatabaseHas('users', ['api_token' => 'privacy-confirm-token']);
    }

    public function test_privacy_purge_command_deletes_expired_pending_photos(): void
    {
        Storage::fake('s3');

        $user = $this->createUser('privacy-purge-token');
        $photoPath = "private/users/{$user->id}/photos/expired.jpg";
        Storage::disk('s3')->put($photoPath, 'photo-bytes', ['visibility' => 'private']);

        $photo = UploadedPhoto::query()->create([
            'user_id' => $user->id,
            'storage_path' => $photoPath,
            'mime_type' => 'image/jpeg',
            'file_size_bytes' => 11,
            'width' => 256,
            'height' => 256,
            'parental_consent_at' => now()->subDays(2),
            'status' => 'pending',
        ]);
        $photo->created_at = now()->subDays(2);
        $photo->updated_at = now()->subDays(2);
        $photo->saveQuietly();

        Artisan::call('privacy:purge-expired');

        $photo->refresh();
        $this->assertSame('deleted', $photo->status);
        Storage::disk('s3')->assertMissing($photoPath);
    }

    public function test_privacy_purge_command_deletes_expired_failed_generations(): void
    {
        Storage::fake('s3');

        $user = $this->createUser('privacy-failed-token');
        $generation = $this->createBookForUser($user, 'failed');
        $imagePath = "books/{$generation->id}/page-1.svg";

        Storage::disk('s3')->put($imagePath, '<svg></svg>', ['visibility' => 'private']);
        BookPage::query()->where('book_generation_id', $generation->id)->update(['image_url' => $imagePath]);

        $generation->created_at = now()->subDays(10);
        $generation->updated_at = now()->subDays(10);
        $generation->saveQuietly();

        Artisan::call('privacy:purge-expired');

        $this->assertDatabaseMissing('book_generations', ['id' => $generation->id]);
        Storage::disk('s3')->assertMissing($imagePath);
    }

    private function createUser(string $token, string $email = 'privacy@example.com'): User
    {
        return User::query()->create([
            'name' => 'Privacy User',
            'email' => $email,
            'password' => bcrypt('password'),
            'plan' => 'free',
            'subscription_status' => 'inactive',
            'api_token' => $token,
            'api_token_expires_at' => now()->addDay(),
        ]);
    }

    private function createBookForUser(User $user, string $status = 'completed'): BookGeneration
    {
        $goal = StoryGoal::query()->create([
            'name' => 'Делиться игрушками',
            'description' => 'Test goal',
        ]);

        $template = BookTemplate::query()->create([
            'title' => 'Тестовая сказка',
            'story_goal_id' => $goal->id,
            'description' => 'Test template',
            'is_free' => true,
            'template_type' => 'story',
            'is_active' => true,
        ]);

        $generation = BookGeneration::query()->create([
            'user_id' => $user->id,
            'book_template_id' => $template->id,
            'child_name' => 'Маша',
            'child_age' => 5,
            'child_gender' => 'girl',
            'child_goal' => 'Делиться игрушками',
            'status' => $status,
        ]);

        BookPage::query()->create([
            'book_generation_id' => $generation->id,
            'page_number' => 1,
            'text' => 'Тестовая страница',
            'image_url' => null,
        ]);

        return $generation;
    }
}
