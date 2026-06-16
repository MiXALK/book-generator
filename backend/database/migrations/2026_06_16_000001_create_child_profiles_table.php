<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('child_name');
            $table->unsignedTinyInteger('child_age')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'child_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_profiles');
    }
};
