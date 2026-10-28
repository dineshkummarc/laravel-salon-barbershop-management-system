<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use App\Support\BusinessContext;
use Drutogoti\DrutogotiServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(DrutogotiServiceProvider::class);
        $this->app->scoped(BusinessContext::class, fn () => new BusinessContext);
    }

    public function boot(): void
    {
		Schema::defaultStringLength(191);
        config(['ai-hub.settings.ui_enabled' => false]);
        RateLimiter::for('public-write', fn ($request) => Limit::perMinute(15)->by($request->ip()));
        RateLimiter::for('login', fn ($request) => Limit::perMinute(5)->by(strtolower($request->input('email', '')).'|'.$request->ip()));
        Gate::define('manage-business', fn ($user) => in_array($user->role, ['owner', 'admin', 'platform']));
    }
}
