<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var list<array{
     *     key: string,
     *     title: string,
     *     category: string,
     *     ratio_profile: string,
     *     text_position: string,
     *     sort_order: int
     * }>
     */
    private const array REMOVED_LAYOUTS = [
        ['key' => 'cover_top_01', 'title' => 'Cover Top Hero', 'category' => 'cover', 'ratio_profile' => '80_20', 'text_position' => 'bottom', 'sort_order' => 1],
        ['key' => 'cover_left_01', 'title' => 'Cover Left Hero', 'category' => 'cover', 'ratio_profile' => '80_20', 'text_position' => 'right', 'sort_order' => 2],
        ['key' => 'cover_right_01', 'title' => 'Cover Right Hero', 'category' => 'cover', 'ratio_profile' => '80_20', 'text_position' => 'left', 'sort_order' => 3],
        ['key' => 'ending_top_01', 'title' => 'Ending Top', 'category' => 'ending', 'ratio_profile' => '80_20', 'text_position' => 'bottom', 'sort_order' => 13],
        ['key' => 'ending_left_01', 'title' => 'Ending Left', 'category' => 'ending', 'ratio_profile' => '80_20', 'text_position' => 'right', 'sort_order' => 14],
        ['key' => 'ending_right_01', 'title' => 'Ending Right', 'category' => 'ending', 'ratio_profile' => '80_20', 'text_position' => 'left', 'sort_order' => 15],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('layout_templates')
            ->whereIn('category', ['cover', 'ending'])
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $now = now();

        foreach (self::REMOVED_LAYOUTS as $layout) {
            DB::table('layout_templates')->updateOrInsert(
                ['key' => $layout['key']],
                [
                    'title' => $layout['title'],
                    'category' => $layout['category'],
                    'ratio_profile' => $layout['ratio_profile'],
                    'text_position' => $layout['text_position'],
                    'sort_order' => $layout['sort_order'],
                    'is_active' => true,
                    'publication_status' => 'published',
                    'version' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
};
