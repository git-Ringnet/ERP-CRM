<?php

namespace App\Services;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalLevel;
use App\Models\ApprovalHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ApprovalService
{
    /**
     * Gửi duyệt chứng từ
     */
    public function submit(Model $document, string $documentType): array
    {
        $workflow = ApprovalWorkflow::getForDocumentType($documentType);
        if (!$workflow || $workflow->levels->isEmpty()) {
            return ['success' => false, 'message' => 'Chưa cấu hình quy trình duyệt cho loại chứng từ này.'];
        }

        $amount = $document->total ?? 0;
        $firstLevel = $this->findNextApplicableLevel($workflow, 0, $amount);

        if (!$firstLevel) {
            // Không có cấp nào cần duyệt (số tiền nhỏ), tự động duyệt luôn
            $document->update(['status' => 'approved', 'current_approval_level' => 0]);
            return ['success' => true, 'message' => 'Chứng từ đã được tự động duyệt do không cần cấp duyệt nào.', 'auto_approved' => true];
        }

        DB::beginTransaction();
        try {
            $document->update([
                'status' => 'pending',
                'current_approval_level' => 0, // Bắt đầu từ 0, chưa duyệt cấp nào
            ]);

            // Tạo bản ghi chờ duyệt cho cấp đầu tiên phù hợp
            $this->createPendingHistory($document, $documentType, $firstLevel);

            // Ghi nhận các cấp bị bỏ qua (nếu có)
            $skippedLevels = $workflow->levels()
                ->where('level', '>', 0)
                ->where('level', '<', $firstLevel->level)
                ->get();
            
            foreach ($skippedLevels as $sl) {
                $this->logSkipped($document, $documentType, $sl);
            }

            DB::commit();
            return ['success' => true, 'message' => 'Đã gửi duyệt thành công.'];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }

    /**
     * Xử lý duyệt cấp hiện tại
     */
    public function approve(Model $document, string $documentType, string $comment = null): array
    {
        if ($document->status !== 'pending') {
            return ['success' => false, 'message' => 'Chứng từ không ở trạng thái chờ duyệt.'];
        }

        $workflow = ApprovalWorkflow::getForDocumentType($documentType);
        $nextLevel = $this->getNextLevelToApprove($document, $documentType, $workflow);

        if (!$nextLevel) {
            return ['success' => false, 'message' => 'Không tìm thấy cấp duyệt tiếp theo.'];
        }

        // Kiểm tra quyền duyệt
        if (!$this->canUserAction($document, $documentType, Auth::user(), $nextLevel)) {
            return ['success' => false, 'message' => 'Bạn không có quyền duyệt cấp này hoặc không được ủy quyền.'];
        }
// ... (rest of method remains mostly same, just checking the usage)

        DB::beginTransaction();
        try {
            $user = Auth::user();
            $amount = $document->total ?? 0;
            $currentLevel = $nextLevel;
            
            while ($currentLevel) {
                $currentHistory = $this->getPendingHistory($document, $documentType, $currentLevel->level);
                
                $historyData = [
                    'approver_id' => $user->id,
                    'approver_name' => $user->name,
                    'action' => 'approved',
                    'comment' => $currentLevel->level === $nextLevel->level ? $comment : 'Hệ thống tự động duyệt (Cùng người duyệt)',
                    'action_at' => now(),
                ];

                if ($currentHistory) {
                    $currentHistory->update($historyData);
                } else {
                    // Trường hợp hy hữu không có history pending
                    ApprovalHistory::create(array_merge([
                        'document_type' => $documentType,
                        'document_id' => $document->id,
                        'level' => $currentLevel->level,
                        'level_name' => $currentLevel->name,
                    ], $historyData));
                }

                $document->current_approval_level = $currentLevel->level;
                
                // Tìm cấp tiếp theo phù hợp số tiền
                $nextNextLevel = $this->findNextApplicableLevel($workflow, $currentLevel->level, $amount);

                if (!$nextNextLevel) {
                    // Hoàn thành quy trình
                    $document->status = 'approved';
                    $currentLevel = null;
                } else {
                    // Ghi nhận các cấp bị bỏ qua ở giữa
                    $skippedLevels = $workflow->levels()
                        ->where('level', '>', $currentLevel->level)
                        ->where('level', '<', $nextNextLevel->level)
                        ->get();
                    
                    foreach ($skippedLevels as $sl) {
                        $this->logSkipped($document, $documentType, $sl);
                    }

                    // TỰ ĐỘNG DUYỆT CẤP TIẾP THEO nếu người này có quyền
                    if ($nextNextLevel->canApprove($user, $amount)) {
                        $currentLevel = $nextNextLevel;
                    } else {
                        // Tạo pending cho cấp tiếp theo và dừng lại
                        $this->createPendingHistory($document, $documentType, $nextNextLevel);
                        $currentLevel = null;
                    }
                }
            }

            $document->save();
            DB::commit();

            return [
                'success' => true, 
                'message' => $document->status === 'approved' ? 'Chứng từ đã được duyệt hoàn tất.' : 'Đã duyệt đến cấp ' . $document->current_approval_level . '.'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()];
        }
    }

    /**
     * Từ chối duyệt
     */
    public function reject(Model $document, string $documentType, string $comment): array
    {
        if ($document->status !== 'pending') {
            return ['success' => false, 'message' => 'Chứng từ không ở trạng thái chờ duyệt.'];
        }

        $workflow = ApprovalWorkflow::getForDocumentType($documentType);
        $nextLevel = $this->getNextLevelToApprove($document, $documentType, $workflow);

        if (!$nextLevel) {
            return ['success' => false, 'message' => 'Không tìm thấy cấp duyệt hiện tại.'];
        }

        if (!$this->canUserAction($document, $documentType, Auth::user(), $nextLevel)) {
            return ['success' => false, 'message' => 'Bạn không có quyền từ chối cấp này.'];
        }

        DB::beginTransaction();
        try {
            $currentHistory = $this->getPendingHistory($document, $documentType, $nextLevel->level);
            
            $historyData = [
                'approver_id' => Auth::id(),
                'approver_name' => Auth::user()->name,
                'action' => 'rejected',
                'comment' => $comment,
                'action_at' => now(),
            ];

            if ($currentHistory) {
                $currentHistory->update($historyData);
            } else {
                ApprovalHistory::create(array_merge([
                    'document_type' => $documentType,
                    'document_id' => $document->id,
                    'level' => $nextLevel->level,
                    'level_name' => $nextLevel->name,
                ], $historyData));
            }

            $document->update(['status' => 'rejected']);
            
            DB::commit();
            return ['success' => true, 'message' => 'Chứng từ đã bị từ chối.'];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    /**
     * Ủy quyền/Chuyển người duyệt cho cấp hiện tại
     */
    public function delegate(Model $document, string $documentType, int $toUserId, string $comment = null): array
    {
        if ($document->status !== 'pending') {
            return ['success' => false, 'message' => 'Chứng từ không ở trạng thái chờ duyệt.'];
        }

        $workflow = ApprovalWorkflow::getForDocumentType($documentType);
        $nextLevel = $this->getNextLevelToApprove($document, $documentType, $workflow);
        $toUser = User::find($toUserId);

        if (!$nextLevel || !$toUser) {
            return ['success' => false, 'message' => 'Thông tin không hợp lệ.'];
        }

        // Chỉ người có quyền duyệt hiện tại mới có thể ủy quyền (hoặc Admin)
        if (!$this->canUserAction($document, $documentType, Auth::user(), $nextLevel)) {
            return ['success' => false, 'message' => 'Bạn không có quyền chuyển lượt duyệt của cấp này.'];
        }

        DB::beginTransaction();
        try {
            $currentHistory = $this->getPendingHistory($document, $documentType, $nextLevel->level);
            
            if ($currentHistory) {
                $currentHistory->update([
                    'original_approver_id' => Auth::id(),
                    'delegated_to_id' => $toUser->id,
                    'approver_name' => $currentHistory->approver_name . ' (Ủy quyền cho ' . $toUser->name . ')',
                    'comment' => $comment ? 'Chuyển quyền: ' . $comment : 'Chuyển quyền duyệt cho ' . $toUser->name,
                    'action' => 'delegated',
                ]);
            }

            // Tạo history pending mới cho người được ủy quyền
            ApprovalHistory::create([
                'document_type' => $documentType,
                'document_id' => $document->id,
                'level' => $nextLevel->level,
                'level_name' => $nextLevel->name,
                'approver_id' => $toUser->id,
                'approver_name' => $toUser->name,
                'action' => 'pending',
                'comment' => 'Nhận ủy quyền từ ' . Auth::user()->name,
            ]);

            DB::commit();
            return ['success' => true, 'message' => 'Đã chuyển quyền duyệt cho ' . $toUser->name];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }

    /**
     * Tìm cấp duyệt tiếp theo phù hợp với số tiền
     */
    public function findNextApplicableLevel(ApprovalWorkflow $workflow, int $currentLevel, float $amount): ?ApprovalLevel
    {
        return $workflow->levels()
            ->where('level', '>', $currentLevel)
            ->where(function ($query) use ($amount) {
                // Return if within amount range OR if explicitly required
                $query->where(function ($q) use ($amount) {
                    $q->where(function ($inner) use ($amount) {
                        $inner->whereNull('min_amount')->orWhere('min_amount', '<=', $amount);
                    })->where(function ($inner) use ($amount) {
                        $inner->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
                    });
                })->orWhere('is_required', true);
            })
            ->orderBy('level')
            ->first();
    }

    /**
     * Kiểm tra người dùng có quyền thao tác cấp này không
     */
    public function canUserAction(Model $document, string $documentType, User $user, ApprovalLevel $level): bool
    {
        // 1. Check if user is delegated for this specific pending history
        $isDelegated = ApprovalHistory::where('document_type', $documentType)
            ->where('document_id', $document->id)
            ->where('level', $level->level)
            ->where('action', 'pending')
            ->where('delegated_to_id', $user->id) // Wait, my delegation logic creates a NEW entry. 
            // Better check the LATEST pending entry for this level.
            ->exists();
            
        // Let's re-check the logic in delegate(). If it creates a new pending entry with approver_id = toUser->id
        $pendingEntry = $this->getPendingHistory($document, $documentType, $level->level);
        if ($pendingEntry && $pendingEntry->approver_id == $user->id) {
            return true;
        }

        // 2. Default check from ApprovalLevel
        return $level->canApprove($user, $document->total ?? 0);
    }

    private function getNextLevelToApprove(Model $document, string $documentType, ApprovalWorkflow $workflow): ?ApprovalLevel
    {
        $pending = ApprovalHistory::where('document_type', $documentType)
            ->where('document_id', $document->id)
            ->where('action', 'pending')
            ->orderBy('level')
            ->first();

        if (!$pending) return null;

        return $workflow->levels()->where('level', $pending->level)->first();
    }

    private function getPendingHistory(Model $document, string $documentType, int $level)
    {
        return ApprovalHistory::where('document_type', $documentType)
            ->where('document_id', $document->id)
            ->where('level', $level)
            ->where('action', 'pending')
            ->first();
    }

    private function createPendingHistory(Model $document, string $documentType, ApprovalLevel $level): void
    {
        // If approver_value contains commas (multiple users) or is a role slug, 
        // we store null in the ID column to avoid DB errors. 
        // The actual permission check happens in canUserAction().
        $approverId = ($level->approver_type === 'user' && is_numeric($level->approver_value)) 
            ? (int)$level->approver_value 
            : null;

        ApprovalHistory::create([
            'document_type' => $documentType,
            'document_id' => $document->id,
            'level' => $level->level,
            'level_name' => $level->name,
            'approver_id' => $approverId,
            'approver_name' => $level->approver_label,
            'action' => 'pending',
        ]);
    }

    private function logSkipped(Model $document, string $documentType, ApprovalLevel $level): void
    {
        ApprovalHistory::create([
            'document_type' => $documentType,
            'document_id' => $document->id,
            'level' => $level->level,
            'level_name' => $level->name,
            'approver_id' => null,
            'approver_name' => 'Hệ thống',
            'action' => 'skipped',
            'comment' => 'Tự động bỏ qua do giá trị chứng từ (' . number_format($document->total ?? 0) . ') không nằm trong hạn mức cấu hình.',
            'action_at' => now(),
        ]);
    }
}
