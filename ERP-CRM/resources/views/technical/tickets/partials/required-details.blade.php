{{--
    Dữ liệu bắt buộc theo loại ticket (TECH 10-12): After-Sales Support, Event / Speaker, Training / Update.
    Biến: $details (mảng ticket_details hiện có, [] khi tạo mới). Cần biến Alpine `workType` ở form cha.
--}}
@php
    $details = $details ?? [];
    $dv = fn ($key, $default = '') => old('ticket_details.' . $key, $details[$key] ?? $default);
    $inputClass = 'w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary';
    $req = '<span class="text-red-500">*</span>';
@endphp

<!-- f) Ticket After-sales support -->
<div x-show="workType === 'after_sales'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Người liên hệ (Họ tên, Email/SĐT) {!! $req !!}</label>
        <input type="text" name="ticket_details[after_sales_contact]" :required="workType === 'after_sales'"
            placeholder="Họ tên, SĐT hoặc Email người liên hệ..."
            class="{{ $inputClass }}" value="{{ $dv('after_sales_contact') }}">
        @error('ticket_details.after_sales_contact')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Serial Number thiết bị {!! $req !!}</label>
        <input type="text" name="ticket_details[after_sales_serial]" :required="workType === 'after_sales'"
            placeholder="S/N thiết bị lỗi (nhiều S/N cách nhau bằng dấu phẩy)..."
            class="{{ $inputClass }}" value="{{ $dv('after_sales_serial') }}">
        @error('ticket_details.after_sales_serial')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Mô tả vấn đề / Sự cố {!! $req !!}</label>
        <textarea name="ticket_details[after_sales_problem]" rows="4" :required="workType === 'after_sales'"
            placeholder="Mô tả chi tiết lỗi phát sinh, hiện tượng sự cố kỹ thuật..."
            class="{{ $inputClass }}">{{ $dv('after_sales_problem') }}</textarea>
        @error('ticket_details.after_sales_problem')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
</div>

<!-- g) Ticket Event -->
<div x-show="workType === 'event'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Tên sự kiện (Event Name) {!! $req !!}</label>
        <input type="text" name="ticket_details[event_name]" :required="workType === 'event'"
            placeholder="Nhập tên sự kiện, hội thảo..." class="{{ $inputClass }}" value="{{ $dv('event_name') }}">
        @error('ticket_details.event_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Thời gian tổ chức {!! $req !!}</label>
        <input type="datetime-local" name="ticket_details[event_time]" :required="workType === 'event'"
            class="{{ $inputClass }}" value="{{ $dv('event_time') }}">
        @error('ticket_details.event_time')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Địa điểm tổ chức {!! $req !!}</label>
        <input type="text" name="ticket_details[event_location]" :required="workType === 'event'"
            placeholder="Địa chỉ tổ chức sự kiện / link online..." class="{{ $inputClass }}" value="{{ $dv('event_location') }}">
        @error('ticket_details.event_location')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Đối tượng tham gia</label>
        <input type="text" name="ticket_details[event_attendees]"
            placeholder="Partner, Customer, End-User..." class="{{ $inputClass }}" value="{{ $dv('event_attendees') }}">
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Cử Speaker tham gia? {!! $req !!}</label>
        <select name="ticket_details[event_speaker]" :required="workType === 'event'" class="{{ $inputClass }}">
            <option value="">-- Chọn --</option>
            <option value="Yes" @selected($dv('event_speaker') === 'Yes')>Có (Yes)</option>
            <option value="No" @selected($dv('event_speaker') === 'No')>Không (No)</option>
        </select>
        @error('ticket_details.event_speaker')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Chuẩn bị Slide trình bày? {!! $req !!}</label>
        <select name="ticket_details[event_slide]" :required="workType === 'event'" class="{{ $inputClass }}">
            <option value="">-- Chọn --</option>
            <option value="Yes" @selected($dv('event_slide') === 'Yes')>Có (Yes)</option>
            <option value="No" @selected($dv('event_slide') === 'No')>Không (No)</option>
        </select>
        @error('ticket_details.event_slide')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Triển khai Demo trực tiếp?</label>
        <select name="ticket_details[event_demo]" class="{{ $inputClass }}">
            <option value="No" @selected($dv('event_demo', 'No') === 'No')>Không (No)</option>
            <option value="Yes" @selected($dv('event_demo') === 'Yes')>Có (Yes)</option>
        </select>
    </div>
    <div class="md:col-span-3">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Yêu cầu Speaker {!! $req !!}</label>
        <textarea name="ticket_details[event_speaker_request]" rows="2" :required="workType === 'event'"
            placeholder="Chủ đề trình bày, thời lượng, ngôn ngữ, số speaker... (ghi 'Không cần speaker' nếu không cử)"
            class="{{ $inputClass }}">{{ $dv('event_speaker_request') }}</textarea>
        @error('ticket_details.event_speaker_request')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div class="md:col-span-3">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Thông tin tài liệu / slide liên quan {!! $req !!}</label>
        <textarea name="ticket_details[event_material_info]" rows="2" :required="workType === 'event'"
            placeholder="Link tài liệu/slide mẫu, template của hãng, hạn nộp slide... (ghi 'Không có' nếu không có)"
            class="{{ $inputClass }}">{{ $dv('event_material_info') }}</textarea>
        @error('ticket_details.event_material_info')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div class="md:col-span-3">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Yêu cầu khác</label>
        <textarea name="ticket_details[event_notes]" rows="3"
            placeholder="Các yêu cầu chuẩn bị thiết bị, banner, quà tặng..."
            class="{{ $inputClass }}">{{ $dv('event_notes') }}</textarea>
    </div>
</div>

<!-- h) Ticket Training/Update -->
<div x-show="workType === 'training'" class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Đối tượng đào tạo {!! $req !!}</label>
        <select name="ticket_details[training_audience]" :required="workType === 'training'" class="{{ $inputClass }}">
            <option value="">-- Chọn --</option>
            <option value="Internal" @selected($dv('training_audience') === 'Internal')>Nội bộ (Internal)</option>
            <option value="Partner" @selected($dv('training_audience') === 'Partner')>Đối tác (Partner)</option>
            <option value="Customer" @selected($dv('training_audience') === 'Customer')>Khách hàng (Customer)</option>
        </select>
        @error('ticket_details.training_audience')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Hình thức {!! $req !!}</label>
        <select name="ticket_details[training_format]" :required="workType === 'training'" class="{{ $inputClass }}">
            <option value="">-- Chọn --</option>
            <option value="Online" @selected($dv('training_format') === 'Online')>Online</option>
            <option value="Offline" @selected($dv('training_format') === 'Offline')>Offline</option>
        </select>
        @error('ticket_details.training_format')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Thời gian đào tạo {!! $req !!}</label>
        <input type="datetime-local" name="ticket_details[training_time]" :required="workType === 'training'"
            class="{{ $inputClass }}" value="{{ $dv('training_time') }}">
        @error('ticket_details.training_time')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div class="md:col-span-3">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Địa điểm (Offline) / Link họp (Online) {!! $req !!}</label>
        <input type="text" name="ticket_details[training_location]" :required="workType === 'training'"
            placeholder="Địa chỉ phòng Lab, văn phòng hoặc link Teams/Zoom/Meet..."
            class="{{ $inputClass }}" value="{{ $dv('training_location') }}">
        @error('ticket_details.training_location')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div class="md:col-span-3">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Nội dung / Mục tiêu đào tạo {!! $req !!}</label>
        <textarea name="ticket_details[training_goal]" rows="3" :required="workType === 'training'"
            placeholder="Các bài Lab, nội dung sản phẩm cần đào tạo, mục tiêu sau đào tạo..."
            class="{{ $inputClass }}">{{ $dv('training_goal') }}</textarea>
        @error('ticket_details.training_goal')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
</div>
