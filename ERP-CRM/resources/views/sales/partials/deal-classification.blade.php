@php
    $isLocked = $isLocked ?? false;
    $saleProject = isset($sale) ? $sale->project : null;
    $projectTradeUp = $saleProject?->deal_type === 'trade_up';
    $matrix = old('trade_up_matrix', isset($sale) ? $sale->trade_up_matrix : ($projectTradeUp ? null : 'none'));
    $hasProject = !empty($saleProject) || !empty($selectedProject) || old('project_id');
    $showRequireProjectNotice = in_array($matrix, ['correct', 'incorrect'], true) && !$hasProject;
@endphp

<div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4" id="dealClassification" data-project-trade-up="{{ $projectTradeUp ? '1' : '0' }}" data-is-locked="{{ $isLocked ? '1' : '0' }}">
    <div class="flex items-start gap-3">
        <i class="fas fa-tags mt-1 text-indigo-600"></i>
        <div class="flex-1">
            <h4 class="font-semibold text-indigo-950">Phân loại deal <span class="text-red-500">*</span></h4>
            <p class="mt-1 text-xs text-indigo-800">Chọn License VNET nếu áp dụng. Chọn một trạng thái Trade up hoặc “Không thuộc trường hợp này”.</p>

            <div class="mt-3 flex flex-wrap items-center gap-6">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-800 cursor-pointer select-none">
                    <input type="checkbox" name="is_license_vnet" value="1" {{ old('is_license_vnet', $sale->is_license_vnet ?? false) ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span>License VNET</span>
                </label>
                @if($isLocked)<input type="hidden" name="is_license_vnet" value="{{ ($sale->is_license_vnet ?? false) ? 1 : 0 }}">@endif

                <label class="inline-flex items-center gap-2 text-sm font-medium text-purple-900 bg-purple-50 px-2.5 py-1 rounded border border-purple-200 cursor-pointer select-none">
                    <input type="checkbox" name="is_fulfill" id="deal_is_fulfill" value="1" {{ old('is_fulfill', $sale->is_fulfill ?? false) ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }} class="rounded border-purple-300 text-purple-600 focus:ring-purple-500" onchange="syncTradeUpRequirements()">
                    <span><i class="fas fa-truck-loading text-purple-600 mr-1"></i> Fulfill - Hãng đưa xuống</span>
                </label>
                @if($isLocked)<input type="hidden" name="is_fulfill" value="{{ ($sale->is_fulfill ?? false) ? 1 : 0 }}">@endif
            </div>

            <div class="mt-3 grid gap-2 sm:grid-cols-3" id="tradeUpMatrixChoices">
                @foreach(['none' => 'Không thuộc trường hợp này', 'correct' => 'Trade up đúng matrix', 'incorrect' => 'Trade up không đúng matrix'] as $value => $label)
                    <label class="flex items-center gap-2 rounded border border-indigo-200 bg-white px-3 py-2 text-sm text-gray-800 cursor-pointer">
                        <input type="radio" name="trade_up_matrix" value="{{ $value }}" {{ $matrix === $value ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }} class="trade-up-matrix text-indigo-600 focus:ring-indigo-500">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @if($isLocked)<input type="hidden" name="trade_up_matrix" value="{{ $sale->trade_up_matrix ?? 'none' }}">@endif

            <div id="projectTradeUpNotice" class="{{ $projectTradeUp ? '' : 'hidden' }} mt-3 rounded bg-amber-50 p-2 text-xs text-amber-800">
                <i class="fas fa-info-circle mr-1 text-amber-600"></i> ĐKDA này là Trade up: không thể bỏ đánh dấu Trade up; vui lòng chọn đúng hoặc không đúng matrix.
            </div>
            <div id="tradeUpRequireProjectNotice" class="{{ $showRequireProjectNotice ? '' : 'hidden' }} mt-3 rounded-lg border border-rose-200 bg-rose-50 p-2.5 text-xs text-rose-800 flex items-start gap-2">
                <i class="fas fa-exclamation-triangle text-rose-600 mt-0.5"></i>
                <div>
                    <strong>Bắt buộc có dự án đính kèm:</strong> Đơn hàng Trade up / Project bắt buộc phải tạo từ dự án đã đăng ký trước đó (trừ khi là đơn Fulfill). Vui lòng chọn Dự án chính.
                </div>
            </div>
            <label id="ohfCostAddedWrapper" class="{{ $matrix === 'incorrect' ? '' : 'hidden' }} mt-3 flex items-center gap-2 text-sm font-medium text-amber-900">
                <input type="checkbox" name="ohf_cost_added" value="1" {{ old('ohf_cost_added', $sale->ohf_cost_added ?? false) ? 'checked' : '' }} {{ $isLocked ? 'disabled' : '' }} class="rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                Đã bổ sung chi phí OHF ($200) vào PNL
            </label>
            @if($isLocked)<input type="hidden" name="ohf_cost_added" value="{{ ($sale->ohf_cost_added ?? false) ? 1 : 0 }}">@endif
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
    function syncDealClassificationFromProject() {
        const dealClassEl = document.getElementById('dealClassification');
        const isLocked = dealClassEl?.dataset.isLocked === '1';
        const project = document.getElementById('projectSelect');
        const selected = project && project.options[project.selectedIndex];
        const currentProjectTradeUp = dealClassEl?.dataset.projectTradeUp === '1';
        const isTradeUp = selected ? selected.dataset.dealType === 'trade_up' : currentProjectTradeUp;
        const none = document.querySelector('.trade-up-matrix[value="none"]');
        const notice = document.getElementById('projectTradeUpNotice');
        if (none && !isLocked) {
            none.disabled = !!isTradeUp;
            if (isTradeUp && none.checked) {
                const correct = document.querySelector('.trade-up-matrix[value="correct"]');
                if (correct) correct.checked = true;
            }
        }
        if (notice) notice.classList.toggle('hidden', !isTradeUp);
        syncTradeUpRequirements();
    }

    function syncTradeUpRequirements() {
        const selectedMatrix = document.querySelector('.trade-up-matrix:checked')?.value;
        const isTradeUpActive = selectedMatrix === 'correct' || selectedMatrix === 'incorrect';
        const isFulfill = document.getElementById('deal_is_fulfill')?.checked;
        const incorrect = document.querySelector('.trade-up-matrix[value="incorrect"]');
        const ohfWrapper = document.getElementById('ohfCostAddedWrapper');
        if (ohfWrapper) ohfWrapper.classList.toggle('hidden', !incorrect || !incorrect.checked);

        const projectSelect = document.getElementById('projectSelect');
        const hasProjectSelected = projectSelect ? !!projectSelect.value : {{ (!empty($saleProject) || !empty($selectedProject)) ? 'true' : 'false' }};

        const requireNotice = document.getElementById('tradeUpRequireProjectNotice');
        if (requireNotice) {
            // Fulfill exempts project requirement
            requireNotice.classList.toggle('hidden', isFulfill || !isTradeUpActive || hasProjectSelected);
        }

        // When Trade up is selected, auto ensure project mode and project wrapper are active
        const saleTypeSelect = document.getElementById('saleType');
        const projectWrapper = document.getElementById('projectSelectWrapper');
        const projectSelect = document.getElementById('projectSelect');
        const projectLabel = document.querySelector('label[for="projectSelect"]') || projectWrapper?.querySelector('label');

        if (isTradeUpActive) {
            if (saleTypeSelect && saleTypeSelect.value !== 'project') {
                saleTypeSelect.value = 'project';
                if (typeof toggleProjectSelect === 'function') {
                    toggleProjectSelect();
                } else if (projectWrapper) {
                    projectWrapper.classList.remove('hidden');
                }
            } else if (projectWrapper) {
                projectWrapper.classList.remove('hidden');
            }

            // Highlight project requirement
            if (projectLabel && !document.getElementById('tradeUpProjectBadge')) {
                const badge = document.createElement('span');
                badge.id = 'tradeUpProjectBadge';
                badge.className = 'ml-2 inline-flex items-center text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-200 px-2 py-0.5 rounded-full';
                badge.innerHTML = '<i class="fas fa-asterisk text-[9px] mr-1"></i> Bắt buộc cho Trade up';
                projectLabel.appendChild(badge);
            }
        } else {
            const badge = document.getElementById('tradeUpProjectBadge');
            if (badge) badge.remove();
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('projectSelect')?.addEventListener('change', syncDealClassificationFromProject);
        document.querySelectorAll('.trade-up-matrix').forEach(input => input.addEventListener('change', syncTradeUpRequirements));

        // Listen for saleType changes: if user switches back to retail while Trade up is checked, warn or revert
        document.getElementById('saleType')?.addEventListener('change', function() {
            const selectedMatrix = document.querySelector('.trade-up-matrix:checked')?.value;
            if (this.value === 'retail' && (selectedMatrix === 'correct' || selectedMatrix === 'incorrect')) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Đơn hàng Trade up bắt buộc chọn Dự án',
                        text: 'Phân loại hiện tại là Trade up nên không thể chọn loại đơn bán lẻ mà không có dự án. Nếu muốn bán lẻ, vui lòng đổi phân loại deal sang "Không thuộc trường hợp này".',
                        confirmButtonText: 'Đã hiểu'
                    });
                }
                this.value = 'project';
                if (typeof toggleProjectSelect === 'function') {
                    toggleProjectSelect();
                }
            }
        });

        syncDealClassificationFromProject();
    });
</script>
@endpush
@endonce
