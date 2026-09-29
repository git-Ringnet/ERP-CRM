@extends('layouts.app')

@section('title', 'Trung Tâm Trợ Giúp & Hướng Dẫn Sử Dụng Hệ Thống')
@section('page-title', 'Trung Tâm Hướng Dẫn Vận Hành ERP-CRM')

@push('styles')
    <style>
        .role-nav-btn.active {
            background-color: #1e40af;
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 4px 6px -1px rgba(30, 64, 175, 0.2);
        }

        .role-section {
            display: none;
        }

        .role-section.active {
            display: block;
            animation: fadeIn 0.25s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .highlight-match {
            background-color: #fef08a;
            color: #854d0e;
            padding: 1px 4px;
            border-radius: 3px;
            font-weight: bold;
        }

        .step-card:hover {
            border-color: #93c5fd;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.08);
        }
    </style>
@endpush

@section('content')
    <div class="px-4 sm:px-6 lg:px-8 py-6" x-data="userGuideHub()">

        <!-- Top Header & Banner -->
        <div
            class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-2xl shadow-xl p-6 sm:p-8 text-white mb-8 relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none">
                <i class="fas fa-book-open text-9xl text-white"></i>
            </div>

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6 relative z-10">
                <div>
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 bg-blue-800 bg-opacity-60 border border-blue-400 border-opacity-30 rounded-full text-xs font-semibold tracking-wide text-blue-200 uppercase mb-3">
                        <i class="fas fa-shield-alt text-cyan-400"></i> Standard Operating Procedure (SOP 4.9)
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                        Trung Tâm Trợ Giúp & Hướng Dẫn Vận Hành
                    </h1>
                    <p class="text-blue-100 text-sm sm:text-base mt-1 max-w-2xl">
                        Tra cứu quy trình chuẩn hóa, ma trận phân quyền và thao tác từng bước cho toàn bộ 12 vai trò trong
                        hệ thống ERP-CRM.
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center gap-3">
                    @if($fileExists)
                        <a href="{{ route('user-guide.download') }}"
                            class="inline-flex items-center px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold rounded-xl shadow-md transition-all hover:scale-105">
                            <i class="fas fa-file-word text-lg mr-2"></i>
                            <span>Tải file Word (.docx)</span>
                            <span
                                class="ml-2 text-xs bg-emerald-700 px-2 py-0.5 rounded-full text-emerald-100">{{ $fileSize }}</span>
                        </a>
                    @endif

                    <button @click="showUploadModal = true"
                        class="inline-flex items-center px-4 py-2.5 bg-white bg-opacity-15 hover:bg-opacity-25 border border-white border-opacity-30 text-white text-sm font-semibold rounded-xl transition-all">
                        <i class="fas fa-cloud-upload-alt text-lg mr-2 text-cyan-300"></i>
                        <span>Cập nhật tài liệu mới</span>
                    </button>
                </div>
            </div>

            <!-- Global Search Bar -->
            <div class="mt-6 pt-6 border-t border-white border-opacity-15">
                <div class="relative max-w-3xl">
                    <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 text-lg"></i>
                    <input type="text" x-model="searchQuery" @input="handleSearch()"
                        placeholder="Tìm kiếm nhanh từ khóa: 'BOM', 'P&L', 'ĐKDA', 'Duyệt PR', 'Nhập kho', 'UNC', 'Báo giá', 'Quà tặng'..."
                        class="w-full pl-12 pr-10 py-3 bg-white text-gray-900 rounded-xl shadow-inner placeholder-gray-400 text-sm sm:text-base focus:outline-none focus:ring-4 focus:ring-blue-400 focus:border-transparent font-medium">
                    <button x-show="searchQuery.length > 0" @click="searchQuery = ''; handleSearch()"
                        class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times-circle text-lg"></i>
                    </button>
                </div>

                <!-- Quick Tag Pills -->
                <div class="flex flex-wrap items-center gap-2 mt-3 text-xs text-blue-200">
                    <span class="font-semibold text-white mr-1">Từ khóa phổ biến:</span>
                    <template x-for="tag in quickTags" :key="tag">
                        <button @click="searchQuery = tag; handleSearch()"
                            class="px-2.5 py-1 bg-white bg-opacity-10 hover:bg-opacity-25 rounded-lg border border-white border-opacity-20 text-blue-100 hover:text-white transition-colors"
                            x-text="'#' + tag">
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Search Results Banner (When searching) -->
        <div x-show="searchQuery.trim().length > 0"
            class="mb-6 bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center justify-between">
            <div class="flex items-center text-sm text-blue-900">
                <i class="fas fa-filter text-blue-600 mr-2 text-base"></i>
                <span>Kết quả lọc theo từ khóa: <strong class="text-blue-700"
                        x-text="'&quot;' + searchQuery + '&quot;'"></strong> (<span x-text="matchedCount"></span> mục phù
                    hợp)</span>
            </div>
            <button @click="searchQuery = ''; handleSearch()" class="text-xs text-blue-600 hover:underline font-semibold">
                Xóa tìm kiếm
            </button>
        </div>

        <!-- Main Content Layout (Sidebar + Role Sections) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            <!-- Left Navigation Sidebar: 12 Roles -->
            <div class="lg:col-span-4 xl:col-span-3">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4 sticky top-6">
                    <div class="flex items-center justify-between pb-3 mb-3 border-b border-gray-100">
                        <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wider flex items-center">
                            <i class="fas fa-users-cog text-blue-600 mr-2"></i> Danh Mục Vai Trò
                        </h3>
                        <span class="text-xs font-semibold bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">12 Chức
                            Danh</span>
                    </div>

                    <nav class="space-y-1 max-h-[calc(100vh-220px)] overflow-y-auto pr-1">
                        <template x-for="role in rolesList" :key="role.id">
                            <button @click="activeRole = role.id"
                                :class="{'role-nav-btn active': activeRole === role.id, 'text-gray-700 hover:bg-gray-50 hover:text-blue-600': activeRole !== role.id}"
                                class="w-full flex items-center justify-between px-3.5 py-2.5 rounded-xl text-left text-xs sm:text-sm transition-all duration-150 group">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span :class="role.iconBg"
                                        class="w-7 h-7 rounded-lg flex items-center justify-center text-white flex-shrink-0 text-xs shadow-sm">
                                        <i :class="role.icon"></i>
                                    </span>
                                    <span class="truncate font-medium" x-text="role.name"></span>
                                </div>
                                <i class="fas fa-chevron-right text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                                    :class="{'opacity-100 text-white': activeRole === role.id}"></i>
                            </button>
                        </template>
                    </nav>
                </div>
            </div>

            <!-- Right Content Area: Detailed Guides for each Role -->
            <div class="lg:col-span-8 xl:col-span-9 space-y-6">

                <!-- ======================================================= -->
                <!-- 1. TỔNG QUAN HỆ THỐNG & MA TRẬN PHÂN QUYỀN -->
                <!-- ======================================================= -->
                <div x-show="activeRole === 'overview'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div>
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2.5 py-1 rounded-md">Phần
                                1: Kiến Trúc Doanh Nghiệp</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1.5 flex items-center gap-2">
                                <i class="fas fa-sitemap text-blue-600"></i> Tổng Quan Quy Trình Liên Hoàn & Ma Trận Phân
                                Quyền
                            </h2>
                        </div>
                    </div>

                    <div class="prose max-w-none text-gray-700 text-sm leading-relaxed space-y-4">
                        <p>
                            Hệ thống ERP-CRM được chuẩn hóa theo mô hình <strong>luồng nghiệp vụ liên hoàn 3 giai
                                đoạn</strong> kết nối chặt chẽ giữa 12 vị trí chức danh từ khi phát sinh nhu cầu đến khi
                            hoàn tất thanh toán:
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 my-6">
                            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                                <div class="font-bold text-blue-900 text-sm flex items-center gap-2 mb-2">
                                    <span
                                        class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs">1</span>
                                    Giai đoạn Marketing & Leads
                                </div>
                                <p class="text-xs text-blue-800 leading-normal">
                                    Sales xây dựng Database KH $\rightarrow$ Marketing lên kế hoạch Event & Dự toán ngân
                                    sách $\rightarrow$ BOD/Legal duyệt ngân sách $\rightarrow$ Tổ chức sự kiện $\rightarrow$
                                    Bàn giao Leads cho Sales.
                                </p>
                            </div>

                            <div class="bg-teal-50 border border-teal-200 rounded-xl p-4">
                                <div class="font-bold text-teal-900 text-sm flex items-center gap-2 mb-2">
                                    <span
                                        class="w-6 h-6 rounded-full bg-teal-600 text-white flex items-center justify-center text-xs">2</span>
                                    Giai đoạn Tư Vấn & ĐKDA
                                </div>
                                <p class="text-xs text-teal-800 leading-normal">
                                    Sales tạo Cơ hội $\rightarrow$ Tech tư vấn BOM $\rightarrow$ Sales gửi ĐKDA: PO Team
                                    (FTN) hoặc PM Team (Non-FTN) $\rightarrow$ Cập nhật Cost Price $\rightarrow$ Sales lập
                                    Báo giá trình Sales Manager duyệt.
                                </p>
                            </div>

                            <div class="bg-orange-50 border border-orange-200 rounded-xl p-4">
                                <div class="font-bold text-orange-900 text-sm flex items-center gap-2 mb-2">
                                    <span
                                        class="w-6 h-6 rounded-full bg-orange-600 text-white flex items-center justify-center text-xs">3</span>
                                    Hợp Đồng, Mua Hàng & Thu Nợ
                                </div>
                                <p class="text-xs text-orange-800 leading-normal">
                                    Khách chốt $\rightarrow$ Sales lập HĐMB & P&L trình duyệt 2 cấp (Legal C1 $\rightarrow$
                                    BOD C2) $\rightarrow$ Lập PR $\rightarrow$ PO mua Hãng $\rightarrow$ Hàng về Nhập kho
                                    $\rightarrow$ Xuất HĐ VAT & Thu nợ kèm UNC.
                                </p>
                            </div>
                        </div>

                        <div
                            class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-xl my-4 text-xs text-amber-900 space-y-1">
                            <div class="font-bold text-sm flex items-center gap-1.5 text-amber-800">
                                <i class="fas fa-exclamation-triangle text-amber-600"></i> Nguyên tắc phân luồng đặc thù
                                quan trọng:
                            </div>
                            <div>• <strong>Hãng FTN (Fortinet...):</strong> ĐKDA gửi trực tiếp sang <strong>PO Team</strong>
                                xử lý trên Portal Hãng Fortinet.</div>
                            <div>• <strong>Hãng Non-FTN (Check Point, Cisco, Trend Micro...):</strong> ĐKDA gửi sang
                                <strong>PM Team (Department PM)</strong> đàm phán Special Bid.</div>
                            <div>• <strong>Order Management (Chị Bích):</strong> Kiểm soát toàn diện đơn hàng, phê duyệt
                                PR/PO, ngân sách Marketing và xác nhận điều kiện xuất kho.</div>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- 2. MARKETING TEAM -->
                <!-- ======================================================= -->
                <div x-show="activeRole === 'marketing'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div>
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-purple-600 bg-purple-50 px-2.5 py-1 rounded-md">Vai
                                Trò 1: Tiếp Thị & Sự Kiện</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1.5 flex items-center gap-2">
                                <i class="fas fa-bullhorn text-purple-600"></i> Marketing Team (Nhân Viên Marketing)
                            </h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                        <a href="{{ route('marketing-events.index') }}"
                            class="flex items-center gap-3 p-3 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition-all text-purple-900 text-xs font-bold">
                            <i class="fas fa-calendar-alt text-lg text-purple-600"></i>
                            <span>👉 Đến Sự Kiện MKT</span>
                        </a>
                        <a href="{{ route('marketing-items.index') }}"
                            class="flex items-center gap-3 p-3 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition-all text-purple-900 text-xs font-bold">
                            <i class="fas fa-gift text-lg text-purple-600"></i>
                            <span>👉 Đến Kho Quà Tặng MKT</span>
                        </a>
                        <a href="{{ route('customers.index') }}"
                            class="flex items-center gap-3 p-3 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition-all text-purple-900 text-xs font-bold">
                            <i class="fas fa-address-book text-lg text-purple-600"></i>
                            <span>👉 Danh Sách Khách Hàng</span>
                        </a>
                    </div>

                    <div class="space-y-4">
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs">1</span>
                                Bước 1: Tạo Sự Kiện MKT & Dự Toán Ngân Sách
                            </div>
                            <p class="text-xs text-slate-600 pl-8 leading-relaxed">
                                Vào menu <strong>Bán hàng → Sự kiện Marketing → '+ Tạo Sự Kiện MKT'</strong>. Khai báo Tên sự kiện, Hãng đồng hành (Vendor), Thời gian tổ chức, Địa điểm. Nhập chi tiết dự toán chi phí và chọn nguồn ngân sách (<strong>Chi phí công ty</strong> hoặc <strong>Quỹ hỗ trợ từ Hãng - MDF</strong>).
                            </p>
                        </div>

                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs">2</span>
                                Bước 2: Gửi Duyệt Kế Hoạch & Ngân Sách (Approval Workflow)
                            </div>
                            <p class="text-xs text-slate-600 pl-8 leading-relaxed">
                                Bấm nút <strong>'Gửi duyệt ngân sách' (Submit Approval)</strong>. Hệ thống gửi phê duyệt đến Ban Giám Đốc (BOD) và Order Management (Chị Bích).<br>
                                <span class="text-amber-700 font-semibold mt-1 inline-block">⚠️ Lưu ý: Khi sự kiện đang ở trạng thái 'Chờ duyệt', hệ thống sẽ tạm khóa tạo ticket cho đến khi BOD phê duyệt chính thức.</span>
                            </p>
                        </div>

                        <div class="step-card bg-purple-50 border border-purple-200 rounded-xl p-4">
                            <div class="font-bold text-purple-900 text-sm mb-2 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs">3</span>
                                Bước 3: Tạo Ticket Yêu Cầu Hỗ Trợ Liên Phòng Ban (Mở sau khi BOD duyệt)
                            </div>
                            <p class="text-xs text-purple-900 pl-8 leading-relaxed mb-3">
                                Khi sự kiện đạt trạng thái <strong>Đã phê duyệt (Approved)</strong>, hệ thống mở khóa nút <strong>'+ Tạo Ticket hỗ trợ'</strong> gồm <strong>4 LOẠI TICKET</strong>:
                            </p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pl-8 text-xs">
                                <div class="bg-white p-3 rounded-lg border border-purple-100 shadow-sm">
                                    <div class="font-bold text-purple-700 flex items-center gap-1.5 mb-1">
                                        <i class="fas fa-users-cog"></i> 1. Ticket Internal Collaboration
                                    </div>
                                    <p class="text-slate-600">
                                        • <strong>Technical Team:</strong> Speaker thuyết trình, hỗ trợ kỹ thuật Demo PoC.<br>
                                        • <strong>Sales Team:</strong> Lập danh sách khách mời, gửi thư mời & đón tiếp khách.<br>
                                        • Chọn P.I.C tiếp nhận: Lead Team / Sales Assistant / All Members.
                                    </p>
                                </div>

                                <div class="bg-white p-3 rounded-lg border border-purple-100 shadow-sm">
                                    <div class="font-bold text-purple-700 flex items-center gap-1.5 mb-1">
                                        <i class="fas fa-plane-departure"></i> 2. Ticket Business Trip (Công tác tỉnh)
                                    </div>
                                    <p class="text-slate-600">
                                        • Khai báo Ngày xuất phát, Số lượng nhân sự tham gia.<br>
                                        • Ghi chú lịch trình các đoàn và Dự toán chi phí công tác (VND).<br>
                                        • Đính kèm vé máy bay, booking khách sạn.
                                    </p>
                                </div>

                                <div class="bg-white p-3 rounded-lg border border-purple-100 shadow-sm">
                                    <div class="font-bold text-purple-700 flex items-center gap-1.5 mb-1">
                                        <i class="fas fa-money-check-alt"></i> 3. Ticket Payment (Thanh toán / Tạm ứng)
                                    </div>
                                    <p class="text-slate-600">
                                        • Khai báo nội dung: Cọc tiệc khách sạn, in backdrop, thuê thiết bị...<br>
                                        • Nhập số tiền chi bằng số và bằng chữ.<br>
                                        • Liên kết mã Request và đính kèm hóa đơn/báo giá.
                                    </p>
                                </div>

                                <div class="bg-white p-3 rounded-lg border border-purple-100 shadow-sm">
                                    <div class="font-bold text-purple-700 flex items-center gap-1.5 mb-1">
                                        <i class="fas fa-tasks"></i> 4. Ticket Others (Khác / Giao việc trực tiếp)
                                    </div>
                                    <p class="text-slate-600">
                                        • Giao các đầu việc phát sinh, nhiệm vụ đột xuất hoặc yêu cầu hỗ trợ khác cho các nhân sự liên quan.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs">4</span>
                                Bước 4: Quản Lý Kho Quà Tặng & Duyệt Cấp Phát Vật Phẩm
                            </div>
                            <p class="text-xs text-slate-600 pl-8 leading-relaxed">
                                Vào tab <strong>Ticket từ Sales</strong> hoặc menu <strong>Kho vật phẩm MKT</strong>:<br>
                                • <strong>Bước 1 (Phân bổ quà):</strong> Bấm nút <strong>'Phân bổ quà'</strong> để chọn loại quà và số lượng xuất từ kho (hệ thống tự động trừ tồn kho).<br>
                                • <strong>Bước 2 (Bàn giao):</strong> Sau khi đã phân bổ quà xong, bấm <strong>'Bàn giao quà'</strong> để xác nhận đóng gói và chuyển giao cho Sales đi gặp khách.
                            </p>
                        </div>

                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-purple-600 text-white flex items-center justify-center text-xs">5</span>
                                Bước 5: Nghiệm Thu Chi Phí Thực Tế & Quyết Toán Sự Kiện
                            </div>
                            <p class="text-xs text-slate-600 pl-8 leading-relaxed">
                                Theo dõi tiến độ hoàn thành các Ticket liên phòng ban → Cập nhật chi phí thực tế phát sinh theo các Ticket Payment đã giải ngân → Tổng kết và hoàn tất quyết toán sự kiện.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- 3. SALES STAFF (AM) -->
                <!-- ======================================================= -->
                <div x-show="activeRole === 'sales_staff'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div>
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2.5 py-1 rounded-md">Vai
                                Trò 2: Kinh Doanh Trực Tiếp</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1.5 flex items-center gap-2">
                                <i class="fas fa-user-tie text-blue-600"></i> Sales Staff (Nhân Viên Kinh Doanh / AM)
                            </h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
                        <a href="{{ route('opportunities.index') }}"
                            class="flex items-center gap-2 p-2.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-all text-blue-900 text-xs font-bold">
                            <i class="fas fa-lightbulb text-base text-blue-600"></i>
                            <span>👉 Tạo Cơ Hội Bán Hàng</span>
                        </a>
                        <a href="{{ route('projects.index') }}"
                            class="flex items-center gap-2 p-2.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-all text-blue-900 text-xs font-bold">
                            <i class="fas fa-project-diagram text-base text-blue-600"></i>
                            <span>👉 Đăng Ký Dự Án (ĐKDA)</span>
                        </a>
                        <a href="{{ route('quotations.index') }}"
                            class="flex items-center gap-2 p-2.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-all text-blue-900 text-xs font-bold">
                            <i class="fas fa-file-invoice text-base text-blue-600"></i>
                            <span>👉 Lập Báo Giá</span>
                        </a>
                        <a href="{{ route('sales.index') }}"
                            class="flex items-center gap-2 p-2.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-all text-blue-900 text-xs font-bold">
                            <i class="fas fa-file-signature text-base text-blue-600"></i>
                            <span>👉 Tạo HĐMB & P&L</span>
                        </a>
                        <a href="{{ route('purchase-requests.index') }}"
                            class="flex items-center gap-2 p-2.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-all text-blue-900 text-xs font-bold">
                            <i class="fas fa-cart-arrow-down text-base text-blue-600"></i>
                            <span>👉 Yêu Cầu Đặt Hàng (PR)</span>
                        </a>
                        <a href="{{ route('technical-tickets.index') }}"
                            class="flex items-center gap-2 p-2.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-all text-blue-900 text-xs font-bold">
                            <i class="fas fa-tools text-base text-blue-600"></i>
                            <span>👉 Tạo Ticket Kỹ Thuật</span>
                        </a>
                    </div>

                    <div class="space-y-4">
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1 flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs">1</span>
                                Bước 1: Tiếp Nhận & Tạo Cơ Hội Bán Hàng (Opportunity)
                            </div>
                            <p class="text-xs text-slate-600 pl-8 leading-relaxed">
                                Truy cập <strong>Cơ hội bán hàng $\rightarrow$ '+ Tạo Cơ Hội Mới'</strong>. Chọn Khách hàng,
                                Người liên hệ, Doanh số dự kiến, Ngày dự kiến chốt deal và Nguồn gốc cơ hội.
                            </p>
                        </div>

                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1 flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs">2</span>
                                Bước 2: Yêu Cầu Tech Tư Vấn BOM & Quà Tặng MKT
                            </div>
                            <p class="text-xs text-slate-600 pl-8 leading-relaxed">
                                Tại chi tiết Cơ hội $\rightarrow$ Tab <strong>'Technical Tickets'</strong>: Tạo ticket nhờ
                                Tech Team khảo sát, tư vấn giải pháp, lên BOM cấu hình hoặc mượn thiết bị Demo PoC. Tab 'Quà
                                tặng': tạo ticket xin quà MKT cho khách.
                            </p>
                        </div>

                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1 flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs">3</span>
                                Bước 3: Đăng Ký Bảo Vệ Dự Án (ĐKDA) Với Hãng
                            </div>
                            <p class="text-xs text-slate-600 pl-8 leading-relaxed">
                                Sau khi chốt BOM $\rightarrow$ Bấm <strong>'Chuyển thành Dự án (ĐKDA)'</strong>. Hệ thống tự
                                động định tuyến: Hãng FTN sang PO Team; Hãng Non-FTN sang PM Team để lấy Special Bid Price
                                và cập nhật Cost Price.
                            </p>
                        </div>

                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1 flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs">4</span>
                                Bước 4: Lập Báo Giá & Trình Sales Manager Duyệt
                            </div>
                            <p class="text-xs text-slate-600 pl-8 leading-relaxed">
                                Truy cập <strong>Báo giá $\rightarrow$ '+ Tạo Báo Giá'</strong> $\rightarrow$ Nhập giá bán
                                $\rightarrow$ Kiểm tra Gross Margin (%) $\rightarrow$ Bấm <strong>'Gửi duyệt Báo
                                    giá'</strong> để Sales Manager phê duyệt chính sách giá.
                            </p>
                        </div>

                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1 flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs">5</span>
                                Bước 5: Tạo Đơn Hàng (HĐMB) & Trình Duyệt P&L 2 Cấp
                            </div>
                            <p class="text-xs text-slate-600 pl-8 leading-relaxed">
                                Bấm <strong>'Tạo Đơn Hàng'</strong> từ Báo giá đã duyệt $\rightarrow$ Khai báo số HĐMB, điều
                                khoản thanh toán $\rightarrow$ Kiểm tra Bảng tính P&L $\rightarrow$ Bấm <strong>'Gửi Duyệt
                                    Đơn Hàng & P&L'</strong> (Duyệt Cấp 1: Legal $\rightarrow$ Cấp 2: BOD).
                            </p>
                        </div>

                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1 flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs">6</span>
                                Bước 6: Tạo Yêu Cầu Đặt Hàng (PR)
                            </div>
                            <p class="text-xs text-slate-600 pl-8 leading-relaxed">
                                Sau khi BOD phê duyệt đơn hàng $\rightarrow$ Bấm nút <strong>'Tạo Yêu Cầu Đặt Hàng
                                    (PR)'</strong> $\rightarrow$ Gửi sang Order Management và PO Team để tiến hành mua hàng
                                Hãng.
                            </p>
                        </div>

                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1 flex items-center gap-2">
                                <span
                                    class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs">7</span>
                                Bước 7: Theo Dõi Giao Hàng, YC Xuất HĐ & Đính Kèm UNC
                            </div>
                            <p class="text-xs text-slate-600 pl-8 leading-relaxed">
                                Theo dõi hàng về kho và xuất kho $\rightarrow$ Bấm <strong>'Yêu cầu xuất hóa đơn'</strong>
                                sang Kế toán $\rightarrow$ Upload file Ủy nhiệm chi (UNC) vào mục Bằng chứng thanh toán khi
                                khách chuyển tiền để tất toán công nợ.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- 4. SALES MANAGER -->
                <!-- ======================================================= -->
                <div x-show="activeRole === 'sales_manager'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div>
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-blue-800 bg-blue-50 px-2.5 py-1 rounded-md">Vai
                                Trò 3: Quản Lý Bán Hàng</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1.5 flex items-center gap-2">
                                <i class="fas fa-chart-line text-blue-800"></i> Sales Manager (Trưởng Phòng Kinh Doanh)
                            </h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                        <a href="{{ route('dashboard') }}"
                            class="flex items-center gap-3 p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-all text-blue-900 text-xs font-bold">
                            <i class="fas fa-tachometer-alt text-lg text-blue-700"></i>
                            <span>👉 Dashboard Pipeline</span>
                        </a>
                        <a href="{{ route('quotations.index') }}"
                            class="flex items-center gap-3 p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-all text-blue-900 text-xs font-bold">
                            <i class="fas fa-check-double text-lg text-blue-700"></i>
                            <span>👉 Phê Duyệt Báo Giá</span>
                        </a>
                        <a href="{{ route('customer-debts.index') }}"
                            class="flex items-center gap-3 p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl transition-all text-blue-900 text-xs font-bold">
                            <i class="fas fa-hand-holding-usd text-lg text-blue-700"></i>
                            <span>👉 Quản Lý Công Nợ</span>
                        </a>
                    </div>

                    <div class="space-y-4">
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">1. Giám Sát Dashboard Pipeline & Phân Bổ
                                Leads</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Theo dõi tổng giá trị pipeline cơ hội, phân bổ
                                Leads từ Marketing cho các AM phụ trách phù hợp với chuyên môn ngành hàng.</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">2. Thẩm Định & Phê Duyệt Báo Giá</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Lọc danh sách Báo giá 'Chờ duyệt'
                                $\rightarrow$ Kiểm tra mức giá bán, tỷ suất lợi nhuận Gross Margin (%) $\rightarrow$ Bấm Phê
                                duyệt (Approve) hoặc Từ chối yêu cầu AM chỉnh sửa.</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">3. Kiểm Soát Biên Lợi Nhuận P&L Đơn Hàng
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">Theo dõi các đơn hàng đang trong luồng phê
                                duyệt Legal & BOD, can thiệp các đơn hàng có biên lợi nhuận thấp bất thường.</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">4. Đôn Đốc Thu Hồi Công Nợ Đến Hạn</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Theo dõi danh sách nợ quá hạn tại phân hệ
                                Customer Debts, chỉ đạo AM bám sát khách hàng thu hồi nợ.</p>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- 5. PM TEAM -->
                <!-- ======================================================= -->
                <div x-show="activeRole === 'pm_team'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div>
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-teal-600 bg-teal-50 px-2.5 py-1 rounded-md">Vai
                                Trò 4: Quản Lý Dự Án & Hãng Non-FTN</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1.5 flex items-center gap-2">
                                <i class="fas fa-tasks text-teal-600"></i> PM Team (Project Specialist)
                            </h2>
                        </div>
                    </div>

                    <div class="mb-6">
                        <a href="{{ route('projects.index') }}"
                            class="inline-flex items-center gap-3 p-3 bg-teal-50 hover:bg-teal-100 border border-teal-200 rounded-xl transition-all text-teal-900 text-xs font-bold">
                            <i class="fas fa-folder-open text-lg text-teal-600"></i>
                            <span>👉 Tiếp Nhận & Quản Lý ĐKDA Non-FTN</span>
                        </a>
                    </div>

                    <div class="space-y-4">
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">1. Tiếp Nhận Intake ĐKDA Hãng Non-FTN</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Truy cập <strong>Projects</strong>
                                $\rightarrow$ Lọc dự án thuộc các hãng Non-FTN (Check Point, Cisco, Trend Micro, IBM...) do
                                Sales Staff gửi sang.</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">2. Đăng Ký Bảo Vệ Deal & Đàm Phán Special Bid
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">Liên hệ đại diện Hãng/Nhà phân phối
                                $\rightarrow$ Đăng ký bảo vệ dự án $\rightarrow$ Đàm phán mức chiết khấu Special Bid tối ưu
                                nhất.</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">3. Cập Nhật Giá Mua Hãng (Cost Price)</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Mở chi tiết Dự án $\rightarrow$ Tab Sản phẩm
                                $\rightarrow$ Nhập Cost Price chính thức và thời hạn bảo vệ deal để Sales tiến hành báo giá.
                            </p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">4. Thiết Lập & Giám Sát Milestone Dự Án</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Tạo các mốc giai đoạn triển khai (Giao hàng,
                                Cài đặt, UAT, Bàn giao) và phối hợp Technical nghiệm thu dự án.</p>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- 6. PO TEAM -->
                <!-- ======================================================= -->
                <div x-show="activeRole === 'po_team'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div>
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-orange-600 bg-orange-50 px-2.5 py-1 rounded-md">Vai
                                Trò 5: Bộ Phận Mua Hàng</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1.5 flex items-center gap-2">
                                <i class="fas fa-shopping-cart text-orange-600"></i> PO Team (PO Manager & Staff)
                            </h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                        <a href="{{ route('purchase-requests.index') }}"
                            class="flex items-center gap-3 p-3 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-xl transition-all text-orange-900 text-xs font-bold">
                            <i class="fas fa-inbox text-lg text-orange-600"></i>
                            <span>👉 Yêu Cầu Mua Hàng (PR)</span>
                        </a>
                        <a href="{{ route('purchase-orders.index') }}"
                            class="flex items-center gap-3 p-3 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-xl transition-all text-orange-900 text-xs font-bold">
                            <i class="fas fa-file-contract text-lg text-orange-600"></i>
                            <span>👉 Đơn Mua Hàng (PO)</span>
                        </a>
                        <a href="{{ route('shipping-allocations.index') }}"
                            class="flex items-center gap-3 p-3 bg-orange-50 hover:bg-orange-100 border border-orange-200 rounded-xl transition-all text-orange-900 text-xs font-bold">
                            <i class="fas fa-truck-loading text-lg text-orange-600"></i>
                            <span>👉 Phân Bổ Vận Chuyển</span>
                        </a>
                    </div>

                    <div class="space-y-4">
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">1. ĐKDA Hãng FTN (Fortinet)</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Nhận ĐKDA từ Sales $\rightarrow$ Đăng ký trên
                                Portal Fortinet $\rightarrow$ Cập nhật Deal ID và Cost Price cho Sales.</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">2. Xử Lý PR Đặt Hàng & Lập PO Hãng</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Nhận PR từ Sales (đã qua duyệt BOD)
                                $\rightarrow$ Bấm 'Tạo PO từ PR' $\rightarrow$ Khai báo Nhà cung cấp, Incoterms, ETA hàng về
                                $\rightarrow$ PO Manager phê duyệt PO.</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">3. Phân Bổ Chi Phí Vận Chuyển & Lập Phiếu
                                Nhập Kho</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Khai báo cước vận chuyển/thuế (Shipping
                                Allocations) vào giá vốn lô hàng $\rightarrow$ Khi hàng về/license active, bấm 'Lập Phiếu
                                Nhập Kho' chuyển Kho kiểm nhận.</p>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- 7. LEGAL TEAM -->
                <!-- ======================================================= -->
                <div x-show="activeRole === 'legal_team'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div>
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-rose-600 bg-rose-50 px-2.5 py-1 rounded-md">Vai
                                Trò 6: Pháp Chế Doanh Nghiệp</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1.5 flex items-center gap-2">
                                <i class="fas fa-balance-scale text-rose-600"></i> Legal Team (Ban Pháp Chế)
                            </h2>
                        </div>
                    </div>

                    <div class="mb-6">
                        <a href="{{ route('sales.index') }}"
                            class="inline-flex items-center gap-3 p-3 bg-rose-50 hover:bg-rose-100 border border-rose-200 rounded-xl transition-all text-rose-900 text-xs font-bold">
                            <i class="fas fa-file-signature text-lg text-rose-600"></i>
                            <span>👉 Danh Sách HĐMB Cần Thẩm Định</span>
                        </a>
                    </div>

                    <div class="space-y-4">
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">1. Tiếp Nhận Hồ Sơ HĐMB & Thẩm Định Pháp Lý
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">Lọc đơn hàng 'Chờ Pháp chế duyệt'
                                $\rightarrow$ Kiểm tra tư cách pháp nhân khách hàng, điều khoản thanh toán, phạt vi phạm và
                                bảo hành.</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">2. Rà Soát Bảng P&L & Phê Duyệt Cấp 1</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Kiểm tra cơ cấu giá vốn, chiết khấu và hoa
                                hồng $\rightarrow$ Bấm <strong>'Phê duyệt Cấp 1'</strong> để chuyển lên Ban Giám Đốc (BOD)
                                phê duyệt Cấp 2.</p>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- 8. BOD / DIRECTOR -->
                <!-- ======================================================= -->
                <div x-show="activeRole === 'director'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div>
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-slate-700 bg-slate-100 px-2.5 py-1 rounded-md">Vai
                                Trò 7: Ban Lãnh Đạo</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1.5 flex items-center gap-2">
                                <i class="fas fa-crown text-amber-500"></i> Ban Giám Đốc (BOD / Director)
                            </h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                        <a href="{{ route('dashboard.business-activity') }}"
                            class="flex items-center gap-3 p-3 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-xl transition-all text-slate-900 text-xs font-bold">
                            <i class="fas fa-chart-pie text-lg text-slate-700"></i>
                            <span>👉 Executive Business Dashboard</span>
                        </a>
                        <a href="{{ route('sales.index') }}"
                            class="flex items-center gap-3 p-3 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-xl transition-all text-slate-900 text-xs font-bold">
                            <i class="fas fa-stamp text-lg text-slate-700"></i>
                            <span>👉 Phê Duyệt HĐMB Cấp 2 (Final)</span>
                        </a>
                    </div>

                    <div class="space-y-4">
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">1. Giám Sát Executive Dashboard Thời Gian
                                Thực</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Theo dõi Doanh thu, Lợi nhuận gộp lũy kế, Tồn
                                kho giá trị cao và Tổng mức Công nợ toàn công ty.</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">2. Phê Duyệt HĐMB & P&L Cấp 2 (Final
                                Approval)</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Xem xét đơn hàng đã qua Legal thẩm định
                                $\rightarrow$ Bấm <strong>'Phê duyệt Hợp đồng'</strong> để mở khóa chức năng tạo PR mua
                                hàng.</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">3. Phê Duyệt Ngân Sách Marketing Events</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Thẩm định kế hoạch và phê duyệt dự toán ngân
                                sách sự kiện Marketing/Quỹ MDF Hãng.</p>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- 9. ORDER MANAGEMENT (CHỊ BÍCH) -->
                <!-- ======================================================= -->
                <div x-show="activeRole === 'order_management'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div>
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-pink-600 bg-pink-50 px-2.5 py-1 rounded-md">Vai
                                Trò 8: Quản Lý Đơn Hàng & Điều Phối</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1.5 flex items-center gap-2">
                                <i class="fas fa-clipboard-check text-pink-600"></i> Order Management (Chị Bích)
                            </h2>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">1. Rà Soát Hồ Sơ Đơn Bán Hàng & Duyệt PR
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">Kiểm tra tính hợp lệ của BOM và hợp đồng
                                $\rightarrow$ Phê duyệt Yêu cầu Đặt hàng PR và phân công PO Staff mua hàng.</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">2. Duyệt Đơn Mua Hàng (PO) & Ngân Sách MKT
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">Kiểm tra tính chính xác của PO $\rightarrow$
                                Duyệt PO gửi Vendor $\rightarrow$ Kiểm tra dự toán ngân sách sự kiện MKT.</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">3. Xác Nhận Điều Kiện Xuất Hàng (Approve
                                Export)</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Kiểm tra điều kiện hợp đồng và thanh toán
                                $\rightarrow$ Bấm <strong>'Phê duyệt Điều Kiện Xuất Hàng'</strong> để Kho thực hiện xuất
                                kho.</p>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- 10. WAREHOUSE TEAM -->
                <!-- ======================================================= -->
                <div x-show="activeRole === 'warehouse'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div>
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-md">Vai
                                Trò 9: Quản Lý Kho & Vận</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1.5 flex items-center gap-2">
                                <i class="fas fa-warehouse text-emerald-600"></i> Warehouse Team (Kho & Logistics)
                            </h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
                        <a href="{{ route('imports.index') }}"
                            class="flex items-center gap-2 p-2.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl transition-all text-emerald-900 text-xs font-bold">
                            <i class="fas fa-file-import text-emerald-600"></i>
                            <span>👉 Phiếu Nhập Kho</span>
                        </a>
                        <a href="{{ route('exports.index') }}"
                            class="flex items-center gap-2 p-2.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl transition-all text-emerald-900 text-xs font-bold">
                            <i class="fas fa-file-export text-emerald-600"></i>
                            <span>👉 Phiếu Xuất Kho</span>
                        </a>
                        <a href="{{ route('inventory.index') }}"
                            class="flex items-center gap-2 p-2.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-xl transition-all text-emerald-900 text-xs font-bold">
                            <i class="fas fa-boxes text-emerald-600"></i>
                            <span>👉 Quản Lý Tồn Kho</span>
                        </a>
                    </div>

                    <div class="space-y-4">
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">1. Kiểm Đếm & Duyệt Nhập Kho (Imports)</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Kiểm đếm hàng về, quét mã Serial/Part Number
                                $\rightarrow$ Warehouse Manager bấm <strong>'Duyệt Nhập Kho'</strong> $\rightarrow$ Tồn kho
                                tự động tăng.</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">2. Xuất Kho Giao Hàng (Exports)</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Nhận lệnh xuất đã duyệt $\rightarrow$ Soạn
                                hàng, quét Serial xuất kho, in Phiếu xuất kho $\rightarrow$ Bấm <strong>'Xác Nhận Xuất
                                    Kho'</strong>.</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">3. Điều Chuyển & Quản Lý Hàng Hỏng (Damaged
                                Goods)</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Thực hiện điều chuyển giữa các kho chi nhánh;
                                lập biên bản hàng lỗi hỏng để đổi trả bảo hành hoặc thanh lý.</p>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- 11. FINANCE TEAM -->
                <!-- ======================================================= -->
                <div x-show="activeRole === 'accountant'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div>
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-sky-600 bg-sky-50 px-2.5 py-1 rounded-md">Vai
                                Trò 10: Tài Chính & Kế Toán</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1.5 flex items-center gap-2">
                                <i class="fas fa-calculator text-sky-600"></i> Finance Team (Bộ Phận Kế Toán)
                            </h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                        <a href="{{ route('customer-debts.index') }}"
                            class="flex items-center gap-3 p-3 bg-sky-50 hover:bg-sky-100 border border-sky-200 rounded-xl transition-all text-sky-900 text-xs font-bold">
                            <i class="fas fa-hand-holding-usd text-lg text-sky-600"></i>
                            <span>👉 Công Nợ & Ghi Nhận Thu Tiền (Record Payment)</span>
                        </a>
                        <a href="{{ route('financial-transactions.index') }}"
                            class="flex items-center gap-3 p-3 bg-sky-50 hover:bg-sky-100 border border-sky-200 rounded-xl transition-all text-sky-900 text-xs font-bold">
                            <i class="fas fa-money-check-alt text-lg text-sky-600"></i>
                            <span>👉 Giao Dịch Thu Chi & Sổ Quỹ</span>
                        </a>
                    </div>

                    <div class="space-y-4">
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">1. Xuất & Nhập Hóa Đơn VAT Điện Tử</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Nhận yêu cầu xuất HĐ từ Sales $\rightarrow$ Mở
                                đơn hàng $\rightarrow$ Nhập Số HĐ, Ngày phát hành và đính kèm file HĐ điện tử (PDF/XML).</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">2. Theo Dõi Công Nợ & Ghi Nhận Thanh Toán
                                (Record Payment)</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Theo dõi tuổi nợ $\rightarrow$ Khi tiền về tài
                                khoản ngân hàng: Bấm <strong>'Ghi nhận thanh toán'</strong>, nhập số tiền thực nhận và
                                upload UNC/Giấy báo có để tất toán đơn hàng.</p>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- 12. TECHNICAL TEAM -->
                <!-- ======================================================= -->
                <div x-show="activeRole === 'technical'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div>
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-md">Vai
                                Trò 11: Kỹ Thuật & Giải Pháp</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1.5 flex items-center gap-2">
                                <i class="fas fa-tools text-indigo-600"></i> Technical Team (Lead & Kỹ Sư)
                            </h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                        <a href="{{ route('technical.dashboard') }}"
                            class="flex items-center gap-3 p-3 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-xl transition-all text-indigo-900 text-xs font-bold">
                            <i class="fas fa-chart-pie text-lg text-indigo-600"></i>
                            <span>👉 Technical Dashboard & Giám Sát SLA</span>
                        </a>
                        <a href="{{ route('technical-tickets.index') }}"
                            class="flex items-center gap-3 p-3 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-xl transition-all text-indigo-900 text-xs font-bold">
                            <i class="fas fa-ticket-alt text-lg text-indigo-600"></i>
                            <span>👉 Quản Lý Ticket Kỹ Thuật</span>
                        </a>
                    </div>

                    <div class="space-y-4">
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">1. Tiếp Nhận Ticket & Phân Công Xử Lý</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Nhận ticket từ Sales/MKT (Tư vấn BOM, Demo
                                PoC, Khảo sát, Triển khai, Bảo hành) $\rightarrow$ Lead gán Kỹ sư phụ trách hoặc Kỹ sư tự
                                nhận việc.</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">2. Ghi Nhật Ký Support Logs & Đóng Ticket
                            </div>
                            <p class="text-xs text-slate-600 leading-relaxed">Thực hiện công việc $\rightarrow$ Cập nhật
                                Technical Support Logs, đính kèm biên bản nghiệm thu $\rightarrow$ Lead kiểm tra và bấm Phê
                                duyệt đóng Ticket.</p>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- 13. SUPER ADMIN / ADMIN -->
                <!-- ======================================================= -->
                <div x-show="activeRole === 'super_admin'"
                    class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div>
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-gray-700 bg-gray-100 px-2.5 py-1 rounded-md">Vai
                                Trò 12: Quản Trị Hệ Thống</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1.5 flex items-center gap-2">
                                <i class="fas fa-cogs text-gray-700"></i> Quản Trị Viên (Super Admin & Admin)
                            </h2>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                        <a href="{{ route('users.index') }}"
                            class="flex items-center gap-3 p-3 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-xl transition-all text-gray-900 text-xs font-bold">
                            <i class="fas fa-users-cog text-lg text-gray-700"></i>
                            <span>👉 Người Dùng & Roles</span>
                        </a>
                        <a href="{{ route('roles.index') }}"
                            class="flex items-center gap-3 p-3 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-xl transition-all text-gray-900 text-xs font-bold">
                            <i class="fas fa-user-shield text-lg text-gray-700"></i>
                            <span>👉 Cấu Hình Phân Quyền</span>
                        </a>
                        <a href="{{ route('cost-formulas.index') }}"
                            class="flex items-center gap-3 p-3 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-xl transition-all text-gray-900 text-xs font-bold">
                            <i class="fas fa-calculator text-lg text-gray-700"></i>
                            <span>👉 Công Thức Giá Vốn</span>
                        </a>
                    </div>

                    <div class="space-y-4">
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">1. Quản Trị Người Dùng, Gán Vai Trò & Phân
                                Quyền</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Tạo tài khoản nhân viên, gán Vai trò (Role) và
                                Phòng ban (Department: lưu ý gán đúng Dept 'PM' cho nhân sự PM Team).</p>
                        </div>
                        <div class="step-card bg-slate-50 border border-slate-200 rounded-xl p-4">
                            <div class="font-bold text-slate-800 text-sm mb-1">2. Phê Duyệt Đặt Hàng & Điều Kiện Xuất Hàng
                                (Admin Approvals)</div>
                            <p class="text-xs text-slate-600 leading-relaxed">Duyệt Yêu cầu Đặt hàng PR từ Sales và xác nhận
                                điều kiện xuất hàng cho bộ phận Kho trên tài khoản Admin.</p>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- 14. SOP & FAQ -->
                <!-- ======================================================= -->
                <div x-show="activeRole === 'faq'" class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 sm:p-8">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div>
                            <span
                                class="text-xs font-bold uppercase tracking-wider text-amber-600 bg-amber-50 px-2.5 py-1 rounded-md">Phần
                                3: Quy Tắc SOP & Xử Lý Sự Cố</span>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1.5 flex items-center gap-2">
                                <i class="fas fa-question-circle text-amber-500"></i> Bảng Tra Cứu Sự Cố Nghiệp Vụ Thường
                                Gặp (FAQ)
                            </h2>
                        </div>
                    </div>

                    <div class="space-y-4 text-xs sm:text-sm">
                        <div class="border border-gray-200 rounded-xl p-4 bg-slate-50">
                            <div class="font-bold text-slate-800 flex items-center gap-2 text-sm text-blue-900 mb-1">
                                <i class="fas fa-question-circle text-blue-600"></i> 1. Sales không thấy nút 'Tạo PR Đặt
                                Hàng' trên Đơn hàng?
                            </div>
                            <p class="text-slate-600 pl-6">
                                <strong>Nguyên nhân:</strong> Đơn bán hàng (Sales Order) chưa hoàn tất đủ 2 cấp duyệt (Legal
                                Cấp 1 & BOD Cấp 2).<br>
                                <strong>Cách xử lý:</strong> Kiểm tra tab Approval History; liên hệ Legal hoặc BOD hoàn tất
                                phê duyệt hợp đồng.
                            </p>
                        </div>

                        <div class="border border-gray-200 rounded-xl p-4 bg-slate-50">
                            <div class="font-bold text-slate-800 flex items-center gap-2 text-sm text-blue-900 mb-1">
                                <i class="fas fa-question-circle text-blue-600"></i> 2. PM Team không nhận được thông báo
                                ĐKDA của Hãng Non-FTN?
                            </div>
                            <p class="text-slate-600 pl-6">
                                <strong>Nguyên nhân:</strong> Tài khoản PM chưa được cấu hình đúng Department là 'PM'.<br>
                                <strong>Cách xử lý:</strong> Admin truy cập Quản lý Người dùng, cập nhật lại Department
                                thành 'PM'.
                            </p>
                        </div>

                        <div class="border border-gray-200 rounded-xl p-4 bg-slate-50">
                            <div class="font-bold text-slate-800 flex items-center gap-2 text-sm text-blue-900 mb-1">
                                <i class="fas fa-question-circle text-blue-600"></i> 3. Kho không thể tạo Phiếu Xuất Kho cho
                                đơn hàng đã duyệt?
                            </div>
                            <p class="text-slate-600 pl-6">
                                <strong>Nguyên nhân:</strong> Chưa có bước 'Xác nhận điều kiện xuất kho' từ Order Management
                                / Admin.<br>
                                <strong>Cách xử lý:</strong> Liên hệ Order Management kiểm tra điều kiện thanh toán/hợp đồng
                                để bấm Duyệt xuất kho.
                            </p>
                        </div>

                        <div class="border border-gray-200 rounded-xl p-4 bg-slate-50">
                            <div class="font-bold text-slate-800 flex items-center gap-2 text-sm text-blue-900 mb-1">
                                <i class="fas fa-question-circle text-blue-600"></i> 4. Không thể Ghi nhận thanh toán
                                (Record Payment) cho khách?
                            </div>
                            <p class="text-slate-600 pl-6">
                                <strong>Nguyên nhân:</strong> Đơn hàng chưa được Kế toán nhập thông tin Hóa đơn VAT chính
                                thức.<br>
                                <strong>Cách xử lý:</strong> Kế toán nhập Số HĐ và Ngày phát hành Hóa đơn VAT trước khi ghi
                                nhận thanh toán.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Upload New Version Modal -->
        <div x-show="showUploadModal" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
            @keydown.escape.window="showUploadModal = false">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 sm:p-8 relative"
                @click.away="showUploadModal = false">
                <button @click="showUploadModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>

                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Cập Nhật File Hướng Dẫn Mới</h3>
                        <p class="text-xs text-gray-500">Tải lên file Word (.docx) hoặc PDF (.pdf) mới nhất</p>
                    </div>
                </div>

                <form action="{{ route('user-guide.upload') }}" method="POST" enctype="multipart/form-data"
                    class="space-y-4 mt-4">
                    @csrf
                    <div
                        class="border-2 border-dashed border-gray-300 hover:border-blue-500 rounded-xl p-6 text-center cursor-pointer bg-gray-50 hover:bg-blue-50 transition-colors">
                        <input type="file" name="guide_file" id="guide_file" accept=".docx,.pdf" required
                            class="w-full text-xs sm:text-sm text-gray-600">
                        <p class="text-xs text-gray-500 mt-2">Hỗ trợ định dạng: Word (.docx), PDF (.pdf) - Dung lượng tối
                            đa: 50MB</p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="showUploadModal = false"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl">
                            Hủy
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold rounded-xl shadow-md">
                            <i class="fas fa-upload mr-1.5"></i> Tải lên ngay
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <script>
        function userGuideHub() {
            return {
                activeRole: 'overview',
                searchQuery: '',
                matchedCount: 0,
                showUploadModal: false,
                quickTags: ['BOM', 'P&L', 'ĐKDA', 'Duyệt PR', 'Nhập kho', 'UNC', 'Báo giá', 'Quà tặng', 'Sự kiện', 'HĐMB'],
                rolesList: [
                    { id: 'overview', name: '1. Tổng quan & Ma trận', icon: 'fas fa-sitemap', iconBg: 'bg-blue-600' },
                    { id: 'marketing', name: '2. Marketing Team', icon: 'fas fa-bullhorn', iconBg: 'bg-purple-600' },
                    { id: 'sales_staff', name: '3. Sales Staff (AM)', icon: 'fas fa-user-tie', iconBg: 'bg-blue-600' },
                    { id: 'sales_manager', name: '4. Sales Manager', icon: 'fas fa-chart-line', iconBg: 'bg-blue-800' },
                    { id: 'pm_team', name: '5. PM Team (Non-FTN)', icon: 'fas fa-tasks', iconBg: 'bg-teal-600' },
                    { id: 'po_team', name: '6. PO Team (Mua hàng)', icon: 'fas fa-shopping-cart', iconBg: 'bg-orange-600' },
                    { id: 'legal_team', name: '7. Legal Team (Pháp chế)', icon: 'fas fa-balance-scale', iconBg: 'bg-rose-600' },
                    { id: 'director', name: '8. Ban Giám Đốc (BOD)', icon: 'fas fa-crown', iconBg: 'bg-slate-700' },
                    { id: 'order_management', name: '9. Order Management (Chị Bích)', icon: 'fas fa-clipboard-check', iconBg: 'bg-pink-600' },
                    { id: 'warehouse', name: '10. Warehouse (Kho & Vận)', icon: 'fas fa-warehouse', iconBg: 'bg-emerald-600' },
                    { id: 'accountant', name: '11. Finance (Kế toán)', icon: 'fas fa-calculator', iconBg: 'bg-sky-600' },
                    { id: 'technical', name: '12. Technical Team', icon: 'fas fa-tools', iconBg: 'bg-indigo-600' },
                    { id: 'super_admin', name: '13. Quản Trị Hệ Thống', icon: 'fas fa-cogs', iconBg: 'bg-gray-700' },
                    { id: 'faq', name: '14. Quy Tắc SOP & FAQ', icon: 'fas fa-question-circle', iconBg: 'bg-amber-500' }
                ],
                handleSearch() {
                    const q = this.searchQuery.trim().toLowerCase();
                    if (!q) {
                        this.matchedCount = 0;
                        return;
                    }

                    // Count occurrences and highlight matching role sections
                    let count = 0;
                    const textNodes = document.querySelectorAll('.step-card, .prose');

                    // Auto switch to first matching role tab if current doesn't match
                    const roleKeywords = {
                        'marketing': ['marketing', 'sự kiện', 'mdf', 'quà tặng', 'mkt', 'event', 'khách mời'],
                        'sales_staff': ['sales staff', 'cơ hội', 'opportunity', 'đkda', 'báo giá', 'hđmb', 'p&l', 'pr', 'unc', 'bom'],
                        'sales_manager': ['sales manager', 'duyệt báo giá', 'gross margin', 'công nợ quá hạn', 'pipeline'],
                        'pm_team': ['pm team', 'non-ftn', 'special bid', 'hãng', 'milestone', 'deal'],
                        'po_team': ['po team', 'ftn', 'fortinet', 'mua hàng', 'purchase order', 'shipping', 'vận chuyển'],
                        'legal_team': ['legal', 'pháp chế', 'thẩm định', 'cấp 1', 'điều khoản', 'hợp đồng'],
                        'director': ['bod', 'giám đốc', 'cấp 2', 'final approval', 'báo cáo quản trị'],
                        'order_management': ['chị bích', 'order management', 'điều kiện xuất', 'duyệt pr'],
                        'warehouse': ['kho', 'nhập kho', 'xuất kho', 'tồn kho', 'serial', 'damaged goods', 'thẻ kho'],
                        'accountant': ['kế toán', 'hóa đơn', 'vat', 'record payment', 'thu tiền', 'sổ quỹ'],
                        'technical': ['technical', 'ticket', 'support logs', 'kỹ sư', 'sla', 'demo', 'poc'],
                        'super_admin': ['admin', 'người dùng', 'roles', 'phân quyền', 'cost formulas']
                    };

                    for (const [roleId, kws] of Object.entries(roleKeywords)) {
                        if (kws.some(k => q.includes(k) || k.includes(q))) {
                            this.activeRole = roleId;
                            break;
                        }
                    }
                    this.matchedCount = document.querySelectorAll('.step-card').length;
                }
            }
        }
    </script>
@endsection