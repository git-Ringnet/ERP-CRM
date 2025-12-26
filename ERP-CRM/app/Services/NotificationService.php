<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\InventoryTransaction;

class NotificationService
{
    /**
     * Tạo thông báo khi phiếu nhập kho được tạo
     */
    public function notifyImportCreated(InventoryTransaction $import, array $recipientUserIds): void
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
    public function notifyExportCreated(InventoryTransaction $export, array $recipientUserIds): void
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
    public function notifyTransferCreated(InventoryTransaction $transfer, array $recipientUserIds): void
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
     */
    public function notifyDocumentApproved(InventoryTransaction $document, string $documentType, int $creatorUserId): void
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
     */
    public function notifyDocumentRejected(InventoryTransaction $document, string $documentType, int $creatorUserId, string $reason): void
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
}
