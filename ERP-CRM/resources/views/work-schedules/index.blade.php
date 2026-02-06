@extends('layouts.app')

@section('title', 'Lịch làm việc')
@section('page-title', 'Lịch làm việc')

@push('styles')
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    <style>
        .fc-event {
            cursor: pointer;
            border: none !important;
            background: transparent !important;
            box-shadow: none !important;
        }
        .fc-day-sat, .fc-day-sun {
            background-color: #f9fafb;
        }
        .custom-event {
            transition: transform 0.1s;
        }
        .custom-event:hover {
            transform: scale(1.02);
            z-index: 50;
        }
    </style>
@endpush

@section('content')
<div class="flex flex-col h-full space-y-4" x-data="calendarApp()">
    <!-- Legend & Filter -->
    <div class="bg-white p-4 rounded-lg shadow-sm flex flex-col sm:flex-row justify-between items-center space-y-3 sm:space-y-0">
        <div class="flex items-center space-x-4 overflow-x-auto w-full sm:w-auto pb-2 sm:pb-0">
            <div class="flex items-center space-x-2">
                <span class="text-sm font-semibold text-gray-700 whitespace-nowrap">Mức độ ưu tiên:</span>
                <div class="flex items-center space-x-1"><span class="w-3 h-3 rounded-full bg-blue-600"></span><span class="text-xs font-medium">Thấp</span></div>
                <div class="flex items-center space-x-1"><span class="w-3 h-3 rounded-full bg-orange-500"></span><span class="text-xs font-medium">Trung bình</span></div>
                <div class="flex items-center space-x-1"><span class="w-3 h-3 rounded-full bg-red-600"></span><span class="text-xs font-medium">Cao/Gấp</span></div>
            </div>
            <span class="text-gray-300">|</span>
            <div class="flex items-center space-x-2">
                <span class="text-sm font-semibold text-gray-700 whitespace-nowrap">Trạng thái:</span>
                <div class="flex items-center space-x-1"><i class="fas fa-check-circle text-green-600 text-sm"></i><span class="text-xs font-medium">Hoàn thành</span></div>
                <div class="flex items-center space-x-1"><i class="fas fa-exclamation-circle text-red-600 text-sm"></i><span class="text-xs font-medium">Quá hạn</span></div>
            </div>
        </div>

        <div class="flex items-center space-x-2 w-full sm:w-auto">
            <select x-model="filterType" @change="refetchEvents()" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                <option value="all">Tất cả loại lịch</option>
                <option value="personal">Lịch cá nhân</option>
                <option value="group">Lịch nhóm</option>
            </select>
            <button @click="openModal()" class="bg-primary hover:bg-primary-dark text-white px-3 py-2 rounded-lg flex items-center shadow-sm text-sm whitespace-nowrap">
                <i class="fas fa-plus mr-1"></i> Thêm mới
            </button>
        </div>
    </div>

    <!-- Calendar Container -->
    <div class="flex-1 bg-white rounded-lg shadow-sm p-4 overflow-hidden flex flex-col">
        <div id="calendar" class="flex-1 min-h-[600px]"></div>
    </div>

    <!-- Event Modal -->
    <div x-show="isModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="isModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="isModalOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="flex justify-between items-start">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title" x-text="isEditMode ? 'Cập nhật sự kiện' : 'Thêm sự kiện mới'"></h3>
                        <button @click="closeModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <form id="eventForm" @submit.prevent="saveEvent" class="mt-4 space-y-4">
                        <!-- Title -->
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Tiêu đề <span class="text-red-500">*</span></label>
                            <input type="text" id="title" x-model="form.title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" required placeholder="Nhập tiêu đề công việc...">
                        </div>

                        <!-- Date Time Range -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="start_datetime" class="block text-sm font-medium text-gray-700">Bắt đầu <span class="text-red-500">*</span></label>
                                <input type="text" id="start_datetime" x-ref="startPicker" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" required placeholder="Chọn thời gian">
                            </div>
                            <div>
                                <label for="end_datetime" class="block text-sm font-medium text-gray-700">Kết thúc</label>
                                <input type="text" id="end_datetime" x-ref="endPicker" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50" placeholder="Chọn thời gian (tùy chọn)">
                            </div>
                        </div>

                        <!-- Type & Priority -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700">Loại lịch <span class="text-red-500">*</span></label>
                                <select id="type" x-model="form.type" @change="handleTypeChange()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                                    <option value="personal">Cá nhân</option>
                                    <option value="group">Nhóm / Deadline</option>
                                </select>
                            </div>
                            <div>
                                <label for="priority" class="block text-sm font-medium text-gray-700">Mức độ ưu tiên <span class="text-red-500">*</span></label>
                                <select id="priority" x-model="form.priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                                    <option value="low">Thấp</option>
                                    <option value="medium">Trung bình</option>
                                    <option value="high">Cao / Gấp</option>
                                </select>
                            </div>
                        </div>

                        <!-- Status (Only for Edit) -->
                        <div x-show="isEditMode">
                            <label for="status" class="block text-sm font-medium text-gray-700">Trạng thái</label>
                            <select id="status" x-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50">
                                <option value="new">Mới</option>
                                <option value="in_progress">Đang làm</option>
                                <option value="completed">Hoàn thành</option>
                                <option value="overdue">Quá hạn</option>
                            </select>
                        </div>

                        <!-- Participants (Show if Group) -->
                        <div x-show="form.type === 'group'" x-transition>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Người tham gia</label>
                            <div class="border rounded-md max-h-40 overflow-y-auto participants-list p-2 space-y-2 bg-gray-50">
                                @foreach($users as $user)
                                    <div class="flex items-center">
                                        <input type="checkbox" id="user_{{ $user->id }}" value="{{ $user->id }}" x-model="form.participants" class="rounded border-gray-300 text-primary shadow-sm focus:border-primary focus:ring focus:ring-offset-0 focus:ring-primary focus:ring-opacity-50">
                                        <label for="user_{{ $user->id }}" class="ml-2 text-sm text-gray-700 cursor-pointer flex items-center">
                                            <div class="w-6 h-6 rounded-full bg-gray-300 flex items-center justify-center text-xs mr-2 text-white font-bold">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                            {{ $user->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Mô tả chi tiết</label>
                            <textarea id="description" x-model="form.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50"></textarea>
                        </div>
                    </form>
                </div>
                <!-- Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" form="eventForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-medium text-white hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm">
                        <span x-text="isEditMode ? 'Cập nhật' : 'Lưu'"></span>
                    </button>
                    
                    <button x-show="isEditMode && canEdit" type="button" @click="deleteEvent()" class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Xóa
                    </button>

                    <button type="button" @click="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Hủy
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('calendarApp', () => ({
            calendar: null,
            isModalOpen: false,
            isEditMode: false,
            canEdit: false,
            currentEventId: null,
            startPicker: null,
            endPicker: null,
            filterType: 'all',
            form: {
                title: '',
                start_datetime: '',
                end_datetime: '',
                type: 'personal',
                priority: 'medium',
                status: 'new',
                description: '',
                participants: []
            },

            init() {
                this.initCalendar();
                this.initPickers();
            },

            initPickers() {
                const config = {
                    enableTime: true,
                    dateFormat: "Y-m-d H:i",
                    time_24hr: true,
                    locale: "vn"
                };
                
                this.startPicker = flatpickr(this.$refs.startPicker, {
                    ...config,
                    onChange: (selectedDates, dateStr, instance) => {
                        this.form.start_datetime = dateStr;
                    }
                });

                this.endPicker = flatpickr(this.$refs.endPicker, {
                    ...config,
                    onChange: (selectedDates, dateStr, instance) => {
                        this.form.end_datetime = dateStr;
                    }
                });
            },

            initCalendar() {
                const calendarEl = document.getElementById('calendar');
                this.calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    locale: 'vi',
                    selectable: true,
                    editable: true,
                    themeSystem: 'standard',
                    events: {
                        url: '{{ route("work-schedules.events") }}',
                        extraParams: () => {
                            return {
                                filter_type: this.filterType
                            };
                        }
                    },
                    
                    eventContent: function(arg) {
                        const props = arg.event.extendedProps;
                        
                        // Priority Colors - Màu đậm và rõ ràng hơn
                        let borderColorClass = 'border-blue-600';
                        let cardBg = 'bg-blue-100 text-blue-900';
                        let borderWidth = 'border-l-4';
                        
                        if (props.priority === 'high') {
                            borderColorClass = 'border-red-600';
                            cardBg = 'bg-red-100 text-red-900';
                        } else if (props.priority === 'medium') {
                            borderColorClass = 'border-orange-500';
                            cardBg = 'bg-orange-100 text-orange-900';
                        }

                        // Status styling
                        let statusIcon = '';
                        if (props.status === 'completed') statusIcon = '<i class="fas fa-check-circle text-green-500 ml-1"></i>';
                        if (props.status === 'overdue') statusIcon = '<i class="fas fa-exclamation-circle text-red-600 ml-1"></i>';

                        // Avatars
                        let avatarsHtml = '';
                        if (props.participants && props.participants.length > 0) {
                            avatarsHtml = '<div class="flex -space-x-1 ml-auto">';
                            props.participants.slice(0, 3).forEach(p => {
                                let initial = p.name ? p.name.charAt(0) : '?';
                                avatarsHtml += `<div class="w-5 h-5 rounded-full bg-gray-400 flex items-center justify-center text-white text-[10px] border border-white" title="${p.name}">${initial}</div>`;
                            });
                            if (props.participants.length > 3) {
                                avatarsHtml += `<div class="w-5 h-5 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 text-[9px] border border-white">+${props.participants.length - 3}</div>`;
                            }
                            avatarsHtml += '</div>';
                        }

                        // Time formatting
                        let timeText = '';
                        if (!arg.event.allDay) {
                             const start = arg.event.start;
                             timeText = start.getHours().toString().padStart(2, '0') + ':' + start.getMinutes().toString().padStart(2, '0');
                        }

                        return {
                            html: `
                            <div class="custom-event p-1.5 pl-2 ${borderWidth} rounded-r shadow-sm text-xs overflow-hidden ${borderColorClass} ${cardBg} flex items-center font-medium">
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold truncate">
                                        ${timeText ? `<span class="opacity-80 mr-1 font-bold">${timeText}</span>` : ''}
                                        ${arg.event.title}
                                    </div>
                                </div>
                                ${statusIcon}
                                ${avatarsHtml}
                            </div>
                            `
                        };
                    },

                    eventDidMount: function(info) {
                        const props = info.event.extendedProps;
                        const statusMap = {
                            'new': 'Mới',
                            'in_progress': 'Đang làm',
                            'completed': 'Hoàn thành',
                            'overdue': 'Quá hạn'
                        };
                        const priorityMap = {
                            'low': 'Thấp',
                            'medium': 'Trung bình',
                            'high': 'Cao'
                        };

                        let creatorText = props.creator ? `<p><strong>Người tạo:</strong> ${props.creator}</p>` : '';
                        let deadlineText = info.event.end 
                            ? `<p><strong>Kết thúc:</strong> ${info.event.end.toLocaleString('vi-VN')}</p>` 
                            : '';

                        tippy(info.el, {
                            content: `
                                <div class="text-left text-sm">
                                    <p class="font-bold text-base mb-1">${info.event.title}</p>
                                    <p class="mb-1">${props.description || 'Không có mô tả'}</p>
                                    <div class="border-t border-gray-500 my-1 pt-1 opacity-75 text-xs">
                                        <p><strong>Ưu tiên:</strong> ${priorityMap[props.priority] || props.priority}</p>
                                        <p><strong>Trạng thái:</strong> ${statusMap[props.status] || props.status}</p>
                                        ${deadlineText}
                                        ${creatorText}
                                    </div>
                                </div>
                            `,
                            allowHTML: true,
                            theme: 'light-border',
                            placement: 'top',
                            animation: 'scale',
                        });
                    },

                    select: (info) => {
                        this.resetForm();
                        this.startPicker.setDate(info.startStr + ' 08:00');
                        this.form.start_datetime = info.startStr + ' 08:00';
                        this.isEditMode = false;
                        this.isModalOpen = true;
                    },

                    eventClick: (info) => {
                        this.loadEvent(info.event);
                    },

                    eventDrop: (info) => {
                        this.updateEventDrop(info.event);
                    },

                    eventResize: (info) => {
                        this.updateEventDrop(info.event);
                    }
                });
                this.calendar.render();
            },

            refetchEvents() {
                this.calendar.refetchEvents();
            },

            // ... (keep resetForm, loadEvent, etc. same as before)
            resetForm() {
                this.form = {
                    title: '',
                    start_datetime: '',
                    end_datetime: '',
                    type: 'personal',
                    priority: 'medium',
                    status: 'new',
                    description: '',
                    participants: []
                };
                this.startPicker.clear();
                this.endPicker.clear();
                this.currentEventId = null;
                this.canEdit = true;
            },

            loadEvent(event) {
                this.resetForm();
                this.currentEventId = event.id;
                this.isEditMode = true;
                this.isModalOpen = true;

                // Load data
                this.form.title = event.title;
                
                const start = event.start ? this.formatDate(event.start) : '';
                const end = event.end ? this.formatDate(event.end) : '';

                this.form.start_datetime = start;
                this.form.end_datetime = end;
                this.startPicker.setDate(start);
                this.endPicker.setDate(end);

                const props = event.extendedProps;
                this.form.type = props.type;
                this.form.priority = props.priority;
                this.form.status = props.status;
                this.form.description = props.description;
                this.form.participants = props.participant_ids || []; // Using IDs from updated controller
                this.canEdit = props.can_edit;
            },
            
            formatDate(date) {
                const pad = (n) => n < 10 ? '0' + n : n;
                const d = new Date(date);
                return `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
            },

            openModal() {
                this.resetForm();
                this.isEditMode = false;
                this.isModalOpen = true;
                const now = new Date();
                const nowStr = this.formatDate(now);
                this.startPicker.setDate(nowStr);
                this.form.start_datetime = nowStr;
            },

            closeModal() {
                this.isModalOpen = false;
            },

            handleTypeChange() {},

            saveEvent() {
                if (!this.form.title || !this.form.start_datetime) {
                    Swal.fire('Lỗi', 'Vui lòng nhập tiêu đề và thời gian bắt đầu', 'error');
                    return;
                }

                const url = this.isEditMode 
                    ? `/work-schedules/${this.currentEventId}` 
                    : '{{ route("work-schedules.store") }}';
                
                const method = this.isEditMode ? 'PUT' : 'POST';

                axios({
                    method: method,
                    url: url,
                    data: this.form
                })
                .then(response => {
                    if (response.data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: response.data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        this.calendar.refetchEvents();
                        this.closeModal();
                    }
                })
                .catch(error => {
                    console.error(error);
                    Swal.fire('Lỗi', error.response?.data?.message || 'Có lỗi xảy ra', 'error');
                });
            },

            updateEventDrop(event) {
                const start = event.start ? this.formatDate(event.start) : '';
                const end = event.end ? this.formatDate(event.end) : '';
                
                axios.put(`/work-schedules/${event.id}`, {
                    title: event.title,
                    start_datetime: start,
                    end_datetime: end,
                    type: event.extendedProps.type,
                    priority: event.extendedProps.priority,
                    status: event.extendedProps.status,
                    participants: event.extendedProps.participant_ids // Use participant_ids for update
                })
                .then(response => {
                    if (!response.data.success) {
                        event.revert();
                        Swal.fire('Lỗi', response.data.message, 'error');
                    }
                })
                .catch(error => {
                    event.revert();
                    Swal.fire('Lỗi', 'Không thể cập nhật thời gian', 'error');
                });
            },

            deleteEvent() {
                Swal.fire({
                    title: 'Bạn có chắc chắn?',
                    text: "Bạn sẽ không thể hoàn tác hành động này!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Xóa',
                    cancelButtonText: 'Hủy'
                }).then((result) => {
                    if (result.isConfirmed) {
                        axios.delete(`/work-schedules/${this.currentEventId}`)
                        .then(response => {
                            if (response.data.success) {
                                Swal.fire('Đã xóa!', response.data.message, 'success');
                                this.calendar.refetchEvents();
                                this.closeModal();
                            }
                        })
                        .catch(error => {
                            Swal.fire('Lỗi', error.response?.data?.message || 'Có lỗi xảy ra', 'error');
                        });
                    }
                });
            }
        }));
    });
</script>
@endpush
