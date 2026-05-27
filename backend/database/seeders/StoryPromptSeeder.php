<?php

namespace Database\Seeders;

use App\Models\AgeRange;
use App\Models\StoryGoal;
use App\Models\StoryPrompt;
use Illuminate\Database\Seeder;

class StoryPromptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prompts = [
            [
                'title' => 'RU Sharing Adventure Prompt',
                'language' => 'ru',
                'age_label' => '2-4 года / 2-4 years',
                'goal_name' => 'Делиться игрушками',
                'prompt_text' => 'Напиши детскую историю для возраста {age} про {name} и цель {goal}. ' .
                    'Сделай сюжет добрым, с одним мягким поворотом, и выдай текст по страницам до 80 символов.',
            ],
            [
                'title' => 'RU Sleep Confidence Prompt',
                'language' => 'ru',
                'age_label' => '2-4 года / 2-4 years',
                'goal_name' => 'Засыпать самостоятельно',
                'prompt_text' => 'Сгенерируй спокойную сказку про {name} ({age}) для цели {goal}. ' .
                    'История должна помогать уснуть, быть интересной и постранично до 80 символов.',
            ],
            [
                'title' => 'RU Brave Twist Prompt',
                'language' => 'ru',
                'age_label' => '5-7 лет / 5-7 years',
                'goal_name' => 'Преодолевать страхи',
                'prompt_text' => 'Создай увлекательную сказку о ребенке {name}, возраст {age}, цель {goal}. ' .
                    'Добавь безопасный сюжетный поворот и ограничь каждую страницу 80 символами.',
            ],
        ];

        foreach ($prompts as $prompt) {
            $ageRange = AgeRange::query()->where('label', $prompt['age_label'])->first();
            $storyGoal = StoryGoal::query()->where('name', $prompt['goal_name'])->first();

            StoryPrompt::query()->updateOrCreate(
                ['title' => $prompt['title']],
                [
                    'prompt_text' => $prompt['prompt_text'],
                    'language' => $prompt['language'],
                    'age_range_id' => $ageRange?->id,
                    'story_goal_id' => $storyGoal?->id,
                    'quality_score' => 0,
                    'rating_count' => 0,
                    'usage_count' => 0,
                    'is_active' => true,
                ],
            );
        }
    }
}
