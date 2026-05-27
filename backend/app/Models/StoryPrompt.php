<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title',
    'prompt_text',
    'language',
    'age_range_id',
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
     * Get the age range associated with this prompt.
     */
    public function ageRange()
    {
        return $this->belongsTo(AgeRange::class);
    }

    /**
     * Get the story goal associated with this prompt.
     */
    public function storyGoal()
    {
        return $this->belongsTo(StoryGoal::class);
    }

    /**
     * Get all ratings for this prompt.
     */
    public function ratings()
    {
        return $this->hasMany(StoryPromptRating::class);
    }

    /**
     * Get all generations that used this prompt.
     */
    public function bookGenerations()
    {
        return $this->hasMany(BookGeneration::class);
    }
}
