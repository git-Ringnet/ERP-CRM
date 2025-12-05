@extends('layouts.app')

@section('title', 'Import Giao Dịch')
@section('page-title', 'Import Giao Dịch từ File')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">Import dữ liệu giao dịch</h2>
            <a href="{{ route('transactions.index') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>
        
        <form action="{{ route('transactions.import') }}" method="POST" enctype="multipart/form-data" class="p-6">
            @csrf
            
            <!-- Instructions -->
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h3 class="text-sm font-semibold text-blue-900 mb-2">
                    <i class="fas fa-info-circle mr-1"></i> Hướng dẫn
                </h3>
                <ul class="text-sm text-blue-800 space-y-1 list-disc list-inside">
                    <li>Chọn file CSV hoặc JSON chứa dữ liệu giao dịch</li>
                    <li>File CSV phải có header: code, type, warehouse_id, date, employee_id, note</li>
                    <li>File JSON phải có cấu trúc đúng định dạng export</li>
                    <li>Hệ thống sẽ tự động kiểm tra và bỏ qua các giao dịch không hợp lệ</li>
                </ul>
            </div>

            <!-- File Upload -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Chọn file <span class="text-red-500">*</span>
                </label>
                <input type="file" name="file" required accept=".csv,.json,.txt"
                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg @error('file') border-red-500 @enderror">
                @error('file')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Format Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Định dạng file <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-4">
                    <label class="flex items-center">
                        <input type="radio" name="format" value="csv" checked
                               class="mr-2 text-primary focus:ring-primary">
                        <span class="text-sm text-gray-700">CSV</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="format" value="json"
                               class="mr-2 text-primary focus:ring-primary">
                        <span class="text-sm text-gray-700">JSON</span>
                    </label>
                </div>
                @error('format')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <!-- Sample Format -->
            <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <h3 class="text-sm font-semibold text-gray-900 mb-2">
                    <i class="fas fa-file-alt mr-1"></i> Mẫu định dạng CSV
                </h3>
                <pre class="text-xs text-gray-700 overflow-x-auto">code,type,warehouse_id,date,employee_id,note
IMP-2024-001,import,1,2024-01-15,2,Nhập hàng từ nhà cung cấp
EXP-2024-001,export,1,2024-01-16,3,Xuất hàng cho khách
TRF-2024-001,transfer,1,2024-01-17,2,Chuyển kho nội bộ</pre>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end gap-3">
                <a href="{{ route('transactions.index') }}" 
                   class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                    <i class="fas fa-times mr-1"></i> Hủy
                </a>
                <button type="submit" 
                        class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:bg-primary-dark">
                    <i class="fas fa-upload mr-1"></i> Import
                </button>
            </div>
        </form>
    </div>

    <!-- Import Results -->
    @if(session('success') || session('error'))
    <div class="mt-6 bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Kết quả import</h3>
        
        @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-800">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            </p>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-800">
                <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            </p>
        </div>
        @endif

        @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="mb-4">
            <h4 class="text-sm font-semibold text-red-900 mb-2">Lỗi:</h4>
            <ul class="text-sm text-red-800 space-y-1 list-disc list-inside">
                @foreach(session('import_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(session('import_warnings') && count(session('import_warnings')) > 0)
        <div>
            <h4 class="text-sm font-semibold text-yellow-900 mb-2">Cảnh báo:</h4>
            <ul class="text-sm text-yellow-800 space-y-1 list-disc list-inside">
                @foreach(session('import_warnings') as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
    @endif
@endsection
