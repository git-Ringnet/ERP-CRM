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
                        <a href="{{ route('opportunities.index', ['view' => 'list']) }}"
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

        <!-- Kanban Board -->
        <div class="flex-1 overflow-x-auto overflow-y-hidden pb-4">
            <div class="flex space-x-4 h-full w-full px-4">
                @foreach ($stages as $key => $label)
                    <div class="flex-1 min-w-[16rem] bg-gray-100 rounded-lg flex flex-col h-full max-h-[calc(100vh-14rem)]">
                        <!-- Adjusted height to use max-h and flex -->
                        <div class="p-3 bg-gray-200 rounded-t-lg font-semibold text-gray-700 flex justify-between items-center">
                            <span>{{ $label }}</span>
                            <span
                                class="bg-gray-300 text-gray-600 px-2 py-0.5 rounded-full text-xs">{{ $kanbanData[$key]->count() }}</span>
                        </div>
                        <div class="p-2 overflow-y-auto flex-1 space-y-3 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent custom-scrollbar"
                            id="stage-{{ $key }}" data-stage="{{ $key }}">
                            @foreach ($kanbanData[$key] as $opportunity)
                                <div class="bg-white p-3 rounded shadow-sm border border-gray-200 cursor-move hover:shadow-md transition-shadow relative group"
                                    draggable="true" data-id="{{ $opportunity->id }}">
                                    <div class="flex justify-between items-start mb-2">
                                        <h3 class="font-medium text-gray-900 truncate pr-6">{{ $opportunity->name }}</h3>
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
                                    <div class="mt-2 text-xs text-gray-400 flex items-center justify-between">
                                        <span><i class="far fa-user mr-1"></i> {{ $opportunity->assignedTo->name ?? 'N/A' }}</span>
                                        @if($opportunity->expected_close_date)
                                            <span class="{{ $opportunity->expected_close_date->isPast() ? 'text-red-500' : '' }}">
                                                {{ $opportunity->expected_close_date->format('d/m/Y') }}
                                            </span>
                                        @endif
                                    </div>
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