@extends('layouts.app')

@section('title', $payroll->title)
@section('page-title', 'Chi tiết Bảng lương')

@section('content')
<div class="space-y-6">
    <!-- Header Tools -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <a href="{{ route('payrolls.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium text-sm">
            <i class="fas fa-arrow-left mr-2"></i>Quay lại danh sách
        </a>
        
        <div class="flex flex-wrap gap-2">
            <form action="{{ route('payrolls.updateStatus', $payroll->id) }}" method="POST" class="inline-flex">
                @csrf
                @method('PATCH')
                <div class="flex items-center space-x-2 bg-white px-3 py-1 rounded-lg border border-gray-300 shadow-sm">
                    <span class="text-sm font-medium text-gray-600 mr-2">Trạng thái:</span>
                    <select name="status" onchange="this.form.submit()" class="form-select text-sm border-gray-300 focus:border-primary focus:ring-primary rounded-md py-1.5 font-medium {{ 
                        $payroll->status == 'draft' ? 'text-gray-700' : (
                        $payroll->status == 'pending_approval' ? 'text-yellow-700' : (
                        $payroll->status == 'approved' ? 'text-blue-700' : 'text-green-700'
                        )) }}">
                        <option value="draft" {{ $payroll->status == 'draft' ? 'selected' : '' }}>Bản nháp</option>
                        <option value="pending_approval" {{ $payroll->status == 'pending_approval' ? 'selected' : '' }}>Chờ duyệt</option>
                        <option value="approved" {{ $payroll->status == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                        <option value="paid" {{ $payroll->status == 'paid' ? 'selected' : '' }}>Đã thanh toán (Hoàn tất)</option>
                    </select>
                </div>
            </form>
            
            <button type="button" onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium text-sm shadow-sm hidden md:inline-flex">
                <i class="fas fa-print mr-2 text-primary"></i>In bảng lương
            </button>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex flex-col md:flex-row md:justify-between md:items-center gap-2">
            <div>
                <h2 class="text-xl font-bold text-gray-800">{{ $payroll->title }}</h2>
                <div class="text-sm text-gray-500 mt-1 flex items-center">
                    <i class="fas fa-calendar-alt mr-1"></i>Kỳ tính lương: Tháng {{ str_pad($payroll->month, 2, '0', STR_PAD_LEFT) }}/{{ $payroll->year }}
                    <span class="mx-2">|</span>
                    <i class="fas fa-briefcase mr-1"></i>Ngày công chuẩn: {{ $payroll->standard_working_days }}
                </div>
            </div>
            
            <div class="text-right">
                <p class="text-sm font-medium text-gray-500 mb-1">Tổng quỹ lương đợt này</p>
                <div class="text-2xl font-black text-primary">{{ number_format($items->sum('net_salary')) }} đ</div>
            </div>
        </div>
        
        <div class="grid grid-cols-2 lg:grid-cols-4 divide-x divide-y lg:divide-y-0 divide-gray-100">
            <div class="p-4 text-center">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Nhân viên tính lương</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">{{ $items->count() }}</p>
            </div>
            <div class="p-4 text-center">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tổng Phụ cấp</p>
                <p class="text-xl font-bold text-green-600 mt-1">+{{ number_format($items->sum('total_allowance')) }} đ</p>
            </div>
            <div class="p-4 text-center">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tổng Hoa hồng</p>
                <p class="text-xl font-bold text-green-600 mt-1">+{{ number_format($items->sum('commission_bonus')) }} đ</p>
            </div>
            <div class="p-4 text-center">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tổng Khấu trừ</p>
                <p class="text-xl font-bold text-red-600 mt-1">-{{ number_format($items->sum('total_deduction')) }} đ</p>
            </div>
        </div>
    </div>

    <!-- Detailed Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Chi tiết bảng lương nhân sự</h3>
        </div>
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left whitespace-nowrap min-w-[1000px]">
                <thead class="bg-gray-50/80 text-xs uppercase font-medium text-gray-500 tracking-wider">
                    <tr>
                        <th class="px-6 py-3 border-b border-gray-200">Nhân viên</th>
                        <th class="px-4 py-3 border-b border-gray-200 text-right">Lương CB (VNĐ)</th>
                        <th class="px-4 py-3 border-b border-gray-200 text-center">Ngày công TT</th>
                        <th class="px-4 py-3 border-b border-gray-200 text-right text-green-700">Phụ cấp (+)</th>
                        <th class="px-4 py-3 border-b border-gray-200 text-right text-green-700 bg-green-50/30">Hoa hồng (+)</th>
                        <th class="px-4 py-3 border-b border-gray-200 text-right text-red-700 bg-red-50/30">Khấu trừ (-)</th>
                        <th class="px-6 py-3 border-b border-gray-200 text-right font-bold text-gray-800 bg-gray-100">THỰC NHẬN</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($items as $item)
                        <tr class="hover:bg-blue-50/50 transition-colors group">
                            <td class="px-6 py-3">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold mr-3">
                                        {{ substr($item->user->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900 group-hover:text-primary transition-colors">
                                            <a href="{{ route('employees.show', $item->user->id) }}" target="_blank">{{ $item->user->name }}</a>
                                        </div>
                                        <div class="text-xs text-gray-500">{{ $item->user->employee_code }} • {{ $item->user->department }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-gray-600">
                                {{ number_format($item->basic_salary) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 font-bold rounded text-sm">
                                    {{ rtrim(rtrim(number_format($item->actual_working_days, 1), '0'), '.') }} / {{ $payroll->standard_working_days }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-green-600">
                                {{ number_format($item->total_allowance) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-green-600 bg-green-50/10">
                                {{ number_format($item->commission_bonus) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-red-600 bg-red-50/10">
                                {{ number_format($item->total_deduction) }}
                            </td>
                            <td class="px-6 py-3 text-right font-bold text-base text-gray-900 bg-gray-50/50 group-hover:bg-primary/5 transition-colors">
                                {{ number_format($item->net_salary) }} <small class="text-xs text-gray-500">đ</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-users-slash text-4xl mb-3 text-gray-300"></i>
                                <p>Không có nhân viên nào đủ điều kiện hoặc nằm trong bảng lương đợt này.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($items->isNotEmpty())
                <tfoot class="bg-blue-900 text-white font-semibold shadow-[0_-2px_10px_rgba(0,0,0,0.1)]">
                    <tr class="divide-x divide-blue-800/50">
                        <td class="px-6 py-5 text-left font-bold uppercase tracking-wider text-xs text-blue-200">Tổng cộng toàn công ty</td>
                        <td class="px-4 py-5 text-right text-sm font-medium">{{ number_format($items->sum('basic_salary')) }}</td>
                        <td class="px-4 py-5 text-center text-blue-400/50">-</td>
                        <td class="px-4 py-5 text-right text-sm text-green-400 font-bold">+{{ number_format($items->sum('total_allowance')) }}</td>
                        <td class="px-4 py-5 text-right text-sm text-green-400 font-bold">+{{ number_format($items->sum('commission_bonus')) }}</td>
                        <td class="px-4 py-5 text-right text-sm text-red-400 font-bold">-{{ number_format($items->sum('total_deduction')) }}</td>
                        <td class="px-6 py-5 text-right text-xl font-bold bg-blue-950/40">
                            {{ number_format($items->sum('net_salary')) }} <span class="text-xs font-normal text-blue-300 uppercase ml-1">vnđ</span>
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&display=swap');
    
    body {
        font-family: 'Be Vietnam Pro', sans-serif !important;
    }

    @media print {
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body { 
            font-family: 'Be Vietnam Pro', sans-serif !important; 
            font-size: 10pt; 
            background: #fff !important; 
            color: #000 !important;
        }

        /* Ẩn các thành phần không cần thiết */
        .sidebar, .navbar, .header-tools, form, button, a[href*="index"], .fas, .no-print { 
            display: none !important; 
        }

        /* Tối ưu container */
        .main-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .space-y-6 { space-y: 0 !important; }
        .shadow-sm, .border { border: none !important; box-shadow: none !important; }

        /* Tiêu đề bảng lương */
        h2 { 
            font-size: 18pt !important; 
            text-align: center; 
            text-transform: uppercase;
            margin-bottom: 5mm !important;
            font-weight: bold !important;
        }

        .text-sm.text-gray-500 { 
            text-align: center; 
            display: block; 
            margin-bottom: 8mm;
            font-size: 11pt !important;
            color: #000 !important;
        }

        /* Tổng hợp nhanh */
        .grid-cols-2.lg-grid-cols-4 {
            display: table !important;
            width: 100% !important;
            border-collapse: collapse !important;
            margin-bottom: 8mm !important;
        }

        .grid-cols-2.lg-grid-cols-4 > div {
            display: table-cell !important;
            border: 1px solid #000 !important;
            padding: 3mm !important;
            text-align: center !important;
        }

        /* Bảng chi tiết */
        table { 
            border-collapse: collapse !important; 
            width: 100% !important; 
            margin-top: 5mm !important;
        }

        th, td { 
            border: 0.5pt solid #000 !important; 
            padding: 2mm !important; 
            vertical-align: middle !important;
        }

        thead { background: #f0f0f0 !important; }
        th { font-weight: bold !important; text-transform: uppercase; font-size: 9pt !important; }

        /* Hàng tổng cộng trong bản in */
        tfoot { 
            background: #333 !important; 
            color: #fff !important; 
            -webkit-print-color-adjust: exact; 
        }
        
        tfoot td { 
            font-weight: bold !important; 
            border-color: #333 !important;
        }

        /* Ký tên phía dưới */
        .print-footer {
            margin-top: 15mm;
            display: grid !important;
            grid-template-columns: 1fr 1fr 1fr !important;
            text-align: center;
        }

        .print-date {
            text-align: right;
            margin-top: 10mm;
            font-style: italic;
        }
    }
</style>

{{-- Thêm phần footer chỉ hiển thị khi in --}}
<div class="hidden print:block mt-10">
    <div class="print-date">Ngày in: {{ date('d/m/Y H:i') }}</div>
    <div class="print-footer mt-5">
        <div>
            <p class="font-bold">Người lập biểu</p>
            <p class="text-xs text-gray-400 mt-10">(Ký, họ tên)</p>
        </div>
        <div>
            <p class="font-bold">Kế toán trưởng</p>
            <p class="text-xs text-gray-400 mt-10">(Ký, họ tên)</p>
        </div>
        <div>
            <p class="font-bold">Giám đốc</p>
            <p class="text-xs text-gray-400 mt-10">(Ký, đóng dấu)</p>
        </div>
    </div>
</div>
@endsection
