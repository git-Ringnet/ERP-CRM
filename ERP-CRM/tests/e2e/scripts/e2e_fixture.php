<?php

require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ApprovalHistory;
use App\Models\Customer;
use App\Models\Export;
use App\Models\ExportItem;
use App\Models\MarketingEvent;
use App\Models\MarketingItem;
use App\Models\MarketingItemTransaction;
use App\Models\MarketingRequest;
use App\Models\MarketingTicket;
use App\Models\Notification as CustomNotification;
use App\Models\Opportunity;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleOrderRequest;
use App\Models\SaleOrderRequestItem;
use App\Models\SalePaymentSchedule;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\TechnicalSupportLog;
use App\Models\TechnicalTicket;
use App\Models\TechnicalTicketComment;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$action = $argv[1] ?? '';
$payload = json_decode($argv[2] ?? '{}', true) ?: [];

function ensureUser(string $email, string $name, array $roleSlugs = []): User
{
    $user = User::firstOrCreate(
        ['email' => $email],
        [
            'name' => $name,
            'password' => Hash::make('E2ePassword123!'),
            'status' => 'active',
        ]
    );
    $user->update(['name' => $name, 'password' => Hash::make('E2ePassword123!'), 'status' => 'active']);
    if (!empty($roleSlugs)) {
        $roleIds = Role::whereIn('slug', $roleSlugs)->pluck('id')->all();
        $user->roles()->sync($roleIds);
    } else {
        $user->roles()->sync([]);
    }
    return $user;
}

function ensureCustomer(string $suffix): Customer
{
    return Customer::firstOrCreate(
        ['tax_code' => 'E2E-TAX-' . $suffix],
        ['name' => 'E2E Customer ' . $suffix]
    );
}

function ensureProduct(string $suffix): Product
{
    return Product::firstOrCreate(
        ['code' => 'E2E-PRD-' . $suffix],
        ['name' => 'E2E Product ' . $suffix, 'unit' => 'Cái']
    );
}

if ($action === 'setup_adm_pur_004') {
    $admin = ensureUser('e2e_admin@test.local', 'E2E Admin', ['admin']);
    $po = ensureUser('e2e_po@test.local', 'E2E PO Manager', ['purchase_manager']);
    $sales = ensureUser('e2e_sales@test.local', 'E2E Sales', ['sales_staff']);

    $customer = ensureCustomer('PUR004');
    $product = ensureProduct('PUR004');

    $sale = Sale::create([
        'code' => 'E2E-SO-PUR004-' . time(),
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'user_id' => $sales->id,
        'date' => now()->toDateString(),
        'status' => 'approved',
        'pl_status' => 'approved',
        'total' => 1000000,
    ]);

    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 2,
        'price' => 500000,
        'total' => 1000000,
        'is_from_stock' => false,
    ]);

    $sor = SaleOrderRequest::create([
        'code' => 'E2E-PR-PUR004-' . time(),
        'sale_id' => $sale->id,
        'created_by' => $sales->id,
        'status' => SaleOrderRequest::STATUS_PENDING_ADMIN,
        'sent_at' => now(),
    ]);

    SaleOrderRequestItem::create([
        'sale_order_request_id' => $sor->id,
        'part_number' => $product->code,
        'quantity' => 2,
        'unit' => 'Cái',
        'type' => 'HW',
    ]);

    echo json_encode([
        'adminEmail' => $admin->email,
        'poEmail' => $po->email,
        'salesEmail' => $sales->email,
        'saleId' => $sale->id,
        'saleCode' => $sale->code,
        'sorId' => $sor->id,
        'sorCode' => $sor->code,
    ]);
    exit(0);
}

if ($action === 'setup_adm_out_002') {
    $sales = ensureUser('e2e_sales@test.local', 'E2E Sales', ['sales_staff']);
    $whRunrate = Warehouse::where('code', 'WH_RUNRATE')->firstOrFail();
    $customer = ensureCustomer('OUT002');
    $product = ensureProduct('OUT002');

    // Clear old E2E items for this product
    ProductItem::where('product_id', $product->id)->delete();

    // 1 held by sales, 4 free in WH_RUNRATE (total 5 in stock, but sales only holds 1/3)
    ProductItem::create([
        'product_id' => $product->id,
        'warehouse_id' => $whRunrate->id,
        'sku' => 'E2E-SKU-HELD-' . time(),
        'quantity' => 1,
        'status' => ProductItem::STATUS_IN_STOCK,
        'borrower' => $sales->name,
    ]);
    ProductItem::create([
        'product_id' => $product->id,
        'warehouse_id' => $whRunrate->id,
        'sku' => 'E2E-SKU-FREE-' . time(),
        'quantity' => 4,
        'status' => ProductItem::STATUS_IN_STOCK,
        'borrower' => null,
    ]);

    $sale = Sale::create([
        'code' => 'E2E-SO-OUT002-' . time(),
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'user_id' => $sales->id,
        'date' => now()->toDateString(),
        'status' => 'approved',
        'pl_status' => 'approved',
        'total' => 3000000,
    ]);

    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 3,
        'price' => 1000000,
        'total' => 3000000,
        'is_from_stock' => true,
    ]);

    $export = Export::create([
        'code' => 'E2E-EXP-OUT002-' . time(),
        'warehouse_id' => $whRunrate->id,
        'customer_id' => $customer->id,
        'date' => now()->toDateString(),
        'employee_id' => $sales->id,
        'total_qty' => 3,
        'reference_type' => 'sale',
        'reference_id' => $sale->id,
        'status' => 'draft',
        'note' => 'E2E test runrate hold validation',
    ]);

    ExportItem::create([
        'export_id' => $export->id,
        'product_id' => $product->id,
        'warehouse_id' => $whRunrate->id,
        'quantity' => 3,
        'requested_quantity' => 3,
    ]);

    echo json_encode([
        'salesEmail' => $sales->email,
        'saleId' => $sale->id,
        'exportId' => $export->id,
        'exportCode' => $export->code,
        'productName' => $product->name,
        'whRunrateId' => $whRunrate->id,
    ]);
    exit(0);
}

if ($action === 'setup_sys_perm_003') {
    $uOwn = ensureUser('e2e_perm_own@test.local', 'E2E Perm Own', []);
    $uGrp = ensureUser('e2e_perm_group@test.local', 'E2E Perm Group', []);
    $uAll = ensureUser('e2e_perm_all@test.local', 'E2E Perm All', []);
    $uOth = ensureUser('e2e_perm_other@test.local', 'E2E Perm Other', []);

    // Assign direct permissions for clean 3-level scope testing
    $permIds = fn(array $slugs) => Permission::whereIn('slug', $slugs)->pluck('id')->all();

    $uOwn->directPermissions()->sync($permIds(['view_own_quotations', 'view_own_sales', 'view_own_purchase_orders']));
    $uGrp->directPermissions()->sync($permIds(['view_group_quotations', 'view_group_sales', 'view_group_purchase_orders']));
    $uAll->directPermissions()->sync($permIds(['view_all_quotations', 'view_all_sales', 'view_all_purchase_orders']));
    $uOth->directPermissions()->sync([]);

    // Active UserGroup: uGrp leads, uOwn is member; uOth is outside
    $group = UserGroup::firstOrCreate(
        ['name' => 'E2E Perm Test Group'],
        ['leader_id' => $uGrp->id, 'status' => 'active']
    );
    $group->update(['leader_id' => $uGrp->id, 'status' => 'active']);
    $group->members()->sync([$uGrp->id, $uOwn->id]);

    $customer = ensureCustomer('PERM003');
    $supplier = Supplier::firstOrCreate(
        ['code' => 'E2E-SUP-PERM003'],
        ['name' => 'E2E Supplier PERM003', 'email' => 'e2e_sup@test.local', 'phone' => '0900000000']
    );

    $ts = time();
    $qOwn = Quotation::create(['code' => "E2E-Q-OWN-{$ts}", 'customer_id' => $customer->id, 'customer_name' => $customer->name, 'title' => 'E2E Quote Own', 'date' => now()->toDateString(), 'valid_until' => now()->addDays(7)->toDateString(), 'created_by' => $uOwn->id, 'total' => 1000]);
    $qGrp = Quotation::create(['code' => "E2E-Q-GRP-{$ts}", 'customer_id' => $customer->id, 'customer_name' => $customer->name, 'title' => 'E2E Quote Group', 'date' => now()->toDateString(), 'valid_until' => now()->addDays(7)->toDateString(), 'created_by' => $uGrp->id, 'total' => 1000]);
    $qOth = Quotation::create(['code' => "E2E-Q-OTH-{$ts}", 'customer_id' => $customer->id, 'customer_name' => $customer->name, 'title' => 'E2E Quote Other', 'date' => now()->toDateString(), 'valid_until' => now()->addDays(7)->toDateString(), 'created_by' => $uOth->id, 'total' => 1000]);

    $sOwn = Sale::create(['code' => "E2E-S-OWN-{$ts}", 'customer_id' => $customer->id, 'customer_name' => $customer->name, 'user_id' => $uOwn->id, 'date' => now()->toDateString(), 'status' => 'pending', 'total' => 1000]);
    $sGrp = Sale::create(['code' => "E2E-S-GRP-{$ts}", 'customer_id' => $customer->id, 'customer_name' => $customer->name, 'user_id' => $uGrp->id, 'date' => now()->toDateString(), 'status' => 'pending', 'total' => 1000]);
    $sOth = Sale::create(['code' => "E2E-S-OTH-{$ts}", 'customer_id' => $customer->id, 'customer_name' => $customer->name, 'user_id' => $uOth->id, 'date' => now()->toDateString(), 'status' => 'pending', 'total' => 1000]);

    $poOwn = PurchaseOrder::create(['code' => "E2E-PO-OWN-{$ts}", 'supplier_id' => $supplier->id, 'order_date' => now()->toDateString(), 'created_by' => $uOwn->id, 'sale_id' => $sOwn->id, 'status' => 'draft', 'total' => 1000]);
    $poGrp = PurchaseOrder::create(['code' => "E2E-PO-GRP-{$ts}", 'supplier_id' => $supplier->id, 'order_date' => now()->toDateString(), 'created_by' => $uGrp->id, 'sale_id' => $sGrp->id, 'status' => 'draft', 'total' => 1000]);
    $poOth = PurchaseOrder::create(['code' => "E2E-PO-OTH-{$ts}", 'supplier_id' => $supplier->id, 'order_date' => now()->toDateString(), 'created_by' => $uOth->id, 'sale_id' => $sOth->id, 'status' => 'draft', 'total' => 1000]);

    echo json_encode([
        'ownEmail' => $uOwn->email,
        'groupEmail' => $uGrp->email,
        'allEmail' => $uAll->email,
        'quotes' => ['own' => $qOwn->code, 'group' => $qGrp->code, 'other' => $qOth->code],
        'sales' => ['own' => $sOwn->code, 'group' => $sGrp->code, 'other' => $sOth->code],
        'pos' => ['own' => $poOwn->code, 'group' => $poGrp->code, 'other' => $poOth->code],
    ]);
    exit(0);
}

if ($action === 'setup_leg_bod_pnl') {
    $legal1 = ensureUser('e2e_legal1@test.local', 'E2E Legal 1', ['legal_team']);
    $legal2 = ensureUser('e2e_legal2@test.local', 'E2E Legal 2', ['legal_team']);
    $bod = ensureUser('e2e_bod@test.local', 'E2E BOD', ['director']);
    $sales = ensureUser('e2e_sales_pnl@test.local', 'E2E Sales PnL', ['sales_staff']);

    $customer = ensureCustomer('PNL');
    $product = ensureProduct('PNL');
    $supplier = Supplier::firstOrCreate(
        ['code' => 'E2E-SUP-PNL'],
        ['name' => 'E2E Supplier PNL', 'email' => 'e2e_sup_pnl@test.local', 'phone' => '0900000001']
    );
    $wh = Warehouse::where('status', 'active')->firstOrFail();

    $ts = time();
    // 1. Draft sale created later (should sort BELOW pending P&L!)
    $saleDraft = Sale::create([
        'code' => "E2E-SO-DRAFT-{$ts}",
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'user_id' => $sales->id,
        'date' => now()->toDateString(),
        'status' => 'pending',
        'pl_status' => 'draft',
        'current_approval_level' => 0,
        'total' => 250000000,
    ]);

    // 2. Level 1 pending P&L (Legal)
    $saleL1 = Sale::create([
        'code' => "E2E-SO-PNL1-{$ts}",
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'user_id' => $sales->id,
        'date' => now()->toDateString(),
        'status' => 'pending',
        'pl_status' => 'pending',
        'current_approval_level' => 0,
        'total' => 250000000,
        'payment_term_type' => 'custom',
        'payment_terms' => [['name' => 'Thanh toán 100%', 'percentage' => 100, 'timing' => 'after_export']],
    ]);
    SaleItem::create([
        'sale_id' => $saleL1->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'supplier_id' => $supplier->id,
        'quantity' => 1,
        'price' => 250000000,
        'total' => 250000000,
    ]);
    ApprovalHistory::create([
        'document_type' => 'sale_pnl',
        'document_id' => $saleL1->id,
        'level' => 1,
        'level_name' => 'Legal Team review hợp đồng & P&L',
        'action' => 'pending',
    ]);

    // 3. Level 2 pending P&L (BOD)
    $saleL2 = Sale::create([
        'code' => "E2E-SO-PNL2-{$ts}",
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'user_id' => $sales->id,
        'date' => now()->toDateString(),
        'status' => 'pending',
        'pl_status' => 'pending',
        'current_approval_level' => 1,
        'total' => 250000000,
        'payment_term_type' => 'custom',
        'payment_terms' => [['name' => 'Thanh toán 100%', 'percentage' => 100, 'timing' => 'after_export']],
    ]);
    SaleItem::create([
        'sale_id' => $saleL2->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'supplier_id' => $supplier->id,
        'quantity' => 1,
        'price' => 250000000,
        'total' => 250000000,
    ]);
    ApprovalHistory::create([
        'document_type' => 'sale_pnl',
        'document_id' => $saleL2->id,
        'level' => 1,
        'level_name' => 'Legal Team review hợp đồng & P&L',
        'approver_id' => $legal1->id,
        'approver_name' => $legal1->name,
        'action' => 'approved',
        'action_at' => now()->subMinute(),
    ]);
    ApprovalHistory::create([
        'document_type' => 'sale_pnl',
        'document_id' => $saleL2->id,
        'level' => 2,
        'level_name' => 'BOD phê duyệt P&L & hợp đồng cuối cùng',
        'action' => 'pending',
    ]);

    // 4. Export linked to saleL1 for LEG-APR-004
    $export = Export::create([
        'code' => "E2E-EXP-LEG004-{$ts}",
        'warehouse_id' => $wh->id,
        'customer_id' => $customer->id,
        'date' => now()->toDateString(),
        'employee_id' => $sales->id,
        'total_qty' => 1,
        'reference_type' => 'sale',
        'reference_id' => $saleL1->id,
        'status' => 'draft',
    ]);
    ExportItem::create([
        'export_id' => $export->id,
        'product_id' => $product->id,
        'warehouse_id' => $wh->id,
        'quantity' => 1,
        'requested_quantity' => 1,
    ]);

    echo json_encode([
        'legal1Email' => $legal1->email,
        'legal1Id' => $legal1->id,
        'legal2Email' => $legal2->email,
        'legal2Id' => $legal2->id,
        'bodEmail' => $bod->email,
        'bodId' => $bod->id,
        'salesEmail' => $sales->email,
        'salesId' => $sales->id,
        'saleDraftCode' => $saleDraft->code,
        'saleL1Id' => $saleL1->id,
        'saleL1Code' => $saleL1->code,
        'saleL2Id' => $saleL2->id,
        'saleL2Code' => $saleL2->code,
        'exportId' => $export->id,
        'exportCode' => $export->code,
    ]);
    exit(0);
}

if ($action === 'setup_sales_today') {
    $sales1 = ensureUser('e2e_sales1@test.local', 'E2E Sales 1', ['sales_staff']);
    $sales2 = ensureUser('e2e_sales2@test.local', 'E2E Sales 2', ['sales_staff']);
    $salesMgr = ensureUser('e2e_sales_mgr@test.local', 'E2E Sales Manager', ['sales_manager']);
    $customer = ensureCustomer('SALTODAY');
    $product = ensureProduct('SALTODAY');
    $supplier = Supplier::firstOrCreate(
        ['code' => 'E2E-SUP-SALTODAY'],
        ['name' => 'E2E Supplier SALTODAY', 'email' => 'e2e_sup_sal@test.local', 'phone' => '0900000002']
    );

    $ts = time();
    // 1. SAL-PRJ-007: Project registered within 24h by Sales 1 (with non-empty bom_data)
    $dupTax = "E2E-TAX-DUP24H-{$ts}";
    $dupName = "E2E Project Dup 24h {$ts}";
    $prj = Project::create([
        'code' => "E2E-PRJ-DUP-{$ts}",
        'name' => $dupName,
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'eu_name_vi' => $customer->name,
        'eu_tax_code' => $dupTax,
        'vendor_id' => $supplier->id,
        'manager_id' => $sales1->id,
        'bom_data' => 'FortiGate 60F x2',
        'registration_status' => 'submitted',
        'intake_status' => 'pending',
    ]);

    // 2. SAL-PNL-002: Sale approved 10 days ago, not exported yet -> milestone due_date must NOT start from pl_approved_at
    $sale = Sale::create([
        'code' => "E2E-SO-PNL002-{$ts}",
        'customer_id' => $customer->id,
        'customer_name' => $customer->name,
        'user_id' => $sales1->id,
        'date' => now()->subDays(10)->toDateString(),
        'status' => 'approved',
        'pl_status' => 'approved',
        'pl_approved_at' => now()->subDays(10),
        'delivery_date' => null,
        'total' => 50000000,
    ]);
    SaleItem::create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'supplier_id' => $supplier->id,
        'quantity' => 1,
        'price' => 50000000,
        'total' => 50000000,
    ]);
    $sale->syncPaymentSchedules([
        [
            'milestone_name' => 'Đặt cọc đợt 1 (30%)',
            'percentage' => 30,
            'amount' => 15000000,
            'timing' => 'after_contract',
            'required_before' => 'before_order',
            'is_blocking' => 'yes',
            'due_days' => 5,
            'status' => 'unpaid',
        ],
        [
            'milestone_name' => 'Thanh toán đợt 2 (70%)',
            'percentage' => 70,
            'amount' => 35000000,
            'timing' => 'after_delivery',
            'required_before' => 'after_delivery',
            'is_blocking' => 'no',
            'due_days' => 30,
            'status' => 'unpaid',
        ],
    ]);

    // 3. SAL-RPT-001: 2 completed + 1 planned Opportunity for Sales 1 today
    Opportunity::create([
        'name' => "E2E-OPP-DONE-1-{$ts}",
        'customer_id' => $customer->id,
        'assigned_to' => $sales1->id,
        'created_by' => $sales1->id,
        'activity_type' => 'meeting',
        'activity_date' => now()->toDateString(),
        'status' => 'completed',
    ]);
    Opportunity::create([
        'name' => "E2E-OPP-DONE-2-{$ts}",
        'customer_id' => $customer->id,
        'assigned_to' => $sales1->id,
        'created_by' => $sales1->id,
        'activity_type' => 'meeting',
        'activity_date' => now()->toDateString(),
        'status' => 'completed',
    ]);
    Opportunity::create([
        'name' => "E2E-OPP-PLAN-3-{$ts}",
        'customer_id' => $customer->id,
        'assigned_to' => $sales1->id,
        'created_by' => $sales1->id,
        'activity_type' => 'meeting',
        'activity_date' => now()->toDateString(),
        'status' => 'planned',
    ]);

    $resolved = $sale->fresh()->resolvePaymentDue();

    echo json_encode([
        'sales1Email' => $sales1->email,
        'sales1Name' => $sales1->name,
        'sales2Email' => $sales2->email,
        'salesMgrEmail' => $salesMgr->email,
        'vendorId' => $supplier->id,
        'dupTax' => $dupTax,
        'dupName' => $dupName,
        'dupCode' => $prj->code,
        'saleId' => $sale->id,
        'saleCode' => $sale->code,
        'resolvedDueDate' => $resolved['date'] ? $resolved['date']->toDateString() : null,
        'resolvedWaiting' => $resolved['waiting'],
    ]);
    exit(0);
}

if ($action === 'setup_tech_today') {
    $lead = ensureUser('e2e_tech_lead@test.local', 'E2E Tech Lead', ['technical_lead', 'super_admin']);
    $eng1 = ensureUser('e2e_tech_eng1@test.local', 'E2E Tech Eng 1', ['technical_engineer']);
    $eng2 = ensureUser('e2e_tech_eng2@test.local', 'E2E Tech Eng 2', ['technical_engineer']);
    $salesReq = ensureUser('e2e_sales_req@test.local', 'E2E Sales Requester', ['sales_staff']);

    $techPermIds = Permission::whereIn('slug', [
        'view_technical_tickets',
        'create_technical_tickets',
        'edit_technical_tickets',
        'pickup_technical_tickets',
        'complete_technical_tickets',
        'manage_technical_support_logs',
    ])->pluck('id')->all();
    $eng1->directPermissions()->sync($techPermIds);
    $eng2->directPermissions()->sync($techPermIds);
    $lead->directPermissions()->sync($techPermIds);

    Setting::whereIn('key', ['technical_ticket_work_type_permissions', 'opportunity_meeting_kpi_target'])->delete();
    Cache::forget('setting.technical_ticket_work_type_permissions');
    Cache::forget('setting.opportunity_meeting_kpi_target');

    $customer = ensureCustomer('TECHTODAY');
    $ts = time();

    // 1. TEC-TKT-002: Survey ticket (unassigned, open) -> Engineer 1 should see and self-pickup
    $tSurvey = TechnicalTicket::create([
        'code' => "E2E-TECH-SURVEY-{$ts}",
        'title' => "E2E Survey Ticket {$ts}",
        'work_type' => 'survey',
        'priority' => 'medium',
        'status' => 'open',
        'customer_id' => $customer->id,
        'created_by' => $salesReq->id,
        'sales_owner_id' => $salesReq->id,
    ]);

    // 2. TL-CFG-001: Training ticket (unassigned, open) -> after Lead enables 'training' => 'all', Engineer 1 can pickup
    $tTraining = TechnicalTicket::create([
        'code' => "E2E-TECH-TRAIN-{$ts}",
        'title' => "E2E Training Ticket {$ts}",
        'work_type' => 'training',
        'priority' => 'medium',
        'status' => 'open',
        'customer_id' => $customer->id,
        'created_by' => $salesReq->id,
        'sales_owner_id' => $salesReq->id,
    ]);

    // 3. TEC-CLOSE-001 & TL-CLOSE-001: In-progress ticket assigned to Eng 1
    $tClose = TechnicalTicket::create([
        'code' => "E2E-TECH-CLOSE-{$ts}",
        'title' => "E2E Close Flow Ticket {$ts}",
        'work_type' => 'BOM',
        'priority' => 'medium',
        'status' => 'in_progress',
        'customer_id' => $customer->id,
        'assigned_to' => $eng1->id,
        'created_by' => $salesReq->id,
        'sales_owner_id' => $salesReq->id,
        'team_lead_id' => $lead->id,
    ]);
    $tClose->assignedEngineers()->sync([$eng1->id]);

    // 4. TEC-LOG-005: Support log by Eng 2 on another ticket (Eng 1 must see it too!)
    $tOther = TechnicalTicket::create([
        'code' => "E2E-TECH-OTHER-{$ts}",
        'title' => "E2E Other Ticket {$ts}",
        'work_type' => 'it_support',
        'priority' => 'medium',
        'status' => 'in_progress',
        'customer_id' => $customer->id,
        'assigned_to' => $eng2->id,
        'created_by' => $salesReq->id,
    ]);
    $logEng2 = TechnicalSupportLog::create([
        'technical_ticket_id' => $tOther->id,
        'user_id' => $eng2->id,
        'log_date' => now()->toDateString(),
        'work_category' => 'on_call',
        'support_content' => "E2E-LOG-ENG2-CONTENT-{$ts}",
        'status' => 'in_progress',
        'customer_info' => $customer->name,
    ]);

    echo json_encode([
        'leadEmail' => $lead->email,
        'leadId' => $lead->id,
        'eng1Email' => $eng1->email,
        'eng1Id' => $eng1->id,
        'eng1Name' => $eng1->name,
        'eng2Email' => $eng2->email,
        'salesReqEmail' => $salesReq->email,
        'salesReqId' => $salesReq->id,
        'salesReqName' => $salesReq->name,
        'tSurveyId' => $tSurvey->id,
        'tSurveyCode' => $tSurvey->code,
        'tTrainingId' => $tTraining->id,
        'tTrainingCode' => $tTraining->code,
        'tCloseId' => $tClose->id,
        'tCloseCode' => $tClose->code,
        'tOtherId' => $tOther->id,
        'logEng2Id' => $logEng2->id,
        'logEng2Content' => $logEng2->support_content,
    ]);
    exit(0);
}

if ($action === 'check_23_24_state') {
    $sale1 = Sale::find($payload['sale1Id'] ?? 0);
    $sor1 = SaleOrderRequest::with('items')->find($payload['sor1Id'] ?? 0);
    $expOut = Export::find($payload['expOutId'] ?? 0);
    $expHold = Export::find($payload['expHoldId'] ?? 0);
    $po1 = PurchaseOrder::with(['imports', 'supplier.poConfig'])->find($payload['po1Id'] ?? 0);
    $activityCount = \App\Models\ActivityLog::where('subject_type', SaleOrderRequest::class)
        ->where('subject_id', $payload['sor1Id'] ?? 0)
        ->count();

    echo json_encode([
        'sale1EuName' => $sale1?->eu_name,
        'sale1EuTax' => $sale1?->eu_tax_code,
        'sor1EuMst' => $sor1?->items->first()?->eu_name_mst,
        'expOutStatus' => $expOut?->status,
        'expHoldStatus' => $expHold?->status,
        'po1ImportCount' => $po1?->imports?->count() ?? 0,
        'poConfigType' => $po1?->supplier?->poConfig?->template_type,
        'sorActivityCount' => $activityCount,
    ]);
    exit(0);
}

if ($action === 'setup_acc_today') {
    $acc = ensureUser('e2e_acc@test.local', 'E2E Accountant', ['accountant']);
    $sales = ensureUser('e2e_sales@test.local', 'E2E Sales ACC', ['sales_staff']);
    $admin = ensureUser('e2e_admin@test.local', 'E2E Admin ACC', ['admin']);
    $ts = time();

    $cust = Customer::firstOrCreate(
        ['tax_code' => 'E2E-TAX-ACC'],
        ['name' => 'E2E Customer ACC', 'debt_days' => 30, 'phone' => '0900000999']
    );
    $prd = ensureProduct('ACC');

    $saleInv = Sale::create([
        'code' => "E2E-SO-ACCINV-{$ts}",
        'customer_id' => $cust->id,
        'customer_name' => $cust->name,
        'user_id' => $sales->id,
        'date' => now()->subDays(45)->toDateString(),
        'invoice_date' => now()->subDays(45)->toDateString(),
        'payment_due_date' => now()->subDays(15)->toDateString(),
        'status' => 'approved',
        'pl_status' => 'approved',
        'is_fulfill' => true,
        'has_bank_guarantee' => true,
        'subtotal' => 1000000,
        'total' => 1000000,
        'paid_amount' => 0,
        'debt_amount' => 1000000,
    ]);
    SaleItem::create([
        'sale_id' => $saleInv->id,
        'product_id' => $prd->id,
        'product_name' => $prd->name,
        'quantity' => 1,
        'price' => 1000000,
        'total' => 1000000,
    ]);
    $invReq = \App\Models\InvoiceRequest::create([
        'sale_id' => $saleInv->id,
        'requester_id' => $sales->id,
        'tax_name' => $cust->name,
        'tax_code' => $cust->tax_code,
        'tax_address' => 'E2E Address',
        'billing_email' => 'acc@e2e.local',
        'status' => 'pending',
    ]);

    $saleDebt = Sale::create([
        'code' => "E2E-SO-ACCDEBT-{$ts}",
        'customer_id' => $cust->id,
        'customer_name' => $cust->name,
        'user_id' => $sales->id,
        'date' => now()->subDays(40)->toDateString(),
        'invoice_date' => now()->subDays(40)->toDateString(),
        'payment_due_date' => now()->subDays(10)->toDateString(),
        'status' => 'approved',
        'pl_status' => 'approved',
        'subtotal' => 2000000,
        'total' => 2000000,
        'paid_amount' => 500000,
        'debt_amount' => 1500000,
    ]);
    $schedule = SalePaymentSchedule::create([
        'sale_id' => $saleDebt->id,
        'sort_order' => 1,
        'milestone_name' => 'Đợt 1',
        'trigger_type' => 'after_invoice',
        'due_base' => 'invoice_date',
        'due_days' => 30,
        'percentage' => 100,
        'amount' => 1500000,
        'status' => 'pending_finance',
    ]);
    $payment = \App\Models\PaymentHistory::create([
        'sale_id' => $saleDebt->id,
        'customer_id' => $cust->id,
        'amount' => 500000,
        'payment_method' => 'bank_transfer',
        'payment_date' => now()->toDateString(),
        'note' => 'E2E Payment Initial',
        'created_by' => $acc->name,
    ]);

    echo json_encode([
        'accEmail' => $acc->email,
        'adminEmail' => $admin->email,
        'custId' => $cust->id,
        'custName' => $cust->name,
        'saleInvId' => $saleInv->id,
        'saleInvCode' => $saleInv->code,
        'invReqId' => $invReq->id,
        'saleDebtId' => $saleDebt->id,
        'saleDebtCode' => $saleDebt->code,
        'paymentId' => $payment->id,
        'ts' => $ts,
    ]);
    exit(0);
}

if ($action === 'check_acc_today') {
    $invReq = \App\Models\InvoiceRequest::find($payload['invReqId'] ?? 0);
    $saleInv = Sale::find($payload['saleInvId'] ?? 0);
    $saleDebt = Sale::find($payload['saleDebtId'] ?? 0);
    $paymentExists = \App\Models\PaymentHistory::whereKey($payload['paymentId'] ?? 0)->exists();
    echo json_encode([
        'invReqStatus' => $invReq?->status,
        'invReqNumber' => $invReq?->invoice_number,
        'saleInvDate' => $saleInv?->invoice_date?->format('Y-m-d'),
        'saleDebtPaid' => (float) ($saleDebt?->paid_amount ?? 0),
        'saleDebtAmount' => (float) ($saleDebt?->debt_amount ?? 0),
        'paymentExists' => $paymentExists,
    ]);
    exit(0);
}

if ($action === 'setup_23_24') {
    $sales = ensureUser('e2e_sales@test.local', 'E2E Sales One', ['sales_staff']);
    $sales2 = ensureUser('e2e_sales2@test.local', 'E2E Sales Two', ['sales_staff']);
    $om = ensureUser('e2e_om@test.local', 'E2E Order Management', ['order_management']);
    $poStaff = ensureUser('e2e_po@test.local', 'E2E PO Staff', ['purchase_staff']);
    $poMgr = ensureUser('e2e_pomgr@test.local', 'E2E PO Manager', ['purchase_manager']);
    $wh = ensureUser('e2e_wh@test.local', 'E2E Warehouse Mgr', ['warehouse_manager']);
    $admin = ensureUser('e2e_admin@test.local', 'E2E Admin User', ['admin']);
    $ts = time();

    $cust = ensureCustomer('2324');
    $sup1 = Supplier::firstOrCreate(['code' => 'E2E-SUP-ALPHA'], ['name' => 'E2E Brand Alpha', 'email' => 'alpha@e2e.local', 'phone' => '0900000001']);
    $sup2 = Supplier::firstOrCreate(['code' => 'E2E-SUP-BETA'], ['name' => 'E2E Brand Beta', 'email' => 'beta@e2e.local', 'phone' => '0900000002']);

    $prd1 = Product::firstOrCreate(['code' => 'E2E-PRD-ALPHA'], ['name' => 'E2E Product Alpha', 'brand' => 'E2E Brand Alpha', 'unit' => 'Cái']);
    $prd1->update(['brand' => 'E2E Brand Alpha']);
    $prd2 = Product::firstOrCreate(['code' => 'E2E-PRD-BETA'], ['name' => 'E2E Product Beta', 'brand' => 'E2E Brand Beta', 'unit' => 'Cái']);
    $prd2->update(['brand' => 'E2E Brand Beta']);

    $whRunrate = Warehouse::where('code', 'WH_RUNRATE')->first()
        ?: Warehouse::where('is_holdable', true)->first()
        ?: Warehouse::first();

    $heldItem = ProductItem::create([
        'product_id' => $prd1->id,
        'warehouse_id' => $whRunrate->id,
        'sku' => "E2E-HOLD-SN-{$ts}",
        'status' => ProductItem::STATUS_IN_STOCK,
        'quantity' => 1,
        'borrower' => $sales->name,
        'cost_usd' => 100,
    ]);
    $heldItem2 = ProductItem::create([
        'product_id' => $prd1->id,
        'warehouse_id' => $whRunrate->id,
        'sku' => "E2E-HOLD-SN2-{$ts}",
        'status' => ProductItem::STATUS_IN_STOCK,
        'quantity' => 1,
        'borrower' => $sales2->name,
        'cost_usd' => 100,
    ]);

    $proj1 = Project::create([
        'code' => "E2E-PRJ-A-{$ts}",
        'name' => "E2E Project Alpha {$ts}",
        'customer_id' => $cust->id,
        'vendor_id' => $sup1->id,
        'manager_id' => $sales->id,
        'registration_status' => 'submitted',
        'status' => 'in_progress',
        'eu_name_vi' => 'E2E EU Alpha',
    ]);
    $proj2 = Project::create([
        'code' => "E2E-PRJ-B-{$ts}",
        'name' => "E2E Project Beta {$ts}",
        'customer_id' => $cust->id,
        'vendor_id' => $sup2->id,
        'manager_id' => $sales2->id,
        'registration_status' => 'closed_won',
        'status' => 'completed',
        'eu_name_vi' => 'E2E EU Beta',
    ]);

    $sale1 = Sale::create([
        'code' => "E2E-SO-A-{$ts}",
        'customer_id' => $cust->id,
        'customer_name' => $cust->name,
        'eu_name' => "E2E EndUser Alpha {$ts}",
        'eu_tax_code' => '0123456789',
        'project_id' => $proj1->id,
        'user_id' => $sales->id,
        'date' => now()->toDateString(),
        'status' => 'approved',
        'pl_status' => 'approved',
        'total' => 1000000,
    ]);
    $saleItem1 = SaleItem::create([
        'sale_id' => $sale1->id,
        'product_id' => $prd1->id,
        'product_name' => $prd1->name,
        'quantity' => 1,
        'price' => 1000000,
        'total' => 1000000,
    ]);

    $sale2 = Sale::create([
        'code' => "E2E-SO-B-{$ts}",
        'customer_id' => $cust->id,
        'customer_name' => $cust->name,
        'eu_name' => "E2E EndUser Beta {$ts}",
        'project_id' => $proj2->id,
        'user_id' => $sales2->id,
        'date' => now()->toDateString(),
        'status' => 'approved',
        'pl_status' => 'approved',
        'total' => 2000000,
    ]);
    SaleItem::create([
        'sale_id' => $sale2->id,
        'product_id' => $prd2->id,
        'product_name' => $prd2->name,
        'quantity' => 1,
        'price' => 2000000,
        'total' => 2000000,
    ]);

    $sor1 = SaleOrderRequest::create([
        'sale_id' => $sale1->id,
        'code' => "E2E-PR-{$ts}",
        'status' => 'pending_admin',
        'created_by' => $sales->id,
        'sent_at' => now(),
    ]);
    $sorItem1 = SaleOrderRequestItem::create([
        'sale_order_request_id' => $sor1->id,
        'vendor_id' => $sup1->id,
        'vendor' => $sup1->name,
        'type' => 'HW',
        'part_number' => $prd1->code,
        'product_id' => $prd1->id,
        'sale_item_id' => $saleItem1->id,
        'quantity' => 1,
        'serial_number' => "E2E-SN-REQ-{$ts}",
        'si_name' => 'E2E SI Partner',
        'pos_id' => 'POS-E2E-01',
        'eu_name_mst' => "E2E EndUser Alpha {$ts} - 0123456789",
    ]);

    $po1 = PurchaseOrder::create([
        'code' => "E2E-PO-{$ts}",
        'supplier_id' => $sup1->id,
        'order_date' => now()->toDateString(),
        'status' => 'approved',
        'created_by' => $poMgr->id,
    ]);
    \App\Models\PurchaseOrderItem::create([
        'purchase_order_id' => $po1->id,
        'sale_order_request_item_id' => $sorItem1->id,
        'product_id' => $prd1->id,
        'product_code' => $prd1->code,
        'product_name' => $prd1->name,
        'quantity' => 1,
        'unit_price' => 500000,
        'total' => 500000,
        'serial_number' => "E2E-SN-REQ-{$ts}",
    ]);

    $expOut = Export::create([
        'code' => "E2E-EXP-OUT-{$ts}",
        'warehouse_id' => $whRunrate->id,
        'customer_id' => $cust->id,
        'reference_type' => 'sale',
        'reference_id' => $sale1->id,
        'employee_id' => $sales->id,
        'date' => now()->toDateString(),
        'status' => 'draft',
        'total_qty' => 1,
    ]);
    ExportItem::create([
        'export_id' => $expOut->id,
        'product_id' => $prd1->id,
        'warehouse_id' => $whRunrate->id,
        'quantity' => 1,
        'serial_number' => json_encode([$heldItem->id]),
    ]);

    $expHold = Export::create([
        'code' => "E2E-EXP-HOLD-{$ts}",
        'warehouse_id' => $whRunrate->id,
        'customer_id' => $cust->id,
        'reference_type' => 'sale',
        'reference_id' => $sale2->id,
        'employee_id' => $sales2->id,
        'date' => now()->toDateString(),
        'status' => 'pending',
        'total_qty' => 1,
    ]);
    ExportItem::create([
        'export_id' => $expHold->id,
        'product_id' => $prd1->id,
        'warehouse_id' => $whRunrate->id,
        'quantity' => 1,
        'serial_number' => json_encode([$heldItem2->id]),
    ]);
    \App\Models\InvoiceRequest::create([
        'sale_id' => $sale2->id,
        'export_id' => $expHold->id,
        'requester_id' => $sales2->id,
        'tax_name' => $cust->name,
        'tax_code' => $cust->tax_code,
        'tax_address' => 'E2E Address',
        'billing_email' => 'inv@e2e.local',
        'status' => 'official_issued',
    ]);

    echo json_encode([
        'salesEmail' => $sales->email,
        'salesId' => $sales->id,
        'salesName' => $sales->name,
        'sales2Id' => $sales2->id,
        'omEmail' => $om->email,
        'poEmail' => $poStaff->email,
        'poMgrEmail' => $poMgr->email,
        'whEmail' => $wh->email,
        'whId' => $wh->id,
        'adminEmail' => $admin->email,
        'sup1Id' => $sup1->id,
        'sup1Name' => $sup1->name,
        'sup2Id' => $sup2->id,
        'sup2Name' => $sup2->name,
        'proj1Code' => $proj1->code,
        'proj1Id' => $proj1->id,
        'proj2Code' => $proj2->code,
        'sale1Id' => $sale1->id,
        'sale1Code' => $sale1->code,
        'sale2Code' => $sale2->code,
        'sor1Id' => $sor1->id,
        'po1Id' => $po1->id,
        'po1Code' => $po1->code,
        'prd1Id' => $prd1->id,
        'prd1Code' => $prd1->code,
        'whRunrateId' => $whRunrate->id,
        'heldSn' => $heldItem->sku,
        'heldSn2' => $heldItem2->sku,
        'expOutId' => $expOut->id,
        'expOutCode' => $expOut->code,
        'expHoldId' => $expHold->id,
        'expHoldCode' => $expHold->code,
        'ts' => $ts,
    ]);
    exit(0);
}

if ($action === 'setup_mkt_today') {
    $mkt = ensureUser('e2e_mkt@test.local', 'E2E Marketing', ['marketing']);
    $om = ensureUser('e2e_om@test.local', 'E2E Order Management', ['order_management']);
    $bod = ensureUser('e2e_bod@test.local', 'E2E BOD Director', ['director']);
    $acc = ensureUser('e2e_acc@test.local', 'E2E Accountant', ['accountant']);
    $sales = ensureUser('e2e_sales@test.local', 'E2E Sales Staff', ['sales_staff']);
    $sm = ensureUser('e2e_sm@test.local', 'E2E Sales Manager', ['sales_manager']);
    $tech = ensureUser('e2e_tech@test.local', 'E2E Tech Lead', ['technical_lead', 'technical_engineer']);

    $cust1 = ensureCustomer('MKT1');
    $cust2 = ensureCustomer('MKT2');
    $ts = time();

    $item1 = MarketingItem::create([
        'code' => "E2E-ITEM-1-{$ts}",
        'name' => "E2E Item 1 {$ts}",
        'category' => 'Quà tặng',
        'unit' => 'Cái',
        'stock_quantity' => 20,
        'min_stock_alert' => 2,
        'unit_cost' => 100000,
        'status' => 'active',
        'approval_status' => 'approved',
        'submitted_by' => $mkt->id,
        'approved_by' => $bod->id,
        'approved_at' => now(),
    ]);
    $item2 = MarketingItem::create([
        'code' => "E2E-ITEM-2-{$ts}",
        'name' => "E2E Item 2 {$ts}",
        'category' => 'Quà tặng',
        'unit' => 'Cái',
        'stock_quantity' => 15,
        'min_stock_alert' => 2,
        'unit_cost' => 150000,
        'status' => 'active',
        'approval_status' => 'approved',
        'submitted_by' => $mkt->id,
        'approved_by' => $bod->id,
        'approved_at' => now(),
    ]);
    $itemPending = MarketingItem::create([
        'code' => "E2E-ITEM-PEND-{$ts}",
        'name' => "E2E Item Pending {$ts}",
        'category' => 'Quà tặng',
        'unit' => 'Cái',
        'stock_quantity' => 10,
        'min_stock_alert' => 1,
        'unit_cost' => 200000,
        'status' => 'active',
        'approval_status' => 'pending',
        'submitted_by' => $mkt->id,
    ]);

    $approvedEvent = MarketingEvent::create([
        'code' => "E2E-EVT-APP-{$ts}",
        'scope' => 'internal',
        'internal_department' => 'Marketing',
        'internal_purpose' => 'E2E Event Testing',
        'is_public_to_sales' => true,
        'title' => "E2E-EVT-APPROVED-{$ts}",
        'description' => 'E2E approved internal event',
        'event_date' => now()->addDays(7)->toDateString(),
        'location' => 'HCM',
        'budget' => 20000000,
        'funding_source' => 'company_budget',
        'status' => 'approved',
        'created_by' => $mkt->id,
        'approved_by' => $bod->id,
        'approved_at' => now(),
    ]);

    $ticketGift = MarketingTicket::create([
        'code' => "E2E-MTK-GIFT-{$ts}",
        'title' => "E2E Gift Ticket {$ts}",
        'type' => 'internal_collaboration',
        'marketing_event_id' => $approvedEvent->id,
        'created_by' => $mkt->id,
        'status' => 'open',
    ]);
    $ticketTrip = MarketingTicket::create([
        'code' => "E2E-MTK-TRIP-{$ts}",
        'title' => "E2E Trip Ticket {$ts}",
        'type' => 'business_trip',
        'marketing_event_id' => $approvedEvent->id,
        'created_by' => $mkt->id,
        'status' => 'pending',
    ]);
    $ticketExp = MarketingTicket::create([
        'code' => "E2E-MTK-EXP-{$ts}",
        'title' => "E2E Exp Ticket {$ts}",
        'type' => 'payment',
        'marketing_event_id' => $approvedEvent->id,
        'created_by' => $mkt->id,
        'status' => 'pending',
    ]);

    $reqGift004 = MarketingRequest::create([
        'marketing_ticket_id' => $ticketGift->id,
        'marketing_event_id' => $approvedEvent->id,
        'code' => "E2E-REQ-GIFT004-{$ts}",
        'support_team' => 'marketing',
        'assigned_to' => $mkt->id,
        'support_content' => 'E2E Gift 004 Content',
        'description' => 'E2E Gift 004 Description',
        'status' => 'pending_approval',
    ]);

    $reqTrip = MarketingRequest::create([
        'marketing_ticket_id' => $ticketTrip->id,
        'marketing_event_id' => $approvedEvent->id,
        'code' => "E2E-REQ-TRIP-{$ts}",
        'support_team' => 'business_trip',
        'support_content' => 'E2E Business Trip',
        'description' => 'E2E Business Trip Description',
        'departure_date' => now()->addDays(5)->toDateString(),
        'personnel_count' => 2,
        'status' => 'pending_approval',
    ]);

    $reqExp = MarketingRequest::create([
        'marketing_ticket_id' => $ticketExp->id,
        'marketing_event_id' => $approvedEvent->id,
        'code' => "E2E-REQ-EXP-{$ts}",
        'support_team' => 'payment',
        'support_content' => 'E2E Payment Request',
        'description' => 'E2E Payment Description',
        'amount' => 5000000,
        'funding_source' => 'company_budget',
        'status' => 'pending_approval',
    ]);

    $oppSmPres = Opportunity::create([
        'name' => "E2E-OPP-SMPRES-{$ts}",
        'customer_type' => 'existing',
        'customer_id' => $cust1->id,
        'activity_type' => 'solution_presentation',
        'activity_date' => now()->addDays(2)->toDateString(),
        'giveaway' => 'Quà trình bày giải pháp E2E',
        'giveaway_status' => 'pending',
        'status' => 'planned',
        'created_by' => $sales->id,
        'assigned_to' => $sales->id,
    ]);

    $oppBodPres = Opportunity::create([
        'name' => "E2E-OPP-BODPRES-{$ts}",
        'customer_type' => 'existing',
        'customer_id' => $cust1->id,
        'activity_type' => 'demo_online',
        'needs_technical' => true,
        'activity_date' => now()->addDays(3)->toDateString(),
        'status' => 'planned',
        'created_by' => $sales->id,
        'assigned_to' => $sales->id,
    ]);

    $oppGift = Opportunity::create([
        'name' => "E2E-OPP-GIFT-{$ts}",
        'customer_type' => 'existing',
        'customer_id' => $cust1->id,
        'activity_type' => 'client_meeting',
        'activity_date' => now()->addDays(4)->toDateString(),
        'giveaway' => 'Áo thun + Sổ tay E2E',
        'giveaway_status' => 'pending',
        'status' => 'planned',
        'created_by' => $sales->id,
        'assigned_to' => $sales->id,
    ]);

    echo json_encode([
        'mktEmail' => $mkt->email,
        'mktId' => $mkt->id,
        'omEmail' => $om->email,
        'omId' => $om->id,
        'bodEmail' => $bod->email,
        'bodId' => $bod->id,
        'accEmail' => $acc->email,
        'accId' => $acc->id,
        'salesEmail' => $sales->email,
        'salesId' => $sales->id,
        'smEmail' => $sm->email,
        'smId' => $sm->id,
        'techEmail' => $tech->email,
        'techId' => $tech->id,
        'matchedTech' => User::where('status', 'active')->whereHas('roles', fn ($q) => $q->whereIn('slug', ['technical_lead', 'technical_engineer']))->pluck('id'),
        'cust1Id' => $cust1->id,
        'cust1Name' => $cust1->name,
        'cust2Id' => $cust2->id,
        'cust2Name' => $cust2->name,
        'item1Id' => $item1->id,
        'item1Name' => $item1->name,
        'item2Id' => $item2->id,
        'item2Name' => $item2->name,
        'itemPendingId' => $itemPending->id,
        'itemPendingName' => $itemPending->name,
        'approvedEventId' => $approvedEvent->id,
        'approvedEventTitle' => $approvedEvent->title,
        'reqGift004Id' => $reqGift004->id,
        'reqGift004Code' => $reqGift004->code,
        'reqTripId' => $reqTrip->id,
        'reqTripCode' => $reqTrip->code,
        'reqExpId' => $reqExp->id,
        'reqExpCode' => $reqExp->code,
        'oppSmPresId' => $oppSmPres->id,
        'oppSmPresName' => $oppSmPres->name,
        'oppBodPresId' => $oppBodPres->id,
        'oppBodPresName' => $oppBodPres->name,
        'oppGiftId' => $oppGift->id,
        'oppGiftName' => $oppGift->name,
        'ts' => $ts,
    ]);
    exit(0);
}

if ($action === 'check_mkt_state') {
    $item1 = MarketingItem::find($payload['item1Id'] ?? 0);
    $item2 = MarketingItem::find($payload['item2Id'] ?? 0);
    $itemPend = MarketingItem::find($payload['itemPendingId'] ?? 0);
    $oppSm = Opportunity::find($payload['oppSmPresId'] ?? 0);
    $oppBod = Opportunity::find($payload['oppBodPresId'] ?? 0);
    $oppGift = Opportunity::find($payload['oppGiftId'] ?? 0);
    $reqGift004 = MarketingRequest::find($payload['reqGift004Id'] ?? 0);
    $reqTrip = MarketingRequest::find($payload['reqTripId'] ?? 0);
    $reqExp = MarketingRequest::find($payload['reqExpId'] ?? 0);
    $giveawayTicket = $oppSm ? MarketingTicket::where('opportunity_id', $oppSm->id)->first() : null;

    echo json_encode([
        'item1Stock' => $item1?->stock_quantity,
        'item2Stock' => $item2?->stock_quantity,
        'itemPendApproval' => $itemPend?->approval_status,
        'oppSmGiveawayStatus' => $oppSm?->giveaway_status,
        'oppBodTechId' => $oppBod?->technical_user_id,
        'oppGiftGiveawayStatus' => $oppGift?->giveaway_status,
        'reqGift004Status' => $reqGift004?->status,
        'reqTripStatus' => $reqTrip?->status,
        'reqExpStatus' => $reqExp?->status,
        'giveawayTicketId' => $giveawayTicket?->id,
        'giveawayTicketCode' => $giveawayTicket?->code,
    ]);
    exit(0);
}

if ($action === 'check_notifications') {
    $userId = (int) ($payload['userId'] ?? 0);
    $items = CustomNotification::where('user_id', $userId)
        ->orderByDesc('id')
        ->limit(10)
        ->get(['id', 'type', 'title', 'message']);
    echo json_encode(['count' => $items->count(), 'items' => $items]);
    exit(0);
}

if ($action === 'cleanup') {
    $e2eExportIds = Export::where('code', 'like', 'E2E-%')->pluck('id');
    \App\Models\InvoiceRequest::whereIn('export_id', $e2eExportIds)->delete();
    ExportItem::whereIn('export_id', $e2eExportIds)->delete();
    Export::whereIn('id', $e2eExportIds)->delete();

    $e2ePoIds = PurchaseOrder::where('code', 'like', 'E2E-%')->pluck('id');
    \App\Models\PurchaseOrderItem::whereIn('purchase_order_id', $e2ePoIds)->delete();
    PurchaseOrder::whereIn('id', $e2ePoIds)->delete();
    Quotation::where('code', 'like', 'E2E-%')->delete();

    $e2eSorIds = SaleOrderRequest::where('code', 'like', 'E2E-%')->pluck('id');
    SaleOrderRequestItem::whereIn('sale_order_request_id', $e2eSorIds)->delete();
    SaleOrderRequest::whereIn('id', $e2eSorIds)->delete();

    $e2eSaleIds = Sale::where('code', 'like', 'E2E-%')->pluck('id');
    \App\Models\InvoiceRequest::whereIn('sale_id', $e2eSaleIds)->delete();
    \App\Models\PaymentHistory::whereIn('sale_id', $e2eSaleIds)->delete();
    ApprovalHistory::where('document_type', 'sale_pnl')->whereIn('document_id', $e2eSaleIds)->delete();
    SalePaymentSchedule::whereIn('sale_id', $e2eSaleIds)->delete();
    SaleItem::whereIn('sale_id', $e2eSaleIds)->delete();
    Sale::whereIn('id', $e2eSaleIds)->delete();

    Project::where('code', 'like', 'E2E-%')->orWhere('note', 'like', '%E2E-EVT-%')->delete();
    $e2eOppIds = Opportunity::where('name', 'like', 'E2E-%')->pluck('id');

    $e2eMktEventIds = MarketingEvent::where('code', 'like', 'E2E-%')->orWhere('title', 'like', 'E2E-%')->pluck('id');
    $e2eMktItemIds = MarketingItem::where('code', 'like', 'E2E-%')->orWhere('name', 'like', 'E2E-%')->pluck('id');
    $e2eMktTicketIds = MarketingTicket::where('code', 'like', 'E2E-%')
        ->orWhereIn('marketing_event_id', $e2eMktEventIds)
        ->orWhereIn('opportunity_id', $e2eOppIds)
        ->pluck('id');
    $e2eMktReqIds = MarketingRequest::where('code', 'like', 'E2E-%')
        ->orWhereIn('marketing_event_id', $e2eMktEventIds)
        ->orWhereIn('opportunity_id', $e2eOppIds)
        ->orWhereIn('marketing_ticket_id', $e2eMktTicketIds)
        ->pluck('id');

    MarketingItemTransaction::whereIn('marketing_item_id', $e2eMktItemIds)
        ->orWhereIn('marketing_event_id', $e2eMktEventIds)
        ->delete();
    MarketingRequest::whereIn('id', $e2eMktReqIds)->delete();
    MarketingTicket::whereIn('id', $e2eMktTicketIds)->delete();
    ApprovalHistory::where('document_type', 'marketing_budget')->whereIn('document_id', $e2eMktEventIds)->delete();
    foreach (MarketingEvent::whereIn('id', $e2eMktEventIds)->get() as $ev) {
        $ev->customers()->detach();
    }
    MarketingEvent::whereIn('id', $e2eMktEventIds)->delete();
    MarketingItem::whereIn('id', $e2eMktItemIds)->delete();

    Opportunity::whereIn('id', $e2eOppIds)->delete();

    $e2eTicketIds = TechnicalTicket::where('code', 'like', 'E2E-%')->pluck('id');
    TechnicalSupportLog::whereIn('technical_ticket_id', $e2eTicketIds)->orWhere('support_content', 'like', 'E2E-%')->delete();
    TechnicalTicketComment::whereIn('technical_ticket_id', $e2eTicketIds)->delete();
    DB::table('technical_ticket_engineers')->whereIn('technical_ticket_id', $e2eTicketIds)->delete();
    TechnicalTicket::whereIn('id', $e2eTicketIds)->delete();

    Setting::whereIn('key', ['technical_ticket_work_type_permissions', 'opportunity_meeting_kpi_target'])->delete();
    Cache::forget('setting.technical_ticket_work_type_permissions');
    Cache::forget('setting.opportunity_meeting_kpi_target');

    $e2eProductIds = Product::where('code', 'like', 'E2E-%')->pluck('id');
    ProductItem::whereIn('product_id', $e2eProductIds)->orWhere('sku', 'like', 'E2E-%')->delete();
    Product::whereIn('id', $e2eProductIds)->delete();

    $e2eSupIds = Supplier::where('code', 'like', 'E2E-%')->pluck('id');
    \App\Models\SupplierPoConfig::whereIn('supplier_id', $e2eSupIds)->delete();
    Supplier::whereIn('id', $e2eSupIds)->delete();

    $group = UserGroup::where('name', 'E2E Perm Test Group')->first();
    if ($group) {
        $group->members()->detach();
        $group->delete();
    }

    $e2eUsers = User::where('email', 'like', '%@test.local')->get();
    CustomNotification::where('title', 'like', '%E2E-%')->orWhere('message', 'like', '%E2E-%')->delete();
    foreach ($e2eUsers as $u) {
        CustomNotification::where('user_id', $u->id)->delete();
        $u->roles()->detach();
        $u->directPermissions()->detach();
        $u->delete();
    }

    echo json_encode(['cleaned' => true]);
    exit(0);
}

if ($action === 'setup_sales_nov25') {
    $sales = ensureUser('e2e_sales_n25@test.local', 'E2E Sales N25', ['sales_staff']);
    $acc = ensureUser('e2e_acc_n25@test.local', 'E2E Acc N25', ['accountant']);
    $ts = time();

    $cust = Customer::create([
        'code' => "E2E-CUS-N25-{$ts}",
        'name' => "E2E Customer N25 {$ts}",
        'tax_code' => "E2E-TAX-N25-{$ts}",
        'debt_days' => 30,
        'phone' => '0900000888',
    ]);
    $sup = Supplier::firstOrCreate(
        ['code' => 'E2E-SUP-N25'],
        ['name' => 'E2E Supplier N25', 'email' => 'e2e-sup-n25@test.local', 'phone' => '0900000889']
    );
    $prd = ensureProduct('SN25');

    // 1. Sale for SAL-PAY-007 (no UNC) & SAL-PAY-008 (with UNC)
    $salePay = Sale::create([
        'code' => "E2E-SO-PAY-{$ts}",
        'customer_id' => $cust->id,
        'customer_name' => $cust->name,
        'user_id' => $sales->id,
        'date' => now()->toDateString(),
        'status' => 'approved',
        'pl_status' => 'approved',
        'subtotal' => 2000000,
        'total' => 2000000,
        'paid_amount' => 0,
        'debt_amount' => 2000000,
        'payment_status' => 'unpaid',
    ]);
    $ms1 = \App\Models\SalePaymentSchedule::create([
        'sale_id' => $salePay->id,
        'milestone_name' => 'Đặt cọc đợt 1',
        'percentage' => 50,
        'amount' => 1000000,
        'paid_amount' => 0,
        'due_base' => 'contract',
        'trigger_type' => 'ON_CONTRACT_SIGNED',
        'blocking_stage' => 'BLOCK_PO_SEND',
        'required_docs' => 'unc',
        'status' => 'unpaid',
        'sort_order' => 1,
    ]);
    $ms2 = \App\Models\SalePaymentSchedule::create([
        'sale_id' => $salePay->id,
        'milestone_name' => 'Đặt cọc đợt 2',
        'percentage' => 50,
        'amount' => 1000000,
        'paid_amount' => 0,
        'due_base' => 'contract',
        'trigger_type' => 'BEFORE_EXPORT',
        'blocking_stage' => 'BLOCK_WAREHOUSE_EXPORT',
        'required_docs' => 'unc',
        'status' => 'unpaid',
        'sort_order' => 2,
    ]);

    // 2. Completed Sale for SAL-FLT-004
    $saleDone = Sale::create([
        'code' => "E2E-SO-DONE-{$ts}",
        'customer_id' => $cust->id,
        'customer_name' => $cust->name,
        'user_id' => $sales->id,
        'date' => now()->toDateString(),
        'status' => 'completed',
        'pl_status' => 'approved',
        'subtotal' => 1000000,
        'total' => 1000000,
        'paid_amount' => 1000000,
        'debt_amount' => 0,
        'payment_status' => 'paid',
    ]);

    // 3. Two Sales for SAL-SYNC-001 (order request 1 updates customer; order request 2 pre-fills)
    $saleSync1 = Sale::create([
        'code' => "E2E-SO-SYNC1-{$ts}",
        'customer_id' => $cust->id,
        'customer_name' => $cust->name,
        'user_id' => $sales->id,
        'date' => now()->toDateString(),
        'status' => 'approved',
        'pl_status' => 'approved',
        'subtotal' => 1000000,
        'total' => 1000000,
        'paid_amount' => 1000000,
        'debt_amount' => 0,
        'payment_status' => 'paid',
    ]);
    \App\Models\SaleItem::create([
        'sale_id' => $saleSync1->id,
        'product_id' => $prd->id,
        'supplier_id' => $sup->id,
        'product_name' => $prd->name,
        'quantity' => 1,
        'price' => 1000000,
        'total' => 1000000,
    ]);
    $saleSync2 = Sale::create([
        'code' => "E2E-SO-SYNC2-{$ts}",
        'customer_id' => $cust->id,
        'customer_name' => $cust->name,
        'user_id' => $sales->id,
        'date' => now()->toDateString(),
        'status' => 'approved',
        'pl_status' => 'approved',
        'subtotal' => 1000000,
        'total' => 1000000,
        'paid_amount' => 1000000,
        'debt_amount' => 0,
        'payment_status' => 'paid',
    ]);
    \App\Models\SaleItem::create([
        'sale_id' => $saleSync2->id,
        'product_id' => $prd->id,
        'supplier_id' => $sup->id,
        'product_name' => $prd->name,
        'quantity' => 1,
        'price' => 1000000,
        'total' => 1000000,
    ]);

    echo json_encode([
        'ts' => $ts,
        'salesEmail' => $sales->email,
        'accEmail' => $acc->email,
        'accId' => $acc->id,
        'custId' => $cust->id,
        'supId' => $sup->id,
        'prdId' => $prd->id,
        'prdCode' => $prd->code,
        'salePayId' => $salePay->id,
        'salePayCode' => $salePay->code,
        'ms1Id' => $ms1->id,
        'ms2Id' => $ms2->id,
        'saleDoneCode' => $saleDone->code,
        'saleSync1Id' => $saleSync1->id,
        'saleSync2Id' => $saleSync2->id,
    ]);
    exit(0);
}

if ($action === 'check_sales_nov25') {
    $ms1 = \App\Models\SalePaymentSchedule::find($payload['ms1Id'] ?? 0);
    $ms2 = \App\Models\SalePaymentSchedule::find($payload['ms2Id'] ?? 0);
    $salePay = Sale::find($payload['salePayId'] ?? 0);
    $cust = Customer::find($payload['custId'] ?? 0);
    $accNotif = CustomNotification::where('user_id', $payload['accId'] ?? 0)
        ->where('type', 'payment_alert')
        ->where('message', 'like', '%' . ($payload['salePayCode'] ?? '') . '%')
        ->count();
    echo json_encode([
        'ms1Status' => $ms1?->status,
        'ms1Proof' => $ms1?->proof_file_path,
        'ms2Status' => $ms2?->status,
        'ms2Proof' => $ms2?->proof_file_path,
        'salePayDebt' => (float) ($salePay?->debt_amount ?? 0),
        'eligibleForOrder' => (bool) ($salePay?->getPaymentConditionStatus()['eligible_for_order'] ?? false),
        'accNotifCount' => $accNotif,
        'custNameEn' => $cust?->name_en,
        'custPosId' => $cust?->pos_id,
    ]);
    exit(0);
}

fwrite(STDERR, "Unknown action: {$action}\n");
exit(1);
