@extends('layouts.app')

@section('title', 'Sửa kỹ năng')
@section('page-title', 'Sửa kỹ năng')

@section('content')
<div class="space-y-6">
    <a href="{{ route('skills.index') }}" 
       class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
        <i class="fas fa-arrow-left mr-2"></i>Quay lại
    </a>

    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-edit mr-2 text-primary"></i>Sửa kỹ năng: {{ $skill->name }}
            </h2>
        </div>
        <div class="p-6">
            <form action="{{ route('skills.update', $skill->id) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                {{-- Chọn danh mục --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Danh mục <span class="text-red-500">*</span></label>
                    <select name="skill_category_id" required
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20">
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ $skill->skill_category_id == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('skill_category_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Tên kỹ năng --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên kỹ năng <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $skill->name) }}" required
                           class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20">
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Mô tả --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                    <textarea name="description" rows="3"
                              class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20">{{ old('description', $skill->description) }}</textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('skills.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Hủy
                    </a>
                    <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="fas fa-save mr-2"></i>Cập nhật
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
