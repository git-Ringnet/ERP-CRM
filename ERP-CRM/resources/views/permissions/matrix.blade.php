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
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 pb-2 mb-3 gap-2">
                    <h5 class="text-lg font-semibold text-gray-800">{{ config('permissions.modules.' . $module, ucfirst($module)) }}</h5>
                    @if($module === 'technical_tickets')
                        <button type="button" onclick="openTicketTypesModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-teal-50 hover:bg-teal-100 text-teal-700 text-xs font-semibold rounded-lg border border-teal-300 transition-colors shadow-sm">
                            <i class="fas fa-sliders-h text-teal-600"></i>
                            <span>Cài đặt phân quyền 9 Loại Ticket</span>
                        </button>
                    @endif
                </div>
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
                @if($module === 'technical_tickets')
                    <div class="mt-2 text-xs text-gray-500 flex flex-col sm:flex-row sm:items-center sm:justify-between bg-teal-50/50 p-2.5 rounded-lg border border-teal-100 gap-2">
                        <span class="flex items-center gap-2 text-teal-800">
                            <i class="fas fa-info-circle text-teal-600"></i>
                            <span>Cần phân quyền chi tiết (Leader, Team Kỹ thuật, Tất cả tài khoản, hoặc Vai trò tùy chọn) cho từng loại ticket?</span>
                        </span>
                        <button type="button" onclick="openTicketTypesModal()" class="text-teal-700 hover:text-teal-900 font-semibold underline text-xs inline-flex items-center gap-1 self-start sm:self-auto">
                            <i class="fas fa-cog"></i> Mở cấu hình 9 loại Ticket
                        </button>
                    </div>
                @endif
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

<!-- Modal Cấu hình Phân quyền theo Loại Ticket -->
<div id="ticketTypesModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background backdrop -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeTicketTypesModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-gradient-to-r from-teal-600 to-teal-700 px-6 py-4 flex items-center justify-between text-white">
                <div>
                    <h3 class="text-lg font-bold flex items-center gap-2" id="modal-title">
                        <i class="fas fa-sliders-h"></i>
                        Phân quyền Tiếp nhận & Tự nhận theo Loại Ticket
                    </h3>
                    <p class="text-xs text-teal-100 mt-0.5">Cấu hình đối tượng được phép nhìn thấy khi chưa phân công, phân công hoặc tự nhận (self-pickup) cho từng loại Ticket.</p>
                </div>
                <button type="button" onclick="closeTicketTypesModal()" class="text-teal-200 hover:text-white transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form action="{{ route('permissions.technical-tickets.update') }}" method="POST" class="p-6">
                @csrf
                
                <div class="overflow-x-auto rounded-lg border border-gray-200 mb-5 max-h-[60vh]">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="px-3 py-2.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-12">STT</th>
                                <th class="px-3 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-48">Loại Ticket</th>
                                <th class="px-3 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Đối tượng được tiếp nhận & Pickup</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @php $stt = 1; @endphp
                            @foreach($ticketWorkTypes ?? [] as $key => $label)
                                @php
                                    $config = $ticketWorkTypePermissions[$key] ?? ['scope' => 'leader', 'roles' => []];
                                    $currentScope = is_array($config) ? ($config['scope'] ?? 'leader') : $config;
                                    $currentRoles = is_array($config) ? ($config['roles'] ?? []) : [];
                                @endphp
                                <tr class="hover:bg-gray-50/70 transition-colors">
                                    <td class="px-3 py-3 text-center text-gray-500 font-medium">{{ $stt++ }}</td>
                                    <td class="px-3 py-3">
                                        <div class="font-semibold text-gray-900 text-sm">{{ $label }}</div>
                                        <div class="text-xs text-gray-400 font-mono mt-0.5">{{ $key }}</div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="space-y-2">
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                                <label class="flex items-center p-2 rounded-lg border cursor-pointer text-xs transition-colors {{ $currentScope === 'leader' ? 'bg-amber-50 border-amber-300 text-amber-900 font-semibold' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}" id="label_leader_{{ $key }}">
                                                    <input type="radio" name="work_type_permissions[{{ $key }}][scope]" value="leader"
                                                           {{ $currentScope === 'leader' ? 'checked' : '' }}
                                                           onchange="toggleCustomRoles('{{ $key }}', 'leader')"
                                                           class="text-amber-600 focus:ring-amber-500 h-3.5 w-3.5 border-gray-300 mr-2">
                                                    <span>Chỉ Lead Kỹ thuật</span>
                                                </label>

                                                <label class="flex items-center p-2 rounded-lg border cursor-pointer text-xs transition-colors {{ ($currentScope === 'all' || $currentScope === 'tech_all') ? 'bg-teal-50 border-teal-300 text-teal-900 font-semibold' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}" id="label_all_{{ $key }}">
                                                    <input type="radio" name="work_type_permissions[{{ $key }}][scope]" value="all"
                                                           {{ ($currentScope === 'all' || $currentScope === 'tech_all') ? 'checked' : '' }}
                                                           onchange="toggleCustomRoles('{{ $key }}', 'all')"
                                                           class="text-teal-600 focus:ring-teal-500 h-3.5 w-3.5 border-gray-300 mr-2">
                                                    <span>Cả Team Kỹ thuật</span>
                                                </label>

                                                <label class="flex items-center p-2 rounded-lg border cursor-pointer text-xs transition-colors {{ $currentScope === 'everyone' ? 'bg-blue-50 border-blue-300 text-blue-900 font-semibold' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}" id="label_everyone_{{ $key }}">
                                                    <input type="radio" name="work_type_permissions[{{ $key }}][scope]" value="everyone"
                                                           {{ $currentScope === 'everyone' ? 'checked' : '' }}
                                                           onchange="toggleCustomRoles('{{ $key }}', 'everyone')"
                                                           class="text-blue-600 focus:ring-blue-500 h-3.5 w-3.5 border-gray-300 mr-2">
                                                    <span>Tất cả tài khoản</span>
                                                </label>
                                            </div>

                                            <div class="flex items-center">
                                                <label class="inline-flex items-center text-xs text-gray-600 cursor-pointer">
                                                    <input type="radio" name="work_type_permissions[{{ $key }}][scope]" value="custom"
                                                           {{ $currentScope === 'custom' ? 'checked' : '' }}
                                                           onchange="toggleCustomRoles('{{ $key }}', 'custom')"
                                                           class="text-purple-600 focus:ring-purple-500 h-3.5 w-3.5 border-gray-300 mr-1.5">
                                                    <span class="font-medium text-purple-700">Tùy chỉnh vai trò (Custom Roles):</span>
                                                </label>
                                            </div>

                                            <div id="custom_roles_{{ $key }}" class="{{ $currentScope === 'custom' ? '' : 'hidden' }} p-2.5 bg-purple-50/60 rounded-lg border border-purple-200">
                                                <p class="text-xs text-purple-800 font-medium mb-1.5">Chọn các vai trò được phép tiếp nhận & tự nhận loại ticket này:</p>
                                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                                                    @foreach($roles as $role)
                                                        @php
                                                            $isRoleChecked = in_array($role->id, (array)$currentRoles) || in_array($role->slug, (array)$currentRoles);
                                                        @endphp
                                                        <label class="inline-flex items-center text-xs text-gray-700 cursor-pointer">
                                                            <input type="checkbox" name="work_type_permissions[{{ $key }}][roles][]" value="{{ $role->id }}"
                                                                   {{ $isRoleChecked ? 'checked' : '' }}
                                                                   class="rounded border-gray-300 text-purple-600 focus:ring-purple-500 h-3.5 w-3.5 mr-1.5">
                                                            <span>{{ $role->name }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                    <p class="text-xs text-gray-500">
                        <i class="fas fa-info-circle mr-1 text-teal-600"></i>
                        Cấu hình được lưu và áp dụng tức thời cho toàn bộ hệ thống.
                    </p>
                    <div class="flex gap-2">
                        <button type="button" onclick="closeTicketTypesModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-lg transition-colors">
                            Đóng
                        </button>
                        <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm flex items-center gap-1.5">
                            <i class="fas fa-save"></i>
                            <span>Lưu cấu hình Loại Ticket</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
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

function openTicketTypesModal() {
    document.getElementById('ticketTypesModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeTicketTypesModal() {
    document.getElementById('ticketTypesModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

function toggleCustomRoles(key, scope) {
    const customRolesDiv = document.getElementById('custom_roles_' + key);
    if (customRolesDiv) {
        if (scope === 'custom') {
            customRolesDiv.classList.remove('hidden');
        } else {
            customRolesDiv.classList.add('hidden');
        }
    }
}
</script>
@endpush
@endsection
