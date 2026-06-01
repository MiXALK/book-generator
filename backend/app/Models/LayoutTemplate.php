<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'title', 'category', 'ratio_profile', 'text_position', 'sort_order', 'is_active'])]
class LayoutTemplate extends Model
{
    use HasFactory;

    /**
     * Get all pages rendered with this layout.
     */
    public function bookPages(): HasMany
    {
        return $this->hasMany(BookPage::class);
    }
}
