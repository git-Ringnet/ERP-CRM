@php
    $isLocked = $isLocked ?? false;
    $saleProject = isset($sale) ? $sale->project : null;
    $projectTradeUp = $saleProject?->deal_type === 'trade_up';
    $matrix = old('trade_up_matrix', isset($sale) ? $sale->trade_up_matrix : ($projectTradeUp ? null : 'none'));
@endphp

<div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4" id="dealClassification" data-project-trade-up="{{ $projectTradeUp ? '1' : '0' }}">
    <div class="flex items-start gap-3">
        <i class="fas fa-tags mt-1 text-indigo-600"></i>
        <div class="flex-1">
            <h4 class="font-semibold text-indigo-950">Phân loại deal <span class="text-red-500">*</span></h4>
            <p class="mt-1 text-xs text-indigo-800">Chọn License VNET nếu áp dụng. Chọn một trạng thái Trade up hoặc “Không thuộc trường hợp này”.</p>

            <label class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-gray-800">
                <input type="checkbox" name="is_license_vnet" value="1" {{ old('is_license_vnet', $sale->is_license_vnet ?? false) ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                License VNET
            </label>
            @if($isLocked)<input type="hidden" name="is_license_vnet" value="{{ $sale->is_license_vnet ? 1 : 0 }}">@endif

            <div class="mt-3 grid gap-2 sm:grid-cols-3" id="tradeUpMatrixChoices">
                @foreach(['none' => 'Không thuộc trường hợp này', 'correct' => 'Trade up đúng matrix', 'incorrect' => 'Trade up không đúng matrix'] as $value => $label)
                    <label class="flex items-center gap-2 rounded border border-indigo-200 bg-white px-3 py-2 text-sm text-gray-800">
                        <input type="radio" name="trade_up_matrix" value="{{ $value }}" {{ $matrix === $value ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }} class="trade-up-matrix text-indigo-600 focus:ring-indigo-500">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @if($isLocked)<input type="hidden" name="trade_up_matrix" value="{{ $sale->trade_up_matrix }}">@endif

            <div id="projectTradeUpNotice" class="{{ $projectTradeUp ? '' : 'hidden' }} mt-3 rounded bg-amber-50 p-2 text-xs text-amber-800">
                ĐKDA này là Trade up: không thể bỏ đánh dấu Trade up; vui lòng chọn đúng hoặc không đúng matrix.
            </div>
            <label id="ohfCostAddedWrapper" class="{{ $matrix === 'incorrect' ? '' : 'hidden' }} mt-3 flex items-center gap-2 text-sm font-medium text-amber-900">
                <input type="checkbox" name="ohf_cost_added" value="1" {{ old('ohf_cost_added', $sale->ohf_cost_added ?? false) ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }} class="rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                Đã bổ sung chi phí OHF vào PNL
            </label>
            @if($isLocked)<input type="hidden" name="ohf_cost_added" value="{{ $sale->ohf_cost_added ? 1 : 0 }}">@endif
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
    function syncDealClassificationFromProject() {
        const project = document.getElementById('projectSelect');
        const selected = project && project.options[project.selectedIndex];
        const currentProjectTradeUp = document.getElementById('dealClassification')?.dataset.projectTradeUp === '1';
        const isTradeUp = selected ? selected.dataset.dealType === 'trade_up' : currentProjectTradeUp;
        const none = document.querySelector('.trade-up-matrix[value="none"]');
        const notice = document.getElementById('projectTradeUpNotice');
        if (none) {
            none.disabled = !!isTradeUp;
            if (isTradeUp && none.checked) document.querySelector('.trade-up-matrix[value="correct"]').checked = true;
        }
        if (notice) notice.classList.toggle('hidden', !isTradeUp);
        syncOhfCostVisibility();
    }
    function syncOhfCostVisibility() {
        const incorrect = document.querySelector('.trade-up-matrix[value="incorrect"]');
        const wrapper = document.getElementById('ohfCostAddedWrapper');
        if (wrapper) wrapper.classList.toggle('hidden', !incorrect || !incorrect.checked);
    }
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('projectSelect')?.addEventListener('change', syncDealClassificationFromProject);
        document.querySelectorAll('.trade-up-matrix').forEach(input => input.addEventListener('change', syncOhfCostVisibility));
        syncDealClassificationFromProject();
    });
</script>
@endpush
@endonce
