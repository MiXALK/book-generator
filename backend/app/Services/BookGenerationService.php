<?php

namespace App\Services;

use App\Models\BookGeneration;
use App\Models\BookTemplate;
use App\Models\StoryPrompt;
use App\Models\User;
use App\Repositories\Contracts\BookGenerationRepositoryInterface;
use App\Repositories\Contracts\BookPageRepositoryInterface;
use App\Repositories\Contracts\LayoutTemplateRepositoryInterface;
use App\Repositories\Contracts\StoryPromptRepositoryInterface;
use App\Services\Ai\Contracts\StoryTextGenerationProviderInterface;
use App\Services\Ai\Data\StoryTextGenerationInput;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookGenerationService
{
    private const int FREE_MONTHLY_LIMIT = 33;

    private const string DEFAULT_PROMPT_TEXT = 'Напиши цельную добрую детскую сказку про {name} (возраст {age}) с целью {goal}. '.
        'Один мягкий сюжетный поворот, безопасный финал.';

    public function __construct(
        private readonly BookGenerationRepositoryInterface $bookGenerations,
        private readonly BookPageRepositoryInterface $bookPages,
        private readonly StoryPromptRepositoryInterface $storyPrompts,
        private readonly LayoutTemplateRepositoryInterface $layoutTemplates,
        private readonly StoryTextGenerationProviderInterface $storyTextProvider,
        private readonly BookIllustrationStorageService $illustrationStorage,
        private readonly StoryPaginator $storyPaginator,
    ) {}

    public function formatForApi(BookGeneration $generation): BookGeneration
    {
        $loaded = $this->bookGenerations->loadForApi($generation);
        $this->illustrationStorage->resolveGenerationImageUrls($loaded);

        return $loaded;
    }

    public function ensureGenerationLimit(User $user): void
    {
        if ($user->plan !== 'free') {
            return;
        }

        $count = $this->bookGenerations->countForUserInCurrentMonth($user->id);

        if ($count >= self::FREE_MONTHLY_LIMIT) {
            throw new HttpResponseException(response()->json([
                'message' => 'Monthly free generation limit reached.',
                'limit' => self::FREE_MONTHLY_LIMIT,
            ], 422));
        }
    }

    public function generate(
        User $user,
        BookTemplate $template,
        string $childName,
        int $age,
        string $goal,
    ): BookGeneration {
        $prompt = $this->selectPrompt($user, $age, $goal);

        return DB::transaction(function () use ($user, $template, $childName, $age, $goal, $prompt) {
            $generation = $this->createGeneration($user, $template, $childName, $age, $goal, $prompt);
            $built = $this->buildPages($childName, $age, $goal, $prompt);
            $pages = $this->attachPlaceholderIllustrations($generation->id, $built['pages'], $built['layouts']);

            $this->bookPages->createMany($generation, $pages);
            $this->completeGeneration($generation, $prompt);

            return $this->formatForApi($generation);
        });
    }

    private function createGeneration(
        User $user,
        BookTemplate $template,
        string $childName,
        int $age,
        string $goal,
        ?StoryPrompt $prompt,
    ): BookGeneration {
        return $this->bookGenerations->create([
            'user_id' => $user->id,
            'book_template_id' => $template->id,
            'story_prompt_id' => $prompt?->id,
            'child_name' => $childName,
            'child_age' => $age,
            'child_goal' => $goal,
            'prompt_snapshot' => $prompt?->prompt_text,
            'status' => 'processing',
        ]);
    }

    private function completeGeneration(BookGeneration $generation, ?StoryPrompt $prompt): void
    {
        $this->bookGenerations->updateStatus($generation, 'completed');

        if ($prompt) {
            $this->storyPrompts->incrementUsageCount($prompt);
        }
    }

    private function selectPrompt(User $user, int $age, string $goal): ?StoryPrompt
    {
        return $this->storyPrompts->findBestForGeneration($user->language ?? 'ru', $age, $goal);
    }

    private function buildPages(string $name, int $age, string $goal, ?StoryPrompt $prompt): array
    {
        $storyText = $this->resolveStoryText($name, $age, $goal, $prompt);
        $pageTexts = $this->storyPaginator->paginate($storyText);

        $pageCount = count($pageTexts);
        $layouts = $this->pickLayouts($pageCount);

        $pages = [];
        foreach ($pageTexts as $index => $text) {
            $pages[] = [
                'page_number' => $index + 1,
                'layout_template_id' => $layouts[$index]?->id,
                'text' => $text,
            ];
        }

        return [
            'pages' => $pages,
            'layouts' => $layouts,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $pages
     */
    private function attachPlaceholderIllustrations(int $generationId, array $pages, Collection $layouts): array
    {
        foreach ($pages as $index => $page) {
            $layout = $layouts[$index] ?? null;
            $category = is_object($layout) && isset($layout->category)
                ? (string) $layout->category
                : 'content';
            $pageNumber = (int) $page['page_number'];
            $pages[$index]['image_url'] = $this->illustrationStorage->storePlaceholder(
                $generationId,
                $pageNumber,
                $category,
            );
        }

        return $pages;
    }

    private function resolveStoryText(string $name, int $age, string $goal, ?StoryPrompt $prompt): string
    {
        $story = $this->generateStoryWithAi($name, $age, $goal, $prompt);

        if ($story !== null && trim($story) !== '') {
            return $story;
        }

        return $this->buildFallbackStory($name, $age, $goal);
    }

    private function generateStoryWithAi(
        string $name,
        int $age,
        string $goal,
        ?StoryPrompt $prompt,
    ): ?string {
        if (! $this->storyTextProvider->isConfigured()) {
            return null;
        }

        $promptText = $this->resolvePromptText($prompt, $name, $age, $goal);

        $input = new StoryTextGenerationInput(
            promptText: $promptText,
            childName: $name,
            childAge: $age,
            childGoal: $goal,
        );

        $story = $this->storyTextProvider->generateStory($input);

        if ($story === null) {
            Log::warning('Story text generation returned no story; using fallback text', [
                'child_age' => $age,
                'child_goal' => $goal,
                'story_prompt_id' => $prompt?->id,
            ]);
        }

        return $story;
    }

    private function resolvePromptText(?StoryPrompt $prompt, string $name, int $age, string $goal): string
    {
        if ($prompt === null) {
            $template = self::DEFAULT_PROMPT_TEXT;
        } else {
            $template = $prompt->prompt_text;
        }

        return strtr($template, [
            '{name}' => $name,
            '{age}' => (string) $age,
            '{goal}' => $goal,
        ]);
    }

    private function buildFallbackStory(string $name, int $age, string $goal): string
    {
        $sentences = [];
        $sentenceCount = 8;
        $midpoint = (int) ceil($sentenceCount / 2);

        for ($pageNumber = 1; $pageNumber <= $sentenceCount; $pageNumber++) {
            $sentences[] = $this->fallbackText($name, $age, $goal, $pageNumber, $sentenceCount, $midpoint);
        }

        return implode(' ', $sentences);
    }

    private function fallbackText(string $name, int $age, string $goal, int $pageNumber, int $totalPages, int $midpoint): string
    {
        return match (true) {
            $pageNumber === 1 => "{$name}, {$age}, начинает историю о цели: {$goal}.",
            $pageNumber === $midpoint => "Неожиданно сюжет меняется, но {$name} не сдается.",
            $pageNumber === $totalPages => "{$name} достигает цели {$goal} и радуется финалу.",
            default => "{$name} делает шаг к цели {$goal} и становится смелее.",
        };
    }

    private function pickLayouts(int $pagesCount): Collection
    {
        $cover = $this->layoutTemplates->findRandomActiveByCategory('cover');
        $ending = $this->layoutTemplates->findRandomActiveByCategory('ending');
        $content = $this->layoutTemplates->listRandomActiveByCategory('content', max(0, $pagesCount - 2));

        $layouts = collect();

        if ($cover) {
            $layouts->push($cover);
        }

        $layouts = $layouts->merge($content);

        if ($ending) {
            $layouts->push($ending);
        }

        while ($layouts->count() < $pagesCount) {
            $fallback = $this->layoutTemplates->findRandomActive();
            if (! $fallback) {
                break;
            }
            $layouts->push($fallback);
        }

        return $layouts->take($pagesCount)->values();
    }
}
