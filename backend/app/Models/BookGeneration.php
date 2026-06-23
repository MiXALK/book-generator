<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read User|null $user
 */
#[Fillable([
    'user_id',
    'book_template_id',
    'story_prompt_id',
    'child_profile_id',
    'uploaded_photo_id',
    'generated_character_id',
    'child_name',
    'child_age',
    'child_goal',
    'prompt_snapshot',
    'book_template_snapshot',
    'status',
    'illustration_status',
    'error_message',
    'correlation_id',
    'text_duration_ms',
    'layout_duration_ms',
    'image_duration_ms',
])]
class BookGeneration extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'book_template_snapshot' => 'array',
        ];
    }

    /**
     * Get the user who requested this generation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the source template for this generation.
     */
    public function bookTemplate(): BelongsTo
    {
        return $this->belongsTo(BookTemplate::class);
    }

    /**
     * Get the story prompt used for this generation.
     */
    public function storyPrompt(): BelongsTo
    {
        return $this->belongsTo(StoryPrompt::class);
    }

    public function childProfile(): BelongsTo
    {
        return $this->belongsTo(ChildProfile::class);
    }

    public function uploadedPhoto(): BelongsTo
    {
        return $this->belongsTo(UploadedPhoto::class);
    }

    public function generatedCharacter(): BelongsTo
    {
        return $this->belongsTo(GeneratedCharacter::class);
    }

    /**
     * Get all pages compiled for this generation.
     */
    public function bookPages(): HasMany
    {
        return $this->hasMany(BookPage::class);
    }
}
