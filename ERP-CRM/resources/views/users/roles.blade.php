@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Quản lý Vai trò: {{ $user->name }}</h2>
        <p class="text-gray-600 mt-1">Email: {{ $user->email }}</p>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 flex justify-between items-center">
        <span>{{ session('success') }}</span>
        <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900">
            <i class="fas fa-times"></i>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 flex justify-between items-center">
        <span>{{ session('error') }}</span>
        <button onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-900">
            <i class="fas fa-times"></i>
        </button>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Current Roles -->
        <div class="bg-white rounded-lg shadow">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-semibold text-gray-800">Vai trò Hiện tại</h5>
            </div>
            <div class="p-6">
                @if($user->roles->count() > 0)
                <div class="space-y-3">
                    @foreach($user->roles as $role)
                    <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div>
                            <div class="font-semibold text-gray-900">{{ $role->name }}</div>
                            <div class="text-sm text-gray-600">{{ $role->description }}</div>
                        </div>
                        @can('revoke_user_roles')
                        <form action="{{ route('users.roles.revoke', [$user->id, $role->id]) }}" method="POST" 
                              onsubmit="return confirm('Bạn có chắc chắn muốn gỡ bỏ vai trò này?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900 px-3 py-1 rounded hover:bg-red-50">
                                <i class="fas fa-times"></i> Gỡ bỏ
                            </button>
                        </form>
                        @endcan
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-gray-500">Người dùng chưa có vai trò nào.</p>
                @endif
            </div>
        </div>

        <!-- Assign New Role -->
        <div class="bg-white rounded-lg shadow">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-semibold text-gray-800">Gán Vai trò Mới</h5>
            </div>
            <div class="p-6">
                @can('assign_user_roles')
                <form action="{{ route('users.roles.assign', $user->id) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="role_id" class="block text-sm font-medium text-gray-700 mb-2">Chọn Vai trò</label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('role_id') border-red-500 @enderror" 
                                id="role_id" name="role_id" required>
                            <option value="">-- Chọn vai trò --</option>
                            @foreach($availableRoles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('role_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                        <i class="fas fa-plus"></i>
                        <span>Gán Vai trò</span>
                    </button>
                </form>
                @else
                <p class="text-gray-500">Bạn không có quyền gán vai trò.</p>
                @endcan
            </div>

            <!-- Sync Roles -->
            <div class="border-t border-gray-200 p-6">
                <h6 class="text-md font-semibold text-gray-800 mb-3">Đồng bộ Vai trò</h6>
                @can('assign_user_roles')
                <form action="{{ route('users.roles.sync', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="space-y-2 mb-4">
                        @foreach($availableRoles as $role)
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                   name="role_ids[]" 
                                   value="{{ $role->id }}" 
                                   {{ $user->roles->contains($role->id) ? 'checked' : '' }}>
                            <span class="ml-2 text-gray-700">{{ $role->name }}</span>
                        </label>
                        @endforeach
                    </div>
                    <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                        <i class="fas fa-sync"></i>
                        <span>Đồng bộ</span>
                    </button>
                </form>
                @else
                <p class="text-gray-500">Bạn không có quyền đồng bộ vai trò.</p>
                @endcan
            </div>
        </div>
    </div>

    <!-- Effective Permissions -->
    <div class="mt-6 bg-white rounded-lg shadow">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <h5 class="text-lg font-semibold text-gray-800">Quyền Hiệu lực (từ Vai trò)</h5>
        </div>
        <div class="p-6">
            @php
                $effectivePermissions = $user->getAllPermissions();
                $groupedPermissions = $effectivePermissions->groupBy('module');
            @endphp

            @if($effectivePermissions->count() > 0)
            <div class="space-y-4">
                @foreach($groupedPermissions as $module => $permissions)
                <div>
                    <h6 class="text-md font-semibold text-blue-600 mb-2">{{ ucfirst($module) }}</h6>
                    <div class="flex flex-wrap gap-2">
                        @foreach($permissions as $permission)
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ $permission->name }}</span>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-500">Người dùng chưa có quyền nào.</p>
            @endif
        </div>
    </div>

    <!-- Direct Permissions -->
    <div class="mt-6 bg-white rounded-lg shadow">
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h5 class="text-lg font-semibold text-gray-800">Quyền Trực tiếp</h5>
            <a href="{{ route('users.permissions.show', $user->id) }}" class="text-blue-600 hover:text-blue-800 text-sm flex items-center gap-1">
                <i class="fas fa-cog"></i>
                <span>Quản lý Quyền Trực tiếp</span>
            </a>
        </div>
        <div class="p-6">
            @if($user->directPermissions->count() > 0)
            <div class="flex flex-wrap gap-2">
                @foreach($user->directPermissions as $permission)
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">{{ $permission->name }}</span>
                @endforeach
            </div>
            @else
            <p class="text-gray-500">Người dùng chưa có quyền trực tiếp nào.</p>
            @endif
        </div>
    </div>
</div>
@endsection
