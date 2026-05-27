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
        Schema::create('story_prompt_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_prompt_id')->constrained('story_prompts')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->unsignedTinyInteger('rating');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['story_prompt_id', 'rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('story_prompt_ratings');
    }
};
