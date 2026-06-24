<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('child_profiles', function (Blueprint $table) {
            $table->string('child_gender', 4)->nullable()->after('child_age');
        });

        Schema::table('book_generations', function (Blueprint $table) {
            $table->string('child_gender', 4)->nullable()->after('child_age');
        });
    }

    public function down(): void
    {
        Schema::table('book_generations', function (Blueprint $table) {
            $table->dropColumn('child_gender');
        });

        Schema::table('child_profiles', function (Blueprint $table) {
            $table->dropColumn('child_gender');
        });
    }
};
