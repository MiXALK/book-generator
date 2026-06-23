<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property UserRole $role
 */
#[Fillable(['name', 'email', 'password', 'google_id', 'avatar_url', 'plan', 'subscription_status', 'stripe_customer_id', 'stripe_subscription_id', 'language', 'role', 'api_token', 'api_token_expires_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Get all book generations requested by this user.
     */
    public function bookGenerations(): HasMany
    {
        return $this->hasMany(BookGeneration::class);
    }

    public function childProfiles(): HasMany
    {
        return $this->hasMany(ChildProfile::class);
    }

    public function uploadedPhotos(): HasMany
    {
        return $this->hasMany(UploadedPhoto::class);
    }

    /**
     * Get prompt ratings created by this user.
     */
    public function storyPromptRatings(): HasMany
    {
        return $this->hasMany(StoryPromptRating::class);
    }
}
