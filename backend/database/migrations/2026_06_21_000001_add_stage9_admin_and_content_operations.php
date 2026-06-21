<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->after('language');
        });

        foreach (['book_templates', 'story_prompts', 'layout_templates'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('publication_status', 20)->default('published')->after('is_active');
                $table->unsignedInteger('version')->default(1)->after('publication_status');
            });
        }

        Schema::table('book_generations', function (Blueprint $table) {
            $table->json('book_template_snapshot')->nullable()->after('prompt_snapshot');
        });

        Schema::create('book_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_template_id')->constrained('book_templates')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->timestamp('published_at');
            $table->timestamps();

            $table->unique(['book_template_id', 'version']);
        });

        Schema::create('story_prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_prompt_id')->constrained('story_prompts')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->timestamp('published_at');
            $table->timestamps();

            $table->unique(['story_prompt_id', 'version']);
        });

        Schema::create('layout_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('layout_template_id')->constrained('layout_templates')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->timestamp('published_at');
            $table->timestamps();

            $table->unique(['layout_template_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layout_template_versions');
        Schema::dropIfExists('story_prompt_versions');
        Schema::dropIfExists('book_template_versions');

        Schema::table('book_generations', function (Blueprint $table) {
            $table->dropColumn('book_template_snapshot');
        });

        foreach (['layout_templates', 'story_prompts', 'book_templates'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['publication_status', 'version']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
