<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $version
 * @property PublicationStatus $publication_status
 * @property-read string|null $description
 */
#[Fillable(['title', 'is_free', 'is_active', 'publication_status', 'version', 'story_goal_id'])]
class BookTemplate extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = [
        'description',
    ];

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
     * Borrow description from the linked StoryGoal (single source of truth).
     *
     * @return Attribute<string|null, never>
     */
    protected function description(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                $goal = $this->storyGoal;

                return $goal instanceof StoryGoal ? $goal->description : null;
            },
        );
    }

    /**
     * Get the development goal this template is linked to.
     *
     * @return BelongsTo<StoryGoal, $this>
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
