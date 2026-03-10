@extends('layouts.app')

@section('title', 'Đánh giá kỹ năng - ' . $employee->name)
@section('page-title', 'Đánh giá kỹ năng nhân viên')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('employee-skills.show', $employee->id) }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $employee->name }}</h2>
                <p class="text-sm text-gray-500">{{ $employee->position }} — {{ $employee->department }}</p>
            </div>
        </div>
    </div>

    @if($categories->isEmpty() || $categories->sum(fn($c) => $c->skills->count()) === 0)
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <i class="fas fa-exclamation-triangle text-4xl text-yellow-400 mb-4"></i>
            <p class="text-gray-500 text-lg">Chưa có kỹ năng nào trong hệ thống.</p>
            <a href="{{ route('skills.create') }}" class="inline-flex items-center mt-4 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                <i class="fas fa-plus mr-2"></i>Tạo kỹ năng
            </a>
        </div>
    @else
        <form action="{{ route('employee-skills.update', $employee->id) }}" method="POST">
            @csrf
            @method('PUT')

            @foreach($categories as $category)
                @if($category->skills->isNotEmpty())
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">
                            <i class="fas fa-folder-open mr-2 text-primary"></i>{{ $category->name }}
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        @foreach($category->skills as $skill)
                            @php
                                $current = $currentSkills->get($skill->id);
                                $isChecked = $current !== null;
                                $currentLevel = $current ? $current->level : 3;
                                $currentNote = $current ? $current->note : '';
                            @endphp
                            <div class="border border-gray-200 rounded-lg p-4 skill-item {{ $isChecked ? 'bg-blue-50 border-blue-200' : '' }}" 
                                 id="skill-row-{{ $skill->id }}">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                    {{-- Checkbox + Skill name --}}
                                    <div class="flex items-center gap-3 sm:w-1/4">
                                        <input type="checkbox" 
                                               class="skill-checkbox w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary"
                                               id="check-{{ $skill->id }}"
                                               data-skill-id="{{ $skill->id }}"
                                               {{ $isChecked ? 'checked' : '' }}>
                                        <label for="check-{{ $skill->id }}" class="font-medium text-gray-800 cursor-pointer">
                                            {{ $skill->name }}
                                        </label>
                                    </div>

                                    {{-- Rating --}}
                                    <div class="sm:w-1/3 skill-fields {{ $isChecked ? '' : 'opacity-40 pointer-events-none' }}" 
                                         id="fields-{{ $skill->id }}">
                                        <div class="flex items-center gap-1">
                                            <span class="text-sm text-gray-500 mr-2">Mức độ:</span>
                                            @php $levels = \App\Models\EmployeeSkill::LEVELS; @endphp
                                            @for($i = 1; $i <= 5; $i++)
                                                <label class="cursor-pointer" title="{{ $levels[$i] }}">
                                                    <input type="radio" 
                                                           name="skills[{{ $skill->id }}][level]" 
                                                           value="{{ $i }}"
                                                           class="hidden star-radio"
                                                           data-skill="{{ $skill->id }}"
                                                           data-star="{{ $i }}"
                                                           {{ $currentLevel == $i ? 'checked' : '' }}>
                                                    <i class="fas fa-star text-lg star-icon-{{ $skill->id }} {{ $i <= $currentLevel ? 'text-yellow-400' : 'text-gray-300' }}" 
                                                       data-skill="{{ $skill->id }}" data-value="{{ $i }}"></i>
                                                </label>
                                            @endfor
                                            <span class="text-xs text-gray-400 ml-2 level-label-{{ $skill->id }}">{{ $levels[$currentLevel] }}</span>
                                        </div>
                                    </div>

                                    {{-- Note --}}
                                    <div class="sm:flex-1 skill-fields-note {{ $isChecked ? '' : 'opacity-40 pointer-events-none' }}" 
                                         id="note-{{ $skill->id }}">
                                        <input type="text"
                                               name="skills[{{ $skill->id }}][note]"
                                               value="{{ $currentNote }}"
                                               placeholder="Ghi chú..."
                                               class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            @endforeach

            <div class="flex justify-end gap-3">
                <a href="{{ route('employee-skills.show', $employee->id) }}" 
                   class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Hủy
                </a>
                <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-save mr-2"></i>Lưu đánh giá
                </button>
            </div>
        </form>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const levelLabels = @json(\App\Models\EmployeeSkill::LEVELS);

    // Checkbox toggle
    document.querySelectorAll('.skill-checkbox').forEach(function(cb) {
        cb.addEventListener('change', function() {
            const skillId = this.dataset.skillId;
            const row = document.getElementById('skill-row-' + skillId);
            const fields = document.getElementById('fields-' + skillId);
            const note = document.getElementById('note-' + skillId);

            if (this.checked) {
                row.classList.add('bg-blue-50', 'border-blue-200');
                fields.classList.remove('opacity-40', 'pointer-events-none');
                note.classList.remove('opacity-40', 'pointer-events-none');
                // Enable inputs
                fields.querySelectorAll('input').forEach(inp => inp.disabled = false);
                note.querySelectorAll('input').forEach(inp => inp.disabled = false);
            } else {
                row.classList.remove('bg-blue-50', 'border-blue-200');
                fields.classList.add('opacity-40', 'pointer-events-none');
                note.classList.add('opacity-40', 'pointer-events-none');
                // Disable inputs so they don't submit
                fields.querySelectorAll('input').forEach(inp => inp.disabled = true);
                note.querySelectorAll('input').forEach(inp => inp.disabled = true);
            }
        });

        // Initialize state: disable unchecked
        if (!cb.checked) {
            const skillId = cb.dataset.skillId;
            const fields = document.getElementById('fields-' + skillId);
            const note = document.getElementById('note-' + skillId);
            if (fields) fields.querySelectorAll('input').forEach(inp => inp.disabled = true);
            if (note) note.querySelectorAll('input').forEach(inp => inp.disabled = true);
        }
    });

    // Star rating click
    document.querySelectorAll('.star-radio').forEach(function(radio) {
        const star = radio.nextElementSibling; // the <i> tag
        if (star) {
            star.addEventListener('click', function() {
                const skillId = this.dataset.skill;
                const value = parseInt(this.dataset.value);
                radio.checked = true;

                // Update stars visual
                document.querySelectorAll('.star-icon-' + skillId).forEach(function(s) {
                    const sv = parseInt(s.dataset.value);
                    if (sv <= value) {
                        s.classList.remove('text-gray-300');
                        s.classList.add('text-yellow-400');
                    } else {
                        s.classList.remove('text-yellow-400');
                        s.classList.add('text-gray-300');
                    }
                });

                // Update label
                const label = document.querySelector('.level-label-' + skillId);
                if (label) label.textContent = levelLabels[value] || '';
            });
        }
    });
});
</script>
@endpush
@endsection
