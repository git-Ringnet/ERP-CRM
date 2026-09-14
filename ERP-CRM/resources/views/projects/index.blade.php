@extends('layouts.app')

@section('title', 'Quản lý dự án')
@section('page-title', 'Quản lý dự án')

@section('content')
    <div class="space-y-4">
        <!-- Header Actions -->
        <div class="flex flex-wrap gap-2 justify-end items-center">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('projects.export', request()->query()) }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-file-excel mr-2"></i> Xuất Excel
                </a>
                <a href="{{ route('projects.report') }}"
                    class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-chart-bar mr-2"></i> Báo cáo
                </a>
                <a href="{{ route('projects.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-plus mr-2"></i> Thêm dự án
                </a>
            </div>
        </div>

        <!-- Team Filter Tabs -->
        <div class="flex border-b border-gray-200 bg-white rounded-t-lg overflow-hidden px-4 pt-3 gap-2">
            <a href="{{ route('projects.index', array_merge(request()->query(), ['team' => ''])) }}"
               class="px-4 py-2 text-sm font-semibold border-b-2 transition-all {{ !request('team') ? 'border-primary text-primary bg-blue-50/50' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                <i class="fas fa-layer-group mr-1.5"></i> Tất cả dự án
            </a>
            <a href="{{ route('projects.index', array_merge(request()->query(), ['team' => 'po_team'])) }}"
               class="px-4 py-2 text-sm font-semibold border-b-2 transition-all {{ request('team') === 'po_team' ? 'border-purple-600 text-purple-700 bg-purple-50' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                <i class="fas fa-shield-alt mr-1.5 text-purple-600"></i> PO Team (FTN - Fortinet)
            </a>
            <a href="{{ route('projects.index', array_merge(request()->query(), ['team' => 'pm_team'])) }}"
               class="px-4 py-2 text-sm font-semibold border-b-2 transition-all {{ request('team') === 'pm_team' ? 'border-blue-600 text-blue-700 bg-blue-50' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                <i class="fas fa-briefcase mr-1.5 text-blue-600"></i> PM Team (Non-FTN)
            </a>
        </div>

        <!-- Search & Filter -->
        <div class="bg-white rounded-b-lg shadow-sm p-4">
            <form method="GET" class="flex flex-wrap gap-3">
                <input type="hidden" name="team" value="{{ request('team') }}">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Tìm mã, tên dự án, tên tiếng Anh, MST..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div class="w-44">
                    <select name="registration_status"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                        <option value="">-- Trạng thái ĐKDA --</option>
                        <option value="submitted" {{ request('registration_status') == 'submitted' ? 'selected' : '' }}>Mới gửi ĐKDA</option>
                        <option value="vendor_processing" {{ request('registration_status') == 'vendor_processing' ? 'selected' : '' }}>Chờ Hãng phản hồi</option>
                        <option value="vendor_reminded" {{ request('registration_status') == 'vendor_reminded' ? 'selected' : '' }}>Đã nhắc Hãng</option>
                        <option value="vendor_quoted" {{ request('registration_status') == 'vendor_quoted' ? 'selected' : '' }}>Hãng đã báo giá</option>
                        <option value="update_status" {{ request('registration_status') == 'update_status' ? 'selected' : '' }}>Đang theo đuổi (Update status)</option>
                        <option value="closed_won" {{ request('registration_status') == 'closed_won' ? 'selected' : '' }}>Closed Won</option>
                        <option value="closed_lost" {{ request('registration_status') == 'closed_lost' ? 'selected' : '' }}>Closed Lost</option>
                        <option value="expired" {{ request('registration_status') == 'expired' ? 'selected' : '' }}>Expired (Hết hạn)</option>
                    </select>
                </div>
                <div class="w-44">
                    <select name="vendor_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary text-sm">
                        <option value="">-- Tất cả Hãng --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" {{ request('vendor_id') == $supplier->id ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search mr-1"></i> Tìm
                </button>
                <a href="{{ route('projects.index') }}"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-redo mr-1"></i> Reset
                </a>
            </form>
        </div>

        <!-- Projects Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-3 py-3 text-center w-10">
                                <input type="checkbox" id="selectAllProjects" title="Chọn tất cả"
                                       class="rounded border-gray-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer">
                            </th>
                            <th class="px-4 py-3 text-left whitespace-nowrap">Mã dự án</th>
                            <th class="px-4 py-3 text-left">Tên dự án (Vi/En)</th>
                            <th class="px-4 py-3 text-left">Người đăng ký</th>
                            <th class="px-4 py-3 text-left">End-User / MST</th>
                            <th class="px-4 py-3 text-left">Hãng / Team</th>
                            <th class="px-4 py-3 text-center whitespace-nowrap">Trạng thái ĐKDA</th>
                            <th class="px-4 py-3 text-center whitespace-nowrap">Cảnh báo SLA</th>
                            <th class="px-4 py-3 text-center whitespace-nowrap">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        @forelse($projects as $project)
                            @php
                                $partnerName = $project->collaborate_company ?: ($project->customer->name ?? $project->customer_name ?? 'Trống');
                                $partnerTax = $project->collaborate_tax_code ?: ($project->customer->tax_code ?? '');
                                $partnerId = $project->collaborate_customer_id ?: $project->customer_id;
                                $euName = $project->eu_name_vi ?: ($project->customer_name ?? 'Trống');
                                $euTax = $project->eu_tax_code ?? '';
                            @endphp
                            <tr class="hover:bg-blue-50/30 transition-colors project-row" id="row-{{ $project->id }}">
                                <!-- Checkbox -->
                                <td class="px-3 py-3 text-center">
                                    <input type="checkbox" class="project-checkbox rounded border-gray-300 text-primary focus:ring-primary h-4 w-4 cursor-pointer"
                                           value="{{ $project->id }}"
                                           data-id="{{ $project->id }}"
                                           data-code="{{ $project->code }}"
                                           data-name="{{ $project->name }}"
                                           data-partner-name="{{ $partnerName }}"
                                           data-partner-tax="{{ $partnerTax }}"
                                           data-partner-id="{{ $partnerId }}"
                                           data-eu-name="{{ $euName }}"
                                           data-eu-tax="{{ $euTax }}"
                                           onchange="handleProjectCheckboxChange()">
                                </td>
                                <!-- 1. Mã dự án -->
                                <td class="px-4 py-3">
                                    <a href="{{ route('projects.show', $project->id) }}"
                                        class="font-mono font-bold text-primary hover:underline">
                                        {{ $project->code }}
                                    </a>
                                    <span class="block text-[11px] text-gray-400">{{ $project->created_at ? $project->created_at->format('d/m/Y H:i') : '' }}</span>
                                    @if($project->po_code)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 mt-0.5" title="Đã có đơn hàng bán">
                                            <i class="fas fa-file-invoice-dollar mr-0.5"></i> {{ $project->po_code }}
                                        </span>
                                    @endif
                                </td>
                                <!-- 2. Tên dự án (Vi/En) -->
                                <td class="px-4 py-3">
                                    <a href="{{ route('projects.show', $project->id) }}" class="font-medium text-gray-900 hover:text-primary">
                                        {{ $project->name }}
                                    </a>
                                    @if($project->name_en)
                                        <span class="block text-xs text-gray-500 italic">{{ $project->name_en }}</span>
                                    @endif
                                </td>
                                <!-- 2.5. Người đăng ký -->
                                <td class="px-4 py-3">
                                    <span class="font-medium text-gray-800">{{ $project->manager->name ?? 'N/A' }}</span>
                                    <span class="block text-xs text-gray-500">{{ $project->manager->email ?? '' }}</span>
                                </td>
                                <!-- 3. End-User / MST -->
                                <td class="px-4 py-3">
                                    <span class="font-medium text-gray-800">{{ $project->eu_name_vi ?: $project->customer_name }}</span>
                                    <span class="block text-xs font-mono text-gray-500">MST: {{ $project->eu_tax_code ?: '-' }}</span>
                                </td>
                                <!-- 4. Hãng / Team -->
                                <td class="px-4 py-3">
                                    <span class="font-semibold text-gray-900">{{ $project->vendor->name ?? '-' }}</span>
                                    <span class="block text-[11px] px-2 py-0.5 rounded w-max font-semibold {{ $project->assigned_team === 'po_team' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ $project->assigned_team === 'po_team' ? 'PO Team (FTN)' : 'PM Team' }}
                                    </span>
                                </td>
                                <!-- 5. Trạng thái ĐKDA -->
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <span class="inline-block px-2.5 py-1 text-xs font-semibold rounded-full whitespace-nowrap {{ $project->registration_status_badge['color'] }}">
                                        {{ $project->registration_status_badge['label'] }}
                                    </span>
                                </td>
                                <!-- 6. Cảnh báo SLA -->
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if($project->intake_status === 'pending')
                                        <span class="px-2 py-1 text-xs font-bold rounded-full border whitespace-nowrap {{ $project->initial_sla_status['color'] }}">
                                            {{ $project->initial_sla_status['label'] }}
                                        </span>
                                    @elseif($project->is_vendor_overdue)
                                        <span class="px-2 py-1 text-xs font-bold bg-red-100 text-red-800 rounded-full border border-red-300 whitespace-nowrap">
                                            🔴 Quá hạn Hãng
                                        </span>
                                    @elseif($project->is_sales_update_overdue)
                                        <span class="px-2 py-1 text-xs font-bold bg-amber-100 text-amber-800 rounded-full border border-amber-300 whitespace-nowrap">
                                            ⚠️ Cần Sales update
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400 whitespace-nowrap">Đúng SLA</span>
                                    @endif
                                </td>
                                <!-- 7. Thao tác -->
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <div class="flex justify-center gap-1">
                                        <a href="{{ route('projects.show', $project->id) }}"
                                            class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors"
                                            title="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('projects.edit', $project->id) }}"
                                            class="p-2 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 hover:text-yellow-700 transition-colors"
                                            title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('projects.destroy', $project) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" onclick="confirmDelete(this.form, 'dự án {{ $project->name }}')"
                                                class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200"
                                                title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-folder-open text-4xl mb-2 text-gray-300"></i>
                                    <p>Không có dự án nào</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($projects->hasPages())
                <div class="px-4 py-3 border-t">
                    {{ $projects->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Floating Batch Action Bar -->
    <div id="batchActionBar" class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-40 bg-gray-900 text-white px-6 py-3.5 rounded-2xl shadow-2xl flex items-center space-x-4 border border-gray-700 hidden transition-all duration-300">
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-primary text-xs font-bold text-white" id="selectedCountBadge">0</span>
            <span class="text-sm font-medium">dự án đã chọn</span>
        </div>
        <div class="h-5 w-px bg-gray-700"></div>
        <button type="button" onclick="handleCreateOrderFromSelectedProjects()" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white text-sm font-semibold rounded-xl shadow-md transition-all">
            <i class="fas fa-file-invoice-dollar mr-2"></i> Tạo đơn hàng từ các dự án đã chọn
        </button>
        <button type="button" onclick="deselectAllProjects()" class="text-gray-400 hover:text-white text-sm px-2 py-1">
            <i class="fas fa-times mr-1"></i> Bỏ chọn
        </button>
    </div>

    <!-- Mismatch Partner / End-User Confirmation Modal -->
    <div id="mismatchPartnerEuModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeMismatchModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100">
                <div class="bg-amber-500 px-6 py-4 flex items-center justify-between text-white">
                    <div class="flex items-center space-x-2.5">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                        <h3 class="text-base font-bold" id="modal-title">Thông tin Partner / End-User không trùng khớp</h3>
                    </div>
                    <button type="button" onclick="closeMismatchModal()" class="text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="p-6 space-y-4 text-sm text-gray-700">
                    <p class="leading-relaxed">
                        Các dự án bạn đã chọn có thông tin <strong>Partner (SI)</strong> hoặc <strong>End-User (EU)</strong> khác nhau:
                    </p>

                    <div class="overflow-x-auto max-h-56 overflow-y-auto border border-gray-200 rounded-xl">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-100 font-semibold text-gray-700 border-b border-gray-200">
                                <tr>
                                    <th class="px-3 py-2 text-left">Mã dự án</th>
                                    <th class="px-3 py-2 text-left">Tên dự án</th>
                                    <th class="px-3 py-2 text-left">Partner (SI)</th>
                                    <th class="px-3 py-2 text-left">End-User (EU)</th>
                                </tr>
                            </thead>
                            <tbody id="mismatchProjectTableBody" class="divide-y divide-gray-100">
                                <!-- Populated dynamically by JS -->
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3.5 space-y-2 text-xs text-amber-900">
                        <div class="font-bold flex items-center">
                            <i class="fas fa-info-circle mr-1.5 text-amber-600"></i> Bạn có muốn tiếp tục tạo đơn hàng không?
                        </div>
                        <p class="leading-relaxed text-amber-800">
                            👉 <strong>Nếu tiếp tục:</strong> Đơn hàng mới sẽ <strong>để trống thông tin SI và EU</strong> để Sales tự nhập lại. Các dự án đang đăng ký vẫn được <strong>giữ nguyên</strong> và sẽ hiển thị liên kết với mã đơn hàng sau khi lưu.
                        </p>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex flex-wrap justify-end gap-3 border-t border-gray-100">
                    <button type="button" onclick="closeMismatchModal()"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl transition-colors">
                        Hủy bỏ
                    </button>
                    <button type="button" id="btnConfirmProceedMismatch"
                            class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-xl shadow-md transition-colors">
                        <i class="fas fa-arrow-right mr-1.5"></i> Tiếp tục tạo đơn hàng
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Checkbox management
        const selectAllCheckbox = document.getElementById('selectAllProjects');
        const projectCheckboxes = document.querySelectorAll('.project-checkbox');
        const batchActionBar = document.getElementById('batchActionBar');
        const selectedCountBadge = document.getElementById('selectedCountBadge');

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function () {
                projectCheckboxes.forEach(cb => {
                    cb.checked = selectAllCheckbox.checked;
                    updateRowHighlight(cb);
                });
                updateBatchActionBar();
            });
        }

        function handleProjectCheckboxChange() {
            updateBatchActionBar();
            // Update select all state
            const allChecked = Array.from(projectCheckboxes).length > 0 && Array.from(projectCheckboxes).every(cb => cb.checked);
            if (selectAllCheckbox) selectAllCheckbox.checked = allChecked;
        }

        function updateRowHighlight(cb) {
            const row = document.getElementById('row-' + cb.value);
            if (row) {
                if (cb.checked) {
                    row.classList.add('bg-blue-50/70');
                } else {
                    row.classList.remove('bg-blue-50/70');
                }
            }
        }

        function updateBatchActionBar() {
            const checked = Array.from(projectCheckboxes).filter(cb => cb.checked);
            checked.forEach(cb => updateRowHighlight(cb));
            Array.from(projectCheckboxes).filter(cb => !cb.checked).forEach(cb => updateRowHighlight(cb));

            if (checked.length > 0) {
                selectedCountBadge.textContent = checked.length;
                batchActionBar.classList.remove('hidden');
            } else {
                batchActionBar.classList.add('hidden');
            }
        }

        function deselectAllProjects() {
            projectCheckboxes.forEach(cb => {
                cb.checked = false;
                updateRowHighlight(cb);
            });
            if (selectAllCheckbox) selectAllCheckbox.checked = false;
            updateBatchActionBar();
        }

        function handleCreateOrderFromSelectedProjects() {
            const checked = Array.from(projectCheckboxes).filter(cb => cb.checked);
            if (checked.length === 0) {
                showToast('Vui lòng chọn ít nhất một dự án', 'error');
                return;
            }

            if (checked.length === 1) {
                // Single project redirect
                window.location.href = "{{ route('sales.create') }}?project_id=" + checked[0].value;
                return;
            }

            // Multi-project check: Partner and EU matching logic
            const projectsData = checked.map(cb => ({
                id: cb.value,
                code: cb.dataset.code || '',
                name: cb.dataset.name || '',
                partnerName: cb.dataset.partnerName || '',
                partnerTax: (cb.dataset.partnerTax || '').trim(),
                euName: cb.dataset.euName || '',
                euTax: (cb.dataset.euTax || '').trim(),
            }));

            // Partner key: use tax code if available, otherwise lowercase name
            const getPartnerKey = p => (p.partnerTax || p.partnerName || '').trim().toLowerCase();
            // EU key: use tax code if available, otherwise lowercase name
            const getEuKey = p => (p.euTax || p.euName || '').trim().toLowerCase();

            const firstPartnerKey = getPartnerKey(projectsData[0]);
            const firstEuKey = getEuKey(projectsData[0]);

            const isPartnerMatching = projectsData.every(p => getPartnerKey(p) === firstPartnerKey);
            const isEuMatching = projectsData.every(p => getEuKey(p) === firstEuKey);

            const projectIdsParams = projectsData.map(p => 'project_ids[]=' + encodeURIComponent(p.id)).join('&');

            if (isPartnerMatching && isEuMatching) {
                // All match -> retain info
                window.location.href = "{{ route('sales.create') }}?" + projectIdsParams;
            } else {
                // Mismatch -> show confirmation modal
                showMismatchPartnerEuModal(projectsData, projectIdsParams);
            }
        }

        function showMismatchPartnerEuModal(projectsData, projectIdsParams) {
            const tbody = document.getElementById('mismatchProjectTableBody');
            tbody.innerHTML = '';

            projectsData.forEach(p => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-3 py-2 font-mono font-bold text-primary">${p.code}</td>
                    <td class="px-3 py-2 font-medium text-gray-800">${p.name}</td>
                    <td class="px-3 py-2">
                        <span class="font-medium text-gray-900">${p.partnerName || '-'}</span>
                        ${p.partnerTax ? `<span class="block text-[11px] font-mono text-gray-500">MST: ${p.partnerTax}</span>` : ''}
                    </td>
                    <td class="px-3 py-2">
                        <span class="font-medium text-gray-900">${p.euName || '-'}</span>
                        ${p.euTax ? `<span class="block text-[11px] font-mono text-gray-500">MST: ${p.euTax}</span>` : ''}
                    </td>
                `;
                tbody.appendChild(tr);
            });

            const btnConfirm = document.getElementById('btnConfirmProceedMismatch');
            btnConfirm.onclick = function () {
                window.location.href = "{{ route('sales.create') }}?" + projectIdsParams + "&clear_partner_eu=1";
            };

            document.getElementById('mismatchPartnerEuModal').classList.remove('hidden');
        }

        function closeMismatchModal() {
            document.getElementById('mismatchPartnerEuModal').classList.add('hidden');
        }

        function updateProjectStatus(projectId, newStatus, selectEl) {
            const statusColors = {
                'planning': 'bg-yellow-100 text-yellow-800',
                'in_progress': 'bg-blue-100 text-blue-800',
                'completed': 'bg-green-100 text-green-800',
                'cancelled': 'bg-red-100 text-red-800',
                'on_hold': 'bg-gray-100 text-gray-800',
            };

            // Remove old color classes
            Object.values(statusColors).forEach(cls => {
                cls.split(' ').forEach(c => selectEl.classList.remove(c));
            });

            // Add new color classes
            const newClasses = statusColors[newStatus] || statusColors['planning'];
            newClasses.split(' ').forEach(c => selectEl.classList.add(c));

            // AJAX update
            fetch(`/projects/${projectId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                }
            })
            .catch(err => {
                console.error('Error:', err);
                showToast('Lỗi khi cập nhật trạng thái', 'error');
            });
        }

        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white text-sm font-medium transition-all transform ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 2500);
        }
    </script>
@endsection