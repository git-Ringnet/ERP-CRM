<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ProductItem;
use App\Models\TechnicalTicket;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * TECH 13: Tra cứu Serial Number và license đã bán cho bộ phận kỹ thuật.
 * - Leader kỹ thuật / Quản trị: xem toàn bộ.
 * - Kỹ sư: chỉ xem dữ liệu liên quan ticket / khách hàng được giao.
 * - Chỉ hiển thị thông tin phục vụ hỗ trợ, không hiển thị thông tin thương mại (giá, chi phí, lợi nhuận).
 */
class TechnicalSerialLookupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        if (!TechnicalTicket::canUserLookupSerials($user)) {
            abort(403, 'Bạn không có quyền tra cứu Serial Number / license.');
        }

        $isLeader = TechnicalTicket::isTechnicalLeader($user);
        $scope = $isLeader ? null : $this->engineerScope($user);

        $query = ProductItem::query()
            ->select('product_items.*')
            ->whereNotNull('product_items.export_id')
            ->with([
                'product:id,code,name',
                'export:id,code,date,customer_id,project_id,reference_type,reference_id,status',
                'export.customer:id,name',
                'export.project:id,code,name,customer_id',
                'export.sale:id,code,customer_id,customer_name',
                'import:id,code,reference_type,reference_id,po_code',
            ]);

        if ($scope !== null) {
            $query->where(function ($q) use ($scope) {
                $q->whereHas('export', function ($eq) use ($scope) {
                    $eq->whereIn('customer_id', $scope['customer_ids'] ?: [0])
                        ->orWhereIn('project_id', $scope['project_ids'] ?: [0])
                        ->orWhere(function ($sq) use ($scope) {
                            $sq->where('reference_type', 'sale')->whereIn('reference_id', $scope['sale_ids'] ?: [0]);
                        });
                });
                if (!empty($scope['serials'])) {
                    $q->orWhereIn('product_items.sku', $scope['serials']);
                }
            });
        }

        $search = trim((string) $request->input('q'));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('product_items.sku', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($pq) => $pq->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('customer_id')) {
            $customerId = (int) $request->input('customer_id');
            $query->whereHas('export', fn ($eq) => $eq->where('customer_id', $customerId)
                ->orWhereHas('sale', fn ($sq) => $sq->where('customer_id', $customerId)));
        }
        if ($request->input('type') === 'license') {
            $query->where(function ($q) {
                $q->whereNotNull('product_items.custom_fields->license_type')
                    ->orWhereHas('product', fn ($pq) => $pq->where('code', 'like', '%-12')->orWhere('code', 'like', '%-24')
                        ->orWhere('code', 'like', '%-36')->orWhere('code', 'like', '%-48')->orWhere('code', 'like', '%-60')
                        ->orWhere('name', 'like', '%license%')->orWhere('name', 'like', '%subscription%'));
            });
        } elseif ($request->input('type') === 'serial') {
            $query->hasSerial();
        }

        $items = $query->orderByDesc('product_items.updated_at')->paginate(25)->withQueryString();

        $customers = Customer::query()
            ->when($scope !== null, fn ($q) => $q->whereIn('id', $scope['customer_ids'] ?: [0]))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('technical.serial_lookup.index', compact('items', 'customers', 'isLeader'));
    }

    /**
     * Phạm vi dữ liệu của kỹ sư: khách hàng / dự án / đơn hàng / S/N trên các ticket được giao
     * (đang phụ trách, đã bàn giao, hoặc là Lead của ticket).
     */
    protected function engineerScope(User $user): array
    {
        $userId = (int) $user->id;
        $tickets = TechnicalTicket::query()
            ->with('project:id,customer_id')
            ->where(function ($q) use ($userId) {
                $q->where('assigned_to', $userId)
                    ->orWhere('team_lead_id', $userId)
                    ->orWhereJsonContains('co_lead_ids', $userId)
                    ->orWhereJsonContains('co_lead_ids', (string) $userId)
                    ->orWhereHas('assignedEngineers', fn ($eq) => $eq->where('users.id', $userId));
            })
            ->get(['id', 'customer_id', 'project_id', 'sale_id', 'ticket_details']);

        $serials = [];
        foreach ($tickets as $ticket) {
            $raw = (string) ($ticket->ticket_details['after_sales_serial'] ?? '');
            foreach (preg_split('/[\s,;]+/', $raw) as $sn) {
                if (($sn = trim($sn)) !== '') {
                    $serials[] = $sn;
                }
            }
        }

        return [
            'customer_ids' => $tickets->pluck('customer_id')
                ->merge($tickets->pluck('project.customer_id'))
                ->filter()->unique()->values()->all(),
            'project_ids' => $tickets->pluck('project_id')->filter()->unique()->values()->all(),
            'sale_ids' => $tickets->pluck('sale_id')->filter()->unique()->values()->all(),
            'serials' => array_values(array_unique($serials)),
        ];
    }
}
