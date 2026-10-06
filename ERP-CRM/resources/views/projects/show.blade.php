@extends('layouts.app')

@section('title', 'Chi tiết dự án')
@section('page-title', "Chi tiết dự án: {$project->code}")

@section('content')
@php
    $canViewCommercial = auth()->user()->canViewCommercialPrice($project->manager_id);
@endphp
<div class="space-y-6">
    @if($project->registration_status === 'duplicate')
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg text-red-800 flex items-start gap-3 shadow-sm">
            <i class="fas fa-exclamation-triangle text-red-500 text-xl mt-0.5"></i>
            <div>
                <h4 class="font-bold text-sm">DỰ ÁN BỊ TRÙNG LẶP ĐĂNG KÝ (DUPLICATE)</h4>
                <p class="text-xs mt-1">Dự án này bị trùng thông tin với dự án đã tồn tại trên hệ thống. Tính năng chỉnh sửa đã bị khóa để bảo toàn dữ liệu. Vui lòng liên hệ PM/PO Team để được hỗ trợ xử lý.</p>
            </div>
        </div>
    @elseif((($project->intake_status === 'registered') || in_array($project->registration_status, ['update_status', 'vendor_quoted'], true)) && !auth()->user()->hasAnyRole(['admin', 'super_admin', 'pm', 'po']))
        <div class="p-3 bg-blue-50 border-l-4 border-blue-500 rounded-r-lg text-blue-800 flex items-start gap-3 shadow-sm">
            <i class="fas fa-lock text-blue-500 mt-0.5"></i>
            <div>
                <p class="text-xs font-medium">Dự án đã được duyệt ĐKDA thành công nên đã khóa chỉnh sửa trực tiếp đối với Sales. Nếu cần điều chỉnh hoặc gửi thêm thông tin, vui lòng sử dụng mục Thảo luận/Ghi chú hoặc liên hệ PO/PM.</p>
            </div>
        </div>
    @endif

    <!-- Actions Toolbar -->
    <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
        <!-- Left Side: Back Button -->
        <div class="flex items-center gap-2">
            <a href="{{ route('projects.index') }}" 
               class="inline-flex items-center px-3.5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium text-sm whitespace-nowrap">
                <i class="fas fa-arrow-left mr-2"></i> Quay lại
            </a>
        </div>

        <!-- Right Side: Action Buttons (Grouped by role/function) -->
        <div class="flex flex-wrap items-center gap-3 justify-end">
            
            <!-- Nhóm 1: Công cụ & Chỉnh sửa -->
            <div class="flex items-center gap-2 bg-gray-50 p-1 rounded-lg border border-gray-150">
                <!-- Duplicate Button -->
                <a href="{{ route('projects.duplicate', $project->id) }}" 
                   class="inline-flex items-center px-3 py-1.5 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 transition-colors font-medium text-xs shadow-sm whitespace-nowrap"
                   title="Nhân bản dữ liệu sang dự án mới để đăng ký hãng khác">
                    <i class="fas fa-copy mr-1"></i> Nhân bản
                </a>

                <!-- Edit Button (ĐKDA 2 & 7) -->
                @php
                    $currentUser = auth()->user();
                    $isPoOrAdmin = $currentUser?->hasAnyRole(['admin', 'super_admin', 'pm', 'po']);
                    $isDuplicateDeal = ($project->registration_status === 'duplicate');
                    $isApprovedDeal = ($project->intake_status === 'registered') || in_array($project->registration_status, ['update_status', 'vendor_quoted'], true);
                    $canDirectEdit = ($isPoOrAdmin || (!$isDuplicateDeal && (in_array($project->registration_status, ['submitted', 'incomplete', 'pending']) || $project->intake_status === 'incomplete') && $project->status !== 'cancelled')) && !in_array($project->registration_status, ['vendor_rejected', 'closed_won', 'closed_lost', 'cancelled', 'expired'], true);
                @endphp
                @if($canDirectEdit)
                    <a href="{{ route('projects.edit', $project->id) }}" 
                       class="inline-flex items-center px-3 py-1.5 bg-amber-500 text-white rounded-md hover:bg-amber-600 transition-colors font-medium text-xs shadow-sm whitespace-nowrap"
                       title="Chỉnh sửa thông tin dự án">
                        <i class="fas fa-edit mr-1"></i> Sửa
                    </a>
                @else
                    <span class="inline-flex items-center px-3 py-1.5 bg-gray-200 text-gray-500 rounded-md font-medium text-xs cursor-not-allowed opacity-90 whitespace-nowrap"
                       title="{{ $isDuplicateDeal ? 'Dự án trùng lặp - Đã khóa sửa (PM/PO có thể hoàn trả để mở lại)' : ($isApprovedDeal ? 'Dự án đã duyệt - Đã khóa sửa với Sales (dùng chức năng Nhân bản nếu muốn điều chỉnh)' : 'Dự án đã đóng hoặc từ chối') }}">
                        <i class="fas fa-lock mr-1 text-gray-400"></i> Đã khóa sửa
                    </span>
                @endif
                
                <!-- Vertical Template for Email (ĐKDA 8) -->
                <button type="button" onclick="openModal('verticalVendorTemplateModal')"
                   class="inline-flex items-center px-3 py-1.5 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-md hover:bg-indigo-100 transition-colors font-medium text-xs shadow-sm whitespace-nowrap"
                   title="Xem mẫu đăng ký dự án dạng dọc để sao chép gửi email cho Hãng">
                    <i class="fas fa-file-alt mr-1"></i> Mẫu gửi Hãng (Dọc)
                </button>

                <!-- 1-Click Excel Export for Vendor -->
                <a href="{{ route('projects.export-vendor-excel', $project->id) }}" 
                   class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors font-medium text-xs shadow-sm whitespace-nowrap"
                   title="Xuất file Excel Đăng ký dự án dạng dọc gửi Hãng">
                    <i class="fas fa-file-excel mr-1 text-emerald-600"></i> Xuất Excel gửi Hãng
                </a>
            </div>

            <!-- Nhóm 2: Tác vụ Sales (Đơn hàng / Tiến độ) -->
            @if(in_array($project->registration_status, ['update_status', 'vendor_quoted', 'registered']) || !in_array($project->registration_status, ['closed_won', 'closed_lost', 'cancelled', 'expired']))
            <div class="flex items-center gap-2 bg-blue-50/50 p-1 rounded-lg border border-blue-100">
                <!-- Create Order Button -->
                <a href="{{ route('sales.create', ['project_id' => $project->id]) }}" 
                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors font-medium text-xs shadow-sm whitespace-nowrap">
                    <i class="fas fa-plus mr-1"></i> Tạo đơn hàng
                </a>
                @if(auth()->user()->can('create', \App\Models\Quotation::class) || auth()->user()->hasAnyRole(['super_admin', 'admin', 'sales_manager', 'sales_staff', 'order_management']) || in_array(auth()->user()->department, ['Sales', 'BU1', 'BU2', 'BU3', 'Kinh doanh']) || auth()->user()->can('create_quotations'))
                    <a href="{{ route('quotations.create', ['project_id' => $project->id]) }}"
                       class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors font-medium text-xs shadow-sm whitespace-nowrap">
                        <i class="fas fa-file-invoice mr-1"></i> Tạo báo giá
                    </a>
                @endif
                @can('create_technical_tickets')
                    <a href="{{ route('technical-tickets.create', ['project_id' => $project->id]) }}"
                       class="inline-flex items-center px-3 py-1.5 bg-sky-600 text-white rounded-md hover:bg-sky-700 transition-colors font-medium text-xs shadow-sm whitespace-nowrap">
                        <i class="fas fa-tools mr-1"></i> Tạo Ticket Kỹ thuật
                    </a>
                @endcan
                
                <!-- Sales Actions -->
                @if(in_array($project->registration_status, ['update_status', 'vendor_quoted', 'registered']))
                    <button type="button" onclick="openModal('monthlyUpdateModal')"
                        class="inline-flex items-center px-3 py-1.5 bg-cyan-600 text-white rounded-md hover:bg-cyan-700 transition-colors font-medium text-xs shadow-sm whitespace-nowrap">
                        <i class="fas fa-sync-alt mr-1"></i> Update tiến độ
                    </button>
                @endif
            </div>
            @endif

            <!-- Nhóm 3: Tác vụ Quản lý & Vận hành (PO/PM Team) -->
            <div class="flex flex-wrap items-center gap-2 bg-purple-50/40 p-1 rounded-lg border border-purple-100/50">
                <!-- PM / PO Quote & Vendor Actions -->
                @if(in_array(auth()->user()->department, ['PM', 'PO', 'PM Team', 'PO Team']) || auth()->user()->hasAnyRole(['super_admin', 'admin', 'purchase_manager', 'purchase_staff']))
                    @if(in_array($project->registration_status, ['vendor_processing', 'vendor_reminded', 'registered', 'processing', 'vendor_quoted', 'update_status']))
                        <!-- Submit Vendor Quote Modal Button -->
                        <button type="button" onclick="openModal('vendorQuoteModal')"
                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors font-medium text-xs shadow-sm whitespace-nowrap">
                            <i class="fas fa-file-invoice-dollar mr-1"></i> {{ $project->vendorQuoteVersions->count() > 0 ? 'Cập nhật Báo giá Hãng' : 'Đính kèm Báo giá Hãng' }}
                        </button>
                    @endif
                    @if(in_array($project->registration_status, ['vendor_processing', 'vendor_reminded', 'registered', 'processing']))
                        <!-- Remind Vendor SLA Button -->
                        <button type="button" onclick="openModal('remindVendorModal')"
                            class="inline-flex items-center px-3 py-1.5 bg-orange-500 text-white rounded-md hover:bg-orange-600 transition-colors font-medium text-xs shadow-sm whitespace-nowrap">
                            <i class="fas fa-clock mr-1"></i> Hãng chưa phản hồi (+3d SLA)
                        </button>
                        <!-- Complete Registration Button -->
                        <form action="{{ route('projects.complete-registration', $project->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" onclick="return confirm('Xác nhận hoàn tất đăng ký dự án và chuyển sang giai đoạn Update Status?')"
                                class="inline-flex items-center px-3 py-1.5 bg-teal-600 text-white rounded-md hover:bg-teal-700 transition-colors font-medium text-xs shadow-sm whitespace-nowrap">
                                <i class="fas fa-check-double mr-1"></i> Hoàn tất ĐKDA
                            </button>
                        </form>
                    @endif

                    <!-- Back to Duplicate Button for PO / PM Team -->
                    @if(in_array($project->registration_status, ['update_status', 'registered', 'vendor_quoted', 'vendor_processing', 'vendor_reminded']))
                        <button type="button" onclick="openIntakeModal('duplicate')"
                            class="inline-flex items-center px-3 py-1.5 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition-colors font-medium text-xs shadow-sm whitespace-nowrap"
                            title="PO/PM phát hiện hoặc Hãng báo trùng - Back về Dự án trùng & ghi chú lý do trùng">
                            <i class="fas fa-copy mr-1"></i> Báo trùng dự án
                        </button>
                    @endif
                @endif

                <!-- Close Project Modal Button -->
                @if(!in_array($project->registration_status, ['closed_won', 'closed_lost', 'cancelled', 'expired']))
                    <button type="button" onclick="openModal('closeProjectModal')"
                        class="inline-flex items-center px-3 py-1.5 bg-rose-600 text-white rounded-md hover:bg-rose-700 transition-colors font-medium text-xs shadow-sm whitespace-nowrap">
                        <i class="fas fa-times-circle mr-1"></i> Đóng dự án
                    </button>
                @endif

                <!-- Restore Button for Expired -->
                @if($project->registration_status === 'expired' && (in_array(auth()->user()->department, ['PM', 'PO']) || auth()->user()->hasAnyRole(['super_admin', 'admin', 'sales_manager', 'purchase_manager', 'purchase_staff'])))
                    <form action="{{ route('projects.restore', $project->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" onclick="return confirm('Khôi phục dự án này trở lại trạng thái hoạt động?')"
                            class="inline-flex items-center px-3 py-1.5 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors font-medium text-xs shadow-sm whitespace-nowrap">
                            <i class="fas fa-undo mr-1"></i> Khôi phục dự án
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @php
        $isClosedOrRejected = in_array($project->registration_status, ['rejected', 'duplicate', 'cancelled', 'expired', 'closed_lost', 'closed_won']) 
            || in_array($project->status, ['cancelled', 'completed']);
    @endphp

    <!-- SLA & Intake Banners -->
    @if(!$isClosedOrRejected && $project->intake_status === 'pending' && $project->registration_status === 'submitted' && (in_array(auth()->user()->department, ['PM', 'PO', 'PM Team', 'PO Team']) || auth()->user()->hasAnyRole(['super_admin', 'admin', 'purchase_manager', 'purchase_staff'])))
        <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border-2 border-purple-300 rounded-xl p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-purple-600 text-white rounded-xl flex items-center justify-center text-xl shadow-md">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-purple-900">TIẾP NHẬN TICKET ĐĂNG KÝ DỰ ÁN (Phân luồng: {{ $project->assigned_team === 'po_team' ? 'PO Team (FTN)' : 'PM Team (Non-FTN)' }})</h3>
                        <p class="text-xs text-purple-700 mt-1">Hạn SLA tiếp nhận 4 giờ: {!! $project->initial_sla_status['label'] !!}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" onclick="openIntakeModal('registered')"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all font-semibold text-sm shadow-sm flex items-center gap-1.5">
                        <i class="fas fa-check-circle"></i> 1. Đã đăng ký dự án
                    </button>
                    <button type="button" onclick="openIntakeModal('duplicate')"
                        class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-all font-semibold text-sm shadow-sm flex items-center gap-1.5">
                        <i class="fas fa-copy"></i> 2. Dự án trùng
                    </button>
                    <button type="button" onclick="openIntakeModal('incomplete')"
                        class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-all font-semibold text-sm shadow-sm flex items-center gap-1.5"
                        title="Hoàn trả để Sales chỉnh sửa bổ sung thông tin trên chính form hiện tại">
                        <i class="fas fa-undo"></i> 3. Hoàn trả bổ sung
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($project->registration_status === 'incomplete' || $project->intake_status === 'incomplete')
        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border-2 border-amber-300 rounded-xl p-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-amber-500 text-white rounded-xl flex items-center justify-center text-xl shadow-md flex-shrink-0">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-amber-900">DỰ ÁN ĐÃ ĐƯỢC HOÀN TRẢ / YÊU CẦU BỔ SUNG THÔNG TIN</h3>
                        <p class="text-xs text-amber-800 mt-1 font-medium">Ghi chú yêu cầu bổ sung từ PM/PO Team:</p>
                        <p class="text-xs text-amber-700 bg-white p-3 rounded-lg border border-amber-200 mt-1.5 italic font-mono">"{{ $project->intake_note ?? 'Vui lòng bổ sung đầy đủ thông tin để đăng ký dự án.' }}"</p>
                    </div>
                </div>
                <div>
                    <a href="{{ route('projects.edit', $project->id) }}" 
                       class="px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-all font-semibold text-sm shadow-sm flex items-center gap-1.5 whitespace-nowrap">
                        <i class="fas fa-edit"></i> Bổ sung thông tin ngay
                    </a>
                </div>
            </div>
        </div>
    @endif
    @if($project->registration_status === 'duplicate')
        <div class="bg-gradient-to-r from-orange-50 to-red-50 border-2 border-orange-300 rounded-xl p-5 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-orange-600 text-white rounded-xl flex items-center justify-center text-xl shadow-md flex-shrink-0">
                    <i class="fas fa-copy"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-bold text-orange-950 flex items-center gap-2">
                        <span>THÔNG BÁO DỰ ÁN TRÙNG ĐĂNG KÝ (ĐÃ HỦY)</span>
                        <span class="px-2 py-0.5 bg-red-100 text-red-800 text-xs font-semibold rounded-full border border-red-200">Đã khóa</span>
                    </h3>
                    <p class="text-xs text-orange-800 mt-1">Dự án này đã bị PO/PM Team từ chối tiếp nhận do trùng lặp thông tin với đối tác hoặc Sales khác.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3 bg-white p-3.5 rounded-lg border border-orange-200">
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">1. Thông tin Sales nội bộ đang làm trùng:</span>
                            <p class="text-sm font-medium text-gray-900 mt-1 font-mono">
                                {{ $project->duplicate_sales_info ?: 'Chưa có thông tin cụ thể' }}
                            </p>
                            <span class="block text-[11px] text-gray-400 mt-0.5 italic">* Bảo mật: Không công khai nội dung BOM/giá dự án</span>
                        </div>
                        <div>
                            <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">2. Thông tin Sales Hãng (Vendor Rep) đang làm:</span>
                            <p class="text-sm font-medium text-gray-900 mt-1 font-mono">
                                {{ $project->duplicate_vendor_sales_info ?: 'Chưa cập nhật' }}
                            </p>
                        </div>
                        @if($project->intake_note)
                            <div class="col-span-full border-t border-orange-100 pt-2.5 mt-1">
                                <span class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">3. Ghi chú lý do trùng từ PO/PM Team:</span>
                                <p class="text-sm font-medium text-gray-900 mt-1 italic bg-amber-50 p-2.5 rounded border border-amber-200">
                                    "{{ $project->intake_note }}"
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="mt-3 flex items-center gap-2 flex-wrap">
                        @if(auth()->user()->hasAnyRole(['super_admin', 'admin', 'pm', 'po']) || in_array(auth()->user()->department, ['PM', 'PO', 'PM Team', 'PO Team']))
                            <button type="button" onclick="openModal('reopenIntakeModal')"
                                class="inline-flex items-center px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-all font-semibold text-xs shadow-sm gap-1"
                                title="Mở khóa và hoàn trả lại để Sales chỉnh sửa trực tiếp">
                                <i class="fas fa-undo"></i> Hoàn trả cho Sales sửa lại
                            </button>
                        @endif
                        <form action="{{ route('projects.duplicate', $project->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" onclick="return confirm('Xác nhận nhân bản thông tin dự án này sang một dự án mới?')"
                                class="inline-flex items-center px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-all font-semibold text-xs shadow-sm gap-1">
                                <i class="fas fa-copy"></i> Tạo dự án mới từ thông tin này (Nhân bản)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($project->registration_status === 'expired')
        <div class="bg-gradient-to-r from-slate-100 to-rose-50 border-2 border-rose-300 rounded-xl p-5 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-slate-800 text-white rounded-xl flex items-center justify-center text-xl shadow-md flex-shrink-0">
                    <i class="fas fa-history"></i>
                </div>
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-base font-bold text-slate-900">DỰ ÁN ĐÃ HẾT HẠN BẢO HỘ (EXPIRED)</h3>
                        <span class="px-2.5 py-0.5 bg-rose-600 text-white text-xs font-semibold rounded-full shadow-sm">Quá 3 tháng không cập nhật tiến độ</span>
                        <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 text-xs font-semibold rounded-full border border-emerald-300">Đã mở quyền đăng ký cho Sales khác</span>
                    </div>
                    <p class="text-xs text-slate-700 mt-2 leading-relaxed">
                        Dự án này đã vượt quá thời hạn 90 ngày (3 tháng) không có hoạt động cập nhật tiến độ từ Sales phụ trách. Theo quy tắc bảo hộ đăng ký dự án, hệ thống đã tự động chuyển sang trạng thái <strong>Hết hạn (Expired)</strong> và <strong>mở quyền đăng ký dự án / End User này cho các Sales khác</strong>.
                    </p>
                    @if($project->note)
                        <div class="mt-2.5 bg-white p-3 rounded-lg border border-slate-200 text-xs text-slate-600 font-mono">
                            {!! nl2br(e($project->note)) !!}
                        </div>
                    @endif
                    <div class="mt-3 flex items-center gap-2 flex-wrap">
                        @if(in_array(auth()->user()->department, ['PM', 'PO']) || auth()->user()->hasAnyRole(['super_admin', 'admin', 'sales_manager', 'purchase_manager', 'purchase_staff']))
                            <form action="{{ route('projects.restore', $project->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" onclick="return confirm('Khôi phục dự án này trở lại trạng thái hoạt động?')"
                                    class="inline-flex items-center px-3.5 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-all font-semibold text-xs shadow-sm gap-1">
                                    <i class="fas fa-undo"></i> Khôi phục dự án (Restore)
                                </button>
                            </form>
                        @endif
                        <form action="{{ route('projects.duplicate', $project->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" onclick="return confirm('Xác nhận đăng ký lại dự án này sang một ticket mới?')"
                                class="inline-flex items-center px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-all font-semibold text-xs shadow-sm gap-1">
                                <i class="fas fa-copy"></i> Đăng ký lại dự án (Nhân bản)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @elseif($project->registration_status === 'rejected' || ($project->status === 'cancelled' && !in_array($project->registration_status, ['duplicate', 'expired'])))
        <div class="bg-gradient-to-r from-red-50 to-rose-50 border-2 border-red-300 rounded-xl p-5 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-red-600 text-white rounded-xl flex items-center justify-center text-xl shadow-md flex-shrink-0">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-base font-bold text-red-950 flex items-center gap-2">
                        <span>DỰ ÁN ĐÃ TỪ CHỐI / ĐÃ HỦY (KHÓA CHỈNH SỬA)</span>
                        <span class="px-2 py-0.5 bg-red-100 text-red-800 text-xs font-semibold rounded-full border border-red-200">Đã khóa</span>
                    </h3>
                    <p class="text-xs text-red-800 mt-1">Dự án này đã bị hủy hoặc từ chối và không thể chỉnh sửa trực tiếp.</p>
                    @if($project->close_reason || $project->close_note || $project->intake_note)
                        <div class="mt-2 bg-white p-3 rounded-lg border border-red-200 text-xs text-red-700">
                            <strong>Lý do:</strong> {{ $project->close_reason ?: ($project->close_note ?: $project->intake_note) }}
                        </div>
                    @endif
                    <div class="mt-3">
                        <form action="{{ route('projects.duplicate', $project->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" onclick="return confirm('Xác nhận nhân bản thông tin dự án này sang một dự án mới?')"
                                class="inline-flex items-center px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition-all font-semibold text-xs shadow-sm gap-1">
                                <i class="fas fa-copy"></i> Tạo dự án mới (Nhân bản)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if(!$isClosedOrRejected && $project->is_sales_update_overdue)
        <div class="bg-amber-50 border-2 border-amber-400 rounded-xl p-4 flex flex-wrap items-center justify-between gap-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-500 text-white rounded-lg flex items-center justify-center text-lg flex-shrink-0">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h4 class="font-bold text-amber-900 text-sm flex items-center gap-2">
                        <span>CẢNH BÁO: ĐÃ QUÁ 30 NGÀY CHƯA CẬP NHẬT TIẾN ĐỘ DỰ ÁN</span>
                        <span class="px-2 py-0.5 bg-amber-200 text-amber-900 text-xs rounded-full font-semibold">Cần Sales update</span>
                    </h4>
                    <p class="text-xs text-amber-800 mt-0.5">
                        Dự án đã quá 30 ngày kể từ lần cập nhật gần nhất. Vui lòng bấm <strong>Update tiến độ</strong> để duy trì hiệu lực bảo hộ. Dự án không có cập nhật trong 90 ngày (3 tháng) sẽ tự động bị <strong>Hết hạn (Expired)</strong> và nhường quyền đăng ký cho Sales khác.
                    </p>
                </div>
            </div>
            <div>
                <button type="button" onclick="openModal('monthlyUpdateModal')"
                    class="px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-colors font-semibold text-xs shadow-sm flex items-center gap-1.5 whitespace-nowrap">
                    <i class="fas fa-sync-alt"></i> Update tiến độ ngay
                </button>
            </div>
        </div>
    @endif

    @if($project->is_vendor_overdue)
        <div class="bg-red-50 border-2 border-red-400 rounded-xl p-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                <div>
                    <h4 class="font-bold text-red-900 text-sm">CẢNH BÁO QUÁ HẠN HÃNG PHẢN HỒI!</h4>
                    <p class="text-xs text-red-700">Đã quá {{ $project->vendor_sla_status['label'] }} nhưng Hãng chưa có báo giá/duyệt. Vui lòng giục Hãng hoặc bấm "Hãng chưa phản hồi" để gia hạn SLA.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Statistics Cards Row -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Revenue -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium text-gray-500">Doanh thu</p>
                <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-coins text-blue-500 text-sm"></i>
                </div>
            </div>
            @if($canViewCommercial)
                <p class="text-xl font-bold text-gray-900">{{ number_format($salesStats['total_revenue']) }} đ</p>
                <p class="text-xs text-gray-400 mt-1">{{ $salesStats['total_orders'] }} đơn hàng</p>
            @else
                <p class="text-xl font-bold text-gray-400 font-mono italic">***</p>
                <p class="text-xs text-purple-600 mt-1"><i class="fas fa-shield-alt mr-1"></i>{{ $salesStats['total_orders'] }} đơn (Bảo mật Sales)</p>
            @endif
        </div>

        <!-- Cost -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium text-gray-500">Giá vốn</p>
                <div class="w-9 h-9 bg-orange-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-box text-orange-500 text-sm"></i>
                </div>
            </div>
            @if($canViewCommercial)
                <p class="text-xl font-bold text-gray-900">{{ number_format($salesStats['total_cost']) }} đ</p>
            @else
                <p class="text-xl font-bold text-gray-400 font-mono italic">***</p>
                <p class="text-xs text-gray-400 mt-1">Bảo mật Sales</p>
            @endif
        </div>

        <!-- Profit -->
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium text-gray-500">Lợi nhuận</p>
                <div class="w-9 h-9 {{ $salesStats['profit'] >= 0 ? 'bg-green-50' : 'bg-red-50' }} rounded-lg flex items-center justify-center">
                    <i class="fas fa-chart-line {{ $salesStats['profit'] >= 0 ? 'text-green-500' : 'text-red-500' }} text-sm"></i>
                </div>
            </div>
            @if($canViewCommercial)
                <p class="text-xl font-bold {{ $salesStats['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($salesStats['profit']) }} đ</p>
                <p class="text-xs text-gray-400 mt-1">{{ number_format($salesStats['profit_percent'], 2) }}% margin</p>
            @else
                <p class="text-xl font-bold text-gray-400 font-mono italic">***</p>
                <p class="text-xs text-gray-400 mt-1">Bảo mật Sales</p>
            @endif
        </div>

        <!-- Budget Progress or Debt -->
        @if($project->budget > 0)
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium text-gray-500">Dự toán vs Thực tế</p>
            </div>
            @php
                $budgetPercent = $project->budget > 0 ? min(($salesStats['total_revenue'] / $project->budget) * 100, 100) : 0;
            @endphp
            @if($canViewCommercial)
                <p class="text-xl font-bold text-gray-900">{{ number_format($budgetPercent, 1) }}%</p>
                <div class="w-full bg-gray-100 rounded-full h-2 mt-2">
                    <div class="h-2 rounded-full {{ $budgetPercent >= 100 ? 'bg-green-500' : 'bg-blue-500' }} transition-all" 
                         style="width: {{ $budgetPercent }}%"></div>
                </div>
                <div class="flex justify-between text-xs text-gray-400 mt-1.5">
                    <span>{{ number_format($salesStats['total_revenue']) }} đ</span>
                    <span>{{ number_format($project->budget) }} đ</span>
                </div>
            @else
                <p class="text-xl font-bold text-gray-900">{{ number_format($project->budget) }} đ</p>
                <p class="text-xs text-gray-400 mt-1">Dự toán dự án</p>
            @endif
        </div>
        @else
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium text-gray-500">Công nợ</p>
                <div class="w-9 h-9 {{ $salesStats['total_debt'] > 0 ? 'bg-red-50' : 'bg-green-50' }} rounded-lg flex items-center justify-center">
                    <i class="fas {{ $salesStats['total_debt'] > 0 ? 'fa-file-invoice-dollar text-red-500' : 'fa-check-circle text-green-500' }} text-sm"></i>
                </div>
            </div>
            @if($canViewCommercial)
                <p class="text-xl font-bold {{ $salesStats['total_debt'] > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ number_format($salesStats['total_debt']) }} đ</p>
                @if($salesStats['total_debt'] == 0)
                    <p class="text-xs text-green-500 mt-1">Không có công nợ</p>
                @endif
            @else
                <p class="text-xl font-bold text-gray-400 font-mono italic">***</p>
                <p class="text-xs text-gray-400 mt-1">Bảo mật Sales</p>
            @endif
        </div>
        @endif
    </div>

    <!-- Project Information Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Section A: Distributor Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-building mr-2 text-blue-500"></i> Thông tin Nhà phân phối
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-3.5">
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Vendor</span>
                        <span class="text-sm font-medium text-gray-800">{{ $project->vendor?->name ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <span class="text-sm text-gray-500">Distributor</span>
                        <span class="text-sm font-medium text-gray-800">Tech Horizon Corporation</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-sm text-gray-500">Distributor AM</span>
                        <span class="text-sm font-medium text-gray-800">{{ $project->distributor_am ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section B: End-User Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-user-tie mr-2 text-green-500"></i> Thông tin End-User
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Tên tiếng Việt</p>
                        <p class="text-sm font-medium text-gray-800">{{ $project->eu_name_vi ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Tên tiếng Anh</p>
                        <p class="text-sm font-medium text-gray-800">{{ $project->eu_name_en ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Tên viết tắt</p>
                        <p class="text-sm text-gray-700">{{ $project->eu_name_abbr ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">MST / Website</p>
                        <p class="text-sm font-medium text-gray-800">{{ $project->eu_tax_code ?? '-' }}</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs text-gray-400 mb-1">Địa chỉ</p>
                        <p class="text-sm text-gray-700">{{ $project->address ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Tỉnh / Thành phố</p>
                        <p class="text-sm text-gray-700">{{ $project->eu_province ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Ngành nghề</p>
                        <p class="text-sm text-gray-700">{{ $project->eu_industry ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section C: Collaboration -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-handshake mr-2 text-purple-500"></i> Thông tin Hợp tác
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Loại hợp tác</p>
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full {{ $project->collaborate_type == 'partner' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                            {{ $project->collaborate_type == 'partner' ? 'Partner' : 'End-user' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Tên công ty (Partner)</p>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ $project->collaborateCustomer?->name_en ?: ($project->collaborate_company ?: ($project->collaborateCustomer?->name ?: '-')) }}
                        </p>
                        @if($project->collaborateCustomer && $project->collaborateCustomer->name_en && $project->collaborateCustomer->name && $project->collaborateCustomer->name_en !== $project->collaborateCustomer->name)
                            <span class="block text-xs text-gray-500 font-normal">({{ $project->collaborateCustomer->name }})</span>
                        @endif
                        @if($project->collaborateCustomer?->pos_id)
                            <span class="inline-block mt-1 px-1.5 py-0.5 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded text-[10px] font-semibold">POS-ID: {{ $project->collaborateCustomer->pos_id }}</span>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Mã số thuế</p>
                        <p class="text-sm text-gray-700">{{ $project->collaborate_tax_code ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Người liên hệ (PIC)</p>
                        <p class="text-sm text-gray-700">
                            @if($project->collaborate_pic_name)
                                <span class="font-medium">{{ $project->collaborate_pic_name }}</span>
                                @if($project->collaborate_pic_title)
                                    <span class="text-gray-400 mx-1">|</span>{{ $project->collaborate_pic_title }}
                                @endif
                            @else
                                -
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">SĐT PIC</p>
                        <p class="text-sm text-gray-700">
                            @if($project->collaborate_pic_phone)
                                <a href="tel:{{ $project->collaborate_pic_phone }}" class="text-blue-600 hover:underline">{{ $project->collaborate_pic_phone }}</a>
                            @else - @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Email PIC</p>
                        <p class="text-sm text-gray-700">
                            @if($project->collaborate_pic_email)
                                <a href="mailto:{{ $project->collaborate_pic_email }}" class="text-blue-600 hover:underline">{{ $project->collaborate_pic_email }}</a>
                            @else - @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section D: Project Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
                <h3 class="text-sm font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-project-diagram mr-2 text-orange-500"></i> Thông tin Dự án
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Mã dự án</p>
                        <p class="text-sm font-bold text-gray-900">{{ $project->code }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Trạng thái</p>
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full {{ $project->status_color }}">
                            {{ $project->status_label }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Ngày đăng ký</p>
                        <p class="text-sm text-gray-700 font-medium">{{ $project->created_at ? $project->created_at->format('d/m/Y H:i') : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Người đăng ký</p>
                        <p class="text-sm text-gray-700 font-medium">{{ $project->manager->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Hãng phản hồi (SLA)</p>
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full {{ $project->vendor_sla_status['color'] }}">
                            {{ $project->vendor_sla_status['label'] }}
                        </span>
                    </div>
                    <div class="col-span-2">
                        <p class="text-xs text-gray-400 mb-1">Tên dự án</p>
                        <p class="text-sm font-medium text-gray-800">{{ $project->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Ngày hết hạn (Expired Date)</p>
                        <p class="text-sm text-gray-700">
                            {{ $project->end_date?->format('d/m/Y') ?? '-' }}
                            @if($project->estimated_close_months)
                                <span class="text-xs text-gray-400 ml-1">(+{{ $project->estimated_close_months }}M)</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Loại Deal</p>
                        @if($project->deal_type)
                            <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-700">
                                {{ $project->deal_type == 'new_buy' ? 'New Buy' : 'Trade Up' }}
                            </span>
                        @else
                            <p class="text-sm text-gray-400">-</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Net to Tech Horizon</p>
                        <p class="text-sm font-medium text-gray-800">{{ $project->net_to_tech_horizon ? number_format($project->net_to_tech_horizon) . ' đ' : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 mb-1">Dự toán / Ngân sách</p>
                        <p class="text-sm font-medium text-gray-800">{{ number_format($project->budget) }} đ</p>
                    </div>
                    @if($project->stage)
                    <div class="col-span-2">
                        <p class="text-xs text-gray-400 mb-1">Giai đoạn (Stage)</p>
                        <p class="text-sm text-gray-700">{{ $project->stage }}</p>
                    </div>
                    @endif

                    @if($project->po_code || (isset($recentSales) && $recentSales->count() > 0))
                    <div class="col-span-2 bg-emerald-50/70 border border-emerald-200 rounded-xl p-3 flex items-center justify-between">
                        <div class="flex items-center space-x-2.5">
                            <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-emerald-600 text-white text-xs">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </span>
                            <div>
                                <span class="text-[11px] text-gray-500 font-medium block">Đơn hàng bán liên kết (SO):</span>
                                <span class="font-mono font-bold text-emerald-800 text-sm">
                                    {{ $project->po_code ?: $recentSales->first()?->code }}
                                </span>
                            </div>
                        </div>
                        @if(isset($recentSales) && $recentSales->first())
                            <a href="{{ route('sales.show', $recentSales->first()->id) }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-900 bg-white px-3 py-1.5 rounded-lg border border-emerald-300 shadow-xs hover:bg-emerald-50 transition-colors">
                                Xem chi tiết đơn <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        @endif
                    </div>
                    @endif
                    @if($project->opportunities && $project->opportunities->count() > 0)
                    <div class="col-span-2 border-t border-gray-100 pt-3 mt-1">
                        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-2">Cơ hội phát sinh (CRM Origin):</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach($project->opportunities as $opp)
                                <a href="{{ route('opportunities.show', $opp->id) }}" 
                                   class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-lg text-xs font-bold transition-all">
                                    <i class="fas fa-calendar-check text-blue-500"></i>
                                    [{{ $opp->activity_type_label }}] {{ $opp->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- BOM Section -->
    @if($project->bom_file || $project->bom_data)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-3 border-b border-gray-100 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-700 flex items-center">
                <i class="fas fa-file-alt mr-2 text-blue-500"></i> BOM (Bill of Materials)
            </h3>
        </div>
        <div class="p-6 space-y-4">
            @if($project->bom_file)
                @php
                    $files = is_array($project->bom_file) ? $project->bom_file : [$project->bom_file];
                @endphp
                <div class="space-y-3">
                    @foreach($files as $file)
                        @if(empty($file)) @continue @endif
                        @php
                            $filename = basename($file);
                            if (preg_match('/^\d+_(.+)$/', $filename, $matches)) {
                                $displayFilename = $matches[1];
                            } else {
                                $displayFilename = $filename;
                            }
                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            $icon = 'fa-file-alt';
                            $color = 'text-blue-500';
                            if (in_array($ext, ['xls', 'xlsx'])) {
                                $icon = 'fa-file-excel';
                                $color = 'text-green-600';
                            } elseif ($ext === 'pdf') {
                                $icon = 'fa-file-pdf';
                                $color = 'text-red-500';
                            } elseif (in_array($ext, ['doc', 'docx'])) {
                                $icon = 'fa-file-word';
                                $color = 'text-blue-600';
                            }
                        @endphp
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-100 gap-4">
                            <div class="flex items-center gap-3">
                                <i class="fas {{ $icon }} {{ $color }} text-3xl"></i>
                                <div>
                                    <p class="text-sm font-semibold text-gray-800">{{ $displayFilename }}</p>
                                    <p class="text-xs text-gray-500">Định dạng: {{ strtoupper($ext) }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2 self-stretch sm:self-auto">
                                <a href="javascript:void(0)" onclick="openFilePreviewModal('{{ Storage::url($file) }}', '{{ addslashes($displayFilename) }}')"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5 whitespace-nowrap justify-center">
                                    <i class="fas fa-eye"></i> Xem trước
                                </a>
                                <a href="{{ Storage::url($file) }}" download
                                    class="px-4 py-2 bg-white hover:bg-gray-50 text-blue-700 border border-blue-200 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5 whitespace-nowrap justify-center">
                                    <i class="fas fa-download"></i> Tải xuống
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
            @if($project->bom_data)
                <div>
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Thông tin BOM chi tiết:</h4>
                    <div class="text-sm bg-gray-50 p-4 rounded-lg border border-gray-200 whitespace-pre-line text-gray-700">{{ $project->bom_data }}</div>
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Description & Note -->
    @if($project->description || $project->note)
    <div class="grid grid-cols-1 {{ $project->description && $project->note ? 'lg:grid-cols-2' : '' }} gap-6">
        @if($project->description)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-semibold text-gray-700 flex items-center mb-3">
                <i class="fas fa-align-left mr-2 text-gray-400"></i> Mô tả
            </h3>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $project->description }}</p>
        </div>
        @endif
        @if($project->note)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-semibold text-gray-700 flex items-center mb-3">
                <i class="fas fa-sticky-note mr-2 text-gray-400"></i> Ghi chú
            </h3>
            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $project->note }}</p>
        </div>
        @endif
    </div>
    @endif

    <!-- Danh sách Báo giá & Đơn hàng đã tạo từ dự án -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex flex-wrap justify-between items-center gap-3">
            <div>
                <h3 class="text-base font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-file-invoice-dollar mr-2 text-indigo-600"></i> Báo giá & Đơn hàng liên kết
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">
                    @if($canViewCommercial)
                        Danh sách báo giá và đơn hàng được tạo cho dự án này.
                    @else
                        <span class="text-purple-600 font-medium"><i class="fas fa-shield-alt mr-1"></i>Bảo mật giá Sales:</span> Đơn giá và tổng tiền thương mại được ẩn với tài khoản PM/PO.
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if(auth()->user()->can('create', \App\Models\Quotation::class) || auth()->user()->hasAnyRole(['super_admin', 'admin', 'sales_manager', 'sales_staff', 'order_management']) || in_array(auth()->user()->department, ['Sales', 'BU1', 'BU2', 'BU3', 'Kinh doanh']) || auth()->user()->can('create_quotations'))
                    <a href="{{ route('quotations.create', ['project_id' => $project->id]) }}" 
                       class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium text-xs shadow-xs">
                        <i class="fas fa-plus mr-1"></i> Tạo báo giá mới
                    </a>
                @endif
                <a href="{{ route('sales.create', ['project_id' => $project->id]) }}" 
                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium text-xs shadow-xs">
                    <i class="fas fa-plus mr-1"></i> Tạo đơn hàng mới
                </a>
            </div>
        </div>
        <div class="p-6 space-y-6">
            <!-- 1. Quotations List -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center">
                        <i class="fas fa-file-invoice text-indigo-500 mr-1.5"></i> Danh sách Báo giá ({{ isset($quotations) ? $quotations->count() : 0 }})
                    </h4>
                </div>
                @if(isset($quotations) && $quotations->count() > 0)
                    <div class="overflow-x-auto border border-gray-100 rounded-xl">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-gray-50 text-gray-600 font-semibold border-b border-gray-100">
                                <tr>
                                    <th class="p-3">Mã báo giá</th>
                                    <th class="p-3">Tiêu đề</th>
                                    <th class="p-3">Khách hàng</th>
                                    <th class="p-3">Người tạo</th>
                                    <th class="p-3">Ngày tạo</th>
                                    <th class="p-3 text-right">Tổng tiền (gồm VAT)</th>
                                    <th class="p-3 text-center">Trạng thái</th>
                                    <th class="p-3 text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach($quotations as $quote)
                                    <tr class="hover:bg-gray-50/60 transition-colors">
                                        <td class="p-3 font-mono font-bold text-indigo-600">
                                            <a href="{{ route('quotations.show', $quote->id) }}" class="hover:underline">
                                                {{ $quote->code }}
                                            </a>
                                        </td>
                                        <td class="p-3 text-gray-800 font-medium">{{ $quote->title }}</td>
                                        <td class="p-3 text-gray-600">{{ $quote->customer?->name ?? 'N/A' }}</td>
                                        <td class="p-3 text-gray-600">{{ $quote->creator?->name ?? 'N/A' }}</td>
                                        <td class="p-3 text-gray-500">{{ $quote->date ? $quote->date->format('d/m/Y') : $quote->created_at->format('d/m/Y') }}</td>
                                        <td class="p-3 text-right font-bold text-gray-900">
                                            @if($canViewCommercial)
                                                {{ number_format($quote->total) }} {{ $quote->currency?->symbol ?? '₫' }}
                                            @else
                                                <span class="text-gray-400 font-mono italic" title="Giá chào được bảo mật với PM/PO">***</span>
                                            @endif
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $quote->status_color ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ $quote->status_label ?? ucfirst($quote->status) }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <a href="{{ route('quotations.show', $quote->id) }}" 
                                                   class="px-2 py-1 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded text-xs font-medium" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($canViewCommercial)
                                                <a href="{{ route('quotations.edit', $quote->id) }}" 
                                                   class="px-2 py-1 bg-amber-50 text-amber-600 hover:bg-amber-100 rounded text-xs font-medium" title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 bg-gray-50/50 rounded-xl border border-dashed text-xs text-gray-500">
                        <i class="fas fa-file-invoice text-xl text-gray-300 mb-1"></i>
                        <p>Chưa có báo giá nào được tạo cho dự án này.</p>
                    </div>
                @endif
            </div>

            <!-- 2. Sales Orders List -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center">
                        <i class="fas fa-shopping-cart text-blue-500 mr-1.5"></i> Danh sách Đơn hàng bán ({{ isset($recentSales) ? $recentSales->count() : 0 }})
                    </h4>
                </div>
                @if(isset($recentSales) && $recentSales->count() > 0)
                    <div class="overflow-x-auto border border-gray-100 rounded-xl">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-gray-50 text-gray-600 font-semibold border-b border-gray-100">
                                <tr>
                                    <th class="p-3">Mã đơn hàng</th>
                                    <th class="p-3">Khách hàng</th>
                                    <th class="p-3">Ngày đặt</th>
                                    <th class="p-3 text-right">Tổng tiền (gồm VAT)</th>
                                    <th class="p-3 text-center">Trạng thái đơn</th>
                                    <th class="p-3 text-center">Thanh toán</th>
                                    <th class="p-3 text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach($recentSales as $sale)
                                    <tr class="hover:bg-gray-50/60 transition-colors">
                                        <td class="p-3 font-mono font-bold text-blue-600">
                                            <a href="{{ route('sales.show', $sale->id) }}" class="hover:underline">
                                                {{ $sale->code }}
                                            </a>
                                        </td>
                                        <td class="p-3 text-gray-700 font-medium">{{ $sale->customer?->name ?? 'N/A' }}</td>
                                        <td class="p-3 text-gray-500">{{ $sale->date ? $sale->date->format('d/m/Y') : $sale->created_at->format('d/m/Y') }}</td>
                                        <td class="p-3 text-right font-bold text-gray-900">
                                            @if($canViewCommercial)
                                                {{ number_format($sale->total) }} đ
                                            @else
                                                <span class="text-gray-400 font-mono italic" title="Giá bán được bảo mật với PM/PO">***</span>
                                            @endif
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $sale->status_color ?? 'bg-blue-100 text-blue-800' }}">
                                                {{ $sale->status_label ?? ucfirst($sale->status) }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $sale->payment_status_color ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ $sale->payment_status_label ?? ucfirst($sale->payment_status) }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-center">
                                            <a href="{{ route('sales.show', $sale->id) }}" 
                                               class="px-2.5 py-1 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded text-xs font-medium" title="Xem chi tiết đơn">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 bg-gray-50/50 rounded-xl border border-dashed text-xs text-gray-500">
                        <i class="fas fa-shopping-cart text-xl text-gray-300 mb-1"></i>
                        <p>Chưa có đơn hàng nào được tạo cho dự án này.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Lịch sử trao đổi & Thảo luận giữa PM/PO & Sales -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <h3 class="text-base font-semibold text-gray-900 flex items-center">
                <i class="fas fa-comments mr-2 text-indigo-500"></i> Trao đổi & Thảo luận (PM & Sales)
            </h3>
            <span class="px-2.5 py-1 text-xs font-semibold bg-indigo-50 text-indigo-700 rounded-full">
                {{ $project->notes->count() }} phản hồi
            </span>
        </div>
        <div class="p-6 space-y-6">
            <!-- Notes List -->
            @if($project->notes->count() > 0)
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @foreach($project->notes as $index => $note)
                            <li>
                                <div class="relative pb-8">
                                    @if($index < $project->notes->count() - 1)
                                        <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex items-start space-x-3">
                                        <!-- User Icon based on role -->
                                        <div class="relative">
                                            @php
                                                $roleBg = 'bg-gray-400';
                                                $roleIcon = 'fa-user';
                                                if (in_array($note->user_role, ['pm', 'po'])) {
                                                    $roleBg = 'bg-purple-600';
                                                    $roleIcon = 'fa-user-shield';
                                                } elseif ($note->user_role === 'sales') {
                                                    $roleBg = 'bg-blue-600';
                                                    $roleIcon = 'fa-user-tie';
                                                }
                                            @endphp
                                            <span class="h-10 w-10 rounded-full flex items-center justify-center text-white {{ $roleBg }} ring-8 ring-white shadow-sm">
                                                <i class="fas {{ $roleIcon }} text-sm"></i>
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div>
                                                <div class="text-sm flex items-center justify-between gap-4">
                                                    <div class="font-semibold text-gray-800">
                                                        {{ $note->user?->name ?? 'Người dùng' }}
                                                        <span class="ml-1.5 px-2 py-0.5 text-[10px] font-bold rounded uppercase tracking-wider {{ in_array($note->user_role, ['pm', 'po']) ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                                            {{ strtoupper($note->user_role) }}
                                                        </span>
                                                    </div>
                                                    <div class="text-xs text-gray-400">
                                                        {{ $note->created_at->format('d/m/Y H:i') }}
                                                    </div>
                                                </div>
                                                @if($note->sla_extended_days > 0)
                                                    <div class="mt-1">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                            <i class="fas fa-clock mr-1"></i> +{{ $note->sla_extended_days }} ngày SLA Hãng
                                                        </span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="mt-2 text-sm text-gray-700 whitespace-pre-line leading-relaxed bg-gray-50/50 p-3 rounded-lg border border-gray-100">
                                                {!! nl2br(e($note->content)) !!}
                                            </div>
                                            
                                            <!-- Attachments -->
                                            @if($note->attachments && count($note->attachments) > 0)
                                                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    @foreach($note->attachments as $file)
                                                        @php
                                                            $filename = $file['name'] ?? basename($file['path']);
                                                            $ext = strtolower(pathinfo($file['path'], PATHINFO_EXTENSION));
                                                            $fileIcon = 'fa-file-alt';
                                                            $fileColor = 'text-gray-400';
                                                            if (in_array($ext, ['xls', 'xlsx'])) {
                                                                $fileIcon = 'fa-file-excel';
                                                                $fileColor = 'text-green-600';
                                                            } elseif ($ext === 'pdf') {
                                                                $fileIcon = 'fa-file-pdf';
                                                                $fileColor = 'text-red-500';
                                                            }
                                                        @endphp
                                                        <a href="{{ Storage::url($file['path']) }}" target="_blank"
                                                           class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-xs font-medium text-gray-600 shadow-sm">
                                                            <i class="fas {{ $fileIcon }} {{ $fileColor }} text-base"></i>
                                                            <span class="truncate max-w-[200px]">{{ $filename }}</span>
                                                            <i class="fas fa-download ml-auto text-gray-400"></i>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="text-center py-8 text-gray-500 bg-gray-50/50 rounded-xl border border-dashed">
                    <i class="fas fa-comments text-3xl mb-2 text-gray-300"></i>
                    <p class="text-sm">Chưa có nội dung trao đổi nào. Nhập phản hồi dưới đây để thảo luận.</p>
                </div>
            @endif

            <!-- Post Note Form -->
            <div class="border-t border-gray-100 pt-6">
                <form action="{{ route('projects.add-note', $project->id) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label for="content" class="block text-sm font-semibold text-gray-700">Gửi nội dung trao đổi mới</label>
                        <p class="text-xs text-gray-400 mb-2">
                            @if(in_array(auth()->user()->department, ['PM', 'PO']) || auth()->user()->hasAnyRole(['super_admin', 'admin', 'purchase_manager', 'purchase_staff']))
                                <span class="text-amber-600 font-medium"><i class="fas fa-info-circle mr-1"></i> Lưu ý:</span> Gửi note từ vai trò PM/PO sẽ tự động **gia hạn thêm +1 ngày làm việc** cho SLA của Hãng.
                            @else
                                Nhập thông tin phản hồi, làm rõ yêu cầu hoặc trả lời PM/PO Team tại đây.
                            @endif
                        </p>
                        <textarea id="content" name="content" rows="3" required
                                  class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                  placeholder="Nhập nội dung trao đổi, đính kèm Bom/Giá hoặc trả lời đối tác..."></textarea>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Đính kèm file (nếu có)</label>
                            <input type="file" name="attachments[]" multiple accept=".xlsx,.xls,.pdf,.doc,.docx,.jpg,.png"
                                   class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        </div>
                        <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-lg shadow-sm transition-colors whitespace-nowrap gap-2">
                            <i class="fas fa-paper-plane"></i> Gửi phản hồi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Lịch sử Phiên bản Báo giá Hãng (Quotation Versions) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <h3 class="text-base font-semibold text-gray-900 flex items-center">
                <i class="fas fa-file-invoice-dollar mr-2 text-indigo-500"></i> Lịch sử các phiên bản Báo giá từ Hãng
            </h3>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-semibold bg-indigo-50 text-indigo-700 rounded-full">
                    {{ $project->vendorQuoteVersions->count() }} phiên bản
                </span>
                @if(in_array(auth()->user()->department, ['PM', 'PO', 'PM Team', 'PO Team']) || auth()->user()->hasAnyRole(['super_admin', 'admin', 'purchase_manager', 'purchase_staff']))
                    <button type="button" onclick="openModal('vendorQuoteModal')"
                        class="inline-flex items-center px-2.5 py-1 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-medium text-xs shadow-xs">
                        <i class="fas fa-plus mr-1"></i> {{ $project->vendorQuoteVersions->count() > 0 ? 'Cập nhật Báo giá' : 'Đính kèm Báo giá' }}
                    </button>
                @endif
            </div>
        </div>
        <div class="p-6">
            @if($project->vendorQuoteVersions->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Phiên bản</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Mã Deal Hãng</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ngày nhận</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Thời hạn báo giá</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Người cập nhật</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Lý do cập nhật</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">File báo giá</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ghi chú</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($project->vendorQuoteVersions as $quote)
                                <tr class="hover:bg-gray-50/50">
                                    <td class="px-4 py-3 text-sm font-bold text-indigo-600">v{{ $quote->version_number }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-800">{{ $quote->vendor_deal_id ?: '-' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-500">{{ $quote->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600 font-medium">
                                        @if($quote->valid_until)
                                            {{ $quote->valid_until->format('d/m/Y') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-700 font-medium">{{ $quote->creator->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-xs text-gray-600 max-w-[150px] truncate" title="{{ $quote->requote_reason }}">{{ $quote->requote_reason ?: '-' }}</td>
                                    <td class="px-4 py-3 text-xs">
                                        @if($quote->quote_file && count($quote->quote_file) > 0)
                                            <div class="flex flex-col gap-1.5">
                                                @foreach($quote->quote_file as $file)
                                                    @php
                                                        $filename = basename($file);
                                                        $displayFilename = preg_replace('/^\d+_/', '', $filename);
                                                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                        $quoteIcon = 'fa-file-alt';
                                                        $quoteColor = 'text-gray-400';
                                                        if (in_array($ext, ['xls', 'xlsx'])) {
                                                            $quoteIcon = 'fa-file-excel';
                                                            $quoteColor = 'text-green-600';
                                                        } elseif ($ext === 'pdf') {
                                                            $quoteIcon = 'fa-file-pdf';
                                                            $quoteColor = 'text-red-500';
                                                        } elseif (in_array($ext, ['doc', 'docx'])) {
                                                            $quoteIcon = 'fa-file-word';
                                                            $quoteColor = 'text-blue-600';
                                                        }
                                                    @endphp
                                                    <div class="inline-flex items-center justify-between gap-1.5 bg-gray-50 hover:bg-gray-100 px-2 py-1 rounded border border-gray-200">
                                                        <a href="{{ Storage::url($file) }}" target="_blank"
                                                           class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline max-w-[150px]">
                                                            <i class="fas {{ $quoteIcon }} {{ $quoteColor }} mr-0.5"></i>
                                                            <span class="truncate" title="{{ $displayFilename }}">{{ $displayFilename }}</span>
                                                        </a>
                                                        @if(auth()->user()->hasAnyRole(['super_admin', 'admin', 'purchase_manager', 'purchase_staff']) || in_array(auth()->user()->department, ['PM', 'PO', 'PM Team', 'PO Team']) || auth()->id() === $quote->created_by)
                                                            <form action="{{ route('projects.delete-vendor-quote-file', [$project->id, $quote->id]) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa file báo giá này: {{ addslashes($displayFilename) }}?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <input type="hidden" name="file_path" value="{{ $file }}">
                                                                <button type="submit" class="text-red-400 hover:text-red-600 p-0.5 transition-colors" title="Xóa file này">
                                                                    <i class="fas fa-times-circle"></i>
                                                                </button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic">Không có file</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500 max-w-[150px] truncate" title="{{ $quote->quote_note }}">{{ $quote->quote_note ?: '-' }}</td>
                                    <td class="px-4 py-3 text-center text-xs">
                                        @if(auth()->user()->hasAnyRole(['super_admin', 'admin', 'purchase_manager', 'purchase_staff']) || in_array(auth()->user()->department, ['PM', 'PO', 'PM Team', 'PO Team']) || auth()->id() === $quote->created_by)
                                            <form action="{{ route('projects.delete-vendor-quote-version', [$project->id, $quote->id]) }}" method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa toàn bộ phiên bản báo giá v{{ $quote->version_number }} này không?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center gap-1 px-2 py-1 text-xs text-red-600 bg-red-50 hover:bg-red-100 rounded border border-red-200 font-medium transition-colors" title="Xóa toàn bộ phiên bản này">
                                                    <i class="fas fa-trash-alt"></i> Xóa bản này
                                                </button>
                                            </form>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-6 text-gray-400 bg-gray-50/50 rounded-xl border border-dashed text-sm">
                    <i class="fas fa-file-invoice-dollar text-2xl mb-1 text-gray-300"></i>
                    <p>Chưa có phiên bản báo giá hãng nào được gửi.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Lịch sử hoạt động & Nhật ký tiến trình dự án (Timeline) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <div>
                <h3 class="text-base font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-history mr-2 text-indigo-500"></i> Nhật ký hoạt động & Lịch sử dự án
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">Theo dõi chi tiết các bước cập nhật, người thực hiện và dự báo hành động tiếp theo</p>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                {{ $activityLogs->count() }} hoạt động
            </span>
        </div>
        <div class="p-6">
            @if($activityLogs->count() > 0)
                <div class="flow-root max-h-[500px] overflow-y-auto pr-3 custom-scrollbar">
                    <ul role="list" class="-mb-8">
                        @foreach($activityLogs as $index => $log)
                            @php
                                $userInfo = \App\Services\ProjectActivityLogPresenter::getUserInfo($log, $project);
                                $changes = \App\Services\ProjectActivityLogPresenter::formatChanges($log->properties['changes'] ?? []);
                                $nextAction = \App\Services\ProjectActivityLogPresenter::predictNextAction($log, $project);
                            @endphp
                            <li>
                                <div class="relative pb-8">
                                    @if($index < $activityLogs->count() - 1)
                                        <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3.5 items-start">
                                        <!-- Avatar / Icon -->
                                        <div class="relative flex-shrink-0">
                                            <div class="h-10 w-10 rounded-full {{ $userInfo['avatar_bg'] }} text-white flex items-center justify-center font-bold text-xs shadow-sm ring-4 ring-white">
                                                {{ $userInfo['initials'] }}
                                            </div>
                                            <span class="absolute -bottom-1 -right-1 h-4 w-4 rounded-full bg-white flex items-center justify-center shadow-xs">
                                                @if($log->action === 'created')
                                                    <i class="fas fa-plus-circle text-[11px] text-green-500"></i>
                                                @elseif($log->action === 'updated')
                                                    <i class="fas fa-pen-alt text-[10px] text-blue-500"></i>
                                                @elseif($log->action === 'deleted')
                                                    <i class="fas fa-trash-alt text-[10px] text-red-500"></i>
                                                @else
                                                    <i class="fas fa-info-circle text-[11px] text-gray-400"></i>
                                                @endif
                                            </span>
                                        </div>

                                        <!-- Log Body -->
                                        <div class="flex-1 min-w-0 bg-gray-50/70 hover:bg-gray-50 border border-gray-100 rounded-xl p-4 transition-all duration-150">
                                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                                <!-- User & Role -->
                                                <div class="flex items-center flex-wrap gap-2">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold border {{ $userInfo['role_color'] }}">
                                                        {{ $userInfo['role_title'] }}
                                                    </span>
                                                    <span class="font-semibold text-gray-900 text-sm">{{ $userInfo['name'] }}</span>
                                                    <span class="text-xs text-gray-500 font-normal">
                                                        @if($log->action === 'created')
                                                            đã tạo mới Đăng ký dự án
                                                        @elseif($log->action === 'deleted')
                                                            đã xóa bản ghi dự án
                                                        @else
                                                            {{ Str::replaceFirst('Project: ' . $project->name, '', $log->description ?: 'đã cập nhật thông tin dự án') }}
                                                        @endif
                                                    </span>
                                                </div>
                                                <!-- Timestamp -->
                                                <div class="text-xs text-gray-400 flex items-center space-x-1 whitespace-nowrap">
                                                    <i class="far fa-clock text-[11px]"></i>
                                                    <span>{{ $log->created_at->format('d/m/Y H:i') }}</span>
                                                    <span class="text-gray-300">({{ $log->created_at->diffForHumans() }})</span>
                                                </div>
                                            </div>

                                            <!-- Changed Fields Diff -->
                                            @if(!empty($changes))
                                                <div class="mt-2.5 bg-white rounded-lg p-3 border border-gray-200/80 shadow-xs space-y-1.5 text-xs">
                                                    <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-1 flex items-center">
                                                        <i class="fas fa-exchange-alt mr-1.5 text-indigo-500"></i> Nội dung thay đổi chi tiết:
                                                    </div>
                                                    @foreach($changes as $item)
                                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-1 py-1 border-b border-gray-50 last:border-0 items-start">
                                                            <div class="md:col-span-4 font-medium text-gray-700">
                                                                {{ $item['label'] }}:
                                                            </div>
                                                            <div class="md:col-span-8 flex items-center flex-wrap gap-1.5">
                                                                <span class="line-through text-gray-400 bg-gray-50 px-1.5 py-0.5 rounded border border-gray-100 max-w-full break-all">
                                                                    {{ $item['old'] }}
                                                                </span>
                                                                <i class="fas fa-arrow-right text-[10px] text-gray-400 mx-0.5"></i>
                                                                <span class="font-semibold text-indigo-700 bg-indigo-50/60 px-1.5 py-0.5 rounded border border-indigo-100 max-w-full break-all">
                                                                    {{ $item['new'] }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <!-- Next Action Forecast -->
                                            @if($nextAction)
                                                <div class="mt-3 bg-gradient-to-r from-slate-50 to-indigo-50/40 rounded-lg p-3 border border-indigo-100/70 flex items-start space-x-2.5">
                                                    <div class="mt-0.5 flex-shrink-0">
                                                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-md bg-indigo-100 text-indigo-600 text-xs">
                                                            <i class="{{ $nextAction['icon'] }}"></i>
                                                        </span>
                                                    </div>
                                                    <div class="flex-1 text-xs">
                                                        <div class="flex items-center gap-1.5 flex-wrap">
                                                            <span class="font-bold text-gray-800">Dự báo hành động tiếp theo:</span>
                                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-600 text-white shadow-xs">
                                                                👉 Cần phản hồi: {{ $nextAction['respondent'] }}
                                                            </span>
                                                        </div>
                                                        <p class="text-gray-600 mt-1 leading-relaxed">
                                                            {{ $nextAction['action_text'] }}
                                                        </p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="text-center py-8 text-gray-400 bg-gray-50/50 rounded-xl border border-dashed text-sm">
                    <i class="fas fa-history text-3xl mb-2 text-gray-300"></i>
                    <p class="font-medium text-gray-600">Chưa có nhật ký hoạt động nào được ghi nhận cho dự án này.</p>
                    <p class="text-xs text-gray-400 mt-1">Các thao tác tạo mới, cập nhật thông tin và báo giá sẽ tự động được ghi lại tại đây.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Export Materials Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <div>
                <h3 class="text-base font-semibold text-gray-900">Vật tư đã xuất cho dự án</h3>
                <p class="text-sm text-gray-500 mt-0.5">
                    Tổng giá trị: <span class="font-semibold text-orange-600">{{ number_format($exportStats['total_export_value']) }} đ</span>
                    <span class="text-gray-300 mx-2">|</span>
                    {{ $exportStats['total_exports'] }} phiếu xuất
                </p>
            </div>
            <a href="{{ route('exports.index', ['project_id' => $project->id]) }}" class="text-sm text-primary hover:underline font-medium">
                Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-8"></th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã phiếu</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày xuất</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kho xuất</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số lượng</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentExports as $index => $export)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <button type="button" onclick="toggleExportDetails({{ $index }})" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-chevron-right transition-transform" id="icon-{{ $index }}"></i>
                            </button>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('exports.show', $export->id) }}" class="font-medium text-orange-600 hover:underline">
                                {{ $export->code }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $export->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $export->warehouse->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-sm font-semibold bg-orange-100 text-orange-800 rounded">
                                {{ number_format($export->total_qty) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($export->status === 'pending')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Chờ xử lý</span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Hoàn thành</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('exports.show', $export->id) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <tr id="details-{{ $index }}" class="hidden bg-gray-50">
                        <td colspan="7" class="px-4 py-4">
                            <div class="bg-white rounded-lg border border-gray-200 p-4">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                                    <i class="fas fa-boxes text-orange-500 mr-2"></i>Chi tiết sản phẩm
                                </h4>
                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Mã SP</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tên sản phẩm</th>
                                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase">SL</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ghi chú</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($export->items as $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-3 py-2">
                                                <span class="font-mono text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">
                                                    {{ $item->product->code ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-sm text-gray-900">{{ $item->product->name ?? '-' }}</td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="px-2 py-1 text-xs font-bold bg-orange-100 text-orange-800 rounded-full">
                                                    {{ number_format($item->quantity) }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-xs text-gray-500">{{ $item->comments ?: '-' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>Chưa có phiếu xuất vật tư nào cho dự án này</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function toggleExportDetails(index) {
        const detailsRow = document.getElementById('details-' + index);
        const icon = document.getElementById('icon-' + index);
        if (detailsRow.classList.contains('hidden')) {
            detailsRow.classList.remove('hidden');
            icon.classList.add('rotate-90');
        } else {
            detailsRow.classList.add('hidden');
            icon.classList.remove('rotate-90');
        }
    }
    </script>

    <!-- Technical Tickets Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <h3 class="text-base font-semibold text-gray-900">Ticket Kỹ thuật của dự án</h3>
                <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-blue-100 text-blue-800">
                    {{ $technicalTickets->count() }}
                </span>
            </div>
            <div class="flex items-center gap-3">
                @can('create_technical_tickets')
                    <a href="{{ route('technical-tickets.create', ['project_id' => $project->id]) }}" 
                       class="inline-flex items-center px-3 py-1.5 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors font-medium text-xs shadow-xs">
                        <i class="fas fa-plus mr-1.5"></i> Thêm Ticket Kỹ thuật
                    </a>
                @endcan
                <a href="{{ route('technical-tickets.index', ['project_id' => $project->id]) }}" class="text-sm text-primary hover:underline font-medium">
                    Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã Ticket</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tiêu đề</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Loại công việc</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Ưu tiên</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kỹ sư phụ trách</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Hạn SLA</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($technicalTickets as $ticket)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('technical-tickets.show', $ticket->id) }}" class="font-mono text-xs font-bold text-primary hover:underline">
                                {{ $ticket->code }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            <a href="{{ route('technical-tickets.show', $ticket->id) }}" class="hover:text-primary transition-colors">
                                {{ $ticket->title }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600">
                            <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700">
                                {{ $ticket->work_type_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full 
                                {{ $ticket->priority === 'urgent' ? 'bg-red-100 text-red-800' : ($ticket->priority === 'high' ? 'bg-orange-100 text-orange-800' : ($ticket->priority === 'medium' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800')) }}">
                                {{ $ticket->priority_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-700">
                            @if($ticket->assignedEngineers->count() > 0)
                                <div class="flex flex-wrap gap-1">
                                    @foreach($ticket->assignedEngineers as $eng)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            <i class="fas fa-user-circle mr-1 text-indigo-400"></i> {{ $eng->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @elseif($ticket->assignedTo)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    <i class="fas fa-user-circle mr-1 text-indigo-400"></i> {{ $ticket->assignedTo->name }}
                                </span>
                            @else
                                <span class="text-gray-400 italic">Chưa phân công</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-xs">
                            @if($ticket->sla_deadline)
                                <span class="{{ $ticket->is_overdue ? 'text-red-600 font-bold' : 'text-gray-600' }}">
                                    {{ $ticket->sla_deadline->format('H:i d/m/Y') }}
                                    @if($ticket->is_overdue)
                                        <i class="fas fa-exclamation-triangle text-red-500 ml-1" title="Quá hạn SLA"></i>
                                    @endif
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 text-xs font-semibold rounded-full 
                                {{ match($ticket->status) {
                                    'open' => 'bg-blue-100 text-blue-800',
                                    'assigned' => 'bg-indigo-100 text-indigo-800',
                                    'in_progress' => 'bg-amber-100 text-amber-800',
                                    'waiting' => 'bg-purple-100 text-purple-800',
                                    'completed' => 'bg-green-100 text-green-800',
                                    'closed' => 'bg-gray-100 text-gray-800',
                                    default => 'bg-gray-100 text-gray-800'
                                } }}">
                                {{ $ticket->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('technical-tickets.show', $ticket->id) }}" class="text-blue-600 hover:text-blue-800 font-medium text-xs">
                                    <i class="fas fa-eye mr-0.5"></i> Xem
                                </a>
                                @can('edit_technical_tickets')
                                <a href="{{ route('technical-tickets.edit', $ticket->id) }}" class="text-amber-600 hover:text-amber-800 font-medium text-xs">
                                    <i class="fas fa-edit mr-0.5"></i> Sửa
                                </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-ticket-alt text-3xl mb-2 text-gray-300"></i>
                            <p class="text-sm">Chưa có ticket kỹ thuật nào cho dự án này</p>
                            @can('create_technical_tickets')
                                <a href="{{ route('technical-tickets.create', ['project_id' => $project->id]) }}" class="inline-flex items-center mt-2 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 font-medium text-xs transition-colors">
                                    <i class="fas fa-plus mr-1"></i> Tạo Ticket kỹ thuật ngay
                                </a>
                            @endcan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Sales -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-base font-semibold text-gray-900">Đơn hàng của dự án</h3>
            <a href="{{ route('sales.index', ['project_id' => $project->id]) }}" class="text-sm text-primary hover:underline font-medium">
                Xem tất cả <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mã đơn</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ngày</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tổng tiền</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Công nợ</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Trạng thái</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($recentSales as $sale)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('sales.show', $sale->id) }}" class="font-medium text-primary hover:underline">
                                {{ $sale->code }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">{{ $sale->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-right font-medium">{{ number_format($sale->total) }} đ</td>
                        <td class="px-4 py-3 text-sm text-right {{ $sale->debt_amount > 0 ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                            {{ number_format($sale->debt_amount) }} đ
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $sale->status_color }}">
                                {{ $sale->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('sales.show', $sale->id) }}" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            Chưa có đơn hàng nào cho dự án này
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- =================================================================== -->
<!-- MODALS -->
<!-- =================================================================== -->

<!-- 1. Remind Vendor Modal -->
<div id="remindVendorModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('remindVendorModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-lg font-bold text-gray-900">Báo cáo: Hãng chưa phản hồi</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('remindVendorModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('projects.remind-vendor', $project->id) }}" method="POST">
                @csrf
                <div class="mt-4 space-y-4">
                    <p class="text-sm text-gray-600">
                        Xác nhận Hãng chưa phản hồi báo giá cho dự án này. Hệ thống sẽ ghi nhận lịch sử nhắc nhở và tự động **gia hạn thêm +3 ngày làm việc** chờ Hãng phản hồi.
                    </p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nội dung nhắc nhở / Ghi chú <span class="text-red-500">*</span></label>
                        <textarea name="remind_note" rows="3" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Nhập nội dung giục hãng hoặc ghi chú tình trạng..."></textarea>
                    </div>
                </div>
                <div class="mt-5 sm:mt-6 flex justify-end gap-3">
                    <button type="button" class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50" onclick="closeModal('remindVendorModal')">Hủy</button>
                    <button type="submit" class="inline-flex justify-center rounded-lg border border-transparent bg-amber-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-amber-700">Xác nhận (+3 ngày SLA)</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. Submit Vendor Quote Modal -->
<div id="vendorQuoteModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('vendorQuoteModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-lg font-bold text-gray-900">
                    {{ $project->vendorQuoteVersions->count() > 0 ? 'Cập nhật Báo giá Hãng (Phiên bản v' . ($project->vendorQuoteVersions->count() + 1) . ')' : 'Đính kèm Báo giá Hãng' }}
                </h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('vendorQuoteModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('projects.submit-vendor-quote', $project->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mã đăng ký dự án của Hãng (Vendor Deal ID)</label>
                        <input type="text" name="vendor_deal_id" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Nhập mã Deal ID của Hãng (nếu có)..." value="{{ old('vendor_deal_id', $project->vendor_deal_id) }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">File báo giá từ Hãng <span class="text-red-500">*</span></label>
                        <input type="file" name="quote_file[]" id="vendor_quote_file_input" multiple required accept=".xlsx,.xls,.pdf,.doc,.docx,.jpg,.png" 
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                            onchange="previewVendorQuoteFiles(this)">
                        <p class="text-xs text-gray-400 mt-1">Đính kèm một hoặc nhiều file báo giá/cấu hình (giữ <kbd class="px-1 py-0.5 bg-gray-100 border rounded text-[10px] font-mono">Ctrl</kbd> hoặc <kbd class="px-1 py-0.5 bg-gray-100 border rounded text-[10px] font-mono">Shift</kbd> khi chọn để chọn nhiều file cùng lúc).</p>
                        <div id="vendor_quote_preview_list" class="mt-2 space-y-1.5 hidden"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Thời hạn hiệu lực báo giá</label>
                        <input type="date" name="valid_until" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" value="{{ old('valid_until') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Ghi chú giá / Điều khoản</label>
                        <textarea name="quote_note" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Nhập các ghi chú về giá đặc biệt, chiết khấu..."></textarea>
                    </div>
                    @if($project->vendorQuoteVersions()->count() > 0)
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lý do xin giá lại (Cập nhật phiên bản mới)</label>
                        <textarea name="requote_reason" rows="2" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Nhập lý do cập nhật báo giá (VD: Đổi cấu hình BOM, hãng giảm thêm giá...)..."></textarea>
                    </div>
                    @endif
                </div>
                <div class="mt-5 sm:mt-6 flex justify-end gap-3">
                    <button type="button" class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50" onclick="closeModal('vendorQuoteModal')">Hủy</button>
                    <button type="submit" class="inline-flex justify-center rounded-lg border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">Gửi báo giá</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. Monthly Update Modal -->
<div id="monthlyUpdateModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('monthlyUpdateModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-lg font-bold text-gray-900">Cập nhật tiến độ dự án định kỳ</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('monthlyUpdateModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('projects.update-status-monthly', $project->id) }}" method="POST">
                @csrf
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Dự báo doanh số (Forecast Stage) <span class="text-red-500">*</span></label>
                        <select name="forecast_stage" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="commit" {{ old('forecast_stage', $project->forecast_stage) == 'commit' ? 'selected' : '' }}>Commit (Chắc chắn chốt trong quý)</option>
                            <option value="best_case" {{ old('forecast_stage', $project->forecast_stage) == 'best_case' ? 'selected' : '' }}>Best Case (Có tiềm năng nhưng chưa chắc chắn)</option>
                            <option value="close_deal" {{ old('forecast_stage', $project->forecast_stage) == 'close_deal' ? 'selected' : '' }}>Close Deal (Đóng dự án)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Yêu cầu hỗ trợ tiếp theo</label>
                        <select name="support_request_type" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">-- Không yêu cầu --</option>
                            @if($project->assigned_team === 'po_team')
                                <option value="request_discount" {{ old('support_request_type') == 'request_discount' ? 'selected' : '' }}>Yêu cầu xin chiết khấu giá (Gửi PO Team)</option>
                            @else
                                <option value="request_update_price" {{ old('support_request_type') == 'request_update_price' ? 'selected' : '' }}>Yêu cầu xin lại giá Hãng (Gửi PM Team)</option>
                            @endif
                            <option value="other_request" {{ old('support_request_type') == 'other_request' ? 'selected' : '' }}>Yêu cầu hỗ trợ khác</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nội dung chi tiết yêu cầu hỗ trợ</label>
                        <textarea name="support_request_note" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Nhập chi tiết yêu cầu hỗ trợ của bạn (ví dụ lý do cần update giá, mức chiết khấu mong muốn...)..."></textarea>
                    </div>
                </div>
                <div class="mt-5 sm:mt-6 flex justify-end gap-3">
                    <button type="button" class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50" onclick="closeModal('monthlyUpdateModal')">Hủy</button>
                    <button type="submit" class="inline-flex justify-center rounded-lg border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 4. Close Project Modal -->
<div id="closeProjectModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('closeProjectModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-lg font-bold text-gray-900">Đóng dự án</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('closeProjectModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('projects.close', $project->id) }}" method="POST">
                @csrf
                @php $canCloseWon = $canCloseWon ?? false; @endphp
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kết quả đóng dự án <span class="text-red-500">*</span></label>
                        <select name="close_status" id="close_status" required onchange="toggleCloseStatusFields()" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">-- Chọn kết quả --</option>
                            <option value="closed_won" {{ !$canCloseWon ? 'disabled class=text-gray-400' : '' }}>
                                Closed Won (Thắng dự án){{ !$canCloseWon ? ' - (Yêu cầu PO/PM duyệt ĐKDA trước)' : '' }}
                            </option>
                            <option value="closed_lost">Closed Lost (Thua dự án)</option>
                            <option value="cancelled">Cancelled (Hủy dự án)</option>
                            <option value="on_hold">On Hold (Tạm dừng)</option>
                        </select>
                        @if(!$canCloseWon)
                            <p class="text-xs text-amber-600 mt-1 flex items-center gap-1 font-medium">
                                <i class="fas fa-exclamation-triangle"></i> Lưu ý: Không thể chọn Closed Won vì dự án chưa được PO/PM duyệt hoặc đăng ký thành công với Hãng.
                            </p>
                        @endif
                    </div>
                    
                    <!-- Closed Won Fields -->
                    <div id="closed_won_fields" class="hidden space-y-4">
                        <input type="hidden" name="source_mode" id="source_mode_input" value="{{ $recentSales->count() > 0 ? 'sync_sale' : 'manual' }}">
                        
                        @if($recentSales->count() > 0)
                            <!-- Mode Switcher Tabs -->
                            <div class="bg-gray-100 p-1 rounded-xl flex items-center gap-1 border border-gray-200">
                                <button type="button" id="tab_mode_sync" onclick="toggleCloseMode('sync_sale')" class="flex-1 py-1.5 text-xs font-bold text-center rounded-lg bg-indigo-600 text-white shadow-sm transition-all">
                                    <i class="fas fa-bolt mr-1"></i> Đồng bộ từ Đơn hàng
                                </button>
                                <button type="button" id="tab_mode_manual" onclick="toggleCloseMode('manual')" class="flex-1 py-1.5 text-xs font-medium text-center rounded-lg bg-transparent text-gray-600 hover:bg-gray-200 transition-all">
                                    <i class="fas fa-edit mr-1"></i> Nhập thủ công (Gộp hãng/DA)
                                </button>
                            </div>

                            <!-- Sync Mode Container -->
                            <div id="sync_sale_container" class="space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Chọn Đơn bán hàng liên kết <span class="text-red-500">*</span></label>
                                    <select name="sale_id" id="close_sale_id" onchange="updateCloseSalePreview()" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        @foreach($recentSales as $s)
                                            <option value="{{ $s->id }}" 
                                                    data-code="{{ $s->code }}"
                                                    data-customer="{{ $s->customer?->name ?? 'N/A' }}"
                                                    data-date="{{ $s->date ? $s->date->format('Y-m-d') : '' }}"
                                                    data-date-formatted="{{ $s->date ? $s->date->format('d/m/Y') : '-' }}"
                                                    data-total="{{ (float)$s->total }}"
                                                    data-total-formatted="{{ number_format($s->total, 0, ',', '.') }} đ"
                                                    data-items-count="{{ $s->items->count() }}"
                                                    {{ ($latestSaleForClosure?->id === $s->id) ? 'selected' : '' }}>
                                                #{{ $s->code }} - {{ $s->customer?->name ?? 'N/A' }} ({{ number_format($s->total, 0, ',', '.') }} đ | {{ $s->date?->format('d/m/Y') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Selected Sale Quick Preview Card -->
                                <div id="close_sale_preview" class="p-3 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-1.5 text-xs">
                                    <div class="flex justify-between items-center pb-1 border-b border-indigo-100">
                                        <span class="font-bold text-indigo-900 flex items-center gap-1">
                                            <i class="fas fa-file-invoice text-indigo-600"></i> Mã đơn: <span id="preview_sale_code">{{ $latestSaleForClosure?->code ?? '-' }}</span>
                                        </span>
                                        <span class="px-2 py-0.5 bg-indigo-200/80 text-indigo-900 rounded font-semibold text-[11px]" id="preview_sale_items">{{ $latestSaleForClosure?->items->count() ?? 0 }} sản phẩm</span>
                                    </div>
                                    <div class="flex justify-between text-gray-700">
                                        <span class="text-gray-500">Khách hàng:</span>
                                        <span class="font-semibold text-gray-900 text-right truncate max-w-[220px]" id="preview_sale_customer">{{ $latestSaleForClosure?->customer?->name ?? '-' }}</span>
                                    </div>
                                    <div class="flex justify-between text-gray-700">
                                        <span class="text-gray-500">Ngày đơn hàng:</span>
                                        <span class="font-medium text-gray-900" id="preview_sale_date">{{ $latestSaleForClosure?->date?->format('d/m/Y') ?? '-' }}</span>
                                    </div>
                                    <div class="flex justify-between text-gray-700 pt-1 border-t border-indigo-100">
                                        <span class="font-bold text-indigo-950">Tổng giá trị đơn:</span>
                                        <span class="font-bold text-indigo-700 text-sm" id="preview_sale_total">{{ $latestSaleForClosure ? number_format($latestSaleForClosure->total, 0, ',', '.') . ' đ' : '0 đ' }}</span>
                                    </div>
                                </div>

                                <!-- Sync BOM & selling price checkbox -->
                                <div class="p-2.5 bg-emerald-50 border border-emerald-200 rounded-xl">
                                    <label class="flex items-start gap-2.5 cursor-pointer">
                                        <input type="checkbox" name="sync_bom" value="1" checked class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                        <div>
                                            <span class="text-xs font-bold text-emerald-900 flex items-center gap-1">
                                                <i class="fas fa-sync-alt text-emerald-600"></i> Tự động đồng bộ BOM & Đơn giá bán thực tế
                                            </span>
                                            <p class="text-[11px] text-emerald-700 mt-0.5">Cập nhật danh mục hàng hóa, số lượng và giá chốt thực tế từ đơn bán hàng vào dữ liệu dự án.</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        @else
                            <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800 flex items-center gap-2">
                                <i class="fas fa-info-circle text-amber-600 text-base shrink-0"></i>
                                <span>Chưa có Đơn bán hàng nào được liên kết với dự án. Vui lòng nhập thông tin đóng dự án thủ công bên dưới.</span>
                            </div>
                        @endif

                        <!-- Manual Mode Inputs (Active when manual tab selected or no sales exist) -->
                        <div id="manual_sale_container" class="{{ $recentSales->count() > 0 ? 'hidden' : '' }} space-y-3 p-3 bg-gray-50 border border-gray-200 rounded-xl">
                            <div class="text-[11px] text-gray-500 italic">
                                Dành cho trường hợp gộp đơn hàng từ nhiều hãng, chia nhỏ đơn hoặc tự phân bổ giá trị dự án.
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Số đơn đặt hàng (PO Code) <span class="text-red-500">*</span></label>
                                <input type="text" name="po_code" id="po_code" value="{{ old('po_code', $latestSaleForClosure?->code) }}" placeholder="VD: PO-2026-001 hoặc SO-2026-0099" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Tổng giá trị đơn hàng (VNĐ) <span class="text-red-500">*</span></label>
                                <input type="number" name="order_value" id="order_value" value="{{ old('order_value', $latestSaleForClosure?->total) }}" placeholder="VD: 150000000" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Ngày đặt hàng <span class="text-red-500">*</span></label>
                                <input type="date" name="order_date" id="order_date" value="{{ old('order_date', $latestSaleForClosure?->date?->format('Y-m-d') ?? date('Y-m-d')) }}" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Closed Lost / Cancelled Reason -->
                    <div id="close_reason_fields" class="hidden">
                        <label class="block text-sm font-medium text-gray-700">Lý do đóng dự án <span class="text-red-500">*</span></label>
                        <select name="close_reason" id="close_reason" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">-- Chọn lý do --</option>
                            <option value="Mất dự án">Mất dự án</option>
                            <option value="Khách hàng dừng mua">Khách hàng dừng mua</option>
                            <option value="Đối thủ thắng">Đối thủ thắng</option>
                            <option value="Không có ngân sách">Không có ngân sách</option>
                            <option value="Không đủ giá cạnh tranh">Không đủ giá cạnh tranh</option>
                            <option value="Không phản hồi">Không phản hồi</option>
                            <option value="Lý do khác">Lý do khác</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Chi tiết / Diễn giải thêm</label>
                        <textarea name="close_note" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Nhập thêm chi tiết diễn giải..."></textarea>
                    </div>
                </div>
                <div class="mt-5 sm:mt-6 flex justify-end gap-3">
                    <button type="button" class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50" onclick="closeModal('closeProjectModal')">Hủy</button>
                    <button type="submit" class="inline-flex justify-center rounded-lg border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-rose-700">Xác nhận đóng</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 5. Intake Modal -->
<div id="intakeModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('intakeModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-lg font-bold text-gray-900" id="intake-modal-title">Tiếp nhận đăng ký dự án</h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('intakeModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('projects.process-intake', $project->id) }}" method="POST">
                @csrf
                <input type="hidden" name="intake_status" id="intake_status">
                
                <div class="mt-4 space-y-4">
                    <!-- Incomplete reasons -->
                    <div id="intake_note_container" class="hidden">
                        <label class="block text-sm font-medium text-gray-700">Lý do chưa đầy đủ (Thiếu thông tin gì) <span class="text-red-500">*</span></label>
                        <textarea name="intake_note" id="intake_note" rows="3" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Nhập ghi chú những phần thông tin còn thiếu để Sales sửa lại..."></textarea>
                    </div>
                    
                    <!-- Duplicate info -->
                    <div id="duplicate_info_container" class="hidden space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                1. Thông tin Sales nội bộ đang làm trùng <span class="text-red-500">*</span>
                            </label>
                            <p class="text-[11px] text-gray-500 mb-1">Cung cấp tên / contact Sales nội bộ đang phụ trách (Lưu ý: Không công khai nội dung BOM/giá dự án khác).</p>
                            <textarea name="duplicate_sales_info" id="duplicate_sales_info" rows="2" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="VD: Sales Nguyễn Văn A (Phòng BU1)..."></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                2. Thông tin Sales Hãng (Vendor Rep) đang làm
                            </label>
                            <p class="text-[11px] text-gray-500 mb-1">Cung cấp thông tin AM / Sales Hãng đang làm việc cho dự án này.</p>
                            <textarea name="duplicate_vendor_sales_info" id="duplicate_vendor_sales_info" rows="2" class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="VD: Hãng Fortinet - Sales Rep: Trần B (tran.b@fortinet.com)..."></textarea>
                        </div>
                    </div>
                    
                    <p class="text-sm text-gray-500" id="intake_confirm_text">Bạn có chắc chắn muốn cập nhật quyết định tiếp nhận này?</p>
                </div>
                <div class="mt-5 sm:mt-6 flex justify-end gap-3">
                    <button type="button" class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50" onclick="closeModal('intakeModal')">Hủy</button>
                    <button type="submit" class="inline-flex justify-center rounded-lg border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">Xác nhận</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 5b. Reopen Intake Modal (Hoàn trả cho Sales sửa) -->
<div id="reopenIntakeModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('reopenIntakeModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-undo text-amber-600"></i> Hoàn trả dự án cho Sales chỉnh sửa
                </h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('reopenIntakeModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form action="{{ route('projects.reopen-intake', $project->id) }}" method="POST">
                @csrf
                <div class="mt-4 space-y-4">
                    <p class="text-sm text-gray-600 leading-relaxed">
                        Hệ thống sẽ hoàn trả dự án về trạng thái <strong>Chưa đầy đủ / Yêu cầu bổ sung</strong> và mở khóa form để Sales có thể sửa lại trực tiếp trên thông tin cũ mà không cần phải tạo mới.
                    </p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lý do hoàn trả / Ghi chú cho Sales <span class="text-red-500">*</span></label>
                        <textarea name="reason" rows="3" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500 sm:text-sm" placeholder="Nhập lý do hoàn trả hoặc các điểm cần Sales điều chỉnh..."></textarea>
                    </div>
                </div>
                <div class="mt-5 sm:mt-6 flex justify-end gap-3">
                    <button type="button" class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50" onclick="closeModal('reopenIntakeModal')">Hủy</button>
                    <button type="submit" class="inline-flex justify-center rounded-lg border border-transparent bg-amber-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-amber-700">Xác nhận hoàn trả</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 6. Vertical Export Modal (Dạng dọc Key-Value gửi Hãng) -->
<div id="verticalExportModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('verticalExportModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
            <div class="flex justify-between items-center pb-3 border-b">
                <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <i class="fas fa-align-left text-cyan-600"></i> Form Đăng Ký Dự Án Dạng Dọc (Gửi Mail Hãng)
                </h3>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('verticalExportModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mt-4">
                <p class="text-xs text-gray-500 mb-2">Dưới đây là thông tin dự án được chuẩn hóa dạng cột dọc Key: Value kèm BOM để bạn sao chép nhanh vào email gửi Hãng / Distributor:</p>
                <div class="relative">
                    <textarea id="vertical_export_textarea" rows="16" readonly
                        class="w-full font-mono text-xs p-3.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:ring-2 focus:ring-cyan-500 focus:outline-none select-all leading-relaxed">=== THÔNG TIN ĐĂNG KÝ DỰ ÁN (PROJECT REGISTRATION) ===
Mã dự án: {{ $project->code }}
Tên dự án (VN): {{ $project->name }}
Tên dự án (EN): {{ $project->name_en ?: $project->name }}
Hãng sản xuất (Vendor): {{ $project->vendor?->name ?? 'Fortinet' }}
Distributor: Tech Horizon Corporation
Distributor AM: {{ $project->distributor_am }}

--- KHÁCH HÀNG CUỐI (END-USER) ---
Tên khách hàng (VN): {{ $project->eu_name_vi }}
Tên khách hàng (EN): {{ $project->eu_name_en ?: $project->eu_name_vi }}
Mã số thuế / Website: {{ $project->eu_tax_code }}
Địa chỉ triển khai: {{ $project->address }}
Tỉnh / Thành phố: {{ $project->eu_province ?: 'N/A' }}
Ngành nghề (Industry): {{ $project->eu_industry_label ?: ($project->eu_industry ?: 'N/A') }}

--- ĐƠN VỊ HỢP TÁC (COLLABORATION) ---
Hình thức: {{ $project->collaborate_type === 'partner' ? 'Qua đối tác (Partner)' : 'Trực tiếp với End-User' }}
@if($project->collaborate_type === 'partner')
Tên đại lý / đối tác: {{ $project->collaborateCustomer?->name_en ?: ($project->collaborate_company ?: ($project->collaborateCustomer?->name ?: 'N/A')) }}
MST đối tác: {{ $project->collaborate_tax_code ?: 'N/A' }}
Người liên hệ (PIC): {{ $project->collaborate_pic_name ?: 'N/A' }}
Chức vụ PIC: {{ $project->collaborate_pic_title ?: 'N/A' }}
SĐT PIC: {{ $project->collaborate_pic_phone ?: 'N/A' }}
Email PIC: {{ $project->collaborate_pic_email ?: 'N/A' }}
@endif

--- THÔNG TIN THƯƠNG MẠI & DEAL ---
Ngày chốt dự kiến (Close Date): {{ $project->end_date ? $project->end_date->format('d/m/Y') : 'N/A' }}
@if($project->deal_type)
Loại Deal: {{ $project->deal_type === 'new_buy' ? 'New Buy (Mua mới)' : 'Trade Up (Nâng cấp)' }}
@endif
@if($project->sn_numbers)
S/N Thiết bị cũ (Trade Up): {{ $project->sn_numbers }}
@endif
@if($project->net_to_tech_horizon)
Net to Tech Horizon / FTN: {{ number_format($project->net_to_tech_horizon) }} VNĐ
@endif
@if($project->budget)
Dự toán / Budget: {{ number_format($project->budget) }} VNĐ
@endif
@if($project->special_request_type)
Yêu cầu thêm: {{ $project->special_request_type === 'bom_project' ? 'BOM dự án (Nhờ Hãng tạo BOM)' : 'Cần giá gấp' }} - {{ $project->special_request_note }}
@endif
Phụ trách kinh doanh (Sales Rep): {{ $project->manager?->name ?? 'N/A' }} ({{ $project->manager?->email ?? '' }})
Ghi chú dự án: {{ $project->note ?: 'Không có' }}

=== DANH MỤC VẬT TƯ (BOM - BILL OF MATERIALS) ===
@if($project->bom_data)
{{ $project->bom_data }}
@else
(Đính kèm theo file: {{ is_array($project->bom_file) ? implode(', ', array_map('basename', $project->bom_file)) : ($project->bom_file ? basename($project->bom_file) : 'Chưa có file') }})
@endif</textarea>
                </div>
            </div>

            <div class="mt-5 sm:mt-6 flex justify-between items-center gap-3">
                <span id="vertical_copy_status" class="text-xs text-emerald-600 font-bold hidden">
                    <i class="fas fa-check-circle mr-1"></i> Đã sao chép nội dung vào bộ nhớ tạm!
                </span>
                <div class="flex gap-2 ml-auto">
                    <button type="button" class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50" onclick="closeModal('verticalExportModal')">Đóng</button>
                    <button type="button" onclick="copyVerticalContent()" class="inline-flex items-center justify-center rounded-lg border border-transparent bg-cyan-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-cyan-700 gap-1.5">
                        <i class="fas fa-copy"></i> Sao chép toàn bộ
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function copyVerticalContent() {
        const textarea = document.getElementById('vertical_export_textarea');
        if (!textarea) return;
        textarea.select();
        textarea.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(textarea.value).then(() => {
            const statusEl = document.getElementById('vertical_copy_status');
            if (statusEl) {
                statusEl.classList.remove('hidden');
                setTimeout(() => {
                    statusEl.classList.add('hidden');
                }, 3000);
            }
        });
    }

    function openModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function openIntakeModal(status) {
        const modal = document.getElementById('intakeModal');
        if (!modal) return;
        
        document.getElementById('intake_status').value = status;
        
        const titleEl = document.getElementById('intake-modal-title');
        const noteContainer = document.getElementById('intake_note_container');
        const noteInput = document.getElementById('intake_note');
        const duplicateContainer = document.getElementById('duplicate_info_container');
        const duplicateInput = document.getElementById('duplicate_sales_info');
        const confirmText = document.getElementById('intake_confirm_text');
        
        noteContainer.classList.add('hidden');
        noteInput.required = false;
        duplicateContainer.classList.add('hidden');
        duplicateInput.required = false;
        confirmText.classList.remove('hidden');
        
        if (status === 'registered') {
            titleEl.textContent = 'Xác nhận: Đăng ký dự án thành công';
            confirmText.textContent = 'Dự án đã được PM/PO đăng ký thành công với Hãng. Trạng thái sẽ chuyển sang giai đoạn theo dõi tiến độ (Update status).';
        } else if (status === 'duplicate') {
            titleEl.textContent = 'Xác nhận: Dự án trùng lặp (Back về Trùng)';
            duplicateContainer.classList.remove('hidden');
            duplicateInput.required = true;
            noteContainer.classList.remove('hidden');
            noteInput.required = false;
            noteInput.placeholder = 'Ghi chú thêm lý do trùng (VD: Hãng Fortinet đã bảo hộ cho đối tác khác, trùng End-User...)...';
            confirmText.classList.add('hidden');
        } else if (status === 'incomplete') {
            titleEl.textContent = 'Xác nhận: Thiếu thông tin đăng ký (Hoàn trả Sales)';
            noteContainer.classList.remove('hidden');
            noteInput.required = true;
            noteInput.placeholder = 'Nhập ghi chú những phần thông tin còn thiếu để Sales sửa lại...';
            confirmText.classList.add('hidden');
        }
        
        modal.classList.remove('hidden');
    }

    function toggleCloseMode(mode) {
        const input = document.getElementById('source_mode_input');
        if (input) input.value = mode;
        
        const syncContainer = document.getElementById('sync_sale_container');
        const manualContainer = document.getElementById('manual_sale_container');
        const tabSync = document.getElementById('tab_mode_sync');
        const tabManual = document.getElementById('tab_mode_manual');
        const poInput = document.getElementById('po_code');
        const valInput = document.getElementById('order_value');
        const dateInput = document.getElementById('order_date');
        const status = document.getElementById('close_status')?.value;

        if (mode === 'sync_sale') {
            if (syncContainer) syncContainer.classList.remove('hidden');
            if (manualContainer) manualContainer.classList.add('hidden');
            if (tabSync) tabSync.className = "flex-1 py-1.5 text-xs font-bold text-center rounded-lg bg-indigo-600 text-white shadow-sm transition-all";
            if (tabManual) tabManual.className = "flex-1 py-1.5 text-xs font-medium text-center rounded-lg bg-transparent text-gray-600 hover:bg-gray-200 transition-all";
            
            if (poInput) poInput.required = false;
            if (valInput) valInput.required = false;
            if (dateInput) dateInput.required = false;
            updateCloseSalePreview();
        } else {
            if (syncContainer) syncContainer.classList.add('hidden');
            if (manualContainer) manualContainer.classList.remove('hidden');
            if (tabSync) tabSync.className = "flex-1 py-1.5 text-xs font-medium text-center rounded-lg bg-transparent text-gray-600 hover:bg-gray-200 transition-all";
            if (tabManual) tabManual.className = "flex-1 py-1.5 text-xs font-bold text-center rounded-lg bg-indigo-600 text-white shadow-sm transition-all";
            
            if (status === 'closed_won') {
                if (poInput) poInput.required = true;
                if (valInput) valInput.required = true;
                if (dateInput) dateInput.required = true;
            }
        }
    }

    function updateCloseSalePreview() {
        const select = document.getElementById('close_sale_id');
        if (!select || select.selectedIndex < 0) return;
        const opt = select.options[select.selectedIndex];
        
        const preview = document.getElementById('close_sale_preview');
        if (!preview) return;

        if (opt && opt.value) {
            const codeEl = document.getElementById('preview_sale_code');
            const custEl = document.getElementById('preview_sale_customer');
            const dateEl = document.getElementById('preview_sale_date');
            const totalEl = document.getElementById('preview_sale_total');
            const itemsEl = document.getElementById('preview_sale_items');

            if (codeEl) codeEl.textContent = opt.dataset.code || '-';
            if (custEl) custEl.textContent = opt.dataset.customer || '-';
            if (dateEl) dateEl.textContent = opt.dataset.dateFormatted || '-';
            if (totalEl) totalEl.textContent = opt.dataset.totalFormatted || '0 đ';
            if (itemsEl) itemsEl.textContent = (opt.dataset.itemsCount || '0') + ' sản phẩm';
            preview.classList.remove('hidden');
        } else {
            preview.classList.add('hidden');
        }
    }

    function toggleCloseStatusFields() {
        const status = document.getElementById('close_status').value;
        const wonFields = document.getElementById('closed_won_fields');
        const reasonFields = document.getElementById('close_reason_fields');
        const reasonSelect = document.getElementById('close_reason');
        const modeInput = document.getElementById('source_mode_input');
        const poInput = document.getElementById('po_code');
        const valInput = document.getElementById('order_value');
        const dateInput = document.getElementById('order_date');

        wonFields.classList.add('hidden');
        reasonFields.classList.add('hidden');
        reasonSelect.required = false;

        if (poInput) poInput.required = false;
        if (valInput) valInput.required = false;
        if (dateInput) dateInput.required = false;

        if (status === 'closed_won') {
            wonFields.classList.remove('hidden');
            const mode = modeInput ? modeInput.value : 'manual';
            if (mode === 'manual') {
                if (poInput) poInput.required = true;
                if (valInput) valInput.required = true;
                if (dateInput) dateInput.required = true;
            } else {
                updateCloseSalePreview();
            }
        } else if (status === 'closed_lost' || status === 'cancelled') {
            reasonFields.classList.remove('hidden');
            reasonSelect.required = true;
        }
    }

    function copyVerticalTemplate() {
        const textEl = document.getElementById('verticalVendorTemplateText');
        if (textEl) {
            textEl.select();
            navigator.clipboard.writeText(textEl.value).then(() => {
                const btn = document.getElementById('copyVerticalTemplateBtn');
                if (btn) {
                    const oldHtml = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-check"></i> <span>Đã sao chép!</span>';
                    btn.classList.remove('bg-indigo-600', 'hover:bg-indigo-700');
                    btn.classList.add('bg-emerald-600');
                    setTimeout(() => {
                        btn.innerHTML = oldHtml;
                        btn.classList.remove('bg-emerald-600');
                        btn.classList.add('bg-indigo-600', 'hover:bg-indigo-700');
                    }, 2500);
                }
            });
        }
    }

    function previewVendorQuoteFiles(input) {
        const container = document.getElementById('vendor_quote_preview_list');
        if (!container) return;
        container.innerHTML = '';
        if (input.files && input.files.length > 0) {
            container.classList.remove('hidden');
            const title = document.createElement('div');
            title.className = 'text-xs font-semibold text-gray-700 mb-1';
            title.innerText = `Đã chọn ${input.files.length} file:`;
            container.appendChild(title);
            Array.from(input.files).forEach((file) => {
                const item = document.createElement('div');
                item.className = 'flex items-center justify-between text-xs bg-indigo-50/70 border border-indigo-100 px-2.5 py-1.5 rounded-lg text-indigo-900';
                const sizeKb = (file.size / 1024).toFixed(1);
                item.innerHTML = `<span class="truncate max-w-[320px] font-medium"><i class="fas fa-file-alt mr-1.5 text-indigo-500"></i>${file.name}</span><span class="text-gray-500 text-[11px] shrink-0 font-mono">${sizeKb} KB</span>`;
                container.appendChild(item);
            });
        } else {
            container.classList.add('hidden');
        }
    }
</script>

<!-- 6. Vertical Vendor Registration Template Modal (ĐKDA 8) -->
@php
    $bomItemsList = \App\Exports\ProjectsExport::parseBomData($project->bom_data);
    if (empty($bomItemsList) && $project->saleItems && $project->saleItems->count() > 0) {
        foreach ($project->saleItems as $sItem) {
            $bomItemsList[] = [
                'pn' => $sItem->product->code ?? '',
                'model' => $sItem->product->name ?? '',
                'qty' => $sItem->quantity ?? 1,
            ];
        }
    }
@endphp
<div id="verticalVendorTemplateModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModal('verticalVendorTemplateModal')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-xl px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
            <div class="flex justify-between items-center pb-3 border-b">
                <div class="flex items-center gap-2">
                    <i class="fas fa-file-alt text-indigo-600 text-lg"></i>
                    <h3 class="text-lg font-bold text-gray-900">Mẫu Đăng ký Dự án dạng Dọc (Gửi Hãng qua Email)</h3>
                </div>
                <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closeModal('verticalVendorTemplateModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="mt-4 space-y-3">
                <p class="text-xs text-gray-500">Mẫu đã được căn chỉnh theo cấu trúc danh sách dọc chuẩn xác, thuận tiện để sao chép trực tiếp vào nội dung email gửi Hãng (Vendor).</p>
                <div class="relative">
                    <textarea id="verticalVendorTemplateText" readonly rows="16" class="w-full font-mono text-xs p-3 bg-gray-50 border border-gray-300 rounded-lg text-gray-800 focus:outline-none select-all leading-relaxed">THÔNG TIN ĐĂNG KÝ DỰ ÁN (DEAL REGISTRATION)
==================================================
1. THÔNG TIN CHUNG
- Mã dự án (Project Code): {{ $project->code }}
- Tên dự án (Project Name): {{ $project->name }}
- Hãng (Vendor): {{ $project->vendor->name ?? 'N/A' }}
- Nhà phân phối (Distributor): {{ $project->distributor ?: 'Tech Horizon Corporation' }}
- AM phụ trách (Distributor AM): {{ $project->manager->name ?? $project->distributor_am ?? 'N/A' }}
- Ngày đăng ký (Deal Reg Date): {{ $project->created_at ? $project->created_at->format('d/m/Y') : '' }}
- Dự kiến chốt (Expected Close Date): {{ $project->end_date ? $project->end_date->format('d/m/Y') : 'N/A' }}
@if($project->net_to_tech_horizon)
- Net to FTN: {{ number_format($project->net_to_tech_horizon, 0, ',', '.') }} VNĐ
@endif

2. THÔNG TIN KHÁCH HÀNG CUỐI (END-USER)
- Tên End-User (Tiếng Việt): {{ $project->eu_name_vi }}
- Tên End-User (Tiếng Anh): {{ $project->eu_name_en ?: 'N/A' }}
- Mã số thuế (Tax Code): {{ $project->eu_tax_code ?: 'N/A' }}
- Địa chỉ (Address): {{ $project->eu_address ?: 'N/A' }}
- Người liên hệ (Contact Person): {{ $project->eu_contact_name ?: 'N/A' }}
- Số điện thoại (Phone): {{ $project->eu_contact_phone ?: 'N/A' }}
- Email: {{ $project->eu_contact_email ?: 'N/A' }}

3. ĐẠI LÝ / ĐỐI TÁC HỢP TÁC (PARTNER / SI)
- Hình thức: {{ $project->collaborate_type == 'direct' ? 'Làm việc trực tiếp End-User' : 'Qua Đại lý / SI' }}
@if($project->collaborate_type != 'direct')
- Tên đại lý (Partner Company): {{ $project->collaborateCustomer?->name_en ?: ($project->collaborate_company ?: 'N/A') }}
- Mã số thuế đại lý: {{ $project->collaborate_tax_code ?: 'N/A' }}
- Người liên hệ: {{ $project->collaborate_contact_name ?: 'N/A' }}
- Số điện thoại: {{ $project->collaborate_contact_phone ?: 'N/A' }}
@endif

4. DANH SÁCH THIẾT BỊ / BOM (BILL OF MATERIALS)
@if(count($bomItemsList) > 0)
@foreach($bomItemsList as $idx => $bItem)
[{{ $idx + 1 }}] Part Number: {{ $bItem['pn'] ?: '-' }} | Model: {{ $bItem['model'] ?: '-' }} | Số lượng: {{ $bItem['qty'] ?: 1 }}
@endforeach
@else
(Chưa có danh sách thiết bị BOM chi tiết)
@endif

5. GHI CHÚ (NOTE)
{{ $project->note ?: '(Không có ghi chú bổ sung)' }}
==================================================</textarea>
                </div>
            </div>
            <div class="mt-5 sm:mt-6 flex justify-end gap-3">
                <button type="button" class="inline-flex justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50" onclick="closeModal('verticalVendorTemplateModal')">Đóng</button>
                <button type="button" id="copyVerticalTemplateBtn" onclick="copyVerticalTemplate()" class="inline-flex items-center gap-1.5 justify-center rounded-lg border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                    <i class="fas fa-copy"></i>
                    <span>Sao chép vào Clipboard</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
