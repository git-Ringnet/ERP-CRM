{{-- Smart File Preview Modal --}}
<div id="filePreviewModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-[9999] flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-6xl max-h-[95vh] flex flex-col animate-modal-up">
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50 rounded-t-2xl">
            <div class="flex items-center space-x-3 overflow-hidden">
                <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 flex-shrink-0">
                    <i class="fas fa-file-alt text-xl" id="previewIcon"></i>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base font-bold text-gray-900 leading-tight truncate" id="previewTitle">Xem trước tài liệu</h3>
                    <p class="text-[10px] text-gray-500 mt-0.5 uppercase tracking-wider font-semibold" id="previewSubtitle">Trình xem tài liệu hệ thống</p>
                </div>
            </div>
            <div class="flex items-center space-x-2 ml-4">
                <div id="previewLoading" class="hidden flex items-center mr-4 text-indigo-600">
                    <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-xs font-semibold">Đang nạp...</span>
                </div>
                <a href="#" id="previewDownloadBtn" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-all shadow-sm">
                    <i class="fas fa-download mr-2 text-indigo-500"></i> Tải về
                </a>
                <button onclick="closeFilePreviewModal()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-all">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-auto bg-gray-100/50 p-4 sm:p-6 flex items-start justify-center min-h-[400px]" id="previewBody">
            {{-- Image Preview --}}
            <img id="previewImage" src="" class="hidden max-w-full max-h-full object-contain shadow-2xl rounded-lg border-4 border-white" />
            
            {{-- PDF Preview --}}
            <iframe id="previewPdf" src="" class="hidden w-full h-full rounded-lg border border-gray-200 bg-white shadow-sm" frameborder="0" style="min-height: calc(95vh - 120px);"></iframe>

            {{-- HTML Preview (Excel/Word) --}}
            <div id="previewHtml" class="hidden w-full bg-white rounded-xl shadow-lg border border-gray-200 p-8 prose prose-indigo max-w-none">
            </div>

            {{-- Unsupported Placeholder --}}
            <div id="previewPlaceholder" class="hidden text-center max-w-md mx-auto py-12 px-8 bg-white rounded-3xl shadow-xl border border-gray-100 mt-20">
                <div class="w-24 h-24 bg-yellow-50 rounded-3xl flex items-center justify-center mx-auto mb-6 transform rotate-3">
                    <i class="fas fa-file-alt text-yellow-500 text-5xl" id="placeholderIcon"></i>
                </div>
                <h4 class="text-xl font-bold text-gray-900 mb-2">Định dạng không hỗ trợ xem trực tiếp</h4>
                <p class="text-sm text-gray-500 mb-8 leading-relaxed">Loại file này (.<span id="placeholderExt" class="font-bold text-gray-700"></span>) cần được tải về máy để xem nội dung.</p>
                <a href="#" id="previewSecondaryDownloadBtn" class="inline-flex items-center px-10 py-3.5 bg-indigo-600 text-white rounded-2xl font-bold hover:bg-indigo-700 transition-all shadow-lg hover:shadow-indigo-200 active:scale-95">
                    <i class="fas fa-download mr-2"></i> Tải về ngay
                </a>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes modal-up {
    from { opacity: 0; transform: translateY(30px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.animate-modal-up {
    animation: modal-up 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
#previewHtml table {
    width: 100% !important;
    border-collapse: collapse !important;
    font-size: 13px !important;
}
#previewHtml th, #previewHtml td {
    border: 1px solid #e2e8f0 !important;
    padding: 8px !important;
    text-align: left !important;
}
#previewHtml tr:nth-child(even) {
    background-color: #f8fafc !important;
}
</style>

{{-- Libraries for Excel and Word --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js"></script>

<script>
function openFilePreviewModal(url, fileName) {
    const modal = document.getElementById('filePreviewModal');
    const title = document.getElementById('previewTitle');
    const downloadBtn = document.getElementById('previewDownloadBtn');
    const secondaryDownloadBtn = document.getElementById('previewSecondaryDownloadBtn');
    
    const img = document.getElementById('previewImage');
    const pdf = document.getElementById('previewPdf');
    const htmlDiv = document.getElementById('previewHtml');
    const placeholder = document.getElementById('previewPlaceholder');
    const previewLoading = document.getElementById('previewLoading');
    
    const placeholderExt = document.getElementById('placeholderExt');
    const placeholderIcon = document.getElementById('placeholderIcon');
    const previewIcon = document.getElementById('previewIcon');

    // Reset visibility
    img.classList.add('hidden');
    pdf.classList.add('hidden');
    htmlDiv.classList.add('hidden');
    placeholder.classList.add('hidden');
    previewLoading.classList.add('hidden');
    
    // Set basic info
    title.innerText = fileName;
    downloadBtn.href = url;
    secondaryDownloadBtn.href = url;
    downloadBtn.setAttribute('download', fileName);
    secondaryDownloadBtn.setAttribute('download', fileName);

    const ext = fileName.split('.').pop().toLowerCase();
    placeholderExt.innerText = ext;

    const isImage = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext);
    const isPdf = (ext === 'pdf');
    const isExcel = ['xlsx', 'xls', 'csv'].includes(ext);
    const isWord = ['docx'].includes(ext);

    if (isImage) {
        img.src = url;
        img.classList.remove('hidden');
        previewIcon.className = 'fas fa-image text-xl';
    } else if (isPdf) {
        setTimeout(() => {
            pdf.src = url + '#toolbar=0';
            pdf.classList.remove('hidden');
        }, 50);
        previewIcon.className = 'fas fa-file-pdf text-xl';
    } else if (isExcel) {
        previewIcon.className = 'fas fa-file-excel text-xl';
        renderExcel(url);
    } else if (isWord) {
        previewIcon.className = 'fas fa-file-word text-xl';
        renderWord(url);
    } else {
        placeholder.classList.remove('hidden');
        placeholderIcon.className = 'fas fa-file-alt text-gray-500 text-5xl';
        previewIcon.className = 'fas fa-file-alt text-xl';
    }

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');

    function showLoading() { previewLoading.classList.remove('hidden'); }
    function hideLoading() { previewLoading.classList.add('hidden'); }

    async function renderExcel(fileUrl) {
        showLoading();
        try {
            const response = await fetch(fileUrl);
            const data = await response.arrayBuffer();
            const workbook = XLSX.read(data, { type: 'array' });
            
            // Get first sheet
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            
            // Convert to HTML
            const html = XLSX.utils.sheet_to_html(worksheet);
            
            htmlDiv.innerHTML = `<div class="mb-4 flex items-center justify-between border-b pb-2">
                <span class="text-xs font-bold text-gray-500 uppercase">Sheet: ${firstSheetName}</span>
                <span class="text-[10px] text-gray-400 font-medium">Bản xem trước dữ liệu thô</span>
            </div>` + html;
            
            htmlDiv.classList.remove('hidden');
        } catch (error) {
            console.error('Excel preview failed:', error);
            placeholder.classList.remove('hidden');
        }
        hideLoading();
    }

    async function renderWord(fileUrl) {
        showLoading();
        try {
            const response = await fetch(fileUrl);
            const data = await response.arrayBuffer();
            
            const result = await mammoth.convertToHtml({ arrayBuffer: data });
            
            htmlDiv.innerHTML = `<div class="mb-6 flex items-center justify-between border-b pb-4">
                <span class="text-xs font-bold text-gray-500 uppercase">Văn bản tài liệu</span>
                <span class="text-[10px] text-gray-400 font-medium">Bản xem trước nội dung</span>
            </div>` + (result.value || '<p class="text-gray-400 italic">Tài liệu trống</p>');
            
            htmlDiv.classList.remove('hidden');
        } catch (error) {
            console.error('Word preview failed:', error);
            placeholder.classList.remove('hidden');
        }
        hideLoading();
    }
}

function closeFilePreviewModal() {
    const modal = document.getElementById('filePreviewModal');
    const pdf = document.getElementById('previewPdf');
    const img = document.getElementById('previewImage');
    const htmlDiv = document.getElementById('previewHtml');
    
    pdf.src = '';
    img.src = '';
    htmlDiv.innerHTML = '';
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeFilePreviewModal();
});

document.getElementById('filePreviewModal').addEventListener('click', function(e) {
    if (e.target === this) closeFilePreviewModal();
});
</script>
