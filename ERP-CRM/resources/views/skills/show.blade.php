@extends('layouts.app')

@section('title', 'Chi tiết Kỹ năng: ' . $skill->name)
@section('page-title', 'Chi tiết Kỹ năng')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('skills.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $skill->name }}</h2>
                <p class="text-sm text-gray-500">Danh mục: {{ $skill->category->name }}</p>
            </div>
        </div>
        <a href="{{ route('skills.employees', $skill->id) }}"
           class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
            <i class="fas fa-users-cog mr-2"></i>Gán nhân viên / Đánh giá
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    {{-- Skill Details Card --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 border-b pb-3 mb-4">Thông tin Kỹ năng</h3>
        <p class="text-gray-700 mb-2"><strong>Mô tả:</strong> {{ $skill->description ?: 'Không có mô tả' }}</p>
        <p class="text-gray-700"><strong>Số nhân viên sở hữu:</strong> {{ $skill->employeeSkills->count() }} người</p>
    </div>

    {{-- Employee List --}}
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Danh sách nhân viên có kỹ năng này</h3>
        </div>
        
        @if($skill->employeeSkills->isEmpty())
            <div class="p-12 text-center text-gray-500">
                <i class="fas fa-users-slash text-4xl text-gray-300 mb-4"></i>
                <p class="text-lg">Chưa có nhân viên nào được gán kỹ năng này.</p>
                <a href="{{ route('skills.employees', $skill->id) }}" class="inline-block mt-4 text-primary hover:underline">
                    Tiến hành gán nhân viên ngay
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 font-medium text-gray-600">Nhân viên</th>
                            <th class="px-6 py-3 font-medium text-gray-600">Phòng ban</th>
                            <th class="px-6 py-3 font-medium text-gray-600">Mức độ</th>
                            <th class="px-6 py-3 font-medium text-gray-600">Ghi chú</th>
                            <th class="px-6 py-3 font-medium text-gray-600">Cập nhật lúc</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($skill->employeeSkills as $es)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 font-medium text-gray-900">
                                <a href="{{ route('employees.show', $es->user_id) }}" class="hover:text-primary transition-colors">
                                    {{ $es->user->name }}
                                </a>
                            </td>
                            <td class="px-6 py-3 text-gray-500">{{ $es->user->department ?? '—' }}</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-1">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full mr-2
                                        @if($es->level == 1) bg-gray-100 text-gray-600
                                        @elseif($es->level == 2) bg-blue-100 text-blue-700
                                        @elseif($es->level == 3) bg-indigo-100 text-indigo-700
                                        @elseif($es->level == 4) bg-yellow-100 text-yellow-700
                                        @else bg-green-100 text-green-700
                                        @endif">
                                        {{ \App\Models\EmployeeSkill::LEVELS[$es->level] ?? 'N/A' }}
                                    </span>
                                    <span class="text-yellow-400 text-xs">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star {{ $i <= $es->level ? '' : 'text-gray-200' }}"></i>
                                        @endfor
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-gray-500 italic">{{ $es->note ?: '—' }}</td>
                            <td class="px-6 py-3 text-gray-500">{{ $es->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
