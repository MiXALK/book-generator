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
            $table->foreignId('story_goal_id')
                ->nullable()
                ->after('is_active')
                ->constrained('story_goals')
                ->nullOnDelete();

            $table->unique('story_goal_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('book_templates', function (Blueprint $table) {
            $table->dropForeign(['story_goal_id']);
            $table->dropUnique(['story_goal_id']);
            $table->dropColumn('story_goal_id');
        });
    }
};
