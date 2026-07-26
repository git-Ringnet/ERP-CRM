<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectVendorQuote;
use App\Models\ProjectRegistrationNote;
use App\Models\ProjectStatusUpdate;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use App\Services\NotificationService;
use App\Exports\ProjectsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ProjectController extends Controller
{
    /**
     * Master data: Danh sách ngành nghề chuẩn.
     */
    public const INDUSTRIES = [
        'banking_finance'     => 'Banking & Finance (Ngân hàng & Tài chính)',
        'government'          => 'Government & Public Sector (Chính phủ & Công)',
        'healthcare'          => 'Healthcare (Y tế & Sức khỏe)',
        'education'           => 'Education (Giáo dục)',
        'manufacturing'       => 'Manufacturing (Sản xuất)',
        'retail'              => 'Retail & E-commerce (Bán lẻ & TMĐT)',
        'telecom'             => 'Telecommunications (Viễn thông)',
        'energy'              => 'Energy & Utilities (Năng lượng & Tiện ích)',
        'transportation'      => 'Transportation & Logistics (Vận tải & Logistics)',
        'real_estate'         => 'Real Estate & Construction (BĐS & Xây dựng)',
        'media'               => 'Media & Entertainment (Truyền thông & Giải trí)',
        'technology'          => 'Information Technology (Công nghệ thông tin)',
        'hospitality'         => 'Hospitality & Tourism (Khách sạn & Du lịch)',
        'agriculture'         => 'Agriculture (Nông nghiệp)',
        'insurance'           => 'Insurance (Bảo hiểm)',
        'legal'               => 'Legal & Consulting (Pháp lý & Tư vấn)',
        'other'               => 'Others (Khác)',
    ];

    /**
     * Display a listing of projects.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Project::class);

        $query = Project::with(['customer', 'manager', 'vendor', 'initialProcessedBy'])
            ->forUser(Auth::user());

        // Search
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filter by team
        if ($request->filled('team')) {
            $query->where('assigned_team', $request->team);
        }

        // Filter by registration status
        if ($request->filled('registration_status')) {
            $query->where('registration_status', $request->registration_status);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->filterByStatus($request->status);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by date range (Active during period)
        if ($request->filled('date_from')) {
            $query->where(function ($q) use ($request) {
                $q->whereDate('end_date', '>=', $request->date_from)
                    ->orWhereNull('end_date');
            });
        }
        if ($request->filled('date_to')) {
            $query->whereDate('start_date', '<=', $request->date_to);
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate(15);
        $customers = Customer::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('projects.index', compact('projects', 'customers', 'suppliers'));
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(Request $request)
    {
        $this->authorize('create', Project::class);

        $managers = User::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $code = $this->generateProjectCode();

        // Auto-fill Distributor AM from logged-in user (format: email | name)
        $distributorAm = (Auth::user()->email ?: '') . ' | ' . (Auth::user()->name ?: '');

        // Handle pre-filling from MarketingEvent or Opportunity
        $preFill = [];
        if ($request->filled('marketing_event_id')) {
            $mktEvent = \App\Models\MarketingEvent::find($request->marketing_event_id);
            if ($mktEvent) {
                $preFill['marketing_event_id'] = $mktEvent->id;
                $preFill['name'] = "DA từ " . $mktEvent->title;
                $preFill['budget'] = $mktEvent->budget;
                $preFill['description'] = "Dự án phát sinh từ sự kiện: " . $mktEvent->title . "\n" . $mktEvent->description;
            }
        }
        if ($request->filled('opportunity_id')) {
            $opp = \App\Models\Opportunity::find($request->opportunity_id);
            if ($opp) {
                $preFill['opportunity_id'] = $opp->id;
                $preFill['customer_type'] = $opp->customer_type;
                $preFill['customer_id'] = $opp->customer_id;
                $preFill['contact_id'] = $opp->contact_id;
                $preFill['name'] = $opp->name;
                $preFill['description'] = $opp->description;
                $preFill['eu_name_vi'] = $opp->eu_company_name;
                $preFill['eu_contact_name'] = $opp->eu_contact_name;
                $preFill['eu_phone'] = $opp->eu_phone;
                $preFill['eu_email'] = $opp->eu_email;
                $preFill['eu_position'] = $opp->eu_position;
            }
        }
        if ($request->filled('customer_id')) {
            $preFill['customer_id'] = $request->customer_id;
        }

        // Only load the selected customer to prevent performance issues with large datasets
        $selectedCustomerId = old('collaborate_customer_id') ?? $preFill['customer_id'] ?? null;
        $customers = $selectedCustomerId 
            ? Customer::where('id', $selectedCustomerId)->get() 
            : collect();

        $industries = self::INDUSTRIES;

        return view('projects.create', compact('customers', 'managers', 'suppliers', 'code', 'preFill', 'distributorAm', 'industries'));
    }

    /**
     * Generate unique project code
     */
    private function generateProjectCode(): string
    {
        $prefix = 'DA-';
        $lastProject = Project::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastProject) {
            $lastNumber = intval(substr($lastProject->code, strlen($prefix)));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * AJAX: Check duplicate project by Vendor + EU Tax Code + Project Name
     */
    public function checkDuplicate(Request $request)
    {
        $vendorId = $request->input('vendor_id');
        $taxCode = trim($request->input('tax_code', ''));
        $name = trim($request->input('name', ''));
        $bomData = trim($request->input('bom_data', ''));

        if (empty($taxCode) || empty($name)) {
            return response()->json(['duplicate' => false]);
        }

        $query = Project::where('eu_tax_code', $taxCode)
            ->where('name', $name);

        if ($vendorId) {
            $query->where('vendor_id', $vendorId);
        }

        if (!empty($bomData)) {
            $query->where('bom_data', $bomData);
        } else {
            $query->where(function($q) {
                $q->whereNull('bom_data')->orWhere('bom_data', '');
            });
        }

        $existing = $query->with('manager')->first();

        if ($existing) {
            $isSameUser = $existing->manager_id === Auth::id();
            if ($isSameUser) {
                return response()->json([
                    'duplicate' => true,
                    'is_same_user' => true,
                    'project_id' => $existing->id,
                    'project_code' => $existing->code,
                    'project_name' => $existing->name,
                    'sales_name' => $existing->manager ? $existing->manager->name : 'N/A',
                    'sales_email' => $existing->manager ? $existing->manager->email : 'N/A',
                    'created_at' => $existing->created_at ? $existing->created_at->format('d/m/Y H:i') : '',
                ]);
            } else {
                return response()->json([
                    'duplicate' => true,
                    'is_same_user' => false,
                ]);
            }
        }

        return response()->json(['duplicate' => false]);
    }

    /**
     * AJAX: Check tax code in customer database
     */
    public function checkTaxCode(Request $request)
    {
        $taxCode = trim($request->input('tax_code', ''));

        if (empty($taxCode) || strlen($taxCode) < 3) {
            return response()->json(['exists' => false]);
        }

        $customer = Customer::where('tax_code', $taxCode)->first();

        if ($customer) {
            return response()->json([
                'exists' => true,
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'tax_code' => $customer->tax_code,
                    'address' => $customer->address,
                ],
            ]);
        }

        return response()->json(['exists' => false]);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request, NotificationService $notificationService)
    {
        $this->authorize('create', Project::class);

        $collabRequired = $request->input('collaborate_type') === 'partner' ? 'required' : 'nullable';

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:projects,code'],
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:planning,in_progress,completed,cancelled,on_hold'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'note' => ['required', 'string'],
            'marketing_event_id' => ['nullable', 'exists:marketing_events,id'],
            'opportunity_id' => ['nullable', 'exists:opportunities,id'],
            // Distributor
            'vendor_id' => ['required', 'exists:suppliers,id'],
            'distributor_am' => ['required', 'string', 'max:255'],
            // End-User
            'eu_name_vi' => ['required', 'string', 'max:500'],
            'eu_name_en' => ['required', 'string', 'max:500'],
            'eu_name_abbr' => ['nullable', 'string', 'max:100'],
            'eu_tax_code' => ['required', 'string', 'max:100'],
            'eu_province' => ['nullable', 'string', 'max:100'],
            'eu_industry' => ['nullable', 'string'],
            'eu_industry_other' => ['required_if:eu_industry,other', 'nullable', 'string', 'max:255'],
            // Collaboration
            'collaborate_type' => ['required', 'in:partner,end_user'],
            'collaborate_customer_id' => ['nullable', 'exists:customers,id'],
            'collaborate_company' => [$collabRequired, 'string', 'max:500'],
            'collaborate_tax_code' => [$collabRequired, 'string', 'max:100'],
            'collaborate_pic_name' => [$collabRequired, 'string', 'max:255'],
            'collaborate_pic_title' => [$collabRequired, 'string', 'max:255'],
            'collaborate_pic_phone' => [$collabRequired, 'string', 'max:50'],
            'collaborate_pic_email' => ['nullable', 'string', 'email', 'max:255'],
            'end_date' => ['required', 'date'],
            'bom_file' => ['nullable', 'array'],
            'bom_file.*' => ['file', 'mimes:xlsx,xls,pdf,doc,docx', 'max:10240'],
            'bom_data' => ['nullable', 'string'],
            'net_to_tech_horizon' => ['nullable', 'numeric', 'min:0'],
            'stage' => ['nullable', 'string', 'max:50'],
            'deal_type' => ['nullable', 'string', 'max:50'],
            'sn_numbers' => ['required_if:deal_type,trade_up', 'nullable', 'string'],
            'special_request_type' => ['nullable', 'string'],
            'special_request_note' => ['required_with:special_request_type', 'nullable', 'string'],
        ], [], $this->validationAttributes());

        $validated['name_en'] = $validated['name_en'] ?? $validated['name'];

        if ($validated['eu_industry'] === 'other') {
            $validated['eu_industry'] = $request->input('eu_industry_other');
        } elseif (!empty($validated['eu_industry'])) {
            if (!array_key_exists($validated['eu_industry'], self::INDUSTRIES)) {
                return back()->withInput()->withErrors(['eu_industry' => 'Ngành nghề không hợp lệ.']);
            }
        }

        // Duplicate check logic on submission
        $vendorId = $validated['vendor_id'];
        $taxCode = trim($validated['eu_tax_code']);
        $name = trim($validated['name']);
        $bomData = trim($validated['bom_data'] ?? '');

        $duplicateQuery = Project::where('eu_tax_code', $taxCode)
            ->where('name', $name);

        if ($vendorId) {
            $duplicateQuery->where('vendor_id', $vendorId);
        }

        if (!empty($bomData)) {
            $duplicateQuery->where('bom_data', $bomData);
        } else {
            $duplicateQuery->where(function($q) {
                $q->whereNull('bom_data')->orWhere('bom_data', '');
            });
        }

        $existing = $duplicateQuery->with('manager')->first();

        // Auto-determine assigned_team based on Vendor configuration (config/projects.php)
        $supplier = Supplier::find($validated['vendor_id']);
        $validated['assigned_team'] = $supplier ? $supplier->assigned_team : 'pm_team';

        // Auto-set dates
        $validated['start_date'] = now()->format('Y-m-d');
        $validated['end_date'] = Carbon::parse($validated['end_date'])->format('Y-m-d');
        $validated['estimated_close_months'] = (int) max(1, round(Carbon::parse($validated['start_date'])->diffInMonths(Carbon::parse($validated['end_date']))));
        $validated['customer_name'] = $validated['eu_name_vi'];
        $validated['manager_id'] = $validated['manager_id'] ?? Auth::id();

        // 4-Hour Initial Processing SLA
        $validated['initial_sla_due_at'] = now()->addHours(4);

        if ($existing) {
            if ($existing->manager_id === Auth::id()) {
                // Same Sales -> Redirect to edit page of the existing project
                return redirect()->route('projects.edit', $existing->id)
                    ->withInput()
                    ->with('warning', 'Dự án này đã được bạn đăng ký trước đó. Vui lòng cập nhật trực tiếp tại đây.');
            } else {
                // Different Sales -> Flag as duplicate for PM, proceed creation
                $validated['intake_status'] = 'duplicate';
                $validated['registration_status'] = 'duplicate';
                $validated['duplicate_sales_info'] = "Trùng với dự án {$existing->code} đã được Sales {$existing->manager->name} ({$existing->manager->email}) đăng ký trước đó.";
            }
        } else {
            $validated['intake_status'] = 'pending';
            $validated['registration_status'] = 'submitted';
        }

        // Collaboration handling
        if ($validated['collaborate_type'] === 'end_user') {
            $validated['collaborate_company'] = $validated['eu_name_vi'];
            $validated['collaborate_tax_code'] = $validated['eu_tax_code'];
            $validated['collaborate_customer_id'] = null;
        }

        // BOM file handling
        if ($request->hasFile('bom_file')) {
            $files = $request->file('bom_file');
            if (!is_array($files)) {
                $files = [$files];
            }
            $paths = [];
            foreach ($files as $file) {
                $originalName = $file->getClientOriginalName();
                $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalName);
                $paths[] = $file->storeAs('bom', $safeName, 'public');
            }
            $validated['bom_file'] = $paths;
        } else {
            $validated['bom_file'] = [];
        }

        $project = Project::create($validated);

        app(\App\Services\ActivityLogService::class)->logCreated($project);

        if ($request->filled('opportunity_id')) {
            $opp = \App\Models\Opportunity::find($request->opportunity_id);
            if ($opp) {
                $opp->update(['project_id' => $project->id]);
            }
        }

        // Trigger notification to target team
        $notificationService->notifyProjectSubmittedToTeam($project);

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Đăng ký dự án thành công. Đã chuyển ticket sang cho ' . ($project->assigned_team === 'po_team' ? 'PO Team (FTN)' : 'PM Team (Non-FTN)') . ' tiếp nhận xử lý.');
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);

        $project->load([
            'customer', 'manager', 'vendor', 'collaborateCustomer',
            'initialProcessedBy', 'vendorQuoteVersions.creator',
            'notes.user', 'statusUpdates.user', 'sales.items'
        ]);

        $salesStats = [
            'total_orders' => $project->sales()->count(),
            'total_revenue' => $project->total_revenue,
            'total_cost' => $project->total_cost,
            'profit' => $project->profit,
            'profit_percent' => $project->profit_percent,
            'total_debt' => $project->total_debt,
        ];

        $recentSales = $project->sales()
            ->with('items')
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        $exportStats = [
            'total_exports' => $project->exports()->count(),
            'total_export_value' => $project->total_export_value,
        ];

        $recentExports = $project->exports()
            ->with(['warehouse', 'items.product'])
            ->orderBy('date', 'desc')
            ->limit(10)
            ->get();

        $activityLogs = \App\Models\ActivityLog::where('subject_type', Project::class)
            ->where('subject_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('projects.show', compact('project', 'salesStats', 'recentSales', 'exportStats', 'recentExports', 'activityLogs'));
    }

    /**
     * Process Intake Decision (PO or PM Team action).
     */
    public function processIntake(Request $request, Project $project, NotificationService $notificationService)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'intake_status' => ['required', 'in:registered,duplicate,incomplete'],
            'intake_note' => ['required_if:intake_status,incomplete', 'nullable', 'string'],
            'duplicate_sales_info' => ['required_if:intake_status,duplicate', 'nullable', 'string'],
        ]);

        $status = $validated['intake_status'];
        $now = now();

        $updateData = [
            'intake_status' => $status,
            'initial_processed_at' => $now,
            'initial_processed_by' => Auth::id(),
            'intake_note' => $validated['intake_note'] ?? null,
            'duplicate_sales_info' => $validated['duplicate_sales_info'] ?? null,
        ];

        if ($status === 'registered') {
            $updateData['vendor_submitted_at'] = $now;
            // 3 Working days SLA for Vendor response
            $updateData['vendor_due_at'] = Project::addWorkingDays($now, 3);
            
            if ($project->assigned_team === 'po_team') {
                // FTN: PO registers and done; transitions to update_status for Sales
                $updateData['registration_status'] = 'update_status';
            } else {
                // Non-FTN: waiting for PM to request vendor quote/BOM
                $updateData['registration_status'] = 'vendor_processing';
            }
        } elseif ($status === 'duplicate') {
            $updateData['registration_status'] = 'duplicate';
        } elseif ($status === 'incomplete') {
            $updateData['registration_status'] = 'incomplete';
        }

        $old = $project->getAttributes();
        $project->update($updateData);
        app(\App\Services\ActivityLogService::class)->logUpdated($project, $old, $project->fresh()->getAttributes());

        $notificationService->notifyProjectIntakeOutcome($project, $status, $validated['intake_note'] ?? $validated['duplicate_sales_info']);

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Đã cập nhật trạng thái tiếp nhận dự án thành công.');
    }

    /**
     * Add Interactive Discussion Note (PM & Sales exchange).
     * If note is posted by PM/PO, automatically extends vendor SLA by +1 working day.
     */
    public function addNote(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'content' => ['required', 'string'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ]);

        $userRole = 'sales';
        if (Auth::user()->department === 'PM' || Auth::user()->department === 'PM Team') {
            $userRole = 'pm';
        } elseif (Auth::user()->department === 'PO' || Auth::user()->department === 'PO Team') {
            $userRole = 'po';
        }

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('project_notes', 'public');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                ];
            }
        }

        $extendDays = 0;
        // PM note extends vendor SLA by +1 working day
        if ($userRole === 'pm' && $project->vendor_due_at) {
            $extendDays = 1;
            $project->update([
                'vendor_due_at' => Project::addWorkingDays($project->vendor_due_at, 1),
                'registration_status' => 'vendor_reminded',
            ]);
        }

        $note = $project->notes()->create([
            'user_id' => Auth::id(),
            'user_role' => $userRole,
            'content' => $validated['content'],
            'attachments' => $attachments,
            'sla_extended_days' => $extendDays,
        ]);

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Đã gửi note thành công' . ($extendDays > 0 ? ' (Đã gia hạn SLA Hãng +1 ngày làm việc)' : '') . '.');
    }

    /**
     * Remind Vendor action (PM extends Vendor SLA by +3 working days)
     */
    public function remindVendor(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $request->validate([
            'remind_note' => ['nullable', 'string'],
        ]);

        $now = now();
        $currentDue = $project->vendor_due_at ? max($now, $project->vendor_due_at) : $now;
        $newDue = Project::addWorkingDays(Carbon::parse($currentDue), 3);

        $old = $project->getAttributes();
        $project->increment('vendor_reminder_count');
        $project->update([
            'vendor_due_at' => $newDue,
            'last_vendor_reminded_at' => $now,
            'registration_status' => 'vendor_reminded',
        ]);
        app(\App\Services\ActivityLogService::class)->logUpdated($project, $old, $project->fresh()->getAttributes());

        if ($request->filled('remind_note')) {
            $project->notes()->create([
                'user_id' => Auth::id(),
                'user_role' => 'pm',
                'content' => "🔴 [Nhắc Hãng lần " . $project->vendor_reminder_count . "]: " . $request->remind_note,
                'sla_extended_days' => 3,
            ]);
        }

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Đã ghi nhận nhắc Hãng và gia hạn thời hạn Hãng phản hồi thêm +3 ngày làm việc.');
    }

    /**
     * Submit Vendor Price Quote (PM action - Quotation Versioning v1, v2, v3...)
     */
    public function submitVendorQuote(Request $request, Project $project, NotificationService $notificationService)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'vendor_deal_id' => ['nullable', 'string', 'max:100'],
            'quote_file.*' => ['nullable', 'file', 'mimes:xlsx,xls,pdf,doc,docx,jpg,png', 'max:10240'],
            'quote_note' => ['nullable', 'string'],
            'valid_until' => ['nullable', 'date'],
            'requote_reason' => ['nullable', 'string'],
        ]);

        $paths = [];
        if ($request->hasFile('quote_file')) {
            $files = $request->file('quote_file');
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                $originalName = $file->getClientOriginalName();
                $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalName);
                $paths[] = $file->storeAs('vendor_quotes', $safeName, 'public');
            }
        }

        // Determine version number
        $lastVersion = $project->vendorQuoteVersions()->max('version_number') ?? 0;
        $newVersion = $lastVersion + 1;

        // Create Quote Version record (preserving past files without overwriting)
        $quoteVersion = $project->vendorQuoteVersions()->create([
            'version_number' => $newVersion,
            'vendor_deal_id' => $validated['vendor_deal_id'] ?? $project->vendor_deal_id,
            'quote_file' => $paths,
            'quote_note' => $validated['quote_note'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'requote_reason' => $validated['requote_reason'] ?? null,
            'created_by' => Auth::id(),
        ]);

        // Update current project quote fields
        $old = $project->getAttributes();
        $project->update([
            'vendor_deal_id' => $quoteVersion->vendor_deal_id,
            'vendor_quote_file' => array_merge($project->vendor_quote_file ?? [], $paths),
            'vendor_quote_note' => $quoteVersion->quote_note,
            'vendor_quote_valid_until' => $quoteVersion->valid_until,
            'registration_status' => 'vendor_quoted',
        ]);
        app(\App\Services\ActivityLogService::class)->logUpdated($project, $old, $project->fresh()->getAttributes());

        $notificationService->notifyProjectVendorQuoted($project);

        return redirect()->route('projects.show', $project->id)
            ->with('success', "Đã gửi Báo giá Hãng cho Sales thành công (Phiên bản v{$newVersion}).");
    }

    /**
     * Complete Registration Workflow (PM Action -> Transition to Update Status mode)
     */
    public function completeRegistration(Project $project)
    {
        $this->authorize('update', $project);

        $old = $project->getAttributes();
        $project->update([
            'registration_status' => 'update_status',
        ]);
        app(\App\Services\ActivityLogService::class)->logUpdated($project, $old, $project->fresh()->getAttributes());

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Đã hoàn tất quy trình Đăng ký dự án. Dự án chuyển sang giai đoạn Cập nhật tiến độ hàng tháng (Update Status).');
    }

    /**
     * Monthly Project Status Update (Sales Action)
     */
    public function updateProjectStatus(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'forecast_stage' => ['required', 'in:commit,best_case,close_deal'],
            'support_request_type' => ['nullable', 'string'],
            'support_request_note' => ['nullable', 'string'],
        ]);

        $now = now();

        // Record update entry
        $project->statusUpdates()->create([
            'user_id' => Auth::id(),
            'forecast_stage' => $validated['forecast_stage'],
            'support_request_type' => $validated['support_request_type'] ?? null,
            'support_request_note' => $validated['support_request_note'] ?? null,
        ]);

        $updateData = [
            'forecast_stage' => $validated['forecast_stage'],
            'support_request_type' => $validated['support_request_type'] ?? null,
            'support_request_note' => $validated['support_request_note'] ?? null,
            'last_sales_updated_at' => $now,
            'missed_update_count' => 0, // Reset missed count
        ];

        if ($validated['forecast_stage'] === 'close_deal') {
            // Handled via closeProject modal if closing
        } elseif ($validated['support_request_type'] === 'request_update_price') {
            // Non-FTN re-quote request: starts 3 working days SLA for PM
            $updateData['vendor_due_at'] = Project::addWorkingDays($now, 3);
            $updateData['registration_status'] = 'vendor_processing';
        }

        $old = $project->getAttributes();
        $project->update($updateData);
        app(\App\Services\ActivityLogService::class)->logUpdated($project, $old, $project->fresh()->getAttributes());

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Đã cập nhật tiến độ dự án định kỳ thành công.');
    }

    /**
     * Close Project Action
     */
    public function closeProject(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'close_status' => ['required', 'in:closed_won,closed_lost,cancelled,on_hold'],
            'close_reason' => ['required_if:close_status,closed_lost,cancelled', 'nullable', 'string'],
            'close_note' => ['nullable', 'string'],
            'po_code' => ['required_if:close_status,closed_won', 'nullable', 'string', 'max:100'],
            'order_value' => ['required_if:close_status,closed_won', 'nullable', 'numeric', 'min:0'],
            'order_date' => ['required_if:close_status,closed_won', 'nullable', 'date'],
        ]);

        $closeStatus = $validated['close_status'];
        $updateData = [
            'registration_status' => $closeStatus,
            'status' => $closeStatus === 'closed_won' ? 'completed' : ($closeStatus === 'on_hold' ? 'on_hold' : 'cancelled'),
            'close_reason' => $validated['close_reason'] ?? null,
            'close_note' => $validated['close_note'] ?? null,
        ];

        if ($closeStatus === 'closed_won') {
            $updateData['po_code'] = $validated['po_code'];
            $updateData['order_value'] = $validated['order_value'];
            $updateData['order_date'] = $validated['order_date'];
        }

        $old = $project->getAttributes();
        $project->update($updateData);
        app(\App\Services\ActivityLogService::class)->logUpdated($project, $old, $project->fresh()->getAttributes());

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Đã cập nhật đóng dự án thành công.');
    }

    /**
     * Restore Expired or Cancelled Project (PM or Manager action)
     */
    public function restoreProject(Project $project)
    {
        $this->authorize('update', $project);

        $old = $project->getAttributes();
        $project->update([
            'registration_status' => 'update_status',
            'status' => 'in_progress',
            'missed_update_count' => 0,
            'last_sales_updated_at' => now(),
        ]);
        app(\App\Services\ActivityLogService::class)->logUpdated($project, $old, $project->fresh()->getAttributes());

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Đã khôi phục dự án hoạt động trở lại thành công.');
    }

    /**
     * Export single Project detail to Excel format for Vendor submission
     */
    public function exportVendorExcel(Project $project)
    {
        $this->authorize('view', $project);

        $filename = 'Dang_Ky_Du_An_' . $project->code . '_' . date('Y-m-d') . '.xlsx';
        return Excel::download(new ProjectsExport(['project_id' => $project->id]), $filename);
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit(Project $project)
    {
        $this->authorize('update', $project);

        $selectedCustomerId = old('collaborate_customer_id') ?? $project->collaborate_customer_id;
        $customers = $selectedCustomerId 
            ? Customer::where('id', $selectedCustomerId)->get() 
            : collect();

        $managers = User::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $industries = self::INDUSTRIES;

        return view('projects.edit', compact('project', 'customers', 'managers', 'suppliers', 'industries'));
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $old = $project->getAttributes();

        $collabRequired = $request->input('collaborate_type') === 'partner' ? 'required' : 'nullable';

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('projects')->ignore($project->id)],
            'name' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:planning,in_progress,completed,cancelled,on_hold'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'note' => ['required', 'string'],
            // Distributor
            'vendor_id' => ['required', 'exists:suppliers,id'],
            'distributor_am' => ['required', 'string', 'max:255'],
            // End-User
            'eu_name_vi' => ['required', 'string', 'max:500'],
            'eu_name_en' => ['required', 'string', 'max:500'],
            'eu_name_abbr' => ['nullable', 'string', 'max:100'],
            'eu_tax_code' => ['required', 'string', 'max:100'],
            'eu_province' => ['nullable', 'string', 'max:100'],
            'eu_industry' => ['nullable', 'string'],
            'eu_industry_other' => ['required_if:eu_industry,other', 'nullable', 'string', 'max:255'],
            // Collaboration
            'collaborate_type' => ['required', 'in:partner,end_user'],
            'collaborate_customer_id' => ['nullable', 'exists:customers,id'],
            'collaborate_company' => [$collabRequired, 'string', 'max:500'],
            'collaborate_tax_code' => [$collabRequired, 'string', 'max:100'],
            'collaborate_pic_name' => [$collabRequired, 'string', 'max:255'],
            'collaborate_pic_title' => [$collabRequired, 'string', 'max:255'],
            'collaborate_pic_phone' => [$collabRequired, 'string', 'max:50'],
            'collaborate_pic_email' => ['nullable', 'string', 'email', 'max:255'],
            'end_date' => ['required', 'date'],
            'bom_file' => ['nullable', 'array'],
            'bom_file.*' => ['file', 'mimes:xlsx,xls,pdf,doc,docx', 'max:10240'],
            'keep_bom_files' => ['nullable', 'array'],
            'bom_data' => ['nullable', 'string'],
            'net_to_tech_horizon' => ['nullable', 'numeric', 'min:0'],
            'stage' => ['nullable', 'string', 'max:50'],
            'deal_type' => ['nullable', 'string', 'max:50'],
            'sn_numbers' => ['required_if:deal_type,trade_up', 'nullable', 'string'],
            'special_request_type' => ['nullable', 'string'],
            'special_request_note' => ['nullable', 'string'],
        ], [], $this->validationAttributes());

        $validated['name_en'] = $validated['name_en'] ?? $validated['name'];

        if ($validated['eu_industry'] === 'other') {
            $validated['eu_industry'] = $request->input('eu_industry_other');
        } elseif (!empty($validated['eu_industry'])) {
            if (!array_key_exists($validated['eu_industry'], self::INDUSTRIES)) {
                return back()->withInput()->withErrors(['eu_industry' => 'Ngành nghề không hợp lệ.']);
            }
        }

        $validated['customer_name'] = $validated['eu_name_vi'];
        $validated['end_date'] = Carbon::parse($validated['end_date'])->format('Y-m-d');
        $startDate = Carbon::parse($project->start_date ?? now());
        $validated['estimated_close_months'] = (int) max(1, round($startDate->diffInMonths(Carbon::parse($validated['end_date']))));

        // File keeping logic
        $currentFiles = is_array($project->bom_file) ? $project->bom_file : [];
        $keepFiles = $request->input('keep_bom_files', []);
        $deletedFiles = array_diff($currentFiles, $keepFiles);
        foreach ($deletedFiles as $deletedFile) {
            Storage::disk('public')->delete($deletedFile);
        }

        $newFiles = [];
        if ($request->hasFile('bom_file')) {
            $files = $request->file('bom_file');
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                $originalName = $file->getClientOriginalName();
                $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $originalName);
                $newFiles[] = $file->storeAs('bom', $safeName, 'public');
            }
        }
        $validated['bom_file'] = array_merge($keepFiles, $newFiles);

        $project->update($validated);
        app(\App\Services\ActivityLogService::class)->logUpdated($project, $old, $project->fresh()->getAttributes());

        return redirect()->route('projects.show', $project->id)
            ->with('success', 'Dự án đã được cập nhật thành công.');
    }

    /**
     * Update project status via AJAX.
     */
    public function updateStatus(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'status' => ['required', 'in:planning,in_progress,completed,cancelled,on_hold'],
        ]);

        $old = $project->getAttributes();
        $project->update(['status' => $validated['status']]);
        app(\App\Services\ActivityLogService::class)->logUpdated($project, $old, $project->fresh()->getAttributes());

        return response()->json([
            'success' => true,
            'message' => 'Trạng thái đã được cập nhật.',
            'status_label' => $project->fresh()->status_label,
        ]);
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);

        if ($project->sales()->exists() || $project->saleItems()->exists()) {
            return back()->with('error', 'Không thể xóa dự án đã có đơn hàng.');
        }

        if ($project->exports()->exists()) {
            return back()->with('error', 'Không thể xóa dự án đã có phiếu xuất vật tư.');
        }

        app(\App\Services\ActivityLogService::class)->logDeleted($project);
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Dự án đã được xóa thành công.');
    }

    /**
     * Get projects for AJAX dropdown selection
     */
    public function getList(Request $request)
    {
        $query = Project::query();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $query->whereIn('status', ['planning', 'in_progress']);

        $projects = $query->orderBy('name')
            ->limit(50)
            ->get(['id', 'code', 'name', 'customer_name', 'eu_tax_code']);

        return response()->json($projects);
    }

    /**
     * Export projects to Excel
     */
    public function export(Request $request)
    {
        $filters = $request->only([
            'search', 'status', 'customer_id', 'vendor_id', 'quarter', 'year',
            'manager_id', 'initial_processed_by', 'registration_status', 'is_overdue_sla',
            'start_date', 'end_date', 'expiry_start_date', 'expiry_end_date'
        ]);
        $filters['export_type'] = 'detailed';
        $filename = 'bao-cao-du-an-' . date('Y-m-d') . '.xlsx';

        return Excel::download(new ProjectsExport($filters), $filename);
    }

    /**
     * Executive Report & KPI Dashboard
     */
    public function report(Request $request)
    {
        $this->authorize('viewReport', Project::class);

        $query = Project::with(['customer', 'vendor', 'manager', 'initialProcessedBy', 'vendorQuoteVersions']);

        // Filters
        if ($request->filled('quarter')) {
            $quarter = $request->quarter;
            $year = $request->input('year', date('Y'));
            $quarterMonths = [
                'Q1' => [1, 3],
                'Q2' => [4, 6],
                'Q3' => [7, 9],
                'Q4' => [10, 12],
            ];
            if (isset($quarterMonths[$quarter])) {
                $query->whereYear('created_at', $year)
                      ->whereMonth('created_at', '>=', $quarterMonths[$quarter][0])
                      ->whereMonth('created_at', '<=', $quarterMonths[$quarter][1]);
            }
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        if ($request->filled('initial_processed_by')) {
            $query->where('initial_processed_by', $request->initial_processed_by);
        }

        if ($request->filled('registration_status')) {
            $query->where('registration_status', $request->registration_status);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('expiry_start_date')) {
            $query->whereDate('end_date', '>=', $request->expiry_start_date);
        }

        if ($request->filled('expiry_end_date')) {
            $query->whereDate('end_date', '<=', $request->expiry_end_date);
        }

        if ($request->filled('is_overdue_sla')) {
            $query->where(function($q) {
                $q->where(function($q1) {
                    $q1->where('intake_status', 'pending')
                       ->whereNotNull('initial_sla_due_at')
                       ->where('initial_sla_due_at', '<', now());
                })->orWhere(function($q2) {
                    $q2->whereIn('registration_status', ['vendor_processing', 'vendor_reminded', 'processing'])
                       ->whereNotNull('vendor_due_at')
                       ->where('vendor_due_at', '<', now());
                });
            });
        }

        $projects = $query->orderBy('created_at', 'desc')->get();

        $totalCount = $projects->count();
        $closedWonCount = $projects->where('registration_status', 'closed_won')->count();
        $duplicateCount = $projects->where('intake_status', 'duplicate')->count();
        $expiredCount = $projects->where('registration_status', 'expired')->count();
        $waitingVendorCount = $projects->whereIn('registration_status', ['vendor_processing', 'vendor_reminded'])->count();
        $overdueSlaCount = $projects->filter(fn($p) => $p->is_initial_overdue || $p->is_vendor_overdue)->count();

        // Projects cancelled because Sales didn't update (expired and cancelled)
        $cancelledByNoUpdateCount = $projects->where('registration_status', 'expired')
            ->where('status', 'cancelled')
            ->count();

        // Calculate PM average turnaround time (hours)
        $pmTimes = $projects->filter(fn($p) => $p->initial_processed_at)
            ->map(fn($p) => $p->created_at->diffInHours($p->initial_processed_at));
        $avgPmHours = $pmTimes->count() > 0 ? round($pmTimes->avg(), 1) : 0;

        // Calculate Vendor average response time (hours)
        $vendorTimes = $projects->filter(fn($p) => $p->vendor_submitted_at && $p->vendorQuoteVersions->count() > 0)
            ->map(fn($p) => $p->vendor_submitted_at->diffInHours($p->vendorQuoteVersions->min('created_at')));
        $avgVendorHours = $vendorTimes->count() > 0 ? round($vendorTimes->avg(), 1) : 0;

        $kpis = [
            'total' => $totalCount,
            'closed_won' => $closedWonCount,
            'closed_won_rate' => $totalCount > 0 ? round(($closedWonCount / $totalCount) * 100, 1) : 0,
            'duplicate_rate' => $totalCount > 0 ? round(($duplicateCount / $totalCount) * 100, 1) : 0,
            'expired_rate' => $totalCount > 0 ? round(($expiredCount / $totalCount) * 100, 1) : 0,
            'waiting_vendor' => $waitingVendorCount,
            'overdue_sla' => $overdueSlaCount,
            'avg_pm_hours' => $avgPmHours,
            'avg_vendor_hours' => $avgVendorHours,
            'cancelled_no_update' => $cancelledByNoUpdateCount,
        ];

        $totals = [
            'budget' => $projects->sum('budget'),
            'revenue' => $projects->sum(fn($p) => $p->total_revenue),
            'cost' => $projects->sum(fn($p) => $p->total_cost),
            'profit' => $projects->sum(fn($p) => $p->profit),
            'debt' => $projects->sum(fn($p) => $p->total_debt),
        ];

        $vendors = Supplier::orderBy('name')->get();
        // Load all users with PM/PO role/department and all managers/sales
        $managers = User::orderBy('name')->get();
        $pms = User::whereIn('department', ['PM', 'PO', 'PM Team', 'PO Team'])
            ->orWhereHas('roles', function($q) {
                $q->whereIn('slug', ['purchase_manager', 'purchase_staff']);
            })->orderBy('name')->get();

        return view('projects.report', compact('projects', 'totals', 'kpis', 'vendors', 'managers', 'pms'));
    }

    /**
     * Vietnamese validation attribute names
     */
    private function validationAttributes(): array
    {
        return [
            'code' => 'Mã dự án',
            'name' => 'Tên tiếng Việt (Dự án)',
            'name_en' => 'Tên tiếng Anh (Dự án)',
            'vendor_id' => 'Hãng (Vendor)',
            'distributor_am' => 'Distributor AM',
            'eu_name_vi' => 'Tên tiếng Việt (End-User)',
            'eu_name_en' => 'Tên tiếng Anh (End-User)',
            'eu_tax_code' => 'Website / Mã số thuế (End-User)',
            'eu_province' => 'Tỉnh / Thành phố',
            'eu_industry' => 'Ngành nghề',
            'collaborate_type' => 'Hình thức hợp tác',
            'collaborate_company' => 'Tên công ty hợp tác',
            'collaborate_tax_code' => 'Mã số thuế (hợp tác)',
            'collaborate_pic_name' => 'Tên người liên hệ (PIC)',
            'collaborate_pic_title' => 'Chức danh (PIC)',
            'collaborate_pic_phone' => 'Số điện thoại (PIC)',
            'collaborate_pic_email' => 'Email (PIC)',
            'end_date' => 'Ngày chốt dự kiến (Estimated Close Date)',
            'bom_file' => 'File BOM / YCKT',
            'deal_type' => 'Loại deal (Fortinet)',
            'sn_numbers' => 'Note (Fortinet Dealreg Only)',
            'note' => 'Note',
            'special_request_note' => 'Ghi chú yêu cầu thêm',
        ];
    }
}
