<?php

namespace App\Repositories\Contracts\Admin;

use App\Models\BookTemplate;
use App\Models\LayoutTemplate;
use App\Models\StoryPrompt;

interface AdminVersionRepositoryInterface
{
    public function snapshotBookTemplate(BookTemplate $template): void;

    public function snapshotStoryPrompt(StoryPrompt $prompt): void;

    public function snapshotLayoutTemplate(LayoutTemplate $layout): void;
}
