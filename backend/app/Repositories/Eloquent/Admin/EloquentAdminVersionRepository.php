<?php

namespace App\Repositories\Eloquent\Admin;

use App\Models\BookTemplate;
use App\Models\BookTemplateVersion;
use App\Models\LayoutTemplate;
use App\Models\LayoutTemplateVersion;
use App\Models\StoryPrompt;
use App\Models\StoryPromptVersion;
use App\Repositories\Contracts\Admin\AdminVersionRepositoryInterface;

class EloquentAdminVersionRepository implements AdminVersionRepositoryInterface
{
    public function snapshotBookTemplate(BookTemplate $template): void
    {
        BookTemplateVersion::query()->updateOrCreate(
            [
                'book_template_id' => $template->id,
                'version' => $template->version,
            ],
            [
                'snapshot' => $this->bookTemplateSnapshot($template),
                'published_at' => now(),
            ],
        );
    }

    public function snapshotStoryPrompt(StoryPrompt $prompt): void
    {
        StoryPromptVersion::query()->updateOrCreate(
            [
                'story_prompt_id' => $prompt->id,
                'version' => $prompt->version,
            ],
            [
                'snapshot' => $this->storyPromptSnapshot($prompt),
                'published_at' => now(),
            ],
        );
    }

    public function snapshotLayoutTemplate(LayoutTemplate $layout): void
    {
        LayoutTemplateVersion::query()->updateOrCreate(
            [
                'layout_template_id' => $layout->id,
                'version' => $layout->version,
            ],
            [
                'snapshot' => $this->layoutTemplateSnapshot($layout),
                'published_at' => now(),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function bookTemplateSnapshot(BookTemplate $template): array
    {
        $template->loadMissing('storyGoal');

        return [
            'title' => $template->title,
            'description' => $template->description,
            'is_free' => $template->is_free,
            'is_active' => $template->is_active,
            'story_goal_id' => $template->story_goal_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storyPromptSnapshot(StoryPrompt $prompt): array
    {
        return [
            'title' => $prompt->title,
            'prompt_text' => $prompt->prompt_text,
            'language' => $prompt->language,
            'age_range' => $prompt->age_range?->value,
            'story_goal_id' => $prompt->story_goal_id,
            'quality_score' => $prompt->quality_score,
            'rating_count' => $prompt->rating_count,
            'is_active' => $prompt->is_active,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function layoutTemplateSnapshot(LayoutTemplate $layout): array
    {
        return [
            'key' => $layout->key,
            'title' => $layout->title,
            'ratio_profile' => $layout->ratio_profile,
            'text_position' => $layout->text_position,
            'sort_order' => $layout->sort_order,
            'is_active' => $layout->is_active,
        ];
    }
}
