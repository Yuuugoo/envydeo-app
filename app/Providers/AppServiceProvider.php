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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Internal Movie Night funnel stats: only for emails listed in ADMIN_EMAILS.
        Gate::define('view-movie-night-stats', fn (User $user) => in_array(
            strtolower($user->email),
            config('envydeo.admin_emails', []),
            true
        ));
    }
}
