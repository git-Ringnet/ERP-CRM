@extends('layouts.app')

@section('title', 'Tồn kho')
@section('page-title', 'Quản lý Tồn kho')

@section('content')
@php
    $activeTab = request('tab', 'stocking');
@endphp

    <div class="bg-white rounded-lg shadow-sm">
        <!-- Header & Filters -->
        <div class="p-3 sm:p-4 border-b border-gray-200 space-y-3">
            <div class="flex flex-col sm:flex-row gap-3">
                <!-- Search -->
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tìm kiếm</label>
                    <div class="relative">
                        <form action="{{ route('inventory.index') }}" method="GET" class="flex">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Tìm kiếm sản phẩm..."
                                class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="hidden" name="tab" value="{{ $activeTab }}">
                            <input type="hidden" name="warehouse_id" value="{{ request('warehouse_id') }}">
                        </form>
                    </div>
                </div>

                <!-- Filter by Warehouse -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Kho</label>
                    <select name="warehouse_id"
                        onchange="window.location.href='{{ route('inventory.index') }}?warehouse_id='+this.value+'&tab={{ $activeTab }}&search={{ request('search') }}'"
                        class="w-full sm:w-auto border border-gray-300 rounded-lg px-3 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-primary appearance-none bg-white">
                        <option value="">Tất cả kho</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap justify-between items-center gap-2 pt-2">
                <div class="flex gap-2">
                    <a href="{{ route('inventory.export', request()->query()) }}"
                        class="inline-flex items-center justify-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-emerald-600 transition-colors text-sm">
                        <i class="fas fa-file-excel mr-2"></i>Xuất Excel
                    </a>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="border-b border-gray-200 bg-gray-50/50 flex flex-col sm:flex-row sm:justify-between sm:items-center pr-4">
            <nav class="-mb-px flex space-x-6 px-4" aria-label="Tabs">
                <a href="{{ route('inventory.index', array_merge(request()->query(), ['tab' => 'stocking'])) }}" 
                   class="{{ ($activeTab === 'stocking') ? 'border-primary text-primary border-b-2 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium' }} whitespace-nowrap py-3 px-1 text-sm transition-all">
                    Hàng stocking
                </a>
                <a href="{{ route('inventory.index', array_merge(request()->query(), ['tab' => 'project'])) }}" 
                   class="{{ ($activeTab === 'project') ? 'border-primary text-primary border-b-2 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium' }} whitespace-nowrap py-3 px-1 text-sm transition-all">
                    Hàng dự án
                </a>
                <a href="{{ route('inventory.index', array_merge(request()->query(), ['tab' => 'rmodel'])) }}" 
                   class="{{ ($activeTab === 'rmodel') ? 'border-primary text-primary border-b-2 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium' }} whitespace-nowrap py-3 px-1 text-sm transition-all">
                    hàng R và NFR
                </a>
            </nav>
            @if(in_array($activeTab, ['stocking', 'project', 'rmodel']))
                <div class="px-4 py-2 sm:py-0">
                    <button onclick="addCustomColumn('{{ $activeTab }}')" 
                            class="inline-flex items-center justify-center px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors text-xs font-medium">
                        <i class="fas fa-plus mr-1"></i>Thêm cột cho {{ $activeTab === 'stocking' ? 'Hàng stocking' : ($activeTab === 'project' ? 'Hàng dự án' : 'hàng R & NFR') }}
                    </button>
                </div>
            @endif
        </div>

        <!-- Tab 1 & 2: Stocking and Project detailed lists -->
        @if($activeTab === 'stocking' || $activeTab === 'project')
            @php
                $items = ($activeTab === 'stocking') ? $stockingItems : $projectItems;
                $cols = ($activeTab === 'stocking') ? $stockingColumns : $projectColumns;
            @endphp
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="divide-x divide-gray-200">
                            <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase w-12">STT</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase min-w-[200px]">Tên thiết bị</th>
                            <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase w-16">Số lượng</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase">Người đặt hàng</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase">Số PO</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase">
                                <span class="inline-flex items-center gap-1 cursor-pointer" onclick="showProjectRuleHelp()" title="Click để xem chi tiết quy tắc hiển thị">
                                    Dự án
                                    <i class="fas fa-question-circle text-gray-400 hover:text-primary text-xs"></i>
                                </span>
                            </th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase min-w-[150px]">Người mượn thiết bị</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase min-w-[200px]">Ghi chú</th>
                            
                            <!-- Custom columns headers -->
                            @foreach($cols as $col)
                                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase min-w-[150px] relative group">
                                    <div class="flex items-center justify-between">
                                        <span>{{ $col->name }}</span>
                                        <button onclick="deleteCustomColumn({{ $col->id }}, '{{ $col->name }}')" 
                                                class="text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity ml-1" 
                                                title="Xóa cột này">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($items as $item)
                            <tr class="hover:bg-gray-50 divide-x divide-gray-100">
                                <td class="px-3 py-2 text-center text-gray-500">
                                    {{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}
                                </td>
                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-900">{{ $item->product->name }}</div>
                                    <div class="text-xs text-gray-500 flex flex-wrap gap-x-2 gap-y-0.5">
                                        <span>Mã: {{ $item->product->code }}</span>
                                        @if(!$item->isNoSku())
                                            <span class="text-primary font-semibold">S/N: {{ $item->sku }}</span>
                                        @else
                                            <span class="text-gray-400">Không serial</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-center text-gray-900 font-medium">
                                    {{ $item->quantity }}
                                </td>
                                <td class="px-3 py-2 text-gray-700">
                                    {{ $item->order_creator_name ?: '-' }}
                                </td>
                                <td class="px-3 py-2 text-gray-700 font-mono text-xs">
                                    {{ $item->purchase_order_code ?: '-' }}
                                </td>
                                <td class="px-3 py-2 text-gray-700">
                                    {{ $item->project_name ?: '-' }}
                                </td>
                                <td class="px-3 py-1.5">
                                    <input type="text" value="{{ $item->borrower }}" 
                                           placeholder="Nhập tên..." 
                                           class="w-full bg-transparent border border-transparent hover:border-gray-200 focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary rounded px-2 py-1 text-sm transition-all"
                                           data-item-id="{{ $item->item_ids }}" data-field="borrower"
                                           onblur="saveItemField(this)">
                                </td>
                                <td class="px-3 py-1.5">
                                    <input type="text" value="{{ $item->comments }}" 
                                           placeholder="Ghi chú..." 
                                           class="w-full bg-transparent border border-transparent hover:border-gray-200 focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary rounded px-2 py-1 text-sm transition-all"
                                           data-item-id="{{ $item->item_ids }}" data-field="comments"
                                           onblur="saveItemField(this)">
                                </td>
                                
                                <!-- Custom columns inputs -->
                                @foreach($cols as $col)
                                    <td class="px-3 py-1.5">
                                        @php
                                            $val = $item->custom_fields[$col->key] ?? '';
                                        @endphp
                                        <input type="text" value="{{ $val }}" 
                                               placeholder="..." 
                                               class="w-full bg-transparent border border-transparent hover:border-gray-200 focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary rounded px-2 py-1 text-sm transition-all"
                                               data-item-id="{{ $item->item_ids }}" data-custom-key="{{ $col->key }}"
                                               onblur="saveItemField(this)">
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 8 + count($cols) }}" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-boxes text-4xl mb-2"></i>
                                    <p>Không có dữ liệu thiết bị nào</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($items->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $items->appends(request()->query())->links() }}
                </div>
            @endif

        <!-- Tab 3: R & NFR Model -->
        @elseif($activeTab === 'rmodel')
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="divide-x divide-gray-200">
                            <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase w-12">STT</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase min-w-[200px]">Tên thiết bị</th>
                            <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase w-16">Số lượng</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase">Người đặt hàng</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase min-w-[150px]">Người mượn thiết bị</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase min-w-[200px]">Ghi chú</th>
                            
                            <!-- Custom columns headers -->
                            @foreach($rmodelColumns as $col)
                                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase min-w-[150px] relative group">
                                    <div class="flex items-center justify-between">
                                        <span>{{ $col->name }}</span>
                                        <button onclick="deleteCustomColumn({{ $col->id }}, '{{ $col->name }}')" 
                                                class="text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity ml-1" 
                                                title="Xóa cột này">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($rmodelItems as $item)
                            <tr class="hover:bg-gray-50 divide-x divide-gray-100">
                                <td class="px-3 py-2 text-center text-gray-500">
                                    {{ ($rmodelItems->currentPage() - 1) * $rmodelItems->perPage() + $loop->iteration }}
                                </td>
                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-900">{{ $item->product->name }}</div>
                                    <div class="text-xs text-gray-500 flex flex-wrap gap-x-2 gap-y-0.5">
                                        <span>Mã: {{ $item->product->code }}</span>
                                        @if(!$item->isNoSku())
                                            <span class="text-primary font-semibold">S/N: {{ $item->sku }}</span>
                                        @else
                                            <span class="text-gray-400">Không serial</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-center text-gray-900 font-medium">
                                    {{ $item->quantity }}
                                </td>
                                <td class="px-3 py-2 text-gray-700">
                                    {{ $item->r_model_orderer_info ?: '-' }}
                                </td>
                                <td class="px-3 py-1.5">
                                    <input type="text" value="{{ $item->borrower }}" 
                                           placeholder="Nhập tên..." 
                                           class="w-full bg-transparent border border-transparent hover:border-gray-200 focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary rounded px-2 py-1 text-sm transition-all"
                                           data-item-id="{{ $item->item_ids }}" data-field="borrower"
                                           onblur="saveItemField(this)">
                                </td>
                                <td class="px-3 py-1.5">
                                    <input type="text" value="{{ $item->comments }}" 
                                           placeholder="Ghi chú..." 
                                           class="w-full bg-transparent border border-transparent hover:border-gray-200 focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary rounded px-2 py-1 text-sm transition-all"
                                           data-item-id="{{ $item->item_ids }}" data-field="comments"
                                           onblur="saveItemField(this)">
                                </td>
                                
                                <!-- Custom columns inputs -->
                                @foreach($rmodelColumns as $col)
                                    <td class="px-3 py-1.5">
                                        @php
                                            $val = $item->custom_fields[$col->key] ?? '';
                                        @endphp
                                        <input type="text" value="{{ $val }}" 
                                               placeholder="..." 
                                               class="w-full bg-transparent border border-transparent hover:border-gray-200 focus:border-primary focus:bg-white focus:ring-1 focus:ring-primary rounded px-2 py-1 text-sm transition-all"
                                               data-item-id="{{ $item->item_ids }}" data-custom-key="{{ $col->key }}"
                                               onblur="saveItemField(this)">
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 6 + count($rmodelColumns) }}" class="px-4 py-8 text-center text-gray-500">
                                    <i class="fas fa-boxes text-4xl mb-2"></i>
                                    <p>Không có dữ liệu thiết bị nào</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($rmodelItems->hasPages())
                <div class="px-4 py-3 border-t border-gray-200">
                    {{ $rmodelItems->appends(request()->query())->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- Scripts for Inline Editing & Custom Columns -->
    <script>
        // Simple elegant Toast Notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 right-4 z-50 flex items-center px-4 py-3 rounded-lg shadow-lg text-white text-sm transition-all duration-300 transform translate-y-2 opacity-0 ${
                type === 'success' ? 'bg-green-600' : 'bg-red-600'
            }`;
            toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i> ${message}`;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            }, 10);

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        // AJAX update for borrower, comments, and custom fields
        function saveItemField(element) {
            const itemId = element.getAttribute('data-item-id');
            const field = element.getAttribute('data-field');
            const customKey = element.getAttribute('data-custom-key');
            const value = element.value;

            // Only update if value changes
            if (element.getAttribute('data-last-value') === value) {
                return;
            }

            let payload = {};
            if (customKey) {
                payload['custom_fields'] = {};
                payload['custom_fields'][customKey] = value;
            } else {
                payload[field] = value;
            }

            // Highlight editing cell
            element.classList.add('bg-yellow-50');

            fetch(`/inventory/items/${itemId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    element.setAttribute('data-last-value', value);
                    element.classList.remove('bg-yellow-50');
                    element.classList.add('bg-green-50');
                    setTimeout(() => element.classList.remove('bg-green-50'), 1000);
                    showToast('Đã lưu thành công', 'success');
                } else {
                    throw new Error(data.message || 'Lỗi không xác định');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                element.classList.remove('bg-yellow-50');
                element.classList.add('bg-red-50');
                showToast('Không thể lưu giá trị', 'error');
            });
        }

        // Initialize last values to avoid duplicate updates
        document.querySelectorAll('input[data-item-id]').forEach(input => {
            input.setAttribute('data-last-value', input.value);
        });

        // Add custom column dialog
        function addCustomColumn(tab) {
            const columnName = prompt("Nhập tên cột mới cần thêm:");
            if (!columnName || columnName.trim() === '') {
                return;
            }

            fetch(`/inventory/custom-columns`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    tab: tab,
                    name: columnName.trim()
                })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw new Error(err.message || 'Lỗi máy chủ') });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    throw new Error(data.message || 'Lỗi không xác định');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'Có lỗi xảy ra khi thêm cột.');
            });
        }

        // Delete custom column dialog
        function deleteCustomColumn(id, name) {
            if (!confirm(`Bạn có chắc chắn muốn xóa cột "${name}"? Tất cả dữ liệu của cột này trên toàn bộ thiết bị sẽ bị xóa vĩnh viễn khỏi database.`)) {
                return;
            }

            fetch(`/inventory/custom-columns/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Không thể xóa cột');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    throw new Error(data.message || 'Lỗi không xác định');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'Có lỗi xảy ra khi xóa cột.');
            });
        }

        // Show Project / End User displaying rules SweetAlert
        function showProjectRuleHelp() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Quy tắc hiển thị Dự án / End User',
                    html: `
                        <div class="text-left text-sm space-y-3" style="line-height: 1.6;">
                            <p><strong>1. Ưu tiên 1 (Dự án liên kết):</strong> Mã dự án - Tên dự án từ đơn SO tương ứng.<br><span class="text-xs text-blue-600 font-mono"></span></p>
                            <p><strong>2. Ưu tiên 2 (EU của Phiếu đặt hàng):</strong> Tên End-User/Mã số thuế của phiếu yêu cầu đặt hàng (SOR) nếu đơn SO không liên kết dự án.<br><span class="text-xs text-blue-600 font-mono"></span></p>
                            <p><strong>3. Ưu tiên 3 (Khách hàng đơn SO):</strong> Tên khách hàng của đơn SO làm phương án dự phòng cuối cùng.</p>
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonText: 'Đã hiểu',
                    confirmButtonColor: '#2563eb'
                });
            } else {
                alert("Quy tắc hiển thị Dự án / End User:\n\n1. Ưu tiên 1: Mã dự án - Tên dự án từ SO liên kết (Ví dụ: DA-0001 - DA từ 4324234).\n2. Ưu tiên 2: EU Name - MST của phiếu đặt hàng (nếu có).\n3. Ưu tiên 3: Tên khách hàng (customer_name) của đơn SO làm fallback.");
            }
        }
    </script>
@endsection