@extends('layouts.app')

@section('title', 'Thêm mới Đấu mối')
@section('page-title', 'Thêm mới Đấu mối')

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-bullseye text-cyan-500 mr-2"></i>Thêm mới Đấu mối
            </h2>
            <a href="{{ route('leads.index') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>

        <form action="{{ route('leads.store') }}" method="POST" class="p-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="col-span-1">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Tên người liên hệ <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                        required>
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="col-span-1">
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">
                        Tên công ty / Tổ chức
                    </label>
                    <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('company_name') border-red-500 @enderror">
                    @error('company_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="col-span-1">
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                        Số điện thoại
                    </label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('phone') border-red-500 @enderror">
                    @error('phone')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="col-span-1">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                        Email
                    </label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror">
                    @error('email')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="col-span-1">
                    <label for="source" class="block text-sm font-medium text-gray-700 mb-1">
                        Nguồn
                    </label>
                    <select name="source" id="source"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white @error('source') border-red-500 @enderror">
                        <option value="">-- Chọn nguồn --</option>
                        <option value="Website" {{ old('source') == 'Website' ? 'selected' : '' }}>Website</option>
                        <option value="Facebook" {{ old('source') == 'Facebook' ? 'selected' : '' }}>Facebook</option>
                        <option value="Zalo" {{ old('source') == 'Zalo' ? 'selected' : '' }}>Zalo</option>
                        <option value="Giới thiệu" {{ old('source') == 'Giới thiệu' ? 'selected' : '' }}>Giới thiệu</option>
                        <option value="Khác" {{ old('source') == 'Khác' ? 'selected' : '' }}>Khác</option>
                    </select>
                    @error('source')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="col-span-1">
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                        Trạng thái <span class="text-red-500">*</span>
                    </label>
                    <select name="status" id="status"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white @error('status') border-red-500 @enderror"
                        required>
                        <option value="new" {{ old('status') == 'new' ? 'selected' : '' }}>Mới</option>
                        <option value="contacted" {{ old('status') == 'contacted' ? 'selected' : '' }}>Đã liên hệ</option>
                        <option value="qualified" {{ old('status') == 'qualified' ? 'selected' : '' }}>Đủ điều kiện</option>
                        <option value="lost" {{ old('status') == 'lost' ? 'selected' : '' }}>Thất bại</option>
                    </select>
                    @error('status')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="col-span-1">
                    <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-1">
                        Người phụ trách
                    </label>
                    <select name="assigned_to" id="assigned_to"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white @error('assigned_to') border-red-500 @enderror">
                        <option value="">-- Chọn nhân viên --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_to')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">
                        Ghi chú
                    </label>
                    <textarea name="notes" id="notes" rows="3"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                <a href="{{ route('leads.index') }}"
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