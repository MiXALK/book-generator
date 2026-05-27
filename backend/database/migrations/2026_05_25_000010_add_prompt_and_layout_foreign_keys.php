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
        Schema::table('book_generations', function (Blueprint $table) {
            $table->foreign('story_prompt_id')->references('id')->on('story_prompts')->nullOnDelete();
        });

        Schema::table('book_pages', function (Blueprint $table) {
            $table->foreign('layout_template_id')->references('id')->on('layout_templates')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('book_pages', function (Blueprint $table) {
            $table->dropForeign(['layout_template_id']);
        });

        Schema::table('book_generations', function (Blueprint $table) {
            $table->dropForeign(['story_prompt_id']);
        });
    }
};
