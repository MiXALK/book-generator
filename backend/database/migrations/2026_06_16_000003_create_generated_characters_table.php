<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_profile_id')->constrained('child_profiles')->onDelete('cascade');
            $table->foreignId('uploaded_photo_id')->nullable()->constrained('uploaded_photos')->nullOnDelete();
            $table->string('storage_path')->nullable();
            $table->text('style_bible');
            $table->timestamps();

            $table->unique('child_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_characters');
    }
};
