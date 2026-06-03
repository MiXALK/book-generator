<?php

namespace App\Providers;

use App\Repositories\Contracts\AgeRangeRepositoryInterface;
use App\Repositories\Contracts\BookGenerationRepositoryInterface;
use App\Repositories\Contracts\BookPageRepositoryInterface;
use App\Repositories\Contracts\BookTemplateRepositoryInterface;
use App\Repositories\Contracts\LayoutTemplateRepositoryInterface;
use App\Repositories\Contracts\StoryGoalRepositoryInterface;
use App\Repositories\Contracts\StoryPromptRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentAgeRangeRepository;
use App\Repositories\Eloquent\EloquentBookGenerationRepository;
use App\Repositories\Eloquent\EloquentBookPageRepository;
use App\Repositories\Eloquent\EloquentBookTemplateRepository;
use App\Repositories\Eloquent\EloquentLayoutTemplateRepository;
use App\Repositories\Eloquent\EloquentStoryGoalRepository;
use App\Repositories\Eloquent\EloquentStoryPromptRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Services\Ai\Contracts\StoryTextGenerationProviderInterface;
use App\Services\Ai\StoryTextGenerationProviderFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(StoryGoalRepositoryInterface::class, EloquentStoryGoalRepository::class);
        $this->app->bind(AgeRangeRepositoryInterface::class, EloquentAgeRangeRepository::class);
        $this->app->bind(BookTemplateRepositoryInterface::class, EloquentBookTemplateRepository::class);
        $this->app->bind(BookGenerationRepositoryInterface::class, EloquentBookGenerationRepository::class);
        $this->app->bind(BookPageRepositoryInterface::class, EloquentBookPageRepository::class);
        $this->app->bind(StoryPromptRepositoryInterface::class, EloquentStoryPromptRepository::class);
        $this->app->bind(LayoutTemplateRepositoryInterface::class, EloquentLayoutTemplateRepository::class);

        $this->app->singleton(StoryTextGenerationProviderInterface::class, function ($app) {
            return $app->make(StoryTextGenerationProviderFactory::class)->make();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
