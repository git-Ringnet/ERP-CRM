@extends('layouts.app')

@section('title', 'Bảng Cân đối kế toán')

@section('header')
<div class="flex justify-between items-center">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Bảng Cân đối kế toán') }} (Chuẩn MISA)
    </h2>
    <div class="flex items-center space-x-2">
        <form method="GET" action="{{ route('reports.balance-sheet') }}" class="flex items-center space-x-2">
            <input type="date" name="date" value="{{ $date }}" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                {{ __('Xem báo cáo') }}
            </button>
        </form>
        <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:text-gray-500 focus:outline-none focus:border-blue-300 focus:ring ring-blue-200 disabled:opacity-25 transition ease-in-out duration-150">
            <i class="fas fa-print mr-2"></i> {{ __('In báo cáo') }}
        </button>
    </div>
</div>
@endsection

@section('content')
<style>
    .report-font { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    .font-bold-misa { font-weight: 700 !important; }
    .font-medium-misa { font-weight: 500 !important; }
    .font-normal-misa { font-weight: 400 !important; }
</style>
<div class="py-12 report-font text-gray-800">
    <div class="">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8 border border-gray-200">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-bold-misa uppercase tracking-tight text-gray-900">Bảng Cân đối kế toán</h1>
                <p class="text-gray-500 mt-2 text-lg font-normal-misa">Tại ngày: <span class="font-bold-misa text-gray-800">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span></p>
                <p class="text-sm italic text-gray-400 mt-1 font-normal-misa">(Đơn vị tính: VNĐ)</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <!-- TÀI SẢN -->
                <div>
                    <div class="flex items-center justify-between border-b-2 border-indigo-500 pb-2 mb-4">
                        <h3 class="text-xl font-bold-misa text-indigo-900 uppercase">TÀI SẢN</h3>
                    </div>
                    <table class="min-w-full table-fixed border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="w-1/2 px-4 py-3 text-left text-xs font-bold-misa text-gray-500 uppercase tracking-wider">Chỉ tiêu</th>
                                <th class="w-1/6 px-2 py-3 text-center text-xs font-bold-misa text-gray-500 uppercase tracking-wider">Mã số</th>
                                <th class="w-1/3 px-4 py-3 text-right text-xs font-bold-misa text-gray-500 uppercase tracking-wider">Số cuối kỳ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <!-- A. TÀI SẢN NGẮN HẠN -->
                            @php $shortTerm = $assets['short_term']; @endphp
                            <tr class="bg-indigo-50/50">
                                <td class="px-4 py-3 font-bold-misa text-gray-900 uppercase">A - TÀI SẢN NGẮN HẠN</td>
                                <td class="px-2 py-3 text-center font-bold-misa text-gray-900">{{ $shortTerm['code'] }}</td>
                                <td class="px-4 py-3 text-right font-bold-misa text-gray-900">
                                    {{ number_format($shortTerm['items'][0]['value'] + $shortTerm['items'][1]['value'] + $shortTerm['items'][2]['value'], 0, ',', '.') }}
                                </td>
                            </tr>
                            @foreach($shortTerm['items'] as $group)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-2.5 pl-8 font-medium-misa text-gray-800">{{ $group['name'] }}</td>
                                    <td class="px-2 py-2.5 text-center text-gray-600 font-normal-misa">{{ $group['code'] }}</td>
                                    <td class="px-4 py-2.5 text-right font-medium-misa text-gray-800">{{ number_format($group['value'], 0, ',', '.') }}</td>
                                </tr>
                                @if(isset($group['sub']))
                                    @foreach($group['sub'] as $sub)
                                        <tr class="text-sm text-gray-600 hover:bg-gray-50 transition-colors font-normal-misa">
                                            <td class="px-4 py-1.5 pl-14">{{ $sub['name'] }}</td>
                                            <td class="px-2 py-1.5 text-center italic text-gray-400 font-normal-misa">{{ $sub['code'] }}</td>
                                            <td class="px-4 py-1.5 text-right italic font-normal-misa">{{ number_format($sub['value'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach

                            <!-- B. TÀI SẢN DÀI HẠN -->
                            @php $longTerm = $assets['long_term']; @endphp
                            <tr class="bg-indigo-50/50">
                                <td class="px-4 py-3 font-bold-misa text-gray-900 uppercase">B - TÀI SẢN DÀI HẠN</td>
                                <td class="px-2 py-3 text-center font-bold-misa text-gray-900">{{ $longTerm['code'] }}</td>
                                <td class="px-4 py-3 text-right font-bold-misa text-gray-900">0</td>
                            </tr>
                            @foreach($longTerm['items'] as $group)
                                <tr class="hover:bg-gray-50 transition-colors font-normal-misa">
                                    <td class="px-4 py-2.5 pl-8 text-gray-700">{{ $group['name'] }}</td>
                                    <td class="px-2 py-2.5 text-center text-gray-500 font-normal-misa">{{ $group['code'] }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-400 font-normal-misa">0</td>
                                </tr>
                                @if(isset($group['sub']))
                                    @foreach($group['sub'] as $sub)
                                        <tr class="text-xs text-gray-500 hover:bg-gray-50 transition-colors font-normal-misa">
                                            <td class="px-4 py-1 pl-14">{{ $sub['name'] }}</td>
                                            <td class="px-2 py-1 text-center italic opacity-50">{{ $sub['code'] }}</td>
                                            <td class="px-4 py-1 text-right italic">0</td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                            
                            <tr class="bg-gray-100 border-t-2 border-gray-300">
                                <td class="px-4 py-4 font-bold-misa text-gray-900 uppercase tracking-wide">{{ $assets['total']['name'] }}</td>
                                <td class="px-2 py-4 text-center font-bold-misa text-gray-900">{{ $assets['total']['code'] }}</td>
                                <td class="px-4 py-4 text-right font-bold-misa text-gray-900 text-xl border-double border-b-4 border-gray-900">
                                    {{ number_format($assets['total']['value'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- NGUỒN VỐN -->
                <div>
                    <div class="flex items-center justify-between border-b-2 border-emerald-500 pb-2 mb-4">
                        <h3 class="text-xl font-bold-misa text-emerald-900 uppercase">NGUỒN VỐN</h3>
                    </div>
                    <table class="min-w-full table-fixed border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200">
                                <th class="w-1/2 px-4 py-3 text-left text-xs font-bold-misa text-gray-500 uppercase tracking-wider">Chỉ tiêu</th>
                                <th class="w-1/6 px-2 py-3 text-center text-xs font-bold-misa text-gray-500 uppercase tracking-wider">Mã số</th>
                                <th class="w-1/3 px-4 py-3 text-right text-xs font-bold-misa text-gray-500 uppercase tracking-wider">Số cuối kỳ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-normal-misa">
                            <!-- C. NỢ PHẢI TRẢ -->
                            @php $liabilitiesSec = $liabilities['liabilities']; @endphp
                            <tr class="bg-emerald-50/50">
                                <td class="px-4 py-3 font-bold-misa text-gray-900 uppercase">C - NỢ PHẢI TRẢ</td>
                                <td class="px-2 py-3 text-center font-bold-misa text-gray-900">{{ $liabilitiesSec['code'] }}</td>
                                <td class="px-4 py-3 text-right font-bold-misa text-gray-900">
                                    {{ number_format($liabilitiesSec['items'][0]['value'], 0, ',', '.') }}
                                </td>
                            </tr>
                            @foreach($liabilitiesSec['items'] as $group)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-2.5 pl-8 font-medium-misa text-gray-800">{{ $group['name'] }}</td>
                                    <td class="px-2 py-2.5 text-center text-gray-600 font-normal-misa">{{ $group['code'] }}</td>
                                    <td class="px-4 py-2.5 text-right font-medium-misa text-gray-800">{{ number_format($group['value'], 0, ',', '.') }}</td>
                                </tr>
                                @if(isset($group['sub']))
                                    @foreach($group['sub'] as $sub)
                                        <tr class="text-sm text-gray-600 hover:bg-gray-50 transition-colors font-normal-misa">
                                            <td class="px-4 py-1.5 pl-14">{{ $sub['name'] }}</td>
                                            <td class="px-2 py-1.5 text-center italic text-gray-400 font-normal-misa">{{ $sub['code'] }}</td>
                                            <td class="px-4 py-1.5 text-right italic font-normal-misa">{{ number_format($sub['value'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach

                            <!-- D. VỐN CHỦ SỞ HỮU -->
                            @php $equitySec = $liabilities['equity']; @endphp
                            <tr class="bg-emerald-50/50">
                                <td class="px-4 py-3 font-bold-misa text-gray-900 uppercase">D - VỐN CHỦ SỞ HỮU</td>
                                <td class="px-2 py-3 text-center font-bold-misa text-gray-900">{{ $equitySec['code'] }}</td>
                                <td class="px-4 py-3 text-right font-bold-misa text-gray-900">
                                    {{ number_format($equitySec['items'][0]['value'], 0, ',', '.') }}
                                </td>
                            </tr>
                            @foreach($equitySec['items'] as $group)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-2.5 pl-8 font-medium-misa text-gray-800">{{ $group['name'] }}</td>
                                    <td class="px-2 py-2.5 text-center text-gray-600 font-normal-misa">{{ $group['code'] }}</td>
                                    <td class="px-4 py-2.5 text-right font-medium-misa text-gray-800">{{ number_format($group['value'], 0, ',', '.') }}</td>
                                </tr>
                                @if(isset($group['sub']))
                                    @foreach($group['sub'] as $sub)
                                        <tr class="text-sm text-gray-600 hover:bg-gray-50 transition-colors font-normal-misa">
                                            <td class="px-4 py-1.5 pl-14">{{ $sub['name'] }}</td>
                                            <td class="px-2 py-1.5 text-center italic text-gray-400 font-normal-misa">{{ $sub['code'] }}</td>
                                            <td class="px-4 py-1.5 text-right italic font-normal-misa">{{ number_format($sub['value'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            @endforeach
                            
                            <tr class="bg-gray-100 border-t-2 border-gray-300">
                                <td class="px-4 py-4 font-bold-misa text-gray-900 uppercase tracking-wide">{{ $liabilities['total']['name'] }}</td>
                                <td class="px-2 py-4 text-center font-bold-misa text-gray-900">{{ $liabilities['total']['code'] }}</td>
                                <td class="px-4 py-4 text-right font-bold-misa text-gray-900 text-xl border-double border-b-4 border-gray-900">
                                    {{ number_format($liabilities['total']['value'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="mt-16 flex justify-between px-10 text-center font-normal-misa">
                <div class="w-1/3">
                    <p class="font-bold-misa text-gray-900 uppercase text-sm mb-16 font-bold-misa">Người lập biểu</p>
                    <p class="text-gray-400 italic text-xs mb-2 font-normal-misa">(Ký, họ tên)</p>
                    <div class="border-b border-gray-300 w-3/4 mx-auto"></div>
                </div>
                <div class="w-1/3">
                    <p class="font-bold-misa text-gray-900 uppercase text-sm mb-16 font-bold-misa">Kế toán trưởng</p>
                    <p class="text-gray-400 italic text-xs mb-2 font-normal-misa">(Ký, họ tên)</p>
                    <div class="border-b border-gray-300 w-3/4 mx-auto"></div>
                </div>
                <div class="w-1/3">
                    <p class="font-bold-misa text-gray-900 uppercase text-sm mb-16 font-bold-misa">Giám đốc</p>
                    <p class="text-gray-400 italic text-xs mb-2 font-normal-misa">(Ký, họ tên, đóng dấu)</p>
                    <div class="border-b border-gray-300 w-3/4 mx-auto"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
