@extends('layouts.app')

@section('title', 'Chấm công cá nhân')
@section('page-title', 'Chấm công bằng GPS')

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- Trạng thái hôm nay -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 mb-6">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                <i class="fas fa-calendar-alt text-primary mr-3"></i>
                Hôm nay, {{ \Carbon\Carbon::now()->format('d/m/Y') }}
            </h2>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-2 gap-6">
                <!-- Check in -->
                <div class="bg-green-50 rounded-lg p-5 border border-green-100 text-center">
                    <p class="text-sm text-green-600 font-medium mb-1">Giờ Vào (Check-in)</p>
                    <p class="text-3xl font-bold text-green-700 mb-2" id="checkinText">
                        {{ $attendance && $attendance->check_in_time ? \Carbon\Carbon::parse($attendance->check_in_time)->format('H:i') : '--:--' }}
                    </p>
                    @if($attendance && $attendance->check_in_time)
                        <span class="inline-flex items-center text-xs text-green-600 bg-green-100 px-2 py-0.5 rounded-full">
                            <i class="fas fa-check-circle mr-1"></i>Đã ghi nhận
                        </span>
                    @else
                        <span class="inline-flex items-center text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                            <i class="fas fa-clock mr-1"></i>Chưa check-in
                        </span>
                    @endif
                </div>

                <!-- Check out -->
                <div class="bg-orange-50 rounded-lg p-5 border border-orange-100 text-center">
                    <p class="text-sm text-orange-600 font-medium mb-1">Giờ Ra (Check-out)</p>
                    <p class="text-3xl font-bold text-orange-700 mb-2" id="checkoutText">
                        {{ $attendance && $attendance->check_out_time ? \Carbon\Carbon::parse($attendance->check_out_time)->format('H:i') : '--:--' }}
                    </p>
                    @if($attendance && $attendance->check_out_time)
                        <span class="inline-flex items-center text-xs text-orange-600 bg-orange-100 px-2 py-0.5 rounded-full">
                            <i class="fas fa-check-circle mr-1"></i>Đã ghi nhận
                        </span>
                    @else
                        <span class="inline-flex items-center text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                            <i class="fas fa-clock mr-1"></i>Chưa check-out
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Bảng điều khiển thao tác -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-8 text-center relative overflow-hidden">
        
        <!-- Decoration for Irregular staff -->
        @if(Auth::user()->timekeeping_type == 'irregular')
            <div class="absolute top-0 right-0 p-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    <i class="fas fa-route mr-1"></i>Nhân viên lưu động
                </span>
            </div>
        @endif

        <!-- Đồng hồ real-time -->
        <div class="mb-6">
            <div id="realtimeClock" class="text-5xl font-bold text-gray-800 tracking-wider font-mono">
                --:--:--
            </div>
            <p class="text-gray-500 mt-2 text-sm" id="locationStatus">Đang kiểm tra thiết bị...</p>
        </div>

        <!-- Mini Map for verification -->
        <div id="miniMap" class="w-full h-48 mb-6 rounded-xl border border-gray-200 z-0 {{ Auth::user()->timekeeping_type == 'irregular' ? '' : 'hidden' }}"></div>

        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <button id="btnCheckIn" class="px-8 py-3 text-lg font-bold text-white bg-green-600 rounded-xl hover:bg-green-700 transition shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center transform hover:-translate-y-1 disabled:hover:translate-y-0"
                {{ ($attendance && $attendance->check_in_time) ? 'disabled' : '' }}>
                <i class="fas fa-sign-in-alt mr-2"></i>CHECK-IN VÀO CA
            </button>
            
            <button id="btnCheckOut" class="px-8 py-3 text-lg font-bold text-white bg-orange-600 rounded-xl hover:bg-orange-700 transition shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center transform hover:-translate-y-1 disabled:hover:translate-y-0"
                {{ (!$attendance || !$attendance->check_in_time || ($attendance && $attendance->check_out_time)) ? 'disabled' : '' }}>
                <i class="fas fa-sign-out-alt mr-2"></i>CHECK-OUT TAN CA
            </button>
        </div>

        <!-- System messages -->
        <div id="statusMessage" class="hidden mt-6 p-4 rounded-lg text-sm font-medium text-left"></div>
    </div>

    <!-- Thông tin văn phòng được phê duyệt -->
    <div class="mt-8 bg-blue-50 rounded-lg p-5 border border-blue-100 text-left">
        <h3 class="text-sm font-bold text-blue-800 mb-2"><i class="fas fa-info-circle mr-2"></i>Vị trí Văn phòng được phép chấm công:</h3>
        <ul class="list-disc pl-5 text-sm text-blue-700 space-y-1">
            @forelse($locations as $loc)
                <li><strong>{{ $loc->name }}</strong> (Bán kính cho phép: {{ $loc->radius }}m)</li>
            @empty
                <li>Hệ thống chưa thiết lập văn phòng nào. Bạn không thể chấm công.</li>
            @endforelse
        </ul>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Đồng hồ thời gian thực
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('vi-VN', { hour12: false });
            document.getElementById('realtimeClock').textContent = timeString;
        }
        setInterval(updateClock, 1000);
        updateClock();

        const btnCheckIn = document.getElementById('btnCheckIn');
        const btnCheckOut = document.getElementById('btnCheckOut');
        const statusMessage = document.getElementById('statusMessage');
        const locationStatus = document.getElementById('locationStatus');
        
        // Mini map logic
        let map, marker;
        const miniMapContainer = document.getElementById('miniMap');

        function initMiniMap(lat, lng) {
            if (!map) {
                map = L.map('miniMap').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19
                }).addTo(map);
                marker = L.marker([lat, lng]).addTo(map);
            } else {
                map.setView([lat, lng]);
                marker.setLatLng([lat, lng]);
            }
        }

        function showMessage(message, type) {
            statusMessage.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-green-50', 'text-green-700');
            statusMessage.textContent = message;
            if(type === 'error') {
                statusMessage.classList.add('bg-red-50', 'text-red-700');
            } else {
                statusMessage.classList.add('bg-green-50', 'text-green-700');
            }
        }

        // Kiểm tra quyền GPS và Update Map định kỳ
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(position => {
                locationStatus.textContent = "Thiết bị sẵn sàng. Tọa độ hiện tại đã được xác định.";
                locationStatus.classList.remove('text-gray-500');
                locationStatus.classList.add('text-green-600');
                
                @if(Auth::user()->timekeeping_type == 'irregular')
                    initMiniMap(position.coords.latitude, position.coords.longitude);
                @endif
            }, error => {
                locationStatus.textContent = "Không thể lấy vị trí. Vui lòng bật định vị trên trình duyệt.";
                locationStatus.classList.add('text-red-600');
            });
        } else {
            locationStatus.textContent = "Thiết bị/Trình duyệt của bạn không hỗ trợ định vị GPS.";
            locationStatus.classList.add('text-red-600');
            if(btnCheckIn) btnCheckIn.disabled = true;
            if(btnCheckOut) btnCheckOut.disabled = true;
        }

        function handleAttendance(actionUrl, buttonId) {
            const btn = document.getElementById(buttonId);
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang lấy vị trí...';
            btn.disabled = true;

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Đang xử lý...';
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    fetch(actionUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            latitude: lat,
                            longitude: lng
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessage(data.message, 'success');
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            showMessage(data.message, 'error');
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }
                    })
                    .catch(error => {
                        showMessage('Có lỗi kết nối máy chủ. Vui lòng thử lại.', 'error');
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    });
                },
                function(error) {
                    let msg = '';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            msg = "Bạn đã từ chối quyền định vị. Vui lòng bật cấp quyền GPS cho trang web trong nút ổ khoá ở thanh URL.";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            msg = "Thông tin vị trí không khả dụng do thiết bị.";
                            break;
                        case error.TIMEOUT:
                            msg = "Thời gian lấy vị trí quá lâu (Timeout).";
                            break;
                        default:
                            msg = "Đã xảy ra lỗi không xác định khi lấy vị trí.";
                            break;
                    }
                    showMessage(msg, 'error');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        if(btnCheckIn) {
            btnCheckIn.addEventListener('click', () => handleAttendance('{{ route("attendance.check-in") }}', 'btnCheckIn'));
        }
        
        if(btnCheckOut) {
            btnCheckOut.addEventListener('click', () => handleAttendance('{{ route("attendance.check-out") }}', 'btnCheckOut'));
        }
    });
</script>
@endpush
@endsection
