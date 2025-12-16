@extends('layouts.app')

@section('title', 'Sửa quy trình duyệt - ' . $approvalWorkflow->name)
@section('page-title', 'Sửa quy trình: ' . $approvalWorkflow->name)

@section('content')
@if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
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
                        class="w-full border rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Loại chứng từ</label>
                    <input type="text" value="{{ $documentTypes[$approvalWorkflow->document_type] ?? $approvalWorkflow->document_type }}" readonly
                        class="w-full border rounded-lg px-4 py-2 bg-gray-100">
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
                <div class="level-item bg-gray-50 p-4 rounded-lg mb-4 relative">
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
                            <select name="levels[{{ $index }}][approver_type]" required onchange="toggleApproverOptions(this)" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 approver-type-select">
                                <option value="role" {{ $level->approver_type == 'role' ? 'selected' : '' }}>Theo vai trò</option>
                                <option value="user" {{ $level->approver_type == 'user' ? 'selected' : '' }}>Người dùng cụ thể</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Người duyệt <span class="text-red-500">*</span></label>
                            <select name="levels[{{ $index }}][approver_value]" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 approver-value-select">
                                <option value="">Chọn...</option>
                                @if($level->approver_type == 'role')
                                    <option value="manager" {{ $level->approver_value == 'manager' ? 'selected' : '' }}>Trưởng phòng</option>
                                    <option value="director" {{ $level->approver_value == 'director' ? 'selected' : '' }}>Giám đốc</option>
                                    <option value="accountant" {{ $level->approver_value == 'accountant' ? 'selected' : '' }}>Kế toán</option>
                                    <option value="legal" {{ $level->approver_value == 'legal' ? 'selected' : '' }}>Pháp chế</option>
                                @else
                                    @foreach($users as $user)
                                        <option value="user_{{ $user->id }}" {{ $level->approver_value == "user_{$user->id}" ? 'selected' : '' }}>{{ $user->name }}</option>
                                    @endforeach
                                @endif
                            </select>
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

<script>
let levelIndex = {{ count($approvalWorkflow->levels) }};
const users = @json($users);

const roles = [
    { value: 'manager', label: 'Trưởng phòng' },
    { value: 'director', label: 'Giám đốc' },
    { value: 'accountant', label: 'Kế toán' },
    { value: 'legal', label: 'Pháp chế' }
];

function toggleApproverOptions(selectElement) {
    const levelItem = selectElement.closest('.level-item');
    const approverSelect = levelItem.querySelector('.approver-value-select');
    const approverType = selectElement.value;
    
    // Clear current options
    approverSelect.innerHTML = '<option value="">Chọn...</option>';
    
    if (approverType === 'role') {
        roles.forEach(role => {
            approverSelect.innerHTML += `<option value="${role.value}">${role.label}</option>`;
        });
    } else {
        users.forEach(user => {
            approverSelect.innerHTML += `<option value="user_${user.id}">${user.name}</option>`;
        });
    }
}

function addLevel() {
    const container = document.getElementById('levelsContainer');
    const div = document.createElement('div');
    div.className = 'level-item bg-gray-50 p-4 rounded-lg mb-4 relative';
    
    const roleOptions = roles.map(r => `<option value="${r.value}">${r.label}</option>`).join('');
    
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
                <select name="levels[${levelIndex}][approver_type]" required onchange="toggleApproverOptions(this)" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 approver-type-select">
                    <option value="role">Theo vai trò</option>
                    <option value="user">Người dùng cụ thể</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Người duyệt <span class="text-red-500">*</span></label>
                <select name="levels[${levelIndex}][approver_value]" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 approver-value-select">
                    <option value="">Chọn...</option>
                    ${roleOptions}
                </select>
            </div>
        </div>
    `;
    container.appendChild(div);
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
    });
}
</script>
@endsection
