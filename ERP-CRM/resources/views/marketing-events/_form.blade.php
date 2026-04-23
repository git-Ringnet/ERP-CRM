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

    {{-- Chi phí thực tế (tùy chọn, có thể điền sau) --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Chi phí thực tế (VND)</label>
        <input type="hidden" name="actual_cost"
            value="{{ old('actual_cost', isset($marketingEvent) ? (string) ((int) round((float) ($marketingEvent->actual_cost ?? 0))) : '0') }}"
            data-money-raw>
        <input type="text"
            inputmode="numeric"
            autocomplete="off"
            name="actual_cost_display"
            value="{{ old('actual_cost', isset($marketingEvent) ? number_format((float) ($marketingEvent->actual_cost ?? 0), 0, '.', ',') : '0') }}"
            data-money-display="actual_cost"
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

@push('scripts')
<script>
  (function () {
    function stripToNumericString(value) {
      if (value === null || value === undefined) return '';
      // keep digits only; users may paste "1,000,000" or "1.000.000" or "1 000 000"
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
      // initial sync (in case old() contains formatted value)
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
