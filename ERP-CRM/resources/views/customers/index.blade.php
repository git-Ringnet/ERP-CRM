@extends('layouts.app')

@section('title', 'Khách hàng')
@section('page-title', 'Quản lý Khách hàng')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex flex-col sm:flex-row gap-4 flex-1">
            <!-- Search -->
            <div class="relative flex-1">
                <form action="{{ route('customers.index') }}" method="GET" class="flex">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Tìm kiếm..." 
                           class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </form>
            </div>
            
            <!-- Filter by Type -->
            <div class="flex items-center">
                <select name="type" onchange="window.location.href='{{ route('customers.index') }}?type='+this.value+'&search={{ request('search') }}'" 
                        class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">Tất cả</option>
                    <option value="normal" {{ request('type') == 'normal' ? 'selected' : '' }}>Thường</option>
                    <option value="vip" {{ request('type') == 'vip' ? 'selected' : '' }}>VIP</option>
                </select>
            </div>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-2">
            <!-- Export/Import Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" type="button"
                        class="inline-flex items-center justify-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors text-sm">
                    <i class="fas fa-file-excel mr-2"></i>
                    <span class="hidden sm:inline">Xuất / Nhập</span>
                    <span class="sm:hidden">Excel</span>
                    <i class="fas fa-chevron-down ml-2 text-xs"></i>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak
                     class="absolute left-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                    <a href="{{ route('customers.export') }}?{{ http_build_query(request()->query()) }}" 
                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-t-lg">
                        <i class="fas fa-download mr-2 text-green-600"></i>Export Excel
                    </a>
                    <button type="button" onclick="openImportModal()"
                            class="w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-b-lg">
                        <i class="fas fa-upload mr-2 text-blue-600"></i>Import Excel
                    </button>
                </div>
            </div>
            <a href="{{ route('customers.create') }}" 
               class="inline-flex items-center justify-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors text-sm">
                <i class="fas fa-plus mr-2"></i>
                <span class="hidden sm:inline">Thêm khách hàng</span>
                <span class="sm:hidden">Thêm</span>
            </a>
        </div>
    </div>

    <!-- Table - Desktop View -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mã KH</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tên khách hàng</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Điện thoại</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loại</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hạn mức nợ</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Thao tác</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($customers as $customer)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 whitespace-nowrap text-center text-sm text-gray-500">
                        {{ ($customers->currentPage() - 1) * $customers->perPage() + $loop->iteration }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="font-medium text-gray-900">{{ $customer->code }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm font-medium text-gray-900">{{ $customer->name }}</div>
                        @if($customer->contact_person)
                            <div class="text-sm text-gray-500">LH: {{ $customer->contact_person }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $customer->email }}</td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $customer->phone }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if($customer->type == 'vip')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                <i class="fas fa-crown mr-1"></i>VIP
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                Thường
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                        {{ number_format($customer->debt_limit) }} đ
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('customers.show', $customer->id) }}" 
                               class="p-2 text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-colors" 
                               title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('customers.edit', $customer->id) }}" 
                               class="p-2 text-yellow-600 bg-yellow-50 rounded-lg hover:bg-yellow-100 hover:text-yellow-700 transition-colors" 
                               title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="p-2 text-red-600 bg-red-50 rounded-lg hover:bg-red-100 hover:text-red-700 transition-colors delete-btn" 
                                        data-name="{{ $customer->name }}" title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p>Không có dữ liệu khách hàng</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Card View - Mobile -->
    <div class="md:hidden divide-y divide-gray-200">
        @forelse($customers as $customer)
        <div class="p-4 hover:bg-gray-50">
            <div class="flex justify-between items-start mb-2">
                <div class="flex-1">
                    <div class="font-medium text-gray-900">{{ $customer->name }}</div>
                    <div class="text-sm text-gray-500">{{ $customer->code }}</div>
                </div>
                @if($customer->type == 'vip')
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        <i class="fas fa-crown mr-1"></i>VIP
                    </span>
                @else
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                        Thường
                    </span>
                @endif
            </div>
            <div class="space-y-1 text-sm text-gray-600 mb-3">
                <div><i class="fas fa-envelope w-4"></i> {{ $customer->email }}</div>
                <div><i class="fas fa-phone w-4"></i> {{ $customer->phone }}</div>
                @if($customer->contact_person)
                    <div><i class="fas fa-user w-4"></i> {{ $customer->contact_person }}</div>
                @endif
                <div><i class="fas fa-money-bill w-4"></i> {{ number_format($customer->debt_limit) }} đ</div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('customers.show', $customer->id) }}" 
                   class="flex-1 text-center px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm">
                    <i class="fas fa-eye mr-1"></i>Xem
                </a>
                <a href="{{ route('customers.edit', $customer->id) }}" 
                   class="flex-1 text-center px-3 py-2 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 text-sm">
                    <i class="fas fa-edit mr-1"></i>Sửa
                </a>
                <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" class="flex-1 delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="w-full px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 text-sm delete-btn"
                            data-name="{{ $customer->name }}">
                        <i class="fas fa-trash mr-1"></i>Xóa
                    </button>
                </form>
            </div>
        </div>
        @empty
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-inbox text-4xl mb-2"></i>
            <p>Không có dữ liệu khách hàng</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($customers->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $customers->appends(request()->query())->links() }}
    </div>
    @endif
</div>

<!-- Import Excel Modal -->
<div id="importModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-file-excel text-green-600 mr-2"></i>Import Khách hàng từ Excel
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
                            Tải file Excel mẫu để đảm bảo định dạng dữ liệu đúng. Nếu mã KH đã tồn tại, hệ thống sẽ cập nhật thông tin.
                        </p>
                        <a href="{{ route('customers.import.template') }}" 
                           class="inline-flex items-center px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <i class="fas fa-download mr-2"></i>
                            Tải file mẫu Excel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Upload Form -->
            <form action="{{ route('customers.import') }}" method="POST" enctype="multipart/form-data" id="importForm">
                @csrf

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

if (fileInput) {
    fileInput.addEventListener('change', function(e) {
        if (this.files.length > 0) {
            displayFileInfo(this.files[0]);
        }
    });
}

if (dropZone) {
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
}

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

document.getElementById('importModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImportModal();
    }
});
</script>
@endpush
@endsection
