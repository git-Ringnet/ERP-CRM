<?php

namespace App\Http\Controllers;

use App\Models\CostFormula;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CostFormulaController extends Controller
{
    public function index(Request $request)
    {
        $query = CostFormula::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $formulas = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('cost-formulas.index', compact('formulas'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        
        return view('cost-formulas.create', compact('products', 'customers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:cost_formulas,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:shipping,marketing,commission,other'],
            'calculation_type' => ['required', 'in:fixed,percentage,formula'],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'formula' => ['nullable', 'string'],
            'apply_to' => ['required', 'in:all,product,category,customer'],
            'apply_conditions' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]);

        CostFormula::create($validated);

        return redirect()->route('cost-formulas.index')
            ->with('success', 'Công thức chi phí đã được tạo thành công.');
    }

    public function edit(CostFormula $costFormula)
    {
        $products = Product::orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        
        return view('cost-formulas.edit', compact('costFormula', 'products', 'customers'));
    }

    public function update(Request $request, CostFormula $costFormula)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('cost_formulas')->ignore($costFormula->id)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:shipping,marketing,commission,other'],
            'calculation_type' => ['required', 'in:fixed,percentage,formula'],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'formula' => ['nullable', 'string'],
            'apply_to' => ['required', 'in:all,product,category,customer'],
            'apply_conditions' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $costFormula->update($validated);

        return redirect()->route('cost-formulas.index')
            ->with('success', 'Công thức chi phí đã được cập nhật thành công.');
    }

    public function destroy(CostFormula $costFormula)
    {
        $costFormula->delete();

        return redirect()->route('cost-formulas.index')
            ->with('success', 'Công thức chi phí đã được xóa thành công.');
    }

    /**
     * API: Get applicable formulas for a sale
     */
    public function getApplicableFormulas(Request $request)
    {
        $productId = $request->input('product_id');
        $customerId = $request->input('customer_id');
        $revenue = $request->input('revenue', 0);
        $quantity = $request->input('quantity', 1);

        $formulas = CostFormula::where('is_active', true)->get();
        $applicableExpenses = [];

        foreach ($formulas as $formula) {
            $conditions = [
                'product_id' => $productId,
                'customer_id' => $customerId,
            ];

            if ($formula->appliesTo($conditions)) {
                $cost = $formula->calculateCost($revenue, [
                    'quantity' => $quantity,
                ]);

                $applicableExpenses[] = [
                    'type' => $formula->type,
                    'description' => $formula->name,
                    'amount' => $cost,
                    'formula_id' => $formula->id,
                ];
            }
        }

        return response()->json($applicableExpenses);
    }
}
