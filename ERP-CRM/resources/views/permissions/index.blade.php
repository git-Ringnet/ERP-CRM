@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Quản lý Quyền</h2>
        @can('edit_permissions')
        <a href="{{ route('permissions.matrix') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
            <i class="fas fa-table"></i>
            <span>Ma trận Quyền</span>
        </a>
        @endcan
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 flex justify-between items-center">
        <span>{{ session('success') }}</span>
        <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900">
            <i class="fas fa-times"></i>
        </button>
    </div>
    @endif

    @foreach($groupedPermissions as $module => $permissions)
    <div class="bg-white rounded-lg shadow mb-4 overflow-hidden">
        <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
            <h5 class="text-lg font-semibold text-gray-800">{{ ucfirst($module) }}</h5>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên Quyền</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mô tả</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hành động</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($permissions as $permission)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $permission->name }}</td>
                        <td class="px-6 py-4">
                            <code class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-sm">{{ $permission->slug }}</code>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $permission->description ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $permission->action }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</div>
@endsection
