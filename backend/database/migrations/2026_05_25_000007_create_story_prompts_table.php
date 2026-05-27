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
        Schema::create('story_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('prompt_text');
            $table->string('language', 2)->default('ru');
            $table->foreignId('age_range_id')->nullable()->constrained('age_ranges')->onDelete('set null');
            $table->foreignId('story_goal_id')->nullable()->constrained('story_goals')->onDelete('set null');
            $table->decimal('quality_score', 4, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('usage_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_prompts');
    }
};
