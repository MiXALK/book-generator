<?php

namespace App\Repositories\Contracts\Admin;

use App\Models\BookTemplate;
use App\Models\LayoutTemplate;
use App\Models\StoryGoal;
use App\Models\StoryPrompt;
use App\Models\StoryPromptRating;
use Illuminate\Support\Collection;

interface AdminContentRepositoryInterface
{
    public function listGoals(): Collection;

    public function findGoal(int $id): StoryGoal;

    public function createGoal(array $attributes): StoryGoal;

    public function updateGoal(StoryGoal $goal, array $attributes): StoryGoal;

    public function deleteGoal(StoryGoal $goal): void;

    public function listTemplates(): Collection;

    public function findTemplate(int $id): BookTemplate;

    public function createTemplate(array $attributes): BookTemplate;

    public function updateTemplate(BookTemplate $template, array $attributes): BookTemplate;

    public function deleteTemplate(BookTemplate $template): void;

    public function listPrompts(): Collection;

    public function findPrompt(int $id): StoryPrompt;

    public function createPrompt(array $attributes): StoryPrompt;

    public function updatePrompt(StoryPrompt $prompt, array $attributes): StoryPrompt;

    public function deletePrompt(StoryPrompt $prompt): void;

    public function createPromptRating(StoryPrompt $prompt, ?int $userId, int $rating, ?string $notes): StoryPromptRating;

    public function listLayouts(): Collection;

    public function findLayout(int $id): LayoutTemplate;

    public function createLayout(array $attributes): LayoutTemplate;

    public function updateLayout(LayoutTemplate $layout, array $attributes): LayoutTemplate;

    public function deleteLayout(LayoutTemplate $layout): void;

    public function listReviewQueue(): Collection;
}
