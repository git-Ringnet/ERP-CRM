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
            // Admin user - chạy đầu tiên
            AdminUserSeeder::class,
            
            // Base data - phải chạy trước
            CustomerSeeder::class,
            SupplierSeeder::class,
            EmployeeSeeder::class,
            ProductSeeder::class,          // Bật lại ProductSeeder
            WarehouseSeeder::class,
            InventorySeeder::class,
            InventoryTransactionSeeder::class,
            DamagedGoodSeeder::class,
            
            // Module Bán hàng - chạy sau khi có Customer & Product
            PriceListSeeder::class,       // Bảng giá
            ProjectSeeder::class,          // Quản lý dự án
            SaleSeeder::class,             // Đơn hàng bán
            QuotationSeeder::class,        // Báo giá
            CostFormulaSeeder::class,      // Công thức chi phí
            PaymentHistorySeeder::class,   // Công nợ khách hàng
            
            // Module Mua hàng
            PurchaseRequestSeeder::class,  // Yêu cầu đặt hàng
            SupplierQuotationSeeder::class, // Báo giá NCC
            PurchaseOrderSeeder::class,    // Đơn mua hàng
            
            // Module Quy trình
            ApprovalWorkflowSeeder::class, // Quy trình duyệt
        ]);
    }
}
