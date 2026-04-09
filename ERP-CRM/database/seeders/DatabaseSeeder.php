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
                // RBAC - Permissions and Roles (phải chạy trước để tạo permissions và roles)
            PermissionSeeder::class,
            RoleSeeder::class,

                // Admin user - chạy sau khi đã có roles và permissions
            AdminUserSeeder::class,
            TestUserSeeder::class,
                // Base data - phải chạy trước
                // CustomerSeeder::class,
                // SupplierSeeder::class,
                // EmployeeSeeder::class,
                // ProductSeeder::class,          // Bật lại ProductSeeder
            WarehouseSeeder::class,
            CurrencySeeder::class, //tien te chinh
            ApprovalWorkflowSeeder::class, // Quy trình duyệt
            // InventorySeeder::class,
            // // New separate seeders for imports, exports, transfers
            // ImportSeeder::class,
            // ExportSeeder::class,
            // TransferSeeder::class,
            // DamagedGoodSeeder::class,

            // // Module Bán hàng - chạy sau khi có Customer & Product
            // PriceListSeeder::class,       // Bảng giá
            // ProjectSeeder::class,          // Quản lý dự án
            // SaleSeeder::class,             // Đơn hàng bán
            // QuotationSeeder::class,        // Báo giá
            // CostFormulaSeeder::class,      // Công thức chi phí
            // PaymentHistorySeeder::class,   // Công nợ khách hàng

            // // Module Mua hàng
            // PurchaseRequestSeeder::class,  // Yêu cầu đặt hàng
            // SupplierQuotationSeeder::class, // Báo giá NCC
            // PurchaseOrderSeeder::class,    // Đơn mua hàng

            // // Module Quy trình
            // ApprovalWorkflowSeeder::class, // Quy trình duyệt
        ]);
    }
}
