@php
    $currentVendorIds = old('vendor_ids', isset($marketingEvent) ? ($marketingEvent->suppliers->pluck('id')->all() ?: ($marketingEvent->vendor_id ? [$marketingEvent->vendor_id] : [])) : []);
    $defaultFundingSources = [
        ['source_type' => 'brand', 'supplier_id' => '', 'name' => '', 'planned_amount' => (int)($marketingEvent->budget ?? 0), 'note' => '']
    ];
    if (isset($marketingEvent) && !empty($marketingEvent->funding_sources) && is_array($marketingEvent->funding_sources)) {
        $defaultFundingSources = $marketingEvent->funding_sources;
    }
    $initialFundingSources = old('funding_sources', $defaultFundingSources);
    if (is_string($initialFundingSources)) {
        $initialFundingSources = json_decode($initialFundingSources, true) ?: $defaultFundingSources;
    }
@endphp

<div x-data="{
    scope: '{{ old('scope', $marketingEvent->scope ?? 'external') }}',
    partnerCooperation: '{{ old('partner_cooperation', $marketingEvent->partner_cooperation ?? 'no') }}',
    organizeType: '{{ old('organize_type', $marketingEvent->organize_type ?? 'workshop') }}',
    vendorId: '{{ old('vendor_id', $marketingEvent->vendor_id ?? '') }}',
    selectedVendors: {{ json_encode($currentVendorIds) }},
    fundingSources: {{ json_encode($initialFundingSources) }},
    
    toggleVendor(id) {
        id = parseInt(id);
        const index = this.selectedVendors.indexOf(id);
        if (index === -1) {
            this.selectedVendors.push(id);
        } else {
            this.selectedVendors.splice(index, 1);
        }
        if (this.selectedVendors.length > 0) {
            this.vendorId = this.selectedVendors[0];
        } else {
            this.vendorId = '';
        }
    },
    isVendorSelected(id) {
        return this.selectedVendors.includes(parseInt(id));
    },
    addFundingSource(type = 'brand', defaultName = '') {
        this.fundingSources.push({
            source_type: type,
            supplier_id: '',
            name: defaultName,
            planned_amount: 0,
            note: ''
        });
        this.recalculateBudget();
    },
    removeFundingSource(idx) {
        if (this.fundingSources.length > 1) {
            this.fundingSources.splice(idx, 1);
            this.recalculateBudget();
        }
    },
    formatMoney(val) {
        const num = parseFloat((val + '').replace(/[^\d.]/g, '')) || 0;
        return new Intl.NumberFormat('en-US').format(Math.round(num));
    },
    recalculateBudget() {
        let total = 0;
        this.fundingSources.forEach(s => {
            const val = parseFloat((s.planned_amount + '').replace(/[^\d.]/g, '')) || 0;
            total += val;
        });
        const rawBudgetInput = document.querySelector('input[type=hidden][name=budget]');
        const displayBudgetInput = document.querySelector('input[data-money-display=budget]');
        if (rawBudgetInput) rawBudgetInput.value = total;
        if (displayBudgetInput) displayBudgetInput.value = this.formatMoney(total);
    }
}" class="space-y-6">

    {{-- Hidden input for serialized funding sources --}}
    <input type="hidden" name="funding_sources" :value="JSON.stringify(fundingSources)">

    {{-- PHẦN 1: PHẠM VI & THÔNG TIN CHUNG --}}
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-5">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                <i class="fas fa-bullhorn text-violet-500"></i> Thông tin chung hoạt động Marketing
            </h3>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full"
                  :class="scope === 'internal' ? 'bg-amber-100 text-amber-800' : 'bg-purple-100 text-purple-800'">
                <span x-text="scope === 'internal' ? 'Sự kiện Nội bộ' : 'Sự kiện Đối ngoại / Khách hàng'"></span>
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- Phạm vi hoạt động --}}
            <div class="md:col-span-2 bg-gradient-to-r from-purple-50/70 to-indigo-50/50 p-4 rounded-xl border border-purple-100">
                <label class="block text-sm font-bold text-gray-800 mb-2">Phạm vi tổ chức hoạt động <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="flex items-start gap-3 p-3.5 rounded-xl border-2 cursor-pointer transition-all bg-white select-none"
                           :class="scope === 'internal' ? 'border-amber-500 shadow-sm ring-2 ring-amber-100' : 'border-gray-200 hover:border-gray-300'">
                        <input type="radio" name="scope" value="internal" x-model="scope" class="mt-1 text-amber-600 focus:ring-amber-400">
                        <div>
                            <span class="text-sm font-bold text-amber-900 block flex items-center gap-1.5">
                                <i class="fas fa-users text-amber-500"></i> Sự kiện Nội bộ (Internal)
                            </span>
                            <p class="text-xs text-gray-500 mt-1">Dành cho cán bộ nhân viên công ty (Teambuilding, Year End Party, Sinh nhật công ty, Đào tạo nội bộ...). Form tinh giản không bắt buộc hãng & khách ngoài.</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-3.5 rounded-xl border-2 cursor-pointer transition-all bg-white select-none"
                           :class="scope === 'external' ? 'border-purple-600 shadow-sm ring-2 ring-purple-100' : 'border-gray-200 hover:border-gray-300'">
                        <input type="radio" name="scope" value="external" x-model="scope" class="mt-1 text-purple-600 focus:ring-purple-400">
                        <div>
                            <span class="text-sm font-bold text-purple-900 block flex items-center gap-1.5">
                                <i class="fas fa-handshake text-purple-600"></i> Sự kiện Đối ngoại (External)
                            </span>
                            <p class="text-xs text-gray-500 mt-1">Phối hợp Hãng / Vendor, Khách hàng, Đối tác (Workshop, Hội thảo giải pháp, Tiệc tri ân, Triển lãm...).</p>
                        </div>
                    </label>
                </div>
                @error('scope')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Tên sự kiện --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tên chương trình / sự kiện <span class="text-gray-400 text-xs">(Không bắt buộc, hệ thống tự động gán nếu bỏ trống)</span>
                </label>
                <input type="text" name="title" value="{{ old('title', $marketingEvent->title ?? '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm font-medium"
                    :placeholder="scope === 'internal' ? 'VD: Teambuilding Hè 2026 - Gắn kết vươn xa / Year End Party' : 'VD: Workshop Giới thiệu Giải pháp Fortinet Secure SD-WAN Q3/2026'">
                @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- ĐẶC THÙ CHO SỰ KIỆN NỘI BỘ --}}
            <template x-if="scope === 'internal'">
                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 bg-amber-50/60 p-4 rounded-xl border border-amber-200/80">
                    <div>
                        <label class="block text-xs font-bold text-amber-900 uppercase tracking-wider mb-1">
                            <i class="fas fa-sitemap mr-1"></i> Khối / Phòng ban tham gia
                        </label>
                        <input type="text" name="internal_department" value="{{ old('internal_department', $marketingEvent->internal_department ?? '') }}"
                            class="w-full border border-amber-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400 text-sm bg-white"
                            placeholder="VD: Toàn thể CBNV công ty / Khối Kỹ thuật / Khối Kinh doanh...">
                        @error('internal_department')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-amber-900 uppercase tracking-wider mb-1">
                            <i class="fas fa-bullseye mr-1"></i> Mục đích sự kiện nội bộ
                        </label>
                        <select name="internal_purpose" class="w-full border border-amber-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400 text-sm bg-white">
                            @php $pur = old('internal_purpose', $marketingEvent->internal_purpose ?? ''); @endphp
                            <option value="Gắn kết nhân sự / Teambuilding" {{ $pur == 'Gắn kết nhân sự / Teambuilding' ? 'selected' : '' }}>Gắn kết nhân sự / Teambuilding</option>
                            <option value="Đào tạo & Nâng cao kỹ năng" {{ $pur == 'Đào tạo & Nâng cao kỹ năng' ? 'selected' : '' }}>Đào tạo & Nâng cao kỹ năng</option>
                            <option value="Tổng kết & Khen thưởng quý / năm" {{ $pur == 'Tổng kết & Khen thưởng quý / năm' ? 'selected' : '' }}>Tổng kết & Khen thưởng quý / năm</option>
                            <option value="Sinh nhật công ty / Kỷ niệm thành lập" {{ $pur == 'Sinh nhật công ty / Kỷ niệm thành lập' ? 'selected' : '' }}>Sinh nhật công ty / Kỷ niệm thành lập</option>
                            <option value="Sinh hoạt phong trào Công đoàn" {{ $pur == 'Sinh hoạt phong trào Công đoàn' ? 'selected' : '' }}>Sinh hoạt phong trào Công đoàn</option>
                            <option value="Khác" {{ $pur == 'Khác' ? 'selected' : '' }}>Mục đích khác</option>
                        </select>
                        @error('internal_purpose')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </template>

            {{-- Loại hình tổ chức --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Loại hình tổ chức <span class="text-red-500">*</span></label>
                <select name="organize_type" x-model="organizeType"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 bg-white text-sm">
                    <option value="workshop">Workshop / Hội thảo chuyên đề</option>
                    <option value="networking_dinner">Networking Dinner / Tiệc giao lưu</option>
                    <option value="exhibition">Exhibition / Triển lãm - Gian hàng</option>
                    <option value="other">Loại hình khác (điền tay)</option>
                </select>
                @error('organize_type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Loại hình khác --}}
            <div x-show="organizeType === 'other'" x-transition>
                <label class="block text-sm font-medium text-gray-700 mb-1">Chi tiết loại hình tổ chức khác <span class="text-red-500">*</span></label>
                <input type="text" name="organize_type_other" value="{{ old('organize_type_other', $marketingEvent->organize_type_other ?? '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm"
                    placeholder="VD: Webinar trực tuyến, Giải thể thao giao hữu...">
                @error('organize_type_other')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Quyền hiển thị cho Sales (chỉ áp dụng đối ngoại) --}}
            <div x-show="scope === 'external'" x-transition class="md:col-span-2 bg-slate-50 p-4 rounded-xl border border-slate-200">
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                    <i class="fas fa-eye text-indigo-500 mr-1.5"></i> Quyền hiển thị cho Đội ngũ Sales
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-start gap-2.5 p-3 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer transition-colors select-none">
                        <input type="radio" name="is_public_to_sales" value="0" 
                            {{ old('is_public_to_sales', isset($marketingEvent) ? ($marketingEvent->is_public_to_sales ? '1' : '0') : '0') == '0' ? 'checked' : '' }}
                            class="mt-0.5 text-purple-600 focus:ring-purple-400 h-4 w-4">
                        <div>
                            <span class="text-xs font-bold text-gray-800 block">Sự kiện Chỉ định / Riêng tư</span>
                            <p class="text-[11px] text-gray-500 mt-0.5">Chỉ Sales được gán task hoặc phụ trách khách hàng được mời mới thấy.</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-2.5 p-3 rounded-lg border border-slate-200 bg-white hover:bg-purple-50/50 cursor-pointer transition-colors select-none">
                        <input type="radio" name="is_public_to_sales" value="1" 
                            {{ old('is_public_to_sales', isset($marketingEvent) ? ($marketingEvent->is_public_to_sales ? '1' : '0') : '0') == '1' ? 'checked' : '' }}
                            class="mt-0.5 text-purple-600 focus:ring-purple-400 h-4 w-4">
                        <div>
                            <span class="text-xs font-bold text-purple-900 block flex items-center gap-1">
                                <i class="fas fa-globe text-purple-600 text-[10px]"></i> Sự kiện Hãng lớn / Toàn công ty
                            </span>
                            <p class="text-[11px] text-gray-500 mt-0.5">Toàn bộ Sales đều nhìn thấy để nắm thông tin và đăng ký mời khách.</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- PHẦN 2: CHỌN NHIỀU HÃNG (VENDORS) & ĐỐI TÁC --}}
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <div>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fas fa-handshake text-indigo-500"></i> Hãng / Vendor phối hợp (Cho phép chọn nhiều hãng)
                </h3>
                <p class="text-xs text-gray-400 mt-0.5" x-text="scope === 'internal' ? 'Sự kiện nội bộ: Chọn hãng nếu có tài trợ chi phí hoặc quà tặng' : 'Tích chọn các hãng đồng tổ chức hoặc tài trợ sự kiện'"></p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700">
                Đã chọn: <span x-text="selectedVendors.length"></span> hãng
            </span>
        </div>

        {{-- Hidden input for primary vendor --}}
        <input type="hidden" name="vendor_id" :value="vendorId">

        {{-- Grid chọn nhiều hãng --}}
        <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-2">
                Danh sách Hãng / Nhà cung cấp <span x-show="scope === 'external'" class="text-red-500">*</span>
            </label>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2 max-h-56 overflow-y-auto p-2 rounded-xl border border-gray-200 bg-gray-50/50">
                @foreach($suppliers as $supplier)
                    <label class="flex items-center gap-2 p-2 rounded-lg border text-xs font-medium cursor-pointer transition-all select-none bg-white hover:bg-purple-50/40"
                           :class="isVendorSelected({{ $supplier->id }}) ? 'border-purple-500 bg-purple-50/80 text-purple-900 font-bold shadow-xs' : 'border-gray-200 text-gray-700'">
                        <input type="checkbox" name="vendor_ids[]" value="{{ $supplier->id }}"
                               :checked="isVendorSelected({{ $supplier->id }})"
                               @change="toggleVendor({{ $supplier->id }})"
                               class="rounded border-gray-300 text-purple-600 focus:ring-purple-400 h-4 w-4">
                        <span class="truncate" title="{{ $supplier->name }}">{{ $supplier->name }}</span>
                    </label>
                @endforeach
            </div>
            @error('vendor_ids')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            @error('vendor_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Ghi chú hãng khác --}}
        <div>
            <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Ghi chú Hãng khác / Nhà tài trợ bổ sung</label>
            <input type="text" name="vendor_other_note" value="{{ old('vendor_other_note', $marketingEvent->vendor_other_note ?? '') }}"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm"
                placeholder="VD: Phối hợp Fortinet, Cisco, HPE và NTT...">
            @error('vendor_other_note')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        {{-- Phối hợp Partner (chỉ hiển thị khi external) --}}
        <div x-show="scope === 'external'" x-transition class="pt-2 border-t border-gray-100">
            <label class="block text-sm font-medium text-gray-700 mb-2">Có phối hợp với Partner / Đại lý không?</label>
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
                    <span class="text-sm text-gray-700">Khác / Đang đàm phán</span>
                </label>
            </div>

            <div x-show="partnerCooperation === 'yes' || partnerCooperation === 'other'" x-transition class="mt-3">
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Thông tin Partner và người phụ trách</label>
                <input type="text" name="partner_info" value="{{ old('partner_info', $marketingEvent->partner_info ?? '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm"
                    placeholder="Nhập tên Partner, PIC liên hệ, số điện thoại hoặc ghi chú...">
            </div>
        </div>
    </div>

    {{-- PHẦN 3: THỜI GIAN & ĐỊA ĐIỂM --}}
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider border-b border-gray-100 pb-3 flex items-center gap-2">
            <i class="fas fa-calendar-alt text-amber-500"></i> Thời gian & Địa điểm tổ chức
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- Ngày tổ chức --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ngày tổ chức <span class="text-red-500">*</span></label>
                <input type="date" name="event_date" value="{{ old('event_date', isset($marketingEvent) && $marketingEvent->event_date ? $marketingEvent->event_date->format('Y-m-d') : '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm @error('event_date') border-red-400 @enderror">
                @error('event_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Địa điểm --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Địa điểm tổ chức <span class="text-red-500">*</span></label>
                <input type="text" name="location" value="{{ old('location', $marketingEvent->location ?? '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm @error('location') border-red-400 @enderror"
                    :placeholder="scope === 'internal' ? 'VD: Văn phòng Ringnet / Resort Asean Ba Vì' : 'VD: Khách sạn Rex, Quận 1, TP. HCM hoặc Online'">
                @error('location')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Giờ bắt đầu --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Giờ bắt đầu</label>
                <input type="time" name="start_time" value="{{ old('start_time', isset($marketingEvent) && $marketingEvent->start_time ? date('H:i', strtotime($marketingEvent->start_time)) : '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm">
                @error('start_time')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Giờ kết thúc --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Giờ kết thúc</label>
                <input type="time" name="end_time" value="{{ old('end_time', isset($marketingEvent) && $marketingEvent->end_time ? date('H:i', strtotime($marketingEvent->end_time)) : '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm">
                @error('end_time')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- PHẦN 4: NGUỒN TIỀN & PHÂN BỔ DỰ TOÁN CHI TIẾT --}}
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-3 gap-2">
            <div>
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fas fa-coins text-emerald-500"></i> Nguồn tiền & Bảng phân bổ Ngân sách dự toán
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">Hỗ trợ lựa chọn nhiều hãng tài trợ, Quỹ Công đoàn và Ngân sách công ty</p>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" @click="addFundingSource('brand', '')"
                        class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors">
                    <i class="fas fa-plus mr-1"></i> + Hãng tài trợ
                </button>
                <button type="button" @click="addFundingSource('union', 'Quỹ Công đoàn')"
                        class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 transition-colors">
                    <i class="fas fa-plus mr-1"></i> + Quỹ Công đoàn
                </button>
                <button type="button" @click="addFundingSource('company', 'Ngân sách Công ty')"
                        class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors">
                    <i class="fas fa-plus mr-1"></i> + Ngân sách CT
                </button>
                <button type="button" @click="addFundingSource('other', 'Khác')"
                        class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition-colors">
                    <i class="fas fa-plus mr-1"></i> + Khác
                </button>
            </div>
        </div>

        {{-- Bảng chi tiết nguồn tiền tài trợ --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="p-3 w-36">Loại nguồn tiền</th>
                        <th class="p-3 min-w-[200px]">Tên nguồn tài trợ / Hãng chi</th>
                        <th class="p-3 w-48 text-right">Số tiền cam kết (VND)</th>
                        <th class="p-3">Ghi chú / Điều kiện</th>
                        <th class="p-3 w-12 text-center">Xóa</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <template x-for="(source, index) in fundingSources" :key="index">
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-2.5">
                                <select x-model="source.source_type" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs bg-white">
                                    <option value="brand">Hãng tài trợ</option>
                                    <option value="union">Quỹ Công đoàn</option>
                                    <option value="company">Ngân sách Công ty</option>
                                    <option value="other">Khác</option>
                                </select>
                            </td>
                            <td class="p-2.5">
                                <template x-if="source.source_type === 'brand'">
                                    <div class="space-y-1">
                                        <input type="text" x-model="source.name"
                                            class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-purple-400"
                                            placeholder="Tên Hãng (VD: Fortinet, Cisco, HPE...)">
                                    </div>
                                </template>
                                <template x-if="source.source_type !== 'brand'">
                                    <input type="text" x-model="source.name"
                                        class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-purple-400"
                                        placeholder="Nhập tên nguồn tiền...">
                                </template>
                            </td>
                            <td class="p-2.5 text-right">
                                <input type="text"
                                    :value="formatMoney(source.planned_amount)"
                                    @input="source.planned_amount = $event.target.value.replace(/[^\d]/g, ''); recalculateBudget()"
                                    class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs text-right font-bold text-gray-800 focus:ring-1 focus:ring-purple-400"
                                    placeholder="0">
                            </td>
                            <td class="p-2.5">
                                <input type="text" x-model="source.note"
                                    class="w-full border border-gray-300 rounded-lg px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-purple-400"
                                    placeholder="Ghi chú phân bổ...">
                            </td>
                            <td class="p-2.5 text-center">
                                <button type="button" @click="removeFundingSource(index)"
                                        :disabled="fundingSources.length <= 1"
                                        class="text-gray-400 hover:text-red-600 disabled:opacity-30 transition-colors p-1">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Tổng cộng ngân sách & Số lượng đối tượng --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-3 border-t border-gray-100">
            {{-- Số lượng đối tượng tham gia --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <span x-text="scope === 'internal' ? 'Số lượng nhân sự nội bộ tham gia' : 'Số lượng khách dự kiến'"></span> <span class="text-red-500">*</span>
                </label>
                <input type="number" name="target_audience_count" value="{{ old('target_audience_count', $marketingEvent->target_audience_count ?? 0) }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm"
                    min="0" :placeholder="scope === 'internal' ? 'VD: 50 CBNV' : 'VD: 30 Khách'">
                @error('target_audience_count')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Ghi chú đối tượng --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả đối tượng mục tiêu / Ghi chú danh sách</label>
                <input type="text" name="target_audience_note" value="{{ old('target_audience_note', $marketingEvent->target_audience_note ?? '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm"
                    :placeholder="scope === 'internal' ? 'Kèm danh sách phòng ban đăng ký' : 'VD: C-Level, Trưởng phòng IT... Kèm danh sách đính kèm'">
                @error('target_audience_note')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Tổng Ngân sách dự toán --}}
            <div class="bg-gradient-to-r from-emerald-50 to-teal-50 p-4 rounded-xl border border-emerald-200">
                <label class="block text-xs font-bold text-emerald-900 uppercase tracking-wider mb-1">
                    <i class="fas fa-wallet mr-1"></i> Tổng Ngân sách dự toán (VND) <span class="text-red-500">*</span>
                </label>
                <input type="hidden" name="budget"
                    value="{{ old('budget', isset($marketingEvent) ? (string) ((int) round((float) $marketingEvent->budget)) : '0') }}"
                    data-money-raw>
                <input type="text"
                    inputmode="numeric"
                    autocomplete="off"
                    name="budget_display"
                    value="{{ old('budget', isset($marketingEvent) ? number_format((float) $marketingEvent->budget, 0, '.', ',') : '0') }}"
                    data-money-display="budget"
                    class="w-full border border-emerald-300 bg-white rounded-lg px-3 py-2 text-base font-black text-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-400 @error('budget') border-red-400 @enderror">
                <p class="text-[11px] text-emerald-700 mt-1">Tự động đồng bộ từ tổng số tiền cam kết của các nguồn tài trợ ở trên.</p>
                @error('budget')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Nguồn tiền tóm tắt --}}
            <div>
                <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Tóm tắt nguồn tiền / Hãng tài trợ</label>
                <input type="text" name="funding_source" value="{{ old('funding_source', $marketingEvent->funding_source ?? '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm"
                    placeholder="VD: Fortinet, Cisco, Quỹ Công đoàn...">
                @error('funding_source')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Chi tiết yêu cầu ngân sách bên ngoài --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú các yêu cầu ngân sách bên ngoài / Đối soát</label>
                <textarea name="budget_external_note" rows="2"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm"
                    placeholder="Chi tiết các chi phí bên ngoài cần hỗ trợ hoặc note đối soát với từng hãng..."></textarea>
                @error('budget_external_note')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- PHẦN 5: TÀI LIỆU ĐÍNH KÈM & GHI CHÚ ĐẶC BIỆT --}}
    <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm space-y-4">
        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider border-b border-gray-100 pb-3 flex items-center gap-2">
            <i class="fas fa-file-upload text-blue-500"></i> Hồ sơ đính kèm & Ghi chú rủi ro
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            {{-- 1. Dự toán chi phí --}}
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Bảng dự toán chi phí chi tiết (Excel/PDF)</label>
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
                <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">
                    <span x-text="scope === 'internal' ? 'Danh sách CBNV tham dự dự kiến' : 'Danh sách khách mời dự kiến'"></span>
                </label>
                <input type="file" name="guest_list_file" class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100">
                @if(isset($marketingEvent->attachments['guest_list_file']))
                    <p class="text-[10px] text-emerald-600 mt-1"><i class="fas fa-paperclip"></i> Đã đính kèm: {{ $marketingEvent->attachments['guest_list_file']['name'] }}</p>
                @endif
            </div>

            {{-- Mô tả chương trình --}}
            <div class="md:col-span-2 border-t border-gray-100 pt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả / Mục tiêu chung của hoạt động</label>
                <textarea name="description" rows="3"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm"
                    placeholder="Mô tả mục tiêu, chi tiết chương trình, các điểm nổi bật...">{{ old('description', $marketingEvent->description ?? '') }}</textarea>
                @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Ghi chú đặc biệt / Ý kiến BOD --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Ghi chú (Các điều kiện đặc biệt, rủi ro, xin ý kiến BOD...)</label>
                <textarea name="special_notes" rows="3"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 text-sm"
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
