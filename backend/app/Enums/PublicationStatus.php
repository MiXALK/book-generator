<?php

namespace App\Enums;

enum PublicationStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Published = 'published';

    public function isVisibleInCatalog(): bool
    {
        return $this === self::Published;
    }
}
