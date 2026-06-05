@extends('layouts.app')

@section('title', 'Chi tiết hoạt động cơ hội: ' . $opportunity->name)

@section('content')
    <div class="max-w-8xl space-y-6">
        <!-- Header Card -->
        <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-xs font-bold {{ $opportunity->status_color }}" id="status_badge">
                            {{ $opportunity->status_label }}
                        </span>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-800 border border-gray-200">
                            <i class="fas fa-tag mr-1 text-gray-500"></i>{{ $opportunity->activity_type_label }}
                        </span>
                        <h1 class="text-2xl font-bold text-gray-900 ml-1">{{ $opportunity->name }}</h1>
                    </div>
                    <div class="text-sm text-gray-600 flex flex-wrap gap-y-2 gap-x-6 items-center">
                        <span class="flex items-center gap-1.5"><i class="fas fa-building text-gray-400"></i><strong>{{ $opportunity->customer_display_name }}</strong></span>
                        <span class="flex items-center gap-1.5"><i class="far fa-calendar-alt text-gray-400"></i>{{ $opportunity->activity_date->format('d/m/Y') }}</span>
                        <span class="flex items-center gap-1.5"><i class="far fa-clock text-gray-400"></i>{{ $opportunity->start_time ?: 'N/A' }} - {{ $opportunity->end_time ?: 'N/A' }} ({{ $opportunity->duration_minutes }} phút)</span>
                    </div>
                </div>
                
                <div class="flex flex-wrap items-center gap-3 lg:self-center">
                    <!-- Link to Project if already converted -->
                    @if($opportunity->project_id)
                        <a href="{{ route('projects.show', $opportunity->project_id) }}"
                            class="px-4 py-2 bg-green-50 hover:bg-green-100 text-green-700 border border-green-200 rounded-lg font-semibold text-sm flex items-center gap-2 transition-colors">
                            <i class="fas fa-project-diagram"></i> Xem dự án đã liên kết
                        </a>
                    @else
                        <!-- Convert button: Only show if status is completed (and project_id is null) -->
                        @if($opportunity->status === 'completed')
                            <form action="{{ route('opportunities.convert-project', $opportunity->id) }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit"
                                    class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold text-sm flex items-center gap-2 shadow-sm transition-colors">
                                    <i class="fas fa-exchange-alt"></i> Chuyển sang Đăng ký dự án
                                </button>
                            </form>
                        @endif
                    @endif

                    <a href="{{ route('opportunities.edit', $opportunity) }}"
                        class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-semibold text-sm flex items-center gap-2 transition-colors">
                        <i class="fas fa-pencil-alt text-gray-500"></i> Chỉnh sửa
                    </a>
                    
                    <form action="{{ route('opportunities.destroy', $opportunity) }}" method="POST" class="m-0"
                        onsubmit="return confirm('Bạn có chắc chắn muốn xóa hoạt động cơ hội này? Điều này cũng sẽ xóa tất cả tài liệu đính kèm.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="px-4 py-2 bg-red-50 text-red-700 border border-red-200 rounded-lg hover:bg-red-100 font-semibold text-sm flex items-center gap-2 transition-colors">
                            <i class="fas fa-trash-alt"></i> Xóa
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Cột trái: Thông tin khách hàng, chi tiết hoạt động, tệp đính kèm, form báo cáo -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Customer Info Card -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-805 mb-4 pb-2 border-b border-gray-150 flex items-center">
                        <i class="fas fa-building mr-2 text-blue-600"></i>Thông tin Khách hàng ({{ $opportunity->customer_type === 'si' ? 'SI' : 'EU' }})
                    </h2>

                    @if($opportunity->customer_type === 'si' && $opportunity->customer)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                            <!-- Company -->
                            <div class="space-y-1.5">
                                <span class="text-gray-400 text-xs font-semibold uppercase tracking-wider block">Tên công ty SI:</span>
                                <a href="{{ route('customers.show', $opportunity->customer_id) }}" class="font-bold text-blue-600 hover:underline block text-base leading-tight">
                                    {{ $opportunity->customer->name }}
                                </a>
                                <span class="text-gray-400 text-xs block mt-1">Mã số thuế: <strong class="text-gray-700 font-semibold">{{ $opportunity->customer->tax_code ?: 'N/A' }}</strong></span>
                            </div>
                            
                            <!-- PIC -->
                            @if($opportunity->contact)
                                <div class="space-y-1.5 border-t md:border-t-0 md:border-l border-gray-100 md:pl-6">
                                    <span class="text-gray-400 text-xs font-semibold uppercase tracking-wider block">Người liên hệ (Contact Point):</span>
                                    <strong class="text-gray-800 text-base block leading-tight">{{ $opportunity->contact->name }}</strong>
                                    <span class="text-gray-500 text-xs block mt-1">Chức vụ: <strong class="text-gray-700 font-semibold">{{ $opportunity->contact->position ?: 'N/A' }}</strong></span>
                                </div>

                                <!-- Call Actions -->
                                <div class="space-y-3 border-t md:border-t-0 md:border-l border-gray-100 md:pl-6">
                                    <span class="text-gray-400 text-xs font-semibold uppercase tracking-wider block">Liên hệ nhanh:</span>
                                    <div class="space-y-1 text-xs">
                                        <div class="flex items-center gap-1.5 text-gray-750 font-medium"><i class="fas fa-phone-alt text-gray-400 w-4"></i>{{ $opportunity->contact->phone }}</div>
                                        <div class="flex items-center gap-1.5 text-gray-755 font-medium truncate"><i class="far fa-envelope text-gray-400 w-4"></i>{{ $opportunity->contact->email }}</div>
                                    </div>
                                    <div class="flex items-center gap-2 mt-2">
                                        <a href="tel:{{ $opportunity->contact->phone }}" class="flex-1 inline-flex items-center justify-center gap-1 px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-755 rounded-lg text-xs font-bold transition-colors">
                                            <i class="fas fa-phone-alt"></i> Gọi điện
                                        </a>
                                        <a href="mailto:{{ $opportunity->contact->email }}" class="flex-1 inline-flex items-center justify-center gap-1 px-2.5 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-755 rounded-lg text-xs font-bold transition-colors">
                                            <i class="far fa-envelope"></i> Gửi email
                                        </a>
                                    </div>
                                </div>
                            @else
                                <div class="space-y-1.5 border-t md:border-t-0 md:border-l border-gray-100 md:pl-6 col-span-2">
                                    <span class="text-gray-400 text-xs font-semibold uppercase tracking-wider block">Người liên hệ:</span>
                                    <span class="text-gray-500 italic text-sm">Chưa chọn Contact Point.</span>
                                </div>
                            @endif
                        </div>
                    @else
                        <!-- EU Mode Info Display -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
                            <div class="space-y-1.5">
                                <span class="text-gray-400 text-xs font-semibold uppercase tracking-wider block">Tên công ty EU:</span>
                                <strong class="text-gray-800 text-base block leading-tight">{{ $opportunity->eu_company_name }}</strong>
                            </div>
                            <div class="space-y-1.5 border-t md:border-t-0 md:border-l border-gray-100 md:pl-6">
                                <span class="text-gray-400 text-xs font-semibold uppercase tracking-wider block">Người liên hệ EU:</span>
                                <strong class="text-gray-800 text-base block leading-tight">{{ $opportunity->eu_contact_name ?: 'N/A' }}</strong>
                                <span class="text-gray-500 text-xs block mt-1">Chức vụ: <strong class="text-gray-700 font-semibold">{{ $opportunity->eu_position ?: 'N/A' }}</strong></span>
                            </div>
                            <div class="space-y-2 border-t md:border-t-0 md:border-l border-gray-100 md:pl-6">
                                <span class="text-gray-400 text-xs font-semibold uppercase tracking-wider block">Thông tin liên hệ:</span>
                                <div class="space-y-1 text-xs">
                                    <div class="flex items-center gap-1.5 text-gray-750 font-medium"><i class="fas fa-phone-alt text-gray-400 w-4"></i>{{ $opportunity->eu_phone ?: 'N/A' }}</div>
                                    <div class="flex items-center gap-1.5 text-gray-755 font-medium truncate"><i class="far fa-envelope text-gray-400 w-4"></i>{{ $opportunity->eu_email ?: 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Chi tiết hoạt động lên lịch -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-800 mb-5 pb-2 border-b border-gray-100 flex items-center">
                        <i class="fas fa-clipboard-list mr-2 text-blue-600"></i>Chi tiết hoạt động lên lịch
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm mb-4">
                        <div class="md:col-span-2">
                            <span class="text-gray-400 block text-xs font-semibold uppercase tracking-wider mb-1">Nội dung chi tiết hoạt động:</span>
                            <textarea readonly class="w-full text-gray-800 bg-gray-50 p-4 rounded-lg border border-gray-100 border-l-4 border-l-blue-500 focus:outline-none resize-y text-sm font-sans leading-relaxed" rows="3">{{ $opportunity->description ?: 'Không có mô tả chi tiết.' }}</textarea>
                        </div>
                        <div class="md:col-span-2">
                            <span class="text-gray-400 block text-xs font-semibold uppercase tracking-wider mb-1">Ghi chú nội bộ:</span>
                            <textarea readonly class="w-full text-gray-800 bg-gray-50 p-4 rounded-lg border border-gray-100 border-l-4 border-l-amber-500 focus:outline-none resize-y text-sm font-sans leading-relaxed" rows="3">{{ $opportunity->notes ?: 'Không có ghi chú.' }}</textarea>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-xs font-semibold uppercase tracking-wider mb-1">Yêu cầu chuẩn bị (Materials/Documents):</span>
                            <textarea readonly class="w-full text-gray-800 bg-gray-50 p-4 rounded-lg border border-gray-100 focus:outline-none resize-y text-sm font-sans leading-relaxed" rows="3">{{ $opportunity->materials_required ?: 'Không có yêu cầu.' }}</textarea>
                        </div>
                        <div>
                            <span class="text-gray-400 block text-xs font-semibold uppercase tracking-wider mb-1">Quà tặng / Giveaway:</span>
                            <textarea readonly class="w-full text-gray-800 bg-gray-50 p-4 rounded-lg border border-gray-100 focus:outline-none resize-y text-sm font-sans leading-relaxed" rows="3">{{ $opportunity->giveaway ?: 'Không có quà tặng.' }}</textarea>
                            
                            @if($opportunity->giveaway)
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-3 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Trạng thái duyệt:</span>
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $opportunity->giveaway_status_color }}">
                                            {{ $opportunity->giveaway_status_label }}
                                        </span>
                                    </div>
                                    
                                    @if(auth()->user()->hasAnyRole(['super_admin', 'admin', 'sales_manager']))
                                        <div class="flex gap-2">
                                            @if($opportunity->giveaway_status !== 'approved')
                                                <form action="{{ route('opportunities.approve-giveaway', $opportunity->id) }}" method="POST" class="m-0">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-xs font-bold flex items-center gap-1 shadow-sm transition-colors">
                                                        <i class="fas fa-check"></i> Duyệt quà tặng
                                                    </button>
                                                </form>
                                            @endif
                                            @if($opportunity->giveaway_status !== 'rejected')
                                                <form action="{{ route('opportunities.reject-giveaway', $opportunity->id) }}" method="POST" class="m-0">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-lg text-xs font-bold flex items-center gap-1 transition-colors">
                                                        <i class="fas fa-times"></i> Từ chối
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Technical Coordination info -->
                    <div class="border-t border-gray-100 pt-5 mt-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700 flex items-center gap-1.5">
                                <i class="fas fa-cogs text-gray-400"></i> Phối hợp kỹ thuật:
                            </span>
                            @if($opportunity->needs_technical)
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    <i class="fas fa-check-circle mr-1"></i>Yêu cầu phối hợp kỹ thuật
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 border border-gray-200">
                                    Không yêu cầu
                                </span>
                            @endif
                        </div>
                        @if($opportunity->needs_technical && $opportunity->technicalUser)
                            <div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-4 mt-3 flex items-center gap-4 text-sm">
                                <div class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                                    {{ substr($opportunity->technicalUser->name, 0, 1) }}
                                </div>
                                <div>
                                    <span class="text-gray-400 text-xs block font-semibold uppercase tracking-wider">Kỹ sư phối hợp (Technical Manager)</span>
                                    <strong class="text-indigo-900 text-base">{{ $opportunity->technicalUser->name }}</strong>
                                    <span class="text-indigo-700 text-xs block mt-0.5"><i class="far fa-envelope mr-1"></i>{{ $opportunity->technicalUser->email }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Attachments Card -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100 flex items-center justify-between">
                        <span class="flex items-center"><i class="fas fa-paperclip mr-2 text-blue-600"></i>Tập tin đính kèm</span>
                        <span class="bg-gray-100 text-gray-600 text-xs font-semibold px-2.5 py-0.5 rounded-full" id="attachment_count">
                            {{ $opportunity->attachments->count() }}
                        </span>
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="attachment_list">
                        @forelse($opportunity->attachments as $attachment)
                            <div class="flex items-start justify-between gap-3 p-3 border border-gray-100 rounded-xl bg-gray-50/50 hover:bg-gray-50 transition-colors text-sm" id="attachment_row_{{ $attachment->id }}">
                                <div class="flex items-start gap-3 overflow-hidden">
                                    <i class="{{ $attachment->file_icon }} text-2xl mt-0.5 flex-shrink-0 text-gray-550"></i>
                                    <div class="overflow-hidden">
                                        <a href="{{ asset('storage/' . $attachment->file_path) }}" target="_blank"
                                            class="font-semibold text-blue-600 hover:underline block truncate" title="{{ $attachment->file_name }}">
                                            {{ $attachment->file_name }}
                                        </a>
                                        <span class="text-gray-400 text-xs block mt-0.5">
                                            {{ $attachment->file_size_formatted }} • {{ $attachment->uploader?->name ?: 'N/A' }}
                                        </span>
                                        @if($attachment->note)
                                            <span class="text-gray-500 text-xs block italic truncate mt-0.5" title="{{ $attachment->note }}">{{ $attachment->note }}</span>
                                        @endif
                                    </div>
                                </div>
                                <button type="button" onclick="deleteAttachment({{ $attachment->id }})"
                                    class="text-gray-400 hover:text-red-500 p-1.5 transition-colors">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-400 md:col-span-2" id="no_attachments_msg">
                                <i class="far fa-folder-open text-3xl mb-1 text-gray-300"></i>
                                <p class="text-xs">Chưa có tập tin nào.</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Quick AJAX File Uploader -->
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Thêm tệp tin đính kèm</label>
                        <div class="flex gap-2 max-w-md">
                            <input type="file" id="quick_file_input" class="hidden" onchange="uploadQuickAttachment()">
                            <button type="button" onclick="document.getElementById('quick_file_input').click()" 
                                class="px-4 py-2 bg-gray-50 border border-gray-350 hover:bg-gray-100 hover:border-gray-400 rounded-lg text-xs font-bold text-gray-700 transition-colors flex items-center gap-1.5">
                                <i class="fas fa-plus text-gray-400"></i> Chọn tệp tải lên
                            </button>
                            <span id="quick_upload_status" class="hidden text-xs text-blue-600 font-semibold self-center animate-pulse">Đang tải lên...</span>
                        </div>
                    </div>
                </div>

                <!-- FORM BÁO CÁO KẾT QUẢ HOẠT ĐỘNG (Phase 2) -->
                @if(in_array($opportunity->status, ['in_progress', 'completed']))
                    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                        <h2 class="text-lg font-semibold text-gray-800 mb-5 pb-2 border-b border-gray-100 flex items-center">
                            <i class="fas fa-file-signature mr-2 text-green-600"></i>Báo cáo kết quả hoạt động (Giai đoạn 2)
                        </h2>

                        <form action="{{ route('opportunities.update', $opportunity->id) }}" method="POST" enctype="multipart/form-data" id="report_form">
                            @csrf
                            @method('PUT')
                            
                            <!-- Giữ lại các field cũ trong database (hidden) -->
                            <input type="hidden" name="customer_type" value="{{ $opportunity->customer_type }}">
                            <input type="hidden" name="customer_id" value="{{ $opportunity->customer_id }}">
                            <input type="hidden" name="contact_id" value="{{ $opportunity->contact_id }}">
                            <input type="hidden" name="eu_company_name" value="{{ $opportunity->eu_company_name }}">
                            <input type="hidden" name="eu_contact_name" value="{{ $opportunity->eu_contact_name }}">
                            <input type="hidden" name="eu_phone" value="{{ $opportunity->eu_phone }}">
                            <input type="hidden" name="eu_email" value="{{ $opportunity->eu_email }}">
                            <input type="hidden" name="eu_position" value="{{ $opportunity->eu_position }}">
                            <input type="hidden" name="name" value="{{ $opportunity->name }}">
                            <input type="hidden" name="activity_type" value="{{ $opportunity->activity_type }}">
                            <input type="hidden" name="activity_type_other" value="{{ $opportunity->activity_type_other }}">
                            <input type="hidden" name="activity_date" value="{{ $opportunity->activity_date->format('Y-m-d') }}">
                            <input type="hidden" name="start_time" value="{{ $opportunity->start_time }}">
                            <input type="hidden" name="end_time" value="{{ $opportunity->end_time }}">
                            <input type="hidden" name="description" value="{{ $opportunity->description }}">
                            <input type="hidden" name="notes" value="{{ $opportunity->notes }}">
                            <input type="hidden" name="materials_required" value="{{ $opportunity->materials_required }}">
                            <input type="hidden" name="giveaway" value="{{ $opportunity->giveaway }}">
                            <input type="hidden" name="needs_technical" value="{{ $opportunity->needs_technical ? '1' : '0' }}">
                            <input type="hidden" name="technical_user_id" value="{{ $opportunity->technical_user_id }}">
                            <input type="hidden" name="assigned_to" value="{{ $opportunity->assigned_to }}">

                            <div class="space-y-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Đánh giá tiềm năng -->
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Đánh giá mức độ tiềm năng <span class="text-red-500">*</span></label>
                                        <div class="flex flex-col sm:flex-row gap-3">
                                            @foreach($ratings as $key => $label)
                                                @php
                                                    $ratingColorClass = match($key) {
                                                        '90' => 'peer-checked:border-red-500 peer-checked:bg-red-50/50',
                                                        '75' => 'peer-checked:border-orange-500 peer-checked:bg-orange-50/50',
                                                        '50' => 'peer-checked:border-yellow-500 peer-checked:bg-yellow-50/50',
                                                        '25' => 'peer-checked:border-blue-500 peer-checked:bg-blue-50/50',
                                                        default => 'peer-checked:border-primary peer-checked:bg-blue-50/50'
                                                    };
                                                @endphp
                                                <label class="flex-1 relative">
                                                    <input type="radio" name="potential_rating" value="{{ $key }}" required
                                                        {{ old('potential_rating', $opportunity->potential_rating) == $key ? 'checked' : '' }}
                                                        class="sr-only peer">
                                                    <div class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors text-sm font-semibold text-gray-700 {{ $ratingColorClass }}">
                                                        <span class="w-3.5 h-3.5 rounded-full border border-gray-300 mr-2 flex items-center justify-center after:content-[''] after:w-2 after:h-2 after:rounded-full after:bg-transparent peer-checked:after:bg-current"></span>
                                                        {{ $label }}
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                        @error('potential_rating') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>

                                    <!-- Phản hồi khách hàng -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Ý kiến phản hồi của khách hàng <span class="text-red-500">*</span></label>
                                        <textarea name="customer_feedback" rows="3" required placeholder="Khách hàng phản hồi về giải pháp thế nào, thái độ..."
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">{{ old('customer_feedback', $opportunity->customer_feedback) }}</textarea>
                                        @error('customer_feedback') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>

                                    <!-- Kết quả meeting -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Kết quả buổi làm việc <span class="text-red-500">*</span></label>
                                        <textarea name="meeting_result" rows="3" required placeholder="Thống nhất được những gì, demo thành công hay lỗi..."
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">{{ old('meeting_result', $opportunity->meeting_result) }}</textarea>
                                        @error('meeting_result') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>

                                    <!-- Pain points -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Pain Points (Nỗi đau của khách hàng)</label>
                                        <textarea name="pain_points" rows="3" placeholder="Hệ thống cũ chậm, hay bị tấn công DDOS, chi phí cao..."
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">{{ old('pain_points', $opportunity->pain_points) }}</textarea>
                                    </div>

                                    <!-- Next action -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Next Action / Giai đoạn tiếp theo <span class="text-red-500">*</span></label>
                                        <textarea name="next_action" rows="3" required placeholder="VD: Gửi báo giá trước ngày X, Setup buổi POC tiếp theo..."
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">{{ old('next_action', $opportunity->next_action) }}</textarea>
                                        @error('next_action') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <!-- Drag & Drop File Upload -->
                                <div class="pt-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Tài liệu đính kèm báo cáo / Hình ảnh thực tế
                                        @if(in_array($opportunity->activity_type, ['meeting', 'project_meeting']))
                                            <span class="text-red-500 font-bold">* (Bắt buộc đính kèm hình ảnh/tài liệu khi hoàn thành báo cáo cuộc họp)</span>
                                        @endif
                                    </label>
                                    
                                    <div id="dropzone" class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:bg-gray-50/50 hover:border-primary transition-colors cursor-pointer relative">
                                        <input type="file" name="files[]" id="file_input" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                        <p class="text-sm font-semibold text-gray-700">Kéo thả file vào đây hoặc nhấp để chọn</p>
                                        <p class="text-xs text-gray-500 mt-1">Hỗ trợ PDF, Excel, Word, Hình ảnh (JPG, PNG) tối đa 10MB/file</p>
                                    </div>
                                    @error('files') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                                    <!-- Preview files selected to be uploaded -->
                                    <div id="file_list_preview" class="space-y-2 mt-3 hidden">
                                        <h4 class="text-xs font-semibold text-gray-500 uppercase">Tập tin chuẩn bị tải lên:</h4>
                                        <div id="preview_items" class="space-y-1.5"></div>
                                    </div>
                                </div>

                                <!-- Change Status to Completed toggle -->
                                <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                                    <div>
                                        <span class="text-sm font-semibold text-gray-850 font-bold">Đánh dấu Hoàn thành hoạt động này?</span>
                                        <p class="text-xs text-gray-500">Chuyển trạng thái sang "Đã hoàn thành" và ghi nhận báo cáo vào hệ thống.</p>
                                    </div>
                                    <select name="status" id="form_status_select" onchange="checkMeetingAttachmentRequired()"
                                        class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white">
                                        <option value="in_progress" {{ $opportunity->status == 'in_progress' ? 'selected' : '' }}>Đang thực hiện (Lưu nháp báo cáo)</option>
                                        <option value="completed" {{ $opportunity->status == 'completed' ? 'selected' : '' }}>Đã hoàn thành (Ghi nhận chính thức)</option>
                                    </select>
                                </div>

                                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                                    <button type="submit"
                                        class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all font-semibold text-sm shadow-sm flex items-center gap-2">
                                        <i class="fas fa-check-circle"></i> Lưu báo cáo kết quả
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

            <!-- Cột phải: Sidebar -->
            <div class="space-y-6">
                <!-- Sidebar Trạng thái hoạt động Select Box -->
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm space-y-4">
                    <h3 class="text-sm font-bold text-gray-800 pb-2 border-b border-gray-100 flex items-center">
                        <i class="fas fa-tasks mr-2 text-blue-600"></i>Trạng thái hoạt động <span class="text-red-500 ml-1">*</span>
                    </h3>
                    
                    <div>
                        <select id="sidebar_status_select" 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary bg-white font-medium">
                            <option value="draft" {{ $opportunity->status === 'draft' ? 'selected' : '' }}>Nháp (Draft)</option>
                            <option value="planned" {{ $opportunity->status === 'planned' ? 'selected' : '' }}>Đã lên lịch (Planned)</option>
                            <option value="confirmed" {{ $opportunity->status === 'confirmed' ? 'selected' : '' }}>Đã xác nhận (Confirmed)</option>
                            <option value="in_progress" {{ $opportunity->status === 'in_progress' ? 'selected' : '' }}>Đang thực hiện (In Progress)</option>
                            <option value="completed" {{ $opportunity->status === 'completed' ? 'selected' : '' }}>Đã hoàn thành (Completed)</option>
                            <option value="cancelled" {{ $opportunity->status === 'cancelled' ? 'selected' : '' }}>Đã hủy (Cancelled)</option>
                            <option value="postponed" {{ $opportunity->status === 'postponed' ? 'selected' : '' }}>Đã hoãn (Postponed)</option>
                        </select>
                    </div>

                    <!-- Cancel Reason field (only visible when Cancelled is selected) -->
                    <div id="sidebar_cancel_reason_box" class="hidden">
                        <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Lý do hủy <span class="text-red-500">*</span></label>
                        <textarea id="sidebar_cancel_reason" rows="2" 
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Nhập lý do hủy...">{{ $opportunity->cancel_reason }}</textarea>
                    </div>

                    <button type="button" onclick="updateSidebarStatus()" 
                        class="w-full px-4 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-lg transition-all font-semibold text-sm shadow-sm flex items-center justify-center gap-1.5">
                        <i class="fas fa-sync-alt"></i> Cập nhật trạng thái
                    </button>
                </div>

                <!-- Assignment & Tracking info -->
                <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm space-y-3.5 text-sm">
                    <h3 class="text-sm font-bold text-gray-400 mb-3 pb-2 border-b border-gray-100 flex items-center">
                        <i class="fas fa-history mr-2 text-gray-400"></i>Thông tin Theo dõi
                    </h3>
                    <div>
                        <span class="text-gray-400 text-xs block font-medium">Người phụ trách (Sales):</span>
                        <strong class="text-gray-800 block mt-0.5">{{ $opportunity->assignedTo?->name ?: 'N/A' }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-400 text-xs block font-medium">Người tạo:</span>
                        <strong class="text-gray-800 block mt-0.5">{{ $opportunity->createdBy?->name ?: 'N/A' }}</strong>
                    </div>
                    <div>
                        <span class="text-gray-400 text-xs block font-medium">Ngày tạo:</span>
                        <strong class="text-gray-800 block mt-0.5">{{ $opportunity->created_at->format('d/m/Y H:i') }}</strong>
                    </div>
                    @if($opportunity->completed_at)
                        <div>
                            <span class="text-gray-400 text-xs block font-medium">Thời gian hoàn thành:</span>
                            <strong class="text-green-700 block mt-0.5">{{ $opportunity->completed_at->format('d/m/Y H:i') }}</strong>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================
         JAVASCRIPT
         =================================================================== -->
    <script>
        // Toggle Cancel Reason in Sidebar
        function toggleSidebarCancelReason() {
            const select = document.getElementById('sidebar_status_select');
            const box = document.getElementById('sidebar_cancel_reason_box');
            const input = document.getElementById('sidebar_cancel_reason');

            if (select.value === 'cancelled') {
                box.classList.remove('hidden');
                input.setAttribute('required', 'required');
            } else {
                box.classList.add('hidden');
                input.removeAttribute('required');
            }
        }

        // Initialize status change handler and validation on page load
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarStatusSelect = document.getElementById('sidebar_status_select');
            if (sidebarStatusSelect) {
                sidebarStatusSelect.addEventListener('change', toggleSidebarCancelReason);
                toggleSidebarCancelReason();
            }
        });

        // Submit Status Update via AJAX
        function updateSidebarStatus() {
            const select = document.getElementById('sidebar_status_select');
            const reasonInput = document.getElementById('sidebar_cancel_reason');
            const status = select.value;
            const cancel_reason = reasonInput.value.trim();

            if (status === 'cancelled' && !cancel_reason) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Thiếu lý do hủy',
                        text: 'Vui lòng nhập lý do hủy hoạt động.',
                        confirmButtonColor: '#3B82F6'
                    });
                } else {
                    alert('Vui lòng nhập lý do hủy hoạt động.');
                }
                return;
            }

            fetch(`/opportunities/{{ $opportunity->id }}/update-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ status, cancel_reason })
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: result.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        alert(result.message);
                        window.location.reload();
                    }
                } else {
                    alert(result.message || 'Lỗi khi cập nhật trạng thái.');
                }
            })
            .catch(() => alert('Lỗi kết nối khi cập nhật trạng thái.'));
        }

        // Preview selected files before upload in report form
        const fileInput = document.getElementById('file_input');
        const previewDiv = document.getElementById('file_list_preview');
        const previewItems = document.getElementById('preview_items');

        if (fileInput) {
            fileInput.addEventListener('change', function() {
                const files = this.files;
                previewItems.innerHTML = '';
                
                if (files.length > 0) {
                    previewDiv.classList.remove('hidden');
                    Array.from(files).forEach(file => {
                        const size = (file.size / 1024).toFixed(1);
                        const item = document.createElement('div');
                        item.className = 'flex items-center justify-between text-xs p-2 bg-gray-50 border border-gray-200 rounded';
                        item.innerHTML = `
                            <span class="font-semibold text-gray-700 truncate max-w-xs"><i class="far fa-file mr-1 text-gray-400"></i>${file.name}</span>
                            <span class="text-gray-400">${size >= 1024 ? (size / 1024).toFixed(1) + ' MB' : size + ' KB'}</span>
                        `;
                        previewItems.appendChild(item);
                    });
                } else {
                    previewDiv.classList.add('hidden');
                }
                
                checkMeetingAttachmentRequired();
            });
        }

        // Validate meeting attachment requirement on submit
        const reportForm = document.getElementById('report_form');
        const activityType = @json($opportunity->activity_type);
        let initialAttachmentCount = @json($opportunity->attachments->count());

        function checkMeetingAttachmentRequired() {
            const statusSelect = document.getElementById('form_status_select');
            const files = fileInput ? fileInput.files : [];
            
            if (['meeting', 'project_meeting'].includes(activityType) && statusSelect && statusSelect.value === 'completed') {
                if (initialAttachmentCount === 0 && files.length === 0) {
                    return false; // missing
                }
            }
            return true;
        }

        if (reportForm) {
            reportForm.addEventListener('submit', function(e) {
                const isValid = checkMeetingAttachmentRequired();
                if (!isValid) {
                    e.preventDefault();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Thiếu hình ảnh/tài liệu đính kèm!',
                            text: 'Đối với hoạt động "Họp / Meeting", bạn bắt buộc phải đăng tải hình ảnh hoặc tài liệu chứng minh khi báo cáo hoàn thành hoạt động.',
                            confirmButtonColor: '#3B82F6',
                        });
                    } else {
                        alert('Đối với hoạt động "Họp / Meeting", bạn bắt buộc phải đăng tải hình ảnh hoặc tài liệu chứng minh khi báo cáo hoàn thành hoạt động.');
                    }
                }
            });
        }

        // Delete attachment via AJAX
        function deleteAttachment(id) {
            if (!confirm('Bạn có chắc chắn muốn xóa file đính kèm này?')) return;

            fetch(`/opportunity-attachments/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    const row = document.getElementById(`attachment_row_${id}`);
                    if (row) row.remove();
                    initialAttachmentCount--;

                    // Update count
                    const countEl = document.getElementById('attachment_count');
                    let currentCount = parseInt(countEl.textContent);
                    countEl.textContent = currentCount - 1;

                    if (currentCount - 1 === 0) {
                        const list = document.getElementById('attachment_list');
                        list.innerHTML = `
                            <div class="text-center py-8 text-gray-400 md:col-span-2" id="no_attachments_msg">
                                <i class="far fa-folder-open text-3xl mb-1 text-gray-300"></i>
                                <p class="text-xs">Chưa có tập tin nào.</p>
                            </div>
                        `;
                    }

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'success', title: result.message, timer: 1500, showConfirmButton: false });
                    }
                } else {
                    alert(result.message || 'Lỗi khi xóa file.');
                }
            })
            .catch(() => alert('Lỗi kết nối khi xóa file.'));
        }

        // Quick AJAX File Uploader from sidebar
        function uploadQuickAttachment() {
            const input = document.getElementById('quick_file_input');
            const status = document.getElementById('quick_upload_status');
            if (!input.files || input.files.length === 0) return;

            const file = input.files[0];
            const formData = new FormData();
            formData.append('file', file);

            status.classList.remove('hidden');
            status.textContent = 'Đang tải lên: ' + file.name + '...';

            fetch(`/opportunities/{{ $opportunity->id }}/attachments`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(res => res.json())
            .then(result => {
                status.classList.add('hidden');
                input.value = '';
                if (result.success) {
                    // Remove no attachments message if exists
                    const noMsg = document.getElementById('no_attachments_msg');
                    if (noMsg) noMsg.remove();

                    // Append to list
                    const list = document.getElementById('attachment_list');
                    const newRow = document.createElement('div');
                    newRow.className = 'flex items-start justify-between gap-3 p-3 border border-gray-100 rounded-xl bg-gray-50/50 hover:bg-gray-50 transition-colors text-sm';
                    newRow.id = `attachment_row_${result.attachment.id}`;
                    newRow.innerHTML = `
                        <div class="flex items-start gap-3 overflow-hidden">
                            <i class="${result.attachment.file_icon} text-2xl mt-0.5 flex-shrink-0 text-gray-500"></i>
                            <div class="overflow-hidden">
                                <a href="${result.attachment.download_url}" target="_blank"
                                    class="font-semibold text-blue-600 hover:underline block truncate" title="${result.attachment.file_name}">
                                    ${result.attachment.file_name}
                                </a>
                                <span class="text-gray-400 text-xs block mt-0.5">
                                    ${result.attachment.file_size_formatted} • Vừa xong
                                </span>
                            </div>
                        </div>
                        <button type="button" onclick="deleteAttachment(${result.attachment.id})"
                            class="text-gray-400 hover:text-red-500 p-1.5 transition-colors">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    `;
                    list.appendChild(newRow);
                    initialAttachmentCount++;

                    // Update count
                    const countEl = document.getElementById('attachment_count');
                    countEl.textContent = parseInt(countEl.textContent) + 1;

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({ icon: 'success', title: 'Tải lên thành công!', timer: 1500, showConfirmButton: false });
                    }
                } else {
                    alert(result.message || 'Lỗi khi tải lên file.');
                }
            })
            .catch(() => {
                status.classList.add('hidden');
                input.value = '';
                alert('Lỗi kết nối khi tải lên file.');
            });
        }
    </script>
@endsection