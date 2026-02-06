<?php

namespace App\Traits;

use App\Services\ActivityLogService;

trait LogsActivity
{
    /**
     * Boot the trait
     */
    protected static function bootLogsActivity()
    {
        $service = app(ActivityLogService::class);

        static::created(function ($model) use ($service) {
            $service->logCreated($model);
            // Mark the model as just created to avoid duplicate update logs
            $model->_justCreated = true;
        });

        static::updated(function ($model) use ($service) {
            // Tránh log duplicate khi model vừa được tạo
            // Nếu model vừa tạo trong vòng 2 giây, bỏ qua log update
            if (isset($model->_justCreated) && $model->_justCreated) {
                return;
            }

            // Kiểm tra xem model có vừa được tạo (trong 2 giây)
            if ($model->wasRecentlyCreated || 
                ($model->created_at && $model->created_at->diffInSeconds(now()) < 2)) {
                return;
            }

            // Chỉ log nếu có thay đổi thực sự
            if (!$model->wasChanged()) {
                return;
            }

            // Kiểm tra xem có phải đang duyệt/xác nhận không
            $approvalStatuses = ['approved', 'confirmed', 'completed'];
            $statusFields = ['status', 'approval_status'];
            
            foreach ($statusFields as $field) {
                if ($model->wasChanged($field) && 
                    in_array($model->$field, $approvalStatuses)) {
                    // Đây là action duyệt, không phải update thông thường
                    $service->logApproved($model);
                    return;
                }
            }

            // Log update bình thường
            $service->logUpdated(
                $model,
                $model->getOriginal(),
                $model->getAttributes()
            );
        });

        static::deleted(function ($model) use ($service) {
            $service->logDeleted($model);
        });
    }
}
