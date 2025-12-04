@extends('layouts.app')

@section('title', 'Import Dữ Liệu')
@section('page-title', 'Import Dữ Liệu')

@section('content')
<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">
            <i class="fas fa-file-import text-primary"></i>
            Import Dữ Liệu
        </h2>
        <p class="text-gray-600">Nhập dữ liệu hàng loạt từ file Excel cho Khách hàng và Sản phẩm</p>
    </div>

    @if(!isset($preview) && !isset($imported))
    <!-- Upload Form -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="{{ route('import.preview') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <!-- Select Import Type -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Loại dữ liệu <span class="text-red-500">*</span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="relative flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-primary transition-colors">
                        <input type="radio" name="type" value="customers" class="mr-3" required>
                        <div>
                            <div class="font-semibold text-gray-800">
                                <i class="fas fa-users text-primary"></i>
                                Khách hàng
                            </div>
                            <div class="text-sm text-gray-500">Import danh sách khách hàng</div>
                        </div>
                    </label>
                    
                    <label class="relative flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-primary transition-colors">
                        <input type="radio" name="type" value="products" class="mr-3" required>
                        <div>
                            <div class="font-semibold text-gray-800">
                                <i class="fas fa-box text-primary"></i>
                                Sản phẩm
                            </div>
                            <div class="text-sm text-gray-500">Import danh sách sản phẩm</div>
                        </div>
                    </label>
                </div>
                @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Download Template -->
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                    <div class="flex-1">
                        <h4 class="font-semibold text-blue-800 mb-2">Tải file mẫu</h4>
                        <p class="text-sm text-blue-700 mb-3">Tải file Excel mẫu để đảm bảo định dạng đúng:</p>
                        <div class="flex space-x-3">
                            <a href="{{ route('import.template', 'customers') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                <i class="fas fa-download mr-2"></i>
                                Mẫu Khách hàng
                            </a>
                            <a href="{{ route('import.template', 'products') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                <i class="fas fa-download mr-2"></i>
                                Mẫu Sản phẩm
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- File Upload -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Chọn file Excel <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center justify-center w-full">
                    <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                            <p class="mb-2 text-sm text-gray-500">
                                <span class="font-semibold">Click để chọn file</span> hoặc kéo thả
                            </p>
                            <p class="text-xs text-gray-500">Excel (XLSX, XLS) hoặc CSV</p>
                        </div>
                        <input type="file" name="file" class="hidden" accept=".xlsx,.xls,.csv" required onchange="displayFileName(this)">
                    </label>
                </div>
                <p id="fileName" class="mt-2 text-sm text-gray-600"></p>
                @error('file')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-3">
                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-eye mr-2"></i>
                    Xem trước
                </button>
            </div>
        </form>
    </div>
    @endif

    @if(isset($preview) && !isset($imported))
    <!-- Preview Section -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-xl font-bold text-gray-800 mb-4">
            <i class="fas fa-eye text-primary"></i>
            Xem trước dữ liệu
        </h3>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="text-sm text-blue-600 mb-1">Tổng số dòng</div>
                <div class="text-2xl font-bold text-blue-800">
                    {{ count($validation['valid']) + count($validation['invalid']) + count($validation['duplicates']) }}
                </div>
            </div>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="text-sm text-green-600 mb-1">Hợp lệ</div>
                <div class="text-2xl font-bold text-green-800">{{ count($validation['valid']) }}</div>
            </div>
            
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="text-sm text-red-600 mb-1">Lỗi</div>
                <div class="text-2xl font-bold text-red-800">{{ count($validation['invalid']) }}</div>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="text-sm text-yellow-600 mb-1">Trùng lặp</div>
                <div class="text-2xl font-bold text-yellow-800">{{ count($validation['duplicates']) }}</div>
            </div>
        </div>

        <!-- Valid Data Preview -->
        @if(count($validation['valid']) > 0)
        <div class="mb-6">
            <h4 class="font-semibold text-green-800 mb-3">
                <i class="fas fa-check-circle"></i>
                Dữ liệu hợp lệ ({{ count($validation['valid']) }} dòng)
            </h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @if($type === 'customers')
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Điện thoại</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại</th>
                            @else
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tên</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Đơn vị</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Giá bán</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Giá vốn</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach(array_slice($validation['valid'], 0, 5) as $row)
                        <tr>
                            @if($type === 'customers')
                                <td class="px-4 py-3 text-sm">{{ $row['code'] }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row['name'] }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row['email'] }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row['phone'] }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full {{ $row['type'] === 'vip' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($row['type']) }}
                                    </span>
                                </td>
                            @else
                                <td class="px-4 py-3 text-sm">{{ $row['code'] }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row['name'] }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row['unit'] }}</td>
                                <td class="px-4 py-3 text-sm">{{ number_format($row['price']) }}</td>
                                <td class="px-4 py-3 text-sm">{{ number_format($row['cost']) }}</td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @if(count($validation['valid']) > 5)
                <p class="text-sm text-gray-500 mt-2 px-4">... và {{ count($validation['valid']) - 5 }} dòng khác</p>
                @endif
            </div>
        </div>
        @endif

        <!-- Invalid Data -->
        @if(count($validation['invalid']) > 0)
        <div class="mb-6">
            <h4 class="font-semibold text-red-800 mb-3">
                <i class="fas fa-exclamation-circle"></i>
                Dữ liệu lỗi ({{ count($validation['invalid']) }} dòng)
            </h4>
            <div class="space-y-2">
                @foreach(array_slice($validation['invalid'], 0, 5) as $error)
                <div class="p-3 bg-red-50 border border-red-200 rounded text-sm">
                    <span class="font-semibold text-red-800">Dòng {{ $error['row'] }}:</span>
                    <span class="text-red-600">{{ $error['error'] }}</span>
                </div>
                @endforeach
                @if(count($validation['invalid']) > 5)
                <p class="text-sm text-gray-500 px-3">... và {{ count($validation['invalid']) - 5 }} lỗi khác</p>
                @endif
            </div>
        </div>
        @endif

        <!-- Duplicate Data -->
        @if(count($validation['duplicates']) > 0)
        <div class="mb-6">
            <h4 class="font-semibold text-yellow-800 mb-3">
                <i class="fas fa-copy"></i>
                Dữ liệu trùng lặp ({{ count($validation['duplicates']) }} dòng)
            </h4>
            <div class="space-y-2">
                @foreach(array_slice($validation['duplicates'], 0, 5) as $duplicate)
                <div class="p-3 bg-yellow-50 border border-yellow-200 rounded text-sm">
                    <span class="font-semibold text-yellow-800">Dòng {{ $duplicate['row'] }}:</span>
                    <span class="text-yellow-600">{{ $duplicate['error'] }}</span>
                </div>
                @endforeach
                @if(count($validation['duplicates']) > 5)
                <p class="text-sm text-gray-500 px-3">... và {{ count($validation['duplicates']) - 5 }} trùng lặp khác</p>
                @endif
            </div>
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex justify-between items-center pt-4 border-t">
            <a href="{{ route('import.index') }}" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>
                Quay lại
            </a>
            
            @if(count($validation['valid']) > 0)
            <form action="{{ route('import.store') }}" method="POST">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="tempFile" value="{{ $tempFile }}">
                
                <button type="submit" class="px-6 py-2 bg-success text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-check mr-2"></i>
                    Xác nhận Import ({{ count($validation['valid']) }} dòng)
                </button>
            </form>
            @endif
        </div>
    </div>
    @endif

    @if(isset($imported))
    <!-- Import Summary -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                <i class="fas fa-check text-3xl text-green-600"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mb-2">Import thành công!</h3>
            <p class="text-gray-600">Dữ liệu đã được nhập vào hệ thống</p>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                <div class="text-sm text-blue-600 mb-1">Tổng số dòng</div>
                <div class="text-3xl font-bold text-blue-800">{{ $summary['total'] }}</div>
            </div>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                <div class="text-sm text-green-600 mb-1">Thành công</div>
                <div class="text-3xl font-bold text-green-800">{{ $summary['success'] }}</div>
            </div>
            
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                <div class="text-sm text-red-600 mb-1">Thất bại</div>
                <div class="text-3xl font-bold text-red-800">{{ $summary['failed'] }}</div>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                <div class="text-sm text-yellow-600 mb-1">Bỏ qua</div>
                <div class="text-3xl font-bold text-yellow-800">{{ $summary['skipped'] }}</div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-center space-x-3">
            <a href="{{ route('import.index') }}" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Import thêm
            </a>
            
            @if($type === 'customers')
            <a href="{{ route('customers.index') }}" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                <i class="fas fa-list mr-2"></i>
                Xem danh sách
            </a>
            @else
            <a href="{{ route('products.index') }}" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                <i class="fas fa-list mr-2"></i>
                Xem danh sách
            </a>
            @endif
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
function displayFileName(input) {
    const fileName = input.files[0]?.name;
    if (fileName) {
        document.getElementById('fileName').textContent = 'File đã chọn: ' + fileName;
    }
}
</script>
@endpush
@endsection
