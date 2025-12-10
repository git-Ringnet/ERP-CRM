@extends('layouts.app')

@section('title', 'Kết quả Import')
@section('page-title', 'Kết quả Import Dữ Liệu')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h2 class="text-2xl font-semibold text-gray-900">
                @if($success)
                    <i class="fas fa-check-circle text-green-600 mr-2"></i>
                    Import Thành Công
                @else
                    <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                    Import Thất Bại
                @endif
            </h2>
        </div>

        <div class="p-6">
            <!-- Summary -->
            <div class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="text-sm text-blue-600 mb-1">Loại dữ liệu</div>
                        <div class="text-2xl font-bold text-blue-900">
                            {{ $type === 'products' ? 'Sản phẩm' : 'Kho hàng' }}
                        </div>
                    </div>

                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="text-sm text-green-600 mb-1">Đã import</div>
                        <div class="text-2xl font-bold text-green-900">
                            {{ $imported }}
                        </div>
                    </div>

                    <div class="bg-red-50 rounded-lg p-4">
                        <div class="text-sm text-red-600 mb-1">Lỗi</div>
                        <div class="text-2xl font-bold text-red-900">
                            {{ count($errors) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success Message -->
            @if($success)
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-600 mt-1 mr-3"></i>
                    <div>
                        <h4 class="font-medium text-green-900 mb-1">Import hoàn tất!</h4>
                        <p class="text-sm text-green-700">
                            Đã import thành công {{ $imported }} {{ $type === 'products' ? 'sản phẩm' : 'mục hàng' }}.
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Errors -->
            @if(!empty($errors))
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">
                    <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                    Chi tiết lỗi ({{ count($errors) }})
                </h3>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 max-h-96 overflow-y-auto">
                    <ul class="space-y-2">
                        @foreach($errors as $error)
                        <li class="text-sm text-red-700 flex items-start">
                            <i class="fas fa-times-circle mt-0.5 mr-2"></i>
                            <span>{{ $error }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                <a href="{{ route('import.index') }}" 
                   class="px-6 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                    <i class="fas fa-arrow-left mr-1"></i> Import thêm
                </a>

                @if($success)
                <div class="flex gap-3">
                    @if($type === 'products')
                    <a href="{{ route('products.index') }}" 
                       class="px-6 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-box mr-1"></i> Xem Sản phẩm
                    </a>
                    @else
                    <a href="{{ route('transactions.index') }}" 
                       class="px-6 py-2 text-sm text-white bg-green-600 rounded-lg hover:bg-green-700">
                        <i class="fas fa-warehouse mr-1"></i> Xem Giao dịch
                    </a>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
