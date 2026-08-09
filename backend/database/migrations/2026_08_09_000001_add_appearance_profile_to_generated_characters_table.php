<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generated_characters', function (Blueprint $table) {
            $table->text('appearance_profile')->nullable()->after('style_bible');
        });
    }

    public function down(): void
    {
        Schema::table('generated_characters', function (Blueprint $table) {
            $table->dropColumn('appearance_profile');
        });
    }
};
