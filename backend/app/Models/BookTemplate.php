<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'description', 'is_free', 'template_type', 'is_active'])]
class BookTemplate extends Model
{
    use HasFactory;

    /**
     * Get all scenes for this template.
     */
    public function templateScenes()
    {
        return $this->hasMany(TemplateScene::class);
    }

    /**
     * Get all generated books from this template.
     */
    public function bookGenerations()
    {
        return $this->hasMany(BookGeneration::class);
    }
}
