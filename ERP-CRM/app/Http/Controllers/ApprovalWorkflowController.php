<?php

namespace App\Http\Controllers;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalLevel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowController extends Controller
{
    public function index()
    {
        $workflows = ApprovalWorkflow::with('levels')->get();
        return view('approval-workflows.index', compact('workflows'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        $documentTypes = $this->getDocumentTypes();
        
        return view('approval-workflows.create', compact('users', 'documentTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'string', 'unique:approval_workflows,document_type'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'levels' => ['required', 'array', 'min:1'],
            'levels.*.name' => ['required', 'string', 'max:255'],
            'levels.*.approver_type' => ['required', 'in:role,user'],
            'levels.*.approver_value' => ['required', 'string'],
            'levels.*.min_amount' => ['nullable', 'numeric', 'min:0'],
            'levels.*.max_amount' => ['nullable', 'numeric', 'min:0'],
            'levels.*.is_required' => ['boolean'],
        ]);

        DB::beginTransaction();
        try {
            $workflow = ApprovalWorkflow::create([
                'name' => $validated['name'],
                'document_type' => $validated['document_type'],
                'description' => $validated['description'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['levels'] as $index => $levelData) {
                ApprovalLevel::create([
                    'workflow_id' => $workflow->id,
                    'level' => $index + 1,
                    'name' => $levelData['name'],
                    'approver_type' => $levelData['approver_type'],
                    'approver_value' => $levelData['approver_value'],
                    'min_amount' => $levelData['min_amount'] ?? null,
                    'max_amount' => $levelData['max_amount'] ?? null,
                    'is_required' => $levelData['is_required'] ?? true,
                ]);
            }

            DB::commit();

            return redirect()->route('approval-workflows.index')
                ->with('success', 'Quy trình duyệt đã được tạo.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(ApprovalWorkflow $approvalWorkflow)
    {
        $approvalWorkflow->load('levels');
        $users = User::orderBy('name')->get();
        $documentTypes = $this->getDocumentTypes();

        return view('approval-workflows.edit', compact('approvalWorkflow', 'users', 'documentTypes'));
    }

    public function update(Request $request, ApprovalWorkflow $approvalWorkflow)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'levels' => ['required', 'array', 'min:1'],
            'levels.*.name' => ['required', 'string', 'max:255'],
            'levels.*.approver_type' => ['required', 'in:role,user'],
            'levels.*.approver_value' => ['required', 'string'],
            'levels.*.min_amount' => ['nullable', 'numeric', 'min:0'],
            'levels.*.max_amount' => ['nullable', 'numeric', 'min:0'],
            'levels.*.is_required' => ['boolean'],
        ]);

        DB::beginTransaction();
        try {
            $approvalWorkflow->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Delete old levels and create new ones
            $approvalWorkflow->levels()->delete();

            foreach ($validated['levels'] as $index => $levelData) {
                ApprovalLevel::create([
                    'workflow_id' => $approvalWorkflow->id,
                    'level' => $index + 1,
                    'name' => $levelData['name'],
                    'approver_type' => $levelData['approver_type'],
                    'approver_value' => $levelData['approver_value'],
                    'min_amount' => $levelData['min_amount'] ?? null,
                    'max_amount' => $levelData['max_amount'] ?? null,
                    'is_required' => $levelData['is_required'] ?? true,
                ]);
            }

            DB::commit();

            return redirect()->route('approval-workflows.index')
                ->with('success', 'Quy trình duyệt đã được cập nhật.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(ApprovalWorkflow $approvalWorkflow)
    {
        DB::beginTransaction();
        try {
            $approvalWorkflow->levels()->delete();
            $approvalWorkflow->delete();
            DB::commit();

            return redirect()->route('approval-workflows.index')
                ->with('success', 'Quy trình duyệt đã được xóa.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    public function toggle(ApprovalWorkflow $approvalWorkflow)
    {
        $approvalWorkflow->update(['is_active' => !$approvalWorkflow->is_active]);

        $status = $approvalWorkflow->is_active ? 'kích hoạt' : 'tắt';
        return back()->with('success', "Đã {$status} quy trình duyệt.");
    }

    private function getDocumentTypes(): array
    {
        return [
            'quotation' => 'Báo giá',
            'contract' => 'Hợp đồng',
            'order' => 'Đơn hàng',
            'purchase' => 'Đơn mua hàng',
            'payment' => 'Phiếu chi',
        ];
    }

    public function getRoles(): array
    {
        return [
            'admin' => 'Admin',
            'manager' => 'Trưởng phòng',
            'director' => 'Giám đốc',
            'accountant' => 'Kế toán',
            'legal' => 'Pháp chế',
            'sales' => 'Nhân viên bán hàng',
        ];
    }
}
