<?php

use App\Enums\AgeRange;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('story_prompts', function (Blueprint $table) {
            $table->string('age_range')->nullable()->after('language');
        });

        $rows = DB::table('story_prompts')
            ->join('age_ranges', 'story_prompts.age_range_id', '=', 'age_ranges.id')
            ->select('story_prompts.id', 'age_ranges.min_age', 'age_ranges.max_age')
            ->get();

        foreach ($rows as $row) {
            $ageRange = AgeRange::fromBounds((int) $row->min_age, (int) $row->max_age);

            if ($ageRange === null) {
                continue;
            }

            DB::table('story_prompts')
                ->where('id', $row->id)
                ->update(['age_range' => $ageRange->value]);
        }

        Schema::table('story_prompts', function (Blueprint $table) {
            $table->dropForeign(['age_range_id']);
            $table->dropColumn('age_range_id');
        });

        Schema::dropIfExists('age_ranges');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('age_ranges', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique();
            $table->integer('min_age');
            $table->integer('max_age');
            $table->timestamps();
        });

        $now = now();

        foreach (AgeRange::cases() as $case) {
            DB::table('age_ranges')->insert([
                'label' => match ($case) {
                    AgeRange::Toddler => '2-4 года / 2-4 years',
                    AgeRange::EarlyReader => '5-7 лет / 5-7 years',
                },
                'min_age' => $case->minAge(),
                'max_age' => $case->maxAge(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('story_prompts', function (Blueprint $table) {
            $table->foreignId('age_range_id')->nullable()->after('language')->constrained('age_ranges')->onDelete('set null');
        });

        $prompts = DB::table('story_prompts')
            ->whereNotNull('age_range')
            ->select('id', 'age_range')
            ->get();

        foreach ($prompts as $prompt) {
            $case = AgeRange::tryFrom($prompt->age_range);

            if ($case === null) {
                continue;
            }

            $ageRangeId = DB::table('age_ranges')
                ->where('min_age', $case->minAge())
                ->where('max_age', $case->maxAge())
                ->value('id');

            if ($ageRangeId !== null) {
                DB::table('story_prompts')
                    ->where('id', $prompt->id)
                    ->update(['age_range_id' => $ageRangeId]);
            }
        }

        Schema::table('story_prompts', function (Blueprint $table) {
            $table->dropColumn('age_range');
        });
    }
};
