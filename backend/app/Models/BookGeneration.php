<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'book_template_id', 'story_prompt_id', 'child_name', 'child_age', 'child_goal', 'prompt_snapshot', 'status', 'error_message'])]
class BookGeneration extends Model
{
    use HasFactory;

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

    /**
     * Get all pages compiled for this generation.
     */
    public function bookPages(): HasMany
    {
        return $this->hasMany(BookPage::class);
    }
}
