<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('book_templates', function (Blueprint $table) {
            if (Schema::hasColumn('book_templates', 'age_range_id')) {
                $table->dropForeign(['age_range_id']);
                $table->dropColumn('age_range_id');
            }

            if (Schema::hasColumn('book_templates', 'story_goal_id')) {
                $table->dropForeign(['story_goal_id']);
                $table->dropColumn('story_goal_id');
            }

            if (! Schema::hasColumn('book_templates', 'template_type')) {
                $table->string('template_type')->default('story')->after('is_free');
            }

            if (! Schema::hasColumn('book_templates', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('template_type');
            }
        });

        Schema::table('template_scenes', function (Blueprint $table) {
            if (! Schema::hasColumn('template_scenes', 'scene_instruction')) {
                $table->text('scene_instruction')->nullable()->after('scene_number');
            }

            if (Schema::hasColumn('template_scenes', 'text_template')) {
                $table->dropColumn('text_template');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('template_scenes', function (Blueprint $table) {
            if (! Schema::hasColumn('template_scenes', 'text_template')) {
                $table->text('text_template')->nullable()->after('scene_number');
            }

            if (Schema::hasColumn('template_scenes', 'scene_instruction')) {
                $table->dropColumn('scene_instruction');
            }
        });

        Schema::table('book_templates', function (Blueprint $table) {
            if (Schema::hasColumn('book_templates', 'template_type')) {
                $table->dropColumn('template_type');
            }

            if (Schema::hasColumn('book_templates', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (! Schema::hasColumn('book_templates', 'age_range_id')) {
                $table->foreignId('age_range_id')->nullable()->constrained('age_ranges')->onDelete('restrict');
            }

            if (! Schema::hasColumn('book_templates', 'story_goal_id')) {
                $table->foreignId('story_goal_id')->nullable()->constrained('story_goals')->onDelete('restrict');
            }
        });
    }
};
