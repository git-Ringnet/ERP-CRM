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
                            <tr class="hover:bg-blue-50/30 transition-colors">
                                <!-- 1. Mã dự án -->
                                <td class="px-4 py-3">
                                    <a href="{{ route('projects.show', $project->id) }}"
                                        class="font-mono font-bold text-primary hover:underline">
                                        {{ $project->code }}
                                    </a>
                                    <span class="block text-[11px] text-gray-400">{{ $project->created_at ? $project->created_at->format('d/m/Y H:i') : '' }}</span>
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
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
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

    <script>
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
                    // Show toast
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