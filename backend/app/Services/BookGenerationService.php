<?php

namespace App\Services;

use App\Models\BookGeneration;
use App\Models\BookTemplate;
use App\Models\StoryPrompt;
use App\Models\User;
use App\Repositories\Contracts\BookGenerationRepositoryInterface;
use App\Repositories\Contracts\BookPageRepositoryInterface;
use App\Repositories\Contracts\BookTemplateRepositoryInterface;
use App\Repositories\Contracts\LayoutTemplateRepositoryInterface;
use App\Repositories\Contracts\StoryPromptRepositoryInterface;
use App\Services\Ai\Contracts\StoryTextGenerationProviderInterface;
use App\Services\Ai\Data\StoryTextGenerationInput;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookGenerationService
{
    private const int FREE_MONTHLY_LIMIT = 3;

    private const int MAX_PAGE_TEXT_LENGTH = 80;

    public function __construct(
        private readonly BookGenerationRepositoryInterface $bookGenerations,
        private readonly BookPageRepositoryInterface $bookPages,
        private readonly BookTemplateRepositoryInterface $bookTemplates,
        private readonly StoryPromptRepositoryInterface $storyPrompts,
        private readonly LayoutTemplateRepositoryInterface $layoutTemplates,
        private readonly StoryTextGenerationProviderInterface $storyTextProvider,
        private readonly BookIllustrationStorageService $illustrationStorage,
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
            $built = $this->buildPages($childName, $age, $goal, $template, $prompt);
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

    private function buildPages(string $name, int $age, string $goal, BookTemplate $template, ?StoryPrompt $prompt): array
    {
        $scenes = $this->bookTemplates->getOrderedScenes($template);

        if ($scenes->isEmpty()) {
            $scenes = collect([
                (object) ['scene_number' => 1],
                (object) ['scene_number' => 2],
                (object) ['scene_number' => 3],
                (object) ['scene_number' => 4],
            ]);
        }

        $pageCount = $scenes->count();
        $layouts = $this->pickLayouts($pageCount);
        $aiTexts = $this->generateStoryTextsWithAi($name, $age, $goal, $prompt, $scenes, $pageCount);
        $midpoint = (int) ceil($pageCount / 2);

        $pages = $scenes->values()->map(function ($scene, $index) use ($name, $age, $goal, $layouts, $midpoint, $aiTexts) {
            $pageNumber = $index + 1;
            $raw = $aiTexts[$index] ?? $this->fallbackText($name, $age, $goal, $pageNumber, $layouts->count(), $midpoint);

            return [
                'page_number' => $pageNumber,
                'layout_template_id' => $layouts[$index]?->id,
                'text' => $this->limitSymbols($raw),
            ];
        })->all();

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

    private function generateStoryTextsWithAi(
        string $name,
        int $age,
        string $goal,
        ?StoryPrompt $prompt,
        Collection $scenes,
        int $pageCount,
    ): array {
        if (! $prompt || ! $this->storyTextProvider->isConfigured()) {
            return $this->fallbackTexts($name, $age, $goal, $pageCount);
        }

        $sceneInstructions = $scenes
            ->pluck('scene_instruction')
            ->filter(fn ($instruction) => is_string($instruction) && trim($instruction) !== '')
            ->values()
            ->all();

        $promptText = strtr($prompt->prompt_text, [
            '{name}' => $name,
            '{age}' => (string) $age,
            '{goal}' => $goal,
        ]);

        $input = new StoryTextGenerationInput(
            promptText: $promptText,
            childName: $name,
            childAge: $age,
            childGoal: $goal,
            sceneInstructions: $sceneInstructions,
            pageCount: $pageCount,
        );

        $pages = $this->storyTextProvider->generatePages($input);

        if ($pages === null) {
            return $this->fallbackTexts($name, $age, $goal, $pageCount);
        }

        return $this->normalizeAiPages($pages, $name, $age, $goal, $pageCount);
    }

    private function normalizeAiPages(array $pages, string $name, int $age, string $goal, int $pageCount): array
    {
        $normalized = [];

        foreach ($pages as $page) {
            if (! is_string($page)) {
                continue;
            }

            $normalized[] = $this->limitSymbols(trim($page));

            if (count($normalized) === $pageCount) {
                break;
            }
        }

        while (count($normalized) < $pageCount) {
            $pageNumber = count($normalized) + 1;
            $midpoint = (int) ceil($pageCount / 2);
            $normalized[] = $this->limitSymbols($this->fallbackText($name, $age, $goal, $pageNumber, $pageCount, $midpoint));
        }

        return $normalized;
    }

    private function fallbackTexts(string $name, int $age, string $goal, int $pageCount): array
    {
        $midpoint = (int) ceil($pageCount / 2);
        $fallback = [];

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $fallback[] = $this->fallbackText($name, $age, $goal, $pageNumber, $pageCount, $midpoint);
        }

        return $fallback;
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

    private function limitSymbols(string $value): string
    {
        $max = self::MAX_PAGE_TEXT_LENGTH;

        if (mb_strlen($value) <= $max) {
            return $value;
        }

        $chunk = mb_substr($value, 0, $max);
        $sentenceEnd = $this->findLastSentenceEndPosition($chunk);

        if ($sentenceEnd !== null) {
            return rtrim(mb_substr($value, 0, $sentenceEnd + 1));
        }

        $lastSpace = mb_strrpos($chunk, ' ');
        if ($lastSpace !== false && $lastSpace > 0) {
            return rtrim(mb_substr($value, 0, $lastSpace));
        }

        return rtrim($chunk);
    }

    private function findLastSentenceEndPosition(string $text): ?int
    {
        $lastPosition = null;
        $length = mb_strlen($text);

        for ($index = $length - 1; $index >= 0; $index--) {
            $character = mb_substr($text, $index, 1);

            if (in_array($character, ['.', '!', '?', '…'], true)) {
                $lastPosition = $index;
                break;
            }
        }

        return $lastPosition;
    }
}
