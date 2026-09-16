<?php

namespace App\Http\Controllers;

use App\Models\MarketingEvent;
use App\Models\MarketingTicket;
use App\Models\MarketingRequest;
use App\Models\MarketingRequestComment;
use App\Models\MarketingItem;
use App\Models\MarketingItemTransaction;
use App\Models\Opportunity;
use App\Models\User;
use App\Models\MarketingSupplierFund;
use App\Models\MarketingSupplierTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MarketingRequestController extends Controller
{
    /**
     * Tạo Ticket mới và các Yêu cầu con
     */
    public function storeTicket(Request $request, MarketingEvent $marketingEvent)
    {
        $validated = $request->validate([
            'type' => 'required|in:internal_collaboration,business_trip,payment,others',
        ]);

        $ticketType = $validated['type'];

        DB::beginTransaction();
        try {
            $ticket = MarketingTicket::create([
                'marketing_event_id' => $marketingEvent->id,
                'type' => $ticketType,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            if ($ticketType === 'internal_collaboration') {
                $reqsData = $request->input('requests', []);
                if (empty($reqsData)) {
                    return back()->with('error', 'Ticket phối hợp nội bộ phải chứa ít nhất 1 yêu cầu.');
                }

                foreach ($reqsData as $index => $req) {
                    $supportTeam = $req['support_team'] ?? 'technical';
                    if ($supportTeam === 'other' && !empty($req['support_team_other'])) {
                        $supportTeam = $req['support_team_other'];
                    }
                    $supportContent = $req['support_content'] ?? 'others';

                    // Lọc file upload tương ứng với request này
                    $uploadedFiles = [];
                    $fileKey = "request_files_{$index}";
                    if ($request->hasFile($fileKey)) {
                        foreach ($request->file($fileKey) as $file) {
                            $path = $file->store('marketing_requests', 'public');
                            $uploadedFiles[] = [
                                'name' => $file->getClientOriginalName(),
                                'path' => $path,
                                'url'  => asset('storage/' . $path)
                            ];
                        }
                    }

                    MarketingRequest::create([
                        'marketing_ticket_id' => $ticket->id,
                        'marketing_event_id'  => $marketingEvent->id,
                        'support_team'        => $supportTeam,
                        'pic_type'            => $req['pic_type'] ?? 'all',
                        'support_content'     => $supportContent,
                        'support_content_other' => $req['support_content_other'] ?? null,
                        'description'         => $req['description'] ?? '',
                        'deadline'            => $req['deadline'] ?? null,
                        'status'              => 'received', // Mới tiếp nhận, chờ gán PIC
                        'attachment_path'     => $uploadedFiles,
                    ]);
                }

                $ticket->update(['status' => 'in_progress']);

            } elseif ($ticketType === 'business_trip') {
                $request->validate([
                    'departure_date'      => 'required|date',
                    'departure_date_note' => 'nullable|string',
                    'personnel_count'     => 'required|integer|min:1',
                    'amount'              => 'required|numeric|min:0',
                    'trip_files'          => 'nullable|array',
                    'trip_files.*'        => 'file',
                ]);

                // Xử lý tiền tệ
                $this->normalizeMoneyFields($request, ['amount']);

                $uploadedFiles = [];
                if ($request->hasFile('trip_files')) {
                    foreach ($request->file('trip_files') as $file) {
                        $path = $file->store('marketing_requests', 'public');
                        $uploadedFiles[] = [
                            'name' => $file->getClientOriginalName(),
                            'path' => $path,
                            'url'  => asset('storage/' . $path)
                        ];
                    }
                }

                MarketingRequest::create([
                    'marketing_ticket_id' => $ticket->id,
                    'marketing_event_id'  => $marketingEvent->id,
                    'support_team'        => 'bod',
                    'description'         => 'Yêu cầu đi công tác tỉnh ngoài',
                    'departure_date'      => $request->departure_date,
                    'departure_date_note' => $request->departure_date_note,
                    'personnel_count'     => $request->personnel_count,
                    'amount'              => $request->amount,
                    'status'              => 'pending_approval',
                    'attachment_path'     => $uploadedFiles,
                ]);

            } elseif ($ticketType === 'payment') {
                $request->validate([
                    'payment_content'        => 'required|string',
                    'amount'                 => 'required|numeric|min:0',
                    'amount_in_words'        => 'required|string',
                    'reference_request_code' => 'nullable|string',
                    'funding_source'         => 'nullable|string',
                    'payment_files'          => 'required|array|min:1',
                    'payment_files.*'        => 'file',
                    'marketing_supplier_fund_id' => 'nullable|exists:marketing_supplier_funds,id',
                ]);

                $this->normalizeMoneyFields($request, ['amount']);

                $uploadedFiles = [];
                if ($request->hasFile('payment_files')) {
                    foreach ($request->file('payment_files') as $file) {
                        $path = $file->store('marketing_requests', 'public');
                        $uploadedFiles[] = [
                            'name' => $file->getClientOriginalName(),
                            'path' => $path,
                            'url'  => asset('storage/' . $path)
                        ];
                    }
                }

                MarketingRequest::create([
                    'marketing_ticket_id' => $ticket->id,
                    'marketing_event_id'  => $marketingEvent->id,
                    'support_team'        => 'accounting',
                    'support_content'     => 'others',
                    'support_content_other' => 'Thanh toán / Tạm ứng',
                    'description'         => $request->payment_content,
                    'amount'              => $request->amount,
                    'amount_in_words'     => $request->amount_in_words,
                    'reference_request_code' => $request->reference_request_code,
                    'funding_source'         => $request->funding_source,
                    'supplier_debt_checked'  => $request->has('supplier_debt_checked'),
                    'marketing_supplier_fund_id' => $request->marketing_supplier_fund_id,
                    'status'              => 'pending_approval',
                    'attachment_path'     => $uploadedFiles,
                ]);

            } elseif ($ticketType === 'others') {
                $request->validate([
                    'assigned_to' => 'required|exists:users,id',
                    'description' => 'required|string',
                    'other_files' => 'nullable|array',
                    'other_files.*' => 'file',
                ]);

                $uploadedFiles = [];
                if ($request->hasFile('other_files')) {
                    foreach ($request->file('other_files') as $file) {
                        $path = $file->store('marketing_requests', 'public');
                        $uploadedFiles[] = [
                            'name' => $file->getClientOriginalName(),
                            'path' => $path,
                            'url'  => asset('storage/' . $path)
                        ];
                    }
                }

                MarketingRequest::create([
                    'marketing_ticket_id' => $ticket->id,
                    'marketing_event_id'  => $marketingEvent->id,
                    'support_team'        => 'others',
                    'assigned_to'         => $request->assigned_to,
                    'description'         => $request->description,
                    'status'              => 'in_progress', // Không cần duyệt, chuyển sang làm ngay
                    'attachment_path'     => $uploadedFiles,
                ]);

                $ticket->update(['status' => 'in_progress']);
            }

            DB::commit();
            return back()->with('success', 'Đã tạo Ticket yêu cầu thành công.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Lỗi khi tạo Ticket: ' . $e->getMessage());
        }
    }

    /**
     * Gán người phụ trách (PIC) - Tech Lead / Sales Manager thực hiện
     */
    public function assignPic(Request $request, MarketingRequest $marketingRequest)
    {
        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $user = Auth::user();
        $isTechLead = str_contains(strtolower($user->position), 'manager') || str_contains(strtolower($user->position), 'lead');
        $isSalesManager = $user->hasRole('sales_manager') || (str_contains(strtolower($user->position), 'manager') && $user->department === 'Sales');

        $reqTeam = strtolower($marketingRequest->support_team);
        
        $canAssign = false;
        if ($user->hasRole('super_admin') || $user->hasRole('director') || $user->hasRole('marketing')) {
            $canAssign = true;
        } elseif ($reqTeam === 'technical' && $isTechLead && ($user->department === 'Technical' || $user->department === 'Tech' || $user->department === 'IT')) {
            $canAssign = true;
        } elseif ($reqTeam === 'sales' && ($isSalesManager || ($isTechLead && $user->department === 'Sales'))) {
            $canAssign = true;
        } elseif ($reqTeam === strtolower($user->department) && $isTechLead) {
            $canAssign = true;
        }

        if (!$canAssign) {
            return back()->with('error', 'Bạn không có quyền gán nhân sự cho yêu cầu này.');
        }

        $assignee = User::find($request->assigned_to);

        $marketingRequest->update([
            'assigned_to' => $assignee->id,
            // Trạng thái cập nhật: đã tiếp nhận/đang thực hiện
            'status' => 'in_progress',
        ]);

        // Tạo log comment tự động
        MarketingRequestComment::create([
            'marketing_request_id' => $marketingRequest->id,
            'user_id' => $user->id,
            'comment' => "Đã phân công xử lý cho: " . $assignee->name,
        ]);

        // Cập nhật trạng thái ticket
        $marketingRequest->ticket->update(['status' => 'in_progress']);

        return back()->with('success', 'Đã phân công nhân sự thành công.');
    }

    /**
     * Nhân sự được gán xác nhận Accept yêu cầu
     */
    public function acceptRequest(MarketingRequest $marketingRequest)
    {
        if ($marketingRequest->assigned_to !== Auth::id()) {
            return back()->with('error', 'Bạn không phải là nhân sự được gán cho yêu cầu này.');
        }

        $marketingRequest->update(['status' => 'in_progress']);

        MarketingRequestComment::create([
            'marketing_request_id' => $marketingRequest->id,
            'user_id' => Auth::id(),
            'comment' => "Đã chấp nhận và bắt đầu thực hiện yêu cầu.",
        ]);

        return back()->with('success', 'Đã chuyển trạng thái sang Đang thực hiện.');
    }

    /**
     * Cập nhật trạng thái Yêu cầu (Duyệt, Thanh toán, Hoàn thành)
     */
    public function updateStatus(Request $request, MarketingRequest $marketingRequest)
    {
        $request->validate([
            'status' => 'required|in:completed,rejected,pending_payment,in_progress,pending_approval',
            'comment' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        $targetStatus = $request->status;

        // Xử lý logic cụ thể theo loại yêu cầu
        if ($marketingRequest->ticket->type === 'business_trip') {
            // Chỉ BOD (director) mới được duyệt công tác
            if (!$user->hasRole('director') && !$user->hasRole('super_admin')) {
                return back()->with('error', 'Chỉ BOD mới có quyền duyệt yêu cầu đi công tác.');
            }

            if ($targetStatus === 'completed') {
                $marketingRequest->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);
                MarketingRequestComment::create([
                    'marketing_request_id' => $marketingRequest->id,
                    'user_id' => $user->id,
                    'comment' => "BOD đã duyệt yêu cầu công tác. " . ($request->comment ? "Ghi chú: " . $request->comment : ''),
                ]);
            } elseif ($targetStatus === 'rejected') {
                $marketingRequest->update(['status' => 'rejected']);
                MarketingRequestComment::create([
                    'marketing_request_id' => $marketingRequest->id,
                    'user_id' => $user->id,
                    'comment' => "BOD đã từ chối yêu cầu công tác. Lý do: " . ($request->comment ?? 'Không có lý do chi tiết.'),
                ]);
            }

        } elseif ($marketingRequest->ticket->type === 'payment') {
            // Thanh toán có 2 bước duyệt:
            // Bước 1: BOD duyệt (Chuyển sang chờ thanh toán)
            // Bước 2: Kế toán thanh toán (Chuyển sang hoàn thành)

            if ($marketingRequest->status === 'pending_approval') {
                // Chỉ BOD duyệt bước 1
                if (!$user->hasRole('director') && !$user->hasRole('super_admin')) {
                    return back()->with('error', 'Chỉ BOD mới có quyền phê duyệt chi phí thanh toán.');
                }

                if ($targetStatus === 'pending_payment') {
                    $marketingRequest->update(['status' => 'pending_payment']);
                    MarketingRequestComment::create([
                        'marketing_request_id' => $marketingRequest->id,
                        'user_id' => $user->id,
                        'comment' => "BOD đã phê duyệt chi. Chuyển hồ sơ sang bộ phận Kế toán thanh toán.",
                    ]);
                } elseif ($targetStatus === 'rejected') {
                    $marketingRequest->update(['status' => 'rejected']);
                    MarketingRequestComment::create([
                        'marketing_request_id' => $marketingRequest->id,
                        'user_id' => $user->id,
                        'comment' => "BOD đã từ chối phê duyệt chi. Lý do: " . ($request->comment ?? 'Không có lý do.'),
                    ]);
                }
            } elseif ($marketingRequest->status === 'pending_payment') {
                // Chỉ kế toán (accountant) thanh toán bước 2
                if (!$user->hasRole('accountant') && !$user->hasRole('super_admin')) {
                    return back()->with('error', 'Chỉ bộ phận Kế toán mới được xác nhận đã thanh toán.');
                }

                if ($targetStatus === 'completed') {
                    DB::beginTransaction();
                    try {
                        $marketingRequest->update([
                            'status' => 'completed',
                            'completed_at' => now()
                        ]);

                        // 1. Trừ tiền khỏi Quỹ Hãng nếu có liên kết
                        $fund = $marketingRequest->fund;
                        if ($fund) {
                            $fund->used_amount += $marketingRequest->amount;
                            $fund->remaining_amount = $fund->amount - $fund->used_amount;
                            $fund->save();

                            // Ghi nhận giao dịch chi phí quỹ
                            MarketingSupplierTransaction::create([
                                'supplier_id'                => $fund->supplier_id,
                                'marketing_supplier_fund_id' => $fund->id,
                                'marketing_event_id'         => $marketingRequest->marketing_event_id,
                                'marketing_request_id'       => $marketingRequest->id,
                                'type'                       => 'expense',
                                'amount'                     => $marketingRequest->amount,
                                'note'                       => "Chi phí thanh toán từ quỹ: " . ($marketingRequest->description ?? $fund->name),
                                'created_by'                 => $user->id,
                            ]);
                        }

                        // 2. Ghi nhận công nợ hãng phải thu nếu tích chọn supplier_debt_checked
                        if ($marketingRequest->supplier_debt_checked) {
                            $supplierId = $fund->supplier_id ?? $marketingRequest->event->vendor_id;
                            if (!$supplierId) {
                                // Default to first supplier if none linked, or throw warning
                                $firstSup = \App\Models\Supplier::first();
                                $supplierId = $firstSup ? $firstSup->id : 1;
                            }

                            MarketingSupplierTransaction::create([
                                'supplier_id'                => $supplierId,
                                'marketing_supplier_fund_id' => $marketingRequest->marketing_supplier_fund_id,
                                'marketing_event_id'         => $marketingRequest->marketing_event_id,
                                'marketing_request_id'       => $marketingRequest->id,
                                'type'                       => 'receivable',
                                'amount'                     => $marketingRequest->amount,
                                'status'                     => 'pending',
                                'note'                       => "Công nợ hãng hỗ trợ sự kiện: " . ($marketingRequest->event->title ?? ''),
                                'created_by'                 => $user->id,
                            ]);
                        }

                        MarketingRequestComment::create([
                            'marketing_request_id' => $marketingRequest->id,
                            'user_id' => $user->id,
                            'comment' => "Kế toán đã hoàn tất thanh toán/tạm ứng tiền mặt/chuyển khoản.",
                        ]);

                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        return back()->with('error', 'Lỗi khi hoàn thành thanh toán: ' . $e->getMessage());
                    }
                }
            }

        } else {
            // Yêu cầu phối hợp hoặc yêu cầu khác (Internal Collaboration & Others)
            $isMarketingOrAdmin = $user->hasRole('super_admin') || $user->hasRole('admin') || $user->hasRole('marketing') || $user->hasRole('marketing_manager') || $user->hasRole('director');

            // Xử lý riêng cho Ticket Quà tặng / Phối hợp trực tiếp từ Cơ hội
            if ($marketingRequest->opportunity_id || strtolower($marketingRequest->support_team) === 'marketing') {
                if (!$isMarketingOrAdmin && $marketingRequest->assigned_to !== $user->id) {
                    return back()->with('error', 'Bạn không có quyền cập nhật trạng thái cho yêu cầu quà tặng này.');
                }

                $marketingRequest->update([
                    'status' => $targetStatus,
                    'completed_at' => $targetStatus === 'completed' ? now() : null,
                ]);

                MarketingRequestComment::create([
                    'marketing_request_id' => $marketingRequest->id,
                    'user_id' => $user->id,
                    'comment' => $targetStatus === 'completed'
                        ? ("Marketing đã xác nhận hoàn thành bàn giao vật phẩm / quà tặng cho Sales. " . ($request->comment ? "Ghi chú: " . $request->comment : ''))
                        : ("Đã cập nhật trạng thái ticket sang: " . $marketingRequest->status_label . ". " . ($request->comment ? "Ghi chú: " . $request->comment : '')),
                ]);

                if ($marketingRequest->ticket) {
                    $marketingRequest->ticket->update(['status' => $targetStatus]);
                }

                return back()->with('success', 'Đã cập nhật trạng thái ticket quà tặng thành công.');
            }

            if ($marketingRequest->status === 'pending_approval') {
                // Trạng thái đang chờ duyệt, chỉ Marketing/Admin mới được duyệt/từ chối
                if (!$isMarketingOrAdmin) {
                    return back()->with('error', 'Chỉ bộ phận Marketing mới được quyền phê duyệt yêu cầu này.');
                }

                $request->validate([
                    'status' => 'required|in:completed,rejected',
                    'comment' => 'nullable|string|max:500',
                ]);

                if ($targetStatus === 'completed') {
                    $marketingRequest->update([
                        'status' => 'completed',
                        'completed_at' => now()
                    ]);
                    MarketingRequestComment::create([
                        'marketing_request_id' => $marketingRequest->id,
                        'user_id' => $user->id,
                        'comment' => "Marketing đã duyệt hoàn thành yêu cầu. " . ($request->comment ? "Ghi chú: " . $request->comment : ''),
                    ]);
                } else {
                    // Từ chối -> đưa về in_progress để PIC làm lại
                    $marketingRequest->update([
                        'status' => 'in_progress'
                    ]);
                    MarketingRequestComment::create([
                        'marketing_request_id' => $marketingRequest->id,
                        'user_id' => $user->id,
                        'comment' => "Marketing đã từ chối kết quả và yêu cầu làm lại. Lý do: " . ($request->comment ?? 'Không có lý do chi tiết.'),
                    ]);
                }
            } else {
                // Trạng thái khác (received, in_progress, overdue) -> PIC nộp báo cáo kết quả lên Marketing
                // Phải là người được gán thực hiện hoặc Lead của bộ phận phụ trách mới được nộp kết quả
                $isTechLead = str_contains(strtolower($user->position), 'manager') || str_contains(strtolower($user->position), 'lead');
                $isSalesManager = $user->hasRole('sales_manager') || ($isTechLead && $user->department === 'Sales');

                $reqTeam = strtolower($marketingRequest->support_team);
                $canSubmit = false;

                if ($isMarketingOrAdmin || $marketingRequest->assigned_to === $user->id) {
                    $canSubmit = true;
                } elseif ($reqTeam === 'technical' && $isTechLead && ($user->department === 'Technical' || $user->department === 'Tech' || $user->department === 'IT')) {
                    $canSubmit = true;
                } elseif ($reqTeam === 'sales' && $isSalesManager) {
                    $canSubmit = true;
                } elseif ($reqTeam === strtolower($user->department) && $isTechLead) {
                    $canSubmit = true;
                }

                if (!$canSubmit) {
                    return back()->with('error', 'Bạn không có quyền gửi kết quả cho yêu cầu này.');
                }

                // Kiểm tra slide bắt buộc nếu là Speaker
                if ($marketingRequest->support_content === 'speaker') {
                    $hasFile = !empty($marketingRequest->attachment_path) || $request->hasFile('presentation_file');
                    if (!$hasFile) {
                        $hasCommentFile = MarketingRequestComment::where('marketing_request_id', $marketingRequest->id)
                            ->whereNotNull('attachment_path')
                            ->exists();
                        $hasFile = $hasCommentFile;
                    }
                    if (!$hasFile) {
                        return back()->with('error', 'Yêu cầu Speaker bắt buộc đính kèm file thuyết trình/tài liệu slide.');
                    }
                }

                // Đối với Sales Customer List: Bắt buộc đính kèm file Excel
                if ($marketingRequest->support_team === 'sales' && $marketingRequest->support_content === 'customer_list') {
                    $hasFile = !empty($marketingRequest->attachment_path) || $request->hasFile('presentation_file');
                    if (!$hasFile) {
                        return back()->with('error', 'Bắt buộc đính kèm file Excel danh sách khách hàng.');
                    }
                }

                // Upload file đính kèm kết quả
                if ($request->hasFile('presentation_file')) {
                    $file = $request->file('presentation_file');
                    $path = $file->store('marketing_requests', 'public');
                    $currentFiles = $marketingRequest->attachment_path ?? [];
                    $currentFiles[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'url'  => asset('storage/' . $path)
                    ];
                    $marketingRequest->attachment_path = $currentFiles;
                }

                // Thay vì sang completed trực tiếp, chuyển sang pending_approval để chờ Marketing duyệt
                $marketingRequest->status = 'pending_approval';
                $marketingRequest->save();

                MarketingRequestComment::create([
                    'marketing_request_id' => $marketingRequest->id,
                    'user_id' => $user->id,
                    'comment' => "Đã nộp báo cáo kết quả thực hiện. Chờ Marketing duyệt hoàn thành. Ghi chú: " . ($request->comment ?? 'Không có ghi chú.'),
                ]);
            }
        }

        // Kiểm tra xem tất cả các request trong cùng 1 ticket đã hoàn thành chưa để hoàn tất ticket
        $ticket = $marketingRequest->ticket;
        $totalReqs = $ticket->requests()->count();
        $completedReqs = $ticket->requests()->where('status', 'completed')->count();
        if ($totalReqs === $completedReqs) {
            $ticket->update(['status' => 'completed']);
        }

        return back()->with('success', 'Đã cập nhật trạng thái thành công.');
    }

    /**
     * Thảo luận / Thêm phản hồi trao đổi
     */
    public function addComment(Request $request, MarketingRequest $marketingRequest)
    {
        $request->validate([
            'comment' => 'required|string',
            'attachment' => 'nullable|file',
        ]);

        $filePath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filePath = $file->store('marketing_comments', 'public');
        }

        MarketingRequestComment::create([
            'marketing_request_id' => $marketingRequest->id,
            'user_id' => Auth::id(),
            'comment' => $request->comment,
            'attachment_path' => $filePath,
        ]);

        return back()->with('success', 'Đã gửi phản hồi.');
    }

    private function normalizeMoneyFields(Request $request, array $fields): void
    {
        $normalized = [];
        foreach ($fields as $field) {
            if (!$request->has($field)) {
                continue;
            }
            $raw = $request->input($field);
            if ($raw === null || trim((string) $raw) === '') {
                continue;
            }
            $clean = preg_replace('/[,\s]/', '', $raw);
            $normalized[$field] = $clean;
        }
        if (!empty($normalized)) {
            $request->merge($normalized);
        }
    }

    /**
     * Phân bổ quà tặng / vật phẩm trực tiếp từ Ticket
     */
    public function allocateItems(Request $request, MarketingRequest $marketingRequest)
    {
        $user = auth()->user();
        $isMarketingOrAdmin = $user->hasAnyRole(['super_admin', 'admin', 'director', 'marketing', 'marketing_manager']);
        if (!$isMarketingOrAdmin && $marketingRequest->assigned_to !== $user->id) {
            return back()->with('error', 'Bạn không có quyền phân bổ quà tặng cho ticket này.');
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:marketing_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($validated, $marketingRequest, $user) {
                $allocatedSummary = [];
                $ticketCode = $marketingRequest->ticket?->code ?: $marketingRequest->code;
                $targetName = $marketingRequest->opportunity?->name ?: $marketingRequest->event?->title ?: ('Ticket ' . $ticketCode);

                foreach ($validated['items'] as $itemData) {
                    $item = MarketingItem::lockForUpdate()->find($itemData['item_id']);
                    if (!$item || $item->stock_quantity < $itemData['quantity']) {
                        throw new \Exception("Vật phẩm '" . ($item?->name ?? 'Không xác định') . "' không đủ tồn kho (Còn: " . ($item?->stock_quantity ?? 0) . ", yêu cầu: " . $itemData['quantity'] . ").");
                    }

                    $newStock = $item->stock_quantity - $itemData['quantity'];
                    $item->update(['stock_quantity' => $newStock]);

                    MarketingItemTransaction::create([
                        'marketing_item_id'  => $item->id,
                        'type'               => 'export',
                        'quantity'           => $itemData['quantity'],
                        'remaining_stock'    => $newStock,
                        'opportunity_id'     => $marketingRequest->opportunity_id,
                        'marketing_event_id' => $marketingRequest->marketing_event_id,
                        'created_by'         => $user->id,
                        'reference_code'     => 'EXP-' . $ticketCode,
                        'note'               => $validated['note'] ?: ("Xuất quà cho Ticket " . $ticketCode . " - " . $targetName),
                    ]);

                    $allocatedSummary[] = "{$item->name} (x{$itemData['quantity']} {$item->unit})";
                }

                // Tự động chuyển trạng thái Ticket sang in_progress nếu đang là received / pending_approval
                if (in_array($marketingRequest->status, ['received', 'pending_approval', 'pending'])) {
                    $marketingRequest->update(['status' => 'in_progress']);
                    if ($marketingRequest->ticket) {
                        $marketingRequest->ticket->update(['status' => 'in_progress']);
                    }
                }

                // Ghi nhận lịch sử vào Trao đổi / Thảo luận
                MarketingRequestComment::create([
                    'marketing_request_id' => $marketingRequest->id,
                    'user_id'              => $user->id,
                    'comment'              => "Đã phân bổ vật phẩm quà tặng từ kho: " . implode(', ', $allocatedSummary) . ($validated['note'] ? " (Ghi chú: {$validated['note']})" : ""),
                ]);
            });

            return back()->with('success', 'Đã phân bổ quà tặng từ kho cho ticket thành công.');
        } catch (\Exception $e) {
            return back()->with('error', 'Lỗi khi phân bổ quà tặng: ' . $e->getMessage());
        }
    }

    /**
     * Hoàn trả vật phẩm quà tặng đã phân bổ về kho
     */
    public function removeItemTransaction(MarketingRequest $marketingRequest, MarketingItemTransaction $transaction)
    {
        $user = auth()->user();
        $isMarketingOrAdmin = $user->hasAnyRole(['super_admin', 'admin', 'director', 'marketing', 'marketing_manager']);
        if (!$isMarketingOrAdmin && $marketingRequest->assigned_to !== $user->id) {
            return back()->with('error', 'Bạn không có quyền hoàn tác phân bổ quà tặng.');
        }

        if ($transaction->type !== 'export') {
            return back()->with('error', 'Chỉ có thể hoàn tác giao dịch xuất quà.');
        }

        // Verify ownership
        if ($marketingRequest->opportunity_id && $transaction->opportunity_id != $marketingRequest->opportunity_id) {
            return back()->with('error', 'Giao dịch không thuộc về cơ hội của ticket này.');
        }
        if ($marketingRequest->marketing_event_id && $transaction->marketing_event_id != $marketingRequest->marketing_event_id) {
            return back()->with('error', 'Giao dịch không thuộc về sự kiện của ticket này.');
        }

        DB::transaction(function () use ($transaction, $marketingRequest, $user) {
            $item = MarketingItem::lockForUpdate()->find($transaction->marketing_item_id);
            if ($item) {
                $item->increment('stock_quantity', $transaction->quantity);
            }

            MarketingRequestComment::create([
                'marketing_request_id' => $marketingRequest->id,
                'user_id'              => $user->id,
                'comment'              => "Đã hoàn trả vật phẩm quà tặng về kho: " . ($item?->name ?? 'Vật phẩm') . " (x{$transaction->quantity})",
            ]);

            $transaction->delete();
        });

        return back()->with('success', 'Đã hoàn trả vật phẩm quà tặng về kho thành công.');
    }
}
