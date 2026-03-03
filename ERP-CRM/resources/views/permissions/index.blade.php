@extends('layouts.app')

@section('title', 'Quyền')
@section('page-title', 'Quản lý Quyền')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
        <div></div>
        @can('edit_permissions')
        <a href="{{ route('permissions.matrix') }}" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
            <i class="fas fa-table"></i>
            <span>Ma trận Quyền</span>
        </a>
        @endcan
    </div>

    @if(session('success'))
    <div class="p-4 bg-green-50 border-l-4 border-green-500 text-green-700">
        <div class="flex items-center justify-between">
            <span class="text-sm">{{ session('success') }}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="text-green-700 hover:text-green-900">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    @endif

    @foreach($groupedPermissions as $module => $permissions)
    <div class="border-b border-gray-200 last:border-b-0">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
            <h5 class="text-sm font-semibold text-gray-700">{{ ucfirst($module) }}</h5>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tên Quyền</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Mô tả</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Hành động</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($permissions as $permission)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $permission->name }}</td>
                        <td class="px-4 py-2">
                            <code class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs">{{ $permission->slug }}</code>
                        </td>
                        <td class="px-4 py-2 text-xs text-gray-700">{{ Str::limit($permission->description ?? '-', 50) }}</td>
                        <td class="px-4 py-2 text-xs text-gray-700">{{ $permission->action }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</div>
@endsection
