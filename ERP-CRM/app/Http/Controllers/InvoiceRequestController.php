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
            'needs_draft' => 'nullable|boolean',
            
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

        $validated['needs_draft'] = $request->boolean('needs_draft');

        // Process partial items to invoice
        $requestedItems = [];
        $itemsInput = $request->input('items', []);

        if (!empty($itemsInput) && is_array($itemsInput)) {
            foreach ($sale->items as $saleItem) {
                $qtyInput = (float)($itemsInput[$saleItem->id]['quantity'] ?? 0);
                if ($qtyInput > 0) {
                    $maxQty = $saleItem->remaining_invoicable_quantity;
                    $actualQty = min($qtyInput, $maxQty > 0 ? $maxQty : $saleItem->quantity);
                    $customDesc = $request->input("item_descriptions.{$saleItem->id}") ?: ($saleItem->product_name ?: ($saleItem->product->name ?? ''));

                    $requestedItems[] = [
                        'sale_item_id' => $saleItem->id,
                        'product_id' => $saleItem->product_id,
                        'product_name' => $saleItem->product_name ?: ($saleItem->product->name ?? ''),
                        'product_code' => $saleItem->product->code ?? '',
                        'quantity' => $actualQty,
                        'price' => (float)$saleItem->price,
                        'vat' => (float)$saleItem->vat,
                        'is_service' => (bool)$saleItem->is_service,
                        'is_from_stock' => (bool)$saleItem->is_from_stock,
                        'custom_description' => $customDesc,
                    ];
                }
            }
        }

        if (empty($requestedItems)) {
            // Default to all items with their remaining invoicable quantities
            foreach ($sale->items as $saleItem) {
                $rem = $saleItem->remaining_invoicable_quantity;
                if ($rem <= 0 && $sale->invoiceRequests->isNotEmpty()) continue;
                $actualQty = $rem > 0 ? $rem : $saleItem->quantity;
                $customDesc = $request->input("item_descriptions.{$saleItem->id}") ?: ($saleItem->product_name ?: ($saleItem->product->name ?? ''));

                $requestedItems[] = [
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'product_name' => $saleItem->product_name ?: ($saleItem->product->name ?? ''),
                    'product_code' => $saleItem->product->code ?? '',
                    'quantity' => $actualQty,
                    'price' => (float)$saleItem->price,
                    'vat' => (float)$saleItem->vat,
                    'is_service' => (bool)$saleItem->is_service,
                    'is_from_stock' => (bool)$saleItem->is_from_stock,
                    'custom_description' => $customDesc,
                ];
            }
        }

        if (empty($requestedItems)) {
            return back()->with('error', 'Tất cả các sản phẩm trong đơn hàng đã được xuất hóa đơn đầy đủ.');
        }

        $secondaryUserId = $request->input('secondary_user_id', $sale->secondary_user_id);
        $secondaryUserId = $secondaryUserId ? (int)$secondaryUserId : null;
        if ($secondaryUserId === (int)$sale->user_id) {
            $secondaryUserId = null;
        }

        $marginBeneficiaryId = $sale->user_id;
        $primaryMarginPercent = 100.00;
        $secondaryMarginPercent = 0.00;

        if ($secondaryUserId) {
            if ($request->input('split_mode') === 'percent' || ($request->filled('primary_margin_percent') && $request->filled('secondary_margin_percent'))) {
                $primaryMarginPercent = round((float)($request->input('primary_margin_percent', 100)), 2);
                $secondaryMarginPercent = round((float)($request->input('secondary_margin_percent', 0)), 2);
                $marginBeneficiaryId = ($secondaryMarginPercent > $primaryMarginPercent) ? $secondaryUserId : $sale->user_id;
            } else {
                $chosen = $request->input('margin_beneficiary_id');
                if ($chosen && (int)$chosen === $secondaryUserId) {
                    $marginBeneficiaryId = $secondaryUserId;
                    $primaryMarginPercent = 0.00;
                    $secondaryMarginPercent = 100.00;
                } else {
                    $marginBeneficiaryId = $sale->user_id;
                    $primaryMarginPercent = 100.00;
                    $secondaryMarginPercent = 0.00;
                }
            }
        }

        $invoiceRequest = new InvoiceRequest($validated);
        $invoiceRequest->sale_id = $sale->id;
        $invoiceRequest->requester_id = auth()->id();
        $invoiceRequest->secondary_user_id = $secondaryUserId;
        $invoiceRequest->margin_beneficiary_id = $marginBeneficiaryId;
        $invoiceRequest->primary_margin_percent = $primaryMarginPercent;
        $invoiceRequest->secondary_margin_percent = $secondaryMarginPercent;
        $invoiceRequest->status = 'pending';
        $invoiceRequest->requested_items = $requestedItems;
        $invoiceRequest->save();

        // Sync margin distribution back to sale
        $sale->update([
            'secondary_user_id' => $secondaryUserId,
            'margin_beneficiary_id' => $marginBeneficiaryId,
            'primary_margin_percent' => $primaryMarginPercent,
            'secondary_margin_percent' => $secondaryMarginPercent,
        ]);

        $itemSummary = implode(', ', array_map(function($i) {
            return ($i['product_name'] ?? 'SP') . ' (SL: ' . ($i['quantity'] ?? 0) . ')';
        }, $requestedItems));

        \App\Models\InvoiceRequestRevision::create([
            'invoice_request_id' => $invoiceRequest->id,
            'user_id' => auth()->id(),
            'version' => 1,
            'action' => 'created',
            'note' => ($invoiceRequest->needs_draft ? 'Khởi tạo yêu cầu xuất HĐ (Cần HĐ nháp)' : 'Khởi tạo yêu cầu xuất HĐ (Xuất trực tiếp)') . ' - Chi tiết mặt hàng: ' . $itemSummary,
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

        if ($request->has('margin_beneficiary_id') || $request->has('primary_margin_percent') || $request->has('secondary_user_id')) {
            $secId = $request->input('secondary_user_id', $invoiceRequest->secondary_user_id);
            $secId = $secId ? (int)$secId : null;
            if ($secId === (int)$invoiceRequest->sale->user_id) $secId = null;

            $pPercent = round((float)$request->input('primary_margin_percent', $invoiceRequest->primary_margin_percent ?? 100), 2);
            $sPercent = round((float)$request->input('secondary_margin_percent', $invoiceRequest->secondary_margin_percent ?? 0), 2);
            $benId = $request->input('margin_beneficiary_id', $invoiceRequest->margin_beneficiary_id ?? $invoiceRequest->sale->user_id);

            $validated['secondary_user_id'] = $secId;
            $validated['margin_beneficiary_id'] = $benId;
            $validated['primary_margin_percent'] = $pPercent;
            $validated['secondary_margin_percent'] = $sPercent;

            $invoiceRequest->sale->update([
                'secondary_user_id' => $secId,
                'margin_beneficiary_id' => $benId,
                'primary_margin_percent' => $pPercent,
                'secondary_margin_percent' => $sPercent,
            ]);
        }

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
            'invoice_date' => 'nullable|date',
            'payment_due_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $path = $invoiceRequest->draft_path;
            if ($request->hasFile('draft_file')) {
                $path = $request->file('draft_file')->store('invoices/drafts', 'public');
                $invoiceRequest->draft_path = $path;
                $invoiceRequest->official_path = $path;
            }

            // Update invoice_date / payment_due_date on Sale if provided
            $dateUpdates = array_filter([
                'invoice_date' => $request->invoice_date,
                'payment_due_date' => $request->payment_due_date,
            ]);
            if (!empty($dateUpdates)) {
                $invoiceRequest->sale->update($dateUpdates);
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
                'official_path' => $path,
                'note' => $request->note ?: ($isReimport ? "Kế toán import lại hóa đơn (Phiên bản v{$nextVersion})" : "Tải lên file hóa đơn (Phiên bản v{$nextVersion})"),
            ]);

            // Notify Sales requester
            \App\Models\Notification::create([
                'user_id' => $invoiceRequest->requester_id,
                'type' => 'invoice_draft_issued',
                'title' => $isReimport ? 'Hóa đơn đã được import lại' : 'Hóa đơn đã được tải lên',
                'message' => $isReimport 
                    ? "Kế toán đã import lại file hóa đơn (v{$nextVersion}) cho đơn hàng {$invoiceRequest->sale->code}. Vui lòng kiểm tra và xác nhận."
                    : "Hóa đơn cho đơn hàng {$invoiceRequest->sale->code} đã được tải lên. Vui lòng kiểm tra và xác nhận.",
                'link' => route('invoice-requests.show', $invoiceRequest->id),
                'icon' => 'fas fa-file-invoice',
                'color' => 'blue',
            ]);

            DB::commit();
            return back()->with('success', $isReimport ? 'Đã import lại file hóa đơn thành công!' : 'Đã import file hóa đơn thành công!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Upload official invoice and delivery note (Finance Admin) - Retained for backward compatibility
     */
    public function issueOfficial(Request $request, InvoiceRequest $invoiceRequest)
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'accountant'])) {
            return back()->with('error', 'Bạn không có quyền thực hiện thao tác này.');
        }
        $request->validate([
            'invoice_date' => 'nullable|date',
            'payment_due_date' => 'nullable|date',
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
                'official_path' => $invoiceRequest->official_path ?: $invoiceRequest->draft_path,
                'delivery_note_path' => $invoiceRequest->delivery_note_path,
                'note' => 'Xác nhận và hoàn tất hóa đơn',
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

            // Update invoice_date and payment_due_date on Sale if provided
            if ($request->filled('invoice_date') || $request->filled('payment_due_date')) {
                $invoiceRequest->sale->update(array_filter([
                    'invoice_date' => $request->invoice_date,
                    'payment_due_date' => $request->payment_due_date,
                ]));
            }

            DB::commit();
            return back()->with('success', 'Đã xác nhận hoàn tất hóa đơn!');
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
        if (auth()->id() !== (int)$invoiceRequest->requester_id && auth()->id() !== (int)($invoiceRequest->sale->user_id ?? 0) && !auth()->user()->hasAnyRole(['super_admin', 'sales_manager'])) {
            return back()->with('error', 'Bạn không có quyền xác nhận hóa đơn cho yêu cầu này.');
        }
        if ($invoiceRequest->status !== 'draft_issued') {
            return back()->with('error', 'Chỉ có thể xác nhận hóa đơn đang ở trạng thái chờ Sales kiểm tra.');
        }

        DB::beginTransaction();
        try {
            $invoiceRequest->update([
                'status' => 'official_issued',
                'finance_id' => $invoiceRequest->admin_id ?? auth()->id(),
                'official_path' => $invoiceRequest->draft_path,
            ]);

            $currentVersion = (int) $invoiceRequest->revisions()->max('version') ?: 1;
            \App\Models\InvoiceRequestRevision::create([
                'invoice_request_id' => $invoiceRequest->id,
                'user_id' => auth()->id(),
                'version' => $currentVersion,
                'action' => 'official_issued',
                'draft_path' => $invoiceRequest->draft_path,
                'official_path' => $invoiceRequest->draft_path,
                'note' => 'Sales đã kiểm tra và xác nhận hóa đơn chính xác - Hoàn tất quy trình xuất HĐ.',
            ]);

            // Update linked export status from pending_invoice to pending (Chờ kho xuất hàng)
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

            // Ensure invoice_date and payment_due_date on Sale are set
            $sale = $invoiceRequest->sale;
            $updates = [];
            if (empty($sale->invoice_date)) {
                $updates['invoice_date'] = now()->toDateString();
            }
            if (empty($sale->payment_due_date)) {
                $debtDays = (int)($sale->customer->debt_days ?? 30);
                $baseDate = !empty($updates['invoice_date']) ? \Carbon\Carbon::parse($updates['invoice_date']) : ($sale->invoice_date ? \Carbon\Carbon::parse($sale->invoice_date) : now());
                $updates['payment_due_date'] = $baseDate->copy()->addDays($debtDays)->toDateString();
            }
            if (!empty($updates)) {
                $sale->update($updates);
            }

            // Notify Accountants / Finance
            $accountants = \App\Models\User::whereHas('roles', fn($q) => $q->whereIn('slug', ['accountant', 'super_admin', 'sales_manager']))->get();
            foreach ($accountants as $acc) {
                if ($acc->id !== auth()->id()) {
                    \App\Models\Notification::create([
                        'user_id' => $acc->id,
                        'type' => 'invoice_confirmed',
                        'title' => 'Sales đã xác nhận hoàn tất hóa đơn',
                        'message' => "Sales (" . auth()->user()->name . ") đã xác nhận hóa đơn cho đơn {$invoiceRequest->sale->code}. Quy trình hóa đơn đã hoàn tất.",
                        'link' => route('invoice-requests.show', $invoiceRequest->id),
                        'icon' => 'fas fa-check-circle',
                        'color' => 'green',
                    ]);
                }
            }

            DB::commit();
            return back()->with('success', 'Đã xác nhận hóa đơn thành công! Quy trình hóa đơn đã hoàn tất.');
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

        if (auth()->id() !== (int)$invoiceRequest->requester_id && auth()->id() !== (int)($invoiceRequest->sale->user_id ?? 0) && !auth()->user()->hasAnyRole(['super_admin', 'sales_manager'])) {
            return back()->with('error', 'Bạn không có quyền phản hồi hóa đơn cho yêu cầu này.');
        }

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
        $invoiceRequest->load(['sale.items.product', 'requester', 'export.items.product', 'revisions.user', 'secondaryUser', 'marginBeneficiary', 'sale.user', 'sale.secondaryUser']);
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
        $salesUsers = \App\Models\User::orderBy('name')->get();

        return view('invoices.show', compact('invoiceRequest', 'sale', 'hdmbFiles', 'pnlFiles', 'uncFiles', 'licenseFiles', 'warehouses', 'salesUsers'));
    }

    /**
     * Admin notifies Accountant to issue official invoice
     */
    public function notifyAccountant(Request $request, InvoiceRequest $invoiceRequest)
    {
        $sale = $invoiceRequest->sale;
        $note = $request->input('note', 'Admin đã yêu cầu Kế toán tiến hành xuất hóa đơn.');

        // Create revision log
        \App\Models\InvoiceRequestRevision::create([
            'invoice_request_id' => $invoiceRequest->id,
            'user_id' => auth()->id(),
            'version' => (int) $invoiceRequest->revisions()->max('version') ?: 1,
            'action' => 'admin_notified_accountant',
            'note' => $note,
        ]);

        // Send notifications to accountants
        $accountants = \App\Models\User::whereHas('roles', fn($q) => $q->whereIn('slug', ['accountant', 'super_admin']))->get();
        foreach ($accountants as $acc) {
            \App\Models\Notification::create([
                'user_id' => $acc->id,
                'type' => 'invoice_alert',
                'title' => 'Yêu cầu xuất hóa đơn cho đơn ' . $sale->code,
                'message' => "Admin (" . auth()->user()->name . ") đã yêu cầu xuất hóa đơn cho đơn hàng {$sale->code}. {$note}",
                'link' => route('invoice-requests.show', $invoiceRequest->id),
                'icon' => 'fas fa-file-invoice-dollar',
                'color' => 'indigo',
            ]);
        }

        return back()->with('success', 'Đã gửi thông báo yêu cầu Kế toán xuất hóa đơn thành công!');
    }

    /**
     * Admin notifies Warehouse to export goods and creates warehouse export slip if not yet created
     */
    public function notifyWarehouse(Request $request, InvoiceRequest $invoiceRequest)
    {
        $sale = $invoiceRequest->sale;
        $note = $request->input('note', 'Admin đã thông báo bộ phận Kho thực hiện xuất hàng.');
        $warehouseId = $request->input('warehouse_id') ?: (\App\Models\Warehouse::active()->value('id') ?: 1);

        DB::beginTransaction();
        try {
            // Check if there are physical goods in this invoice request
            $itemsToExport = [];
            $customItemsInput = $request->input('items', []);

            if (!empty($customItemsInput) && is_array($customItemsInput)) {
                foreach ($customItemsInput as $pid => $itemData) {
                    $qty = (int)($itemData['quantity'] ?? 0);
                    if ($qty <= 0) continue;

                    $saleItem = $sale->items->where('product_id', $pid)->first();
                    if ($saleItem && $saleItem->is_service) continue; // Skip services

                    $itemsToExport[] = [
                        'product_id' => $pid,
                        'quantity' => $qty,
                        'unit_price' => $saleItem ? (float)$saleItem->price : 0,
                        'calculated_total' => $saleItem ? ((float)$saleItem->price * $qty) : 0,
                        'product_name' => $saleItem ? ($saleItem->product_name ?: ($saleItem->product->name ?? '')) : '',
                    ];
                }
            } else {
                $requestedItems = $invoiceRequest->requested_items ?: [];

                if (!empty($requestedItems) && is_array($requestedItems)) {
                    foreach ($requestedItems as $rItem) {
                        if (!empty($rItem['is_service'])) {
                            continue; // Skip services for physical warehouse export
                        }
                        $qty = (int)($rItem['quantity'] ?? 0);
                        if ($qty > 0 && !empty($rItem['product_id'])) {
                            $itemsToExport[] = [
                                'product_id' => $rItem['product_id'],
                                'quantity' => $qty,
                                'unit_price' => (float)($rItem['price'] ?? 0),
                                'calculated_total' => (float)($rItem['price'] ?? 0) * $qty,
                                'product_name' => $rItem['product_name'] ?? '',
                            ];
                        }
                    }
                } else {
                    foreach ($sale->items as $sItem) {
                        if ($sItem->is_service) continue;
                        $rem = $sItem->remaining_exportable_quantity;
                        $qty = $rem > 0 ? $rem : $sItem->quantity;
                        if ($qty > 0 && $sItem->product_id) {
                            $itemsToExport[] = [
                                'product_id' => $sItem->product_id,
                                'quantity' => $qty,
                                'unit_price' => (float)$sItem->price,
                                'calculated_total' => (float)$sItem->price * $qty,
                                'product_name' => $sItem->product_name ?: ($sItem->product->name ?? ''),
                            ];
                        }
                    }
                }
            }

            if (empty($itemsToExport)) {
                return back()->with('error', 'Đợt yêu cầu này không có sản phẩm vật lý nào cần xuất kho (hoặc toàn bộ là hàng dịch vụ).');
            }

            // If no export is linked to this request and we have physical goods, create an Export slip
            $exportCreated = null;
            if (!$invoiceRequest->export_id && !empty($itemsToExport)) {
                $exportCode = \App\Models\Export::generateCode();
                $exportCreated = \App\Models\Export::create([
                    'code' => $exportCode,
                    'warehouse_id' => $warehouseId,
                    'customer_id' => $sale->customer_id,
                    'project_id' => $sale->project_id,
                    'contact_id' => $sale->contact_id,
                    'date' => now()->toDateString(),
                    'employee_id' => auth()->id(),
                    'total_qty' => array_sum(array_column($itemsToExport, 'quantity')),
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'note' => "Xuất kho theo yêu cầu hóa đơn #{$invoiceRequest->id} của đơn hàng {$sale->code}. {$note}",
                    'status' => 'pending', // Pending warehouse approval & stock reduction
                ]);

                foreach ($itemsToExport as $expItem) {
                    \App\Models\ExportItem::create([
                        'export_id' => $exportCreated->id,
                        'product_id' => $expItem['product_id'],
                        'quantity' => $expItem['quantity'],
                        'unit_price' => $expItem['unit_price'],
                        'total' => $expItem['calculated_total'],
                    ]);
                }

                $invoiceRequest->update(['export_id' => $exportCreated->id]);
            }

            // Update linked exports to pending if pending_invoice
            $linkedExports = \App\Models\Export::where('reference_type', 'sale')
                ->where('reference_id', $sale->id)
                ->where('status', 'pending_invoice')
                ->get();
            foreach ($linkedExports as $exp) {
                $exp->update(['status' => 'pending']);
            }

            // Create revision log
            \App\Models\InvoiceRequestRevision::create([
                'invoice_request_id' => $invoiceRequest->id,
                'user_id' => auth()->id(),
                'version' => (int) $invoiceRequest->revisions()->max('version') ?: 1,
                'action' => 'admin_notified_warehouse',
                'note' => $note . ($exportCreated ? " (Đã tạo phiếu xuất kho {$exportCreated->code})" : ''),
            ]);

            // Notify warehouse staff
            $warehouseStaff = \App\Models\User::whereHas('roles', fn($q) => $q->whereIn('slug', ['warehouse', 'logistics', 'super_admin']))->get();
            foreach ($warehouseStaff as $wh) {
                \App\Models\Notification::create([
                    'user_id' => $wh->id,
                    'type' => 'warehouse_alert',
                    'title' => 'Thông báo chuẩn bị xuất hàng - ' . $sale->code,
                    'message' => "Admin (" . auth()->user()->name . ") đã thông báo xuất hàng cho đơn {$sale->code}." . ($exportCreated ? " Phiếu xuất: {$exportCreated->code}." : '') . " {$note}",
                    'link' => $exportCreated ? route('exports.index', ['search' => $exportCreated->code]) : route('sales.show', $sale->id),
                    'icon' => 'fas fa-truck-loading',
                    'color' => 'teal',
                ]);
            }

            DB::commit();
            return back()->with('success', 'Đã tạo yêu cầu xuất hàng & gửi thông báo tới bộ phận Kho thành công!' . ($exportCreated ? " Mã phiếu: {$exportCreated->code}" : ''));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }

    /**
     * Accountant / Admin marks whether the invoice has been officially issued or not (1-click status update)
     */
    public function markInvoiced(Request $request, InvoiceRequest $invoiceRequest)
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'accountant', 'admin', 'director', 'sales_manager'])) {
            return back()->with('error', 'Chỉ có Kế toán hoặc Quản trị viên mới được thực hiện thao tác này.');
        }

        DB::beginTransaction();
        try {
            $isInvoiced = $request->has('is_invoiced') ? $request->boolean('is_invoiced') : true;

            $invoiceRequest->is_invoiced = $isInvoiced;
            if ($isInvoiced) {
                $invoiceRequest->invoiced_at = now();
                $invoiceRequest->status = 'official_issued';
                $invoiceRequest->finance_id = auth()->id();

                // Update sale invoice date
                $sale = $invoiceRequest->sale;
                $invDate = now()->toDateString();
                $debtDays = (int)($sale->customer->debt_days ?? 30);
                $sale->update([
                    'invoice_date' => $invDate,
                    'payment_due_date' => \Carbon\Carbon::parse($invDate)->addDays($debtDays)->toDateString(),
                ]);
            } else {
                $invoiceRequest->status = $invoiceRequest->needs_draft ? 'draft_issued' : 'pending';
            }
            $invoiceRequest->save();

            \App\Models\InvoiceRequestRevision::create([
                'invoice_request_id' => $invoiceRequest->id,
                'user_id' => auth()->id(),
                'version' => ((int) $invoiceRequest->revisions()->max('version') ?: 1) + 1,
                'action' => $isInvoiced ? 'accountant_marked_invoiced' : 'accountant_marked_uninvoiced',
                'note' => $isInvoiced ? 'Ghi nhận trạng thái: ĐÃ XUẤT HÓA ĐƠN' : 'Chuyển trạng thái về: CHƯA XUẤT HĐ',
            ]);

            // Notify Sales & Admin
            if ($invoiceRequest->requester_id) {
                \App\Models\Notification::create([
                    'user_id' => $invoiceRequest->requester_id,
                    'type' => 'invoice_status_update',
                    'title' => $isInvoiced ? 'Đã ghi nhận xuất hóa đơn' : 'Cập nhật trạng thái HĐ',
                    'message' => $isInvoiced 
                        ? "Đã ghi nhận xuất hóa đơn cho đợt #{$invoiceRequest->id} của đơn hàng {$invoiceRequest->sale->code}."
                        : "Cập nhật đơn {$invoiceRequest->sale->code} chưa xuất hóa đơn.",
                    'link' => route('sales.show', $invoiceRequest->sale_id),
                    'icon' => $isInvoiced ? 'fas fa-check-circle' : 'fas fa-clock',
                    'color' => $isInvoiced ? 'green' : 'amber',
                ]);
            }

            DB::commit();
            return back()->with('success', $isInvoiced ? 'Đã ghi nhận ĐÃ XUẤT HÓA ĐƠN thành công!' : 'Đã chuyển trạng thái về Chưa xuất HĐ.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage());
        }
    }
}
