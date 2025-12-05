<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseRequest;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Display a listing of warehouses.
     */
    public function index(Request $request)
    {
        $query = Warehouse::with('manager');

        // Filter by status
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // Search by name or code
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $warehouses = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('warehouses.index', compact('warehouses'));
    }

    /**
     * Show the form for creating a new warehouse.
     */
    public function create()
    {
        $managers = User::whereNotNull('employee_code')->get();
        $code = Warehouse::generateCode();
        
        return view('warehouses.create', compact('managers', 'code'));
    }

    /**
     * Store a newly created warehouse.
     */
    public function store(WarehouseRequest $request)
    {
        $data = $request->validated();
        $data['has_temperature_control'] = $request->boolean('has_temperature_control');
        $data['has_security_system'] = $request->boolean('has_security_system');

        Warehouse::create($data);

        return redirect()->route('warehouses.index')
            ->with('success', 'Tạo kho mới thành công.');
    }

    /**
     * Display the specified warehouse.
     */
    public function show(Warehouse $warehouse)
    {
        $warehouse->load('manager');
        
        return view('warehouses.show', compact('warehouse'));
    }

    /**
     * Show the form for editing the specified warehouse.
     */
    public function edit(Warehouse $warehouse)
    {
        $managers = User::whereNotNull('employee_code')->get();
        
        return view('warehouses.edit', compact('warehouse', 'managers'));
    }

    /**
     * Update the specified warehouse.
     */
    public function update(WarehouseRequest $request, Warehouse $warehouse)
    {
        $data = $request->validated();
        $data['has_temperature_control'] = $request->boolean('has_temperature_control');
        $data['has_security_system'] = $request->boolean('has_security_system');

        $warehouse->update($data);

        return redirect()->route('warehouses.index')
            ->with('success', 'Cập nhật kho thành công.');
    }

    /**
     * Remove the specified warehouse.
     */
    public function destroy(Warehouse $warehouse)
    {
        // Check if warehouse has inventory
        if ($warehouse->hasInventory()) {
            return redirect()->route('warehouses.index')
                ->with('error', 'Không thể xóa kho đang có hàng tồn kho.');
        }

        $warehouse->delete();

        return redirect()->route('warehouses.index')
            ->with('success', 'Xóa kho thành công.');
    }
}
