@extends('layouts.app')

@section('title', 'Nhật ký hoạt động')
@section('page-title', 'Nhật ký hoạt động')

@section('content')
<div class="flex flex-col h-full space-y-4">
    <!-- Filters & Search -->
    <div class="bg-white p-4 rounded-lg shadow-sm shrink-0">
        <form method="GET" action="{{ route('activity-logs.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- User filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Người dùng</label>
                    <select name="user_id" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                        <option value="">Tất cả</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hành động</label>
                    <select name="action" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                        <option value="">Tất cả</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                {{ ucfirst($action) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Module filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Module</label>
                    <select name="subject_type" class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                        <option value="">Tất cả</option>
                        @foreach($subjectTypes as $type)
                            <option value="App\Models\{{ $type }}" {{ request('subject_type') == "App\\Models\\{$type}" ? 'selected' : '' }}>
                                {{ $type }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Tìm trong mô tả..."
                           class="w-full rounded-lg border-gray-300 focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-filter mr-2"></i>Lọc
                </button>
                <a href="{{ route('activity-logs.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-redo mr-2"></i>Đặt lại
                </a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-lg shadow-sm flex-1 overflow-hidden flex flex-col">
        <div class="overflow-x-auto flex-1">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-40">Thời gian</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-36">Người dùng</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">Hành động</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">Module</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mô tả</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($logs as $log)
                        @php
                            $actionConfig = [
                                'created' => ['icon' => 'plus-circle', 'color' => 'text-green-600', 'bg' => 'bg-green-100', 'label' => 'Tạo'],
                                'updated' => ['icon' => 'edit', 'color' => 'text-blue-600', 'bg' => 'bg-blue-100', 'label' => 'Sửa'],
                                'deleted' => ['icon' => 'trash', 'color' => 'text-red-600', 'bg' => 'bg-red-100', 'label' => 'Xóa'],
                                'approved' => ['icon' => 'check-circle', 'color' => 'text-purple-600', 'bg' => 'bg-purple-100', 'label' => 'Duyệt'],
                                'login' => ['icon' => 'sign-in-alt', 'color' => 'text-yellow-600', 'bg' => 'bg-yellow-100', 'label' => 'Login'],
                                'logout' => ['icon' => 'sign-out-alt', 'color' => 'text-gray-600', 'bg' => 'bg-gray-100', 'label' => 'Logout'],
                            ];
                            $config = $actionConfig[$log->action] ?? ['icon' => 'circle', 'color' => 'text-gray-600', 'bg' => 'bg-gray-100', 'label' => ucfirst($log->action)];
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <div class="font-medium">{{ $log->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $log->created_at->format('H:i:s') }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-gray-900">{{ $log->user_name ?? 'N/A' }}</div>
                                @if($log->user)
                                    <div class="text-xs text-gray-500">{{ $log->user->employee_code ?? 'N/A' }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $config['bg'] }} {{ $config['color'] }}">
                                    <i class="fas fa-{{ $config['icon'] }} mr-1"></i>{{ $config['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ $log->subject_type ? class_basename($log->subject_type) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ $log->description }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                {{ $log->ip_address ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                                <p>Không có nhật ký nào</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
