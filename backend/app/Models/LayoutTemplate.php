<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $version
 * @property PublicationStatus $publication_status
 */
#[Fillable(['key', 'title', 'category', 'ratio_profile', 'text_position', 'sort_order', 'is_active', 'publication_status', 'version'])]
class LayoutTemplate extends Model
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
     * Get all pages rendered with this layout.
     */
    public function bookPages(): HasMany
    {
        return $this->hasMany(BookPage::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LayoutTemplateVersion::class);
    }
}
