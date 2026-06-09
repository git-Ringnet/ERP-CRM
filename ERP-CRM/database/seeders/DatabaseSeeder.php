<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // RBAC - Permissions and Roles
            PermissionSeeder::class,
            RoleSeeder::class,

            // Admin & Test users
            AdminUserSeeder::class,
            TestUserSeeder::class,

            // Base data
            CustomerSeeder::class,
            SupplierSeeder::class,
            EmployeeSeeder::class,
            ProductSeeder::class,
            WarehouseSeeder::class,
            CurrencySeeder::class,
            
            // Inventory
            InventorySeeder::class,
            ImportSeeder::class,
            ExportSeeder::class,
            TransferSeeder::class,
            DamagedGoodSeeder::class,

            // Sales module
            PriceListSeeder::class,
            ProjectSeeder::class,
            SaleSeeder::class,
            QuotationSeeder::class,
            CostFormulaSeeder::class,
            PaymentHistorySeeder::class,

            // Purchase module
            PurchaseRequestSeeder::class,
            SupplierQuotationSeeder::class,
            PurchaseOrderSeeder::class,

            // Approval workflow
            ApprovalWorkflowSeeder::class,
        ]);
    }
}
