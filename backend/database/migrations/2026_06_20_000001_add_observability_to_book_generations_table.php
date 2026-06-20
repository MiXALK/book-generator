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
        Schema::table('book_generations', function (Blueprint $table) {
            $table->uuid('correlation_id')->nullable()->unique()->after('id');
            $table->unsignedInteger('text_duration_ms')->nullable()->after('error_message');
            $table->unsignedInteger('layout_duration_ms')->nullable()->after('text_duration_ms');
            $table->unsignedInteger('image_duration_ms')->nullable()->after('layout_duration_ms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('book_generations', function (Blueprint $table) {
            $table->dropColumn([
                'correlation_id',
                'text_duration_ms',
                'layout_duration_ms',
                'image_duration_ms',
            ]);
        });
    }
};
