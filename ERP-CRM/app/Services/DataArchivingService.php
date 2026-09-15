<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DataArchivingService
{
    protected string $mainConnection = 'mysql';
    protected string $archiveConnection = 'mysql_archive';

    /**
     * Get the name of the main and archive databases.
     */
    public function getMainDbName(): string
    {
        return config('database.connections.mysql.database', '');
    }

    public function getArchiveDbName(): string
    {
        return config('database.connections.mysql_archive.database', $this->getMainDbName() . '_archive');
    }

    /**
     * Check if archive database connection is healthy.
     * If the database does not exist, attempt to create it.
     */
    public function checkArchiveConnection(): array
    {
        $archiveDb = $this->getArchiveDbName();

        try {
            // Attempt direct connection
            DB::connection($this->archiveConnection)->getPdo();
            return [
                'status' => true,
                'message' => "Kết nối CSDL Lưu trữ '{$archiveDb}' thành công.",
                'db_name' => $archiveDb,
            ];
        } catch (\Exception $e) {
            // If connection fails because database doesn't exist, try to create it via main connection
            try {
                DB::connection($this->mainConnection)->statement("CREATE DATABASE IF NOT EXISTS `{$archiveDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // Retry connection
                DB::connection($this->archiveConnection)->getPdo();
                return [
                    'status' => true,
                    'message' => "Đã tự động khởi tạo CSDL Lưu trữ '{$archiveDb}' thành công.",
                    'db_name' => $archiveDb,
                ];
            } catch (\Exception $createEx) {
                return [
                    'status' => false,
                    'message' => "Không thể kết nối hoặc khởi tạo CSDL Lưu trữ '{$archiveDb}': " . $createEx->getMessage(),
                    'db_name' => $archiveDb,
                ];
            }
        }
    }

    /**
     * Synchronize table schemas from Main DB to Archive DB.
     */
    public function syncArchiveSchema(): void
    {
        $mainDb = $this->getMainDbName();
        $archiveDb = $this->getArchiveDbName();

        // Get list of tables in main database
        $tables = DB::connection($this->mainConnection)->select("SHOW TABLES");
        $tableKey = "Tables_in_{$mainDb}";

        foreach ($tables as $tableObj) {
            $tableName = $tableObj->$tableKey ?? array_values((array)$tableObj)[0];
            
            // Create table in archive DB if not exists matching structure of main DB
            DB::statement("CREATE TABLE IF NOT EXISTS `{$archiveDb}`.`{$tableName}` LIKE `{$mainDb}`.`{$tableName}`");
        }
    }

    /**
     * Synchronize master data (Customers, Suppliers, Products, Users, Warehouses) to Archive DB.
     */
    public function syncMasterDataToArchive(): void
    {
        $mainDb = $this->getMainDbName();
        $archiveDb = $this->getArchiveDbName();

        $masterTables = [
            'users',
            'customers',
            'suppliers',
            'products',
            'product_items',
            'warehouses',
            'cost_formulas',
            'transaction_categories',
            'currencies',
            'settings',
        ];

        foreach ($masterTables as $table) {
            if (Schema::connection($this->mainConnection)->hasTable($table) && Schema::connection($this->archiveConnection)->hasTable($table)) {
                DB::statement("INSERT IGNORE INTO `{$archiveDb}`.`{$table}` SELECT * FROM `{$mainDb}`.`{$table}`");
            }
        }
    }

    /**
     * Get statistics of records grouped by year.
     */
    public function getYearlyStats(): array
    {
        $yearsRange = [];
        $currentYear = (int) date('Y');

        // Find the oldest record year across sales, quotations, financial transactions
        $oldestSale = DB::table('sales')->min('created_at');
        $oldestYear = $oldestSale ? (int) date('Y', strtotime($oldestSale)) : ($currentYear - 3);

        if ($oldestYear > $currentYear - 2) {
            $oldestYear = $currentYear - 2;
        }

        $archiveConn = $this->checkArchiveConnection();
        $archiveAccessible = $archiveConn['status'];
        $archiveDb = $this->getArchiveDbName();

        $stats = [];
        for ($year = $currentYear; $year >= $oldestYear; $year--) {
            $startDate = "{$year}-01-01 00:00:00";
            $endDate = "{$year}-12-31 23:59:59";

            // Live counts
            $salesCount = DB::table('sales')->whereBetween('created_at', [$startDate, $endDate])->count();
            $salesTotal = 0;
            if (Schema::hasColumn('sales', 'total')) {
                $salesTotal = DB::table('sales')->whereBetween('created_at', [$startDate, $endDate])->sum('total') ?? 0;
            } elseif (Schema::hasColumn('sales', 'total_amount')) {
                $salesTotal = DB::table('sales')->whereBetween('created_at', [$startDate, $endDate])->sum('total_amount') ?? 0;
            }

            $quotationsCount = Schema::hasTable('quotations') ? DB::table('quotations')->whereBetween('created_at', [$startDate, $endDate])->count() : 0;
            $importsCount = Schema::hasTable('imports') ? DB::table('imports')->whereBetween('created_at', [$startDate, $endDate])->count() : 0;
            $exportsCount = Schema::hasTable('exports') ? DB::table('exports')->whereBetween('created_at', [$startDate, $endDate])->count() : 0;
            $transactionsCount = Schema::hasTable('financial_transactions') ? DB::table('financial_transactions')->whereBetween('created_at', [$startDate, $endDate])->count() : 0;
            $activityLogsCount = Schema::hasTable('activity_logs') ? DB::table('activity_logs')->whereBetween('created_at', [$startDate, $endDate])->count() : 0;

            // Archive counts if available
            $archivedSalesCount = 0;
            if ($archiveAccessible && Schema::connection($this->archiveConnection)->hasTable('sales')) {
                $archivedSalesCount = DB::connection($this->archiveConnection)
                    ->table('sales')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();
            }

            $isSafeToArchive = ($year < $currentYear); // All completed past years can be archived if user wants

            $stats[$year] = [
                'year' => $year,
                'sales_count' => $salesCount,
                'sales_total' => $salesTotal,
                'quotations_count' => $quotationsCount,
                'imports_count' => $importsCount,
                'exports_count' => $exportsCount,
                'transactions_count' => $transactionsCount,
                'activity_logs_count' => $activityLogsCount,
                'total_live_records' => $salesCount + $quotationsCount + $importsCount + $exportsCount + $transactionsCount + $activityLogsCount,
                'archived_sales_count' => $archivedSalesCount,
                'is_safe_to_archive' => $isSafeToArchive,
                'is_current_year' => ($year === $currentYear),
            ];
        }

        return $stats;
    }

    /**
     * Archive transactional data of a specified year into mysql_archive.
     * 
     * @param int $year Year to archive (e.g. 2023)
     * @param bool $purgeFromLive Whether to remove records from live DB after successful copy
     * @return array Result summary
     */
    public function archiveYear(int $year, bool $purgeFromLive = true): array
    {
        $connStatus = $this->checkArchiveConnection();
        if (!$connStatus['status']) {
            throw new \Exception("Không thể kết nối CSDL Lưu trữ: " . $connStatus['message']);
        }

        $mainDb = $this->getMainDbName();
        $archiveDb = $this->getArchiveDbName();

        $startDate = "{$year}-01-01 00:00:00";
        $endDate = "{$year}-12-31 23:59:59";

        // Step 1: Sync Schema & Master Data
        $this->syncArchiveSchema();
        $this->syncMasterDataToArchive();

        // Step 2: Define Transactional Table Hierarchy (children first for deletion, headers first for copy)
        $tablesToArchive = [
            // Sales & Order Requests
            'sales' => ['date_col' => 'created_at', 'id_col' => 'id'],
            'sale_items' => ['parent_table' => 'sales', 'fk_col' => 'sale_id'],
            'sale_expenses' => ['parent_table' => 'sales', 'fk_col' => 'sale_id'],
            'sale_attachments' => ['parent_table' => 'sales', 'fk_col' => 'sale_id'],
            'sale_order_requests' => ['date_col' => 'created_at', 'id_col' => 'id'],
            'sale_order_request_items' => ['parent_table' => 'sale_order_requests', 'fk_col' => 'sale_order_request_id'],
            
            // Quotations
            'quotations' => ['date_col' => 'created_at', 'id_col' => 'id'],
            'quotation_items' => ['parent_table' => 'quotations', 'fk_col' => 'quotation_id'],
            
            // Purchase Orders
            'purchase_orders' => ['date_col' => 'created_at', 'id_col' => 'id'],
            'purchase_order_items' => ['parent_table' => 'purchase_orders', 'fk_col' => 'purchase_order_id'],
            'supplier_quotations' => ['date_col' => 'created_at', 'id_col' => 'id'],
            'supplier_quotation_items' => ['parent_table' => 'supplier_quotations', 'fk_col' => 'supplier_quotation_id'],
            
            // Inventory Imports / Exports / Transfers
            'imports' => ['date_col' => 'created_at', 'id_col' => 'id'],
            'import_items' => ['parent_table' => 'imports', 'fk_col' => 'import_id'],
            'exports' => ['date_col' => 'created_at', 'id_col' => 'id'],
            'export_items' => ['parent_table' => 'exports', 'fk_col' => 'export_id'],
            'transfers' => ['date_col' => 'created_at', 'id_col' => 'id'],
            'transfer_items' => ['parent_table' => 'transfers', 'fk_col' => 'transfer_id'],
            
            // Finance & Logs
            'financial_transactions' => ['date_col' => 'created_at', 'id_col' => 'id'],
            'payment_histories' => ['date_col' => 'created_at', 'id_col' => 'id'],
            'supplier_payment_histories' => ['date_col' => 'created_at', 'id_col' => 'id'],
            'warehouse_journal_entries' => ['date_col' => 'created_at', 'id_col' => 'id'],
            'activity_logs' => ['date_col' => 'created_at', 'id_col' => 'id'],
        ];

        $summary = [];
        $copiedCount = 0;
        $deletedCount = 0;

        // Copy records to Archive Database
        foreach ($tablesToArchive as $table => $meta) {
            if (!Schema::connection($this->mainConnection)->hasTable($table) || !Schema::connection($this->archiveConnection)->hasTable($table)) {
                continue;
            }

            if (isset($meta['date_col'])) {
                $query = "INSERT IGNORE INTO `{$archiveDb}`.`{$table}` 
                          SELECT * FROM `{$mainDb}`.`{$table}` 
                          WHERE `{$meta['date_col']}` BETWEEN '{$startDate}' AND '{$endDate}'";
                DB::statement($query);

                $count = DB::table($table)->whereBetween($meta['date_col'], [$startDate, $endDate])->count();
                $summary[$table] = $count;
                $copiedCount += $count;
            } elseif (isset($meta['parent_table'])) {
                $parentTable = $meta['parent_table'];
                $fk = $meta['fk_col'];
                $query = "INSERT IGNORE INTO `{$archiveDb}`.`{$table}` 
                          SELECT t.* FROM `{$mainDb}`.`{$table}` t 
                          INNER JOIN `{$mainDb}`.`{$parentTable}` p ON t.`{$fk}` = p.id 
                          WHERE p.created_at BETWEEN '{$startDate}' AND '{$endDate}'";
                DB::statement($query);

                $count = DB::table($table)
                    ->whereIn($fk, function($q) use ($parentTable, $startDate, $endDate) {
                        $q->select('id')->from($parentTable)->whereBetween('created_at', [$startDate, $endDate]);
                    })->count();
                $summary[$table] = $count;
                $copiedCount += $count;
            }
        }

        // Purge records from Live DB if requested
        if ($purgeFromLive) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            try {
                // Delete child tables first, then headers
                $reverseTables = array_reverse($tablesToArchive, true);

                foreach ($reverseTables as $table => $meta) {
                    if (!Schema::connection($this->mainConnection)->hasTable($table)) {
                        continue;
                    }

                    if (isset($meta['date_col'])) {
                        $deleted = DB::table($table)->whereBetween($meta['date_col'], [$startDate, $endDate])->delete();
                        $deletedCount += $deleted;
                    } elseif (isset($meta['parent_table'])) {
                        $parentTable = $meta['parent_table'];
                        $fk = $meta['fk_col'];
                        $deleted = DB::table($table)
                            ->whereIn($fk, function($q) use ($parentTable, $startDate, $endDate) {
                                $q->select('id')->from($parentTable)->whereBetween('created_at', [$startDate, $endDate]);
                            })->delete();
                        $deletedCount += $deleted;
                    }
                }
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }

        // Optimize remaining live tables
        foreach (['sales', 'sale_items', 'quotations', 'imports', 'exports', 'financial_transactions', 'activity_logs'] as $tbl) {
            if (Schema::hasTable($tbl)) {
                try {
                    DB::statement("OPTIMIZE TABLE `{$tbl}`");
                } catch (\Exception $e) {
                    // Ignore optimize warnings
                }
            }
        }

        return [
            'success' => true,
            'year' => $year,
            'archive_db' => $archiveDb,
            'copied_count' => $copiedCount,
            'deleted_from_live_count' => $deletedCount,
            'details' => $summary,
            'message' => "Đã đóng gói thành công {$copiedCount} bản ghi năm {$year} sang CSDL '{$archiveDb}'" . ($purgeFromLive ? " và giải phóng {$deletedCount} bản ghi trên CSDL chính." : "."),
        ];
    }

    /**
     * Restore/Unarchive records of a specific year from mysql_archive back to live DB.
     */
    public function restoreYear(int $year): array
    {
        $connStatus = $this->checkArchiveConnection();
        if (!$connStatus['status']) {
            throw new \Exception("Không thể kết nối CSDL Lưu trữ: " . $connStatus['message']);
        }

        $mainDb = $this->getMainDbName();
        $archiveDb = $this->getArchiveDbName();

        $startDate = "{$year}-01-01 00:00:00";
        $endDate = "{$year}-12-31 23:59:59";

        $tables = [
            'sales', 'sale_items', 'sale_expenses', 'sale_attachments',
            'quotations', 'quotation_items',
            'purchase_orders', 'purchase_order_items',
            'imports', 'import_items', 'exports', 'export_items', 'transfers', 'transfer_items',
            'financial_transactions', 'payment_histories', 'warehouse_journal_entries', 'activity_logs'
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $restoredCount = 0;

        try {
            foreach ($tables as $table) {
                if (Schema::connection($this->mainConnection)->hasTable($table) && Schema::connection($this->archiveConnection)->hasTable($table)) {
                    if (Schema::connection($this->archiveConnection)->hasColumn($table, 'created_at')) {
                        $query = "INSERT IGNORE INTO `{$mainDb}`.`{$table}` 
                                  SELECT * FROM `{$archiveDb}`.`{$table}` 
                                  WHERE `created_at` BETWEEN '{$startDate}' AND '{$endDate}'";
                        DB::statement($query);
                    } else {
                        $query = "INSERT IGNORE INTO `{$mainDb}`.`{$table}` SELECT * FROM `{$archiveDb}`.`{$table}`";
                        DB::statement($query);
                    }
                    $restoredCount++;
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        return [
            'success' => true,
            'year' => $year,
            'message' => "Đã khôi phục dữ liệu năm {$year} từ '{$archiveDb}' về CSDL chính thành công.",
        ];
    }
}
