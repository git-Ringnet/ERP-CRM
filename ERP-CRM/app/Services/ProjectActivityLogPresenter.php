<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ProjectActivityLogPresenter
{
    /**
     * Fields ignored in changes diff to keep log clean and user-friendly.
     */
    protected static array $ignoredFields = [
        'id',
        'created_at',
        'updated_at',
        'initial_processed_at',
        'initial_processed_by',
        'initial_sla_due_at',
        'vendor_submitted_at',
        'last_sales_updated_at',
        'last_sales_reminded_at',
        'last_vendor_reminded_at',
        'sales_reminder_count',
        'vendor_reminder_count',
        'missed_update_count',
        'customer_name',
        'marketing_event_id',
        'collaborate_customer_id',
        'customer_id',
        'keep_bom_files',
        'password',
        'remember_token',
    ];

    /**
     * Friendly labels for model attributes.
     */
    protected static array $fieldLabels = [
        'code' => 'Mã dự án',
        'name' => 'Tên dự án',
        'name_en' => 'Tên tiếng Anh dự án',
        'registration_status' => 'Trạng thái ĐKDA',
        'status' => 'Trạng thái dự án',
        'vendor_id' => 'Hãng / Nhà phân phối',
        'distributor_am' => 'AM Hãng phụ trách',
        'assigned_team' => 'Đội ngũ xử lý ĐKDA',
        'eu_name_vi' => 'Tên End-User (VN)',
        'eu_name_en' => 'Tên End-User (EN)',
        'eu_name_abbr' => 'Tên viết tắt End-User',
        'eu_tax_code' => 'Mã số thuế End-User',
        'eu_province' => 'Tỉnh / Thành phố',
        'eu_industry' => 'Ngành nghề End-User',
        'collaborate_type' => 'Hình thức hợp tác',
        'collaborate_company' => 'Công ty đại lý / đối tác',
        'collaborate_tax_code' => 'MST Công ty hợp tác',
        'collaborate_pic_name' => 'Người liên hệ đại lý',
        'collaborate_pic_title' => 'Chức vụ người liên hệ',
        'collaborate_pic_phone' => 'SĐT người liên hệ',
        'collaborate_pic_email' => 'Email người liên hệ',
        'budget' => 'Dự toán ngân sách',
        'net_to_tech_horizon' => 'Net Tech Horizon',
        'start_date' => 'Ngày bắt đầu',
        'end_date' => 'Ngày dự kiến kết thúc',
        'stage' => 'Giai đoạn cơ hội',
        'deal_type' => 'Loại cơ hội',
        'bom_file' => 'File BOM đính kèm',
        'bom_data' => 'Dữ liệu BOM',
        'special_request_type' => 'Yêu cầu đặc biệt',
        'special_request_note' => 'Ghi chú yêu cầu đặc biệt',
        'sn_numbers' => 'Số Serial Number (Trade up)',
        'intake_status' => 'Trạng thái tiếp nhận',
        'intake_note' => 'Ghi chú tiếp nhận',
        'duplicate_sales_info' => 'Thông tin Sales bị trùng',
        'vendor_due_at' => 'Hạn Hãng phản hồi (SLA)',
        'vendor_deal_id' => 'Mã Deal Hãng (Deal ID)',
        'vendor_quote_file' => 'File báo giá Hãng',
        'vendor_quote_note' => 'Phản hồi / Báo giá Hãng',
        'vendor_quote_valid_until' => 'Hiệu lực báo giá Hãng',
        'forecast_stage' => 'Dự báo Sales',
        'support_request_type' => 'Yêu cầu hỗ trợ',
        'support_request_note' => 'Chi tiết yêu cầu hỗ trợ',
        'close_status' => 'Trạng thái đóng',
        'close_reason' => 'Lý do đóng',
        'close_note' => 'Ghi chú đóng',
        'po_code' => 'Mã đơn hàng bán',
        'order_value' => 'Giá trị đơn hàng',
        'order_date' => 'Ngày đơn hàng',
        'manager_id' => 'Sales phụ trách',
        'description' => 'Mô tả dự án',
        'note' => 'Ghi chú',
        'address' => 'Địa chỉ',
    ];

    /**
     * Friendly dictionary translations for known values.
     */
    protected static array $valueLabels = [
        'registration_status' => [
            'submitted' => 'Mới đăng ký (Chờ tiếp nhận)',
            'processing' => 'Đang xử lý tiếp nhận',
            'vendor_processing' => 'Đã gửi Hãng (Chờ phản hồi)',
            'vendor_reminded' => 'Đã nhắc Hãng (Gia hạn SLA)',
            'vendor_quoted' => 'Hãng đã báo giá',
            'vendor_rejected' => 'Hãng từ chối ĐKDA',
            'update_status' => 'Đã đăng ký - Đang theo đuổi',
            'closed_won' => 'Closed Won (Thành công)',
            'closed_lost' => 'Closed Lost (Thất bại)',
            'cancelled' => 'Đã hủy',
            'expired' => 'Hết hạn (Expired)',
            'duplicate' => 'Dự án trùng',
            'incomplete' => 'Thiếu thông tin ĐKDA',
            'on_hold' => 'Tạm dừng (On Hold)',
        ],
        'status' => [
            'planning' => 'Lên kế hoạch',
            'in_progress' => 'Đang thực hiện',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
            'on_hold' => 'Tạm dừng',
        ],
        'forecast_stage' => [
            'commit' => 'Commit (Chắc chắn chốt)',
            'best_case' => 'Best Case (Khả năng cao)',
            'close_deal' => 'Close Deal (Đã chốt)',
        ],
        'support_request_type' => [
            'request_update_price' => 'Xin cập nhật / gia hạn giá Hãng',
            'presale_support' => 'Hỗ trợ kỹ thuật Pre-sale',
            'demo_poc' => 'Mượn thiết bị Demo / POC',
            'pricing_support' => 'Hỗ trợ thương lượng giá đặc biệt',
            'other' => 'Yêu cầu hỗ trợ khác',
        ],
        'collaborate_type' => [
            'partner' => 'Đại lý / Đối tác',
            'end_user' => 'Khách hàng trực tiếp (End-User)',
        ],
        'deal_type' => [
            'new' => 'Dự án mới (New Deal)',
            'renewal' => 'Tái tục bản quyền / Dịch vụ',
            'trade_up' => 'Nâng cấp thiết bị (Trade Up)',
        ],
        'intake_status' => [
            'pending' => 'Chờ tiếp nhận',
            'registered' => 'Đã tiếp nhận',
            'approved' => 'Đã duyệt tiếp nhận',
            'rejected' => 'Từ chối tiếp nhận',
            'incomplete' => 'Thiếu thông tin',
            'duplicate' => 'Trùng dự án',
        ],
        'assigned_team' => [
            'pm_team' => 'PM Team',
            'po_team' => 'PO Team',
        ],
    ];

    /**
     * Get User details & Role Badge
     */
    public static function getUserInfo(ActivityLog $log, ?Project $project = null): array
    {
        $user = $log->user;
        $userName = $log->user_name ?? ($user?->name ?? 'Người dùng');
        
        $roleTitle = 'Thành viên';
        $roleColor = 'bg-gray-100 text-gray-700 border-gray-200';
        $avatarBg = 'bg-gray-500';

        if ($user) {
            $dept = mb_strtoupper(trim($user->department ?? ''));
            $roleSlugs = $user->roles ? $user->roles->pluck('slug')->toArray() : [];

            if (in_array('super_admin', $roleSlugs) || in_array('admin', $roleSlugs)) {
                $roleTitle = 'Admin';
                $roleColor = 'bg-rose-50 text-rose-700 border-rose-200';
                $avatarBg = 'bg-rose-600';
            } elseif (in_array('director', $roleSlugs) || str_contains($dept, 'DIRECTOR') || str_contains($dept, 'GIÁM ĐỐC')) {
                $roleTitle = 'Ban Giám Đốc';
                $roleColor = 'bg-red-50 text-red-700 border-red-200';
                $avatarBg = 'bg-red-600';
            } elseif (str_contains($dept, 'PM') || in_array('pm', $roleSlugs)) {
                $roleTitle = 'PM Team';
                $roleColor = 'bg-purple-50 text-purple-700 border-purple-200';
                $avatarBg = 'bg-purple-600';
            } elseif (str_contains($dept, 'PO') || in_array('purchase_manager', $roleSlugs) || in_array('purchase_staff', $roleSlugs)) {
                $roleTitle = 'PO Team';
                $roleColor = 'bg-amber-50 text-amber-700 border-amber-200';
                $avatarBg = 'bg-amber-600';
            } elseif (in_array('sales_manager', $roleSlugs)) {
                $roleTitle = 'Sales Manager';
                $roleColor = 'bg-blue-50 text-blue-700 border-blue-200';
                $avatarBg = 'bg-blue-600';
            } elseif (in_array('sales_staff', $roleSlugs) || str_contains($dept, 'SALES') || str_contains($dept, 'KINH DOANH')) {
                $roleTitle = 'Sales';
                $roleColor = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                $avatarBg = 'bg-emerald-600';
            } elseif (in_array('accountant', $roleSlugs) || str_contains($dept, 'KẾ TOÁN')) {
                $roleTitle = 'Kế toán';
                $roleColor = 'bg-teal-50 text-teal-700 border-teal-200';
                $avatarBg = 'bg-teal-600';
            } else {
                $firstRole = $user->roles->first()?->name;
                $roleTitle = $firstRole ?: ($user->department ?: 'Thành viên');
                $roleColor = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                $avatarBg = 'bg-indigo-600';
            }
        } elseif ($project && $project->manager_id && $project->manager && $project->manager->name === $userName) {
            $roleTitle = 'Sales';
            $roleColor = 'bg-emerald-50 text-emerald-700 border-emerald-200';
            $avatarBg = 'bg-emerald-600';
        }

        // Generate initials
        $words = preg_split('/\s+/', trim($userName));
        $initials = '';
        if (count($words) >= 2) {
            $initials = mb_substr($words[0], 0, 1) . mb_substr($words[count($words) - 1], 0, 1);
        } else {
            $initials = mb_substr($userName, 0, 2);
        }

        return [
            'name' => $userName,
            'role_title' => $roleTitle,
            'role_color' => $roleColor,
            'avatar_bg' => $avatarBg,
            'initials' => mb_strtoupper($initials),
        ];
    }

    /**
     * Filter & Format changed attributes
     */
    public static function formatChanges(array $changes): array
    {
        $formatted = [];

        foreach ($changes as $field => $change) {
            if (in_array($field, self::$ignoredFields, true)) {
                continue;
            }

            $label = self::$fieldLabels[$field] ?? Str::headline($field);
            $oldVal = self::formatValue($field, $change['old'] ?? null);
            $newVal = self::formatValue($field, $change['new'] ?? null);

            // Skip if formatted values are identical
            if ($oldVal === $newVal) {
                continue;
            }

            $formatted[] = [
                'field' => $field,
                'label' => $label,
                'old' => $oldVal,
                'new' => $newVal,
            ];
        }

        return $formatted;
    }

    /**
     * Format a specific field value into readable Vietnamese text
     */
    public static function formatValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return 'Trống';
        }

        // Check value translation dictionary
        if (isset(self::$valueLabels[$field]) && is_scalar($value) && isset(self::$valueLabels[$field][(string)$value])) {
            return self::$valueLabels[$field][(string)$value];
        }

        // Format Currency
        if (in_array($field, ['budget', 'net_to_tech_horizon', 'order_value']) && is_numeric($value)) {
            return number_format((float)$value, 0, ',', '.') . ' đ';
        }

        // Format Dates
        if (in_array($field, ['start_date', 'end_date', 'order_date', 'vendor_quote_valid_until', 'vendor_due_at'])) {
            try {
                return Carbon::parse($value)->format('d/m/Y');
            } catch (\Exception $e) {
                return (string)$value;
            }
        }

        // Format Files (BOM or Quote files)
        if (in_array($field, ['bom_file', 'vendor_quote_file'])) {
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $value = $decoded;
                }
            }
            if (is_array($value)) {
                $fileNames = array_map(function ($f) {
                    $base = basename($f);
                    // Remove timestamp prefix if any (e.g. 1787839325_file.pdf -> file.pdf)
                    return preg_replace('/^\d+_/', '', $base);
                }, $value);
                return count($fileNames) > 0 ? implode(', ', $fileNames) : 'Trống';
            }
            return preg_replace('/^\d+_/', '', basename((string)$value));
        }

        // Format Supplier / Vendor ID
        if ($field === 'vendor_id' && is_numeric($value)) {
            $supplier = Supplier::find($value);
            return $supplier ? $supplier->name : "Hãng #{$value}";
        }

        // Format Manager ID
        if ($field === 'manager_id' && is_numeric($value)) {
            $manager = User::find($value);
            return $manager ? $manager->name : "User #{$value}";
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string)$value;
    }

    /**
     * Predict Next Action / Next Respondent based on activity log details and project state
     */
    public static function predictNextAction(ActivityLog $log, Project $project): ?array
    {
        $salesName = $project->manager->name ?? 'Sales phụ trách';
        $action = $log->action;
        $changes = $log->properties['changes'] ?? [];
        $regStatusChange = $changes['registration_status']['new'] ?? null;
        $supportTypeChange = $changes['support_request_type']['new'] ?? null;
        $forecastStageChange = $changes['forecast_stage']['new'] ?? null;
        $intakeStatusChange = $changes['intake_status']['new'] ?? null;

        // 1. Initial Creation / Resubmission
        if ($action === 'created' || $regStatusChange === 'submitted' || ($intakeStatusChange === 'pending' && isset($changes['bom_file']))) {
            return [
                'respondent' => 'PO / PM Team',
                'respondent_type' => 'pm',
                'action_text' => 'Tiếp nhận thông tin Đăng ký dự án & gửi đăng ký bảo vệ cơ hội với Hãng (SLA 4h làm việc).',
                'icon' => 'fas fa-clipboard-check',
                'color' => 'purple',
            ];
        }

        // 2. Sent to Vendor (vendor_processing)
        if ($regStatusChange === 'vendor_processing' || $regStatusChange === 'processing') {
            return [
                'respondent' => 'Hãng / PM Team',
                'respondent_type' => 'vendor',
                'action_text' => 'Chờ Hãng duyệt Đăng ký dự án và cung cấp Deal ID / Báo giá đặc biệt (SLA 3 ngày làm việc).',
                'icon' => 'fas fa-clock',
                'color' => 'blue',
            ];
        }

        // 3. Reminded Vendor (vendor_reminded)
        if ($regStatusChange === 'vendor_reminded') {
            return [
                'respondent' => 'Hãng',
                'respondent_type' => 'vendor',
                'action_text' => 'Chờ Hãng phản hồi duyệt dự án (Đã gia hạn thời hạn SLA thêm 3 ngày).',
                'icon' => 'fas fa-bell',
                'color' => 'amber',
            ];
        }

        // 4. Vendor Quoted (vendor_quoted) or Quote version submitted
        if ($regStatusChange === 'vendor_quoted' || isset($changes['vendor_quote_note']) || isset($changes['vendor_deal_id'])) {
            return [
                'respondent' => "Sales ({$salesName})",
                'respondent_type' => 'sales',
                'action_text' => "Kiểm tra giá Hãng, làm Báo giá gửi khách hàng và Cập nhật tiến độ dự án định kỳ (Update Status).",
                'icon' => 'fas fa-file-invoice-dollar',
                'color' => 'emerald',
            ];
        }

        // 5. In Update Status mode
        if ($regStatusChange === 'update_status') {
            return [
                'respondent' => "Sales ({$salesName})",
                'respondent_type' => 'sales',
                'action_text' => "Theo dõi cơ hội và định kỳ cập nhật tình hình đàm phán / chốt deal hàng tháng.",
                'icon' => 'fas fa-chart-line',
                'color' => 'teal',
            ];
        }

        // 6. Sales Update with Re-quote Request
        if ($supportTypeChange === 'request_update_price') {
            return [
                'respondent' => 'PM Team',
                'respondent_type' => 'pm',
                'action_text' => 'Làm việc lại với Hãng để xin cập nhật / gia hạn giá hoặc mức chiết khấu tốt hơn (SLA 3 ngày).',
                'icon' => 'fas fa-tags',
                'color' => 'purple',
            ];
        }

        // 7. Sales Update with Technical / Pre-sale / Demo Support Request
        if (in_array($supportTypeChange, ['presale_support', 'demo_poc', 'pricing_support', 'other'], true)) {
            return [
                'respondent' => 'PM / Đội Kỹ thuật',
                'respondent_type' => 'pm',
                'action_text' => 'Liên hệ Sales để phối hợp tư vấn giải pháp, hồ sơ kỹ thuật hoặc chuẩn bị thiết bị Demo / POC.',
                'icon' => 'fas fa-headset',
                'color' => 'indigo',
            ];
        }

        // 8. Normal Sales Forecast Update (commit / best_case)
        if (in_array($forecastStageChange, ['commit', 'best_case'], true)) {
            return [
                'respondent' => "Sales ({$salesName})",
                'respondent_type' => 'sales',
                'action_text' => "Tiếp tục bám sát khách hàng, chuẩn bị hợp đồng và đơn hàng khi chốt cơ hội thành công.",
                'icon' => 'fas fa-handshake',
                'color' => 'emerald',
            ];
        }

        // 9. Incomplete or Duplicate Registration
        if (in_array($regStatusChange, ['incomplete', 'duplicate'], true)) {
            return [
                'respondent' => "Sales ({$salesName})",
                'respondent_type' => 'sales',
                'action_text' => "Bổ sung / làm rõ thông tin End-User & cấu hình BOM hoặc kiểm tra lại thông tin cơ hội.",
                'icon' => 'fas fa-exclamation-triangle',
                'color' => 'amber',
            ];
        }

        // 10. Closed Won
        if ($regStatusChange === 'closed_won' || ($changes['status']['new'] ?? null) === 'completed') {
            return [
                'respondent' => 'PO / Kho / Kế toán',
                'respondent_type' => 'operation',
                'action_text' => 'Tạo yêu cầu đặt hàng mua (PO), xuất kho vật tư và xuất hóa đơn theo đơn hàng đã chốt.',
                'icon' => 'fas fa-check-double',
                'color' => 'green',
            ];
        }

        // 11. Closed Lost / Cancelled / Expired
        if (in_array($regStatusChange, ['closed_lost', 'cancelled', 'expired'], true) || in_array($changes['status']['new'] ?? null, ['cancelled'], true)) {
            return [
                'respondent' => '—',
                'respondent_type' => 'none',
                'action_text' => 'Dự án đã đóng / kết thúc vòng đời (Không có hành động tiếp theo).',
                'icon' => 'fas fa-check-circle',
                'color' => 'gray',
            ];
        }

        // 12. General update fallback: determine by author role
        $userInfo = self::getUserInfo($log, $project);
        if ($userInfo['role_title'] === 'Sales') {
            return [
                'respondent' => 'PM / PO Team',
                'respondent_type' => 'pm',
                'action_text' => 'Theo dõi các thông tin cập nhật mới từ Sales và hỗ trợ khi có yêu cầu.',
                'icon' => 'fas fa-user-clock',
                'color' => 'purple',
            ];
        } else {
            return [
                'respondent' => "Sales ({$salesName})",
                'respondent_type' => 'sales',
                'action_text' => "Nắm bắt thông tin cập nhật mới và tiếp tục theo đuổi cơ hội dự án.",
                'icon' => 'fas fa-user-clock',
                'color' => 'emerald',
            ];
        }
    }
}
