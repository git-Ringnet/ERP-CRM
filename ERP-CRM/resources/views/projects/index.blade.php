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
                    class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                    <i class="fas fa-chart-bar mr-2"></i> Báo cáo
                </a>
                <a href="{{ route('projects.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-plus mr-2"></i> Thêm dự án
                </a>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="bg-white rounded-lg shadow-sm p-4">
            <form method="GET" class="flex flex-wrap gap-3">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Tìm mã, tên dự án, khách hàng..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">Từ</span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="w-36 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">Đến</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="w-36 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div class="w-40">
                    <select name="status"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">Tất cả trạng thái</option>
                        <option value="planning" {{ request('status') == 'planning' ? 'selected' : '' }}>Lên kế hoạch</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Đang thực hiện</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="on_hold" {{ request('status') == 'on_hold' ? 'selected' : '' }}>Tạm dừng</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                    </select>
                </div>
                <div class="w-48">
                    <select name="customer_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        <option value="">Tất cả khách hàng</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-search mr-1"></i> Tìm
                </button>
                <a href="{{ route('projects.index') }}"
                    class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors">
                    <i class="fas fa-redo mr-1"></i> Reset
                </a>
            </form>
        </div>

        <!-- Projects Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã dự án</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên dự án</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên EU - MST</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên SI</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Expired Date</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($projects as $project)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('projects.show', $project->id) }}"
                                        class="font-medium text-primary hover:underline">
                                        {{ $project->code }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $project->name }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @if($project->eu_name_vi)
                                        <div class="text-sm font-medium text-gray-900">{{ $project->eu_name_vi }}</div>
                                        @if($project->eu_tax_code)
                                            <div class="text-xs text-gray-500">MST: {{ $project->eu_tax_code }}</div>
                                        @endif
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $project->collaborate_company ?? '-' }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm">
                                    @if($project->end_date)
                                        <span class="{{ $project->end_date->isPast() ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                                            {{ $project->end_date->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <select onchange="updateProjectStatus({{ $project->id }}, this.value, this)"
                                        class="text-xs font-semibold rounded-full px-3 py-1 border-0 cursor-pointer focus:ring-2 focus:ring-primary
                                            {{ match($project->status) {
                                                'planning' => 'bg-yellow-100 text-yellow-800',
                                                'in_progress' => 'bg-blue-100 text-blue-800',
                                                'completed' => 'bg-green-100 text-green-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                                'on_hold' => 'bg-gray-100 text-gray-800',
                                                default => 'bg-gray-100 text-gray-800',
                                            } }}">
                                        <option value="planning" {{ $project->status == 'planning' ? 'selected' : '' }}>Lên kế hoạch</option>
                                        <option value="in_progress" {{ $project->status == 'in_progress' ? 'selected' : '' }}>Đang thực hiện</option>
                                        <option value="completed" {{ $project->status == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                                        <option value="on_hold" {{ $project->status == 'on_hold' ? 'selected' : '' }}>Tạm dừng</option>
                                        <option value="cancelled" {{ $project->status == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3 text-center">
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
                                    <i class="fas fa-folder-open text-4xl mb-2"></i>
                                    <p>Chưa có dự án nào</p>
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