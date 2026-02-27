@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Nhật ký Kiểm toán</h2>
        <p class="text-gray-600 mt-1">Theo dõi các thay đổi về quyền và vai trò</p>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <form action="{{ route('audit-logs.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">Từ ngày</label>
                    <input type="date" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div>
                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">Đến ngày</label>
                    <input type="date" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                           id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div>
                    <label for="action_type" class="block text-sm font-medium text-gray-700 mb-2">Loại hành động</label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                            id="action_type" name="action_type">
                        <option value="">Tất cả</option>
                        <option value="created" {{ request('action_type') === 'created' ? 'selected' : '' }}>Tạo mới</option>
                        <option value="updated" {{ request('action_type') === 'updated' ? 'selected' : '' }}>Cập nhật</option>
                        <option value="deleted" {{ request('action_type') === 'deleted' ? 'selected' : '' }}>Xóa</option>
                        <option value="assigned" {{ request('action_type') === 'assigned' ? 'selected' : '' }}>Gán</option>
                        <option value="revoked" {{ request('action_type') === 'revoked' ? 'selected' : '' }}>Gỡ bỏ</option>
                        <option value="unauthorized_access" {{ request('action_type') === 'unauthorized_access' ? 'selected' : '' }}>Truy cập trái phép</option>
                    </select>
                </div>
                <div>
                    <label for="entity_type" class="block text-sm font-medium text-gray-700 mb-2">Loại đối tượng</label>
                    <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                            id="entity_type" name="entity_type">
                        <option value="">Tất cả</option>
                        <option value="role" {{ request('entity_type') === 'role' ? 'selected' : '' }}>Vai trò</option>
                        <option value="permission" {{ request('entity_type') === 'permission' ? 'selected' : '' }}>Quyền</option>
                        <option value="user_role" {{ request('entity_type') === 'user_role' ? 'selected' : '' }}>Vai trò người dùng</option>
                        <option value="user_permission" {{ request('entity_type') === 'user_permission' ? 'selected' : '' }}>Quyền người dùng</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                        <i class="fas fa-filter"></i>
                        <span>Lọc</span>
                    </button>
                    <a href="{{ route('audit-logs.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                        <i class="fas fa-redo"></i>
                        <span>Đặt lại</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thời gian</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người thực hiện</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Đối tượng</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Chi tiết</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="px-6 py-4">
                            @if($log->actor)
                            <div class="text-sm font-medium text-gray-900">{{ $log->actor->name }}</div>
                            <div class="text-xs text-gray-500">{{ $log->actor->email }}</div>
                            @else
                            <span class="text-sm text-gray-500">Hệ thống</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $badgeClass = match($log->action_type) {
                                    'created' => 'bg-green-100 text-green-800',
                                    'updated' => 'bg-blue-100 text-blue-800',
                                    'deleted' => 'bg-red-100 text-red-800',
                                    'assigned' => 'bg-purple-100 text-purple-800',
                                    'revoked' => 'bg-yellow-100 text-yellow-800',
                                    'unauthorized_access' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100 text-gray-800'
                                };
                            @endphp
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $badgeClass }}">{{ $log->action_type }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $log->entity_type }}</div>
                            <div class="text-xs text-gray-500">ID: {{ $log->entity_id }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($log->old_value || $log->new_value)
                            <button class="text-blue-600 hover:text-blue-900 text-sm" 
                                    onclick="toggleDetails('details-{{ $log->id }}')">
                                <i class="fas fa-eye"></i> Xem
                            </button>
                            <div id="details-{{ $log->id }}" class="hidden mt-2 p-3 bg-gray-50 rounded text-xs">
                                @if($log->old_value)
                                <div class="mb-2">
                                    <strong class="text-gray-700">Giá trị cũ:</strong>
                                    <pre class="mt-1 p-2 bg-white rounded overflow-x-auto">{{ json_encode(json_decode($log->old_value), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                                @endif
                                @if($log->new_value)
                                <div>
                                    <strong class="text-gray-700">Giá trị mới:</strong>
                                    <pre class="mt-1 p-2 bg-white rounded overflow-x-auto">{{ json_encode(json_decode($log->new_value), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                                @endif
                            </div>
                            @else
                            <span class="text-sm text-gray-500">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            {{ $log->ip_address ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">Không có nhật ký nào.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 bg-gray-50">
            {{ $logs->links() }}
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleDetails(id) {
    const element = document.getElementById(id);
    element.classList.toggle('hidden');
}
</script>
@endpush
@endsection
