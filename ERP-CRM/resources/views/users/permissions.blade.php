@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Quản lý Quyền Trực tiếp: {{ $user->name }}</h2>
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
        <!-- Current Direct Permissions -->
        <div class="bg-white rounded-lg shadow">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-semibold text-gray-800">Quyền Trực tiếp Hiện tại</h5>
            </div>
            <div class="p-6">
                @if($user->directPermissions->count() > 0)
                <div class="space-y-3">
                    @foreach($user->directPermissions as $permission)
                    <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div>
                            <div class="font-semibold text-gray-900">{{ $permission->name }}</div>
                            <div class="text-xs text-gray-500">{{ $permission->slug }}</div>
                            <div class="text-xs text-gray-500 mt-1">Module: {{ $permission->module }}</div>
                        </div>
                        @can('revoke_user_permissions')
                        <form action="{{ route('users.permissions.revoke', [$user->id, $permission->id]) }}" method="POST" 
                              onsubmit="return confirm('Bạn có chắc chắn muốn gỡ bỏ quyền này?');">
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
                <p class="text-gray-500">Người dùng chưa có quyền trực tiếp nào.</p>
                @endif
            </div>
        </div>

        <!-- Assign New Permission -->
        <div class="bg-white rounded-lg shadow">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-semibold text-gray-800">Gán Quyền Mới</h5>
            </div>
            <div class="p-6">
                @can('assign_user_permissions')
                <form action="{{ route('users.permissions.assign', $user->id) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="permission_id" class="block text-sm font-medium text-gray-700 mb-2">Chọn Quyền</label>
                        <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('permission_id') border-red-500 @enderror" 
                                id="permission_id" name="permission_id" required>
                            <option value="">-- Chọn quyền --</option>
                            @foreach($availablePermissions as $module => $permissions)
                            <optgroup label="{{ ucfirst($module) }}" class="font-semibold">
                                @foreach($permissions as $permission)
                                <option value="{{ $permission->id }}" {{ old('permission_id') == $permission->id ? 'selected' : '' }}>
                                    {{ $permission->name }}
                                </option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                        @error('permission_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                        <i class="fas fa-plus"></i>
                        <span>Gán Quyền</span>
                    </button>
                </form>
                @else
                <p class="text-gray-500">Bạn không có quyền gán quyền trực tiếp.</p>
                @endcan
            </div>
        </div>
    </div>

    <!-- Back to User Roles -->
    <div class="mt-6">
        <a href="{{ route('users.roles.show', $user->id) }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left"></i>
            <span>Quay lại Quản lý Vai trò</span>
        </a>
    </div>
</div>
@endsection
