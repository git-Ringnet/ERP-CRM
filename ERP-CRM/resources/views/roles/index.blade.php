@extends('layouts.app')

@section('title', 'Vai trò')
@section('page-title', 'Quản lý Vai trò')

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <div></div>
            @can('create_roles')
                <a href="{{ route('roles.create') }}"
                    class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    <span>Tạo mới</span>
                </a>
            @endcan
        </div>



        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên Vai trò</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mô tả</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Số Quyền</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hành động</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($roles as $role)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-sm text-gray-900">{{ $role->name }}</div>
                                <div class="text-xs text-gray-500">{{ $role->slug }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ Str::limit($role->description ?? '-', 50) }}</td>
                            <td class="px-4 py-3">
                                @if($role->status === 'active')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoạt
                                        động</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Không hoạt
                                        động</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ $role->permissions->count() }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-3">
                                    @can('edit_roles')
                                        <a href="{{ route('roles.edit', $role->id) }}"
                                            class="text-yellow-600 hover:text-yellow-900 text-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endcan

                                    @can('delete_roles')
                                        <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Bạn có chắc chắn muốn xóa vai trò này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 text-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                <i class="fas fa-user-tag text-4xl mb-2 text-gray-300"></i>
                                <p>Không có dữ liệu</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($roles->hasPages())
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $roles->links() }}
            </div>
        @endif
    </div>
@endsection