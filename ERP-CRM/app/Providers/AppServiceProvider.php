<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
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
        // Use custom Tailwind pagination view
        \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.tailwind');
        \Illuminate\Pagination\Paginator::defaultSimpleView('vendor.pagination.simple-tailwind');

        // Apply email settings from database
        if (Schema::hasTable('settings')) {
            Setting::applyEmailConfig();
        }
    }
}
