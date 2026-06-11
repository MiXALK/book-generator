<?php

namespace App\Models;

use App\Enums\AgeRange;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'prompt_text',
    'language',
    'age_range',
    'story_goal_id',
    'quality_score',
    'rating_count',
    'usage_count',
    'is_active',
])]
class StoryPrompt extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'age_range' => AgeRange::class,
        ];
    }

    /**
     * Get the story goal associated with this prompt.
     */
    public function storyGoal(): BelongsTo
    {
        return $this->belongsTo(StoryGoal::class);
    }

    /**
     * Get all ratings for this prompt.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(StoryPromptRating::class);
    }

    /**
     * Get all generations that used this prompt.
     */
    public function bookGenerations(): HasMany
    {
        return $this->hasMany(BookGeneration::class);
    }
}
