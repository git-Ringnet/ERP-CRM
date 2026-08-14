<?php

namespace App\Http\Controllers;

use App\Models\InvoiceRequest;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class InvoiceRequestController extends Controller
{
    /**
     * Store a new invoice request
     */
    public function store(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'export_id' => 'nullable|exists:exports,id',
            'tax_name' => 'required|string|max:255',
            'tax_address' => 'required|string|max:500',
            'tax_code' => 'required|string|max:50',
            'billing_email' => 'nullable|email|max:255',
            'note' => 'nullable|string',
            
            // New fields
            'seller_name' => 'required|string|max:255',
            'seller_company' => 'required|string|max:255',
            'invoice_content_note' => 'nullable|string',
            'customer_email' => 'nullable|string|max:255',
            'delivery_address' => 'nullable|string|max:500',
            'delivery_contact' => 'nullable|string|max:255',
            'delivery_phone' => 'nullable|string|max:50',
            'payment_terms_note' => 'nullable|string',
            'item_descriptions' => 'nullable|array',
            'item_descriptions.*' => 'nullable|string',
        ]);

        $invoiceRequest = new InvoiceRequest($validated);
        $invoiceRequest->sale_id = $sale->id;
        $invoiceRequest->requester_id = auth()->id();
        $invoiceRequest->status = 'pending';
        $invoiceRequest->save();

        \App\Models\InvoiceRequestRevision::create([
            'invoice_request_id' => $invoiceRequest->id,
            'user_id' => auth()->id(),
            'version' => 1,
            'action' => 'created',
            'note' => 'Khởi tạo yêu cầu xuất hóa đơn',
        ]);

        return back()->with('success', 'Đã gửi yêu cầu xuất hóa đơn thành công!');
    }

    /**
     * Update content of invoice request (general note & per-part descriptions)
     */
    public function updateContent(Request $request, InvoiceRequest $invoiceRequest)
    {
        $validated = $request->validate([
            'seller_name' => 'required|string|max:255',
            'seller_company' => 'required|string|max:255',
            'tax_name' => 'required|string|max:255',
            'tax_code' => 'required|string|max:100',
            'tax_address' => 'required|string|max:500',
            'billing_email' => 'nullable|string|max:255',
            'delivery_address' => 'nullable|string|max:500',
            'delivery_contact' => 'nullable|string|max:255',
            'delivery_phone' => 'nullable|string|max:50',
            'invoice_content_note' => 'nullable|string',
            'payment_terms_note' => 'nullable|string',
            'note' => 'nullable|string',
            'item_descriptions' => 'nullable|array',
            'item_descriptions.*' => 'nullable|string',
        ]);

        $invoiceRequest->update($validated);

        return back()->with('success', 'Cập nhật nội dung xuất hóa đơn thành công!');
    }

    /**
     * Upload / Re-import draft invoice (Accountant / Sales Admin)
     */
    public function issueDraft(Request $request, InvoiceRequest $invoiceRequest)
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'sales_manager', 'accountant'])) {
            return back()->with('error', 'Bạn không có quyền thực hiện thao tác này.');
        }
        $request->validate([
            'draft_file' => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:10240',
            'note' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $path = $invoiceRequest->draft_path;
            if ($request->hasFile('draft_file')) {
                $path = $request->file('draft_file')->store('invoices/drafts', 'public');
                $invoiceRequest->draft_path = $path;
            }

            $isReimport = ($invoiceRequest->status === 'rejected');
            $maxVersion = (int) $invoiceRequest->revisions()->max('version');
            $nextVersion = $maxVersion > 0 ? ($maxVersion + 1) : 1;
            $action = $isReimport ? 'reimported' : 'draft_uploaded';

            $invoiceRequest->update([
                'status' => 'draft_issued',
                'admin_id' => auth()->id(),
                'rejection_reason' => null,
            ]);

            \App\Models\InvoiceRequestRevision::create([
                'invoice_request_id' => $invoiceRequest->id,
                'user_id' => auth()->id(),
                'version' => $nextVersion,
                'action' => $action,
                'draft_path' => $path,
                'note' => $request->note ?: ($isReimport ? "Kế toán import lại hóa đơn nháp (Phiên bản v{$nextVersion})" : "Tải lên hóa đơn nháp (Phiên bản v{$nextVersion})"),
            ]);

            // Notify Sales requester
            \App\Models\Notification::create([
                'user_id' => $invoiceRequest->requester_id,
                'type' => 'invoice_draft_issued',
                'title' => $isReimport ? 'Hóa đơn nháp đã được import lại' : 'Hóa đơn nháp mới đã được tải lên',
                'message' => $isReimport 
                    ? "Kế toán đã import lại hóa đơn nháp (v{$nextVersion}) cho đơn hàng {$invoiceRequest->sale->code}. Vui lòng kiểm tra và xác nhận."
                    : "Hóa đơn nháp cho đơn hàng {$invoiceRequest->sale->code} đã được xuất. Vui lòng kiểm tra và xác nhận.",
                'link' => route('invoice-requests.show', $invoiceRequest->id),
                'icon' => 'fas fa-file-invoice',
                'color' => 'blue',
            ]);

            DB::commit();
            return back()->with('success', $isReimport ? 'Đã import lại hóa đơn nháp thành công!' : 'Đã duyệt yêu cầu và xác nhận hóa đơn nháp!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Upload official invoice and delivery note (Finance Admin)
     */
    public function issueOfficial(Request $request, InvoiceRequest $invoiceRequest)
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'accountant'])) {
            return back()->with('error', 'Bạn không có quyền thực hiện thao tác này.');
        }
        $request->validate([
            'invoice_date' => 'required|date',
            'payment_due_date' => 'required|date',
            'official_file' => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:10240',
            'delivery_note_file' => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:10240',
        ]);

        DB::beginTransaction();
        try {
            if ($request->hasFile('official_file')) {
                $invoiceRequest->official_path = $request->file('official_file')->store('invoices/official', 'public');
            }

            if ($request->hasFile('delivery_note_file')) {
                $invoiceRequest->delivery_note_path = $request->file('delivery_note_file')->store('invoices/delivery_notes', 'public');
            }

            $invoiceRequest->status = 'official_issued';
            $invoiceRequest->finance_id = auth()->id();
            $invoiceRequest->save();

            $currentVersion = (int) $invoiceRequest->revisions()->max('version') ?: 1;
            \App\Models\InvoiceRequestRevision::create([
                'invoice_request_id' => $invoiceRequest->id,
                'user_id' => auth()->id(),
                'version' => $currentVersion,
                'action' => 'official_issued',
                'draft_path' => $invoiceRequest->draft_path,
                'official_path' => $invoiceRequest->official_path,
                'delivery_note_path' => $invoiceRequest->delivery_note_path,
                'note' => 'Xác nhận và xuất hóa đơn chính thức',
            ]);

            // Update linked export status from pending_invoice to pending (Chờ xử lý / Chờ kho xuất)
            if ($invoiceRequest->export_id) {
                $linkedExport = \App\Models\Export::find($invoiceRequest->export_id);
                if ($linkedExport && $linkedExport->status === 'pending_invoice') {
                    $linkedExport->update(['status' => 'pending']);
                }
            } else {
                $linkedExports = \App\Models\Export::where('reference_type', 'sale')
                    ->where('reference_id', $invoiceRequest->sale_id)
                    ->where('status', 'pending_invoice')
                    ->get();
                foreach ($linkedExports as $le) {
                    $le->update(['status' => 'pending']);
                }
            }

            // Update invoice_date and payment_due_date on Sale
            $invoiceRequest->sale->update([
                'invoice_date' => $request->invoice_date,
                'payment_due_date' => $request->payment_due_date,
            ]);

            DB::commit();
            return back()->with('success', 'Đã xác nhận xuất hóa đơn chính thức!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Sales confirms the attached invoice file is correct and completes the invoice flow.
     */
    public function confirm(Request $request, InvoiceRequest $invoiceRequest)
    {
        if (auth()->id() !== (int)$invoiceRequest->requester_id && !auth()->user()->hasAnyRole(['super_admin', 'sales_manager'])) {
            return back()->with('error', 'Bạn không có quyền xác nhận hóa đơn cho yêu cầu này.');
        }

        DB::beginTransaction();
        try {
            $invoiceRequest->update([
                'status' => 'official_issued',
            ]);

            $currentVersion = (int) $invoiceRequest->revisions()->max('version') ?: 1;
            \App\Models\InvoiceRequestRevision::create([
                'invoice_request_id' => $invoiceRequest->id,
                'user_id' => auth()->id(),
                'version' => $currentVersion,
                'action' => 'sales_confirmed',
                'draft_path' => $invoiceRequest->draft_path,
                'official_path' => $invoiceRequest->draft_path,
                'note' => 'Sales đã kiểm tra và xác nhận hóa đơn chính xác.',
            ]);

            // Update linked export status from pending_invoice to pending (Chờ xử lý / Chờ kho xuất)
            if ($invoiceRequest->export_id) {
                $linkedExport = \App\Models\Export::find($invoiceRequest->export_id);
                if ($linkedExport && $linkedExport->status === 'pending_invoice') {
                    $linkedExport->update(['status' => 'pending']);
                }
            } else {
                $linkedExports = \App\Models\Export::where('reference_type', 'sale')
                    ->where('reference_id', $invoiceRequest->sale_id)
                    ->where('status', 'pending_invoice')
                    ->get();
                foreach ($linkedExports as $le) {
                    $le->update(['status' => 'pending']);
                }
            }

            // Notify Accountants / Finance
            $accountants = \App\Models\User::whereHas('roles', fn($q) => $q->whereIn('slug', ['accountant', 'super_admin', 'sales_manager']))->get();
            foreach ($accountants as $acc) {
                if ($acc->id !== auth()->id()) {
                    \App\Models\Notification::create([
                        'user_id' => $acc->id,
                        'type' => 'invoice_confirmed',
                        'title' => 'Hóa đơn đã được Sales xác nhận hoàn tất',
                        'message' => "Sales (" . auth()->user()->name . ") đã xác nhận hóa đơn cho đơn {$invoiceRequest->sale->code} chính xác.",
                        'link' => route('invoice-requests.show', $invoiceRequest->id),
                        'icon' => 'fas fa-check-circle',
                        'color' => 'green',
                    ]);
                }
            }

            DB::commit();
            return back()->with('success', 'Đã xác nhận hóa đơn chính xác! Hoàn tất phần xuất hóa đơn.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Reject draft invoice / Mark incorrect (Sales or Manager)
     */
    public function reject(Request $request, InvoiceRequest $invoiceRequest)
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $currentVersion = (int) $invoiceRequest->revisions()->max('version') ?: 1;

            $invoiceRequest->update([
                'status' => 'rejected',
                'rejection_reason' => $request->reason,
            ]);

            \App\Models\InvoiceRequestRevision::create([
                'invoice_request_id' => $invoiceRequest->id,
                'user_id' => auth()->id(),
                'version' => $currentVersion,
                'action' => 'draft_rejected',
                'draft_path' => $invoiceRequest->draft_path,
                'note' => $request->reason,
            ]);

            // Notify Accountants / Admins
            $accountants = \App\Models\User::whereHas('roles', fn($q) => $q->whereIn('slug', ['accountant', 'super_admin', 'sales_manager']))->get();
            foreach ($accountants as $acc) {
                if ($acc->id !== auth()->id()) {
                    \App\Models\Notification::create([
                        'user_id' => $acc->id,
                        'type' => 'invoice_draft_rejected',
                        'title' => 'Hóa đơn nháp bị phản hồi chưa chính xác',
                        'message' => "Sales (" . auth()->user()->name . ") báo HĐ nháp cho đơn {$invoiceRequest->sale->code} chưa chính xác. Lý do: {$request->reason}. Vui lòng kiểm tra và import lại.",
                        'link' => route('invoice-requests.show', $invoiceRequest->id),
                        'icon' => 'fas fa-exclamation-circle',
                        'color' => 'orange',
                    ]);
                }
            }

            DB::commit();
            return back()->with('success', 'Đã ghi nhận phản hồi chưa chính xác. Kế toán có thể import lại bản nháp từ yêu cầu này.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Cancel/Delete request
     */
    public function cancel(InvoiceRequest $invoiceRequest)
    {
        if ($invoiceRequest->status !== 'pending' && !auth()->user()->hasAnyRole(['super_admin', 'sales_manager'])) {
            return back()->with('error', 'Không thể hủy yêu cầu đã được xử lý.');
        }

        $invoiceRequest->delete();

        return back()->with('success', 'Đã hủy yêu cầu xuất hóa đơn.');
    }

    public function show(InvoiceRequest $invoiceRequest)
    {
        $invoiceRequest->load(['sale.items.product', 'requester', 'export.items.product', 'revisions.user']);
        $sale = $invoiceRequest->sale;

        // 1. HĐMB / Hợp đồng mua bán
        $hdmbFiles = $sale->attachments ?? collect();

        // 2. PNL attachments
        $pnlFiles = $sale->pnlAttachments ?? collect();

        // 3. UNC / Proof of payment
        $uncFiles = \App\Models\PaymentApprovalLog::where('sale_id', $sale->id)
            ->whereNotNull('attachment_path')
            ->get();

        // 4. E-licenses
        $licenseFiles = [];
        if ($sale) {
            foreach ($sale->all_purchase_orders ?? [] as $po) {
                foreach ($po->items as $poItem) {
                    if ($poItem->license_file) {
                        $decoded = json_decode($poItem->license_file, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            foreach ($decoded as $index => $f) {
                                $licenseFiles[] = [
                                    'po_code' => $po->code,
                                    'product_name' => $poItem->product_name ?: ($poItem->product->name ?? 'N/A'),
                                    'file_name' => basename($f),
                                    'file_path' => $f,
                                    'preview_url' => route('purchase-orders.items.preview-license', [$poItem->id, $index])
                                ];
                            }
                        } else {
                            $licenseFiles[] = [
                                'po_code' => $po->code,
                                'product_name' => $poItem->product_name ?: ($poItem->product->name ?? 'N/A'),
                                'file_name' => basename($poItem->license_file),
                                'file_path' => $poItem->license_file,
                                'preview_url' => route('purchase-orders.items.preview-license', [$poItem->id, 0])
                            ];
                        }
                    }
                }
            }
        }

        // Dropdown data for active warehouses
        $warehouses = \App\Models\Warehouse::where('status', 'active')->get();

        return view('invoices.show', compact('invoiceRequest', 'sale', 'hdmbFiles', 'pnlFiles', 'uncFiles', 'licenseFiles', 'warehouses'));
    }
}
