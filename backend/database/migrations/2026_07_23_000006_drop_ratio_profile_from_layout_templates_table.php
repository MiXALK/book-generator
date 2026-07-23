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
        Schema::table('layout_templates', function (Blueprint $table) {
            $table->dropColumn('ratio_profile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('layout_templates', function (Blueprint $table) {
            $table->string('ratio_profile')->default('80_20')->after('title');
        });
    }
};
