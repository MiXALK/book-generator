<?php

namespace Tests\Unit;

use App\Services\StoryPaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoryPaginatorTest extends TestCase
{
    private StoryPaginator $paginator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paginator = new StoryPaginator;
    }

    public function test_paginate_returns_empty_for_blank_input(): void
    {
        $this->assertSame([], $this->paginator->paginate(''));
        $this->assertSame([], $this->paginator->paginate('   '));
    }

    public function test_paginate_packs_multiple_short_sentences_into_one_page(): void
    {
        $story = 'Маша проснулась. Она улыбнулась. Солнце светило в окно.';

        $pages = $this->paginator->paginate($story);

        $this->assertCount(1, $pages);
        $this->assertLessThanOrEqual(80, mb_strlen($pages[0]));
        $this->assertStringContainsString('Маша проснулась.', $pages[0]);
        $this->assertStringContainsString('Солнце светило в окно.', $pages[0]);
    }

    public function test_paginate_splits_when_combined_sentences_exceed_limit(): void
    {
        $story = 'Короткое начало. '.
            'Это предложение уже заметно длиннее и занимает больше места на странице. '.
            'Финал короткий.';

        $pages = $this->paginator->paginate($story);

        $this->assertGreaterThan(1, count($pages));

        foreach ($pages as $page) {
            $this->assertLessThanOrEqual(80, mb_strlen($page));
        }
    }

    public function test_paginate_handles_long_single_sentence(): void
    {
        $story = 'Очень длинное предложение, которое само по себе превышает мягкий лимит в восемьдесят символов и должно быть аккуратно обрезано.';

        $pages = $this->paginator->paginate($story);

        $this->assertSame([$story], $pages);
    }

    public function test_paginate_does_not_split_long_sentence_between_pages(): void
    {
        $longSentence = 'Это очень длинное предложение, которое заметно превышает лимит в восемьдесят символов, но теперь должно оставаться на одной странице целиком.';
        $story = "Короткое начало. {$longSentence} Финал короткий.";

        $pages = $this->paginator->paginate($story);

        $this->assertGreaterThanOrEqual(2, count($pages));
        $this->assertContains($longSentence, $pages);
    }

    public function test_paginate_normalizes_whitespace(): void
    {
        $story = "Первое   предложение.\n\nВторое предложение.";

        $pages = $this->paginator->paginate($story);

        $this->assertCount(1, $pages);
        $this->assertSame('Первое предложение. Второе предложение.', $pages[0]);
    }

    #[DataProvider('cyrillicStoryProvider')]
    public function test_paginate_respects_eighty_character_limit_for_cyrillic(string $story): void
    {
        $pages = $this->paginator->paginate($story);

        $this->assertNotEmpty($pages);

        foreach ($pages as $page) {
            $this->assertLessThanOrEqual(80, mb_strlen($page));
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function cyrillicStoryProvider(): array
    {
        $sentences = [];
        for ($index = 1; $index <= 10; $index++) {
            $sentences[] = "Предложение номер {$index} в доброй детской сказке.";
        }

        return [
            'long_russian_story' => [implode(' ', $sentences)],
        ];
    }

    public function test_paginate_keeps_all_pages_for_long_story(): void
    {
        $sentences = [];
        for ($index = 1; $index <= 20; $index++) {
            $sentences[] = "Предложение {$index}: добрая сказка продолжается и занимает почти целую страницу книги.";
        }

        $pages = $this->paginator->paginate(implode(' ', $sentences));

        $this->assertGreaterThan(16, count($pages));
    }
}
