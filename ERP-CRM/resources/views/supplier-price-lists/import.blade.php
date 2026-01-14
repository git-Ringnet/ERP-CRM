@extends('layouts.app')

@section('title', 'Import bảng giá')
@section('page-title', 'Import bảng giá từ Excel')

@section('content')
    <div class="w-full">
        <div class="bg-white rounded-lg shadow-sm">
            <!-- Header -->
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Import bảng giá nhà cung cấp</h2>
                    <p class="text-sm text-gray-500">Hỗ trợ file Excel từ Fortinet, Cisco, HP... với nhiều sheet</p>
                </div>
                <a href="{{ route('supplier-price-lists.index') }}" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </a>
            </div>

            <!-- Steps Indicator -->
            <div class="px-4 py-3 bg-gray-50 border-b">
                <div class="flex items-center justify-center gap-4">
                    <div class="step-item active" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-text">Chọn file</span>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-item" data-step="2">
                        <span class="step-number">2</span>
                        <span class="step-text">Chọn Sheet</span>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-item" data-step="3">
                        <span class="step-number">3</span>
                        <span class="step-text">Mapping cột</span>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-item" data-step="4">
                        <span class="step-number">4</span>
                        <span class="step-text">Xác nhận</span>
                    </div>
                </div>
            </div>

            <!-- Step 1: Upload File -->
            <div id="step1" class="step-content p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nhà cung cấp <span
                                class="text-red-500">*</span></label>
                        <select id="supplier_id"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Chọn nhà cung cấp...</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" data-type="{{ strtolower($supplier->name) }}">
                                    {{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Loại giá</label>
                        <select id="price_type" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="list">Giá niêm yết (List Price)</option>
                            <option value="partner">Giá đối tác (Partner Price)</option>
                            <option value="cost">Giá gốc (Cost)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tiền tệ</label>
                        <select id="currency" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="USD">USD</option>
                            <option value="VND">VND</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tỷ giá quy đổi VND</label>
                        <input type="number" id="exchange_rate" value="25000" min="1"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Tên bảng giá</label>
                        <input type="text" id="price_list_name" placeholder="VD: Fortinet APAC 2025"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">File Excel <span
                            class="text-red-500">*</span></label>
                    <div id="dropzone"
                        class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-primary transition-colors cursor-pointer">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-3"></i>
                        <p class="text-gray-600">Kéo thả file Excel vào đây hoặc</p>
                        <input type="file" id="excel_file" accept=".xlsx,.xls" class="hidden">
                        <button type="button" onclick="document.getElementById('excel_file').click()"
                            class="mt-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                            <i class="fas fa-folder-open mr-2"></i>Chọn file
                        </button>
                        <p id="file_info" class="mt-3 text-sm text-gray-500"></p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" id="btn_to_step2" disabled
                        class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark disabled:opacity-50 disabled:cursor-not-allowed">
                        Tiếp theo <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>

            <!-- Step 2: Select Sheets -->
            <div id="step2" class="step-content p-6 hidden">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded mb-4">
                    <p class="text-blue-700 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        File có <strong id="sheet_count">0</strong> sheet. Chọn các sheet chứa dữ liệu sản phẩm cần import.
                    </p>
                </div>

                <div class="border rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="select_all_sheets" class="rounded">
                            <span class="font-medium">Chọn tất cả</span>
                        </label>
                        <span id="selected_sheet_count" class="text-sm text-gray-500">0 sheet được chọn</span>
                    </div>
                    <div id="sheet_list" class="max-h-96 overflow-y-auto divide-y">
                        <!-- Sheets will be populated here -->
                    </div>
                </div>

                <div class="mt-6 flex justify-between">
                    <button type="button" onclick="goToStep(1)"
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-arrow-left mr-2"></i>Quay lại
                    </button>
                    <button type="button" id="btn_to_step3" disabled
                        class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark disabled:opacity-50">
                        Tiếp theo <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>

            <!-- Step 3: Column Mapping -->
            <div id="step3" class="step-content p-6 hidden">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <label class="text-sm font-medium text-gray-700">Sheet:</label>
                        <select id="current_sheet" class="border border-gray-300 rounded-lg px-3 py-2"></select>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" onclick="autoDetectAllSheets()"
                            class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                            <i class="fas fa-magic mr-1"></i>Tự động tất cả sheet
                        </button>
                        <button type="button" onclick="autoDetectMapping()"
                            class="px-3 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 text-sm">
                            <i class="fas fa-magic mr-1"></i>Tự động nhận diện
                        </button>
                        <button type="button" onclick="applyMappingToAll()"
                            class="px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm">
                            <i class="fas fa-copy mr-1"></i>Áp dụng cho tất cả
                        </button>
                    </div>
                </div>

                <!-- Quick Apply Info -->
                <div id="quick_apply_info" class="hidden bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                    <p class="text-green-700 text-sm">
                        <i class="fas fa-check-circle mr-2"></i>
                        Đã áp dụng mapping tự động cho <strong id="auto_mapped_count">0</strong> sheet. Bạn có thể chỉnh sửa
                        từng sheet nếu cần.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dòng tiêu đề (Header)</label>
                        <input type="number" id="header_row" value="1" min="1" max="20"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bắt đầu dữ liệu từ dòng</label>
                        <input type="number" id="data_start_row" value="2" min="2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2" readonly>
                    </div>
                </div>

                <!-- Mapping Fields -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3">Thông tin cơ bản</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">SKU / Mã SP <span
                                        class="text-red-500">*</span></label>
                                <select id="map_sku" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">-- Chọn cột --</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Tên sản phẩm</label>
                                <select id="map_product_name"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">-- Chọn cột --</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Mô tả</label>
                                <select id="map_description"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">-- Chọn cột --</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Giá (Price) <span
                                        class="text-red-500">*</span></label>
                                <select id="map_price" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">-- Chọn cột --</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Danh mục</label>
                                <select id="map_category"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">-- Chọn cột --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="bg-orange-50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3"><i
                                class="fas fa-file-contract text-orange-600 mr-2"></i>Giá theo thời hạn hợp đồng</h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Giá 1 năm</label>
                                <select id="map_price_1yr"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">-- Chọn cột --</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Giá 2 năm</label>
                                <select id="map_price_2yr"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">-- Chọn cột --</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Giá 3 năm</label>
                                <select id="map_price_3yr"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">-- Chọn cột --</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Giá 4 năm</label>
                                <select id="map_price_4yr"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">-- Chọn cột --</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-600 mb-1">Giá 5 năm</label>
                                <select id="map_price_5yr"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">-- Chọn cột --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview Table -->
                <div class="mt-6">
                    <h4 class="font-medium text-gray-900 mb-3">Preview dữ liệu (10 dòng đầu)</h4>
                    <div class="border rounded-lg overflow-x-auto">
                        <table id="preview_table" class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left">SKU</th>
                                    <th class="px-3 py-2 text-left">Tên SP</th>
                                    <th class="px-3 py-2 text-left">Mô tả</th>
                                    <th class="px-3 py-2 text-right">Giá</th>
                                    <th class="px-3 py-2 text-left">Danh mục</th>
                                    <th class="px-3 py-2 text-right">1yr</th>
                                    <th class="px-3 py-2 text-right">3yr</th>
                                    <th class="px-3 py-2 text-right">5yr</th>
                                </tr>
                            </thead>
                            <tbody id="preview_tbody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-6 flex justify-between">
                    <button type="button" onclick="goToStep(2)"
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-arrow-left mr-2"></i>Quay lại
                    </button>
                    <button type="button" id="btn_to_step4"
                        class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark">
                        Tiếp theo <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </div>

            <!-- Step 4: Confirm & Import -->
            <div id="step4" class="step-content p-6 hidden">
                <div class="bg-gray-50 rounded-lg p-6">
                    <h4 class="font-medium text-gray-900 mb-4"><i class="fas fa-clipboard-check mr-2"></i>Tổng kết Import
                    </h4>

                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                        <div class="bg-white rounded-lg p-4 text-center border">
                            <i class="fas fa-file-excel text-green-600 text-2xl mb-2"></i>
                            <div class="text-xs text-gray-500">File</div>
                            <div id="summary_file" class="font-medium text-sm truncate">-</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 text-center border">
                            <i class="fas fa-layer-group text-blue-600 text-2xl mb-2"></i>
                            <div class="text-xs text-gray-500">Số sheet</div>
                            <div id="summary_sheets" class="font-medium text-lg">0</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 text-center border">
                            <i class="fas fa-boxes text-purple-600 text-2xl mb-2"></i>
                            <div class="text-xs text-gray-500">Tổng dòng</div>
                            <div id="summary_rows" class="font-medium text-lg">0</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 text-center border">
                            <i class="fas fa-building text-orange-600 text-2xl mb-2"></i>
                            <div class="text-xs text-gray-500">Nhà cung cấp</div>
                            <div id="summary_supplier" class="font-medium text-sm truncate">-</div>
                        </div>
                        <div class="bg-white rounded-lg p-4 text-center border">
                            <i class="fas fa-dollar-sign text-green-600 text-2xl mb-2"></i>
                            <div class="text-xs text-gray-500">Tiền tệ</div>
                            <div id="summary_currency" class="font-medium text-lg">USD</div>
                        </div>
                    </div>

                    <!-- Import Options -->
                    <div class="bg-white rounded-lg p-4 border mb-4">
                        <h5 class="font-medium text-gray-900 mb-3">Tùy chọn import</h5>
                        <div class="space-y-2">
                            <label class="flex items-start gap-3 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                <input type="radio" name="import_mode" value="create" checked class="mt-1">
                                <div>
                                    <span class="font-medium">Tạo mới</span>
                                    <p class="text-sm text-gray-500">Tạo bảng giá mới với tất cả sản phẩm</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-3 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                <input type="radio" name="import_mode" value="update" class="mt-1">
                                <div>
                                    <span class="font-medium">Cập nhật</span>
                                    <p class="text-sm text-gray-500">Cập nhật giá nếu SKU đã tồn tại, thêm mới nếu chưa có
                                    </p>
                                </div>
                            </label>
                            <label class="flex items-start gap-3 p-2 rounded hover:bg-gray-50 cursor-pointer">
                                <input type="radio" name="import_mode" value="replace" class="mt-1">
                                <div>
                                    <span class="font-medium">Thay thế</span>
                                    <p class="text-sm text-gray-500">Xóa toàn bộ sản phẩm cũ và import mới</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Sheet Details -->
                    <div class="bg-white rounded-lg p-4 border">
                        <h5 class="font-medium text-gray-900 mb-3">Chi tiết từng sheet</h5>
                        <div id="sheet_details" class="divide-y max-h-48 overflow-y-auto"></div>
                    </div>
                </div>

                <div class="mt-6 flex justify-between">
                    <button type="button" onclick="goToStep(3)"
                        class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-arrow-left mr-2"></i>Quay lại
                    </button>
                    <button type="button" id="btn_import" onclick="executeImport()"
                        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-upload mr-2"></i>Thực hiện Import
                    </button>
                </div>
            </div>

            <!-- Loading Overlay -->
            <div id="loading_overlay"
                class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-8 text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-primary mb-4"></i>
                    <h4 class="font-medium text-gray-900 mb-2">Đang xử lý...</h4>
                    <p id="loading_text" class="text-sm text-gray-500">Vui lòng đợi</p>
                    <div class="w-64 h-2 bg-gray-200 rounded-full mt-4">
                        <div id="progress_bar" class="h-full bg-primary rounded-full transition-all" style="width: 0%">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <style>
        .step-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: #f3f4f6;
            border-radius: 20px;
            color: #6b7280;
            font-size: 14px;
        }

        .step-item.active {
            background: #3b82f6;
            color: white;
        }

        .step-item.completed {
            background: #10b981;
            color: white;
        }

        .step-number {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 12px;
        }

        .step-line {
            width: 40px;
            height: 2px;
            background: #d1d5db;
        }

        .step-content.hidden {
            display: none;
        }
    </style>

@endsection

@push('scripts')
    <script>
        let importState = {
            tempPath: null,
            fileName: null,
            sheets: [],
            selectedSheets: [],
            sheetMappings: {},
            currentSheet: null,
            supplierType: 'default'
        };

        // Step navigation
        function goToStep(step) {
            document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
            document.getElementById('step' + step).classList.remove('hidden');

            document.querySelectorAll('.step-item').forEach((el, idx) => {
                el.classList.remove('active', 'completed');
                if (idx + 1 < step) el.classList.add('completed');
                if (idx + 1 === step) el.classList.add('active');
            });

            if (step === 3 && importState.selectedSheets.length > 0) {
                // Chỉ load sheet đầu tiên nếu chưa có mapping nào
                const firstSheet = importState.selectedSheets[0];
                if (!importState.sheetMappings[firstSheet.index]?.headers?.length) {
                    loadSheetForMapping(firstSheet.index);
                } else {
                    // Đã có mapping, chỉ cần hiển thị
                    document.getElementById('current_sheet').value = firstSheet.index;
                    importState.currentSheet = firstSheet.index;
                }
            }
            if (step === 4) {
                loadSummary();
            }
        }

        // File upload handling
        document.getElementById('excel_file').addEventListener('change', function (e) {
            if (this.files.length > 0) {
                uploadFile(this.files[0]);
            }
        });

        const dropzone = document.getElementById('dropzone');
        dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('border-primary', 'bg-blue-50'); });
        dropzone.addEventListener('dragleave', e => { e.preventDefault(); dropzone.classList.remove('border-primary', 'bg-blue-50'); });
        dropzone.addEventListener('drop', e => {
            e.preventDefault();
            dropzone.classList.remove('border-primary', 'bg-blue-50');
            if (e.dataTransfer.files.length > 0) {
                uploadFile(e.dataTransfer.files[0]);
            }
        });

        function uploadFile(file) {
            if (!file.name.match(/\.(xlsx|xls)$/i)) {
                alert('Vui lòng chọn file Excel (.xlsx hoặc .xls)');
                return;
            }

            showLoading('Đang phân tích file...');

            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("supplier-price-lists.analyze") }}', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        importState.tempPath = data.tempPath;
                        importState.fileName = data.fileName;
                        importState.sheets = data.sheets;

                        document.getElementById('file_info').innerHTML = `<i class="fas fa-file-excel text-green-600"></i> <strong>${data.fileName}</strong> - ${data.sheets.length} sheets`;
                        document.getElementById('btn_to_step2').disabled = false;

                        // Auto-fill name
                        if (!document.getElementById('price_list_name').value) {
                            document.getElementById('price_list_name').value = data.fileName.replace(/\.(xlsx|xls)$/i, '');
                        }

                        populateSheetList(data.sheets);
                    } else {
                        alert(data.message || 'Lỗi phân tích file');
                    }
                })
                .catch(err => {
                    hideLoading();
                    alert('Lỗi upload file: ' + err.message);
                });
        }


        function populateSheetList(sheets) {
            const skipPatterns = ['cover', 'index', 'general info', 'changes', 'dataset', 'price list changes'];
            const list = document.getElementById('sheet_list');

            list.innerHTML = sheets.map((sheet, idx) => {
                const isSkipped = skipPatterns.some(p => sheet.name.toLowerCase().includes(p));
                return `
                <div class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 ${isSkipped ? 'bg-yellow-50' : ''}">
                    <label class="flex items-center gap-3 cursor-pointer flex-1">
                        <input type="checkbox" class="sheet-checkbox rounded" data-index="${sheet.index}" data-name="${sheet.name}" ${!isSkipped ? 'checked' : ''}>
                        <span class="font-medium">${sheet.name}</span>
                    </label>
                    <span class="text-sm text-gray-500">${sheet.rowCount} dòng</span>
                    ${isSkipped ? '<span class="text-xs bg-yellow-200 text-yellow-800 px-2 py-1 rounded">Bỏ qua</span>' : ''}
                </div>
            `;
            }).join('');

            document.getElementById('sheet_count').textContent = sheets.length;
            updateSelectedSheets();

            // Event listeners
            document.querySelectorAll('.sheet-checkbox').forEach(cb => {
                cb.addEventListener('change', updateSelectedSheets);
            });
            document.getElementById('select_all_sheets').addEventListener('change', function () {
                document.querySelectorAll('.sheet-checkbox').forEach(cb => cb.checked = this.checked);
                updateSelectedSheets();
            });
        }

        function updateSelectedSheets() {
            importState.selectedSheets = [];
            document.querySelectorAll('.sheet-checkbox:checked').forEach(cb => {
                importState.selectedSheets.push({
                    index: parseInt(cb.dataset.index),
                    name: cb.dataset.name
                });
            });
            document.getElementById('selected_sheet_count').textContent = importState.selectedSheets.length + ' sheet được chọn';
            document.getElementById('btn_to_step3').disabled = importState.selectedSheets.length === 0;

            // Populate sheet select in step 3
            const select = document.getElementById('current_sheet');
            select.innerHTML = importState.selectedSheets.map(s => `<option value="${s.index}">${s.name}</option>`).join('');
        }

        // Step 2 -> Step 3
        document.getElementById('btn_to_step2').addEventListener('click', () => {
            if (!document.getElementById('supplier_id').value) {
                alert('Vui lòng chọn nhà cung cấp');
                return;
            }
            goToStep(2);
        });

        document.getElementById('btn_to_step3').addEventListener('click', () => goToStep(3));
        document.getElementById('btn_to_step4').addEventListener('click', () => {
            saveCurrentMapping();
            goToStep(4);
        });

        // Load sheet for mapping
        function loadSheetForMapping(sheetIndex) {
            importState.currentSheet = sheetIndex;
            document.getElementById('current_sheet').value = sheetIndex;

            showLoading('Đang tải dữ liệu sheet...');

            fetch('{{ route("supplier-price-lists.sheet-data") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    temp_path: importState.tempPath,
                    sheet_index: sheetIndex,
                    header_row: parseInt(document.getElementById('header_row').value)
                })
            })
                .then(res => {
                    if (!res.ok) throw new Error('Server error');
                    return res.json();
                })
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        populateColumnSelects(data.headers);
                        importState.sheetMappings[sheetIndex] = { headers: data.headers, preview: data.preview, totalRows: data.totalRows };

                        // Restore mapping if exists
                        if (importState.sheetMappings[sheetIndex].mapping) {
                            restoreMapping(importState.sheetMappings[sheetIndex].mapping);
                        } else {
                            autoDetectMapping();
                        }

                        updatePreview();
                    } else {
                        alert(data.message || 'Lỗi tải dữ liệu sheet');
                    }
                })
                .catch(err => {
                    hideLoading();
                    console.error('Load sheet error:', err);
                    alert('Lỗi tải dữ liệu sheet: ' + err.message);
                });
        }

        document.getElementById('current_sheet').addEventListener('change', function () {
            saveCurrentMapping();
            loadSheetForMapping(parseInt(this.value));
        });

        document.getElementById('header_row').addEventListener('change', function () {
            document.getElementById('data_start_row').value = parseInt(this.value) + 1;
            loadSheetForMapping(importState.currentSheet);
        });


        function populateColumnSelects(headers) {
            const selects = ['map_sku', 'map_product_name', 'map_description', 'map_price', 'map_category', 'map_price_1yr', 'map_price_2yr', 'map_price_3yr', 'map_price_4yr', 'map_price_5yr'];
            const options = '<option value="">-- Chọn cột --</option>' + headers.map(h => `<option value="${h.index}">${h.column}: ${h.name || '(Trống)'}</option>`).join('');
            selects.forEach(id => document.getElementById(id).innerHTML = options);
        }

        function autoDetectMapping() {
            const headers = importState.sheetMappings[importState.currentSheet]?.headers || [];
            if (!headers || headers.length === 0) {
                console.log('No headers to detect');
                return;
            }

            const supplierName = document.getElementById('supplier_id').selectedOptions[0]?.text?.toLowerCase() || '';
            const supplierType = supplierName.includes('fortinet') ? 'fortinet' : supplierName.includes('cisco') ? 'cisco' : 'default';

            fetch('{{ route("supplier-price-lists.auto-detect") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ headers: headers, supplier_type: supplierType })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.mapping) {
                        restoreMapping(data.mapping);
                        updatePreview();
                    }
                })
                .catch(err => {
                    console.error('Auto detect error:', err);
                });
        }

        // Tự động mapping cho TẤT CẢ sheet mà không cần load từng sheet
        async function autoDetectAllSheets() {
            const supplierName = document.getElementById('supplier_id').selectedOptions[0]?.text?.toLowerCase() || '';
            const supplierType = supplierName.includes('fortinet') ? 'fortinet' : supplierName.includes('cisco') ? 'cisco' : 'default';

            // Preset mapping dựa trên loại NCC
            const presetMappings = {
                fortinet: {
                    headerRow: 4, // Fortinet thường có header ở dòng 4-8
                    patterns: {
                        sku: ['SKU', 'Part Number', 'Item'],
                        product_name: ['UNIT', 'Product', 'Identifier', 'Product Name'],
                        description: ['Description', 'Desc'],
                        price: ['Price', 'List Price', 'Unit Price', 'MSRP'],
                        category: ['Category', 'Type'],
                        price_1yr: ['1yr', '1 Year', 'Replaces DD by 12'],
                        price_2yr: ['2yr', '2 Year', 'Replaces DD by 24'],
                        price_3yr: ['3yr', '3 Year', 'Replaces DD by 36'],
                        price_4yr: ['4yr', '4 Year', 'Replaces DD by 48'],
                        price_5yr: ['5yr', '5 Year', 'Replaces DD by 60']
                    }
                },
                cisco: {
                    headerRow: 1,
                    patterns: {
                        sku: ['Part Number', 'SKU'],
                        product_name: ['Product Name', 'Product'],
                        description: ['Description'],
                        price: ['List Price', 'Price'],
                        category: ['Category', 'Product Family']
                    }
                },
                default: {
                    headerRow: 1,
                    patterns: {
                        sku: ['SKU', 'Part Number', 'Code', 'Mã'],
                        product_name: ['Product', 'Name', 'Tên'],
                        description: ['Description', 'Mô tả'],
                        price: ['Price', 'Giá'],
                        category: ['Category', 'Danh mục']
                    }
                }
            };

            const preset = presetMappings[supplierType] || presetMappings.default;

            showLoading('Đang áp dụng mapping tự động cho tất cả sheet...');

            let mappedCount = 0;

            // Áp dụng preset cho tất cả sheet đã chọn
            for (const sheet of importState.selectedSheets) {
                // Khởi tạo mapping cho sheet nếu chưa có
                if (!importState.sheetMappings[sheet.index]) {
                    importState.sheetMappings[sheet.index] = {
                        headers: [],
                        preview: [],
                        totalRows: 0
                    };
                }

                // Áp dụng preset mapping
                importState.sheetMappings[sheet.index].headerRow = preset.headerRow;
                importState.sheetMappings[sheet.index].mapping = {};
                importState.sheetMappings[sheet.index].usePreset = true;
                importState.sheetMappings[sheet.index].presetType = supplierType;

                mappedCount++;
            }

            hideLoading();

            // Hiển thị thông báo
            document.getElementById('quick_apply_info').classList.remove('hidden');
            document.getElementById('auto_mapped_count').textContent = mappedCount;

            // Cập nhật header row input
            document.getElementById('header_row').value = preset.headerRow;
            document.getElementById('data_start_row').value = preset.headerRow + 1;

            alert(`Đã áp dụng cấu hình ${supplierType.toUpperCase()} cho ${mappedCount} sheet!\n\nHeader row: ${preset.headerRow}\n\nKhi import, hệ thống sẽ tự động nhận diện cột dựa trên tên cột trong file Excel.`);
        }

        function restoreMapping(mapping) {
            Object.entries(mapping).forEach(([field, index]) => {
                const select = document.getElementById('map_' + field);
                if (select && index !== undefined) select.value = index;
            });
        }

        function getCurrentMapping() {
            return {
                sku: document.getElementById('map_sku').value,
                product_name: document.getElementById('map_product_name').value,
                description: document.getElementById('map_description').value,
                price: document.getElementById('map_price').value,
                category: document.getElementById('map_category').value,
                price_1yr: document.getElementById('map_price_1yr').value,
                price_2yr: document.getElementById('map_price_2yr').value,
                price_3yr: document.getElementById('map_price_3yr').value,
                price_4yr: document.getElementById('map_price_4yr').value,
                price_5yr: document.getElementById('map_price_5yr').value,
            };
        }

        function saveCurrentMapping() {
            if (importState.currentSheet !== null) {
                importState.sheetMappings[importState.currentSheet].mapping = getCurrentMapping();
                importState.sheetMappings[importState.currentSheet].headerRow = parseInt(document.getElementById('header_row').value);
            }
        }

        function applyMappingToAll() {
            saveCurrentMapping();
            const currentMapping = importState.sheetMappings[importState.currentSheet];
            importState.selectedSheets.forEach(sheet => {
                if (importState.sheetMappings[sheet.index]) {
                    importState.sheetMappings[sheet.index].mapping = { ...currentMapping.mapping };
                    importState.sheetMappings[sheet.index].headerRow = currentMapping.headerRow;
                }
            });
            alert('Đã áp dụng mapping cho ' + importState.selectedSheets.length + ' sheet!');
        }

        function updatePreview() {
            const mapping = getCurrentMapping();
            const preview = importState.sheetMappings[importState.currentSheet]?.preview || [];
            const tbody = document.getElementById('preview_tbody');

            tbody.innerHTML = preview.slice(0, 10).map(row => {
                const getValue = idx => idx !== '' ? (row[parseInt(idx)] || '-') : '-';
                const formatPrice = val => { const n = parseFloat(String(val).replace(/[^0-9.-]/g, '')); return isNaN(n) ? '-' : n.toLocaleString(); };
                return `<tr class="border-b">
                <td class="px-3 py-2">${getValue(mapping.sku)}</td>
                <td class="px-3 py-2 max-w-xs truncate">${getValue(mapping.product_name)}</td>
                <td class="px-3 py-2 max-w-xs truncate">${getValue(mapping.description)}</td>
                <td class="px-3 py-2 text-right">${formatPrice(getValue(mapping.price))}</td>
                <td class="px-3 py-2">${getValue(mapping.category)}</td>
                <td class="px-3 py-2 text-right">${formatPrice(getValue(mapping.price_1yr))}</td>
                <td class="px-3 py-2 text-right">${formatPrice(getValue(mapping.price_3yr))}</td>
                <td class="px-3 py-2 text-right">${formatPrice(getValue(mapping.price_5yr))}</td>
            </tr>`;
            }).join('');
        }

        // Update preview when mapping changes
        ['map_sku', 'map_product_name', 'map_description', 'map_price', 'map_category', 'map_price_1yr', 'map_price_2yr', 'map_price_3yr', 'map_price_4yr', 'map_price_5yr'].forEach(id => {
            document.getElementById(id).addEventListener('change', updatePreview);
        });


        function loadSummary() {
            const supplierSelect = document.getElementById('supplier_id');
            document.getElementById('summary_file').textContent = importState.fileName;
            document.getElementById('summary_sheets').textContent = importState.selectedSheets.length;
            document.getElementById('summary_supplier').textContent = supplierSelect.selectedOptions[0]?.text || '-';
            document.getElementById('summary_currency').textContent = document.getElementById('currency').value;

            let totalRows = 0;
            const details = importState.selectedSheets.map(sheet => {
                const data = importState.sheetMappings[sheet.index];
                // Nếu dùng preset, lấy rowCount từ sheets gốc
                const sheetInfo = importState.sheets.find(s => s.index === sheet.index);
                const rows = data?.totalRows || sheetInfo?.rowCount || 0;
                totalRows += rows;

                const mappingStatus = data?.usePreset ?
                    '<span class="text-green-600 text-xs"><i class="fas fa-magic"></i> Tự động</span>' :
                    (data?.mapping ? '<span class="text-blue-600 text-xs"><i class="fas fa-check"></i> Đã mapping</span>' :
                        '<span class="text-yellow-600 text-xs"><i class="fas fa-exclamation"></i> Chưa mapping</span>');

                return `<div class="flex justify-between py-2">
                <span>${sheet.name}</span>
                <span class="flex items-center gap-3">
                    ${mappingStatus}
                    <span class="text-gray-500">${rows} dòng</span>
                </span>
            </div>`;
            }).join('');

            document.getElementById('summary_rows').textContent = totalRows.toLocaleString();
            document.getElementById('sheet_details').innerHTML = details;
        }

        function executeImport() {
            saveCurrentMapping();

            const supplierId = document.getElementById('supplier_id').value;
            const name = document.getElementById('price_list_name').value;

            if (!supplierId || !name) {
                alert('Vui lòng điền đầy đủ thông tin');
                return;
            }

            const sheets = importState.selectedSheets.map(sheet => {
                const data = importState.sheetMappings[sheet.index];
                return {
                    index: sheet.index,
                    name: sheet.name,
                    header_row: data?.headerRow || 1,
                    mapping: data?.mapping || {}
                };
            });

            showLoading('Đang import dữ liệu...');

            fetch('{{ route("supplier-price-lists.do-import") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    supplier_id: supplierId,
                    name: name,
                    temp_path: importState.tempPath,
                    currency: document.getElementById('currency').value,
                    exchange_rate: parseFloat(document.getElementById('exchange_rate').value),
                    price_type: document.getElementById('price_type').value,
                    sheets: sheets,
                    import_mode: document.querySelector('input[name="import_mode"]:checked').value
                })
            })
                .then(res => {
                    if (!res.ok) {
                        return res.text().then(text => {
                            throw new Error(text.substring(0, 200));
                        });
                    }
                    return res.json();
                })
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        alert(`Import thành công!\n\nTổng: ${data.import_log.total_items} sản phẩm\nTạo mới: ${data.import_log.created}\nCập nhật: ${data.import_log.updated}\nBỏ qua: ${data.import_log.skipped}`);
                        window.location.href = '{{ route("supplier-price-lists.index") }}';
                    } else {
                        alert('Lỗi: ' + (data.message || 'Không xác định'));
                    }
                })
                .catch(err => {
                    hideLoading();
                    console.error('Import error:', err);
                    alert('Lỗi import: ' + err.message);
                });
        }

        function showLoading(text) {
            document.getElementById('loading_text').textContent = text;
            document.getElementById('loading_overlay').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loading_overlay').classList.add('hidden');
        }
    </script>
@endpush