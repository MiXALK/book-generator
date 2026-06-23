<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $version
 * @property PublicationStatus $publication_status
 */
#[Fillable(['title', 'description', 'is_free', 'template_type', 'is_active', 'publication_status', 'version', 'story_goal_id'])]
class BookTemplate extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publication_status' => PublicationStatus::class,
        ];
    }

    /**
     * Get the development goal this template is linked to.
     */
    public function storyGoal(): BelongsTo
    {
        return $this->belongsTo(StoryGoal::class);
    }

    /**
     * Get all generated books from this template.
     */
    public function bookGenerations(): HasMany
    {
        return $this->hasMany(BookGeneration::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(BookTemplateVersion::class);
    }
}
