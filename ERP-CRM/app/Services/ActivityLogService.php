<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Ghi log hoạt động
     */
    public function log(
        string $action,
        ?Model $subject = null,
        ?array $properties = null,
        ?string $description = null
    ): ActivityLog {
        $user = Auth::user();
        
        return ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'properties' => $properties,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log khi tạo mới model
     */
    public function logCreated(Model $model, ?string $description = null): ActivityLog
    {
        $modelName = class_basename($model);
        $desc = $description ?? "Tạo mới {$modelName}: " . $this->getModelLabel($model);
        
        return $this->log('created', $model, [
            'attributes' => $model->getAttributes(),
        ], $desc);
    }

    /**
     * Log khi cập nhật model
     */
    public function logUpdated(Model $model, array $old, array $new): ActivityLog
    {
        $modelName = class_basename($model);
        $changes = $this->getChangedAttributes($old, $new);
        
        if (empty($changes)) {
            // Không có gì thay đổi thực sự, skip
            return $this->log('updated', $model, ['old' => $old, 'new' => $new], "Không có thay đổi");
        }
        
        $desc = "Cập nhật {$modelName}: " . $this->getModelLabel($model);
        
        return $this->log('updated', $model, [
            'old' => $old,
            'new' => $new,
            'changes' => $changes,
        ], $desc);
    }

    /**
     * Log khi xóa model
     */
    public function logDeleted(Model $model, ?string $description = null): ActivityLog
    {
        $modelName = class_basename($model);
        $desc = $description ?? "Xóa {$modelName}: " . $this->getModelLabel($model);
        
        return $this->log('deleted', $model, [
            'attributes' => $model->getAttributes(),
        ], $desc);
    }

    /**
     * Log when a record is approved/confirmed
     */
    public function logApproved(Model $model, ?string $customDescription = null): ActivityLog
    {
        $modelName = class_basename($model);
        $label = $this->getModelLabel($model);
        $description = $customDescription ?? "Duyệt {$modelName}: {$label}";
        
        return $this->log(
            'approved',
            $model,
            [
                'status' => $model->status ?? $model->approval_status ?? 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now()->toDateTimeString(),
            ],
            $description
        );
    }

    /**
     * Log khi user login
     */
    public function logLogin(User $user): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'action' => 'login',
            'description' => "Đăng nhập hệ thống",
            'subject_type' => null,
            'subject_id' => null,
            'properties' => null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Log khi user logout
     */
    public function logLogout(User $user): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'action' => 'logout',
            'description' => "Đăng xuất khỏi hệ thống",
            'subject_type' => null,
            'subject_id' => null,
            'properties' => null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Lấy label của model để hiển thị
     */
    protected function getModelLabel(Model $model): string
    {
        // Thử các field phổ biến
        if (isset($model->name)) return $model->name;
        if (isset($model->code)) return $model->code;
        if (isset($model->title)) return $model->title;
        if (isset($model->subject)) return $model->subject;
        
        return "ID: {$model->id}";
    }

    /**
     * Lấy danh sách attributes đã thay đổi
     */
    protected function getChangedAttributes(array $old, array $new): array
    {
        $changes = [];
        
        foreach ($new as $key => $value) {
            if (!array_key_exists($key, $old) || $old[$key] !== $value) {
                $changes[$key] = [
                    'old' => $old[$key] ?? null,
                    'new' => $value,
                ];
            }
        }
        
        return $changes;
    }
}
