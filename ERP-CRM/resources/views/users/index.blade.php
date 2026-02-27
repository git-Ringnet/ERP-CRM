@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Quản lý Người dùng</h2>
            <p class="text-gray-600 mt-1">Quản lý vai trò và quyền của người dùng</p>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form action="{{ route('users.index') }}" method="GET" class="flex gap-4">
            <div class="flex-1">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Tìm kiếm theo tên, email..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <select name="role" 
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tất cả vai trò</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ request('role') == $role->id ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg flex items-center gap-2">
                <i class="fas fa-search"></i>
                <span>Tìm kiếm</span>
            </button>
            <a href="{{ route('users.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg flex items-center gap-2">
                <i class="fas fa-redo"></i>
                <span>Đặt lại</span>
            </a>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Người dùng</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phòng ban</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vai trò</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Số Quyền</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Trạng thái</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-blue-600 font-semibold">{{ substr($user->name, 0, 1) }}</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                    @if($user->employee_code)
                                    <div class="text-xs text-gray-400">Mã NV: {{ $user->employee_code }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $user->department ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $user->position ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->roles->count() > 0)
                            <div class="flex flex-wrap gap-1">
                                @foreach($user->roles as $role)
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                    {{ $role->name }}
                                </span>
                                @endforeach
                            </div>
                            @else
                            <span class="text-sm text-gray-400">Chưa có vai trò</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $totalPermissions = $user->getAllPermissions()->count();
                                $directPermissions = $user->directPermissions->count();
                            @endphp
                            <div class="text-sm">
                                <span class="font-semibold text-gray-900">{{ $totalPermissions }}</span>
                                <span class="text-gray-500">quyền</span>
                            </div>
                            @if($directPermissions > 0)
                            <div class="text-xs text-yellow-600">
                                ({{ $directPermissions }} trực tiếp)
                            </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($user->status === 'active')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoạt động</span>
                            @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">{{ ucfirst($user->status) }}</span>
                            @endif
                            
                            @if($user->is_locked)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 ml-1">Khóa</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                @can('view_user_roles')
                                <a href="{{ route('users.roles.show', $user->id) }}" 
                                   class="inline-flex items-center px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-lg transition-colors"
                                   title="Quản lý Vai trò">
                                    <i class="fas fa-user-tag mr-1"></i>
                                    Vai trò
                                </a>
                                @endcan
                                
                                @can('view_user_permissions')
                                <a href="{{ route('users.permissions.show', $user->id) }}" 
                                   class="inline-flex items-center px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg transition-colors"
                                   title="Quản lý Quyền">
                                    <i class="fas fa-key mr-1"></i>
                                    Quyền
                                </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">Không có người dùng nào.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 bg-gray-50">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
