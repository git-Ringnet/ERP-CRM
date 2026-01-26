@extends('layouts.app')

@section('title', 'Chỉnh sửa Cơ hội')
@section('page-title', 'Chỉnh sửa Cơ hội')

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-funnel-dollar text-yellow-500 mr-2"></i>Chỉnh sửa Cơ hội: {{ $opportunity->name }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('quotations.create', ['customer_id' => $opportunity->customer_id, 'title' => $opportunity->name]) }}"
                    class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition ease-in-out duration-150">
                    <i class="fas fa-file-invoice-dollar mr-2"></i> Tạo Báo Giá
                </a>
                <form action="{{ route('opportunities.destroy', $opportunity) }}" method="POST"
                    onsubmit="return confirm('Bạn có chắc chắn muốn xóa cơ hội này?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 transition ease-in-out duration-150">
                        <i class="fas fa-trash mr-2"></i> Xóa
                    </button>
                </form>
                <a href="{{ route('opportunities.index') }}"
                    class="text-gray-600 hover:text-gray-900 flex items-center px-4 py-2">
                    <i class="fas fa-arrow-left mr-1"></i> Quay lại
                </a>
            </div>
        </div>

        <form action="{{ route('opportunities.update', $opportunity) }}" method="POST" class="p-4">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Tên cơ hội <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $opportunity->name) }}"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
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
                            <option value="{{ $customer->id }}" {{ old('customer_id', $opportunity->customer_id) == $customer->id ? 'selected' : '' }}>{{ $customer->name }}
                                ({{ $customer->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-1">
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                        Giá trị dự kiến (VND) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="amount" id="amount" value="{{ old('amount', $opportunity->amount) }}"
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
                        <option value="new" {{ old('stage', $opportunity->stage) == 'new' ? 'selected' : '' }}>Mới</option>
                        <option value="qualification" {{ old('stage', $opportunity->stage) == 'qualification' ? 'selected' : '' }}>Đánh giá</option>
                        <option value="proposal" {{ old('stage', $opportunity->stage) == 'proposal' ? 'selected' : '' }}>Báo
                            giá</option>
                        <option value="negotiation" {{ old('stage', $opportunity->stage) == 'negotiation' ? 'selected' : '' }}>Đàm phán</option>
                        <option value="won" {{ old('stage', $opportunity->stage) == 'won' ? 'selected' : '' }}>Thành công
                        </option>
                        <option value="lost" {{ old('stage', $opportunity->stage) == 'lost' ? 'selected' : '' }}>Thất bại
                        </option>
                    </select>
                </div>

                <div class="col-span-1">
                    <label for="probability" class="block text-sm font-medium text-gray-700 mb-1">
                        Xác suất thành công (%)
                    </label>
                    <input type="number" name="probability" id="probability"
                        value="{{ old('probability', $opportunity->probability) }}" min="0" max="100"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="col-span-1">
                    <label for="expected_close_date" class="block text-sm font-medium text-gray-700 mb-1">
                        Ngày dự kiến chốt
                    </label>
                    <input type="date" name="expected_close_date" id="expected_close_date"
                        value="{{ old('expected_close_date', $opportunity->expected_close_date ? $opportunity->expected_close_date->format('Y-m-d') : '') }}"
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
                            <option value="{{ $user->id }}" {{ old('assigned_to', $opportunity->assigned_to) == $user->id ? 'selected' : '' }}>{{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        Mô tả chi tiết
                    </label>
                    <textarea name="description" id="description" rows="3"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description', $opportunity->description) }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                <a href="{{ route('opportunities.index') }}"
                    class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-times mr-1"></i> Hủy
                </a>
                <button type="submit"
                    class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-1"></i> Cập nhật
                </button>
            </div>
        </form>
    </div>
@endsection