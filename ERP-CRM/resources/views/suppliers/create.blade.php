@extends('layouts.app')

@section('title', 'Thêm nhà cung cấp')
@section('page-title', 'Thêm nhà cung cấp mới')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route('suppliers.index') }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại
        </a>
    </div>

    <form action="{{ route('suppliers.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-truck mr-2 text-primary"></i>Thông tin cơ bản</h2>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Mã NCC <span class="text-red-500">*</span></label>
                                <input type="text" name="code" id="code" value="{{ old('code') }}" required placeholder="VD: NCC001"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('code') border-red-500 @enderror">
                                @error('code')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Tên nhà cung cấp <span class="text-red-500">*</span></label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="Nhập tên NCC"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('name') border-red-500 @enderror">
                                @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" required placeholder="email@example.com"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('email') border-red-500 @enderror">
                                @error('email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Điện thoại <span class="text-red-500">*</span></label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone') }}" required placeholder="0123456789"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary @error('phone') border-red-500 @enderror">
                                @error('phone')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">Người liên hệ</label>
                                <input type="text" name="contact_person" id="contact_person" value="{{ old('contact_person') }}" placeholder="Tên người liên hệ"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="tax_code" class="block text-sm font-medium text-gray-700 mb-1">Mã số thuế</label>
                                <input type="text" name="tax_code" id="tax_code" value="{{ old('tax_code') }}" placeholder="Mã số thuế"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div class="md:col-span-2">
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Địa chỉ</label>
                                <input type="text" name="address" id="address" value="{{ old('address') }}" placeholder="Nhập địa chỉ"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="website" class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                                <input type="url" name="website" id="website" value="{{ old('website') }}" placeholder="https://example.com"
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div>
                                <label for="product_type" class="block text-sm font-medium text-gray-700 mb-1">Loại sản phẩm</label>
                                <input type="text" name="product_type" id="product_type" value="{{ old('product_type') }}" placeholder="VD: Điện tử, Văn phòng phẩm..."
                                       class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-sticky-note mr-2 text-primary"></i>Ghi chú</h2>
                    </div>
                    <div class="p-4">
                        <textarea name="note" id="note" rows="3" placeholder="Nhập ghi chú nếu có..."
                                  class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">{{ old('note') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-4 py-3 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-gray-800"><i class="fas fa-credit-card mr-2 text-primary"></i>Thanh toán</h2>
                    </div>
                    <div class="p-4">
                        <label for="payment_terms" class="block text-sm font-medium text-gray-700 mb-1">Điều khoản thanh toán (ngày)</label>
                        <input type="number" name="payment_terms" id="payment_terms" value="{{ old('payment_terms', 30) }}" min="0"
                               class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary focus:border-primary">
                        <p class="mt-1 text-xs text-gray-500">Số ngày được phép thanh toán sau khi nhận hàng</p>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-4">
                    <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors font-medium text-sm">
                        <i class="fas fa-save mr-2"></i>Lưu nhà cung cấp
                    </button>
                    <a href="{{ route('suppliers.index') }}" class="mt-2 w-full inline-block text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 text-sm">
                        Hủy bỏ
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
