<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Import;
use App\Models\Export;
use App\Models\Transfer;
use App\Models\DamagedGood;

class NotificationService
{
    /**
     * Tạo thông báo khi phiếu nhập kho được tạo
     */
    public function notifyImportCreated(Import $import, array $recipientUserIds): void
    {
        $title = 'Phiếu nhập kho mới';
        $creatorName = $import->employee ? $import->employee->name : 'Người dùng';
        $message = "Phiếu nhập #{$import->code} đã được tạo bởi {$creatorName}";
        $link = route('imports.show', $import->id);
        
        foreach ($recipientUserIds as $userId) {
            $this->createNotification(
                $userId,
                'import_created',
                $title,
                $message,
                $link,
                'arrow-down',
                'blue',
                [
                    'document_id' => $import->id,
                    'document_type' => 'import',
                    'document_code' => $import->code,
                ]
            );
        }
    }

    /**
     * Tạo thông báo khi phiếu xuất kho được tạo
     */
    public function notifyExportCreated(Export $export, array $recipientUserIds): void
    {
        $title = 'Phiếu xuất kho mới';
        $creatorName = $export->employee ? $export->employee->name : 'Người dùng';
        $message = "Phiếu xuất #{$export->code} đã được tạo bởi {$creatorName}";
        $link = route('exports.show', $export->id);
        
        foreach ($recipientUserIds as $userId) {
            $this->createNotification(
                $userId,
                'export_created',
                $title,
                $message,
                $link,
                'arrow-up',
                'orange',
                [
                    'document_id' => $export->id,
                    'document_type' => 'export',
                    'document_code' => $export->code,
                ]
            );
        }
    }

    /**
     * Tạo thông báo khi phiếu chuyển kho được tạo
     */
    public function notifyTransferCreated(Transfer $transfer, array $recipientUserIds): void
    {
        $title = 'Phiếu chuyển kho mới';
        $creatorName = $transfer->employee ? $transfer->employee->name : 'Người dùng';
        $message = "Phiếu chuyển #{$transfer->code} đã được tạo bởi {$creatorName}";
        $link = route('transfers.show', $transfer->id);
        
        foreach ($recipientUserIds as $userId) {
            $this->createNotification(
                $userId,
                'transfer_created',
                $title,
                $message,
                $link,
                'exchange',
                'purple',
                [
                    'document_id' => $transfer->id,
                    'document_type' => 'transfer',
                    'document_code' => $transfer->code,
                ]
            );
        }
    }

    /**
     * Tạo thông báo khi phiếu được duyệt
     * Accepts Import, Export, or Transfer model
     */
    public function notifyDocumentApproved(Import|Export|Transfer $document, string $documentType, int $creatorUserId): void
    {
        $typeLabels = [
            'import' => 'nhập kho',
            'export' => 'xuất kho',
            'transfer' => 'chuyển kho',
        ];
        
        $typeLabel = $typeLabels[$documentType] ?? $documentType;
        $title = 'Phiếu đã được duyệt';
        $message = "Phiếu {$typeLabel} #{$document->code} của bạn đã được duyệt";
        
        $routeMap = [
            'import' => 'imports.show',
            'export' => 'exports.show',
            'transfer' => 'transfers.show',
        ];
        
        $link = route($routeMap[$documentType], $document->id);
        
        $this->createNotification(
            $creatorUserId,
            'approved',
            $title,
            $message,
            $link,
            'check',
            'green',
            [
                'document_id' => $document->id,
                'document_type' => $documentType,
                'document_code' => $document->code,
            ]
        );
    }

    /**
     * Tạo thông báo khi phiếu bị từ chối
     * Accepts Import, Export, or Transfer model
     */
    public function notifyDocumentRejected(Import|Export|Transfer $document, string $documentType, int $creatorUserId, string $reason): void
    {
        $typeLabels = [
            'import' => 'nhập kho',
            'export' => 'xuất kho',
            'transfer' => 'chuyển kho',
        ];
        
        $typeLabel = $typeLabels[$documentType] ?? $documentType;
        $title = 'Phiếu bị từ chối';
        $message = "Phiếu {$typeLabel} #{$document->code} của bạn đã bị từ chối. Lý do: {$reason}";
        
        $routeMap = [
            'import' => 'imports.show',
            'export' => 'exports.show',
            'transfer' => 'transfers.show',
        ];
        
        $link = route($routeMap[$documentType], $document->id);
        
        $this->createNotification(
            $creatorUserId,
            'rejected',
            $title,
            $message,
            $link,
            'times',
            'red',
            [
                'document_id' => $document->id,
                'document_type' => $documentType,
                'document_code' => $document->code,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Helper: Tạo thông báo chung
     */
    private function createNotification(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $link,
        ?string $icon,
        ?string $color,
        array $data = []
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'icon' => $icon,
            'color' => $color,
            'data' => $data,
        ]);
    }

    /**
     * Tạo thông báo khi báo cáo hàng hư hỏng được tạo
     */
    public function notifyDamagedGoodCreated(DamagedGood $damagedGood, array $recipientUserIds): void
    {
        $typeLabels = [
            'damaged' => 'hư hỏng',
            'expired' => 'hết hạn',
            'liquidation' => 'thanh lý',
        ];
        
        $typeLabel = $typeLabels[$damagedGood->type] ?? $damagedGood->type;
        $title = 'Báo cáo hàng ' . $typeLabel . ' mới';
        $creatorName = $damagedGood->discoveredBy ? $damagedGood->discoveredBy->name : 'Người dùng';
        $productName = $damagedGood->product ? $damagedGood->product->name : 'Sản phẩm';
        $message = "Báo cáo #{$damagedGood->code} - {$productName} ({$damagedGood->quantity} SP) được tạo bởi {$creatorName}";
        $link = route('damaged-goods.show', $damagedGood->id);
        
        foreach ($recipientUserIds as $userId) {
            $this->createNotification(
                $userId,
                'damaged_good_created',
                $title,
                $message,
                $link,
                'exclamation-triangle',
                'yellow',
                [
                    'document_id' => $damagedGood->id,
                    'document_type' => 'damaged_good',
                    'document_code' => $damagedGood->code,
                ]
            );
        }
    }

    /**
     * Tạo thông báo khi báo cáo hàng hư hỏng được duyệt
     */
    public function notifyDamagedGoodApproved(DamagedGood $damagedGood, int $creatorUserId): void
    {
        $title = 'Báo cáo hàng hư hỏng đã được duyệt';
        $message = "Báo cáo #{$damagedGood->code} của bạn đã được duyệt";
        $link = route('damaged-goods.show', $damagedGood->id);
        
        $this->createNotification(
            $creatorUserId,
            'damaged_good_approved',
            $title,
            $message,
            $link,
            'check',
            'green',
            [
                'document_id' => $damagedGood->id,
                'document_type' => 'damaged_good',
                'document_code' => $damagedGood->code,
            ]
        );
    }

    /**
     * Tạo thông báo khi báo cáo hàng hư hỏng bị từ chối
     */
    public function notifyDamagedGoodRejected(DamagedGood $damagedGood, int $creatorUserId, string $reason = ''): void
    {
        $title = 'Báo cáo hàng hư hỏng bị từ chối';
        $message = "Báo cáo #{$damagedGood->code} của bạn đã bị từ chối";
        if ($reason) {
            $message .= ". Lý do: {$reason}";
        }
        $link = route('damaged-goods.show', $damagedGood->id);
        
        $this->createNotification(
            $creatorUserId,
            'damaged_good_rejected',
            $title,
            $message,
            $link,
            'times',
            'red',
            [
                'document_id' => $damagedGood->id,
                'document_type' => 'damaged_good',
                'document_code' => $damagedGood->code,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Tạo thông báo khi lịch làm việc được tạo
     */
    public function notifyWorkScheduleCreated($schedule, array $recipientUserIds): void
    {
        $typeLabels = [
            'personal' => 'cá nhân',
            'group' => 'nhóm',
        ];
        
        $typeLabel = $typeLabels[$schedule->type] ?? $schedule->type;
        $title = 'Lịch làm việc mới';
        $creatorName = $schedule->creator ? $schedule->creator->name : 'Người dùng';
        $message = "Lịch {$typeLabel} '{$schedule->title}' đã được tạo bởi {$creatorName}";
        $link = '/work-schedules';
        
        foreach ($recipientUserIds as $userId) {
            $this->createNotification(
                $userId,
                'work_schedule_created',
                $title,
                $message,
                $link,
                'calendar',
                'blue',
                [
                    'schedule_id' => $schedule->id,
                    'schedule_title' => $schedule->title,
                    'schedule_type' => $schedule->type,
                    'start_datetime' => $schedule->start_datetime->format('Y-m-d H:i'),
                ]
            );
        }
    }

    /**
     * Tạo thông báo khi lịch làm việc sắp hết hạn (1 ngày trước)
     */
    public function notifyWorkScheduleUpcoming($schedule, array $recipientUserIds): void
    {
        $title = 'Lịch làm việc sắp đến hạn';
        $message = "Lịch '{$schedule->title}' sẽ đến hạn vào {$schedule->end_datetime->format('d/m/Y H:i')}";
        $link = '/work-schedules';
        
        foreach ($recipientUserIds as $userId) {
            $this->createNotification(
                $userId,
                'work_schedule_upcoming',
                $title,
                $message,
                $link,
                'clock',
                'yellow',
                [
                    'schedule_id' => $schedule->id,
                    'schedule_title' => $schedule->title,
                    'end_datetime' => $schedule->end_datetime->format('Y-m-d H:i'),
                ]
            );
        }
    }

    /**
     * Tạo thông báo khi lịch làm việc đã hết hạn
     */
    public function notifyWorkScheduleExpired($schedule, array $recipientUserIds): void
    {
        $title = 'Lịch làm việc đã hết hạn';
        $message = "Lịch '{$schedule->title}' đã hết hạn vào {$schedule->end_datetime->format('d/m/Y H:i')}";
        $link = '/work-schedules';
        
        foreach ($recipientUserIds as $userId) {
            $this->createNotification(
                $userId,
                'work_schedule_expired',
                $title,
                $message,
                $link,
                'exclamation-circle',
                'red',
                [
                    'schedule_id' => $schedule->id,
                    'schedule_title' => $schedule->title,
                    'end_datetime' => $schedule->end_datetime->format('Y-m-d H:i'),
                ]
            );
        }
    }

    /**
     * Tạo thông báo khi đơn hàng sắp tới hạn thanh toán (hoặc tới hạn hôm nay)
     */
    public function notifyPaymentDueSoon(\App\Models\Sale $sale): void
    {
        if (!$sale->user_id) {
            return;
        }
        $title = 'Đơn hàng sắp tới hạn thanh toán';
        $debtDays = $sale->customer?->debt_days ?? 30;
        $dueDate = $sale->invoice_date ? \Carbon\Carbon::parse($sale->invoice_date)->addDays($debtDays) : null;
        $dueDateFormatted = $dueDate ? $dueDate->format('d/m/Y') : 'N/A';
        $message = "Đơn hàng #{$sale->code} sắp tới hạn thanh toán (Hạn: {$dueDateFormatted})";
        $link = route('sales.show', $sale->id);

        $this->createNotification(
            $sale->user_id,
            'payment_due_soon',
            $title,
            $message,
            $link,
            'clock',
            'orange',
            [
                'sale_id' => $sale->id,
                'sale_code' => $sale->code,
                'payment_due_date' => $dueDate ? $dueDate->toDateString() : null,
            ]
        );
    }

    /**
     * Tạo thông báo khi đơn hàng quá hạn thanh toán
     */
    public function notifyPaymentOverdue(\App\Models\Sale $sale, int $overdueDays): void
    {
        if (!$sale->user_id) {
            return;
        }
        $title = 'Đơn hàng quá hạn thanh toán!';
        $debtDays = $sale->customer?->debt_days ?? 30;
        $dueDate = $sale->invoice_date ? \Carbon\Carbon::parse($sale->invoice_date)->addDays($debtDays) : null;
        $dueDateFormatted = $dueDate ? $dueDate->format('d/m/Y') : 'N/A';
        $message = "Đơn hàng #{$sale->code} đã quá hạn thanh toán {$overdueDays} ngày (Hạn: {$dueDateFormatted})";
        $link = route('sales.show', $sale->id);

        $this->createNotification(
            $sale->user_id,
            'payment_overdue',
            $title,
            $message,
            $link,
            'exclamation-triangle',
            'red',
            [
                'sale_id' => $sale->id,
                'sale_code' => $sale->code,
                'payment_due_date' => $dueDate ? $dueDate->toDateString() : null,
                'overdue_days' => $overdueDays,
            ]
        );
    }

    /**
     * Tạo thông báo khi Đăng ký dự án mới được gửi đến PO/PM Team
     */
    public function notifyProjectSubmittedToTeam(\App\Models\Project $project): void
    {
        $teamLabel = $project->assigned_team === 'po_team' ? 'PO Team (FTN)' : 'PM Team (Non-FTN)';
        $title = "Đăng ký dự án mới (#{$project->code})";
        $salesName = $project->manager ? $project->manager->name : 'Sales';
        $message = "Dự án '{$project->name}' đã được đăng ký bởi {$salesName}. Đã phân luồng cho {$teamLabel} (Hạn tiếp nhận: 4h).";
        $link = route('projects.show', $project->id);

        // Find users in target department or admin users
        $dept = $project->assigned_team === 'po_team' ? 'PO' : 'PM';
        $targetUserIds = \App\Models\User::where('department', $dept)
            ->orWhere('department', 'PM Team')
            ->orWhere('department', 'PO Team')
            ->pluck('id')
            ->toArray();

        if (empty($targetUserIds)) {
            // Fallback to all admins or system managers
            $targetUserIds = \App\Models\User::pluck('id')->toArray();
        }

        foreach (array_unique($targetUserIds) as $userId) {
            $this->createNotification(
                $userId,
                'project_submitted',
                $title,
                $message,
                $link,
                'folder-plus',
                'purple',
                [
                    'project_id' => $project->id,
                    'project_code' => $project->code,
                    'assigned_team' => $project->assigned_team,
                ]
            );
        }
    }

    /**
     * Thông báo kết quả tiếp nhận ĐKDA đến Sales
     */
    public function notifyProjectIntakeOutcome(\App\Models\Project $project, string $status, ?string $note = null): void
    {
        if (!$project->manager_id) {
            return;
        }

        $labels = [
            'registered' => 'Đã đăng ký dự án',
            'duplicate' => 'Dự án trùng với Sales khác',
            'incomplete' => 'Thông tin đăng ký chưa đầy đủ',
        ];

        $title = "Kết quả tiếp nhận ĐKDA #{$project->code}";
        $statusText = $labels[$status] ?? $status;
        $message = "Dự án '{$project->name}': {$statusText}";
        if ($note) {
            $message .= ". Ghi chú: {$note}";
        }
        $link = route('projects.show', $project->id);

        $color = match ($status) {
            'registered' => 'green',
            'duplicate' => 'orange',
            'incomplete' => 'yellow',
            default => 'blue',
        };

        $this->createNotification(
            $project->manager_id,
            'project_intake_result',
            $title,
            $message,
            $link,
            'clipboard-check',
            $color,
            [
                'project_id' => $project->id,
                'intake_status' => $status,
                'note' => $note,
            ]
        );
    }

    /**
     * Thông báo cho Sales khi Hãng đã báo giá/PM đính kèm file giá
     */
    public function notifyProjectVendorQuoted(\App\Models\Project $project): void
    {
        if (!$project->manager_id) {
            return;
        }

        $title = "Hãng đã báo giá/duyệt ĐKDA #{$project->code}";
        $message = "Dự án '{$project->name}' đã có báo giá từ Hãng. Vui lòng kiểm tra file đính kèm trong dự án.";
        $link = route('projects.show', $project->id);

        $this->createNotification(
            $project->manager_id,
            'project_vendor_quoted',
            $title,
            $message,
            $link,
            'file-text',
            'emerald',
            [
                'project_id' => $project->id,
                'vendor_deal_id' => $project->vendor_deal_id,
            ]
        );
    }

    /**
     * Thông báo nhắc Sales cập nhật tiến độ hàng tháng
     */
    public function notifyProjectSalesUpdateRequested(\App\Models\Project $project): void
    {
        if (!$project->manager_id) {
            return;
        }

        $title = "Yêu cầu cập nhật tiến độ dự án #{$project->code}";
        $message = "Dự án '{$project->name}' đã đến hạn cập nhật tiến độ định kỳ hàng tháng (Commit/Best Case/Close Deal).";
        $link = route('projects.show', $project->id);

        $this->createNotification(
            $project->manager_id,
            'project_update_requested',
            $title,
            $message,
            $link,
            'refresh-cw',
            'amber',
            [
                'project_id' => $project->id,
            ]
        );
    }
}

