<?php

namespace App\Providers;

use App\Listeners\LogFailedQueueJob;
use App\Repositories\Contracts\BookGenerationRepositoryInterface;
use App\Repositories\Contracts\BookPageRepositoryInterface;
use App\Repositories\Contracts\BookTemplateRepositoryInterface;
use App\Repositories\Contracts\ChildProfileRepositoryInterface;
use App\Repositories\Contracts\GeneratedCharacterRepositoryInterface;
use App\Repositories\Contracts\LayoutTemplateRepositoryInterface;
use App\Repositories\Contracts\StoryGoalRepositoryInterface;
use App\Repositories\Contracts\StoryPromptRepositoryInterface;
use App\Repositories\Contracts\UploadedPhotoRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentBookGenerationRepository;
use App\Repositories\Eloquent\EloquentBookPageRepository;
use App\Repositories\Eloquent\EloquentBookTemplateRepository;
use App\Repositories\Eloquent\EloquentChildProfileRepository;
use App\Repositories\Eloquent\EloquentGeneratedCharacterRepository;
use App\Repositories\Eloquent\EloquentLayoutTemplateRepository;
use App\Repositories\Eloquent\EloquentStoryGoalRepository;
use App\Repositories\Eloquent\EloquentStoryPromptRepository;
use App\Repositories\Eloquent\EloquentUploadedPhotoRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Services\Ai\Contracts\IllustrationGenerationProviderInterface;
use App\Services\Ai\Contracts\StoryTextGenerationProviderInterface;
use App\Services\Ai\IllustrationGenerationProviderFactory;
use App\Services\Ai\StoryTextGenerationProviderFactory;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->app->bind(BookTemplateRepositoryInterface::class, EloquentBookTemplateRepository::class);
        $this->app->bind(BookGenerationRepositoryInterface::class, EloquentBookGenerationRepository::class);
        $this->app->bind(BookPageRepositoryInterface::class, EloquentBookPageRepository::class);
        $this->app->bind(ChildProfileRepositoryInterface::class, EloquentChildProfileRepository::class);
        $this->app->bind(UploadedPhotoRepositoryInterface::class, EloquentUploadedPhotoRepository::class);
        $this->app->bind(GeneratedCharacterRepositoryInterface::class, EloquentGeneratedCharacterRepository::class);
        $this->app->bind(StoryPromptRepositoryInterface::class, EloquentStoryPromptRepository::class);
        $this->app->bind(LayoutTemplateRepositoryInterface::class, EloquentLayoutTemplateRepository::class);

        $this->app->singleton(StoryTextGenerationProviderInterface::class, function ($app) {
            return $app->make(StoryTextGenerationProviderFactory::class)->make();
        });

        $this->app->singleton(IllustrationGenerationProviderInterface::class, function ($app) {
            return $app->make(IllustrationGenerationProviderFactory::class)->make();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(JobFailed::class, LogFailedQueueJob::class);

        RateLimiter::for('books-generate', function (Request $request) {
            $userId = $request->user()?->id;

            return Limit::perMinute(3)->by($userId !== null ? 'user:'.$userId : $request->ip());
        });

        RateLimiter::for('photos-upload', function (Request $request) {
            $userId = $request->user()?->id;

            return Limit::perMinute(5)->by($userId !== null ? 'user:'.$userId : $request->ip());
        });
    }
}
