<?php

namespace App\Services;

use App\Enums\PublicationStatus;
use App\Models\BookTemplate;
use App\Models\LayoutTemplate;
use App\Models\StoryPrompt;
use App\Repositories\Contracts\Admin\AdminContentRepositoryInterface;
use App\Repositories\Contracts\Admin\AdminVersionRepositoryInterface;

readonly class ContentPublicationService
{
    public function __construct(
        private AdminContentRepositoryInterface $adminContent,
        private AdminVersionRepositoryInterface $versions,
        private PromptQualityService $promptQuality,
    ) {}

    public function submitBookTemplateForReview(BookTemplate $template): BookTemplate
    {
        return $this->adminContent->updateTemplate($template, [
            'publication_status' => PublicationStatus::PendingReview,
        ]);
    }

    public function publishBookTemplate(BookTemplate $template): BookTemplate
    {
        if ($template->publication_status === PublicationStatus::Published) {
            $template = $this->adminContent->updateTemplate($template, [
                'version' => $template->version + 1,
            ]);
        }

        $this->versions->snapshotBookTemplate($template);

        return $this->adminContent->updateTemplate($template, [
            'publication_status' => PublicationStatus::Published,
            'is_active' => true,
        ]);
    }

    public function submitStoryPromptForReview(StoryPrompt $prompt): StoryPrompt
    {
        return $this->adminContent->updatePrompt($prompt, [
            'publication_status' => PublicationStatus::PendingReview,
        ]);
    }

    public function publishStoryPrompt(StoryPrompt $prompt): StoryPrompt
    {
        $this->promptQuality->ensurePublishable($prompt);

        if ($prompt->publication_status === PublicationStatus::Published) {
            $prompt = $this->adminContent->updatePrompt($prompt, [
                'version' => $prompt->version + 1,
            ]);
        }

        $this->versions->snapshotStoryPrompt($prompt);

        return $this->adminContent->updatePrompt($prompt, [
            'publication_status' => PublicationStatus::Published,
            'is_active' => true,
        ]);
    }

    public function submitLayoutForReview(LayoutTemplate $layout): LayoutTemplate
    {
        return $this->adminContent->updateLayout($layout, [
            'publication_status' => PublicationStatus::PendingReview,
        ]);
    }

    public function publishLayout(LayoutTemplate $layout): LayoutTemplate
    {
        if ($layout->publication_status === PublicationStatus::Published) {
            $layout = $this->adminContent->updateLayout($layout, [
                'version' => $layout->version + 1,
            ]);
        }

        $this->versions->snapshotLayoutTemplate($layout);

        return $this->adminContent->updateLayout($layout, [
            'publication_status' => PublicationStatus::Published,
            'is_active' => true,
        ]);
    }
}
