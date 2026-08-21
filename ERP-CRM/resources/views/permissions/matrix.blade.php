@extends('layouts.app')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Ma trận Quyền</h2>
        <p class="text-gray-600 mt-1">Quản lý quyền cho từng vai trò</p>
    </div>

    <div class="bg-white rounded-lg shadow">
        <form action="{{ route('permissions.matrix.update') }}" method="POST" id="matrixForm" class="p-6">
            @csrf
            <input type="hidden" name="permissions_json" id="permissionsJson" value="">

            <div class="mb-6 max-w-md">
                <label for="moduleSearch" class="block text-sm font-medium text-gray-700 mb-1">Tìm module</label>
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="search" id="moduleSearch"
                        class="w-full rounded-lg border-gray-300 pl-10 pr-4 focus:border-blue-500 focus:ring-blue-500"
                        placeholder="Ví dụ: Technical, Bán hàng, Kho hàng..." autocomplete="off">
                </div>
            </div>

            <div id="noModuleResults" class="hidden rounded-lg bg-yellow-50 px-4 py-3 text-sm text-yellow-800 mb-6">
                Không tìm thấy module phù hợp.
            </div>

            @foreach($groupedPermissions as $module => $permissions)
            <div class="mb-6 permission-module" data-module-search="{{ $module }} {{ config('permissions.modules.' . $module, ucfirst($module)) }}">
                <h5 class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-2 mb-3">{{ config('permissions.modules.' . $module, ucfirst($module)) }}</h5>
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 border-b border-r border-gray-200" style="width: 200px;">Quyền</th>
                                @foreach($roles as $role)
                                <th class="px-4 py-2 text-center text-xs font-medium text-gray-700 border-b border-r border-gray-200" style="width: 100px;">
                                    <div>{{ $role->name }}</div>
                                    <a href="#" class="text-blue-600 hover:underline text-xs toggle-column" data-role-id="{{ $role->id }}" data-module="{{ $module }}">
                                        Chọn tất cả
                                    </a>
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($permissions as $permission)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 border-b border-r border-gray-200">
                                    <div class="font-medium text-gray-900">{{ $permission->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $permission->slug }}</div>
                                    <a href="#" class="text-blue-600 hover:underline text-xs toggle-row" data-permission-id="{{ $permission->id }}">
                                        Chọn tất cả
                                    </a>
                                </td>
                                @foreach($roles as $role)
                                <td class="px-4 py-2 text-center border-b border-r border-gray-200">
                                    <input type="checkbox" 
                                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 permission-checkbox" 
                                           value="{{ $permission->id }}"
                                           data-role-id="{{ $role->id }}"
                                           data-permission-id="{{ $permission->id }}"
                                           data-module="{{ $module }}"
                                           {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}>
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach

            @can('edit_permissions')
            <div class="flex gap-3 pt-4 border-t border-gray-200">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                    <i class="fas fa-save"></i>
                    <span>Lưu Thay đổi</span>
                </button>
                <a href="{{ route('permissions.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                    <i class="fas fa-times"></i>
                    <span>Hủy</span>
                </a>
            </div>
            @endcan
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const matrixForm = document.getElementById('matrixForm');
    const permissionsJson = document.getElementById('permissionsJson');
    const moduleSearch = document.getElementById('moduleSearch');
    const noModuleResults = document.getElementById('noModuleResults');

    moduleSearch.addEventListener('input', function() {
        const keyword = this.value.trim().toLocaleLowerCase('vi-VN');
        let visibleModules = 0;

        document.querySelectorAll('.permission-module').forEach(function(module) {
            const matches = !keyword || module.dataset.moduleSearch.toLocaleLowerCase('vi-VN').includes(keyword);
            module.classList.toggle('hidden', !matches);

            if (matches) {
                visibleModules++;
            }
        });

        noModuleResults.classList.toggle('hidden', visibleModules > 0);
    });

    // Toggle all checkboxes in a row
    document.querySelectorAll('.toggle-row').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const permissionId = this.dataset.permissionId;
            const checkboxes = document.querySelectorAll(`input[data-permission-id="${permissionId}"]`);
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = !allChecked;
            });
        });
    });

    // Toggle all checkboxes in a column
    document.querySelectorAll('.toggle-column').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const roleId = this.dataset.roleId;
            const module = this.dataset.module;
            const checkboxes = document.querySelectorAll(`input[data-role-id="${roleId}"][data-module="${module}"]`);
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = !allChecked;
            });
        });
    });

    // Send the whole matrix in one field. This avoids PHP's max_input_vars
    // limit truncating a large checkbox matrix during submission.
    matrixForm.addEventListener('submit', function() {
        const permissions = {};

        document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
            const roleId = checkbox.dataset.roleId;

            if (!Object.prototype.hasOwnProperty.call(permissions, roleId)) {
                permissions[roleId] = [];
            }

            if (checkbox.checked) {
                permissions[roleId].push(Number(checkbox.value));
            }
        });

        permissionsJson.value = JSON.stringify(permissions);
    });
});
</script>
@endpush
@endsection
