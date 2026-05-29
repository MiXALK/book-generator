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
            if (! Schema::hasColumn('book_generations', 'story_prompt_id')) {
                $table->unsignedBigInteger('story_prompt_id')->nullable()->after('book_template_id');
                $table->index('story_prompt_id');
            }

            if (! Schema::hasColumn('book_generations', 'prompt_snapshot')) {
                $table->longText('prompt_snapshot')->nullable()->after('child_goal');
            }
        });

        Schema::table('book_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('book_pages', 'layout_template_id')) {
                $table->unsignedBigInteger('layout_template_id')->nullable()->after('book_generation_id');
                $table->index('layout_template_id');
            }
        });

        Schema::table('book_generations', function (Blueprint $table) {
            if (! $this->foreignKeyExists('book_generations', 'book_generations_story_prompt_id_foreign')) {
                $table->foreign('story_prompt_id')->references('id')->on('story_prompts')->nullOnDelete();
            }
        });

        Schema::table('book_pages', function (Blueprint $table) {
            if (! $this->foreignKeyExists('book_pages', 'book_pages_layout_template_id_foreign')) {
                $table->foreign('layout_template_id')->references('id')->on('layout_templates')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('book_pages', function (Blueprint $table) {
            if ($this->foreignKeyExists('book_pages', 'book_pages_layout_template_id_foreign')) {
                $table->dropForeign(['layout_template_id']);
            }
        });

        Schema::table('book_generations', function (Blueprint $table) {
            if ($this->foreignKeyExists('book_generations', 'book_generations_story_prompt_id_foreign')) {
                $table->dropForeign(['story_prompt_id']);
            }
        });
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $result = Schema::getConnection()->selectOne(
            'SELECT 1 FROM information_schema.table_constraints WHERE constraint_name = ? AND table_name = ?',
            [$constraintName, $table]
        );

        return $result !== null;
    }
};
