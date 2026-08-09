<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'child_profile_id',
    'uploaded_photo_id',
    'storage_path',
    'style_bible',
    'appearance_profile',
])]
class GeneratedCharacter extends Model
{
    use HasFactory;

    public function childProfile(): BelongsTo
    {
        return $this->belongsTo(ChildProfile::class);
    }

    public function uploadedPhoto(): BelongsTo
    {
        return $this->belongsTo(UploadedPhoto::class);
    }

    public function bookGenerations(): HasMany
    {
        return $this->hasMany(BookGeneration::class);
    }
}
