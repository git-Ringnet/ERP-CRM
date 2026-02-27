@extends('layouts.app')

@section('content')
<div class="p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Chỉnh sửa Vai trò: {{ $role->name }}</h2>

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('roles.update', $role->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Tên Vai trò <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror" 
                       id="name" name="name" value="{{ old('name', $role->name) }}" required>
                @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Mô tả</label>
                <textarea class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror" 
                          id="description" name="description" rows="3">{{ old('description', $role->description) }}</textarea>
                @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
                <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('status') border-red-500 @enderror" 
                        id="status" name="status">
                    <option value="active" {{ old('status', $role->status) === 'active' ? 'selected' : '' }}>Hoạt động</option>
                    <option value="inactive" {{ old('status', $role->status) === 'inactive' ? 'selected' : '' }}>Không hoạt động</option>
                </select>
                @error('status')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Quyền hiện tại</label>
                <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                    @if($role->permissions->count() > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($role->permissions as $permission)
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ $permission->name }}</span>
                        @endforeach
                    </div>
                    @else
                    <p class="text-gray-500 text-sm">Chưa có quyền nào được gán.</p>
                    @endif
                </div>
                <p class="mt-2 text-sm text-gray-500">
                    Để quản lý quyền, vui lòng sử dụng <a href="{{ route('permissions.matrix') }}" class="text-blue-600 hover:underline">Ma trận Quyền</a>.
                </p>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    <span>Cập nhật</span>
                </button>
                <a href="{{ route('roles.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    <span>Hủy</span>
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
