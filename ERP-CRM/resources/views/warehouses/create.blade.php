@extends('layouts.app')

@section('title', 'Thêm kho mới')
@section('page-title', 'Thêm kho mới')

@section('content')
@push('styles')

    <style>
        .searchable-select {
            position: relative;
        }
        .searchable-dropdown {
            top: 100%;
            left: 0;
            right: 0;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #d1d5db;
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
            z-index: 50;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .searchable-option {
            padding: 0.5rem 0.75rem;
            cursor: pointer;
            font-size: 0.875rem;
        }
        .searchable-option:hover {
            background-color: #eff6ff;
        }
        .searchable-option.highlighted {
            background-color: #dbeafe;
        }
        .no-results {
            padding: 0.5rem 0.75rem;
            color: #6b7280;
            font-style: italic;
            font-size: 0.875rem;
        }
    </style>
@endpush


<div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Thông tin kho hàng</h2>
        </div>
        
        <form action="{{ route('warehouses.store') }}" method="POST" class="p-4">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Mã kho -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                        Mã kho <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="code" id="code" value="{{ old('code', $code) }}" 
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('code') border-red-500 @enderror">
                    @error('code')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Tên kho -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        Tên kho <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" 
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Loại kho -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 mb-1">
                        Loại kho <span class="text-red-500">*</span>
                    </label>
                    <select name="type" id="type" 
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('type') border-red-500 @enderror">
                        <option value="physical" {{ old('type') == 'physical' ? 'selected' : '' }}>Kho vật lý</option>
                        <option value="virtual" {{ old('type') == 'virtual' ? 'selected' : '' }}>Kho ảo</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Trạng thái -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                        Trạng thái <span class="text-red-500">*</span>
                    </label>
                    <select name="status" id="status" 
                            class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('status') border-red-500 @enderror">
                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Đang hoạt động</option>
                        <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Đang bảo trì</option>
                        <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Ngừng hoạt động</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Người quản lý -->
                <div>
                    <label for="manager_id" class="block text-sm font-medium text-gray-700 mb-1">
                        Người quản lý
                    </label>
                    <div class="searchable-select" id="managerSelect">
                        <input type="text" class="searchable-input w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent @error('manager_id') border-red-500 @enderror" 
                               placeholder="Gõ để tìm người quản lý..." 
                               value="{{ old('manager_name') }}"
                               autocomplete="off">
                        <input type="hidden" name="manager_id" id="manager_id" value="{{ old('manager_id') }}" required>
                        <div class="searchable-dropdown hidden absolute">
                            @foreach($managers as $manager)
                                <div class="searchable-option" 
                                     data-value="{{ $manager->id }}" 
                                     data-text="{{ $manager->name }} ({{ $manager->employee_code }})">
                                    {{ $manager->name }} ({{ $manager->employee_code }})
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @error('manager_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Số điện thoại -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                        Số điện thoại
                    </label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}" 
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Diện tích -->
                <div>
                    <label for="area" class="block text-sm font-medium text-gray-700 mb-1">
                        Diện tích (m²)
                    </label>
                    <input type="number" name="area" id="area" value="{{ old('area') }}" step="0.01" min="0"
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    @error('area')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sức chứa -->
                <div>
                    <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">
                        Sức chứa
                    </label>
                    <input type="number" name="capacity" id="capacity" value="{{ old('capacity') }}" min="0"
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    @error('capacity')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Loại sản phẩm -->
                <div class="md:col-span-2">
                    <label for="product_type" class="block text-sm font-medium text-gray-700 mb-1">
                        Loại sản phẩm lưu trữ
                    </label>
                    <input type="text" name="product_type" id="product_type" value="{{ old('product_type') }}" 
                           placeholder="VD: Điện tử, Thực phẩm, Hóa chất..."
                           class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    @error('product_type')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Địa chỉ -->
                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                        Địa chỉ
                    </label>
                    <textarea name="address" id="address" rows="2"
                              class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">{{ old('address') }}</textarea>
                    @error('address')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>



                <!-- Ghi chú -->
                <div class="md:col-span-2">
                    <label for="note" class="block text-sm font-medium text-gray-700 mb-1">
                        Ghi chú
                    </label>
                    <textarea name="note" id="note" rows="3"
                              class="w-full px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">{{ old('note') }}</textarea>
                    @error('note')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
                <a href="{{ route('warehouses.index') }}" 
                   class="px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Hủy
                </a>
                <button type="submit" 
                        class="px-4 py-2 text-sm text-white bg-primary rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="fas fa-save mr-2"></i>Lưu
                </button>
            </div>
        </form>
</div>
@endsection

@push('scripts')
    <script>
        function removeAccents(str) {
            if (!str) return '';
            return str.normalize('NFD')
                      .replace(/[\u0300-\u036f]/g, '')
                      .replace(/đ/g, 'd')
                      .replace(/Đ/g, 'D')
                      .toLowerCase();
        }

        function initSearchableSelect(container) {
            const input = container.querySelector('.searchable-input');
            const hiddenInput = container.querySelector('input[type="hidden"]');
            const dropdown = container.querySelector('.searchable-dropdown');
            const options = dropdown.querySelectorAll('.searchable-option');
            
            input.addEventListener('focus', () => {
                dropdown.classList.remove('hidden');
                filterOptions(input.value);
            });
            
            input.addEventListener('input', (e) => {
                filterOptions(e.target.value);
                // Clear hidden input if manual typing occurs without selection
                // hiddenInput.value = ''; 
            });
            
            function filterOptions(query) {
                const q = removeAccents(query);
                let hasResults = false;
                options.forEach(opt => {
                    const text = removeAccents(opt.dataset.text);
                    if (text.includes(q)) {
                        opt.classList.remove('hidden');
                        hasResults = true;
                    } else {
                        opt.classList.add('hidden');
                    }
                });

                
                let noResults = dropdown.querySelector('.no-results');
                if (!hasResults) {
                    if (!noResults) {
                        noResults = document.createElement('div');
                        noResults.className = 'no-results';
                        noResults.textContent = 'Không tìm thấy kết quả';
                        dropdown.appendChild(noResults);
                    }
                    noResults.classList.remove('hidden');
                } else if (noResults) {
                    noResults.classList.add('hidden');
                }
            }
            
            options.forEach(opt => {
                opt.addEventListener('click', () => {
                    input.value = opt.dataset.text;
                    hiddenInput.value = opt.dataset.value;
                    dropdown.classList.add('hidden');
                    
                    // Add a special input for the name so it's preserved on validation error
                    let nameInput = container.querySelector('input[name="manager_name"]');
                    if (!nameInput) {
                        nameInput = document.createElement('input');
                        nameInput.type = 'hidden';
                        nameInput.name = 'manager_name';
                        container.appendChild(nameInput);
                    }
                    nameInput.value = opt.dataset.text;
                });
            });
            
            // Keyboard navigation
            input.addEventListener('keydown', (e) => {
                const visibleOptions = [...options].filter(o => !o.classList.contains('hidden'));
                const highlighted = dropdown.querySelector('.searchable-option.highlighted');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (!highlighted && visibleOptions.length) {
                        visibleOptions[0].classList.add('highlighted');
                    } else if (highlighted) {
                        const idx = visibleOptions.indexOf(highlighted);
                        if (idx < visibleOptions.length - 1) {
                            highlighted.classList.remove('highlighted');
                            visibleOptions[idx + 1].classList.add('highlighted');
                            visibleOptions[idx + 1].scrollIntoView({ block: 'nearest' });
                        }
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (highlighted) {
                        const idx = visibleOptions.indexOf(highlighted);
                        if (idx > 0) {
                            highlighted.classList.remove('highlighted');
                            visibleOptions[idx - 1].classList.add('highlighted');
                            visibleOptions[idx - 1].scrollIntoView({ block: 'nearest' });
                        }
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (highlighted) highlighted.click();
                } else if (e.key === 'Escape') {
                    dropdown.classList.add('hidden');
                }
            });
            
            document.addEventListener('click', (e) => {
                if (!container.contains(e.target)) {
                    dropdown.classList.add('hidden');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const managerSelect = document.getElementById('managerSelect');
            if (managerSelect) {
                initSearchableSelect(managerSelect);
            }
        });
    </script>
@endpush


