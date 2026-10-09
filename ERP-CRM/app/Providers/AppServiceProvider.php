<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register CacheService
        $this->app->singleton(\App\Services\CacheServiceInterface::class, \App\Services\CacheService::class);

        // Register AuditService
        $this->app->singleton(\App\Services\AuditServiceInterface::class, \App\Services\AuditService::class);

        // Register PermissionService
        $this->app->singleton(\App\Services\PermissionServiceInterface::class, \App\Services\PermissionService::class);

        // Register RoleService
        $this->app->singleton(\App\Services\RoleServiceInterface::class, \App\Services\RoleService::class);

        // Register DashboardService
        $this->app->singleton(\App\Services\DashboardService::class);

        // Register MetricsCalculationService
        $this->app->singleton(\App\Services\MetricsCalculationService::class);

        // Register SidebarBadgeService
        $this->app->singleton(\App\Services\SidebarBadgeService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ép buộc toàn bộ link phải chạy HTTPS
        // if ($this->app->environment('production') || true) {
        //     URL::forceScheme('https');
        // }
        // Use custom Tailwind pagination view
        \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.tailwind');
        \Illuminate\Pagination\Paginator::defaultSimpleView('vendor.pagination.simple-tailwind');

        // Share sidebar badges with layouts.app
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            if (auth()->check()) {
                $sidebarBadgeService = app(\App\Services\SidebarBadgeService::class);
                $view->with('sidebarBadges', $sidebarBadgeService->getBadges(auth()->user()));
            } else {
                $view->with('sidebarBadges', []);
            }
        });

        // Apply email settings from database (uses cached query internally)
        try {
            if (Schema::hasTable('settings')) {
                Setting::applyEmailConfig();
            }
        } catch (\Throwable $e) {
            // Ignore database query errors during application boot (e.g. fresh migration or missing tables)
        }

        // Register Blade directives for RBAC
        $this->registerBladeDirectives();


    }

    /**
     * Register custom Blade directives for role-based access control.
     */
    protected function registerBladeDirectives(): void
    {
        // @can directive - check if user has a specific permission or passes policy ability
        Blade::if('can', function (string $permission, ...$arguments) {
            if (!auth()->check()) {
                return false;
            }
            return empty($arguments) 
                ? auth()->user()->can($permission) 
                : auth()->user()->can($permission, count($arguments) === 1 ? $arguments[0] : $arguments);
        });

        // @canany directive - check if user has any of the specified permissions
        Blade::if('canany', function (array $permissions, ...$arguments) {
            if (!auth()->check()) {
                return false;
            }
            return empty($arguments)
                ? collect($permissions)->some(fn($p) => auth()->user()->can($p))
                : collect($permissions)->some(fn($p) => auth()->user()->can($p, count($arguments) === 1 ? $arguments[0] : $arguments));
        });

        // @role directive - check if user has a specific role
        Blade::if('role', function (string $roleName) {
            return auth()->check() && auth()->user()->hasRole($roleName);
        });

        // @hasrole directive - alias for @role directive
        Blade::if('hasrole', function (string $roleName) {
            return auth()->check() && auth()->user()->hasRole($roleName);
        });
    }
}
