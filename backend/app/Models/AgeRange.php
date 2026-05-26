<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['label', 'min_age', 'max_age'])]
class AgeRange extends Model
{
    use HasFactory;

    /**
     * Get the templates associated with this age range.
     */
    public function bookTemplates()
    {
        return $this->hasMany(BookTemplate::class);
    }
}
