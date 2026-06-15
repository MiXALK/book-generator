<?php

namespace Database\Seeders;

use App\Models\StoryGoal;
use Illuminate\Database\Seeder;

class StoryGoalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $goals = [
            [
                'name' => 'Делиться игрушками',
                'description' => 'RU: Учимся делиться. | EN: Learning to share toys.',
            ],
            [
                'name' => 'Засыпать самостоятельно',
                'description' => 'RU: Спокойный сон без помощи. | EN: Falling asleep alone.',
            ],
            [
                'name' => 'Преодолевать страхи',
                'description' => 'RU: Делаем шаг навстречу смелости. | EN: Facing fears gently.',
            ],
            [
                'name' => 'Говорить «спасибо»',
                'description' => 'RU: Учимся благодарить. | EN: Learning to say thank you.',
            ],
            [
                'name' => 'Развивать самостоятельность',
                'description' => 'RU: Делаем маленькие дела сами. | EN: Building independence.',
            ],
            [
                'name' => 'Управлять эмоциями',
                'description' => 'RU: Спокойно переживаем чувства. | EN: Managing emotions gently.',
            ],
            [
                'name' => 'Дружить и общаться',
                'description' => 'RU: Находим друзей и общаемся. | EN: Making friends and socializing.',
            ],
        ];

        foreach ($goals as $goal) {
            StoryGoal::query()->updateOrCreate(
                ['name' => $goal['name']],
                ['description' => $goal['description']],
            );
        }
    }
}
