@extends('layouts.app')

@section('title', 'Kỳ Đánh giá KPI Bộ phận')
@section('page-title', 'Kỳ Đánh giá KPI Bộ phận')

@section('content')
<div class="w-full">
    <!-- @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-r shadow-sm">
            <p class="font-medium"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</p>
        </div>
    @endif -->

    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="font-semibold text-gray-800">Danh sách Kỳ đánh giá</h2>
            <a href="{{ route('department-kpis.create') }}" class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-dark transition-colors">
                <i class="fas fa-plus mr-2"></i>Tạo kỳ đánh giá mới
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-gray-700 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-4 py-3">Tên kỳ đánh giá</th>
                        <th class="px-4 py-3">Bộ phận</th>
                        <th class="px-4 py-3">Kỳ (Tháng/Năm)</th>
                        <th class="px-4 py-3 text-center">Tổng điểm</th>
                        <th class="px-4 py-3 text-center">Trạng thái</th>
                        <th class="px-4 py-3">Người đánh giá cuối</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($kpis as $kpi)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                <a href="{{ route('department-kpis.show', $kpi) }}" class="text-primary hover:underline">{{ $kpi->title }}</a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">{{ $kpi->department }}</span>
                            </td>
                            <td class="px-4 py-3">{{ $kpi->evaluation_period }}</td>
                            <td class="px-4 py-3 text-center font-bold {{ $kpi->total_score >= 80 ? 'text-green-600' : ($kpi->total_score >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ number_format($kpi->total_score, 1) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $colors = [
                                        'draft' => 'bg-gray-100 text-gray-800',
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'completed' => 'bg-blue-100 text-blue-800',
                                    ];
                                    $labels = [
                                        'draft' => 'Bản nháp',
                                        'pending' => 'Chờ duyệt',
                                        'approved' => 'Đã duyệt',
                                        'completed' => 'Hoàn tất',
                                    ];
                                @endphp
                                <span class="text-xs font-medium px-2.5 py-0.5 rounded {{ $colors[$kpi->status] ?? 'bg-gray-100' }}">
                                    {{ $labels[$kpi->status] ?? $kpi->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $kpi->evaluator->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('department-kpis.show', $kpi) }}" class="text-gray-600 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 p-1.5 rounded inline-flex transition-colors" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('department-kpis.edit', $kpi) }}" class="text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 p-1.5 rounded inline-flex transition-colors" title="Đánh giá/Sửa">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('department-kpis.destroy', $kpi) }}" method="POST" class="inline-block" onsubmit="return confirm('Xác nhận xóa kỳ KPI này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 hover:bg-red-100 p-1.5 rounded inline-flex transition-colors" title="Xóa">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-chart-line text-4xl text-gray-300 mb-3"></i>
                                    <p>Chưa có dữ liệu kỳ đánh giá KPI nào.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $kpis->links() }}
        </div>
    </div>
</div>
@endsection
