<?php

namespace Database\Seeders;

use App\Enums\AgeRange;
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
                'age_range' => AgeRange::Toddler,
                'goal_name' => 'Делиться игрушками',
                'prompt_text' => 'Напиши цельную добрую детскую сказку про {name} (возраст {age}) с целью {goal}. Один мягкий сюжетный поворот, безопасный финал.',
            ],
            [
                'title' => 'RU Sleep Confidence Prompt',
                'language' => 'ru',
                'age_range' => AgeRange::Toddler,
                'goal_name' => 'Засыпать самостоятельно',
                'prompt_text' => 'Сгенерируй спокойную цельную сказку про {name} ({age}) для цели {goal}. История должна помогать уснуть, быть интересной и заканчиваться тёплым финалом.',
            ],
            [
                'title' => 'RU Brave Twist Prompt',
                'language' => 'ru',
                'age_range' => AgeRange::EarlyReader,
                'goal_name' => 'Преодолевать страхи',
                'prompt_text' => 'Создай увлекательную цельную сказку о ребенке {name}, возраст {age}, цель {goal}. Добавь безопасный сюжетный поворот и уверенный финал.',
            ],
            [
                'title' => 'RU Thank You Prompt',
                'language' => 'ru',
                'age_range' => AgeRange::Toddler,
                'goal_name' => 'Говорить «спасибо»',
                'prompt_text' => 'Напиши тёплую цельную сказку про {name} (возраст {age}) с целью {goal}. Покажи, как благодарность делает день ярче, и заверши мягким финалом.',
            ],
            [
                'title' => 'RU Independence Prompt',
                'language' => 'ru',
                'age_range' => AgeRange::EarlyReader,
                'goal_name' => 'Развивать самостоятельность',
                'prompt_text' => 'Сгенерируй добрую цельную сказку про {name} ({age}) для цели {goal}. Пусть герой сделает маленькое дело сам и закончит историю уверенным тёплым финалом.',
            ],
            [
                'title' => 'RU Emotions Prompt',
                'language' => 'ru',
                'age_range' => AgeRange::Toddler,
                'goal_name' => 'Управлять эмоциями',
                'prompt_text' => 'Создай спокойную цельную сказку о ребенке {name}, возраст {age}, цель {goal}. Помоги понять чувства через мягкий сюжет и безопасный финал.',
            ],
            [
                'title' => 'RU Friendship Prompt',
                'language' => 'ru',
                'age_range' => AgeRange::EarlyReader,
                'goal_name' => 'Дружить и общаться',
                'prompt_text' => 'Напиши дружелюбную цельную сказку про {name} (возраст {age}) с целью {goal}. Добавь тёплую встречу с другом и радостный безопасный финал.',
            ],
        ];

        foreach ($prompts as $prompt) {
            $storyGoal = StoryGoal::query()->where('name', $prompt['goal_name'])->first();

            StoryPrompt::query()->updateOrCreate(
                ['title' => $prompt['title']],
                [
                    'prompt_text' => $prompt['prompt_text'],
                    'language' => $prompt['language'],
                    'age_range' => $prompt['age_range'],
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
