<?php

namespace App\Models;

use App\Traits\HasRoles;
use App\Traits\HasPermissions;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, LogsActivity, HasRoles, HasPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_code',
        'birth_date',
        'phone',
        'address',
        'id_card',
        'department',
        'position',
        'join_date',
        'salary',
        'bank_account',
        'bank_name',
        'status',
        'is_locked',
        'note',
        'avatar',
        'timekeeping_type',
        'work_location_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
        'join_date' => 'date',
        'salary' => 'decimal:2',
    ];

    /**
     * Scope for searching employees by name, code, email, or phone
     * Requirements: 3.9
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('employee_code', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for filtering employees by department
     * Requirements: 3.10
     */
    public function scopeFilterByDepartment(Builder $query, ?string $department): Builder
    {
        if (empty($department)) {
            return $query;
        }

        return $query->where('department', $department);
    }

    /**
     * Scope for filtering employees by status
     * Requirements: 3.10
     */
    public function scopeFilterByStatus(Builder $query, ?string $status): Builder
    {
        if (empty($status)) {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * Tài sản / dụng cụ được cấp phát cho nhân viên này.
     */
    public function assetAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\EmployeeAssetAssignment::class, 'user_id');
    }

    public function activeAssets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\EmployeeAssetAssignment::class, 'user_id')
                    ->where('status', 'active');
    }

    /**
     * Kỹ năng của nhân viên (Skillset).
     */
    public function employeeSkills(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeSkill::class, 'user_id');
    }

    /**
     * Lịch sử chấm công.
     */
    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Attendance::class, 'user_id');
    }

    /**
     * Chi tiết các kỳ lương.
     */
    public function payrollItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PayrollItem::class, 'user_id');
    }

    /**
     * Cấu hình phụ cấp/khấu trừ riêng.
     */
    public function employeeSalaryComponents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class, 'user_id');
    }

    /**
     * Địa điểm làm việc của nhân viên.
     */
    public function workLocation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkLocation::class, 'work_location_id');
    }
}
