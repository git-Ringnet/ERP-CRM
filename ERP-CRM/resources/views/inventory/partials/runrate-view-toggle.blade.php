@php
    $currentRunrateView = $runrateView ?? 'model';
    $toggleQuery = request()->except(['auto_switch_tab', 'page_runrate', 'page_runrate_model']);
@endphp
<div class="mx-4 mt-3 flex flex-wrap items-center justify-between gap-2">
    <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5 text-xs font-semibold">
        <a href="{{ route('inventory.index', array_merge($toggleQuery, ['tab' => 'runrate', 'runrate_view' => 'model'])) }}"
           class="px-3 py-1.5 rounded-md transition-colors {{ $currentRunrateView === 'model' ? 'bg-white text-primary shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
            <i class="fas fa-cubes mr-1"></i>Theo model
        </a>
        <a href="{{ route('inventory.index', array_merge($toggleQuery, ['tab' => 'runrate', 'runrate_view' => 'po'])) }}"
           class="px-3 py-1.5 rounded-md transition-colors {{ $currentRunrateView === 'po' ? 'bg-white text-primary shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
            <i class="fas fa-file-invoice mr-1"></i>Theo PO (chi tiết S/N)
        </a>
    </div>
    @if($currentRunrateView === 'model')
        <span class="text-xs text-gray-500">
            Hàng runrate giữ theo model + số lượng. Khi xuất kho, S/N quét thực tế sẽ tự xác định PO nguồn.
        </span>
    @endif
</div>
