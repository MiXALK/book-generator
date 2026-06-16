<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'child_profile_id',
    'storage_path',
    'mime_type',
    'file_size_bytes',
    'width',
    'height',
    'parental_consent_at',
    'status',
])]
class UploadedPhoto extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'parental_consent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function childProfile(): BelongsTo
    {
        return $this->belongsTo(ChildProfile::class);
    }

    public function bookGenerations(): HasMany
    {
        return $this->hasMany(BookGeneration::class);
    }
}
