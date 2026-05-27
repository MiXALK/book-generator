<?php

namespace Database\Seeders;

use App\Models\AgeRange;
use Illuminate\Database\Seeder;

class AgeRangeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ranges = [
            ['label' => '2-4 года / 2-4 years', 'min_age' => 2, 'max_age' => 4],
            ['label' => '5-7 лет / 5-7 years', 'min_age' => 5, 'max_age' => 7],
        ];

        foreach ($ranges as $range) {
            AgeRange::query()->updateOrCreate(
                ['label' => $range['label']],
                ['min_age' => $range['min_age'], 'max_age' => $range['max_age']],
            );
        }
    }
}
