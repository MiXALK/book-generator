<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['story_prompt_id', 'user_id', 'rating', 'notes'])]
class StoryPromptRating extends Model
{
    use HasFactory;

    /**
     * Get the prompt for this rating.
     */
    public function storyPrompt()
    {
        return $this->belongsTo(StoryPrompt::class);
    }

    /**
     * Get the user who left this rating.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
