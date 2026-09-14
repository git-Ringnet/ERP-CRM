<div x-data="{
    scope: '{{ old('scope', $marketingEvent->scope ?? 'external') }}',
    partnerCooperation: '{{ old('partner_cooperation', $marketingEvent->partner_cooperation ?? 'no') }}',
    organizeType: '{{ old('organize_type', $marketingEvent->organize_type ?? 'workshop') }}',
    vendorId: '{{ old('vendor_id', $marketingEvent->vendor_id ?? '') }}'
}" class="space-y-6">

    {{-- PHẦN 1: THÔNG TIN CHUNG --}}
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-3 flex items-center gap-2">
            <i class="fas fa-info-circle text-violet-500"></i> Thông tin chung hoạt động MKT
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- Phạm vi hoạt động --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phạm vi hoạt động <span class="text-red-500">*</span></label>
                <div class="flex gap-6 mt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="radio" name="scope" value="internal" x-model="scope"
                            class="rounded-full border-gray-300 text-purple-600 focus:ring-purple-400 h-4.5 w-4.5">
                        <span class="text-sm text-gray-700 font-semibold">Internal (Nội bộ)</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="radio" name="scope" value="external" x-model="scope"
                            class="rounded-full border-gray-300 text-purple-600 focus:ring-purple-400 h-4.5 w-4.5">
                        <span class="text-sm text-gray-700 font-semibold">External (Đối ngoại / Hãng / Khách)</span>
                    </label>
                </div>
                @error('scope')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Loại hình tổ chức --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Loại hình tổ chức <span class="text-red-500">*</span></label>
                <select name="organize_type" x-model="organizeType" translate="no"
                    class="notranslate w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 bg-white">
                    <option value="workshop" translate="no" class="notranslate">Workshop</option>
                    <option value="networking_dinner" translate="no" class="notranslate">Networking Dinner</option>
                    <option value="exhibition" translate="no" class="notranslate">Exhibition</option>
                    <option value="other" translate="no" class="notranslate">Other: điền tay</option>
                </select>
                @error('organize_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Loại hình tổ chức khác --}}
            <div x-show="organizeType === 'other'" x-transition class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Other (Loại hình tổ chức khác) <span class="text-red-500">*</span></label>
                <input type="text" name="organize_type_other" value="{{ old('organize_type_other', $marketingEvent->organize_type_other ?? '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400"
                    placeholder="VD: Webinar, Giải giao lưu thể thao...">
                @error('organize_type_other')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Tên sự kiện --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tên sự kiện <span class="text-gray-400 text-xs">(Không bắt buộc, hệ thống tự động gán nếu bỏ trống)</span></label>
                <input type="text" name="title" value="{{ old('title', $marketingEvent->title ?? '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400"
                    placeholder="VD: Workshop Giới thiệu Giải pháp WiFi Fortinet Q3/2026">
                @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Quyền hiển thị cho Đội ngũ Sales --}}
            <div class="md:col-span-2 bg-gradient-to-r from-purple-50 to-indigo-50/50 p-4 rounded-xl border border-purple-100/80">
                <label class="block text-sm font-bold text-purple-900 mb-2">
                    <i class="fas fa-eye text-purple-600 mr-1.5"></i> Quyền hiển thị cho Đội ngũ Sales
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1">
                    <label class="flex items-start gap-2.5 p-3 rounded-lg border border-purple-200/60 bg-white hover:bg-purple-50/50 cursor-pointer transition-colors select-none">
                        <input type="radio" name="is_public_to_sales" value="0" 
                            {{ old('is_public_to_sales', isset($marketingEvent) ? ($marketingEvent->is_public_to_sales ? '1' : '0') : '0') == '0' ? 'checked' : '' }}
                            class="mt-0.5 text-purple-600 focus:ring-purple-400 h-4 w-4">
                        <div>
                            <span class="text-xs font-bold text-gray-800 block">Sự kiện Chỉ định / Riêng tư</span>
                            <p class="text-[11px] text-gray-500 mt-0.5 leading-snug">Chỉ Sales tạo sự kiện, Sales được gán task hoặc Sales phụ trách khách hàng được mời trong danh sách mới thấy.</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-2.5 p-3 rounded-lg border border-purple-200/60 bg-white hover:bg-purple-50/50 cursor-pointer transition-colors select-none">
                        <input type="radio" name="is_public_to_sales" value="1" 
                            {{ old('is_public_to_sales', isset($marketingEvent) ? ($marketingEvent->is_public_to_sales ? '1' : '0') : '0') == '1' ? 'checked' : '' }}
                            class="mt-0.5 text-purple-600 focus:ring-purple-400 h-4 w-4">
                        <div>
                            <span class="text-xs font-bold text-purple-900 block flex items-center gap-1">
                                <i class="fas fa-globe text-purple-600 text-[10px]"></i> Sự kiện Hãng lớn / Toàn công ty
                            </span>
                            <p class="text-[11px] text-gray-500 mt-0.5 leading-snug">Toàn bộ Sales đều nhìn thấy trên danh sách để nắm thông tin và chủ động đăng ký mời khách hàng tham dự.</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- PHẦN 2: HÃNG & ĐỐI TÁC --}}
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-3 flex items-center gap-2">
            <i class="fas fa-handshake text-indigo-500"></i> Vendor & Hãng phối hợp
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- Vendor --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Chọn Hãng/Vendor chính <span class="text-red-500">*</span></label>
                <select name="vendor_id" x-model="vendorId"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 bg-white">
                    <option value="">-- Chọn Hãng (Hoặc Hãng khác) --</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ old('vendor_id', $marketingEvent->vendor_id ?? '') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                    @endforeach
                </select>
                @error('vendor_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Vendor notes --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú Hãng khác / Phối hợp nhiều hãng</label>
                <input type="text" name="vendor_other_note" value="{{ old('vendor_other_note', $marketingEvent->vendor_other_note ?? '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400"
                    placeholder="VD: Phối hợp Fortinet, Cisco và HPE">
                @error('vendor_other_note')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Có phối hợp Partner không --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Có phối hợp với Partner không?</label>
                <div class="flex gap-6 mt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="radio" name="partner_cooperation" value="no" x-model="partnerCooperation"
                            class="rounded-full border-gray-300 text-purple-600 focus:ring-purple-400 h-4.5 w-4.5">
                        <span class="text-sm text-gray-700">Không</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="radio" name="partner_cooperation" value="yes" x-model="partnerCooperation"
                            class="rounded-full border-gray-300 text-purple-600 focus:ring-purple-400 h-4.5 w-4.5">
                        <span class="text-sm text-gray-700">Có (Nhập thông tin Partner & PIC)</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="radio" name="partner_cooperation" value="other" x-model="partnerCooperation"
                            class="rounded-full border-gray-300 text-purple-600 focus:ring-purple-400 h-4.5 w-4.5">
                        <span class="text-sm text-gray-700">Khác / Chưa chốt</span>
                    </label>
                </div>
                @error('partner_cooperation')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Chi tiết Partner --}}
            <div x-show="partnerCooperation === 'yes' || partnerCooperation === 'other'" x-transition class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Thông tin Partner và người phụ trách / Ghi chú tình trạng</label>
                <input type="text" name="partner_info" value="{{ old('partner_info', $marketingEvent->partner_info ?? '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400"
                    placeholder="Nhập tên Partner, PIC liên hệ hoặc ghi chú tình trạng đàm phán...">
                @error('partner_info')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- PHẦN 3: THỜI GIAN & ĐỊA ĐIỂM --}}
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-3 flex items-center gap-2">
            <i class="fas fa-calendar-alt text-amber-500"></i> Thời gian & Địa điểm tổ chức
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- Ngày tổ chức --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ngày tổ chức <span class="text-red-500">*</span></label>
                <input type="date" name="event_date" value="{{ old('event_date', isset($marketingEvent) ? $marketingEvent->event_date->format('Y-m-d') : '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 @error('event_date') border-red-400 @enderror">
                @error('event_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Địa điểm --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Địa điểm tổ chức <span class="text-red-500">*</span></label>
                <input type="text" name="location" value="{{ old('location', $marketingEvent->location ?? '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 @error('location') border-red-400 @enderror"
                    placeholder="VD: Khách sạn Rex, Quận 1, TP. HCM hoặc Online">
                @error('location')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Giờ bắt đầu --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Giờ bắt đầu</label>
                <input type="time" name="start_time" value="{{ old('start_time', isset($marketingEvent) && $marketingEvent->start_time ? date('H:i', strtotime($marketingEvent->start_time)) : '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400">
                @error('start_time')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Giờ kết thúc --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Giờ kết thúc</label>
                <input type="time" name="end_time" value="{{ old('end_time', isset($marketingEvent) && $marketingEvent->end_time ? date('H:i', strtotime($marketingEvent->end_time)) : '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400">
                @error('end_time')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- PHẦN 4: ĐỐI TƯỢNG & NGÂN SÁCH --}}
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-3 flex items-center gap-2">
            <i class="fas fa-coins text-emerald-500"></i> Đối tượng & Ngân sách dự toán
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- Số lượng đối tượng --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Số lượng khách dự kiến <span class="text-red-500">*</span></label>
                <input type="number" name="target_audience_count" value="{{ old('target_audience_count', $marketingEvent->target_audience_count ?? 0) }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400"
                    min="0" placeholder="VD: 30">
                @error('target_audience_count')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Note đối tượng --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả đối tượng mục tiêu / Ghi chú danh sách</label>
                <input type="text" name="target_audience_note" value="{{ old('target_audience_note', $marketingEvent->target_audience_note ?? '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400"
                    placeholder="VD: C-Level, Trưởng phòng IT... Kèm danh sách đính kèm">
                @error('target_audience_note')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Ngân sách dự toán --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ngân sách dự toán (VND) <span class="text-red-500">*</span></label>
                <input type="hidden" name="budget"
                    value="{{ old('budget', isset($marketingEvent) ? (string) ((int) round((float) $marketingEvent->budget)) : '0') }}"
                    data-money-raw>
                <input type="text"
                    inputmode="numeric"
                    autocomplete="off"
                    name="budget_display"
                    value="{{ old('budget', isset($marketingEvent) ? number_format((float) $marketingEvent->budget, 0, '.', ',') : '0') }}"
                    data-money-display="budget"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 @error('budget') border-red-400 @enderror">
                @error('budget')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Nguồn tiền tài trợ --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nguồn tiền tài trợ / Hãng hỗ trợ</label>
                <select name="funding_source"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 bg-white">
                    <option value="">-- Chọn Hãng tài trợ --</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->name }}" {{ old('funding_source', $marketingEvent->funding_source ?? '') == $supplier->name ? 'selected' : '' }}>{{ $supplier->name }}</option>
                    @endforeach
                    <option value="Ngân sách công ty" {{ old('funding_source', $marketingEvent->funding_source ?? '') == 'Ngân sách công ty' ? 'selected' : '' }}>Ngân sách công ty (Nội bộ)</option>
                    <option value="Khác" {{ old('funding_source', $marketingEvent->funding_source ?? '') == 'Khác' ? 'selected' : '' }}>Khác</option>
                </select>
                @error('funding_source')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Chi tiết yêu cầu ngân sách bên ngoài --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú các yêu cầu ngân sách bên ngoài</label>
                <textarea name="budget_external_note" rows="2"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400"
                    placeholder="Chi tiết các chi phí bên ngoài cần hỗ trợ hoặc note đối soát với hãng..."></textarea>
                @error('budget_external_note')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- PHẦN 5: TÀI LIỆU ĐÍNH KÈM & GHI CHÚ ĐẶC BIỆT --}}
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider border-b border-gray-100 pb-3 flex items-center gap-2">
            <i class="fas fa-file-upload text-blue-500"></i> Hồ sơ đính kèm & Ghi chú rủi ro
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- 1. Dự toán chi phí --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Bảng dự toán chi phí chi tiết</label>
                <input type="file" name="cost_estimation_file" class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100">
                @if(isset($marketingEvent->attachments['cost_estimation_file']))
                    <p class="text-[10px] text-emerald-600 mt-1"><i class="fas fa-paperclip"></i> Đã đính kèm: {{ $marketingEvent->attachments['cost_estimation_file']['name'] }}</p>
                @endif
            </div>

            {{-- 2. Kế hoạch tổ chức --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Kế hoạch tổ chức (Proposal)</label>
                <input type="file" name="event_plan_file" class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100">
                @if(isset($marketingEvent->attachments['event_plan_file']))
                    <p class="text-[10px] text-emerald-600 mt-1"><i class="fas fa-paperclip"></i> Đã đính kèm: {{ $marketingEvent->attachments['event_plan_file']['name'] }}</p>
                @endif
            </div>

            {{-- 3. Báo giá --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Báo giá của nhà cung cấp dịch vụ</label>
                <input type="file" name="quotation_file" class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100">
                @if(isset($marketingEvent->attachments['quotation_file']))
                    <p class="text-[10px] text-emerald-600 mt-1"><i class="fas fa-paperclip"></i> Đã đính kèm: {{ $marketingEvent->attachments['quotation_file']['name'] }}</p>
                @endif
            </div>

            {{-- 4. Agenda --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Agenda chương trình</label>
                <input type="file" name="agenda_file" class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100">
                @if(isset($marketingEvent->attachments['agenda_file']))
                    <p class="text-[10px] text-emerald-600 mt-1"><i class="fas fa-paperclip"></i> Đã đính kèm: {{ $marketingEvent->attachments['agenda_file']['name'] }}</p>
                @endif
            </div>

            {{-- 5. Danh sách khách mời dự kiến --}}
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Danh sách khách mời dự kiến</label>
                <input type="file" name="guest_list_file" class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100">
                @if(isset($marketingEvent->attachments['guest_list_file']))
                    <p class="text-[10px] text-emerald-600 mt-1"><i class="fas fa-paperclip"></i> Đã đính kèm: {{ $marketingEvent->attachments['guest_list_file']['name'] }}</p>
                @endif
            </div>

            {{-- Mô tả chương trình --}}
            <div class="md:col-span-2 border-t border-gray-100 pt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả / Mục tiêu chung</label>
                <textarea name="description" rows="3"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400"
                    placeholder="Mô tả mục tiêu, chi tiết chương trình...">{{ old('description', $marketingEvent->description ?? '') }}</textarea>
                @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Ghi chú đặc biệt / Ý kiến BOD --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú (Các điều kiện đặc biệt, rủi ro, xin ý kiến BOD...)</label>
                <textarea name="special_notes" rows="3"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400"
                    placeholder="Nhập các điều kiện đặc biệt, rủi ro tiềm ẩn, ý kiến cần BOD phản hồi thêm...">{{ old('special_notes', $marketingEvent->special_notes ?? '') }}</textarea>
                @error('special_notes')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
  (function () {
    function stripToNumericString(value) {
      if (value === null || value === undefined) return '';
      return value.toString().replace(/[^\d]/g, '');
    }

    function formatThousands(value) {
      const raw = stripToNumericString(value);
      if (!raw) return '';
      const n = Number(raw);
      if (!Number.isFinite(n)) return '';
      return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(Math.round(n));
    }

    function syncRaw(displayInput) {
      const field = displayInput.getAttribute('data-money-display');
      if (!field) return;
      const rawInput = document.querySelector('input[type="hidden"][name="' + field + '"][data-money-raw]');
      if (!rawInput) return;
      rawInput.value = stripToNumericString(displayInput.value) || '0';
    }

    function bindMoneyDisplay(displayInput) {
      displayInput.value = formatThousands(displayInput.value) || '0';
      syncRaw(displayInput);

      displayInput.addEventListener('focus', function () {
        displayInput.value = stripToNumericString(displayInput.value);
      });

      displayInput.addEventListener('blur', function () {
        displayInput.value = formatThousands(displayInput.value) || '0';
        syncRaw(displayInput);
      });

      displayInput.addEventListener('input', function () {
        const cursorPos = displayInput.selectionStart;
        const oldLength = displayInput.value.length;

        const rawNumber = stripToNumericString(displayInput.value);
        const formatted = formatThousands(rawNumber);
        displayInput.value = formatted;

        const newLength = formatted.length;
        const diff = newLength - oldLength;
        const newCursor = Math.max(0, (cursorPos || 0) + diff);
        displayInput.setSelectionRange(newCursor, newCursor);

        syncRaw(displayInput);
      });
    }

    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('[data-money-display]').forEach(bindMoneyDisplay);
    });
  })();
</script>
@endpush
