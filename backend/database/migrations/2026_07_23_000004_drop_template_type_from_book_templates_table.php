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
        Schema::table('book_templates', function (Blueprint $table) {
            $table->dropColumn('template_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('book_templates', function (Blueprint $table) {
            $table->string('template_type')->default('story')->after('is_free');
        });
    }
};
