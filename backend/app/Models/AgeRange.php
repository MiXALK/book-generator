<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['label', 'min_age', 'max_age'])]
class AgeRange extends Model
{
    use HasFactory;

    /**
     * Get AI prompts associated with this age range.
     */
    public function storyPrompts(): HasMany
    {
        return $this->hasMany(StoryPrompt::class);
    }
}
