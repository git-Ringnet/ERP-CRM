@extends('layouts.app')

@section('title', 'Thêm mới Cơ hội')
@section('page-title', 'Thêm mới Cơ hội')

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-funnel-dollar text-yellow-500 mr-2"></i>Thêm mới Cơ hội
            </h2>
            <a href="{{ route('opportunities.index') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>

        <form action="{{ route('opportunities.store') }}" method="POST" class="p-4">
            @csrf

            @if(isset($prefill))
                <input type="hidden" name="ref_type" value="{{ $prefill['ref_type'] ?? '' }}">
                <input type="hidden" name="ref_id" value="{{ $prefill['ref_id'] ?? '' }}">
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Tên cơ hội <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                        placeholder="Ví dụ: Dự án cung cấp thiết bị IT cho..."
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                        required>
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-1">
                    <label for="customer_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Khách hàng <span class="text-red-500">*</span>
                    </label>
                    <select name="customer_id" id="customer_id"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                        required>
                        <option value="">-- Chọn khách hàng --</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id', $prefill['customer_id'] ?? '') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }} ({{ $customer->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-1">
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                        Giá trị dự kiến (VND) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="amount" id="amount" value="{{ old('amount', 0) }}"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>

                <div class="col-span-1">
                    <label for="stage" class="block text-sm font-medium text-gray-700 mb-1">
                        Giai đoạn <span class="text-red-500">*</span>
                    </label>
                    <select name="stage" id="stage"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                        required>
                        <option value="new" {{ old('stage') == 'new' ? 'selected' : '' }}>Mới</option>
                        <option value="qualification" {{ old('stage') == 'qualification' ? 'selected' : '' }}>Đánh giá
                        </option>
                        <option value="proposal" {{ old('stage') == 'proposal' ? 'selected' : '' }}>Báo giá</option>
                        <option value="negotiation" {{ old('stage') == 'negotiation' ? 'selected' : '' }}>Đàm phán</option>
                        <option value="won" {{ old('stage') == 'won' ? 'selected' : '' }}>Thành công</option>
                        <option value="lost" {{ old('stage') == 'lost' ? 'selected' : '' }}>Thất bại</option>
                    </select>
                </div>

                <div class="col-span-1">
                    <label for="probability" class="block text-sm font-medium text-gray-700 mb-1">
                        Xác suất thành công (%)
                    </label>
                    <input type="number" name="probability" id="probability" value="{{ old('probability', 10) }}" min="0"
                        max="100"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="col-span-1">
                    <label for="expected_close_date" class="block text-sm font-medium text-gray-700 mb-1">
                        Ngày dự kiến chốt
                    </label>
                    <input type="date" name="expected_close_date" id="expected_close_date"
                        value="{{ old('expected_close_date') }}"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="col-span-1">
                    <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-1">
                        Người phụ trách
                    </label>
                    <select name="assigned_to" id="assigned_to"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="">-- Chọn nhân viên --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Mô tả chi tiết
                    </label>
                    <textarea name="description" id="description" rows="3"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                <a href="{{ route('opportunities.index') }}"
                    class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-times mr-1"></i> Hủy
                </a>
                <button type="submit"
                    class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-1"></i> Lưu
                </button>
            </div>
        </form>
    </div>
@endsection