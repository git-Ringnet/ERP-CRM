@extends('layouts.app')

@section('title', 'Chi tiết Kết quả KPI')
@section('page-title', 'Báo cáo Chi tiết KPI Bộ phận')

@section('content')
<div class="w-full">
 

    <div class="bg-white rounded-lg shadow-sm print:shadow-none">
        
        <!-- Header Actions (Hidden in print) -->
        <div class="p-4 border-b border-gray-200 flex justify-between items-center bg-gray-50 rounded-t-lg print:hidden">
            <div class="flex items-center gap-3">
                <a href="{{ route('department-kpis.index') }}" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left"></i> Về danh sách
                </a>
            </div>
            <div class="space-x-2">
                <button onclick="window.print()" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-print mr-2"></i> In báo cáo
                </button>
                <a href="{{ route('department-kpis.edit', $departmentKpi) }}" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors shadow-sm inline-flex items-center">
                    <i class="fas fa-edit mr-2"></i> Cập nhật Điểm
                </a>
            </div>
        </div>

        <!-- Printable Document -->
        <div class="p-8 print:p-0">
            <!-- Title -->
            <div class="text-center mb-8 border-b-2 border-gray-800 pb-6">
                <h1 class="text-2xl font-bold uppercase text-gray-900 mb-2">BÁO CÁO ĐÁNH GIÁ KPI BỘ PHẬN</h1>
                <h2 class="text-xl font-medium text-gray-700">{{ $departmentKpi->title }}</h2>
            </div>
            
            <!-- Overall Info & Status -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-gray-50 p-4 rounded border border-gray-100 print:border-none">
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Bộ phận</p>
                    <p class="font-bold text-gray-800">{{ $departmentKpi->department }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded border border-gray-100 print:border-none">
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Kỳ đánh giá</p>
                    <p class="font-bold text-gray-800">{{ $departmentKpi->evaluation_period }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded border border-gray-100 print:border-none">
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Trạng thái duyệt</p>
                    <p class="font-bold text-gray-800 capitalize">{{ $departmentKpi->status }}</p>
                </div>
                <div class="p-4 rounded border-2 border-primary text-center print:border-none print:text-left">
                    <p class="text-xs text-primary font-bold uppercase tracking-wider mb-1">Tổng điểm đạt</p>
                    <p class="font-black text-2xl text-primary">{{ number_format($departmentKpi->total_score, 1) }} / 100</p>
                </div>
            </div>

            <!-- Detail Score Table -->
            <div class="mb-8">
                <h3 class="font-bold text-gray-800 mb-4 border-l-4 border-primary pl-2">Chi tiết các Tiêu chí</h3>
                <table class="w-full text-left text-sm border-collapse border border-gray-300">
                    <thead class="bg-gray-100 text-gray-800 font-semibold print:bg-gray-200">
                        <tr>
                            <th class="border border-gray-300 px-3 py-2 w-10 text-center">STT</th>
                            <th class="border border-gray-300 px-3 py-2">Nội dung Tiêu chí</th>
                            <th class="border border-gray-300 px-3 py-2 text-center w-24">Trọng số</th>
                            <th class="border border-gray-300 px-3 py-2 w-48">Mục tiêu (Target)</th>
                            <th class="border border-gray-300 px-3 py-2 w-48">Thực tế đạt được</th>
                            <th class="border border-gray-300 px-3 py-2 text-center w-28 bg-gray-50">Điểm Đạt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($departmentKpi->results as $index => $result)
                            <tr>
                                <td class="border border-gray-300 px-3 py-2 text-center text-gray-600">{{ $index + 1 }}</td>
                                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-800">
                                    {{ $result->criterion_name }}
                                    @if($result->note)
                                        <div class="text-xs text-gray-500 mt-1 italic">- {{ $result->note }}</div>
                                    @endif
                                </td>
                                <td class="border border-gray-300 px-3 py-2 text-center">{{ number_format($result->weight, 0) }}%</td>
                                <td class="border border-gray-300 px-3 py-2 text-gray-600">{{ $result->target ?? '-' }}</td>
                                <td class="border border-gray-300 px-3 py-2 font-medium text-gray-900">{{ $result->actual_value ?? 'Chưa cập nhật' }}</td>
                                <td class="border border-gray-300 px-3 py-2 text-center font-bold text-primary bg-gray-50">{{ number_format($result->score, 1) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="2" class="border border-gray-300 px-3 py-3 text-right font-semibold text-gray-700 uppercase">Tổng cộng:</td>
                            <td class="border border-gray-300 px-3 py-3 text-center font-bold">{{ $departmentKpi->results->sum('weight') }}%</td>
                            <td colspan="2" class="border border-gray-300 px-3 py-3 text-right"></td>
                            <td class="border border-gray-300 px-3 py-3 text-center font-black text-lg text-red-600">{{ number_format($departmentKpi->total_score, 1) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Notes -->
            @if($departmentKpi->note)
            <div class="mb-8 p-4 bg-yellow-50 border border-yellow-200 rounded text-sm print:border-none print:p-0">
                <h3 class="font-bold text-yellow-800 mb-1">Ghi chú & Đánh giá chung:</h3>
                <p class="text-yellow-700 whitespace-pre-wrap">{{ $departmentKpi->note }}</p>
            </div>
            @endif

            <!-- Signatures -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 mt-12 text-center text-sm print:mt-16">
                <div>
                    <p class="font-bold text-gray-800 mb-20">Trưởng Bộ phận</p>
                    <p class="text-gray-500 italic">(Ký & Ghi rõ họ tên)</p>
                </div>
                <div>
                    <p class="font-bold text-gray-800 mb-20">Nhân sự Đánh giá</p>
                    <p class="font-medium text-gray-800">{{ $departmentKpi->evaluator->name ?? '' }}</p>
                    <p class="text-gray-500 italic">(Ký & Ghi rõ họ tên)</p>
                </div>
                <div>
                    <p class="font-bold text-gray-800 mb-20">Giám đốc Nhân sự</p>
                    <p class="text-gray-500 italic">(Ký & Ghi rõ họ tên)</p>
                </div>
                <div>
                    <p class="font-bold text-gray-800 mb-20">Phê duyệt (BGD)</p>
                    <p class="text-gray-500 italic">(Ký & Ghi rõ họ tên)</p>
                </div>
            </div>

            <!-- Audit Stamps -->
            <div class="mt-16 pt-4 border-t border-gray-200 text-xs text-gray-400 flex justify-between print:text-[10px]">
                <p>Khởi tạo bởi: {{ $departmentKpi->creator->name ?? 'System' }} lúc {{ $departmentKpi->created_at->format('d/m/Y H:i') }}</p>
                <p>Mã tài liệu: KPI-{{ $departmentKpi->evaluation_period }}-{{ str_pad($departmentKpi->id, 5, '0', STR_PAD_LEFT) }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
