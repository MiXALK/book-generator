<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['book_template_id', 'scene_number', 'scene_instruction', 'image_prompt_template'])]
class TemplateScene extends Model
{
    use HasFactory;

    /**
     * Get the template for this scene.
     */
    public function bookTemplate()
    {
        return $this->belongsTo(BookTemplate::class);
    }
}
