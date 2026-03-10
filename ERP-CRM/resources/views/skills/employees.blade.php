@extends('layouts.app')

@section('title', 'Gán nhân viên: ' . $skill->name)
@section('page-title', 'Gán kỹ năng cho nhân viên')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('skills.show', $skill->id) }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
            <div>
                <h2 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-users-cog mr-2 text-primary"></i>
                    Gán kỹ năng: {{ $skill->name }}
                </h2>
                <p class="text-sm text-gray-500">Danh mục: {{ $skill->category->name }}</p>
            </div>
        </div>
    </div>

    @if($employees->isEmpty())
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <i class="fas fa-user-slash text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 text-lg">Hệ thống chưa có nhân viên nào.</p>
        </div>
    @else
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg flex justify-between items-center">
                <p class="text-sm text-gray-600">Đánh dấu vào checkbox trước tên nhân viên để gán / bỏ gán kỹ năng này.</p>
                <div class="flex items-center gap-2 text-sm text-blue-600 cursor-pointer hover:text-blue-800 font-medium" id="selectAllBtn">
                    <i class="fas fa-check-double"></i> Chọn/Bỏ chọn tất cả
                </div>
            </div>

            <form action="{{ route('skills.employees.update', $skill->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="p-6">
                    <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                        @foreach($employees as $employee)
                            @php
                                $es = $currentSkills->get($employee->id);
                                $isChecked = $es !== null;
                                $currentLevel = $es ? $es->level : 3;
                                $currentNote = $es ? $es->note : '';
                            @endphp
                            <div class="border border-gray-200 rounded-lg p-4 emp-row transition-colors {{ $isChecked ? 'bg-blue-50 border-blue-200' : 'hover:bg-gray-50' }}" 
                                 id="row-{{ $employee->id }}">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                                    {{-- Employee Info + Checkbox --}}
                                    <div class="flex items-center gap-3 sm:w-1/3">
                                        <input type="checkbox" 
                                               class="emp-checkbox w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary"
                                               id="check-{{ $employee->id }}"
                                               data-emp-id="{{ $employee->id }}"
                                               {{ $isChecked ? 'checked' : '' }}>
                                        <label for="check-{{ $employee->id }}" class="cursor-pointer">
                                            <p class="font-bold text-gray-800">{{ $employee->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $employee->position ?? 'Nhân viên' }} — {{ $employee->department ?? 'N/A' }}</p>
                                        </label>
                                    </div>

                                    {{-- Rating --}}
                                    <div class="sm:w-1/3 emp-fields {{ $isChecked ? '' : 'opacity-40 pointer-events-none' }}" 
                                         id="fields-{{ $employee->id }}">
                                        <div class="flex items-center gap-1">
                                            <span class="text-sm text-gray-500 mr-2">Mức độ:</span>
                                            @php $levels = \App\Models\EmployeeSkill::LEVELS; @endphp
                                            @for($i = 1; $i <= 5; $i++)
                                                <label class="cursor-pointer" title="{{ $levels[$i] }}">
                                                    <input type="radio" 
                                                           name="employees[{{ $employee->id }}][level]" 
                                                           value="{{ $i }}"
                                                           class="hidden star-radio"
                                                           data-emp="{{ $employee->id }}"
                                                           data-star="{{ $i }}"
                                                           {{ $currentLevel == $i ? 'checked' : '' }}>
                                                    <i class="fas fa-star text-lg star-icon-{{ $employee->id }} {{ $i <= $currentLevel ? 'text-yellow-400' : 'text-gray-300' }}" 
                                                       data-emp="{{ $employee->id }}" data-value="{{ $i }}"></i>
                                                </label>
                                            @endfor
                                            <span class="text-xs text-gray-400 ml-2 font-medium level-label-{{ $employee->id }} w-24">
                                                {{ $levels[$currentLevel] }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Note --}}
                                    <div class="sm:flex-1 emp-note-fields {{ $isChecked ? '' : 'opacity-40 pointer-events-none' }}" 
                                         id="note-{{ $employee->id }}">
                                        <input type="text"
                                               name="employees[{{ $employee->id }}][note]"
                                               value="{{ $currentNote }}"
                                               placeholder="Ghi chú đánh giá..."
                                               class="w-full text-sm border-gray-300 rounded-lg shadow-sm focus:border-primary focus:ring focus:ring-primary/20">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Footer Actions --}}
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-lg flex justify-between items-center sticky bottom-0">
                    <p class="text-sm text-gray-500"><span id="selectedCount">{{ $currentSkills->count() }}</span> nhân viên được chọn</p>
                    <div class="flex gap-3">
                        <a href="{{ route('skills.show', $skill->id) }}" 
                           class="px-4 py-2 border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-50 transition-colors">
                            Hủy
                        </a>
                        <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors shadow-sm">
                            <i class="fas fa-save mr-2"></i>Lưu danh sách
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const levelLabels = @json(\App\Models\EmployeeSkill::LEVELS);
    
    function updateSelectedCount() {
        const count = document.querySelectorAll('.emp-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = count;
    }

    // Toggle row function
    function toggleRow(checkbox) {
        const empId = checkbox.dataset.empId;
        const row = document.getElementById('row-' + empId);
        const fields = document.getElementById('fields-' + empId);
        const note = document.getElementById('note-' + empId);

        if (checkbox.checked) {
            row.classList.add('bg-blue-50', 'border-blue-200');
            row.classList.remove('hover:bg-gray-50');
            fields.classList.remove('opacity-40', 'pointer-events-none');
            note.classList.remove('opacity-40', 'pointer-events-none');
            fields.querySelectorAll('input').forEach(inp => inp.disabled = false);
            note.querySelectorAll('input').forEach(inp => inp.disabled = false);
        } else {
            row.classList.remove('bg-blue-50', 'border-blue-200');
            row.classList.add('hover:bg-gray-50');
            fields.classList.add('opacity-40', 'pointer-events-none');
            note.classList.add('opacity-40', 'pointer-events-none');
            fields.querySelectorAll('input').forEach(inp => inp.disabled = true);
            note.querySelectorAll('input').forEach(inp => inp.disabled = true);
        }
    }

    // Initialize state
    document.querySelectorAll('.emp-checkbox').forEach(function(cb) {
        toggleRow(cb); // run on load
        
        cb.addEventListener('change', function() {
            toggleRow(this);
            updateSelectedCount();
        });
    });

    // Select All
    let selectAllState = false;
    document.getElementById('selectAllBtn').addEventListener('click', function() {
        selectAllState = !selectAllState;
        document.querySelectorAll('.emp-checkbox').forEach(cb => {
            if (cb.checked !== selectAllState) {
                cb.checked = selectAllState;
                toggleRow(cb);
            }
        });
        updateSelectedCount();
    });

    // Star rating hover & click
    document.querySelectorAll('.star-radio').forEach(function(radio) {
        const star = radio.nextElementSibling;
        if (star) {
            star.addEventListener('click', function() {
                const empId = this.dataset.emp;
                const value = parseInt(this.dataset.value);
                radio.checked = true;

                // Update stars color manually
                document.querySelectorAll('.star-icon-' + empId).forEach(function(s) {
                    const sv = parseInt(s.dataset.value);
                    if (sv <= value) {
                        s.classList.remove('text-gray-300');
                        s.classList.add('text-yellow-400');
                    } else {
                        s.classList.remove('text-yellow-400');
                        s.classList.add('text-gray-300');
                    }
                });

                // Update label text
                const label = document.querySelector('.level-label-' + empId);
                if (label) label.textContent = levelLabels[value] || '';
            });
        }
    });
});
</script>
@endpush
@endsection
