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
            $query->where(function ($q) use ($search) {
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
            'calculation_type' => ['required', 'in:fixed,percentage'],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            // formula field removed
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
            'calculation_type' => ['required', 'in:fixed,percentage'],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            // formula field removed
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
    /**
     * API: Calculate suggested expenses for a sale based on items and customer
     */
    public function calculateForSale(Request $request)
    {
        $customerId = $request->input('customer_id');
        $items = $request->input('items', []); // Array of {product_id, quantity, price}

        $totalRevenue = 0;
        $productRevenues = []; // product_id => revenue

        // Calculate revenues
        foreach ($items as $item) {
            $lineTotal = ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
            $totalRevenue += $lineTotal;

            $pid = $item['product_id'] ?? null;
            if ($pid) {
                if (!isset($productRevenues[$pid]))
                    $productRevenues[$pid] = 0;
                $productRevenues[$pid] += $lineTotal;
            }
        }

        $formulas = CostFormula::where('is_active', true)->get();
        $suggestedExpenses = [];

        foreach ($formulas as $formula) {
            $amount = 0;
            $isMatched = false;

            if ($formula->apply_to === 'all') {
                $amount = $formula->calculateCost($totalRevenue);
                $isMatched = true;
            } elseif ($formula->apply_to === 'customer') {
                if ($formula->appliesTo(['customer_id' => $customerId])) {
                    $amount = $formula->calculateCost($totalRevenue);
                    $isMatched = true;
                }
            } elseif ($formula->apply_to === 'product') {
                // Check if any item matches the product conditions
                $relevantRevenue = 0;
                $hasMatch = false;

                foreach ($items as $item) {
                    if ($formula->appliesTo(['product_id' => $item['product_id'] ?? null])) {
                        $relevantRevenue += ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
                        $hasMatch = true;
                    }
                }

                if ($hasMatch) {
                    $amount = $formula->calculateCost($relevantRevenue);
                    $isMatched = true;
                }
            }

            if ($isMatched) {
                $suggestedExpenses[] = [
                    'type' => $formula->type,
                    'type_label' => $formula->type_label,
                    'description' => $formula->name,
                    'amount' => max(0, $amount),
                    'formula_id' => $formula->id,
                ];
            }
        }

        return response()->json($suggestedExpenses);
    }
}

