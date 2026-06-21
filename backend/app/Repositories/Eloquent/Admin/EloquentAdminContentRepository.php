<?php

namespace App\Repositories\Eloquent\Admin;

use App\Enums\PublicationStatus;
use App\Models\BookTemplate;
use App\Models\BookTemplateVersion;
use App\Models\LayoutTemplate;
use App\Models\LayoutTemplateVersion;
use App\Models\StoryGoal;
use App\Models\StoryPrompt;
use App\Models\StoryPromptRating;
use App\Models\StoryPromptVersion;
use App\Repositories\Contracts\Admin\AdminContentRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentAdminContentRepository implements AdminContentRepositoryInterface
{
    public function listGoals(): Collection
    {
        return StoryGoal::query()
            ->with('bookTemplate')
            ->orderBy('name')
            ->get();
    }

    public function findGoal(int $id): StoryGoal
    {
        return StoryGoal::query()
            ->with('bookTemplate')
            ->findOrFail($id);
    }

    public function createGoal(array $attributes): StoryGoal
    {
        return StoryGoal::query()->create($attributes);
    }

    public function updateGoal(StoryGoal $goal, array $attributes): StoryGoal
    {
        $goal->update($attributes);

        return $goal->fresh(['bookTemplate']);
    }

    public function deleteGoal(StoryGoal $goal): void
    {
        $goal->delete();
    }

    public function listTemplates(): Collection
    {
        return BookTemplate::query()
            ->with('storyGoal:id,name')
            ->orderBy('title')
            ->get();
    }

    public function findTemplate(int $id): BookTemplate
    {
        return BookTemplate::query()
            ->with(['storyGoal', 'versions'])
            ->findOrFail($id);
    }

    public function createTemplate(array $attributes): BookTemplate
    {
        return BookTemplate::query()->create($attributes);
    }

    public function updateTemplate(BookTemplate $template, array $attributes): BookTemplate
    {
        $template->update($attributes);

        return $template->fresh(['storyGoal', 'versions']);
    }

    public function deleteTemplate(BookTemplate $template): void
    {
        $template->delete();
    }

    public function listPrompts(): Collection
    {
        return StoryPrompt::query()
            ->with('storyGoal:id,name')
            ->orderByDesc('quality_score')
            ->orderBy('title')
            ->get();
    }

    public function findPrompt(int $id): StoryPrompt
    {
        return StoryPrompt::query()
            ->with(['storyGoal', 'ratings', 'versions'])
            ->findOrFail($id);
    }

    public function createPrompt(array $attributes): StoryPrompt
    {
        return StoryPrompt::query()->create($attributes);
    }

    public function updatePrompt(StoryPrompt $prompt, array $attributes): StoryPrompt
    {
        $prompt->update($attributes);

        return $prompt->fresh(['storyGoal', 'ratings', 'versions']);
    }

    public function deletePrompt(StoryPrompt $prompt): void
    {
        $prompt->delete();
    }

    public function createPromptRating(StoryPrompt $prompt, ?int $userId, int $rating, ?string $notes): StoryPromptRating
    {
        return StoryPromptRating::query()->create([
            'story_prompt_id' => $prompt->id,
            'user_id' => $userId,
            'rating' => $rating,
            'notes' => $notes,
        ]);
    }

    public function listLayouts(): Collection
    {
        return LayoutTemplate::query()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->get();
    }

    public function findLayout(int $id): LayoutTemplate
    {
        return LayoutTemplate::query()
            ->with('versions')
            ->findOrFail($id);
    }

    public function createLayout(array $attributes): LayoutTemplate
    {
        return LayoutTemplate::query()->create($attributes);
    }

    public function updateLayout(LayoutTemplate $layout, array $attributes): LayoutTemplate
    {
        $layout->update($attributes);

        return $layout->fresh(['versions']);
    }

    public function deleteLayout(LayoutTemplate $layout): void
    {
        $layout->delete();
    }

    public function listReviewQueue(): Collection
    {
        $templates = BookTemplate::query()
            ->where('publication_status', PublicationStatus::PendingReview)
            ->get()
            ->map(fn (BookTemplate $item) => [
                'type' => 'book_template',
                'id' => $item->id,
                'title' => $item->title,
                'updated_at' => $item->updated_at?->toIso8601String(),
            ]);

        $prompts = StoryPrompt::query()
            ->where('publication_status', PublicationStatus::PendingReview)
            ->get()
            ->map(fn (StoryPrompt $item) => [
                'type' => 'story_prompt',
                'id' => $item->id,
                'title' => $item->title,
                'updated_at' => $item->updated_at?->toIso8601String(),
            ]);

        $layouts = LayoutTemplate::query()
            ->where('publication_status', PublicationStatus::PendingReview)
            ->get()
            ->map(fn (LayoutTemplate $item) => [
                'type' => 'layout_template',
                'id' => $item->id,
                'title' => $item->title,
                'updated_at' => $item->updated_at?->toIso8601String(),
            ]);

        return $templates
            ->concat($prompts)
            ->concat($layouts)
            ->sortByDesc('updated_at')
            ->values();
    }
}
