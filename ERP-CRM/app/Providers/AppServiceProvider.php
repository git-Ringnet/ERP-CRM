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

        // Apply email settings from database
        if (Schema::hasTable('settings')) {
            Setting::applyEmailConfig();
        }

        // Register Blade directives for RBAC
        $this->registerBladeDirectives();

        // Prevent Windows file locking issues and clear Doctrine cache during migrations in local development
        if ($this->app->runningInConsole() && $this->app->environment('local') && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $previousTables = [];

            \Illuminate\Support\Facades\Event::listen(
                \Illuminate\Database\Events\MigrationStarted::class,
                function ($event) {
                    \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                }
            );

            \Illuminate\Support\Facades\Event::listen(
                \Illuminate\Database\Events\MigrationEnded::class,
                function ($event) use (&$previousTables) {
                    \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                    \Illuminate\Support\Facades\DB::disconnect();

                    $reflector = new \ReflectionClass($event->migration);
                    $filePath = $reflector->getFileName();
                    $currentWords = [];

                    if ($filePath && file_exists($filePath)) {
                        $content = strtolower(file_get_contents($filePath));
                        // Find all words of length >= 4 matching [a-z0-9_]+
                        if (preg_match_all('/[a-z0-9_]{4,}/', $content, $matches)) {
                            $ignoreWords = [
                                'php', 'use', 'class', 'extends', 'migration', 'function', 'void', 'public', 
                                'schema', 'table', 'blueprint', 'create', 'update', 'fields', 'added', 'adding', 
                                'column', 'columns', 'to', 'add', 'change', 'modify', 'drop', 'make', 'items', 
                                'with', 'from', 'anonymous', 'info', 'value', 'values', 'data', 'defaults', 
                                'default', 'decimal', 'nullable', 'string', 'integer', 'boolean', 'text', 'date', 
                                'datetime', 'timestamps', 'statement', 'alter', 'engine', 'unsigned', 'null', 
                                'foreign', 'references', 'on', 'delete', 'cascade', 'set', 'illuminate', 'database', 
                                'migrations', 'support', 'facades', 'after', 'comment', 'return', 'const', 'static'
                            ];
                            $currentWords = array_diff(array_unique($matches[0]), $ignoreWords);
                        }
                    }

                    $shouldLongSleep = false;
                    if (!empty($previousTables) && !empty($currentWords)) {
                        $shared = array_intersect($previousTables, $currentWords);
                        if (!empty($shared)) {
                            $shouldLongSleep = true;
                        }
                    }

                    $previousTables = $currentWords;

                    if ($shouldLongSleep) {
                        usleep(3500000); // 3.5 seconds safety delay for Windows Defender to release lock on same table
                    } else {
                        usleep(100000); // 0.1 second quick delay for other migrations
                    }
                }
            );
        }
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
