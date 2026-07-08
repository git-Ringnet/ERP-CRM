<?php

namespace App\Http\Controllers;

use App\Models\PaymentTemplate;
use App\Models\PaymentTemplateItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentTemplateController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', PaymentTemplate::class);
        $templates = PaymentTemplate::withCount('items')->orderBy('created_at', 'desc')->get();
        return view('settings.payment-templates.index', compact('templates'));
    }

    public function create()
    {
        $this->authorize('create', PaymentTemplate::class);
        return view('settings.payment-templates.form');
    }

    public function store(Request $request)
    {
        $this->authorize('create', PaymentTemplate::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.milestone_name' => 'required|string|max:255',
            'items.*.percentage' => 'required|numeric|min:0|max:100',
            'items.*.trigger_type' => 'required|string',
            'items.*.blocking_stage' => 'nullable|string',
            'items.*.due_base' => 'required|string',
            'items.*.due_days' => 'required|integer|min:0',
            'items.*.required_docs' => 'required|string',
        ]);

        // Validate percentage sum is 100%
        $sum = array_sum(array_column($validated['items'], 'percentage'));
        if (abs($sum - 100) > 0.01) {
            return back()->withInput()->with('error', "Tổng tỷ lệ phần trăm của các đợt phải bằng 100% (Hiện tại: {$sum}%).");
        }

        // Auto-generate code from name
        $slug = strtoupper(str_replace('-', '_', \Illuminate\Support\Str::slug($validated['name'])));
        $code = $slug;
        $counter = 1;
        while (PaymentTemplate::where('code', $code)->exists()) {
            $code = $slug . '_' . $counter;
            $counter++;
        }

        DB::beginTransaction();
        try {
            $template = PaymentTemplate::create([
                'code' => $code,
                'name' => $validated['name'],
                'description' => $validated['description'],
                'version' => 1,
                'is_active' => true,
            ]);

            foreach ($validated['items'] as $index => $item) {
                $template->items()->create([
                    'sort_order' => $index + 1,
                    'milestone_name' => $item['milestone_name'],
                    'percentage' => $item['percentage'],
                    'trigger_type' => $item['trigger_type'],
                    'trigger_value' => $item['trigger_value'] ?? null,
                    'blocking_stage' => $item['blocking_stage'],
                    'due_base' => $item['due_base'],
                    'due_days' => $item['due_days'],
                    'required_docs' => $item['required_docs'],
                ]);
            }

            DB::commit();
            return redirect()->route('settings.payment-templates.index')->with('success', 'Tạo mẫu điều khoản thanh toán thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $template = PaymentTemplate::with('items')->findOrFail($id);
        $this->authorize('update', $template);
        return view('settings.payment-templates.form', compact('template'));
    }

    public function update(Request $request, $id)
    {
        $template = PaymentTemplate::findOrFail($id);
        $this->authorize('update', $template);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.milestone_name' => 'required|string|max:255',
            'items.*.percentage' => 'required|numeric|min:0|max:100',
            'items.*.trigger_type' => 'required|string',
            'items.*.blocking_stage' => 'nullable|string',
            'items.*.due_base' => 'required|string',
            'items.*.due_days' => 'required|integer|min:0',
            'items.*.required_docs' => 'required|string',
        ]);

        // Validate percentage sum is 100%
        $sum = array_sum(array_column($validated['items'], 'percentage'));
        if (abs($sum - 100) > 0.01) {
            return back()->withInput()->with('error', "Tổng tỷ lệ phần trăm của các đợt phải bằng 100% (Hiện tại: {$sum}%).");
        }

        DB::beginTransaction();
        try {
            // Update details and increment version as template changed (template versioning)
            $template->name = $validated['name'];
            $template->description = $validated['description'];
            $template->increment('version');
            $template->save();

            // Recreate items
            $template->items()->delete();

            foreach ($validated['items'] as $index => $item) {
                $template->items()->create([
                    'sort_order' => $index + 1,
                    'milestone_name' => $item['milestone_name'],
                    'percentage' => $item['percentage'],
                    'trigger_type' => $item['trigger_type'],
                    'trigger_value' => $item['trigger_value'] ?? null,
                    'blocking_stage' => $item['blocking_stage'],
                    'due_base' => $item['due_base'],
                    'due_days' => $item['due_days'],
                    'required_docs' => $item['required_docs'],
                ]);
            }

            DB::commit();
            return redirect()->route('settings.payment-templates.index')->with('success', 'Cập nhật mẫu điều khoản thanh toán thành công (Lên phiên bản ' . $template->version . ')!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $template = PaymentTemplate::findOrFail($id);
        $this->authorize('delete', $template);
        $template->delete();
        return redirect()->route('settings.payment-templates.index')->with('success', 'Xóa mẫu thành công.');
    }

    public function toggle($id)
    {
        $template = PaymentTemplate::findOrFail($id);
        $this->authorize('update', $template);
        $template->is_active = !$template->is_active;
        $template->save();
        return back()->with('success', 'Đã thay đổi trạng thái hoạt động của mẫu.');
    }
}
