@extends('layouts.app')

@section('title', 'Quyền')
@section('page-title', 'Quản lý Quyền')

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 flex flex-wrap justify-between items-center gap-3">
            <div>
                @if(!$showAll)
                    <div class="flex items-center gap-2">
                        <span class="text-xs bg-blue-50 text-blue-700 px-3 py-1.5 rounded-full font-medium border border-blue-200">
                            <i class="fas fa-filter text-blue-500 mr-1"></i> Đang hiển thị {{ $groupedPermissions->count() }} module có trên Sidebar
                        </span>
                        <a href="{{ route('permissions.index', ['show_all' => 1]) }}" class="text-xs text-blue-600 hover:text-blue-800 underline ml-2">
                            Hiển thị tất cả (bao gồm module tạm ẩn)
                        </a>
                    </div>
                @else
                    <div class="flex items-center gap-2">
                        <span class="text-xs bg-amber-50 text-amber-800 px-3 py-1.5 rounded-full font-medium border border-amber-200">
                            <i class="fas fa-exclamation-circle text-amber-500 mr-1"></i> Đang hiển thị toàn bộ {{ $groupedPermissions->count() }} module
                        </span>
                        <a href="{{ route('permissions.index') }}" class="text-xs text-blue-600 hover:text-blue-800 underline ml-2">
                            Thu gọn (chỉ hiện module trên Sidebar)
                        </a>
                    </div>
                @endif
            </div>
            @can('edit_permissions')
                <a href="{{ route('permissions.matrix', $showAll ? ['show_all' => 1] : []) }}"
                    class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                    <i class="fas fa-table"></i>
                    <span>Ma trận Quyền</span>
                </a>
            @endcan
        </div>

        @foreach($groupedPermissions as $module => $permissions)
            <div class="border-b border-gray-200 last:border-b-0">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <h5 class="text-sm font-semibold text-gray-700">
                        {{ config('permissions.modules.' . $module, ucfirst($module)) }}
                        <span class="text-xs font-mono font-normal text-gray-400 ml-1">({{ $module }})</span>
                    </h5>
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
                                        <code
                                            class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs">{{ $permission->slug }}</code>
                                    </td>
                                    <td class="px-4 py-2 text-xs text-gray-700">
                                        {{ Str::limit($permission->description ?? '-', 50) }}</td>
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