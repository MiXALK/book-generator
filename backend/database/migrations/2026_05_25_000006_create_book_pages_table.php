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
        Schema::create('book_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_generation_id')->constrained('book_generations')->onDelete('cascade');
            $table->unsignedBigInteger('layout_template_id')->nullable();
            $table->integer('page_number');
            $table->string('text', 255); // The personalized compiled text
            $table->string('image_url')->nullable(); // Local storage, S3, or MinIO path
            $table->timestamps();

            $table->unique(['book_generation_id', 'page_number']);
            $table->index('layout_template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_pages');
    }
};
