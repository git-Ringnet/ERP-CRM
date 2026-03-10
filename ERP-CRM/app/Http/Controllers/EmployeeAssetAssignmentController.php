<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAsset;
use App\Models\EmployeeAssetAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeAssetAssignmentController extends Controller
{
    /**
     * Danh sách phiếu cấp phát.
     */
    public function index(Request $request)
    {
        $query = EmployeeAssetAssignment::with(['asset', 'employee', 'assignedByUser'])
            ->latest('assigned_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('asset', fn($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
            )->orWhereHas('employee', fn($q) =>
                $q->where('name', 'like', "%{$search}%")
            );
        }

        // Auto-đánh dấu overdue
        EmployeeAssetAssignment::where('status', 'active')
            ->whereNotNull('expected_return_date')
            ->where('expected_return_date', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        $assignments = $query->paginate(15)->withQueryString();
        $employees   = User::whereNotNull('employee_code')->orderBy('name')->get(['id', 'name', 'employee_code']);

        return view('employee-asset-assignments.index', compact('assignments', 'employees'));
    }

    /**
     * Form cấp phát tài sản.
     */
    public function create(Request $request)
    {
        $employees = User::whereNotNull('employee_code')->orderBy('name')->get(['id', 'name', 'employee_code']);

        // Nếu được truyền asset_id từ trang chi tiết tài sản
        $selectedAsset = null;
        if ($request->filled('asset_id')) {
            $selectedAsset = EmployeeAsset::find($request->asset_id);
        }

        // Chỉ cho chọn tài sản còn available
        $availableAssets = EmployeeAsset::where(function ($q) {
            $q->where('status', 'available')
              ->orWhere('quantity_available', '>', 0);
        })->orderBy('name')->get();

        return view('employee-asset-assignments.create', compact('employees', 'availableAssets', 'selectedAsset'));
    }

    /**
     * Lưu phiếu cấp phát mới.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_asset_id'   => ['required', 'exists:employee_assets,id'],
            'user_id'             => ['required', 'exists:users,id'],
            'quantity'            => ['required', 'integer', 'min:1'],
            'assigned_date'       => ['required', 'date'],
            'expected_return_date'=> ['nullable', 'date', 'after_or_equal:assigned_date'],
            'condition_at_assignment' => ['required', 'in:new,good,fair,poor'],
            'reason'              => ['nullable', 'string'],
        ]);

        $asset = EmployeeAsset::findOrFail($validated['employee_asset_id']);

        // Kiểm tra số lượng
        if ($asset->tracking_type === 'serial') {
            if ($asset->status !== 'available') {
                return back()->with('error', 'Tài sản này đang không ở trạng thái sẵn sàng để cấp phát.');
            }
            $validated['quantity'] = 1;
        } else {
            if ($validated['quantity'] > $asset->quantity_available) {
                return back()->with('error', "Số lượng yêu cầu ({$validated['quantity']}) vượt quá số lượng còn lại ({$asset->quantity_available}).");
            }
        }

        DB::transaction(function () use ($validated, $asset) {
            $validated['assigned_by'] = auth()->id();
            $validated['status']      = 'active';

            EmployeeAssetAssignment::create($validated);
            $asset->decrementAvailable($validated['quantity']);
        });

        return redirect()->route('employee-asset-assignments.index')
            ->with('success', 'Đã cấp phát "' . $asset->name . '" thành công.');
    }

    /**
     * Chi tiết phiếu cấp phát.
     */
    public function show(EmployeeAssetAssignment $employeeAssetAssignment)
    {
        $employeeAssetAssignment->load(['asset', 'employee', 'assignedByUser']);
        return view('employee-asset-assignments.show', compact('employeeAssetAssignment'));
    }

    /**
     * Thu hồi / hoàn trả tài sản.
     */
    public function returnAsset(Request $request, EmployeeAssetAssignment $employeeAssetAssignment)
    {
        if ($employeeAssetAssignment->returned_date !== null) {
            return back()->with('error', 'Phiếu này đã được thu hồi trước đó.');
        }

        $validated = $request->validate([
            'returned_date'      => ['required', 'date'],
            'condition_at_return'=> ['required', 'in:new,good,fair,poor'],
            'return_note'        => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $employeeAssetAssignment) {
            $employeeAssetAssignment->update([
                'returned_date'       => $validated['returned_date'],
                'condition_at_return' => $validated['condition_at_return'],
                'return_note'         => $validated['return_note'] ?? null,
                'status'              => 'returned',
            ]);

            $employeeAssetAssignment->asset->incrementAvailable($employeeAssetAssignment->quantity);
        });

        return redirect()->route('employee-asset-assignments.show', $employeeAssetAssignment)
            ->with('success', 'Đã thu hồi tài sản thành công.');
    }
}
