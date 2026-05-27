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
        ];

        foreach ($goals as $goal) {
            StoryGoal::query()->updateOrCreate(
                ['name' => $goal['name']],
                ['description' => $goal['description']],
            );
        }
    }
}
