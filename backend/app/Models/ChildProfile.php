<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'child_name', 'child_age', 'child_gender'])]
class ChildProfile extends Model
{
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function uploadedPhotos(): HasMany
    {
        return $this->hasMany(UploadedPhoto::class);
    }

    public function generatedCharacter(): HasOne
    {
        return $this->hasOne(GeneratedCharacter::class);
    }

    public function bookGenerations(): HasMany
    {
        return $this->hasMany(BookGeneration::class);
    }
}
