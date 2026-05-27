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
        Schema::create('book_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('book_template_id')->constrained('book_templates')->onDelete('restrict');
            $table->unsignedBigInteger('story_prompt_id')->nullable();
            $table->string('child_name');
            $table->integer('child_age');
            $table->string('child_goal');
            $table->longText('prompt_snapshot')->nullable();
            $table->string('status')->default('draft'); // draft, queued, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('story_prompt_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_generations');
    }
};
