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
        Schema::create('template_scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_template_id')->constrained('book_templates')->onDelete('cascade');
            $table->integer('scene_number');
            $table->text('text_template');
            $table->text('image_prompt_template');
            $table->timestamps();

            // Ensure page/scene number is unique per template
            $table->unique(['book_template_id', 'scene_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_scenes');
    }
};
