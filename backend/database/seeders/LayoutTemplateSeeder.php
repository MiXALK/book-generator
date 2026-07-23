<?php

namespace Database\Seeders;

use App\Models\LayoutTemplate;
use Illuminate\Database\Seeder;

class LayoutTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            ['key' => 'content_top_01', 'title' => 'Content Top', 'ratio_profile' => '80_20', 'text_position' => 'bottom', 'sort_order' => 4],
            ['key' => 'content_top_02', 'title' => 'Content Top Alt', 'ratio_profile' => '80_20', 'text_position' => 'bottom', 'sort_order' => 5],
            ['key' => 'content_bottom_01', 'title' => 'Content Bottom', 'ratio_profile' => '80_20', 'text_position' => 'top', 'sort_order' => 6],
            ['key' => 'content_left_01', 'title' => 'Content Left', 'ratio_profile' => '80_20', 'text_position' => 'right', 'sort_order' => 7],
            ['key' => 'content_left_02', 'title' => 'Content Left Alt', 'ratio_profile' => '80_20', 'text_position' => 'right', 'sort_order' => 8],
            ['key' => 'content_right_01', 'title' => 'Content Right', 'ratio_profile' => '80_20', 'text_position' => 'left', 'sort_order' => 9],
            ['key' => 'content_right_02', 'title' => 'Content Right Alt', 'ratio_profile' => '80_20', 'text_position' => 'left', 'sort_order' => 10],
            ['key' => 'content_split_01', 'title' => 'Content Split Horizontal', 'ratio_profile' => '80_20', 'text_position' => 'bottom', 'sort_order' => 11],
            ['key' => 'content_split_02', 'title' => 'Content Split Vertical', 'ratio_profile' => '80_20', 'text_position' => 'right', 'sort_order' => 12],
        ];

        foreach ($templates as $template) {
            LayoutTemplate::query()->updateOrCreate(
                ['key' => $template['key']],
                [
                    'title' => $template['title'],
                    'ratio_profile' => $template['ratio_profile'],
                    'text_position' => $template['text_position'],
                    'sort_order' => $template['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
