<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'description'])]
class StoryGoal extends Model
{
    use HasFactory;

    /**
     * Get the book template linked to this goal.
     */
    public function bookTemplate(): HasOne
    {
        return $this->hasOne(BookTemplate::class);
    }

    /**
     * Get AI prompts associated with this goal.
     */
    public function storyPrompts(): HasMany
    {
        return $this->hasMany(StoryPrompt::class);
    }
}
