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
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('email');
            $table->string('avatar_url')->nullable()->after('google_id');
            $table->string('plan')->default('free')->after('avatar_url');
            $table->string('subscription_status')->default('inactive')->after('plan');
            $table->string('api_token', 80)->nullable()->unique()->after('remember_token');
            $table->timestamp('api_token_expires_at')->nullable()->after('api_token');

            // For OAuth we don't strictly require local password, so make it nullable
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();

            $table->dropColumn([
                'google_id',
                'avatar_url',
                'plan',
                'subscription_status',
                'api_token',
                'api_token_expires_at',
            ]);
        });
    }
};
