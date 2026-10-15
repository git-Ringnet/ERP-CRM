<?php

namespace App\Exports;

use App\Exports\Sheets\TechnicalEngineerWorkloadSheet;
use App\Exports\Sheets\TechnicalSalesSheet;
use App\Exports\Sheets\TechnicalVendorSheet;
use App\Exports\Sheets\TechnicalWorkTypeSheet;
use App\Exports\Sheets\TechnicalProjectSheet;
use App\Exports\Sheets\TechnicalCustomerSheet;
use App\Exports\Sheets\TechnicalTicketDetailsSheet;
use App\Models\TechnicalTicket;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Carbon\Carbon;

class TechnicalTicketsExport implements WithMultipleSheets
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Return array of sheets based on the report_type filter.
     */
    public function sheets(): array
    {
        $reportType = $this->filters['report_type'] ?? 'all';
        $sheets = [];

        // Preload filtered tickets collection once to share across sheets efficiently
        $tickets = $this->getFilteredTickets();

        switch ($reportType) {
            case 'engineer':
            case 'engineer_workload':
                $sheets[] = new TechnicalEngineerWorkloadSheet($this->filters, $tickets);
                $sheets[] = new TechnicalTicketDetailsSheet($this->filters, $tickets);
                break;

            case 'sales':
                $sheets[] = new TechnicalSalesSheet($this->filters, $tickets);
                $sheets[] = new TechnicalTicketDetailsSheet($this->filters, $tickets);
                break;

            case 'vendor':
                $sheets[] = new TechnicalVendorSheet($this->filters, $tickets);
                $sheets[] = new TechnicalTicketDetailsSheet($this->filters, $tickets);
                break;

            case 'project':
                $sheets[] = new TechnicalProjectSheet($this->filters, $tickets);
                $sheets[] = new TechnicalTicketDetailsSheet($this->filters, $tickets);
                break;

            case 'customer':
                $sheets[] = new TechnicalCustomerSheet($this->filters, $tickets);
                $sheets[] = new TechnicalTicketDetailsSheet($this->filters, $tickets);
                break;

            case 'work_type':
                $sheets[] = new TechnicalWorkTypeSheet($this->filters, $tickets);
                $sheets[] = new TechnicalTicketDetailsSheet($this->filters, $tickets);
                break;

            case 'raw_data':
                $sheets[] = new TechnicalTicketDetailsSheet($this->filters, $tickets);
                break;

            case 'all':
            default:
                // Full comprehensive multi-sheet report
                $sheets[] = new TechnicalEngineerWorkloadSheet($this->filters, $tickets);
                $sheets[] = new TechnicalSalesSheet($this->filters, $tickets);
                $sheets[] = new TechnicalVendorSheet($this->filters, $tickets);
                $sheets[] = new TechnicalWorkTypeSheet($this->filters, $tickets);
                $sheets[] = new TechnicalProjectSheet($this->filters, $tickets);
                $sheets[] = new TechnicalCustomerSheet($this->filters, $tickets);
                $sheets[] = new TechnicalTicketDetailsSheet($this->filters, $tickets);
                break;
        }

        return $sheets;
    }

    protected function getFilteredTickets()
    {
        $query = TechnicalTicket::with([
            'customer', 'project', 'opportunity', 'sale', 'supplier', 
            'assignedTo', 'creator', 'supportLogs', 'comments', 'assignedEngineers', 'salesOwner'
        ]);

        $currentUserId = auth()->id();
        $isManagerOrAdmin = auth()->user() && auth()->user()->hasAnyRole(['super_admin', 'director', 'sales_manager']);
        $isTechLeadRole = auth()->user() && auth()->user()->hasRole('technical_lead');

        if (!$isManagerOrAdmin && !$isTechLeadRole) {
            $query->where(function ($q) use ($currentUserId) {
                $q->where('created_by', $currentUserId)
                  ->orWhere('sales_owner_id', $currentUserId)
                  ->orWhere('assigned_to', $currentUserId)
                  ->orWhere('team_lead_id', $currentUserId)
                  ->orWhereHas('assignedEngineers', function ($sq) use ($currentUserId) {
                      $sq->where('users.id', $currentUserId);
                  });
            });
        }

        // Apply filters
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['work_type'])) {
            $query->where('work_type', $this->filters['work_type']);
        }

        if (!empty($this->filters['priority'])) {
            $query->where('priority', $this->filters['priority']);
        }

        if (!empty($this->filters['assigned_to'])) {
            $engId = $this->filters['assigned_to'];
            $query->where(function ($q) use ($engId) {
                $q->where('assigned_to', $engId)
                  ->orWhereHas('assignedEngineers', function ($sq) use ($engId) {
                      $sq->where('users.id', $engId);
                  });
            });
        }

        if (!empty($this->filters['customer_id'])) {
            $query->where('customer_id', $this->filters['customer_id']);
        }

        if (!empty($this->filters['supplier_id'])) {
            $query->where('supplier_id', $this->filters['supplier_id']);
        }

        if (!empty($this->filters['project_id'])) {
            $query->where('project_id', $this->filters['project_id']);
        }

        if (!empty($this->filters['created_by'])) {
            $query->where('created_by', $this->filters['created_by']);
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        if (isset($this->filters['sla_status'])) {
            $sla = $this->filters['sla_status'];
            $now = Carbon::now();
            if ($sla === 'overdue') {
                $query->slaOverdue();
            } elseif ($sla === 'ontime') {
                $query->slaOntime();
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
