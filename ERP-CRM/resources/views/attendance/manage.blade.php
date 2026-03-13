@extends('layouts.app')

@section('title', 'Lịch sử Chấm công toàn công ty')
@section('page-title', 'Bảng Theo dõi Chấm công')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <!-- Header & Filter -->
    <div class="p-4 border-b border-gray-200 flex flex-col md:flex-row justify-between items-center gap-4">
        <form action="{{ route('attendance.manage') }}" method="GET" class="flex flex-wrap gap-2 items-center w-full md:w-auto">
            <div class="flex items-center gap-2">
                <input type="date" name="date" value="{{ request('date', \Carbon\Carbon::today()->format('Y-m-d')) }}" 
                       class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary h-10">
                
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Tên / Mã NV..." 
                       class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary h-10 w-48">
            </div>
            
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark text-sm whitespace-nowrap h-10">
                <i class="fas fa-filter mr-1"></i>Lọc
            </button>
            <a href="{{ route('attendance.manage') }}" class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 h-10 flex items-center justify-center">
                <i class="fas fa-redo"></i>
            </a>
        </form>
        
        <div class="text-sm font-medium text-gray-600 bg-gray-50 px-4 py-2 rounded-lg border border-gray-200">
            Dữ liệu ngày: <span class="text-primary font-bold">{{ $summaryDate }}</span>
        </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Nhân viên</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Ngày</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Trạng thái</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Check-in</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Check-out</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Vị trí (Văn phòng)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($attendances as $record)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3">
                        <div class="flex items-center">
                            <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold mr-3 flex-shrink-0">
                                {{ substr($record->user->name, 0, 1) }}
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">{{ $record->user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $record->user->employee_code }} • {{ $record->user->department }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center text-sm text-gray-700">
                        {{ \Carbon\Carbon::parse($record->date)->format('d/m/Y') }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($record->status == 'present')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                Đúng giờ
                            </span>
                        @elseif($record->status == 'late')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                Đi muộn
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $record->status }}
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="text-sm font-semibold text-green-600">
                            {{ $record->check_in_time ? \Carbon\Carbon::parse($record->check_in_time)->format('H:i') : '--:--' }}
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="text-sm font-semibold text-orange-600">
                            {{ $record->check_out_time ? \Carbon\Carbon::parse($record->check_out_time)->format('H:i') : '--:--' }}
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        @if($record->workLocation)
                            <div class="font-medium text-blue-700">
                                <i class="fas fa-map-marker-alt mr-1"></i>{{ $record->workLocation->name }}
                            </div>
                        @else
                            <div class="font-medium text-purple-700">
                                <i class="fas fa-street-view mr-1"></i>Lưu động (Remote)
                            </div>
                            @if($record->check_in_latitude && $record->check_in_longitude)
                                <a href="https://www.google.com/maps?q={{ $record->check_in_latitude }},{{ $record->check_in_longitude }}" 
                                   target="_blank" class="text-[10px] text-blue-500 hover:underline flex items-center mt-0.5">
                                    <i class="fas fa-external-link-alt mr-1"></i>Xem trên Google Maps
                                </a>
                            @endif
                        @endif
                        <div class="text-[10px] text-gray-400 mt-0.5 truncate max-w-[150px]" title="{{ $record->device_info }}">
                            {{ Str::limit($record->device_info, 30) }}
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-gray-500">
                        <i class="fas fa-calendar-times text-5xl mb-4 text-gray-300"></i>
                        <p class="text-base font-medium">Không có dữ liệu chấm công cho ngày này.</p>
                        <p class="text-sm mt-1">Vui lòng chọn ngày khác hoặc xóa bộ lọc tìm kiếm.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($attendances->hasPages())
    <div class="px-4 py-3 border-t border-gray-200">
        {{ $attendances->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
