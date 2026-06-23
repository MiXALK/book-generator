<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['layout_template_id', 'version', 'snapshot', 'published_at'])]
class LayoutTemplateVersion extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function layoutTemplate(): BelongsTo
    {
        return $this->belongsTo(LayoutTemplate::class);
    }
}
