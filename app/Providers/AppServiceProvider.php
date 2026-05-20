<?php

namespace App\Providers;

use App\Models\Problem;
use App\Models\UserProfile;
use App\Observers\ProblemObserver;
use App\Observers\UserProfileObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        UserProfile::observe(UserProfileObserver::class);
        Problem::observe(ProblemObserver::class);
    }
}
