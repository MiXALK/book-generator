<?php

namespace Database\Seeders;

use App\Models\BookTemplate;
use App\Models\StoryGoal;
use Illuminate\Database\Seeder;

class BookTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templateDefinitions = [
            [
                'title' => 'Маленький обмен',
                'goal_name' => 'Делиться игрушками',
                'is_free' => true,
                'is_active' => true,
            ],
            [
                'title' => 'Сонный фонарик',
                'goal_name' => 'Засыпать самостоятельно',
                'is_free' => true,
                'is_active' => true,
            ],
            [
                'title' => 'Смелый шаг',
                'goal_name' => 'Преодолевать страхи',
                'is_free' => false,
                'is_active' => true,
            ],
            [
                'title' => 'Волшебное спасибо',
                'goal_name' => 'Говорить «спасибо»',
                'is_free' => false,
                'is_active' => true,
            ],
            [
                'title' => 'Мой маленький шаг',
                'goal_name' => 'Развивать самостоятельность',
                'is_free' => false,
                'is_active' => true,
            ],
            [
                'title' => 'Спокойные чувства',
                'goal_name' => 'Управлять эмоциями',
                'is_free' => false,
                'is_active' => true,
            ],
            [
                'title' => 'Дружная компания',
                'goal_name' => 'Дружить и общаться',
                'is_free' => false,
                'is_active' => true,
            ],
        ];

        foreach ($templateDefinitions as $template) {
            $storyGoal = StoryGoal::query()
                ->where('name', $template['goal_name'])
                ->firstOrFail();

            BookTemplate::query()->updateOrCreate(
                ['title' => $template['title']],
                [
                    'story_goal_id' => $storyGoal->id,
                    'is_free' => $template['is_free'],
                    'is_active' => $template['is_active'],
                ],
            );
        }
    }
}
