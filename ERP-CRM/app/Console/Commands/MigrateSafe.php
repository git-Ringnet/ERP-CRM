<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateSafe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:safe {--seed : Seed the database after migrating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations sequentially with a delay to prevent Windows/Antivirus sharing violations and file locking.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("==================================================");
        $this->info("             SAFE MIGRATION RUNNER                ");
        $this->info("==================================================");

        // 1. Suggest Windows Defender exclusion
        $this->comment("TIP: To permanently fix this issue on Windows, open PowerShell as Administrator and run:");
        $this->line("Add-MpPreference -ExclusionPath '" . realpath(database_path('..')) . "', 'C:\\xampp\\mysql\\data'");
        $this->newLine();

        // 2. Try to stop Apache to prevent incoming request locks
        $this->info("Checking for running Apache server...");
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('taskkill /F /IM httpd.exe 2>NUL', $output, $exitCode);
            if ($exitCode === 0) {
                $this->warn("Stopped running Apache server (httpd.exe) to prevent database locks.");
            }
        }

        // 3. Wipe database (drop all tables & views)
        $this->info("Wiping database...");
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            $dbName = DB::getDatabaseName();
            
            // Drop views
            $views = DB::select("SELECT table_name FROM information_schema.views WHERE table_schema = ?", [$dbName]);
            foreach ($views as $view) {
                $viewName = $view->table_name;
                $this->line("Dropping view: $viewName");
                try {
                    DB::statement("DROP VIEW IF EXISTS `$viewName`");
                } catch (\Exception $e) {}
            }

            // Drop tables
            $tables = DB::select('SHOW TABLES');
            $key = "Tables_in_" . $dbName;
            foreach ($tables as $table) {
                if (isset($table->$key)) {
                    $tableName = $table->$key;
                    $this->line("Dropping table: $tableName");
                    try {
                        DB::statement("DROP TABLE IF EXISTS `$tableName`");
                    } catch (\Exception $e) {
                        try {
                            DB::statement("DROP VIEW IF EXISTS `$tableName`");
                        } catch (\Exception $e2) {
                            $this->error("Failed to drop $tableName: " . $e2->getMessage());
                        }
                    }
                }
            }
            
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            $this->info("All tables and views dropped successfully.");
        } catch (\Exception $e) {
            $this->error("Failed to drop tables: " . $e->getMessage());
            return 1;
        }

        // Recreate migration table
        $this->call('migrate:install');

        // 4. Get migration files
        $migrationsDir = database_path('migrations');
        $files = glob($migrationsDir . '/*.php');
        sort($files);

        $total = count($files);
        $this->info("Found $total migration files. Starting sequential migrations...");

        // 5. Run each migration file one by one with a delay
        foreach ($files as $index => $file) {
            $filename = basename($file);
            $this->line("[" . ($index + 1) . "/$total] Migrating: $filename");

            $exitCode = $this->call('migrate', [
                '--path' => 'database/migrations/' . $filename,
                '--force' => true,
            ]);

            if ($exitCode !== 0) {
                $this->error("Failed migrating: $filename");
                return $exitCode;
            }

            // 1.2s delay to allow Windows Defender to complete file scanning and release locks
            usleep(1200000);
        }

        $this->info("All migrations completed successfully!");

        // 6. Run seeders if requested
        if ($this->option('seed')) {
            $this->info("Running database seeders...");
            $exitCode = $this->call('db:seed');
            if ($exitCode !== 0) {
                $this->error("Database seeding failed.");
                return $exitCode;
            }
            $this->info("Database seeded successfully.");
        }

        return 0;
    }
}
