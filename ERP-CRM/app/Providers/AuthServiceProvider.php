<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\Customer::class => \App\Policies\CustomerPolicy::class,
        \App\Models\Supplier::class => \App\Policies\SupplierPolicy::class,
        \App\Models\User::class => \App\Policies\EmployeePolicy::class,
        \App\Models\Product::class => \App\Policies\ProductPolicy::class,
        \App\Models\Warehouse::class => \App\Policies\WarehousePolicy::class,
        \App\Models\Inventory::class => \App\Policies\InventoryPolicy::class,
        \App\Models\Import::class => \App\Policies\ImportPolicy::class,
        \App\Models\Export::class => \App\Policies\ExportPolicy::class,
        \App\Models\Transfer::class => \App\Policies\TransferPolicy::class,
        \App\Models\DamagedGood::class => \App\Policies\DamagedGoodPolicy::class,
        \App\Models\Sale::class => \App\Policies\SalePolicy::class,
        \App\Models\Quotation::class => \App\Policies\QuotationPolicy::class,
        \App\Models\PurchaseOrder::class => \App\Policies\PurchaseOrderPolicy::class,
        \App\Models\Setting::class => \App\Policies\SettingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
