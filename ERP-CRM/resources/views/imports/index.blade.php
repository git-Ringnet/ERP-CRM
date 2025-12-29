@extends('layouts.app')

@section('title', 'Quản lý Nhập kho')
@section('page-title', 'Quản lý Nhập kho')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-arrow-down text-blue-500 mr-2"></i>Danh sách phiếu nhập kho
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('imports.export', request()->query()) }}" 
                   class="inline-flex items-center px-4 py-2 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition-colors">
                    <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                </a>
                <button type="button" onclick="openImportModal()" 
                        class="inline-flex items-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                    <i class="fas fa-file-import mr-2"></i>Import từ Excel
                </button>
                <a href="{{ route('imports.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Tạo phiếu nhập
                </a>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="p-4 border-b border-gray-200 bg-gray-50">
        <form action="{{ route('imports.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Tìm theo mã phiếu..." 
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <select name="warehouse_id" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                    <option value="">-- Tất cả kho --</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="status" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                </select>
            </div>
            <div>
                <input type="date" name="date_from" value="{{ request('date_from') }}" 
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg" placeholder="Từ ngày">
            </div>
            <div class="flex gap-2">
                <input type="date" name="date_to" value="{{ request('date_to') }}" 
                       class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg" placeholder="Đến ngày">
                <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã phiếu</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho nhập</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày nhập</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nhân viên</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($imports as $import)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('imports.show', $import) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                            {{ $import->code }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $import->warehouse->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $import->date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 text-sm font-semibold bg-blue-100 text-blue-800 rounded">
                            {{ number_format($import->total_qty) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($import->status === 'pending')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Chờ xử lý</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoàn thành</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $import->employee->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('imports.show', $import) }}" 
                               class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200" title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($import->status === 'pending')
                                <a href="{{ route('imports.edit', $import) }}" 
                                   class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200" title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="confirmApprove('{{ route('imports.approve', $import) }}', 'phiếu nhập kho')" 
                                        class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200" title="Duyệt">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button onclick="confirmReject('{{ route('imports.reject', $import) }}', 'phiếu nhập kho')" 
                                        class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200" title="Từ chối">
                                    <i class="fas fa-times"></i>
                                </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Chưa có phiếu nhập kho nào.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($imports->hasPages())
    <div class="p-4 border-t border-gray-200">
        {{ $imports->withQueryString()->links() }}
    </div>
    @endif
</div>
<!-- Import Excel Modal -->
<div id="importModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-file-excel text-green-600 mr-2"></i>Import Sản phẩm từ Excel
                </h3>
                <button type="button" onclick="closeImportModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>
        
        <div class="p-6">
            <!-- Template Download -->
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="flex-1">
                        <h4 class="font-medium text-blue-900 mb-2">Tải file mẫu</h4>
                        <p class="text-sm text-blue-700 mb-3">
                            Tải file Excel mẫu để đảm bảo định dạng dữ liệu đúng. Hệ thống sẽ tự động tạo sản phẩm mới nếu chưa tồn tại và nhập vào kho theo cột "Kho" trong file.
                        </p>
                        <p class="text-sm text-blue-700 mb-3">
                            <strong>Lưu ý:</strong> Cột "Kho" có thể nhập <strong>mã kho</strong> (VD: WH0001) hoặc <strong>tên kho</strong> (VD: Kho Chính HCM).
                        </p>
                        <a href="{{ route('excel-import.template', 'products') }}" 
                           class="inline-flex items-center px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-download mr-2"></i>
                            Tải file mẫu Excel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Upload Form -->
            <form action="{{ route('excel-import.store') }}" method="POST" enctype="multipart/form-data" id="importForm">
                @csrf
                <input type="hidden" name="type" value="products">

                <!-- File Upload -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Chọn file Excel <span class="text-red-500">*</span>
                    </label>
                    
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition-colors"
                         id="dropZone">
                        <input type="file" name="file" id="fileInput" accept=".xlsx,.xls" required class="hidden">
                        
                        <div id="dropZoneContent">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                            <p class="text-gray-600 mb-2">
                                Kéo thả file vào đây hoặc 
                                <label for="fileInput" class="text-blue-600 hover:text-blue-700 cursor-pointer font-medium">
                                    chọn file
                                </label>
                            </p>
                            <p class="text-sm text-gray-500">
                                Hỗ trợ: .xlsx, .xls (Tối đa 10MB)
                            </p>
                        </div>

                        <div id="fileInfo" class="hidden">
                            <i class="fas fa-file-excel text-4xl text-green-600 mb-3"></i>
                            <p class="text-gray-900 font-medium" id="fileName"></p>
                            <p class="text-sm text-gray-500" id="fileSize"></p>
                            <button type="button" onclick="clearFile()" 
                                    class="mt-3 text-sm text-red-600 hover:text-red-700">
                                <i class="fas fa-times mr-1"></i>Xóa file
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeImportModal()" 
                            class="px-6 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-times mr-1"></i> Hủy
                    </button>
                    <button type="submit" id="submitBtn" disabled
                            class="px-6 py-2 text-sm text-white bg-green-600 rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-upload mr-1"></i> Bắt đầu Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openImportModal() {
    document.getElementById('importModal').classList.remove('hidden');
}

function closeImportModal() {
    document.getElementById('importModal').classList.add('hidden');
    clearFile();
}

const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const submitBtn = document.getElementById('submitBtn');
const dropZoneContent = document.getElementById('dropZoneContent');
const fileInfo = document.getElementById('fileInfo');

fileInput.addEventListener('change', function(e) {
    if (this.files.length > 0) {
        displayFileInfo(this.files[0]);
    }
});

// Drag and drop
dropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('border-blue-500', 'bg-blue-50');
});

dropZone.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.classList.remove('border-blue-500', 'bg-blue-50');
});

dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('border-blue-500', 'bg-blue-50');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        fileInput.files = files;
        displayFileInfo(files[0]);
    }
});

function displayFileInfo(file) {
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    
    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    
    dropZoneContent.classList.add('hidden');
    fileInfo.classList.remove('hidden');
    submitBtn.disabled = false;
}

function clearFile() {
    fileInput.value = '';
    dropZoneContent.classList.remove('hidden');
    fileInfo.classList.add('hidden');
    submitBtn.disabled = true;
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Close modal on outside click
document.getElementById('importModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImportModal();
    }
});
</script>
@endpush
@endsection
