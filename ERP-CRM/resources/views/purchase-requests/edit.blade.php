@extends('layouts.app')

@section('title', 'Sửa yêu cầu báo giá')
@section('page-title', 'Sửa yêu cầu báo giá: ' . $purchaseRequest->code)

@section('content')
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-900">
                <i class="fas fa-edit text-orange-500 mr-2"></i>Chỉnh sửa yêu cầu báo giá
            </h2>
            <a href="{{ route('purchase-requests.index') }}" class="text-gray-600 hover:text-gray-900">
                <i class="fas fa-arrow-left mr-1"></i> Quay lại
            </a>
        </div>

        <form action="{{ route('purchase-requests.update', $purchaseRequest) }}" method="POST" class="p-4">
            @csrf
            @method('PUT')

            <!-- Thông tin chung -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mã yêu cầu</label>
                    <input type="text" value="{{ $purchaseRequest->code }}" readonly
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tiêu đề <span
                            class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $purchaseRequest->title) }}" required
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Hạn báo giá <span
                            class="text-red-500">*</span></label>
                    <input type="date" name="deadline"
                        value="{{ old('deadline', $purchaseRequest->deadline->format('Y-m-d')) }}" required
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mức ưu tiên</label>
                    <select name="priority" class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                        <option value="normal" {{ $purchaseRequest->priority == 'normal' ? 'selected' : '' }}>Bình thường
                        </option>
                        <option value="high" {{ $purchaseRequest->priority == 'high' ? 'selected' : '' }}>Cao</option>
                        <option value="urgent" {{ $purchaseRequest->priority == 'urgent' ? 'selected' : '' }}>Khẩn cấp
                        </option>
                    </select>
                </div>
            </div>

            <!-- Chọn NCC -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Chọn nhà cung cấp <span
                        class="text-red-500">*</span></label>
                <select name="suppliers[]" id="suppliersSelect" multiple required
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ $purchaseRequest->suppliers->contains($supplier->id) ? 'selected' : '' }}>
                            {{ $supplier->code }} - {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Có thể chọn nhiều nhà cung cấp</p>
            </div>

            <!-- Danh sách sản phẩm -->
            <div class="border-t border-gray-200 pt-4 mb-6">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-lg font-semibold text-gray-900">Danh sách sản phẩm</h3>
                    <button type="button" id="addItem"
                        class="px-4 py-2 text-sm bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        <i class="fas fa-plus mr-1"></i>Thêm sản phẩm
                    </button>
                </div>
                <div id="itemsContainer" class="space-y-3">
                    @foreach($purchaseRequest->items as $index => $item)
                        <div
                            class="item-row grid grid-cols-12 gap-3 items-end p-3 bg-gray-50 rounded-lg border border-gray-200 relative">
                            <div class="col-span-4 relative product-autocomplete">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Tên sản phẩm</label>
                                <input type="text" name="items[{{ $index }}][product_name]" value="{{ $item->product_name }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 product-name-input"
                                    autocomplete="off" placeholder="Nhập tên sản phẩm...">
                                <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}"
                                    class="product-id">
                                <ul
                                    class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden suggestions-list top-full left-0 mt-1">
                                </ul>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng</label>
                                <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}" min="1"
                                    required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-qty">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Đơn vị</label>
                                <input type="text" name="items[{{ $index }}][unit]" value="{{ $item->unit }}"
                                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-unit">
                            </div>
                            <div class="col-span-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Quy cách</label>
                                <input type="text" name="items[{{ $index }}][specifications]"
                                    value="{{ $item->specifications }}"
                                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded">
                            </div>
                            <div class="col-span-1 flex justify-center">
                                <button type="button"
                                    class="remove-item w-8 h-8 bg-red-100 text-red-600 rounded hover:bg-red-200" {{ $loop->count == 1 ? 'style=display:none' : '' }}>
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Yêu cầu đặc biệt -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Yêu cầu đặc biệt</label>
                <textarea name="requirements" rows="3"
                    class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg">{{ old('requirements', $purchaseRequest->requirements) }}</textarea>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('purchase-requests.index') }}"
                    class="px-4 py-2 text-sm text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                    <i class="fas fa-times mr-1"></i> Hủy
                </a>
                <button type="submit" class="px-4 py-2 text-sm text-white bg-blue-500 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-save mr-1"></i> Cập nhật
                </button>
            </div>
        </form>
    </div>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
        <style>
            .ts-wrapper.multi .ts-control {
                padding: 6px 10px;
                border-radius: 0.5rem;
                border-color: #d1d5db;
                min-height: 42px;
            }

            .ts-wrapper .ts-control .item {
                background-color: #3b82f6;
                color: white;
                border-radius: 4px;
                padding: 2px 8px;
            }

            .ts-dropdown {
                border-radius: 0.5rem;
            }

            .ts-dropdown .option {
                padding: 10px 12px;
            }

            .ts-dropdown .option.active {
                background-color: #eff6ff;
                color: #1e3a8a;
            }

            /* Custom Autocomplete Suggestions */
            .suggestions-list::-webkit-scrollbar {
                width: 6px;
            }

            .suggestions-list::-webkit-scrollbar-thumb {
                background-color: #cbd5e1;
                border-radius: 3px;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                new TomSelect('#suppliersSelect', {
                    plugins: ['remove_button'],
                    placeholder: 'Tìm và chọn nhà cung cấp...',
                    allowEmptyOption: false,
                    maxOptions: 100,
                    render: {
                        no_results: function () {
                            return '<div class="no-results p-3 text-gray-500">Không tìm thấy nhà cung cấp</div>';
                        }
                    }
                });
            });

            @php
                $productData = $products->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->name,
                        'code' => $p->code,
                        'unit' => $p->unit
                    ];
                });
            @endphp
            const products = @json($productData);

            function setupProductAutocomplete(row) {
                const input = row.querySelector('.product-name-input');
                const idInput = row.querySelector('.product-id');
                const suggestions = row.querySelector('.suggestions-list');
                const unitInput = row.querySelector('.item-unit');

                function renderSuggestions(matches) {
                    suggestions.innerHTML = '';
                    if (matches.length === 0) {
                        suggestions.classList.add('hidden');
                        return;
                    }
                    matches.forEach(p => {
                        const li = document.createElement('li');
                        li.className = 'px-3 py-2 cursor-pointer hover:bg-blue-50 border-b border-gray-100 last:border-0';
                        li.innerHTML = `
                            <div class="font-medium text-sm text-gray-900">${p.name}</div>
                            <div class="text-xs text-gray-500">Mã: ${p.code} | ĐVT: ${p.unit || '---'}</div>
                        `;
                        li.addEventListener('mousedown', (e) => {
                            e.preventDefault(); // Prevent blur event
                            input.value = p.name;
                            idInput.value = p.id;
                            if (unitInput && p.unit) {
                                unitInput.value = p.unit;
                            }
                            suggestions.classList.add('hidden');
                        });
                        suggestions.appendChild(li);
                    });
                    suggestions.classList.remove('hidden');
                }

                input.addEventListener('input', function() {
                    const val = this.value.toLowerCase();
                    // Reset ID if input changes
                    const exactMatch = products.find(p => p.name.toLowerCase() === val);
                    idInput.value = exactMatch ? exactMatch.id : '';

                    if (val.length < 1) {
                        suggestions.classList.add('hidden');
                        return;
                    }

                    const matches = products.filter(p => 
                        p.name.toLowerCase().includes(val) || 
                        p.code.toLowerCase().includes(val)
                    ).slice(0, 20);
                    renderSuggestions(matches);
                });

                input.addEventListener('focus', function() {
                    if (this.value.trim() === '') {
                        renderSuggestions(products.slice(0, 20));
                    } else {
                         this.dispatchEvent(new Event('input'));
                    }
                });

                input.addEventListener('blur', function() {
                   setTimeout(() => suggestions.classList.add('hidden'), 200);
                });
            }

            document.querySelectorAll('.item-row').forEach(row => setupProductAutocomplete(row));

            let itemIndex = {{ $purchaseRequest->items->count() }};

            document.getElementById('addItem').addEventListener('click', function() {
                const container = document.getElementById('itemsContainer');
                const newRow = document.createElement('div');
                newRow.className = 'item-row grid grid-cols-12 gap-3 items-end p-3 bg-gray-50 rounded-lg border border-gray-200 relative';
                newRow.innerHTML = `
                    <div class="col-span-4 relative product-autocomplete">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tên sản phẩm</label>
                        <input type="text" name="items[${itemIndex}][product_name]" class="product-name-input w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" autocomplete="off" placeholder="Nhập tên sản phẩm...">
                        <input type="hidden" name="items[${itemIndex}][product_id]" class="product-id">
                        <ul class="absolute z-50 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden suggestions-list top-full left-0 mt-1"></ul>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Số lượng</label>
                        <input type="number" name="items[${itemIndex}][quantity]" value="1" min="1" required class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-qty">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Đơn vị</label>
                        <input type="text" name="items[${itemIndex}][unit]" value="Cái" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded item-unit">
                    </div>
                    <div class="col-span-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Quy cách</label>
                        <input type="text" name="items[${itemIndex}][specifications]" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded">
                    </div>
                    <div class="col-span-1 flex justify-center">
                        <button type="button" class="remove-item w-8 h-8 bg-red-100 text-red-600 rounded hover:bg-red-200"><i class="fas fa-trash"></i></button>
                    </div>
                `;
                container.appendChild(newRow);
                setupProductAutocomplete(newRow);
                itemIndex++;
                updateRemoveButtons();
            });

            document.getElementById('itemsContainer').addEventListener('click', function(e) {
                if (e.target.closest('.remove-item')) {
                    e.target.closest('.item-row').remove();
                    updateRemoveButtons();
                }
            });

            function updateRemoveButtons() {
                const rows = document.querySelectorAll('.item-row');
                rows.forEach(row => {
                    row.querySelector('.remove-item').style.display = rows.length > 1 ? 'flex' : 'none';
                });
            }
        </script>
    @endpush
@endsection
