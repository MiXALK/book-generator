<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_generations', function (Blueprint $table) {
            $table->string('idempotency_key')->nullable()->after('correlation_id');
            $table->string('input_fingerprint', 64)->nullable()->after('idempotency_key');
            $table->text('story_text')->nullable()->after('prompt_snapshot');
            $table->json('cost_breakdown')->nullable()->after('image_duration_ms');
            $table->decimal('total_cost_usd', 10, 6)->nullable()->after('cost_breakdown');

            $table->index('input_fingerprint');
            $table->unique(['user_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::table('book_generations', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'idempotency_key']);
            $table->dropIndex(['input_fingerprint']);
            $table->dropColumn([
                'idempotency_key',
                'input_fingerprint',
                'story_text',
                'cost_breakdown',
                'total_cost_usd',
            ]);
        });
    }
};
