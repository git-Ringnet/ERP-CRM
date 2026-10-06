<?php

namespace App\Services;

use App\Models\User;
use App\Models\Import;
use App\Models\Export;
use App\Models\Transfer;
use App\Models\DamagedGood;
use App\Models\Ticket;
use App\Models\Inventory;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Sale;
use App\Models\TechnicalTicket;
use App\Models\TechnicalSupportLog;
use App\Models\SaleOrderRequest;
use App\Models\SaleOrderRequestItem;
use App\Models\PurchaseOrder;
use App\Models\MarketingEvent;
use App\Models\MarketingItem;
use App\Models\MeetingRoomBooking;
use App\Models\ApprovalHistory;
use App\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SidebarBadgeService
{
    /**
     * Get pending task counts for the sidebar, scoped to the given user.
     * Results are cached for 30 seconds to maximize page load performance.
     */
    public function getBadges(?User $user = null): array
    {
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return [];
        }

        $cacheKey = 'sidebar_badges_user_' . $user->id;

        return Cache::remember($cacheKey, 30, function () use ($user) {
            return $this->calculateBadges($user);
        });
    }

    /**
     * Clear cached badges for a specific user (or all users)
     */
    public function clearCache(?int $userId = null): void
    {
        if ($userId) {
            Cache::forget('sidebar_badges_user_' . $userId);
        } else {
            // Clear current user
            if (auth()->id()) {
                Cache::forget('sidebar_badges_user_' . auth()->id());
            }
        }
    }

    /**
     * Calculate all uncompleted counts related to the user.
     * Items that are finished (completed, cancelled, rejected, closed, won/lost) are excluded.
     * If all are completed (count is 0), the badge will not display.
     */
    public function calculateBadges(User $user): array
    {
        $badges = [];

        try {
            $isSuperAdmin = $user->hasRole('super_admin');
            $isAdmin = $user->hasAnyRole(['super_admin', 'admin']);
            $isDirector = $user->hasAnyRole(['super_admin', 'admin', 'director']);

            // ==========================================
            // 1. MASTER DATA (Dữ liệu đầu vào danh mục - không hiển thị thông báo)
            // ==========================================
            $badges['customers'] = 0;
            $badges['suppliers'] = 0;
            $badges['employees'] = 0;
            $badges['products'] = 0;
            $badges['master_data_total'] = 0;

            // ==========================================
            // 2. KHO HÀNG (WAREHOUSE)
            // ==========================================
            $badges['warehouses'] = 0;

            // Tồn kho: Cảnh báo hàng tồn kho dưới mức tối thiểu (low stock)
            if ($user->can('view_inventory')) {
                $badges['inventory'] = Inventory::whereColumn('stock', '<=', 'min_stock')
                    ->where('min_stock', '>', 0)
                    ->count();
            } else {
                $badges['inventory'] = 0;
            }

            // Nhập kho: Các phiếu nhập kho chờ duyệt / chưa hoàn thành
            if ($user->can('view_imports')) {
                $badges['imports'] = Import::where('status', 'pending')->count();
            } else {
                $badges['imports'] = 0;
            }

            // Xuất kho: Các phiếu xuất kho chưa hoàn thành (chờ duyệt / chờ xuất)
            if ($user->can('view_exports') || $user->can('view_all_exports') || $user->can('view_own_exports')) {
                if ($user->can('view_all_exports') || $isAdmin || $user->hasRole('warehouse') || $user->can('approve_exports')) {
                    $badges['exports'] = Export::whereIn('status', ['pending', 'pending_admin', 'pending_invoice'])->count();
                } else {
                    $badges['exports'] = Export::whereIn('status', ['pending', 'pending_admin', 'pending_invoice'])
                        ->forUser($user)
                        ->count();
                }
            } else {
                $badges['exports'] = 0;
            }

            // Chuyển kho: Chờ duyệt hoặc đang chuyển
            if ($user->can('view_transfers')) {
                $badges['transfers'] = Transfer::whereIn('status', ['pending', 'in_transit'])->count();
            } else {
                $badges['transfers'] = 0;
            }

            // Hàng hư hỏng: Chờ xử lý / duyệt
            if ($user->can('view_damaged_goods')) {
                $badges['damaged_goods'] = DamagedGood::where('status', 'pending')->count();
            } else {
                $badges['damaged_goods'] = 0;
            }

            // Yêu cầu (Ticket): Phiếu yêu cầu kho chưa xử lý xong
            if ($user->can('view_tickets')) {
                if ($isAdmin || $user->hasRole('warehouse')) {
                    $badges['tickets'] = Ticket::whereIn('status', ['pending', 'processing'])->count();
                } else {
                    $badges['tickets'] = Ticket::whereIn('status', ['pending', 'processing'])
                        ->where(function ($q) use ($user) {
                            $q->where('user_id', $user->id)->orWhere('target_user_id', $user->id);
                        })->count();
                }
            } else {
                $badges['tickets'] = 0;
            }

            $badges['warehouse_total'] = $badges['warehouses'] + $badges['inventory'] + $badges['imports']
                + $badges['exports'] + $badges['transfers'] + $badges['damaged_goods'] + $badges['tickets'];

            // ==========================================
            // 3. BÁN HÀNG (SALES)
            // ==========================================
            // Cơ hội: Đang mở (chưa hoàn thành)
            if ($isAdmin || $isDirector || $user->hasRole('sales_manager')) {
                $badges['opportunities'] = Opportunity::whereNotIn('status', ['completed', 'cancelled'])->count();
            } else {
                $badges['opportunities'] = Opportunity::whereNotIn('status', ['completed', 'cancelled'])
                    ->where(function ($q) use ($user) {
                        $q->where('assigned_to', $user->id)->orWhere('created_by', $user->id);
                    })->count();
            }

            // Đăng ký dự án: Chờ duyệt / cần xử lý
            if ($isAdmin || $isDirector || $user->hasRole('purchase_manager')) {
                $badges['projects'] = Project::where('status', 'pending')->count();
            } else {
                $badges['projects'] = Project::where('status', 'pending')
                    ->where(function ($q) use ($user) {
                        $q->where('manager_id', $user->id)->orWhere('created_by', $user->id);
                    })->count();
            }

            // Báo giá: Bản nháp hoặc chờ duyệt
            if ($isAdmin || $isDirector) {
                $badges['quotations'] = Quotation::whereIn('status', ['draft', 'pending_approval', 'pending'])->count();
            } else {
                $badges['quotations'] = Quotation::whereIn('status', ['draft', 'pending_approval', 'pending'])
                    ->where(function ($q) use ($user) {
                        $q->where('user_id', $user->id)->orWhere('created_by', $user->id);
                    })->count();
            }

            // Đơn hàng bán: Chưa hoàn tất (chưa completed / cancelled)
            if ($isAdmin || $isDirector || $user->hasRole('accountant') || $user->hasRole('warehouse')) {
                $badges['sales'] = Sale::whereNotIn('status', ['completed', 'cancelled'])->count();
            } else {
                $badges['sales'] = Sale::whereNotIn('status', ['completed', 'cancelled'])
                    ->where(function ($q) use ($user) {
                        $q->where('user_id', $user->id)->orWhere('created_by', $user->id);
                    })->count();
            }

            // Yêu cầu đặt hàng (my requests): PR chưa hoàn thành do user tạo
            $badges['purchase_requests_my'] = SaleOrderRequest::where('created_by', $user->id)
                ->whereNotIn('status', [SaleOrderRequest::STATUS_COMPLETED])
                ->count();

            // Sự kiện Marketing: Đang chờ duyệt hoặc đang diễn ra
            if ($isAdmin || $isDirector || $user->hasRole('marketing')) {
                $badges['marketing_events'] = MarketingEvent::whereIn('status', ['pending_approval', 'planning', 'in_progress'])->count();
                $badges['marketing_items'] = MarketingItem::where('approval_status', 'pending')->count();
            } else {
                $badges['marketing_events'] = 0;
                $badges['marketing_items'] = 0;
            }

            // Đặt phòng họp: Lịch đặt phòng họp hôm nay của user chưa kết thúc
            $badges['meeting_rooms'] = MeetingRoomBooking::where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->orWhereHas('attendees', fn($a) => $a->where('user_id', $user->id));
                })
                ->where('status', '!=', 'cancelled')
                ->whereDate('start_time', now()->toDateString())
                ->where('end_time', '>=', now())
                ->count();

            $badges['customer_debts'] = 0;

            $badges['sales_total'] = $badges['opportunities'] + $badges['projects'] + $badges['quotations']
                + $badges['sales'] + $badges['purchase_requests_my'] + $badges['marketing_events']
                + $badges['marketing_items'] + $badges['meeting_rooms'] + $badges['customer_debts'];

            // ==========================================
            // 4. TECHNICAL (KỸ THUẬT)
            // ==========================================
            // Ticket Kỹ thuật chưa hoàn thành (chưa completed, closed, cancelled)
            $isTechLeadOrAdmin = $isAdmin || $isDirector || $user->hasAnyRole(['technical_manager', 'technical_lead']);
            if ($isTechLeadOrAdmin) {
                $badges['technical_tickets'] = TechnicalTicket::whereNotIn('status', ['completed', 'closed', 'cancelled'])->count();
            } else {
                $badges['technical_tickets'] = TechnicalTicket::whereNotIn('status', ['completed', 'closed', 'cancelled'])
                    ->where(function ($q) use ($user) {
                        $q->where('created_by', $user->id)
                          ->orWhere('sales_owner_id', $user->id)
                          ->orWhere('team_lead_id', $user->id)
                          ->orWhereJsonContains('co_lead_ids', (int)$user->id)
                          ->orWhereJsonContains('co_lead_ids', (string)$user->id)
                          ->orWhereHas('assignedEngineers', fn($sq) => $sq->where('users.id', $user->id));
                    })->count();
            }

            // Nhật ký hỗ trợ kỹ thuật đang xử lý
            if ($isAdmin || $isDirector) {
                $badges['technical_support_logs'] = TechnicalSupportLog::whereIn('status', ['pending', 'in_progress'])->count();
            } else {
                $badges['technical_support_logs'] = TechnicalSupportLog::whereIn('status', ['pending', 'in_progress'])
                    ->where(function ($q) use ($user) {
                        $q->where('assigned_to', $user->id)->orWhere('created_by', $user->id);
                    })->count();
            }

            $badges['technical_total'] = $badges['technical_tickets'] + $badges['technical_support_logs'];

            // ==========================================
            // 5. MUA HÀNG (PURCHASING)
            // ==========================================
            // Duyệt PR: Chờ duyệt (cho người có quyền duyệt PR)
            if ($isAdmin || $isDirector || $user->hasRole('purchase_manager') || $user->can('view_pr_approvals')) {
                $badges['pr_approvals'] = SaleOrderRequest::whereIn('status', [
                    SaleOrderRequest::STATUS_PENDING_ADMIN,
                    SaleOrderRequest::STATUS_SUBMITTED,
                    SaleOrderRequest::STATUS_NEED_INFO,
                ])->count();
            } else {
                $badges['pr_approvals'] = 0;
            }

            // Gom đơn cần đặt: PR items thuộc các PR đang processing và chưa bị hủy
            if ($isAdmin || $isDirector || $user->hasRole('purchase_manager') || $user->can('view_needs_ordering')) {
                $badges['needs_ordering'] = SaleOrderRequestItem::where('is_cancelled', false)
                    ->whereHas('saleOrderRequest', fn($q) => $q->where('status', SaleOrderRequest::STATUS_PROCESSING))
                    ->count();
            } else {
                $badges['needs_ordering'] = 0;
            }

            // PO: Đơn đặt hàng hãng chưa hoàn thành
            if ($isAdmin || $isDirector || $user->hasRole('purchase_manager') || $user->can('view_all_purchase_orders')) {
                $badges['purchase_orders'] = PurchaseOrder::whereNotIn('status', ['received', 'cancelled'])->count();
            } else {
                $badges['purchase_orders'] = PurchaseOrder::whereNotIn('status', ['received', 'cancelled'])
                    ->where('created_by', $user->id)
                    ->count();
            }

            $badges['purchasing_total'] = $badges['pr_approvals'] + $badges['needs_ordering'] + $badges['purchase_orders'];

            // ==========================================
            // 6. HỆ THỐNG (SYSTEM)
            // ==========================================
            // Quy trình duyệt: Chứng từ đang chờ chính user duyệt
            $badges['approval_workflows'] = ApprovalHistory::where('action', 'pending')
                ->where(function ($q) use ($user) {
                    $q->where('approver_id', $user->id)
                      ->orWhere('delegated_to_id', $user->id);
                })
                ->count();

            $badges['system_total'] = $badges['approval_workflows'];

        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('SidebarBadgeService error: ' . $e->getMessage());
        }

        return $badges;
    }
}
