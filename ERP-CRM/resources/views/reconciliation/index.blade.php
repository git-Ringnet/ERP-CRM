@extends('layouts.app')

@section('title', 'Đối soát giữa các Module')
@section('page-title', 'Đối soát giữa các Module')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-check-double text-indigo-600 mr-2"></i>
                    Đối soát giữa các Module
                </h2>
                <p class="text-sm text-gray-500 mt-1">Kiểm tra tự động phát hiện sự bất nhất dữ liệu giữa các module trong hệ thống</p>
            </div>
            <div class="flex items-center space-x-2">
                @if($totalIssues > 0)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        {{ $totalIssues }} vấn đề phát hiện
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>
                        Không có vấn đề
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        {{-- Sale ↔ Export --}}
        <a href="{{ route('reconciliation.sale-export') }}" class="block bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
                </div>
                @if($saleExportSummary['total_issues'] > 0)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        {{ $saleExportSummary['total_issues'] }} lỗi
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check mr-1"></i> OK
                    </span>
                @endif
            </div>
            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-blue-600 transition-colors">Bán hàng ↔ Xuất kho</h3>
            <p class="text-sm text-gray-500 mt-1">Kiểm tra đơn bán đã duyệt có xuất kho đúng không</p>
            <div class="mt-4 space-y-1 text-xs text-gray-600">
                <div class="flex justify-between">
                    <span>Chưa xuất kho:</span>
                    <span class="{{ $saleExportSummary['missing_exports'] > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                        {{ $saleExportSummary['missing_exports'] }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>Số lượng không khớp:</span>
                    <span class="{{ $saleExportSummary['quantity_mismatches'] > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                        {{ $saleExportSummary['quantity_mismatches'] }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>Phiếu xuất bất thường:</span>
                    <span class="{{ $saleExportSummary['mismatched_exports'] > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                        {{ $saleExportSummary['mismatched_exports'] }}
                    </span>
                </div>
            </div>
        </a>

        {{-- PurchaseOrder ↔ Import --}}
        <a href="{{ route('reconciliation.purchase-import') }}" class="block bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-contract text-orange-600 text-xl"></i>
                </div>
                @if($purchaseImportSummary['total_issues'] > 0)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        {{ $purchaseImportSummary['total_issues'] }} lỗi
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check mr-1"></i> OK
                    </span>
                @endif
            </div>
            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-orange-600 transition-colors">Mua hàng ↔ Nhập kho</h3>
            <p class="text-sm text-gray-500 mt-1">Kiểm tra đơn mua đã nhận có nhập kho đúng không</p>
            <div class="mt-4 space-y-1 text-xs text-gray-600">
                <div class="flex justify-between">
                    <span>Chưa nhập kho:</span>
                    <span class="{{ $purchaseImportSummary['missing_imports'] > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                        {{ $purchaseImportSummary['missing_imports'] }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>Số lượng không khớp:</span>
                    <span class="{{ $purchaseImportSummary['quantity_mismatches'] > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                        {{ $purchaseImportSummary['quantity_mismatches'] }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>Phiếu nhập bất thường:</span>
                    <span class="{{ $purchaseImportSummary['mismatched_imports'] > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                        {{ $purchaseImportSummary['mismatched_imports'] }}
                    </span>
                </div>
            </div>
        </a>

        {{-- Inventory --}}
        <a href="{{ route('reconciliation.inventory') }}" class="block bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-boxes text-purple-600 text-xl"></i>
                </div>
                @if($inventorySummary['total_issues'] > 0)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        {{ $inventorySummary['total_issues'] }} lỗi
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check mr-1"></i> OK
                    </span>
                @endif
            </div>
            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-purple-600 transition-colors">Tồn kho</h3>
            <p class="text-sm text-gray-500 mt-1">Kiểm tra tồn kho ghi nhận có khớp thực tế không</p>
            <div class="mt-4 space-y-1 text-xs text-gray-600">
                <div class="flex justify-between">
                    <span>Lệch chi tiết mã vạch:</span>
                    <span class="{{ $inventorySummary['stock_vs_items'] > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                        {{ $inventorySummary['stock_vs_items'] }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>Không khớp nhập/xuất:</span>
                    <span class="{{ $inventorySummary['stock_vs_transactions'] > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                        {{ $inventorySummary['stock_vs_transactions'] }}
                    </span>
                </div>
            </div>
        </a>

        {{-- Debt ↔ Payment --}}
        <a href="{{ route('reconciliation.debt-payment') }}" class="block bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-invoice-dollar text-emerald-600 text-xl"></i>
                </div>
                @if($debtPaymentSummary['total_issues'] > 0)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        {{ $debtPaymentSummary['total_issues'] }} lỗi
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check mr-1"></i> OK
                    </span>
                @endif
            </div>
            <h3 class="text-lg font-semibold text-gray-800 group-hover:text-emerald-600 transition-colors">Công nợ ↔ Thanh toán</h3>
            <p class="text-sm text-gray-500 mt-1">Kiểm tra công nợ khớp với lịch sử thanh toán</p>
            <div class="mt-4 space-y-1 text-xs text-gray-600">
                <div class="flex justify-between">
                    <span>Công nợ sai:</span>
                    <span class="{{ $debtPaymentSummary['debt_mismatches'] > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                        {{ $debtPaymentSummary['debt_mismatches'] }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>Đã TT sai:</span>
                    <span class="{{ $debtPaymentSummary['paid_mismatches'] > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                        {{ $debtPaymentSummary['paid_mismatches'] }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>Trạng thái sai:</span>
                    <span class="{{ $debtPaymentSummary['status_mismatches'] > 0 ? 'text-red-600 font-semibold' : 'text-green-600' }}">
                        {{ $debtPaymentSummary['status_mismatches'] }}
                    </span>
                </div>
            </div>
        </a>
    </div>

    {{-- Info --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400 text-lg"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Hướng dẫn sử dụng</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc pl-5 space-y-1">
                        <li>Nhấp vào từng thẻ để xem chi tiết các vấn đề phát hiện</li>
                        <li>Số liệu được tính toán realtime từ dữ liệu hiện tại trên hệ thống</li>
                        <li>Các vấn đề cần được xử lý thủ công bởi người quản lý</li>
                        <li><span class="text-green-600 font-semibold">✓ OK</span> = Không phát hiện bất thường, <span class="text-red-600 font-semibold">N lỗi</span> = Có N vấn đề cần kiểm tra</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
