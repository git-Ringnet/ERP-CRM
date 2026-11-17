<?php

require __DIR__ . '/../../../vendor/autoload.php';
$app = require __DIR__ . '/../../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\Export;
use App\Models\ExportItem;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleOrderRequest;
use App\Models\SaleOrderRequestItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\Warehouse;
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
    SaleItem::whereIn('sale_id', $e2eSaleIds)->delete();
    Sale::whereIn('id', $e2eSaleIds)->delete();

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
        $u->roles()->detach();
        $u->directPermissions()->detach();
        $u->delete();
    }

    echo json_encode(['cleaned' => true]);
    exit(0);
}

fwrite(STDERR, "Unknown action: {$action}\n");
exit(1);
