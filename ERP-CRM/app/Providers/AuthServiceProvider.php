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
        // Master Data
        \App\Models\Customer::class => \App\Policies\CustomerPolicy::class,
        \App\Models\Supplier::class => \App\Policies\SupplierPolicy::class,
        \App\Models\User::class => \App\Policies\EmployeePolicy::class,
        \App\Models\Product::class => \App\Policies\ProductPolicy::class,
        
        // Warehouse
        \App\Models\Warehouse::class => \App\Policies\WarehousePolicy::class,
        \App\Models\Inventory::class => \App\Policies\InventoryPolicy::class,
        \App\Models\Import::class => \App\Policies\ImportPolicy::class,
        \App\Models\Export::class => \App\Policies\ExportPolicy::class,
        \App\Models\Transfer::class => \App\Policies\TransferPolicy::class,
        \App\Models\DamagedGood::class => \App\Policies\DamagedGoodPolicy::class,
        
        // Sales
        \App\Models\Lead::class => \App\Policies\LeadPolicy::class,
        \App\Models\Opportunity::class => \App\Policies\OpportunityPolicy::class,
        \App\Models\Activity::class => \App\Policies\ActivityPolicy::class,
        \App\Models\CustomerCareStage::class => \App\Policies\CustomerCareStagePolicy::class,
        \App\Models\Quotation::class => \App\Policies\QuotationPolicy::class,
        \App\Models\Sale::class => \App\Policies\SalePolicy::class,
        \App\Models\Project::class => \App\Policies\ProjectPolicy::class,
        \App\Models\CustomerDebt::class => \App\Policies\CustomerDebtPolicy::class,
        \App\Models\CostFormula::class => \App\Policies\CostFormulaPolicy::class,
        
        // Purchasing
        \App\Models\SupplierPriceList::class => \App\Policies\SupplierPriceListPolicy::class,
        \App\Models\PurchaseRequest::class => \App\Policies\PurchaseRequestPolicy::class,
        \App\Models\SupplierQuotation::class => \App\Policies\SupplierQuotationPolicy::class,
        \App\Models\PurchaseOrder::class => \App\Policies\PurchaseOrderPolicy::class,
        \App\Models\ShippingAllocation::class => \App\Policies\ShippingAllocationPolicy::class,
        
        // System
        \App\Models\WorkSchedule::class => \App\Policies\WorkSchedulePolicy::class,
        \App\Models\ApprovalWorkflow::class => \App\Policies\ApprovalWorkflowPolicy::class,
        \App\Models\ActivityLog::class => \App\Policies\ActivityLogPolicy::class,
        \App\Models\Report::class => \App\Policies\ReportPolicy::class,
        \App\Models\Setting::class => \App\Policies\SettingPolicy::class,
        
        // Additional Policies
        \App\Models\PriceList::class => \App\Policies\PriceListPolicy::class,
        \App\Models\MilestoneTemplate::class => \App\Policies\MilestoneTemplatePolicy::class,
        \App\Models\SaleReport::class => \App\Policies\SaleReportPolicy::class,
        \App\Models\PurchaseReport::class => \App\Policies\PurchaseReportPolicy::class,
        \App\Models\Warranty::class => \App\Policies\WarrantyPolicy::class,
        \App\Models\ExcelImport::class => \App\Policies\ExcelImportPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
