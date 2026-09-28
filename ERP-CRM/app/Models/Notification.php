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
     * Prevents displaying notifications to users who lack authorization to view the target entity.
     */
    public function isAccessibleBy(?User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->user_id !== $user->id) {
            return false;
        }

        // Super Admin bypass: can view all notifications
        if ($user->hasRole('super_admin')) {
            return true;
        }

        try {
            $link = $this->link;
            $type = (string) $this->type;
            $data = is_array($this->data) ? $this->data : [];

            // 1. Check permissions by URL / link path
            if (!empty($link) && $link !== '#' && $link !== '/') {
                $path = parse_url($link, PHP_URL_PATH);
                if ($path) {
                    $path = trim($path, '/');
                    $segments = explode('/', $path);
                    $resource = $segments[0] ?? '';
                    $id = $segments[1] ?? null;

                    switch ($resource) {
                        case 'imports':
                            if (!$user->can('view_imports')) return false;
                            if (is_numeric($id)) {
                                $import = \App\Models\Import::find($id);
                                if ($import && !\Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $import)) {
                                    return false;
                                }
                            }
                            return true;

                        case 'exports':
                            if (!$user->can('view_exports')) return false;
                            if (is_numeric($id)) {
                                $export = \App\Models\Export::find($id);
                                if ($export && !\Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $export)) {
                                    return false;
                                }
                            }
                            return true;

                        case 'transfers':
                            if (!$user->can('view_transfers')) return false;
                            if (is_numeric($id)) {
                                $transfer = \App\Models\Transfer::find($id);
                                if ($transfer && !\Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $transfer)) {
                                    return false;
                                }
                            }
                            return true;

                        case 'damaged-goods':
                            if (!$user->can('view_damaged_goods')) return false;
                            if (is_numeric($id)) {
                                $dg = \App\Models\DamagedGood::find($id);
                                if ($dg && !\Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $dg)) {
                                    return false;
                                }
                            }
                            return true;

                        case 'projects':
                            if (is_numeric($id)) {
                                $project = \App\Models\Project::find($id);
                                if ($project) {
                                    return \Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $project);
                                }
                            }
                            return \Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\Project::class);

                        case 'sales':
                            if (is_numeric($id)) {
                                $sale = \App\Models\Sale::find($id);
                                if ($sale) {
                                    return \Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $sale);
                                }
                            }
                            return \Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\Sale::class);

                        case 'quotations':
                            if (is_numeric($id)) {
                                $quotation = \App\Models\Quotation::find($id);
                                if ($quotation) {
                                    return \Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $quotation);
                                }
                            }
                            return \Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\Quotation::class);

                        case 'purchase-orders':
                            if (is_numeric($id)) {
                                $po = \App\Models\PurchaseOrder::find($id);
                                if ($po) {
                                    return \Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $po)
                                        || \Illuminate\Support\Facades\Gate::forUser($user)->allows('approve', $po);
                                }
                            }
                            return $user->can('view_purchase_orders') 
                                || $user->can('approve_purchase_orders') 
                                || \Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\PurchaseOrder::class);

                        case 'purchase-requests':
                            if (is_numeric($id)) {
                                $pr = \App\Models\PurchaseRequest::find($id);
                                if ($pr) {
                                    return \Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $pr);
                                }
                            }
                            return $user->can('view_purchase_requests') 
                                || \Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\PurchaseRequest::class);

                        case 'tickets':
                            return $user->can('view_tickets')
                                || $user->hasAnyRole(['super_admin', 'admin', 'warehouse_manager', 'warehouse_staff', 'sales_manager', 'sales', 'director']);

                        case 'technical-tickets':
                            return $user->can('view_technical_tickets')
                                || $user->hasAnyRole(['super_admin', 'admin', 'technical_lead', 'technical_engineer', 'director']);

                        case 'invoice-requests':
                            if (is_numeric($id)) {
                                $ir = \App\Models\InvoiceRequest::find($id);
                                if ($ir) {
                                    if ($user->hasAnyRole(['super_admin', 'admin', 'accountant', 'sales_manager'])) return true;
                                    if ($ir->requester_id === $user->id) return true;
                                    if ($ir->sale && $ir->sale->user_id === $user->id) return true;
                                    return false;
                                }
                            }
                            return $user->hasAnyRole(['super_admin', 'admin', 'accountant', 'sales_manager', 'sales']);

                        case 'work-schedules':
                            return $user->can('view_work_schedules') || \Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\WorkSchedule::class);

                        case 'marketing-events':
                            return $user->can('view_marketing_events') || \Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\MarketingEvent::class);

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

            // 2. Check by notification type and payload data
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
                    if ($project && !\Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $project)) {
                        return false;
                    }
                } elseif (!$user->can('view_projects') && !\Illuminate\Support\Facades\Gate::forUser($user)->allows('viewAny', \App\Models\Project::class)) {
                    return false;
                }
            }

            if (str_starts_with($type, 'technical_ticket')) {
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

            if (str_starts_with($type, 'payment_') || str_starts_with($type, 'sale_')) {
                if (!empty($data['sale_id'])) {
                    $sale = \App\Models\Sale::find($data['sale_id']);
                    if ($sale && !\Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $sale)) {
                        return false;
                    }
                }
            }

            if (str_starts_with($type, 'purchase_order_')) {
                if (!empty($data['purchase_order_id'])) {
                    $po = \App\Models\PurchaseOrder::find($data['purchase_order_id']);
                    if ($po && !\Illuminate\Support\Facades\Gate::forUser($user)->allows('view', $po) && !\Illuminate\Support\Facades\Gate::forUser($user)->allows('approve', $po)) {
                        return false;
                    }
                }
            }

            return true;
        } catch (\Throwable $e) {
            // If any error occurs during permission evaluation, fail safe and return true
            // or log warning so notification system doesn't crash
            \Illuminate\Support\Facades\Log::warning('Notification permission check error: ' . $e->getMessage());
            return true;
        }
    }
}
