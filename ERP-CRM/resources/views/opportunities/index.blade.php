@extends('layouts.app')

@section('title', 'Lịch hoạt động cơ hội')
@section('page-title', 'Lịch hoạt động cơ hội')

@section('content')
    <!-- FullCalendar CDN -->
    @push('styles')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
        <style>
            .fc-event {
                cursor: pointer;
                transition: transform 0.15s ease, box-shadow 0.15s ease;
            }
            .fc-event:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }
            .fc-header-toolbar {
                margin-bottom: 1.5rem !important;
            }
            .fc-toolbar-title {
                font-size: 1.125rem !important;
                font-weight: 700 !important;
                color: #1f2937 !important;
            }
            .fc-button-primary {
                background-color: #3b82f6 !important;
                border-color: #3b82f6 !important;
            }
            .fc-button-primary:hover {
                background-color: #2563eb !important;
                border-color: #2563eb !important;
            }
            .fc-button-active {
                background-color: #1d4ed8 !important;
                border-color: #1d4ed8 !important;
            }
        </style>
    @endpush

    <div class="max-w-8xl space-y-4">
        <!-- Header Card -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-calendar-alt text-primary mr-2"></i>Lịch hoạt động cơ hội kinh doanh
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Lên lịch và theo dõi các hoạt động tư vấn, họp mặt và demo sản phẩm với khách hàng.</p>
                </div>
                
                <div class="flex gap-2">
                    <a href="{{ route('opportunities.index', ['view' => 'list'] + request()->except('view')) }}"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-semibold">
                        <i class="fas fa-list mr-2 text-gray-500"></i>Dạng danh sách
                    </a>
                    <a href="{{ route('opportunities.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg transition-colors text-sm font-semibold shadow-sm">
                        <i class="fas fa-plus mr-2"></i>Thêm hoạt động mới
                    </a>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
            <form id="filter_form" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Khách hàng</label>
                    <input type="text" name="customer_name" id="filter_customer_name" placeholder="Tên công ty, EU..."
                        class="w-full border-gray-300 rounded-lg text-sm focus:border-primary focus:ring-primary py-2 px-3">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Loại hoạt động</label>
                    <select name="activity_type" id="filter_activity_type"
                        class="w-full border-gray-300 rounded-lg text-sm focus:border-primary focus:ring-primary bg-white py-2 px-3">
                        <option value="">-- Tất cả --</option>
                        @foreach($activityTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Trạng thái</label>
                    <select name="status" id="filter_status"
                        class="w-full border-gray-300 rounded-lg text-sm focus:border-primary focus:ring-primary bg-white py-2 px-3">
                        <option value="">-- Tất cả --</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Sales phụ trách</label>
                    <select name="assigned_to" id="filter_assigned_to"
                        class="w-full border-gray-300 rounded-lg text-sm focus:border-primary focus:ring-primary bg-white py-2 px-3">
                        <option value="">-- Tất cả --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Kỹ thuật phụ trách</label>
                    <select name="technical_user" id="filter_technical_user"
                        class="w-full border-gray-300 rounded-lg text-sm focus:border-primary focus:ring-primary bg-white py-2 px-3">
                        <option value="">-- Tất cả --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 py-2 bg-primary hover:bg-primary-dark text-white rounded-lg transition-colors text-sm font-semibold flex items-center justify-center">
                        <i class="fas fa-filter mr-1.5"></i> Lọc lịch
                    </button>
                    <button type="button" onclick="clearFilters()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors text-sm font-medium">
                        Xóa lọc
                    </button>
                </div>
            </form>
        </div>

        <!-- Calendar Container -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
            <div id="calendar"></div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/vi.js"></script>
        <script>
            let calendar = null;

            document.addEventListener('DOMContentLoaded', function() {
                const calendarEl = document.getElementById('calendar');
                
                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'vi',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    firstDay: 1, // Thứ 2 là ngày đầu tuần
                    selectable: true,
                    select: function(info) {
                        let datePart = info.startStr.split('T')[0];
                        let startTime = '';
                        let endTime = '';
                        
                        if (info.startStr.includes('T')) {
                            startTime = info.startStr.split('T')[1].substring(0, 5);
                            endTime = info.endStr.split('T')[1].substring(0, 5);
                        }
                        
                        let url = `/opportunities/create?date=${datePart}`;
                        if (startTime) {
                            url += `&start_time=${startTime}`;
                        }
                        if (endTime) {
                            url += `&end_time=${endTime}`;
                        }
                        window.location.href = url;
                    },
                    events: function(info, successCallback, failureCallback) {
                        // Gọi API lấy sự kiện kèm query params
                        const params = new URLSearchParams({
                            start: info.startStr,
                            end: info.endStr,
                            customer_name: document.getElementById('filter_customer_name').value,
                            activity_type: document.getElementById('filter_activity_type').value,
                            status: document.getElementById('filter_status').value,
                            assigned_to: document.getElementById('filter_assigned_to').value,
                            technical_user: document.getElementById('filter_technical_user').value,
                        });

                        fetch(`/opportunities/calendar-events?${params.toString()}`, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(res => res.json())
                        .then(events => {
                            successCallback(events);
                        })
                        .catch(err => {
                            console.error('Lỗi tải dữ liệu lịch:', err);
                            failureCallback(err);
                        });
                    },
                    eventClick: function(info) {
                        info.jsEvent.preventDefault(); // Ngăn redirect mặc định để custom
                        if (info.event.url) {
                            window.location.href = info.event.url;
                        }
                    },
                    eventDidMount: function(info) {
                        // Thêm tooltip đơn giản khi hover
                        const props = info.event.extendedProps;
                        const tooltip = `Khách hàng: ${props.customer}\nTrạng thái: ${props.statusLabel}\nPhụ trách: ${props.assignedTo || 'Chưa phân công'}`;
                        info.el.setAttribute('title', tooltip);
                    }
                });

                calendar.render();

                // Lắng nghe sự kiện submit của form filter
                document.getElementById('filter_form').addEventListener('submit', function(e) {
                    e.preventDefault();
                    calendar.refetchEvents(); // Refresh calendar events
                });
            });

            function clearFilters() {
                document.getElementById('filter_customer_name').value = '';
                document.getElementById('filter_activity_type').value = '';
                document.getElementById('filter_status').value = '';
                document.getElementById('filter_assigned_to').value = '';
                document.getElementById('filter_technical_user').value = '';
                if (calendar) {
                    calendar.refetchEvents();
                }
            }
        </script>
    @endpush
@endsection