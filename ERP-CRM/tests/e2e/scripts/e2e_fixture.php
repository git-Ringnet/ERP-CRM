<?php

require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ApprovalHistory;
use App\Models\Customer;
use App\Models\Export;
use App\Models\ExportItem;
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
    ExportItem::whereIn('export_id', $e2eExportIds)->delete();
    Export::whereIn('id', $e2eExportIds)->delete();

    PurchaseOrder::where('code', 'like', 'E2E-%')->delete();
    Quotation::where('code', 'like', 'E2E-%')->delete();

    $e2eSorIds = SaleOrderRequest::where('code', 'like', 'E2E-%')->pluck('id');
    SaleOrderRequestItem::whereIn('sale_order_request_id', $e2eSorIds)->delete();
    SaleOrderRequest::whereIn('id', $e2eSorIds)->delete();

    $e2eSaleIds = Sale::where('code', 'like', 'E2E-%')->pluck('id');
    ApprovalHistory::where('document_type', 'sale_pnl')->whereIn('document_id', $e2eSaleIds)->delete();
    SalePaymentSchedule::whereIn('sale_id', $e2eSaleIds)->delete();
    SaleItem::whereIn('sale_id', $e2eSaleIds)->delete();
    Sale::whereIn('id', $e2eSaleIds)->delete();

    Project::where('code', 'like', 'E2E-%')->delete();
    Opportunity::where('name', 'like', 'E2E-%')->delete();

    $e2eTicketIds = TechnicalTicket::where('code', 'like', 'E2E-%')->pluck('id');
    TechnicalSupportLog::whereIn('technical_ticket_id', $e2eTicketIds)->orWhere('support_content', 'like', 'E2E-%')->delete();
    TechnicalTicketComment::whereIn('technical_ticket_id', $e2eTicketIds)->delete();
    DB::table('technical_ticket_engineers')->whereIn('technical_ticket_id', $e2eTicketIds)->delete();
    TechnicalTicket::whereIn('id', $e2eTicketIds)->delete();

    Setting::whereIn('key', ['technical_ticket_work_type_permissions', 'opportunity_meeting_kpi_target'])->delete();
    Cache::forget('setting.technical_ticket_work_type_permissions');
    Cache::forget('setting.opportunity_meeting_kpi_target');

    $e2eProductIds = Product::where('code', 'like', 'E2E-%')->pluck('id');
    ProductItem::whereIn('product_id', $e2eProductIds)->delete();
    Product::whereIn('id', $e2eProductIds)->delete();

    Customer::where('tax_code', 'like', 'E2E-TAX-%')->delete();
    Supplier::where('code', 'like', 'E2E-%')->delete();

    $group = UserGroup::where('name', 'E2E Perm Test Group')->first();
    if ($group) {
        $group->members()->detach();
        $group->delete();
    }

    $e2eUsers = User::where('email', 'like', '%@test.local')->get();
    foreach ($e2eUsers as $u) {
        CustomNotification::where('user_id', $u->id)->delete();
        $u->roles()->detach();
        $u->directPermissions()->detach();
        $u->delete();
    }

    echo json_encode(['cleaned' => true]);
    exit(0);
}

fwrite(STDERR, "Unknown action: {$action}\n");
exit(1);
