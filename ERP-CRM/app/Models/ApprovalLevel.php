<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Role;

class ApprovalLevel extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'workflow_id',
        'level',
        'name',
        'approver_type',
        'approver_value',
        'min_amount',
        'max_amount',
        'is_required',
    ];

    protected $casts = [
        'level' => 'integer',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'is_required' => 'boolean',
    ];

    public function workflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    public function getApproverLabelAttribute(): string
    {
        if ($this->approver_type === 'role') {
            $roles = explode(',', $this->approver_value);
            $roleNames = [];
            foreach ($roles as $r) {
                $role = Role::where('slug', trim($r))->first();
                $roleNames[] = $role ? $role->name : $r;
            }
            return 'Vai trò: ' . implode(' hoặc ', $roleNames);
        }
        
        $ids = explode(',', $this->approver_value);
        if (count($ids) > 1) {
            $names = User::whereIn('id', $ids)->pluck('name')->toArray();
            return 'Nhóm người dùng: ' . implode(', ', $names);
        }

        $user = User::find($this->approver_value);
        return $user ? $user->name : 'Người dùng #' . $this->approver_value;
    }

    /**
     * Lấy danh sách người dùng được quyền duyệt cấp này (bao gồm cả Admin)
     */
    public function getApprovers(): \Illuminate\Support\Collection
    {
        $approvers = collect();

        // 1. Phân quyền theo Role
        if ($this->approver_type === 'role') {
            $roleSlugs = explode(',', $this->approver_value);
            foreach ($roleSlugs as $slug) {
                $role = Role::where('slug', trim($slug))->first();
                if ($role) {
                    $approvers = $approvers->merge($role->users()->where('status', 'active')->get());
                }
            }
        } 
        // 2. Phân quyền theo User ID cụ thể
        else {
            $userIds = explode(',', $this->approver_value);
            $users = User::whereIn('id', $userIds)->where('status', 'active')->get();
            $approvers = $approvers->merge($users);
        }

        // 3. Luôn bao gồm tất cả Admin (theo yêu cầu người dùng)
        $admins = User::whereHas('roles', function($q) {
            $q->where('slug', 'super_admin');
        })->where('status', 'active')->get();
        
        $approvers = $approvers->merge($admins);

        return $approvers->unique('id');
    }

    public function canApprove(?User $user, float $amount = 0): bool
    {
        if (!$user) return false;

        // Admin luôn có quyền duyệt
        if ($user->hasRole('super_admin')) return true;

        // Check amount conditions
        if ($this->min_amount && $amount < $this->min_amount) return false;
        if ($this->max_amount && $amount > $this->max_amount) return false;

        // Check approver
        if ($this->approver_type === 'user') {
            $allowedIds = explode(',', $this->approver_value);
            return in_array((string)$user->id, $allowedIds);
        }

        // Check role (supports comma separated roles)
        $roles = explode(',', $this->approver_value);
        foreach ($roles as $role) {
            if ($user->hasRole(trim($role))) {
                return true;
            }
        }

        return false;
    }
}
