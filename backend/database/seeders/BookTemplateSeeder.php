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
                'description' => 'RU: История о дружбе. | EN: A short sharing story.',
                'is_free' => true,
                'template_type' => 'story',
                'is_active' => true,
            ],
            [
                'title' => 'Сонный фонарик',
                'goal_name' => 'Засыпать самостоятельно',
                'description' => 'RU: Ритуал перед сном. | EN: Bedtime courage routine.',
                'is_free' => true,
                'template_type' => 'story',
                'is_active' => true,
            ],
            [
                'title' => 'Смелый шаг',
                'goal_name' => 'Преодолевать страхи',
                'description' => 'RU: История храбрости. | EN: Brave steps every day.',
                'is_free' => true,
                'template_type' => 'story',
                'is_active' => true,
            ],
        ];

        foreach ($templateDefinitions as $template) {
            $storyGoal = StoryGoal::query()->where('name', $template['goal_name'])->first();

            BookTemplate::query()->updateOrCreate(
                ['title' => $template['title']],
                [
                    'story_goal_id' => $storyGoal?->id,
                    'description' => $template['description'],
                    'is_free' => $template['is_free'],
                    'template_type' => $template['template_type'],
                    'is_active' => $template['is_active'],
                ],
            );
        }
    }
}
