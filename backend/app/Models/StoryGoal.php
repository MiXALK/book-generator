<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description'])]
class StoryGoal extends Model
{
    use HasFactory;

    /**
     * Get the templates associated with this goal.
     */
    public function bookTemplates()
    {
        return $this->hasMany(BookTemplate::class);
    }
}
