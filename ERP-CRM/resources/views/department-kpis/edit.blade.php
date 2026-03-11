@extends('layouts.app')

@section('title', 'Đánh giá KPI & Cập nhật')
@section('page-title', 'Đánh giá / Cập nhật Kỳ KPI')

@section('content')
<div class="w-full">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 rounded-t-lg">
            <div class="flex items-center gap-3">
                <a href="{{ route('department-kpis.index') }}" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h2 class="font-bold text-gray-800 text-lg">{{ $departmentKpi->title }}</h2>
                    <p class="text-sm text-gray-500">Bộ phận: <strong>{{ $departmentKpi->department }}</strong> | Kỳ: {{ $departmentKpi->evaluation_period }}</p>
                </div>
            </div>
            <div>
                <span class="bg-indigo-100 text-indigo-800 text-sm font-semibold px-3 py-1 rounded-full">
                    Tổng điểm hiện tại: {{ number_format($departmentKpi->total_score, 1) }}
                </span>
            </div>
        </div>

        <form action="{{ route('department-kpis.update', $departmentKpi) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')

            <!-- General Info update -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 pb-6 border-b border-gray-100">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tựa đề KPI</label>
                    <input type="text" name="title" value="{{ old('title', $departmentKpi->title) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                    <input type="hidden" name="department" value="{{ $departmentKpi->department }}">
                    <input type="hidden" name="evaluation_period" value="{{ $departmentKpi->evaluation_period }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái đánh giá</label>
                    <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">
                        <option value="draft" {{ $departmentKpi->status == 'draft' ? 'selected' : '' }}>Bản nháp (Draft)</option>
                        <option value="pending" {{ $departmentKpi->status == 'pending' ? 'selected' : '' }}>Chờ đánh giá (Pending)</option>
                        <option value="approved" {{ $departmentKpi->status == 'approved' ? 'selected' : '' }}>Đã duyệt / Xác nhận (Approved)</option>
                        <option value="completed" {{ $departmentKpi->status == 'completed' ? 'selected' : '' }}>Hoàn tất kì (Completed)</option>
                    </select>
                </div>
            </div>

            <!-- Scoring Area -->
            <div class="mb-6">
                <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                    <i class="fas fa-tasks text-gray-400 mr-2"></i>Bảng đánh giá các tiêu chí
                </h3>
                
                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-gray-50 text-gray-700 font-medium">
                            <tr>
                                <th class="px-4 py-3 min-w-[200px]">Tiêu chí</th>
                                <th class="px-4 py-3 w-32">Mục tiêu (Target)</th>
                                <th class="px-4 py-3 w-24">Trọng số</th>
                                <th class="px-4 py-3 min-w-[150px]">Kết quả thực tế <span class="text-blue-500">*</span></th>
                                <th class="px-4 py-3 w-32">Điểm đạt (0-100) <span class="text-red-500">*</span></th>
                                <th class="px-4 py-3 min-w-[200px]">Ghi chú / Nhận xét</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($departmentKpi->results as $index => $result)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-800 whitespace-normal">{{ $result->criterion_name }}</p>
                                        <input type="hidden" name="results[{{ $index }}][id]" value="{{ $result->id }}">
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-600 bg-gray-50 text-center rounded">{{ $result->target ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-center font-semibold text-indigo-700 bg-indigo-50 border-x border-white">
                                        {{ number_format($result->weight, 0) }}%
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" name="results[{{ $index }}][actual_value]" value="{{ old("results.{$index}.actual_value", $result->actual_value) }}" placeholder="VD: 1.2 tỷ, Lỗi 2%" class="w-full border border-gray-300 rounded px-2 py-1.5 focus:ring-1 focus:ring-blue-500">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" name="results[{{ $index }}][score]" value="{{ old("results.{$index}.score", $result->score) }}" step="0.1" min="0" required class="w-full border border-gray-300 rounded px-2 py-1.5 focus:ring-1 focus:ring-red-500 font-bold score-input text-right" placeholder="0">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="text" name="results[{{ $index }}][note]" value="{{ old("results.{$index}.note", $result->note) }}" placeholder="Giải trình..." class="w-full border border-gray-300 rounded px-2 py-1.5 text-gray-600 focus:ring-0">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-100">
                                <td colspan="4" class="px-4 py-3 text-right font-semibold text-gray-700 uppercase text-xs tracking-wider">Tổng điểm thực nhận:</td>
                                <td class="px-4 py-3 font-bold text-lg text-right text-red-600" id="live_total_score">
                                    {{ number_format($departmentKpi->total_score, 1) }}
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú tổng kết Kỳ KPI</label>
                <textarea name="note" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary">{{ old('note', $departmentKpi->note) }}</textarea>
            </div>

            <div class="flex gap-3 pt-4 border-t border-gray-200">
                <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-shadow shadow hover:shadow-md flex items-center">
                    <i class="fas fa-check-double mr-2"></i> Lưu đánh giá KPI
                </button>
                <a href="{{ route('department-kpis.show', $departmentKpi) }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium transition-colors">
                    Hủy sửa
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const scoreInputs = document.querySelectorAll('.score-input');
    const totalScoreEl = document.getElementById('live_total_score');

    function calculateTotal() {
        let total = 0;
        scoreInputs.forEach(input => {
            total += parseFloat(input.value || 0);
        });
        totalScoreEl.innerText = total.toFixed(1);
        
        if (total >= 80) totalScoreEl.className = 'px-4 py-3 font-bold text-lg text-right text-green-600';
        else if (total >= 50) totalScoreEl.className = 'px-4 py-3 font-bold text-lg text-right text-yellow-600';
        else totalScoreEl.className = 'px-4 py-3 font-bold text-lg text-right text-red-600';
    }

    scoreInputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });
});
</script>
@endpush
@endsection
