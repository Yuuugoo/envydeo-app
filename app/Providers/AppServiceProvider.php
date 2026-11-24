<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One social graph per request/job, so memoised follow lists never leak between requests.
        $this->app->scoped(\App\Services\SocialGraph::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Admins = emails listed in ADMIN_EMAILS: Movie Night stats and the reports/moderation queue.
        $isAdmin = fn (User $user) => in_array(strtolower($user->email), config('envydeo.admin_emails', []), true);
        Gate::define('view-movie-night-stats', $isAdmin);
        Gate::define('moderate', $isAdmin);
    }
}
