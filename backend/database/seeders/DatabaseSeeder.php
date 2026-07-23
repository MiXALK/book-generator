<?php

namespace Database\Seeders;

use App\Services\TemplateCatalogCacheService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            StoryGoalSeeder::class,
            BookTemplateSeeder::class,
            StoryPromptSeeder::class,
            LayoutTemplateSeeder::class,
        ]);

        app(TemplateCatalogCacheService::class)->bumpVersion();
    }
}
