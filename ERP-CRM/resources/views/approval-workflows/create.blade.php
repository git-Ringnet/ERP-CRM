@extends('layouts.app')

@section('title', 'Tạo quy trình duyệt mới')
@section('page-title', 'Tạo quy trình duyệt mới')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <style>
        .ts-control { border-radius: 0.5rem !important; padding: 0.5rem 0.75rem !important; }
        .ts-wrapper.multi .ts-control > div { background: #3b82f6; color: white; border-radius: 4px; padding: 2px 8px; }
        /* Fix for hidden required fields not showing validation bubbles - ONLY for TomSelect */
        select.approver-value-select[required].is-tomselect {
            display: block !important;
            position: absolute !important;
            clip: rect(0,0,0,0) !important;
            height: 1px !important;
            width: 1px !important;
            padding: 0 !important;
            border: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
        }
    </style>
@endpush

@section('content')
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <strong class="block mb-1">Đã có lỗi xảy ra, vui lòng kiểm tra lại:</strong>
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('approval-workflows.store') }}" method="POST" id="workflowForm">
        @csrf
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold">Thông tin quy trình</h3>
                <a href="{{ route('approval-workflows.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Quay lại
                </a>
            </div>
            <div class="p-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tên quy trình <span
                                class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            placeholder="VD: Quy trình duyệt báo giá"
                            class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Loại chứng từ <span
                                class="text-red-500">*</span></label>
                        <select name="document_type" required
                            class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 @error('document_type') border-red-500 @enderror">
                            <option value="">Chọn loại chứng từ</option>
                            @foreach($documentTypes as $value => $label)
                                <option value="{{ $value }}" {{ old('document_type') == $value ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('document_type')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                        <textarea name="description" rows="2"
                            class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                class="mr-2">
                            <span class="text-sm text-gray-700">Kích hoạt quy trình</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Levels -->
        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold">Các cấp duyệt</h3>
                <button type="button" onclick="addLevel()"
                    class="inline-flex items-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors">
                    <i class="fas fa-plus mr-2"></i> Thêm cấp duyệt
                </button>
            </div>

            <div class="p-6" id="levelsContainer">
                <!-- Initial levels will be added by JS to ensure consistency -->
            </div>
        </div>

        <div class="flex justify-end space-x-4">
            <a href="{{ route('approval-workflows.index') }}"
                class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
                <i class="fas fa-times mr-2"></i> Hủy
            </a>
            <button type="submit"
                class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-save mr-2"></i> Lưu quy trình
            </button>
        </div>
    </form>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        let levelIndex = 0;
        const users = @json($users);
        const roles = @json($roles->map(fn($r) => ['id' => $r->slug, 'name' => $r->name]));

        // Add first 2 levels on load OR restore from old input
        document.addEventListener('DOMContentLoaded', function() {
            const oldLevels = @json(old('levels'));
            if (oldLevels && oldLevels.length > 0) {
                oldLevels.forEach((level, index) => {
                    addLevel(level);
                });
            } else {
                addLevel(); // Level 1
                addLevel(); // Level 2
            }
        });

        function addLevel(data = null) {
            const container = document.getElementById('levelsContainer');
            const div = document.createElement('div');
            div.className = 'level-item bg-gray-50 p-4 rounded-lg mb-4 relative';
            
            const nameValue = data ? (data.name || '') : '';
            const typeValue = data ? (data.approver_type || 'role') : 'role';
            const minAmount = data ? (data.min_amount || '') : '';
            const maxAmount = data ? (data.max_amount || '') : '';
            const isRequired = data ? (data.is_required == '1') : true;

            div.innerHTML = `
                    <div class="absolute top-2 right-2">
                        <button type="button" onclick="removeLevel(this)" class="text-red-600 hover:text-red-800 p-1">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <h5 class="font-semibold mb-3 text-blue-600">Cấp ${levelIndex + 1}</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tên cấp duyệt <span class="text-red-500">*</span></label>
                            <input type="text" name="levels[${levelIndex}][name]" required value="${nameValue}" placeholder="VD: Trưởng phòng"
                                class="w-full border border-gray-200 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loại người duyệt <span class="text-red-500">*</span></label>
                            <select name="levels[${levelIndex}][approver_type]" required onchange="handleTypeChange(this)"
                                class="w-full border border-gray-200 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="role" ${typeValue === 'role' ? 'selected' : ''}>Theo vai trò</option>
                                <option value="user" ${typeValue === 'user' ? 'selected' : ''}>Người dùng cụ thể</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Người duyệt <span class="text-red-500">*</span></label>
                            <div class="approver-select-container">
                                <select name="levels[${levelIndex}][approver_value]" required class="approver-value-select w-full border border-gray-200 rounded px-3 py-2">
                                    <option value="">Chọn...</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Giá trị tối thiểu</label>
                            <input type="number" name="levels[${levelIndex}][min_amount]" value="${minAmount}" min="0" placeholder="0"
                                class="w-full border border-gray-200 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Giá trị tối đa</label>
                            <input type="number" name="levels[${levelIndex}][max_amount]" value="${maxAmount}" min="0" placeholder="Không giới hạn"
                                class="w-full border border-gray-200 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="levels[${levelIndex}][is_required]" value="1" ${isRequired ? 'checked' : ''} class="mr-2">
                                <span class="text-sm text-gray-700">Cấp duyệt bắt buộc (Không được bỏ qua)</span>
                            </label>
                        </div>
                    </div>
                `;
            container.appendChild(div);
            
            // Initialize with current value
            const typeSelect = div.querySelector('select[name*="[approver_type]"]');
            handleTypeChange(typeSelect, data ? data.approver_value : null);

            levelIndex++;
            updateLevelNumbers();
        }

        function handleTypeChange(select, initialValue = null) {
            const row = select.closest('.level-item');
            const container = row.querySelector('.approver-select-container');
            const type = select.value;
            const index = row.dataset.index || [...row.parentNode.children].indexOf(row);
            
            // Destroy existing TomSelect if any
            const existingSelect = row.querySelector('.approver-value-select');
            if (existingSelect && existingSelect.tomselect) {
                existingSelect.tomselect.destroy();
            }

            container.innerHTML = '';
            const newSelect = document.createElement('select');
            newSelect.className = 'approver-value-select w-full border border-gray-200 rounded px-3 py-2';
            newSelect.required = true;
            
            if (type === 'user') {
                newSelect.name = `levels[${index}][approver_value][]`;
                newSelect.multiple = true;
                newSelect.classList.add('is-tomselect');
                
                const selectedIds = initialValue ? (Array.isArray(initialValue) ? initialValue : initialValue.toString().split(',')) : [];

                users.forEach(u => {
                    const opt = document.createElement('option');
                    opt.value = u.id;
                    opt.textContent = u.name;
                    if (selectedIds.includes(u.id.toString())) opt.selected = true;
                    newSelect.appendChild(opt);
                });
                container.appendChild(newSelect);
                new TomSelect(newSelect, {
                    plugins: ['remove_button'],
                    placeholder: 'Chọn một hoặc nhiều người dùng...'
                });
            } else {
                newSelect.name = `levels[${index}][approver_value]`;
                const emptyOpt = document.createElement('option');
                emptyOpt.value = '';
                emptyOpt.textContent = 'Chọn vai trò...';
                newSelect.appendChild(emptyOpt);
                roles.forEach(r => {
                    const opt = document.createElement('option');
                    opt.value = r.id;
                    opt.textContent = r.name;
                    if (initialValue && initialValue.toString() === r.id.toString()) opt.selected = true;
                    newSelect.appendChild(opt);
                });
                container.appendChild(newSelect);
            }
        }
    </script>
    @endpush
@endsection