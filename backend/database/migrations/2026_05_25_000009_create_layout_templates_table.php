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
        Schema::create('layout_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->string('category'); // cover, content, ending
            $table->string('ratio_profile')->default('80_20');
            $table->string('text_position'); // top, left, right, bottom, overlay
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layout_templates');
    }
};
