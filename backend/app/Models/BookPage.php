<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['book_generation_id', 'layout_template_id', 'page_number', 'text', 'image_url'])]
class BookPage extends Model
{
    use HasFactory;

    /**
     * Get the generation that owns this page.
     */
    public function bookGeneration()
    {
        return $this->belongsTo(BookGeneration::class);
    }

    /**
     * Get the layout template used for this page.
     */
    public function layoutTemplate()
    {
        return $this->belongsTo(LayoutTemplate::class);
    }
}
