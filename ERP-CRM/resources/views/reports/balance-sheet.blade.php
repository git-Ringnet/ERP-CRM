@extends('layouts.app')

@section('title', 'Bảng Cân đối kế toán')

@section('page-title', __('Bảng Cân đối kế toán'))

@section('content')
<!-- Header Controls -->
<div class="mb-6 flex justify-between items-center no-print">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Bảng Cân đối kế toán') }} (Thông tư 200/2014/TT-BTC)
    </h2>
    <div class="flex items-center space-x-2">
        <form method="GET" action="{{ route('reports.balance-sheet') }}" class="flex items-center space-x-2">
            <input type="date" name="date" value="{{ $date }}" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                {{ __('Xem báo cáo') }}
            </button>
        </form>
        <a href="{{ route('reports.balance-sheet.export', ['date' => $date]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
            <i class="fas fa-file-excel mr-2"></i> {{ __('Xuất Excel') }}
        </a>
        <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring ring-blue-200 disabled:opacity-25 transition ease-in-out duration-150">
            <i class="fas fa-print mr-2"></i> {{ __('In báo cáo') }}
        </button>
    </div>
</div>

<style>
    .report-container {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: #1f2937;
    }
    .report-table th, .report-table td {
        border: 1px solid #d1d5db;
        padding: 0.6rem 0.75rem;
        line-height: 1.5;
    }
    .font-bold-report { 
        font-weight: 700; 
        color: #111827;
    }
    .font-medium-report {
        font-weight: 600;
        color: #374151;
    }
    .indent-1 { padding-left: 2rem !important; }
    .indent-2 { padding-left: 3.5rem !important; }
    
    .report-table thead th {
        background-color: #f9fafb;
        vertical-align: middle;
    }
    
    @media print {
        header, aside, footer, .no-print, #toggleSidebar, #sidebarOverlay {
            display: none !important;
        }
        
        main {
            padding: 0 !important;
            margin: 0 !important;
        }

        .report-container {
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }

        .bg-white {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
        }

        body {
            background-color: white !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .report-table {
            width: 100%;
            border: 1px solid black !important;
        }

        .report-table th, .report-table td {
            border: 1px solid black !important;
            padding: 4px 8px !important;
            font-size: 11pt !important;
        }

        .report-table thead th {
            background-color: #f3f4f6 !important;
        }

        .font-bold-report { font-weight: bold !important; }
        
        @page {
            size: A4;
            margin: 15mm;
        }
    }
</style>

<div class="py-6 px-4 sm:px-6 lg:px-8 report-container print:p-0">
    <div class="">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 sm:p-8 border border-gray-200 print:border-none print:p-0">
            <!-- Header Section -->
            <div class="flex justify-between mb-8">
                <div class="text-left">
                    <p class="font-bold">Đơn vị báo cáo: ..............................</p>
                    <p class="font-bold">Địa chỉ: ............................................</p>
                </div>
                <div class="text-right">
                    <p class="font-bold">Mẫu số B 01 - DN</p>
                    <p class="italic text-sm">(ban hành kèm theo Thông tư 200/2014/TT-BTC)</p>
                </div>
            </div>

            <div class="text-center mb-6">
                <h1 class="text-xl sm:text-2xl font-bold uppercase">BẢNG CÂN ĐỐI KẾ TOÁN</h1>
                <p class="mt-1 text-sm sm:text-base">Tại ngày {{ \Carbon\Carbon::parse($date)->format('d') }} tháng {{ \Carbon\Carbon::parse($date)->format('m') }} năm {{ \Carbon\Carbon::parse($date)->format('Y') }}</p>
                <p class="italic text-xs sm:text-sm">(Áp dụng cho doanh nghiệp đáp ứng giả định hoạt động liên tục)</p>
                <div class="flex justify-end mt-2">
                    <p class="italic text-xs sm:text-sm">Đơn vị tính: VNĐ</p>
                </div>
            </div>

            <table class="min-w-full report-table border-collapse">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="text-center uppercase text-sm font-bold w-1/2">TÀI SẢN</th>
                        <th class="text-center uppercase text-sm font-bold w-12">Mã số</th>
                        <th class="text-center uppercase text-sm font-bold w-16">Thuyết minh</th>
                        <th class="text-center uppercase text-sm font-bold w-32">Số cuối năm</th>
                        <th class="text-center uppercase text-sm font-bold w-32">Số đầu năm</th>
                    </tr>
                    <tr class="bg-gray-100 text-xs italic">
                        <th class="text-center">1</th>
                        <th class="text-center">2</th>
                        <th class="text-center">3</th>
                        <th class="text-center">4</th>
                        <th class="text-center">5</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(['A', 'B'] as $sec)
                        @if(isset($reportData[$sec]))
                            <tr class="font-bold-report">
                                <td class="uppercase">{{ $sec }} - {{ $reportData[$sec]['name'] }}</td>
                                <td class="text-center">{{ $reportData[$sec]['code'] }}</td>
                                <td></td>
                                <td class="text-right">{{ number_format($reportData[$sec]['end'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($reportData[$sec]['start'], 0, ',', '.') }}</td>
                            </tr>
                            @foreach($reportData[$sec]['sub'] as $subKey => $sub)
                                <tr class="font-medium-report">
                                    <td class="indent-1">{{ $subKey }}. {{ $sub['name'] }}</td>
                                    <td class="text-center">{{ $sub['code'] }}</td>
                                    <td></td>
                                    <td class="text-right">{{ number_format($sub['end'], 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($sub['start'], 0, ',', '.') }}</td>
                                </tr>
                                @foreach($sub['items'] as $item)
                                    <tr>
                                        <td class="indent-2">{{ $item['name'] }}</td>
                                        <td class="text-center">{{ $item['code'] }}</td>
                                        <td class="text-center">{{ $item['note'] ?? '' }}</td>
                                        <td class="text-right">{{ number_format($item['end'], 0, ',', '.') }}</td>
                                        <td class="text-right">{{ number_format($item['start'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endif
                    @endforeach

                    <tr class="bg-gray-100 font-bold-report">
                        <td class="uppercase">{{ $reportData['TOTAL_ASSETS']['name'] ?? 'TỔNG CỘNG TÀI SẢN' }}</td>
                        <td class="text-center">{{ $reportData['TOTAL_ASSETS']['code'] ?? '270' }}</td>
                        <td></td>
                        <td class="text-right">{{ number_format($reportData['TOTAL_ASSETS']['end'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($reportData['TOTAL_ASSETS']['start'] ?? 0, 0, ',', '.') }}</td>
                    </tr>

                    <!-- NGUỒN VỐN SECTION -->
                    <tr class="bg-gray-50 font-bold-report text-center uppercase">
                        <td>NGUỒN VỐN</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>

                    @foreach(['C', 'D'] as $sec)
                        @if(isset($reportData[$sec]))
                            <tr class="font-bold-report">
                                <td class="uppercase">{{ $sec }} - {{ $reportData[$sec]['name'] }}</td>
                                <td class="text-center">{{ $reportData[$sec]['code'] }}</td>
                                <td></td>
                                <td class="text-right">{{ number_format($reportData[$sec]['end'], 0, ',', '.') }}</td>
                                <td class="text-right">{{ number_format($reportData[$sec]['start'], 0, ',', '.') }}</td>
                            </tr>
                            @foreach($reportData[$sec]['sub'] as $subKey => $sub)
                                <tr class="font-medium-report">
                                    <td class="indent-1">{{ $subKey }}. {{ $sub['name'] }}</td>
                                    <td class="text-center">{{ $sub['code'] }}</td>
                                    <td></td>
                                    <td class="text-right">{{ number_format($sub['end'], 0, ',', '.') }}</td>
                                    <td class="text-right">{{ number_format($sub['start'], 0, ',', '.') }}</td>
                                </tr>
                                @foreach($sub['items'] as $item)
                                    <tr>
                                        <td class="indent-2">{{ $item['name'] }}</td>
                                        <td class="text-center">{{ $item['code'] }}</td>
                                        <td class="text-center">{{ $item['note'] ?? '' }}</td>
                                        <td class="text-right">{{ number_format($item['end'], 0, ',', '.') }}</td>
                                        <td class="text-right">{{ number_format($item['start'], 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        @endif
                    @endforeach

                    <tr class="bg-gray-100 font-bold-report">
                        <td class="uppercase">{{ $reportData['TOTAL_RESOURCES']['name'] ?? 'TỔNG CỘNG NGUỒN VỐN' }}</td>
                        <td class="text-center">{{ $reportData['TOTAL_RESOURCES']['code'] ?? '440' }}</td>
                        <td></td>
                        <td class="text-right">{{ number_format($reportData['TOTAL_RESOURCES']['end'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($reportData['TOTAL_RESOURCES']['start'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="mt-6 text-right">
                <p class="italic text-sm">Lập, ngày {{ \Carbon\Carbon::parse($date)->format('d') }} tháng {{ \Carbon\Carbon::parse($date)->format('m') }} năm {{ \Carbon\Carbon::parse($date)->format('Y') }}</p>
            </div>
            
            <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="font-bold">Người lập biểu</p>
                    <p class="italic text-xs">(Ký, họ tên)</p>
                </div>
                <div>
                    <p class="font-bold">Kế toán trưởng</p>
                    <p class="italic text-xs">(Ký, họ tên)</p>
                </div>
                <div>
                    <p class="font-bold">Giám đốc</p>
                    <p class="italic text-xs">(Ký, họ tên, đóng dấu)</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
