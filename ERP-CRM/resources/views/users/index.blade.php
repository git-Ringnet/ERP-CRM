@extends('layouts.app')

@section('title', 'Người dùng')
@section('page-title', 'Quản lý Người dùng')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200">
        <form action="{{ route('users.index') }}" method="GET" class="flex gap-3">
            <!-- Search -->
            <div class="relative flex-1">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="Tìm kiếm theo tên, email..." 
                       class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
            
            <!-- Filter by Role -->
            <select name="role" 
                    class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="">Tất cả vai trò</option>
                @foreach($roles as $role)
                <option value="{{ $role->id }}" {{ request('role') == $role->id ? 'selected' : '' }}>
                    {{ $role->name }}
                </option>
                @endforeach
            </select>
            
            <!-- Buttons -->
            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                <i class="fas fa-search"></i>
                <span>Tìm</span>
            </button>
            <a href="{{ route('users.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                <i class="fas fa-redo"></i>
                <span>Đặt lại</span>
            </a>
        </form>
    </div>

    <!-- Users Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Người dùng</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phòng ban</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vai trò</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quyền</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Hành động</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($users as $user)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-8 w-8">
                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-blue-600 font-semibold text-sm">{{ substr($user->name, 0, 1) }}</span>
                                </div>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm text-gray-900">{{ $user->department ?? '-' }}</div>
                        <div class="text-xs text-gray-500">{{ $user->position ?? '-' }}</div>
                    </td>
                    <td class="px-4 py-3">
                        @if($user->roles->count() > 0)
                        <div class="flex flex-wrap gap-1">
                            @foreach($user->roles as $role)
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                {{ $role->name }}
                            </span>
                            @endforeach
                        </div>
                        @else
                        <span class="text-xs text-gray-400">Chưa có</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $totalPermissions = $user->getAllPermissions()->count();
                        @endphp
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ $totalPermissions }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($user->status === 'active')
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoạt động</span>
                        @else
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">{{ ucfirst($user->status) }}</span>
                        @endif
                        
                        @if($user->is_locked)
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 ml-1">Khóa</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-center gap-3">
                            @can('view_user_roles')
                            <a href="{{ route('users.roles.show', $user->id) }}" 
                               class="text-purple-600 hover:text-purple-900 text-sm"
                               title="Quản lý Vai trò">
                                <i class="fas fa-user-tag"></i>
                            </a>
                            @endcan
                            
                            @can('view_user_permissions')
                            <a href="{{ route('users.permissions.show', $user->id) }}" 
                               class="text-indigo-600 hover:text-indigo-900 text-sm"
                               title="Quản lý Quyền">
                                <i class="fas fa-key"></i>
                            </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-users text-4xl mb-2 text-gray-300"></i>
                        <p>Không có dữ liệu</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($users->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
