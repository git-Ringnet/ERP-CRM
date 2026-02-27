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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // // Ép buộc toàn bộ link phải chạy HTTPS
        // if ($this->app->environment('production') || true) {
        //     URL::forceScheme('https');
        // }
        // Use custom Tailwind pagination view
        \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.tailwind');
        \Illuminate\Pagination\Paginator::defaultSimpleView('vendor.pagination.simple-tailwind');

        // Apply email settings from database
        if (Schema::hasTable('settings')) {
            Setting::applyEmailConfig();
        }

        // Register Blade directives for RBAC
        $this->registerBladeDirectives();
    }

    /**
     * Register custom Blade directives for role-based access control.
     */
    protected function registerBladeDirectives(): void
    {
        // @can directive - check if user has a specific permission
        Blade::if('can', function (string $permission) {
            return auth()->check() && auth()->user()->can($permission);
        });

        // @canany directive - check if user has any of the specified permissions
        Blade::if('canany', function (array $permissions) {
            return auth()->check() && collect($permissions)->some(fn($p) => auth()->user()->can($p));
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
