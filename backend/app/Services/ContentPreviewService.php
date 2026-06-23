<?php

namespace App\Services;

use App\Models\BookTemplate;
use App\Models\LayoutTemplate;
use App\Models\StoryPrompt;

readonly class ContentPreviewService
{
    private const string SAMPLE_TEXT = 'Жили-были в сказочном лесу. Герой узнал что-то важное.';

    public function previewBookTemplate(BookTemplate $template): array
    {
        return [
            'type' => 'book_template',
            'title' => $template->title,
            'description' => $template->description,
            'is_free' => $template->is_free,
            'pages' => [
                $this->samplePage(1, 'cover', 'bottom', 'Обложка: '.$template->title),
                $this->samplePage(2, 'content', 'bottom', self::SAMPLE_TEXT),
                $this->samplePage(3, 'ending', 'bottom', 'Конец. '.$template->title),
            ],
        ];
    }

    public function previewStoryPrompt(StoryPrompt $prompt): array
    {
        return [
            'type' => 'story_prompt',
            'title' => $prompt->title,
            'prompt_text' => $prompt->prompt_text,
            'quality_score' => $prompt->quality_score,
            'rating_count' => $prompt->rating_count,
            'pages' => [
                $this->samplePage(1, 'cover', 'bottom', $prompt->title),
                $this->samplePage(2, 'content', 'left', self::SAMPLE_TEXT),
                $this->samplePage(3, 'ending', 'bottom', 'Финал истории'),
            ],
        ];
    }

    public function previewLayout(LayoutTemplate $layout): array
    {
        return [
            'type' => 'layout_template',
            'title' => $layout->title,
            'category' => $layout->category,
            'text_position' => $layout->text_position,
            'pages' => [
                $this->samplePage(1, $layout->category, $layout->text_position, self::SAMPLE_TEXT, $layout),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function samplePage(
        int $pageNumber,
        string $category,
        string $textPosition,
        string $text,
        ?LayoutTemplate $layout = null,
    ): array {
        return [
            'page_number' => $pageNumber,
            'text' => $text,
            'image_url' => null,
            'layout_template' => [
                'id' => $layout?->id,
                'key' => $layout?->key ?? 'preview',
                'title' => $layout?->title ?? 'Preview',
                'category' => $category,
                'text_position' => $textPosition,
                'ratio_profile' => $layout?->ratio_profile ?? '80_20',
            ],
        ];
    }
}
