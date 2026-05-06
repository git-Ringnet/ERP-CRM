@extends('layouts.app')

@section('title', 'Quản lý Cơ hội')
@section('page-title', 'Quản lý Cơ hội')

@section('content')
    <div class="h-full flex flex-col">
        <!-- Header Section -->
        <div class="bg-white rounded-lg shadow-sm mb-4 shrink-0">
            <div class="p-4 border-b border-gray-200">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-funnel-dollar text-yellow-500 mr-2"></i>Quy trình bán hàng (Pipeline)
                    </h2>
                    <div class="flex gap-2">
                        <a href="{{ route('opportunities.index', ['view' => 'list'] + request()->except('view')) }}"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-list mr-2"></i>Dạng danh sách
                        </a>
                        <a href="{{ route('opportunities.create') }}"
                            class="inline-flex items-center px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors">
                            <i class="fas fa-plus mr-2"></i>Thêm mới
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="bg-white rounded-lg shadow-sm mb-4">
            <form action="{{ route('opportunities.index') }}" method="GET" class="p-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4 items-end">
                <input type="hidden" name="view" value="kanban">
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Từ ngày</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}" 
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Đến ngày</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}" 
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Khách hàng</label>
                    <input type="text" name="customer_name" value="{{ request('customer_name') }}" placeholder="Tên khách hàng..."
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200 text-sm">
                </div>
                <div class="md:col-span-2 lg:col-span-1 xl:col-span-1">
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Tìm theo tên/ghi chú</label>
                    <div class="flex gap-2">
                        <input type="text" name="name" value="{{ request('name') }}" placeholder="Tên..."
                            class="flex-1 min-w-0 border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200 text-sm">
                        <input type="text" name="notes" value="{{ request('notes') }}" placeholder="Ghi chú..."
                            class="flex-1 min-w-0 border-gray-300 rounded-lg shadow-sm focus:border-orange-500 focus:ring focus:ring-orange-200 text-sm">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors text-sm flex-1 sm:flex-none">
                        <i class="fas fa-filter mr-1"></i> Lọc
                    </button>
                    <a href="{{ route('opportunities.index', ['view' => 'kanban']) }}" 
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm flex-1 sm:flex-none text-center">
                        <i class="fas fa-times mr-1"></i> Xóa
                    </a>
                </div>
            </form>
        </div>

        <!-- Kanban Board -->
        <div class="flex-1 overflow-x-auto pb-4 custom-scrollbar">
            <div class="flex h-full space-x-2 px-2">
                @foreach ($stages as $key => $label)
                    <div class="flex-1 min-w-[200px] bg-gray-100 rounded-lg flex flex-col h-full max-h-[calc(100vh-20rem)] shadow-inner">
                        <!-- Adjusted height to use max-h and flex -->
                        <div class="p-2.5 bg-gray-200 rounded-t-lg font-semibold text-gray-700 flex justify-between items-center shrink-0 border-b border-gray-300">
                            <span class="text-xs sm:text-sm truncate">{{ $label }}</span>
                            <span
                                class="bg-white text-gray-600 px-2 py-0.5 rounded-full text-[10px] border border-gray-200">{{ $kanbanData[$key]->count() }}</span>
                        </div>
                        <div class="p-2 overflow-y-auto flex-1 space-y-2 custom-scrollbar"
                            id="stage-{{ $key }}" data-stage="{{ $key }}">
                            @foreach ($kanbanData[$key] as $opportunity)
                                <div class="bg-white p-3 rounded shadow-sm border border-gray-200 cursor-move hover:shadow-md transition-shadow relative group"
                                    draggable="true" data-id="{{ $opportunity->id }}">
                                    <div class="flex justify-between items-start mb-2">
                                        <a href="{{ route('opportunities.show', $opportunity) }}"
                                            class="font-medium text-gray-900 truncate pr-6 hover:text-blue-600">
                                            {{ $opportunity->name }}
                                        </a>
                                        <a href="{{ route('opportunities.edit', $opportunity) }}"
                                            class="opacity-0 group-hover:opacity-100 absolute top-2 right-2 text-gray-400 hover:text-blue-600">
                                            <i class="fas fa-pencil-alt text-xs"></i>
                                        </a>
                                    </div>
                                    <p class="text-xs text-gray-500 mb-2 truncate"><i class="fas fa-building mr-1"></i>
                                        {{ $opportunity->customer->name ?? 'N/A' }}</p>
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="font-semibold text-green-600">{{ number_format($opportunity->amount) }}
                                            {{ $opportunity->currency }}</span>
                                        @if($opportunity->probability > 0)
                                            <span
                                                class="bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded">{{ $opportunity->probability }}%</span>
                                        @endif
                                    </div>

                                    @if($opportunity->description)
                                        <div class="mt-2 text-[11px] text-gray-600 italic bg-gray-50 p-2 rounded border-l-2 {{ $opportunity->stage === 'lost' ? 'border-red-400' : 'border-gray-300' }}">
                                            <div class="font-medium text-gray-700 mb-0.5 not-italic">Ghi chú:</div>
                                            <div class="line-clamp-2" title="{{ $opportunity->description }}">
                                                {{ $opportunity->description }}
                                            </div>
                                        </div>
                                    @endif

                                    <div class="mt-2 text-xs text-gray-400 flex items-center justify-between">
                                        <span><i class="far fa-user mr-1"></i> {{ $opportunity->assignedTo->name ?? 'N/A' }}</span>
                                        @if($opportunity->expected_close_date)
                                            <span class="{{ $opportunity->expected_close_date->isPast() ? 'text-red-500' : '' }}">
                                                {{ $opportunity->expected_close_date->format('d/m/Y') }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($opportunity->next_action)
                                        <div class="mt-2 text-xs border-t pt-2 border-gray-100">
                                            <div class="font-medium text-gray-700">Tiếp theo:</div>
                                            <div class="text-gray-600 truncate" title="{{ $opportunity->next_action }}">
                                                {{ $opportunity->next_action }}
                                            </div>
                                            @if($opportunity->next_action_date)
                                                <div
                                                    class="{{ $opportunity->next_action_date->isPast() ? 'text-red-500' : 'text-gray-400' }}">
                                                    {{ $opportunity->next_action_date->format('d/m/Y') }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Simple Drag and Drop Implementation
                let draggedItem = null;

                document.querySelectorAll('[draggable="true"]').forEach(item => {
                    item.addEventListener('dragstart', function (e) {
                        draggedItem = this;
                        this.classList.add('opacity-50');
                        e.dataTransfer.effectAllowed = 'move';
                    });

                    item.addEventListener('dragend', function () {
                        this.classList.remove('opacity-50');
                        draggedItem = null;
                    });
                });

                document.querySelectorAll('[id^="stage-"]').forEach(container => {
                    container.addEventListener('dragover', function (e) {
                        e.preventDefault();
                        e.dataTransfer.dropEffect = 'move';
                        this.classList.add('bg-gray-200');
                    });

                    container.addEventListener('dragleave', function () {
                        this.classList.remove('bg-gray-200');
                    });

                    container.addEventListener('drop', function (e) {
                        e.preventDefault();
                        this.classList.remove('bg-gray-200');

                        if (draggedItem) {
                            this.appendChild(draggedItem);
                            const opportunityId = draggedItem.dataset.id;
                            const newStage = this.dataset.stage;

                            // Update via AJAX
                            fetch(`/opportunities/${opportunityId}/update-stage`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ stage: newStage })
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        // Optional: Show toast
                                    }
                                })
                                .catch(error => console.error('Error:', error));
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection