@extends('layouts.app')

@section('title', 'Sửa Địa điểm làm việc')
@section('page-title', 'Sửa Địa điểm làm việc')

@section('content')
<div class="">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-800">Thông tin địa điểm: {{ $workLocation->name }}</h2>
            <a href="{{ route('work-locations.index') }}" class="text-gray-500 hover:text-gray-700 flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Quay lại
            </a>
        </div>

        <form action="{{ route('work-locations.update', $workLocation->id) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên địa điểm <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $workLocation->name) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary @error('name') border-red-500 @enderror">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Tọa độ GPS <span class="text-red-500">*</span></label>
                            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">Có thể click vào bản đồ để chọn</span>
                        </div>

                        <!-- Search Address Box -->
                        <div class="relative">
                            <div class="flex gap-2 mb-2">
                                <input type="text" id="addressSearch" placeholder="Nhập địa chỉ cần tìm... (VD: 123 Nguyễn Huệ, Quận 1)" 
                                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                                <button type="button" id="btnSearch" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
                                    <i class="fas fa-search mr-1"></i>Tìm
                                </button>
                            </div>
                            <div id="searchResults" class="absolute left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg z-[1000] max-h-48 overflow-y-auto hidden"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="latitude" class="block text-xs text-gray-500 mb-1">Vĩ độ (Latitude)</label>
                                <input type="number" step="any" name="latitude" id="latitude" value="{{ old('latitude', $workLocation->latitude) }}" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary @error('latitude') border-red-500 @enderror">
                                @error('latitude')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="longitude" class="block text-xs text-gray-500 mb-1">Kinh độ (Longitude)</label>
                                <input type="number" step="any" name="longitude" id="longitude" value="{{ old('longitude', $workLocation->longitude) }}" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary @error('longitude') border-red-500 @enderror">
                                @error('longitude')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <!-- Map Container -->
                        <div id="map" class="w-full h-[300px] mt-4 rounded-lg border border-gray-300 z-10"></div>
                    
                    <div>
                        <label for="radius" class="block text-sm font-medium text-gray-700 mb-1">Bán kính cho phép (Meters) <span class="text-red-500">*</span></label>
                        <input type="number" name="radius" id="radius" value="{{ old('radius', $workLocation->radius) }}" required min="10"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary @error('radius') border-red-500 @enderror">
                        <p class="mt-1 text-sm text-gray-500"><i class="fas fa-info-circle mr-1 text-blue-500"></i>Nhân viên phải đứng trong vòng bán kính này mới được check-in/out hợp lệ.</p>
                        @error('radius')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $workLocation->is_active) ? 'checked' : '' }}
                           class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm font-medium text-gray-700">
                        Kích hoạt địa điểm này
                    </label>
                </div>
                
                <div class="p-4 bg-blue-50 text-blue-800 rounded-lg text-sm border border-blue-200">
                    <div class="mb-3">
                        <label class="block font-bold mb-1 text-blue-900">Mẹo lấy tọa độ Google Maps (Miễn phí):</label>
                        <p class="mb-2 text-xs opacity-80 italic">Cách 1: Mở Google Maps, nhấp chuột phải vào vị trí -> Copy tọa độ. Dán vào ô dưới đây.</p>
                        <p class="mb-3 text-xs opacity-80 italic">Cách 2: Dán link Google Maps (Link chia sẻ) vào ô dưới đây.</p>
                        
                        <div class="flex gap-2">
                            <input type="text" id="smartInput" placeholder="Dán tọa độ hoặc Link Google Maps vào đây..." 
                                   class="flex-1 px-3 py-2 border border-blue-300 rounded focus:ring-2 focus:ring-blue-500 outline-none text-xs">
                            <button type="button" onclick="processSmartInput()" class="px-3 py-2 bg-blue-700 text-white rounded hover:bg-black transition text-xs font-bold">
                                Trích xuất
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2 border-t border-blue-200">
                        <button type="button" onclick="getCurrentLocation()" class="bg-green-600 text-white px-3 py-1.5 rounded text-xs font-semibold hover:bg-green-700 flex items-center">
                            <i class="fas fa-crosshairs mr-1"></i>Lấy vị trí GPS hiện tại (Độ chính xác cao)
                        </button>
                        <a href="https://maps.google.com" target="_blank" class="text-blue-600 hover:underline font-medium text-xs">
                            <i class="fas fa-external-link-alt mr-1"></i>Mở Google Maps
                        </a>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-gray-200">
                    <a href="{{ route('work-locations.index') }}" class="mr-3 px-4 py-2 text-sm text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200">Hủy</a>
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:bg-primary-dark">Lưu thay đổi</button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function getCurrentLocation() {
        if (navigator.geolocation) {
            const options = {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            };
            
            navigator.geolocation.getCurrentPosition(function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                document.getElementById('latitude').value = lat.toFixed(6);
                document.getElementById('longitude').value = lng.toFixed(6);
                // Manual update trigger for map
                const event = new Event('change');
                document.getElementById('latitude').dispatchEvent(event);
                document.getElementById('longitude').dispatchEvent(event);
            }, function(error) {
                alert("Không thể lấy vị trí: " + error.message);
            }, options);
        } else {
            alert("Trình duyệt không hỗ trợ lấy vị trí GPS.");
        }
    }

    function processSmartInput() {
        const input = document.getElementById('smartInput').value.trim();
        if (!input) return;

        // If it's a URL, call backend to resolve
        if (input.startsWith('http')) {
            const btn = document.querySelector('button[onclick="processSmartInput()"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btn.disabled = true;

            fetch('{{ route("attendance.resolve-location") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ url: input })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('latitude').value = data.lat;
                    document.getElementById('longitude').value = data.lng;
                    
                    const event = new Event('change');
                    document.getElementById('latitude').dispatchEvent(event);
                    document.getElementById('longitude').dispatchEvent(event);
                    document.getElementById('smartInput').value = '';
                } else {
                    alert(data.message || "Không thể phân giải liên kết này.");
                }
            })
            .catch(err => alert("Lỗi kết nối máy chủ."))
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
            return;
        }

        // Regex for Lat,Lng coords (e.g. 10.762622, 106.660172)
        const coordRegex = /(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)/;
        // Regex for Google Maps URL coordinates
        const urlCoordRegex = /@(-?\d+\.\d+),(-?\d+\.\d+)/;

        let lat, lng;

        if (coordRegex.test(input)) {
            const match = input.match(coordRegex);
            lat = match[1];
            lng = match[2];
        } else if (urlCoordRegex.test(input)) {
            const match = input.match(urlCoordRegex);
            lat = match[1];
            lng = match[2];
        } else {
            alert("Không tìm thấy tọa độ trong nội dung bạn dán. Vui lòng thử lại bằng cách copy tọa độ trực tiếp hoặc dán Link đầy đủ.");
            return;
        }

        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        
        // Trigger map update
        const event = new Event('change');
        document.getElementById('latitude').dispatchEvent(event);
        document.getElementById('longitude').dispatchEvent(event);
        
        document.getElementById('smartInput').value = '';
    }
</script>
@endpush
@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endpush

@push('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const radiusInput = document.getElementById('radius');

        let initLat = parseFloat(latInput.value) || 10.762622;
        let initLng = parseFloat(lngInput.value) || 106.660172;
        let initRadius = parseInt(radiusInput.value) || 50;

        const map = L.map('map').setView([initLat, initLng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap ERP CRM'
        }).addTo(map);

        let marker = L.marker([initLat, initLng]).addTo(map);
        let circle = L.circle([initLat, initLng], {
            color: 'red',
            fillColor: '#f03',
            fillOpacity: 0.1,
            radius: initRadius
        }).addTo(map);

        // Update when map clicked
        map.on('click', function(e) {
            let lat = e.latlng.lat;
            let lng = e.latlng.lng;
            
            latInput.value = lat.toFixed(6);
            lngInput.value = lng.toFixed(6);

            marker.setLatLng(e.latlng);
            circle.setLatLng(e.latlng);
        });

        // Update when inputs modified directly
        function updateMap() {
            let lat = parseFloat(latInput.value) || initLat;
            let lng = parseFloat(lngInput.value) || initLng;
            let rad = parseInt(radiusInput.value) || 50;
            
            var newLatLng = new L.LatLng(lat, lng);
            marker.setLatLng(newLatLng);
            circle.setLatLng(newLatLng);
            circle.setRadius(rad);
            map.flyTo(newLatLng, map.getZoom());
        }

        latInput.addEventListener('change', updateMap);
        lngInput.addEventListener('change', updateMap);
        radiusInput.addEventListener('change', updateMap);

        // Search Address Feature
        const addressSearch = document.getElementById('addressSearch');
        const btnSearch = document.getElementById('btnSearch');
        const searchResults = document.getElementById('searchResults');

        btnSearch.addEventListener('click', function() {
            const query = addressSearch.value.trim();
            if (query.length < 3) return;

            btnSearch.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btnSearch.disabled = true;

            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5`)
                .then(res => res.json())
                .then(data => {
                    searchResults.innerHTML = '';
                    if (data.length > 0) {
                        searchResults.classList.remove('hidden');
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm border-b border-gray-100';
                            div.textContent = item.display_name;
                            div.onclick = function() {
                                const lat = parseFloat(item.lat);
                                const lon = parseFloat(item.lon);
                                
                                latInput.value = lat.toFixed(6);
                                lngInput.value = lon.toFixed(6);
                                
                                var newLatLng = new L.LatLng(lat, lon);
                                marker.setLatLng(newLatLng);
                                circle.setLatLng(newLatLng);
                                map.flyTo(newLatLng, 17);
                                
                                searchResults.classList.add('hidden');
                                addressSearch.value = item.display_name;
                            };
                            searchResults.appendChild(div);
                        });
                    } else {
                        searchResults.classList.add('hidden');
                        alert('Không tìm thấy địa chỉ này.');
                    }
                })
                .finally(() => {
                    btnSearch.innerHTML = '<i class="fas fa-search mr-1"></i>Tìm';
                    btnSearch.disabled = false;
                });
        });

        // Hide results on click outside
        document.addEventListener('click', function(e) {
            if (!searchResults.contains(e.target) && e.target !== addressSearch) {
                searchResults.classList.add('hidden');
            }
        });
    });
</script>
@endpush
@endsection
