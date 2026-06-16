<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_generations', function (Blueprint $table) {
            $table->foreignId('child_profile_id')->nullable()->after('story_prompt_id')->constrained('child_profiles')->nullOnDelete();
            $table->foreignId('uploaded_photo_id')->nullable()->after('child_profile_id')->constrained('uploaded_photos')->nullOnDelete();
            $table->foreignId('generated_character_id')->nullable()->after('uploaded_photo_id')->constrained('generated_characters')->nullOnDelete();
            $table->string('illustration_status')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('book_generations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('generated_character_id');
            $table->dropConstrainedForeignId('uploaded_photo_id');
            $table->dropConstrainedForeignId('child_profile_id');
            $table->dropColumn('illustration_status');
        });
    }
};
