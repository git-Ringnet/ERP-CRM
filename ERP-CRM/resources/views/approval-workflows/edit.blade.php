@extends('layouts.app')

@section('title', 'Sửa quy trình duyệt - ' . $approvalWorkflow->name)
@section('page-title', 'Sửa quy trình: ' . $approvalWorkflow->name)

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

<form action="{{ route('approval-workflows.update', $approvalWorkflow) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold">Thông tin quy trình</h3>
            <a href="{{ route('approval-workflows.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại
            </a>
        </div>
        <div class="p-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên quy trình <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $approvalWorkflow->name) }}" required
                        class="w-full border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Loại chứng từ</label>
                    <input type="text" value="{{ $documentTypes[$approvalWorkflow->document_type] ?? $approvalWorkflow->document_type }}" readonly
                        class="w-full border rounded-lg px-4 py-2 bg-gray-100 @error('document_type') border-red-500 @enderror">
                    @error('document_type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                    <textarea name="description" rows="2" class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $approvalWorkflow->description) }}</textarea>
                </div>
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $approvalWorkflow->is_active) ? 'checked' : '' }} class="mr-2">
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
            <button type="button" onclick="addLevel()" class="inline-flex items-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors">
                <i class="fas fa-plus mr-2"></i> Thêm cấp duyệt
            </button>
        </div>

        <div class="p-6" id="levelsContainer">
                @foreach($approvalWorkflow->levels as $index => $level)
                <div class="level-item bg-gray-50 p-4 rounded-lg mb-4 relative" data-index="{{ $index }}">
                    <div class="absolute top-2 right-2">
                        <button type="button" onclick="removeLevel(this)" class="text-red-600 hover:text-red-800 p-1">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <h5 class="font-semibold mb-3 text-blue-600">Cấp {{ $index + 1 }}</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tên cấp duyệt <span class="text-red-500">*</span></label>
                            <input type="text" name="levels[{{ $index }}][name]" value="{{ $level->name }}" required
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Loại người duyệt <span class="text-red-500">*</span></label>
                            <select name="levels[{{ $index }}][approver_type]" required onchange="handleTypeChange(this)" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 approver-type-select">
                                <option value="role" {{ $level->approver_type == 'role' ? 'selected' : '' }}>Theo vai trò</option>
                                <option value="user" {{ $level->approver_type == 'user' ? 'selected' : '' }}>Người dùng cụ thể</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Người duyệt <span class="text-red-500">*</span></label>
                            <div class="approver-select-container">
                                @php
                                    $currentValue = $level->approver_value;
                                    // Strip user_ prefix if exists for backward compatibility
                                    $currentValue = str_replace('user_', '', $currentValue);
                                    $selectedValues = explode(',', $currentValue);
                                @endphp

                                @if($level->approver_type == 'user')
                                    <select name="levels[{{ $index }}][approver_value][]" multiple required class="approver-value-select is-tomselect w-full border rounded px-3 py-2">
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ in_array($user->id, $selectedValues) ? 'selected' : '' }}>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <select name="levels[{{ $index }}][approver_value]" required class="approver-value-select w-full border rounded px-3 py-2">
                                        <option value="">Chọn...</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->slug }}" {{ $level->approver_value == $role->slug ? 'selected' : '' }}>{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền tối thiểu</label>
                            <input type="number" name="levels[{{ $index }}][min_amount]" value="{{ $level->min_amount }}" min="0" placeholder="0"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền tối đa</label>
                            <input type="number" name="levels[{{ $index }}][max_amount]" value="{{ $level->max_amount }}" min="0" placeholder="Không giới hạn"
                                class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="levels[{{ $index }}][is_required]" value="1" {{ $level->is_required ? 'checked' : '' }} class="mr-2">
                                <span class="text-sm text-gray-700">Cấp duyệt bắt buộc (Không được bỏ qua)</span>
                            </label>
                        </div>
                    </div>
                </div>
                @endforeach
        </div>
    </div>

    <div class="flex justify-end space-x-4">
        <a href="{{ route('approval-workflows.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors">
            <i class="fas fa-times mr-2"></i> Hủy
        </a>
        <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
            <i class="fas fa-save mr-2"></i> Lưu thay đổi
        </button>
    </div>
</form>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
let levelIndex = {{ count($approvalWorkflow->levels) }};
const users = @json($users);
const roles = @json($roles->map(fn($r) => ['id' => $r->slug, 'name' => $r->name]));

function handleTypeChange(select) {
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
        users.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = u.name;
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
            newSelect.appendChild(opt);
        });
        container.appendChild(newSelect);
    }
}

function addLevel() {
    const container = document.getElementById('levelsContainer');
    const div = document.createElement('div');
    div.className = 'level-item bg-gray-50 p-4 rounded-lg mb-4 relative';
    div.dataset.index = levelIndex;
    
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
                <input type="text" name="levels[${levelIndex}][name]" required
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Loại người duyệt <span class="text-red-500">*</span></label>
                <select name="levels[${levelIndex}][approver_type]" required onchange="handleTypeChange(this)" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 approver-type-select">
                    <option value="role">Theo vai trò</option>
                    <option value="user">Người dùng cụ thể</option>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền tối thiểu</label>
                <input type="number" name="levels[${levelIndex}][min_amount]" min="0" placeholder="0"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Số tiền tối đa</label>
                <input type="number" name="levels[${levelIndex}][max_amount]" min="0" placeholder="Không giới hạn"
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" name="levels[${levelIndex}][is_required]" value="1" checked class="mr-2">
                    <span class="text-sm text-gray-700">Cấp duyệt bắt buộc (Không được bỏ qua)</span>
                </label>
            </div>
        </div>
    `;
    container.appendChild(div);

    // Initialize with Role options
    const typeSelect = div.querySelector('select[name*="[approver_type]"]');
    handleTypeChange(typeSelect);

    levelIndex++;
    updateLevelNumbers();
}

function removeLevel(btn) {
    const items = document.querySelectorAll('.level-item');
    if (items.length > 1) {
        btn.closest('.level-item').remove();
        updateLevelNumbers();
    } else {
        alert('Phải có ít nhất 1 cấp duyệt!');
    }
}

function updateLevelNumbers() {
    document.querySelectorAll('.level-item').forEach((item, index) => {
        item.querySelector('h5').textContent = `Cấp ${index + 1}`;
        item.dataset.index = index;
        item.querySelectorAll('[name]').forEach(input => {
            const name = input.name;
            input.name = name.replace(/levels\[\d+\]/, `levels[${index}]`);
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize TomSelect for existing user-type rows
    document.querySelectorAll('.level-item').forEach(row => {
        const type = row.querySelector('.approver-type-select').value;
        const select = row.querySelector('.approver-value-select');
        if (type === 'user' && select) {
            new TomSelect(select, {
                plugins: ['remove_button'],
                placeholder: 'Chọn một hoặc nhiều người dùng...'
            });
        }
    });
});
</script>
@endpush
@endsection
