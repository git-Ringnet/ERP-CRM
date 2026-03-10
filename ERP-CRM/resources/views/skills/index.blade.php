@extends('layouts.app')

@section('title', 'Quản lý Kỹ năng')
@section('page-title', 'Quản lý Kỹ năng (Skillset)')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <p class="text-gray-600">Quản lý danh mục & kỹ năng dùng để đánh giá năng lực nhân viên.</p>
        <a href="{{ route('skills.create') }}"
           class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
            <i class="fas fa-plus mr-2"></i>Thêm kỹ năng
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @if($categories->isEmpty())
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <i class="fas fa-graduation-cap text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg">Chưa có kỹ năng nào.</p>
            <p class="text-gray-400 mt-1">Nhấn "Thêm kỹ năng" để bắt đầu tạo danh sách kỹ năng.</p>
        </div>
    @else
        @foreach($categories as $category)
        <div class="bg-white rounded-lg shadow-sm">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-folder-open mr-2 text-primary"></i>{{ $category->name }}
                    <span class="text-sm font-normal text-gray-500 ml-2">({{ $category->skills->count() }} kỹ năng)</span>
                </h2>
                <div class="flex items-center gap-2">
                    @if($category->skills->isEmpty())
                    <form action="{{ route('skill-categories.destroy', $category->id) }}" method="POST" 
                          onsubmit="return confirm('Xóa danh mục {{ $category->name }}?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700 text-sm">
                            <i class="fas fa-trash mr-1"></i>Xóa danh mục
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            @if($category->skills->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 font-medium text-gray-600">Tên kỹ năng</th>
                            <th class="px-6 py-3 font-medium text-gray-600">Mô tả</th>
                            <th class="px-6 py-3 font-medium text-gray-600 text-center w-40">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($category->skills as $skill)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-900">
                                <a href="{{ route('skills.show', $skill->id) }}" class="hover:text-primary transition-colors">
                                    {{ $skill->name }}
                                </a>
                            </td>
                            <td class="px-6 py-3 text-gray-500">{{ $skill->description ?: '—' }}</td>
                            <td class="px-6 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('skills.show', $skill->id) }}" 
                                       class="text-indigo-600 hover:text-indigo-800" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('skills.employees', $skill->id) }}" 
                                       class="text-green-600 hover:text-green-800" title="Gán nhân viên">
                                        <i class="fas fa-users-cog"></i>
                                    </a>
                                    <a href="{{ route('skills.edit', $skill->id) }}" 
                                       class="text-blue-600 hover:text-blue-800" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('skills.destroy', $skill->id) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Xóa kỹ năng {{ $skill->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700" title="Xóa">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="p-6 text-center text-gray-400">
                <p>Danh mục này chưa có kỹ năng nào.</p>
            </div>
            @endif
        </div>
        @endforeach
    @endif
</div>
@endsection
