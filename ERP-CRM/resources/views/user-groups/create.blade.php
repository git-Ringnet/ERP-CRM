@extends('layouts.app')

@section('title', 'Thêm Nhóm Người Dùng Mới')
@section('page-title', 'Tạo Nhóm Người Dùng')

@section('content')
    <div class="">
        <div class="bg-white rounded-xl shadow-xs border border-gray-200 overflow-hidden">
            <div class="p-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-users text-primary"></i> Thông tin Nhóm Người Dùng / Team
                </h2>
                <a href="{{ route('user-groups.index') }}" class="text-xs text-gray-600 hover:text-gray-900 font-medium">
                    <i class="fas fa-arrow-left mr-1"></i> Quay lại
                </a>
            </div>

            <form action="{{ route('user-groups.store') }}" method="POST" class="p-6 space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Tên nhóm -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Tên nhóm <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            placeholder="Ví dụ: Kỹ thuật Network & Security"
                            class="w-full text-sm rounded-lg border-gray-300 shadow-xs focus:border-primary focus:ring-primary">
                        @error('name')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Mã nhóm -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Mã nhóm (Mã viết tắt)
                        </label>
                        <input type="text" name="code" value="{{ old('code') }}" placeholder="Ví dụ: TECH_NETSEC"
                            class="w-full text-sm rounded-lg border-gray-300 shadow-xs focus:border-primary focus:ring-primary uppercase">
                        @error('code')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Trưởng nhóm (Lead) - Searchable Select -->
                    <div class="relative">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Trưởng nhóm / Lead phụ trách
                        </label>
                        @php
                            $selectedLeaderId = old('leader_id');
                            $selectedLeader = $users->firstWhere('id', $selectedLeaderId);
                            $selectedLeaderDisplay = $selectedLeader ? $selectedLeader->name . ' (' . $selectedLeader->email . ')' . ($selectedLeader->department ? ' - ' . $selectedLeader->department : '') : '';
                        @endphp
                        <div class="relative" id="leaderSearchableWrapper">
                            <div class="relative flex items-center">
                                <input type="text" id="leader_search_input"
                                    placeholder="-- Tìm và chọn Trưởng nhóm (Team Lead) --"
                                    value="{{ $selectedLeaderDisplay }}" autocomplete="off"
                                    class="w-full text-sm rounded-lg border-gray-300 shadow-xs focus:border-primary focus:ring-primary pl-9 pr-8 cursor-pointer"
                                    onclick="toggleLeaderDropdown()" oninput="filterLeaderDropdown(this.value)">
                                <div class="absolute left-3 text-gray-400 pointer-events-none text-xs">
                                    <i class="fas fa-search"></i>
                                </div>
                                <button type="button" id="leader_clear_btn" onclick="clearLeaderSelection(event)"
                                    class="absolute right-2.5 text-gray-400 hover:text-red-500 text-xs {{ empty($selectedLeaderId) ? 'hidden' : '' }}"
                                    title="Bỏ chọn">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            </div>
                            <input type="hidden" name="leader_id" id="leader_id" value="{{ $selectedLeaderId }}">

                            <!-- Dropdown list -->
                            <div id="leader_dropdown_menu"
                                class="hidden absolute left-0 right-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl z-50 max-h-64 overflow-y-auto divide-y divide-gray-100">
                                <div
                                    class="p-2 text-[11px] text-gray-500 bg-gray-50 flex items-center justify-between sticky top-0 z-10 border-b border-gray-200">
                                    <span class="font-medium">Danh sách nhân viên ({{ $users->count() }})</span>
                                    <button type="button" onclick="clearLeaderSelection(event)"
                                        class="text-primary hover:underline font-semibold text-[11px]">
                                        <i class="fas fa-times mr-0.5"></i> Bỏ chọn
                                    </button>
                                </div>
                                <div id="leader_options_list">
                                    @foreach($users as $user)
                                        @php
                                            $deptStr = $user->department ? $user->department : '';
                                            $roleNames = $user->roles->pluck('name')->join(', ');
                                            $userSearchText = mb_strtolower($user->name . ' ' . $user->email . ' ' . $deptStr . ' ' . $roleNames);
                                            $displayText = $user->name . ' (' . $user->email . ')' . ($deptStr ? ' - ' . $deptStr : '');
                                        @endphp
                                        <div class="leader-option px-3 py-2 hover:bg-blue-50 cursor-pointer flex items-center justify-between transition-colors text-xs {{ $selectedLeaderId == $user->id ? 'bg-blue-50 font-semibold' : '' }}"
                                            data-id="{{ $user->id }}" data-name="{{ $user->name }}"
                                            data-email="{{ $user->email }}" data-display="{{ $displayText }}"
                                            data-search="{{ $userSearchText }}"
                                            onclick="selectLeader('{{ $user->id }}', '{{ addslashes($displayText) }}')">
                                            <div class="flex-1 min-w-0 pr-2">
                                                <div class="font-semibold text-gray-900 truncate flex items-center gap-1.5">
                                                    <span>{{ $user->name }}</span>
                                                    @if($user->roles->isNotEmpty())
                                                        <span
                                                            class="text-[9px] font-normal px-1.5 py-0.2 bg-gray-100 text-gray-600 rounded">
                                                            {{ $user->roles->first()->name }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-[11px] text-gray-500 truncate">{{ $user->email }}</div>
                                            </div>
                                            @if($deptStr)
                                                <span
                                                    class="text-[10px] font-medium px-1.5 py-0.5 bg-blue-50 text-blue-700 rounded shrink-0 border border-blue-100">{{ $deptStr }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                <div id="leader_no_results" class="hidden p-4 text-center text-xs text-gray-500">
                                    <i class="fas fa-search text-gray-400 mb-1 block text-sm"></i>
                                    Không tìm thấy nhân viên phù hợp
                                </div>
                            </div>
                        </div>
                        @error('leader_id')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Bộ phận / Phòng ban -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Bộ phận / Phòng ban
                        </label>
                        <input type="text" name="department" value="{{ old('department') }}"
                            placeholder="Ví dụ: Kỹ thuật, Kinh doanh, Dự án..."
                            class="w-full text-sm rounded-lg border-gray-300 shadow-xs focus:border-primary focus:ring-primary">
                        @error('department')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Trạng thái -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Trạng thái <span class="text-red-500">*</span>
                        </label>
                        <select name="status" required
                            class="w-full text-sm rounded-lg border-gray-300 shadow-xs focus:border-primary focus:ring-primary">
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Hoạt động
                            </option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Tạm dừng</option>
                        </select>
                    </div>
                </div>

                <!-- Mô tả -->
                <div>
                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Mô tả phạm vi / chuyên môn nhóm
                    </label>
                    <textarea name="description" rows="2" placeholder="Ghi chú về chuyên môn, phân nhiệm của nhóm..."
                        class="w-full text-sm rounded-lg border-gray-300 shadow-xs focus:border-primary focus:ring-primary">{{ old('description') }}</textarea>
                </div>

                <!-- Chọn Thành viên trong nhóm -->
                <div class="border-t border-gray-100 pt-5">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h3 class="text-xs font-bold text-gray-800 uppercase tracking-wider">Danh sách Thành viên nhóm
                            </h3>
                            <p class="text-[11px] text-gray-500">Tích chọn các nhân viên thuộc nhóm này (Khi chọn Lead phụ
                                trách ticket, Lead có thể assign các thành viên này vào ticket).</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" id="memberSearch" placeholder="Lọc nhanh nhân viên..."
                                class="text-xs rounded-lg border-gray-300 px-2.5 py-1" onkeyup="filterMembers(this.value)">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 max-h-72 overflow-y-auto p-3 border border-gray-200 rounded-lg bg-gray-50/50"
                        id="memberListContainer">
                        @foreach($users as $user)
                            <label
                                class="flex items-start gap-2.5 p-2 bg-white rounded-lg border border-gray-200 hover:border-primary cursor-pointer transition-all shadow-2xs member-item"
                                data-name="{{ mb_strtolower($user->name) }}" data-email="{{ mb_strtolower($user->email) }}">
                                <input type="checkbox" name="members[]" value="{{ $user->id }}" {{ is_array(old('members')) && in_array($user->id, old('members')) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-primary focus:ring-primary mt-0.5">
                                <div class="text-xs">
                                    <div class="font-semibold text-gray-900">{{ $user->name }}</div>
                                    <div class="text-[10px] text-gray-500">{{ $user->email }}</div>
                                    @if($user->department)
                                        <span class="inline-block text-[10px] text-gray-400">{{ $user->department }}</span>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <a href="{{ route('user-groups.index') }}"
                        class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                        Hủy
                    </a>
                    <button type="submit"
                        class="px-5 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-bold rounded-lg shadow-xs transition-colors">
                        <i class="fas fa-check mr-1.5"></i> Tạo Nhóm
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function removeAccents(str) {
            if (!str) return '';
            return str.normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/đ/g, 'd').replace(/Đ/g, 'D')
                .toLowerCase();
        }

        function openLeaderDropdown() {
            const menu = document.getElementById('leader_dropdown_menu');
            if (menu) menu.classList.remove('hidden');
        }

        function closeLeaderDropdown() {
            const menu = document.getElementById('leader_dropdown_menu');
            if (menu) menu.classList.add('hidden');
        }

        function toggleLeaderDropdown() {
            const menu = document.getElementById('leader_dropdown_menu');
            if (menu) {
                if (menu.classList.contains('hidden')) {
                    openLeaderDropdown();
                    const input = document.getElementById('leader_search_input');
                    filterLeaderDropdown(input ? input.value : '');
                } else {
                    closeLeaderDropdown();
                }
            }
        }

        function filterLeaderDropdown(query) {
            openLeaderDropdown();
            const q = removeAccents(query.trim());
            const options = document.querySelectorAll('#leader_options_list .leader-option');
            let visibleCount = 0;

            options.forEach(opt => {
                const searchData = removeAccents(opt.getAttribute('data-search') || '');
                if (!q || searchData.includes(q)) {
                    opt.style.display = 'flex';
                    visibleCount++;
                } else {
                    opt.style.display = 'none';
                }
            });

            const noResults = document.getElementById('leader_no_results');
            if (noResults) {
                noResults.classList.toggle('hidden', visibleCount > 0);
            }
        }

        function selectLeader(id, displayText) {
            const input = document.getElementById('leader_search_input');
            const hiddenInput = document.getElementById('leader_id');
            const clearBtn = document.getElementById('leader_clear_btn');

            if (input) input.value = displayText;
            if (hiddenInput) hiddenInput.value = id;
            if (clearBtn) clearBtn.classList.remove('hidden');

            document.querySelectorAll('#leader_options_list .leader-option').forEach(opt => {
                if (opt.getAttribute('data-id') == id) {
                    opt.classList.add('bg-blue-50', 'font-semibold');
                } else {
                    opt.classList.remove('bg-blue-50', 'font-semibold');
                }
            });

            closeLeaderDropdown();
        }

        function clearLeaderSelection(e) {
            if (e) {
                e.stopPropagation();
                e.preventDefault();
            }
            const input = document.getElementById('leader_search_input');
            const hiddenInput = document.getElementById('leader_id');
            const clearBtn = document.getElementById('leader_clear_btn');

            if (input) input.value = '';
            if (hiddenInput) hiddenInput.value = '';
            if (clearBtn) clearBtn.classList.add('hidden');

            document.querySelectorAll('#leader_options_list .leader-option').forEach(opt => {
                opt.classList.remove('bg-blue-50', 'font-semibold');
                opt.style.display = 'flex';
            });

            const noResults = document.getElementById('leader_no_results');
            if (noResults) noResults.classList.add('hidden');
        }

        // Click outside to close dropdown
        document.addEventListener('click', function (e) {
            const wrapper = document.getElementById('leaderSearchableWrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                closeLeaderDropdown();
            }
        });

        function filterMembers(query) {
            const q = removeAccents(query.trim());
            document.querySelectorAll('.member-item').forEach(item => {
                const name = removeAccents(item.getAttribute('data-name') || '');
                const email = removeAccents(item.getAttribute('data-email') || '');
                if (name.includes(q) || email.includes(q)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }
    </script>
@endsection