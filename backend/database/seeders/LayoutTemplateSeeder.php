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
            ['key' => 'cover_top_01', 'title' => 'Cover Top Hero', 'category' => 'cover', 'ratio_profile' => '80_20', 'text_position' => 'bottom', 'sort_order' => 1],
            ['key' => 'cover_left_01', 'title' => 'Cover Left Hero', 'category' => 'cover', 'ratio_profile' => '80_20', 'text_position' => 'right', 'sort_order' => 2],
            ['key' => 'cover_right_01', 'title' => 'Cover Right Hero', 'category' => 'cover', 'ratio_profile' => '80_20', 'text_position' => 'left', 'sort_order' => 3],
            ['key' => 'content_top_01', 'title' => 'Content Top', 'category' => 'content', 'ratio_profile' => '80_20', 'text_position' => 'bottom', 'sort_order' => 4],
            ['key' => 'content_top_02', 'title' => 'Content Top Alt', 'category' => 'content', 'ratio_profile' => '80_20', 'text_position' => 'bottom', 'sort_order' => 5],
            ['key' => 'content_bottom_01', 'title' => 'Content Bottom', 'category' => 'content', 'ratio_profile' => '80_20', 'text_position' => 'top', 'sort_order' => 6],
            ['key' => 'content_left_01', 'title' => 'Content Left', 'category' => 'content', 'ratio_profile' => '80_20', 'text_position' => 'right', 'sort_order' => 7],
            ['key' => 'content_left_02', 'title' => 'Content Left Alt', 'category' => 'content', 'ratio_profile' => '80_20', 'text_position' => 'right', 'sort_order' => 8],
            ['key' => 'content_right_01', 'title' => 'Content Right', 'category' => 'content', 'ratio_profile' => '80_20', 'text_position' => 'left', 'sort_order' => 9],
            ['key' => 'content_right_02', 'title' => 'Content Right Alt', 'category' => 'content', 'ratio_profile' => '80_20', 'text_position' => 'left', 'sort_order' => 10],
            ['key' => 'content_split_01', 'title' => 'Content Split Horizontal', 'category' => 'content', 'ratio_profile' => '80_20', 'text_position' => 'bottom', 'sort_order' => 11],
            ['key' => 'content_split_02', 'title' => 'Content Split Vertical', 'category' => 'content', 'ratio_profile' => '80_20', 'text_position' => 'right', 'sort_order' => 12],
            ['key' => 'ending_top_01', 'title' => 'Ending Top', 'category' => 'ending', 'ratio_profile' => '80_20', 'text_position' => 'bottom', 'sort_order' => 13],
            ['key' => 'ending_left_01', 'title' => 'Ending Left', 'category' => 'ending', 'ratio_profile' => '80_20', 'text_position' => 'right', 'sort_order' => 14],
            ['key' => 'ending_right_01', 'title' => 'Ending Right', 'category' => 'ending', 'ratio_profile' => '80_20', 'text_position' => 'left', 'sort_order' => 15],
        ];

        foreach ($templates as $template) {
            LayoutTemplate::query()->updateOrCreate(
                ['key' => $template['key']],
                [
                    'title' => $template['title'],
                    'category' => $template['category'],
                    'ratio_profile' => $template['ratio_profile'],
                    'text_position' => $template['text_position'],
                    'sort_order' => $template['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
