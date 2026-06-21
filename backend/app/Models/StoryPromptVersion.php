<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['story_prompt_id', 'version', 'snapshot', 'published_at'])]
class StoryPromptVersion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function storyPrompt(): BelongsTo
    {
        return $this->belongsTo(StoryPrompt::class);
    }
}
