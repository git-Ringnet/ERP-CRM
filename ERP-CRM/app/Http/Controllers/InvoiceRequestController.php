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
        ]);

        $invoiceRequest = new InvoiceRequest($validated);
        $invoiceRequest->sale_id = $sale->id;
        $invoiceRequest->requester_id = auth()->id();
        $invoiceRequest->status = 'pending';
        $invoiceRequest->save();

        return back()->with('success', 'Đã gửi yêu cầu xuất hóa đơn thành công!');
    }

    /**
     * Upload draft invoice (Sales Admin)
     */
    public function issueDraft(Request $request, InvoiceRequest $invoiceRequest)
    {
        if (!auth()->user()->hasAnyRole(['super_admin', 'sales_manager'])) {
            return back()->with('error', 'Bạn không có quyền thực hiện thao tác này.');
        }
        $request->validate([
            'draft_file' => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:5120',
        ]);

        if ($request->hasFile('draft_file')) {
            $path = $request->file('draft_file')->store('invoices/drafts', 'public');
            $invoiceRequest->draft_path = $path;
        }

        $invoiceRequest->update([
            'status' => 'draft_issued',
            'admin_id' => auth()->id(),
        ]);

        return back()->with('success', 'Đã duyệt yêu cầu và xác nhận hóa đơn nháp!');
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
            'official_file' => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:5120',
            'delivery_note_file' => 'nullable|file|mimes:pdf,jpg,png,doc,docx|max:5120',
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

            // Update linked export status from pending_invoice to pending (Chờ xử lý / Chờ kho xuất)
            if ($invoiceRequest->export_id) {
                $linkedExport = \App\Models\Export::find($invoiceRequest->export_id);
                if ($linkedExport && $linkedExport->status === 'pending_invoice') {
                    $linkedExport->update(['status' => 'pending']);
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
     * Reject request
     */
    public function reject(Request $request, InvoiceRequest $invoiceRequest)
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $invoiceRequest->update([
                'status' => 'rejected',
                'rejection_reason' => $request->reason,
            ]);

            // Notify requester (Sales)
            \App\Models\Notification::create([
                'user_id' => $invoiceRequest->requester_id,
                'type' => 'invoice_request_rejected',
                'title' => 'Yêu cầu xuất hóa đơn bị từ chối / cần bổ sung',
                'message' => "Yêu cầu xuất hóa đơn cho đơn {$invoiceRequest->sale->code} bị từ chối. Lý do: {$request->reason}",
                'link' => route('sales.show', $invoiceRequest->sale_id) . '?tab=invoice',
                'icon' => 'fas fa-times-circle',
                'color' => 'red',
            ]);

            DB::commit();
            return back()->with('success', 'Đã từ chối yêu cầu xuất hóa đơn.');
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
        $invoiceRequest->load(['sale.items.product', 'requester', 'export.items.product']);
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
