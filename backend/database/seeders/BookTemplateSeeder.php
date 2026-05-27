<?php

namespace Database\Seeders;

use App\Models\BookTemplate;
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
                'description' => 'RU: История о дружбе. | EN: A short sharing story.',
                'is_free' => true,
                'template_type' => 'story',
                'is_active' => true,
            ],
            [
                'title' => 'Сонный фонарик',
                'description' => 'RU: Ритуал перед сном. | EN: Bedtime courage routine.',
                'is_free' => true,
                'template_type' => 'story',
                'is_active' => true,
            ],
            [
                'title' => 'Смелый шаг',
                'description' => 'RU: История храбрости. | EN: Brave steps every day.',
                'is_free' => true,
                'template_type' => 'story',
                'is_active' => true,
            ],
        ];

        foreach ($templateDefinitions as $template) {
            BookTemplate::query()->updateOrCreate(
                ['title' => $template['title']],
                [
                    'description' => $template['description'],
                    'is_free' => $template['is_free'],
                    'template_type' => $template['template_type'],
                    'is_active' => $template['is_active'],
                ],
            );
        }
    }
}
