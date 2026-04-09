<div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    {{-- Tiêu đề --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Tên sự kiện <span class="text-red-500">*</span></label>
        <input type="text" name="title" value="{{ old('title', $marketingEvent->title ?? '') }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 @error('title') border-red-400 @enderror"
            placeholder="VD: Hội thảo Công nghệ Fortinet Q2/2026">
        @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Ngày sự kiện --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ngày tổ chức <span class="text-red-500">*</span></label>
        <input type="date" name="event_date" value="{{ old('event_date', isset($marketingEvent) ? $marketingEvent->event_date->format('Y-m-d') : '') }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 @error('event_date') border-red-400 @enderror">
        @error('event_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Địa điểm --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Địa điểm</label>
        <input type="text" name="location" value="{{ old('location', $marketingEvent->location ?? '') }}"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400"
            placeholder="VD: Khách sạn Rex, Hồ Chí Minh">
        @error('location')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Ngân sách dự toán --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ngân sách dự toán (VND) <span class="text-red-500">*</span></label>
        <input type="number" name="budget" value="{{ old('budget', $marketingEvent->budget ?? 0) }}" min="0" step="1000000"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400 @error('budget') border-red-400 @enderror">
        @error('budget')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Chi phí thực tế (tùy chọn, có thể điền sau) --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Chi phí thực tế (VND)</label>
        <input type="number" name="actual_cost" value="{{ old('actual_cost', $marketingEvent->actual_cost ?? 0) }}" min="0" step="1000000"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400">
        @error('actual_cost')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- Mô tả --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả / Mục tiêu sự kiện</label>
        <textarea name="description" rows="4"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400"
            placeholder="Mô tả mục tiêu, nội dung chương trình, đối tượng khách mời...">{{ old('description', $marketingEvent->description ?? '') }}</textarea>
        @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
</div>
