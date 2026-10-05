<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'link',
        'icon',
        'color',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Booted Model Hook
    protected static function booted(): void
    {
        static::creating(function (Notification $notification) {
            $user = $notification->user ?: User::find($notification->user_id);
            if (!$user || $user->status !== 'active' || $user->is_locked) {
                return false; // Abort saving notification
            }

            // Do not create notification if the target user has no permission/relation to view the entity
            if (!$notification->isAccessibleBy($user)) {
                return false; // Abort saving notification
            }

            return true;
        });

        // Real-time broadcast hook: push instantly via WebSockets (Reverb)
        static::created(function (Notification $notification) {
            try {
                broadcast(new \App\Events\NotificationSent($notification));
            } catch (\Throwable $e) {
                // If WebSocket broadcasting server is offline or fails, do not break the request
                \Illuminate\Support\Facades\Log::warning('Realtime Broadcast notification error: ' . $e->getMessage());
            }
        });
    }

    // Methods
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public static function markAllAsRead(int $userId): void
    {
        self::where('user_id', $userId)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Check if a notification is accessible by the specified user.
     * Prevents creating or displaying notifications to users who lack authorization to view the target entity.
     */
    public function isAccessibleBy(?User $user = null): bool
    {
        if (!$user || $user->status !== 'active' || $user->is_locked) {
            return false;
        }

        if ($this->user_id !== $user->id) {
            return false;
        }

        $isSuperAdmin = $user->hasRole('super_admin');

        try {
            $link = (string) $this->link;
            $type = (string) $this->type;
            $data = is_array($this->data) ? $this->data : [];

            // 1. Check permissions by URL / link path
            if (!empty($link) && $link !== '#' && $link !== '/') {
                $path = parse_url($link, PHP_URL_PATH);
                if ($path) {
                    $path = trim($path, '/');
                    $segments = explode('/', $path);

                    // List of recognized resource segments
                    $knownResources = [
                        'imports', 'exports', 'transfers', 'damaged-goods', 'projects', 'sales',
                        'quotations', 'purchase-orders', 'purchase-requests', 'tickets',
                        'technical-tickets', 'invoice-requests', 'work-schedules',
                        'marketing-events', 'opportunities', 'suppliers', 'customers',
                        'warehouses', 'inventory', 'warranties', 'meeting-room-bookings'
                    ];

                    $resource = null;
                    $id = null;
                    foreach ($segments as $idx => $segment) {
                        if (in_array($segment, $knownResources, true)) {
                            $resource = $segment;
                            $next = $segments[$idx + 1] ?? null;
                            if (is_numeric($next)) {
                                $id = (int) $next;
                            }
                            break;
                        }
                    }

                    switch ($resource) {
                        case 'imports':
                            if (!$user->can('view_imports')) return false;
                            $importId = $id ?: ($data['document_id'] ?? null);
                            if (is_numeric($importId)) {
                                $import = \App\Models\Import::find($importId);
                                if ($import && !\Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $import)) {
                                    return false;
                                }
                            }
                            return true;

                        case 'exports':
                            if (!$user->can('view_exports')) return false;
                            $exportId = $id ?: ($data['document_id'] ?? null);
                            if (is_numeric($exportId)) {
                                $export = \App\Models\Export::find($exportId);
                                if ($export && !\Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $export)) {
                                    return false;
                                }
                            }
                            return true;

                        case 'transfers':
                            if (!$user->can('view_transfers')) return false;
                            $transferId = $id ?: ($data['document_id'] ?? null);
                            if (is_numeric($transferId)) {
                                $transfer = \App\Models\Transfer::find($transferId);
                                if ($transfer && !\Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $transfer)) {
                                    return false;
                                }
                            }
                            return true;

                        case 'damaged-goods':
                            if (!$user->can('view_damaged_goods')) return false;
                            $dgId = $id ?: ($data['document_id'] ?? null);
                            if (is_numeric($dgId)) {
                                $dg = \App\Models\DamagedGood::find($dgId);
                                if ($dg && !\Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $dg)) {
                                    return false;
                                }
                            }
                            return true;

                        case 'projects':
                            $projectId = $id ?: ($data['project_id'] ?? null);
                            if (is_numeric($projectId)) {
                                $project = \App\Models\Project::find($projectId);
                                if ($project) {
                                    if ($type === 'project_submitted') {
                                        // Restrict to assigned team: PO cannot see Non-FTN submissions, PM cannot see FTN submissions
                                        if ($project->assigned_team === 'po_team') {
                                            $canAccessPo = in_array($user->department, ['PO', 'PO Team'], true)
                                                || $user->hasAnyRole(['super_admin', 'admin', 'director', 'purchase_manager']);
                                            if (!$canAccessPo) return false;
                                        } elseif ($project->assigned_team === 'pm_team') {
                                            $canAccessPm = in_array($user->department, ['PM', 'PM Team'], true)
                                                || $user->hasAnyRole(['super_admin', 'admin', 'director']);
                                            if (!$canAccessPm) return false;
                                        }
                                        return \Illuminate\Support\Facades\Gate::forUser($user)->allows('processIntake', $project)
                                            || \Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $project);
                                    }
                                    return \Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $project);
                                }
                                return false;
                            }
                            return \Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\Project::class);

                        case 'sales':
                            $saleId = $id ?: ($data['sale_id'] ?? null);
                            if (is_numeric($saleId)) {
                                $sale = \App\Models\Sale::find($saleId);
                                if ($sale) {
                                    if (\Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $sale)) {
                                        return true;
                                    }
                                    // If user is a designated approver for P&L or approval flow
                                    if (str_contains($type, 'approval') || in_array($type, ['pnl_need_revision', 'pnl_rejected', 'order_request', 'payment_alert'])) {
                                        return $user->can('approve_pnl') || $user->hasAnyRole(['super_admin', 'admin', 'director', 'sales_manager', 'accountant']);
                                    }
                                    return false;
                                }
                                return false;
                            }
                            return \Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\Sale::class);

                        case 'quotations':
                            $quotationId = $id ?: ($data['quotation_id'] ?? null);
                            if (is_numeric($quotationId)) {
                                $quotation = \App\Models\Quotation::find($quotationId);
                                if ($quotation) {
                                    return \Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $quotation);
                                }
                                return false;
                            }
                            return \Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\Quotation::class);

                        case 'purchase-orders':
                            $poId = $id ?: ($data['purchase_order_id'] ?? null);
                            if (is_numeric($poId)) {
                                $po = \App\Models\PurchaseOrder::find($poId);
                                if ($po) {
                                    if ($type === 'purchase_order_approval') {
                                        return $user->can('approve_purchase_orders')
                                            && \Illuminate\Support\Facades\Gate::forUser($user)->allows('approve', $po);
                                    }
                                    return \Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $po)
                                        || \Illuminate\Support\Facades\Gate::forUser($user)->allows('approve', $po);
                                }
                                return false;
                            }
                            if ($type === 'purchase_order_approval' || str_contains($link, 'pending_approval')) {
                                return $user->can('approve_purchase_orders');
                            }
                            return $user->can('view_purchase_orders') 
                                || $user->can('approve_purchase_orders') 
                                || \Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\PurchaseOrder::class);

                        case 'purchase-requests':
                            $prId = $id ?: ($data['purchase_request_id'] ?? null);
                            if (is_numeric($prId)) {
                                $pr = \App\Models\PurchaseRequest::find($prId);
                                if ($pr) {
                                    return \Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $pr);
                                }
                                return false;
                            }
                            return $user->can('view_purchase_requests') 
                                || \Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\PurchaseRequest::class);

                        case 'opportunities':
                            $oppId = $id ?: ($data['opportunity_id'] ?? null);
                            if (is_numeric($oppId)) {
                                $opp = \App\Models\Opportunity::find($oppId);
                                if ($opp) {
                                    if ($type === 'opportunity_technical_assigned' && $opp->technical_user_id === $user->id) {
                                        return true;
                                    }
                                    return \Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $opp);
                                }
                                return false;
                            }
                            return \Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\Opportunity::class);

                        case 'tickets':
                            return $user->can('view_tickets')
                                || $user->hasAnyRole(['super_admin', 'admin', 'warehouse_manager', 'warehouse_staff', 'sales_manager', 'sales', 'director']);

                        case 'technical-tickets':
                            $ttId = $id ?: ($data['technical_ticket_id'] ?? null);
                            if (is_numeric($ttId)) {
                                $ticket = \App\Models\TechnicalTicket::find($ttId);
                                if ($ticket && ($ticket->created_by === $user->id || $ticket->assigned_to === $user->id)) {
                                    return true;
                                }
                            }
                            return $user->can('view_technical_tickets')
                                || $user->hasAnyRole(['super_admin', 'admin', 'technical_lead', 'technical_engineer', 'director']);

                        case 'invoice-requests':
                            $irId = $id ?: ($data['invoice_request_id'] ?? null);
                            if (is_numeric($irId)) {
                                $ir = \App\Models\InvoiceRequest::find($irId);
                                if ($ir) {
                                    if ($user->hasAnyRole(['super_admin', 'admin', 'accountant', 'sales_manager'])) return true;
                                    if ($ir->requester_id === $user->id) return true;
                                    if ($ir->sale && $ir->sale->user_id === $user->id) return true;
                                    return false;
                                }
                                return false;
                            }
                            return $user->hasAnyRole(['super_admin', 'admin', 'accountant', 'sales_manager', 'sales']);

                        case 'work-schedules':
                            return $user->can('view_work_schedules') || \Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\WorkSchedule::class);

                        case 'marketing-events':
                            $eventId = $id ?: ($data['marketing_event_id'] ?? null);
                            if (is_numeric($eventId)) {
                                $event = \App\Models\MarketingEvent::find($eventId);
                                if ($event) {
                                    return \Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $event);
                                }
                            }
                            return $user->can('view_marketing_events') || \Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\MarketingEvent::class);

                        case 'meeting-room-bookings':
                            return true;

                        case 'suppliers':
                            return $user->can('view_suppliers');

                        case 'customers':
                            return $user->can('view_customers');

                        case 'warehouses':
                            return $user->can('view_warehouses');

                        case 'inventory':
                            return $user->can('view_inventories');

                        case 'warranties':
                            return $user->can('view_warranties');
                    }
                }
            }

            // 2. Check by notification type and payload data when URL check didn't match
            if (str_starts_with($type, 'import_') && !$user->can('view_imports')) {
                return false;
            }
            if (str_starts_with($type, 'export_') && !$user->can('view_exports')) {
                return false;
            }
            if (str_starts_with($type, 'transfer_') && !$user->can('view_transfers')) {
                return false;
            }
            if (str_starts_with($type, 'damaged_good_') && !$user->can('view_damaged_goods')) {
                return false;
            }

            if (str_starts_with($type, 'project_')) {
                if (!empty($data['project_id'])) {
                    $project = \App\Models\Project::find($data['project_id']);
                    if (!$project) return false;
                    if ($type === 'project_submitted') {
                        if ($project->assigned_team === 'po_team') {
                            $canAccessPo = in_array($user->department, ['PO', 'PO Team'], true)
                                || $user->hasAnyRole(['super_admin', 'admin', 'director', 'purchase_manager']);
                            if (!$canAccessPo) return false;
                        } elseif ($project->assigned_team === 'pm_team') {
                            $canAccessPm = in_array($user->department, ['PM', 'PM Team'], true)
                                || $user->hasAnyRole(['super_admin', 'admin', 'director']);
                            if (!$canAccessPm) return false;
                        }
                        return \Illuminate\Support\Facades\Gate::forUser($user)->allows('processIntake', $project)
                            || \Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $project);
                    }
                    return \Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $project);
                } elseif (!$user->can('view_projects') && !\Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\Project::class)) {
                    return false;
                }
            }

            if (str_starts_with($type, 'technical_ticket')) {
                if (!empty($data['technical_ticket_id'])) {
                    $ticket = \App\Models\TechnicalTicket::find($data['technical_ticket_id']);
                    if ($ticket && ($ticket->created_by === $user->id || $ticket->assigned_to === $user->id)) {
                        return true;
                    }
                }
                if (!$user->can('view_technical_tickets') && !$user->hasAnyRole(['super_admin', 'admin', 'technical_lead', 'technical_engineer'])) {
                    return false;
                }
            }

            if (str_starts_with($type, 'ticket_preload')) {
                if (!$user->hasAnyRole(['super_admin', 'admin', 'purchase_manager', 'purchase_staff'])) {
                    return false;
                }
            }

            if (str_starts_with($type, 'ticket_borrow_warehouse')) {
                if (!$user->hasAnyRole(['super_admin', 'admin', 'warehouse_manager', 'warehouse_staff'])) {
                    return false;
                }
            }

            if (str_starts_with($type, 'payment_') || str_starts_with($type, 'sale_') || str_starts_with($type, 'order_request')) {
                if (!empty($data['sale_id'])) {
                    $sale = \App\Models\Sale::find($data['sale_id']);
                    if (!$sale) return false;
                    if (\Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $sale)) {
                        return true;
                    }
                    if ($user->hasAnyRole(['super_admin', 'admin', 'director', 'sales_manager', 'accountant'])) {
                        return true;
                    }
                    return false;
                }
            }

            if (str_starts_with($type, 'purchase_order_')) {
                if (!empty($data['purchase_order_id'])) {
                    $po = \App\Models\PurchaseOrder::find($data['purchase_order_id']);
                    if (!$po) return false;
                    if ($type === 'purchase_order_approval') {
                        return $user->can('approve_purchase_orders')
                            && \Illuminate\Support\Facades\Gate::forUser($user)->allows('approve', $po);
                    }
                    if (!\Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $po) && !\Illuminate\Support\Facades\Gate::forUser($user)->allows('approve', $po)) {
                        return false;
                    }
                }
            }

            if (str_starts_with($type, 'opportunity_')) {
                if (!empty($data['opportunity_id'])) {
                    $opp = \App\Models\Opportunity::find($data['opportunity_id']);
                    if (!$opp) return false;
                    if ($type === 'opportunity_technical_assigned' && $opp->technical_user_id === $user->id) {
                        return true;
                    }
                    return \Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $opp);
                }
            }

            return true;
        } catch (\Throwable $e) {
            // Fail-safe: do not expose notification if permission evaluation encounters an error
            \Illuminate\Support\Facades\Log::warning('Notification permission check error: ' . $e->getMessage());
            return false;
        }
    }
}
