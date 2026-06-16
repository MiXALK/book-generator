<?php

use App\Models\BookTemplate;
use Database\Seeders\BookTemplateSeeder;
use Database\Seeders\StoryGoalSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('book_templates') || ! Schema::hasColumn('book_templates', 'story_goal_id')) {
            return;
        }

        if (! BookTemplate::query()->whereNull('story_goal_id')->exists()) {
            return;
        }

        (new StoryGoalSeeder)->run();
        (new BookTemplateSeeder)->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
