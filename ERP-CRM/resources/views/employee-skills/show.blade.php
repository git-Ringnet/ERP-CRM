@extends('layouts.app')

@section('title', 'Kỹ năng - ' . $employee->name)
@section('page-title', 'Kỹ năng nhân viên')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('employees.show', $employee->id) }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $employee->name }}</h2>
                <p class="text-sm text-gray-500">{{ $employee->position }} — {{ $employee->department }}</p>
            </div>
        </div>
        <a href="{{ route('employee-skills.edit', $employee->id) }}"
           class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
            <i class="fas fa-star mr-2"></i>Đánh giá kỹ năng
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
        </div>
    @endif

    @php
        $hasAnySkill = false;
        foreach ($categories as $cat) {
            foreach ($cat->skills as $skill) {
                if ($skill->employeeSkills->isNotEmpty()) {
                    $hasAnySkill = true;
                    break 2;
                }
            }
        }
    @endphp

    @if(!$hasAnySkill)
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <i class="fas fa-chart-radar text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg">Chưa có đánh giá kỹ năng nào.</p>
            <p class="text-gray-400 mt-1">Nhấn "Đánh giá kỹ năng" để bắt đầu.</p>
        </div>
    @else
        {{-- Skill Summary Cards --}}
        @php
            $allEmployeeSkills = collect();
            foreach ($categories as $cat) {
                foreach ($cat->skills as $skill) {
                    foreach ($skill->employeeSkills as $es) {
                        $allEmployeeSkills->push($es);
                    }
                }
            }
            $avgLevel = $allEmployeeSkills->avg('level');
            $totalSkills = $allEmployeeSkills->count();
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-list-check text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Tổng kỹ năng</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $totalSkills }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Trung bình</p>
                        <p class="text-2xl font-bold text-gray-800">{{ number_format($avgLevel, 1) }}/5</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-trophy text-yellow-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Thành thạo (5/5)</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $allEmployeeSkills->where('level', 5)->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Skill by Category --}}
        @foreach($categories as $category)
            @php
                $categorySkills = $category->skills->filter(function($s) {
                    return $s->employeeSkills->isNotEmpty();
                });
            @endphp
            @if($categorySkills->isNotEmpty())
            <div class="bg-white rounded-lg shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-folder-open mr-2 text-primary"></i>{{ $category->name }}
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($categorySkills as $skill)
                            @php $es = $skill->employeeSkills->first(); @endphp
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-sm transition-shadow">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium text-gray-800">{{ $skill->name }}</span>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                        @if($es->level == 1) bg-gray-100 text-gray-600
                                        @elseif($es->level == 2) bg-blue-100 text-blue-700
                                        @elseif($es->level == 3) bg-indigo-100 text-indigo-700
                                        @elseif($es->level == 4) bg-yellow-100 text-yellow-700
                                        @else bg-green-100 text-green-700
                                        @endif">
                                        {{ \App\Models\EmployeeSkill::LEVELS[$es->level] ?? 'N/A' }}
                                    </span>
                                </div>
                                {{-- Star Rating --}}
                                <div class="flex items-center gap-1 mb-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star text-sm {{ $i <= $es->level ? 'text-yellow-400' : 'text-gray-200' }}"></i>
                                    @endfor
                                    <span class="text-xs text-gray-400 ml-1">{{ $es->level }}/5</span>
                                </div>
                                @if($es->note)
                                    <p class="text-sm text-gray-500 italic">{{ $es->note }}</p>
                                @endif
                                @if($es->evaluated_at)
                                    <p class="text-xs text-gray-400 mt-2">
                                        <i class="fas fa-calendar mr-1"></i>Đánh giá: {{ $es->evaluated_at->format('d/m/Y') }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        @endforeach
    @endif
</div>
@endsection
