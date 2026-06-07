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
            'tax_name' => 'required|string|max:255',
            'tax_address' => 'required|string|max:500',
            'tax_code' => 'required|string|max:50',
            'billing_email' => 'nullable|email|max:255',
            'note' => 'nullable|string',
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

        $invoiceRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        return back()->with('success', 'Đã từ chối yêu cầu xuất hóa đơn.');
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
}
