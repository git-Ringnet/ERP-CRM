@extends('layouts.app')

@section('title', 'Import Dữ Liệu')
@section('page-title', 'Import Dữ Liệu từ Excel')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-2xl font-semibold text-gray-900">
                <i class="fas fa-file-excel mr-2 text-green-600"></i>
                Import Dữ Liệu
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Nhập dữ liệu hàng loạt từ file Excel cho Sản phẩm và Kho hàng
            </p>
        </div>

        <div class="p-6">
            <!-- Import Type Selection -->
            <div class="mb-8">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    Loại dữ liệu <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="relative flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition-colors">
                        <input type="radio" name="import_type" value="products" class="mr-3" checked onchange="updateImportType()">
                        <div class="flex-1">
                            <div class="flex items-center">
                                <i class="fas fa-box text-blue-600 text-2xl mr-3"></i>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Sản phẩm</h3>
                                    <p class="text-sm text-gray-500">Import danh sách sản phẩm</p>
                                </div>
                            </div>
                        </div>
                    </label>

                    <label class="relative flex items-center p-4 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-500 transition-colors">
                        <input type="radio" name="import_type" value="inventory" class="mr-3" onchange="updateImportType()">
                        <div class="flex-1">
                            <div class="flex items-center">
                                <i class="fas fa-warehouse text-green-600 text-2xl mr-3"></i>
                                <div>
                                    <h3 class="font-semibold text-gray-900">Kho hàng</h3>
                                    <p class="text-sm text-gray-500">Import dữ liệu nhập kho</p>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Template Download -->
            <div class="mb-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-600 mt-1 mr-3"></i>
                    <div class="flex-1">
                        <h4 class="font-medium text-blue-900 mb-2">Tải file mẫu</h4>
                        <p class="text-sm text-blue-700 mb-3">
                            Tải file Excel mẫu để đảm bảo định dạng dữ liệu đúng. File mẫu bao gồm hướng dẫn chi tiết và ví dụ.
                        </p>
                        <div class="flex gap-3">
                            <a href="{{ route('excel-import.template', 'products') }}" 
                               class="inline-flex items-center px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                <i class="fas fa-download mr-2"></i>
                                Mẫu Sản phẩm
                            </a>
                            <a href="{{ route('excel-import.template', 'inventory') }}" 
                               class="inline-flex items-center px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700">
                                <i class="fas fa-download mr-2"></i>
                                Mẫu Kho hàng
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- File Upload Form -->
            <form action="{{ route('excel-import.preview') }}" method="POST" enctype="multipart/form-data" id="importForm">
                @csrf
                <input type="hidden" name="type" id="importTypeInput" value="products">

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Chọn file Excel <span class="text-red-500">*</span>
                    </label>
                    
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-500 transition-colors"
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

                    @error('file')
                        <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-3">
                    <a href="{{ route('imports.index') }}" 
                       class="px-6 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-times mr-1"></i> Hủy
                    </a>
                    <button type="submit" id="submitBtn" disabled
                            class="px-6 py-2 text-sm text-white bg-primary rounded-lg hover:bg-primary-dark disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-upload mr-1"></i> Bắt đầu Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const submitBtn = document.getElementById('submitBtn');
const dropZoneContent = document.getElementById('dropZoneContent');
const fileInfo = document.getElementById('fileInfo');

function updateImportType() {
    const selectedType = document.querySelector('input[name="import_type"]:checked').value;
    document.getElementById('importTypeInput').value = selectedType;
}

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
</script>
@endpush
@endsection
