@extends('layouts.app')

@section('title', 'Import Báo giá NCC')
@section('page-title', 'Import Báo giá từ Excel')

@section('content')
    <div class="w-full">
        <div class="bg-white rounded-lg shadow-sm">
            <!-- Header -->
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Import Báo giá nhà cung cấp</h2>
                    <p class="text-sm text-gray-500">Tạo báo giá mới từ file Excel của nhà cung cấp</p>
                </div>
                <a href="{{ route('supplier-quotations.index') }}" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </a>
            </div>

            <!-- Steps Indicator -->
            <div class="px-4 py-3 bg-gray-50 border-b">
                <div class="flex items-center justify-center gap-4">
                    <div class="step-item active" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-text">Thông tin & File</span>
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

            <!-- Step 1: Upload File & Info -->
            <div id="step1" class="step-content p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nhà cung cấp <span
                                class="text-red-500">*</span></label>
                        <select id="supplier_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">Chọn nhà cung cấp...</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" data-type="{{ strtolower($supplier->name) }}">
                                    {{ $supplier->code }} - {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Liên kết Yêu cầu báo giá</label>
                        <select id="purchase_request_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <option value="">-- Không liên kết --</option>
                            @foreach($purchaseRequests as $request)
                                <option value="{{ $request->id }}">{{ $request->code }} - {{ $request->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mã báo giá <span
                                class="text-red-500">*</span></label>
                        <input type="text" id="code" value="{{ $code }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ngày báo giá <span
                                class="text-red-500">*</span></label>
                        <input type="date" id="quotation_date" value="{{ now()->format('Y-m-d') }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hiệu lực đến <span
                                class="text-red-500">*</span></label>
                        <input type="date" id="valid_until" value="{{ now()->addDays(30)->format('Y-m-d') }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Chiết khấu (%)</label>
                        <input type="number" id="discount_percent" value="0" min="0" max="100"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">VAT (%)</label>
                        <input type="number" id="vat_percent" value="10" min="0" max="100"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phí vận chuyển</label>
                        <input type="number" id="shipping_cost" value="0" min="0"
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

            <!-- Step 2: Select Sheet -->
            <div id="step2" class="step-content p-6 hidden">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded mb-4">
                    <p class="text-blue-700 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        File có <strong id="sheet_count">0</strong> sheet. Lưu ý: Mỗi lần import chỉ nên chọn 1 sheet chứa
                        nội dung báo giá chính xác nhất.
                    </p>
                </div>

                <div class="border rounded-lg overflow-hidden">
                    <div class="bg-gray-50 px-4 py-2 border-b">
                        <span class="font-medium">Danh sách Sheet</span>
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
                        <label class="text-sm font-medium text-gray-700">Đang map sheet:</label>
                        <span id="current_sheet_name" class="font-bold text-primary"></span>
                    </div>
                    <div>
                        <button type="button" onclick="autoDetectMapping()"
                            class="px-3 py-2 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 text-sm">
                            <i class="fas fa-magic mr-1"></i>Tự động nhận diện
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dòng tiêu đề (Header)</label>
                        <input type="number" id="header_row" value="1" min="1" max="50"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bắt đầu dữ liệu từ dòng</label>
                        <input type="number" id="data_start_row" value="2" min="2"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2" readonly>
                    </div>
                </div>

                <!-- Mapping Fields -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h4 class="font-medium text-gray-900 mb-3">Ghép cột Excel với Dữ liệu báo giá</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Mã sản phẩm / SKU <span
                                    class="text-red-500">*</span></label>
                            <select id="map_sku"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                                <option value="">-- Chọn cột --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Tên sản phẩm</label>
                            <select id="map_product_name"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                                <option value="">-- Chọn cột --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Số lượng <span
                                    class="text-red-500">*</span></label>
                            <select id="map_quantity"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                                <option value="">-- Chọn cột --</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Nếu không có cột số lượng, hệ thống sẽ mặc định là 1</p>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Đơn giá <span
                                    class="text-red-500">*</span></label>
                            <select id="map_unit_price"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                                <option value="">-- Chọn cột --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Đơn vị tính</label>
                            <select id="map_unit"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                                <option value="">-- Chọn cột --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Mô tả / Quy cách</label>
                            <select id="map_description"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                                <option value="">-- Chọn cột --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Ghi chú SP</label>
                            <select id="map_note"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                                <option value="">-- Chọn cột --</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Preview Table -->
                <div class="mt-6">
                    <h4 class="font-medium text-gray-900 mb-3">Xem trước dữ liệu (10 dòng đầu)</h4>
                    <div class="border rounded-lg overflow-x-auto">
                        <table id="preview_table" class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left">SKU</th>
                                    <th class="px-3 py-2 text-left">Tên SP</th>
                                    <th class="px-3 py-2 text-center">SL</th>
                                    <th class="px-3 py-2 text-right">Đơn giá</th>
                                    <th class="px-3 py-2 text-right">Thành tiền</th>
                                    <th class="px-3 py-2 text-left">ĐVT</th>
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
                    <h4 class="font-medium text-gray-900 mb-4"><i class="fas fa-clipboard-check mr-2"></i>Xác nhận Import
                    </h4>

                    <div class="bg-white rounded-lg border p-4 mb-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-gray-500 text-sm">Nhà cung cấp:</span>
                                <div id="confirm_supplier" class="font-medium"></div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">Mã báo giá:</span>
                                <div id="confirm_code" class="font-medium"></div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">Yêu cầu liên kết:</span>
                                <div id="confirm_request" class="font-medium"></div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-sm">Sheet nguồn:</span>
                                <div id="confirm_sheet" class="font-medium"></div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-lg flex items-center">
                        <i class="fas fa-info-circle text-blue-500 text-xl mr-3"></i>
                        <div>
                            <p class="font-medium text-blue-800">Sẵn sàng import</p>
                            <p class="text-sm text-blue-600">Hệ thống sẽ tạo báo giá mới với các sản phẩm đã cấu hình.</p>
                        </div>
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
            selectedSheets: [], // Changed from selectedSheet (int) to selectedSheets (array)
            mappingSheetIndex: null, // The sheet used for mapping configuration (usually the first selected)
            headerRow: 1,
            headers: [],
            preview: [],
            mapping: {}
        };

        function showLoading(text) {
            document.getElementById('loading_text').textContent = text;
            document.getElementById('loading_overlay').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loading_overlay').classList.add('hidden');
        }

        function goToStep(step) {
            document.querySelectorAll('.step-content').forEach(el => el.classList.add('hidden'));
            document.getElementById('step' + step).classList.remove('hidden');

            document.querySelectorAll('.step-item').forEach((el, idx) => {
                el.classList.remove('active', 'completed');
                if (idx + 1 < step) el.classList.add('completed');
                if (idx + 1 === step) el.classList.add('active');
            });

            if (step === 3 && importState.mappingSheetIndex !== null) {
                loadSheetData();
            }
            if (step === 4) {
                updateConfirmInfo();
            }
        }

        // Step 1: Upload
        document.getElementById('excel_file').addEventListener('change', function (e) {
            if (this.files.length > 0) uploadFile(this.files[0]);
        });

        // Drag & Drop
        const dropzone = document.getElementById('dropzone');
        dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('border-primary', 'bg-blue-50'); });
        dropzone.addEventListener('dragleave', e => { e.preventDefault(); dropzone.classList.remove('border-primary', 'bg-blue-50'); });
        dropzone.addEventListener('drop', e => {
            e.preventDefault();
            dropzone.classList.remove('border-primary', 'bg-blue-50');
            if (e.dataTransfer.files.length > 0) uploadFile(e.dataTransfer.files[0]);
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

            fetch('{{ route("supplier-quotations.analyze") }}', {
                method: 'POST', body: formData
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

                        populateSheetList(data.sheets);

                        // --- AUTO SELECT BUT DO NOT SKIP ---
                        if (data.suggestedSheetIndex !== undefined) {
                            const suggestedSheet = data.sheets.find(s => s.index == data.suggestedSheetIndex);
                            if (suggestedSheet) {
                                toggleSheet(suggestedSheet.index);
                            }
                        }
                        
                        goToStep(2);
                    } else {
                        alert(data.message || 'Lỗi phân tích file');
                    }
                })
                .catch(err => {
                    hideLoading();
                    alert('Lỗi upload: ' + err.message);
                });
        }

        function populateSheetList(sheets) {
            const list = document.getElementById('sheet_list');
            list.innerHTML = sheets.map(sheet => `
                    <div class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 border-b last:border-0 cursor-pointer" onclick="toggleSheet(${sheet.index})">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" value="${sheet.index}" class="sheet-checkbox w-5 h-5 text-blue-600" 
                                ${importState.selectedSheets.includes(sheet.index) ? 'checked' : ''}>
                            <span class="font-medium">${sheet.name}</span>
                        </div>
                        <span class="text-sm text-gray-500">${sheet.rowCount} dòng</span>
                    </div>
                `).join('');
        }

        window.toggleSheet = function (index) {
            const idx = importState.selectedSheets.indexOf(index);
            if (idx === -1) {
                importState.selectedSheets.push(index);
            } else {
                importState.selectedSheets.splice(idx, 1);
            }
            
            // Checkbox UI update
            document.querySelectorAll(`.sheet-checkbox[value="${index}"]`).forEach(cb => cb.checked = (idx === -1));

            // Enable Next button if at least one sheet selected
            document.getElementById('btn_to_step3').disabled = importState.selectedSheets.length === 0;

            // Pick the first selected sheet as the "Mapping Sheet"
            if (importState.selectedSheets.length > 0) {
                 importState.mappingSheetIndex = importState.selectedSheets[0];
                 const sheetObj = importState.sheets.find(s => s.index === importState.mappingSheetIndex);
                 if (sheetObj) {
                     document.getElementById('current_sheet_name').innerHTML = 
                        (importState.selectedSheets.length > 1) 
                        ? `${sheetObj.name} <span class="text-gray-400 text-sm">(+${importState.selectedSheets.length - 1} sheet khác)</span>` 
                        : sheetObj.name;
                 }
            } else {
                importState.mappingSheetIndex = null;
                document.getElementById('current_sheet_name').textContent = '';
            }
        };

        // Step 3: Mapping
        document.getElementById('btn_to_step3').addEventListener('click', () => goToStep(3));
        document.getElementById('header_row').addEventListener('change', function () {
            importState.headerRow = parseInt(this.value);
            document.getElementById('data_start_row').value = importState.headerRow + 1;
            loadSheetData();
        });

        function loadSheetData() {
            showLoading('Đang đọc dữ liệu sheet...');
            fetch('{{ route("supplier-quotations.sheet-data") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({
                    temp_path: importState.tempPath,
                    sheet_index: importState.mappingSheetIndex, // Use the mapping sheet
                    header_row: importState.headerRow,
                    auto_detect_header: true // Request backend to find best header row
                })
            })
                .then(res => res.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        importState.headers = data.headers;
                        importState.preview = data.preview;

                        // Update Header Row UI if detected differently
                        if (data.detectedHeaderRow && data.detectedHeaderRow != importState.headerRow) {
                            importState.headerRow = data.detectedHeaderRow;
                            document.getElementById('header_row').value = data.detectedHeaderRow;
                            document.getElementById('data_start_row').value = data.detectedHeaderRow + 1;
                            // Note: we don't need to reload data because backend already returned data for the detected row
                        }

                        populateColumnSelects(data.headers);
                        autoDetectMapping(); // Auto detect on load
                        updatePreviewTable();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(err => {
                    hideLoading();
                    console.error(err);
                });
        }

        function populateColumnSelects(headers) {
            const options = '<option value="">-- Chọn cột --</option>' + headers.map(h => `<option value="${h.index}">${h.column}: ${h.name || '(Trống)'}</option>`).join('');
            ['map_sku', 'map_product_name', 'map_quantity', 'map_unit_price', 'map_unit', 'map_description', 'map_note'].forEach(id => {
                const currentVal = document.getElementById(id).value;
                document.getElementById(id).innerHTML = options;
                if (currentVal) document.getElementById(id).value = currentVal;
            });
        }

        // Mapping Auto Detect
        window.autoDetectMapping = function () {
            const headers = importState.headers;
            const supplierName = document.getElementById('supplier_id').selectedOptions[0]?.text?.toLowerCase() || '';
            const supplierType = supplierName.includes('fortinet') ? 'fortinet' : supplierName.includes('cisco') ? 'cisco' : 'default';

            fetch('{{ route("supplier-quotations.auto-detect") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ headers: headers, supplier_type: supplierType })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.mapping) {
                        applyMapping(data.mapping);
                        updatePreviewTable();
                    }
                });
        };

        function applyMapping(mapping) {
            if (mapping.sku !== undefined) document.getElementById('map_sku').value = mapping.sku;
            if (mapping.product_name !== undefined) document.getElementById('map_product_name').value = mapping.product_name;
            if (mapping.quantity !== undefined) document.getElementById('map_quantity').value = mapping.quantity;
            if (mapping.unit_price !== undefined) document.getElementById('map_unit_price').value = mapping.unit_price;
            if (mapping.unit !== undefined) document.getElementById('map_unit').value = mapping.unit;
            if (mapping.description !== undefined) document.getElementById('map_description').value = mapping.description;
        }

        // Update Mapping state change
        document.querySelectorAll('[id^="map_"]').forEach(sel => {
            sel.addEventListener('change', updatePreviewTable);
        });

        function updatePreviewTable() {
            const mapping = {
                sku: document.getElementById('map_sku').value,
                product_name: document.getElementById('map_product_name').value,
                quantity: document.getElementById('map_quantity').value,
                unit_price: document.getElementById('map_unit_price').value,
                unit: document.getElementById('map_unit').value,
                // ...
            };

            const tbody = document.getElementById('preview_tbody');
            tbody.innerHTML = '';

            importState.preview.forEach(row => {
                const sku = mapping.sku ? row[mapping.sku] : '';
                const name = mapping.product_name ? row[mapping.product_name] : '';
                const qty = mapping.quantity ? row[mapping.quantity] : 1;
                const price = mapping.unit_price ? row[mapping.unit_price] : 0;
                const unit = mapping.unit ? row[mapping.unit] : '';

                // Basic format
                const priceVal = parseFloat(String(price).replace(/[^0-9.-]/g, '')) || 0;
                const qtyVal = parseFloat(String(qty).replace(/[^0-9.-]/g, '')) || 1;
                const total = priceVal * qtyVal;

                if (!sku && !name && price <= 0) return; // Skip only if completely empty

                tbody.innerHTML += `
                        <tr class="hover:bg-gray-50 border-b">
                            <td class="px-3 py-2 text-primary font-medium">${sku || '-'}</td>
                            <td class="px-3 py-2 truncate max-w-xs">${name || '-'}</td>
                            <td class="px-3 py-2 text-center text-gray-600">${qtyVal}</td>
                            <td class="px-3 py-2 text-right">${priceVal.toLocaleString()}</td>
                            <td class="px-3 py-2 text-right font-medium">${total.toLocaleString()}</td>
                            <td class="px-3 py-2 text-left">${unit || ''}</td>
                        </tr>
                    `;
            });
        }

        document.getElementById('btn_to_step4').addEventListener('click', () => {
            if (!document.getElementById('map_sku').value && !document.getElementById('map_product_name').value) {
                alert('Vui lòng chọn ít nhất cột Mã sản phẩm hoặc Tên sản phẩm');
                return;
            }
            if (!document.getElementById('map_unit_price').value) {
                alert('Vui lòng chọn cột Đơn giá');
                return;
            }
            goToStep(4);
        });

        function updateConfirmInfo() {
            document.getElementById('confirm_supplier').textContent = document.getElementById('supplier_id').selectedOptions[0]?.text;
            document.getElementById('confirm_code').textContent = document.getElementById('code').value;
            document.getElementById('confirm_request').textContent = document.getElementById('purchase_request_id').selectedOptions[0]?.text;
            
            const sheetCount = importState.selectedSheets.length;
            const sheetNames = importState.sheets.filter(s => importState.selectedSheets.includes(s.index)).map(s => s.name).join(', ');
            document.getElementById('confirm_sheet').textContent = sheetCount > 1 
                ? `${sheetCount} sheets (${sheetNames})` 
                : importState.sheetName; // Fallback or first name
        }

        window.executeImport = function () {
            const finalMapping = {
                sku: document.getElementById('map_sku').value,
                product_name: document.getElementById('map_product_name').value,
                quantity: document.getElementById('map_quantity').value,
                unit_price: document.getElementById('map_unit_price').value,
                unit: document.getElementById('map_unit').value,
                description: document.getElementById('map_description').value,
                note: document.getElementById('map_note').value
            };

            const payload = {
                supplier_id: document.getElementById('supplier_id').value,
                code: document.getElementById('code').value,
                purchase_request_id: document.getElementById('purchase_request_id').value,
                quotation_date: document.getElementById('quotation_date').value,
                valid_until: document.getElementById('valid_until').value,
                discount_percent: document.getElementById('discount_percent').value,
                vat_percent: document.getElementById('vat_percent').value,
                shipping_cost: document.getElementById('shipping_cost').value,

                temp_path: importState.tempPath,
                sheet_indices: importState.selectedSheets, // Send Array array of indices
                header_row: importState.headerRow,
                mapping: finalMapping
            };

            if (!payload.supplier_id) { alert('Vui lòng chọn Nhà cung cấp'); return; }

            showLoading('Đang thực hiện import...');
            fetch('{{ route("supplier-quotations.do-import") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify(payload)
            })
                .then(res => res.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        alert('Import thành công!');
                        window.location.href = data.redirect;
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(err => {
                    hideLoading();
                    alert('Lỗi hệ thống: ' + err.message);
                });
        };
    </script>
@endpush