<table>
    <!-- Header Title -->
    <tr>
        <th colspan="6" style="font-size: 16pt; font-weight: bold; text-align: center; color: #1E3A8A; height: 35px; vertical-align: middle;">
            THÔNG TIN ĐĂNG KÝ DỰ ÁN (PROJECT REGISTRATION FORM)
        </th>
    </tr>
    <tr>
        <th colspan="6" style="font-size: 10pt; font-style: italic; text-align: center; color: #64748B; height: 20px;">
            Mã dự án: {{ $project->code }} | Ngày gửi: {{ date('d/m/Y') }} | Hãng: {{ $project->vendor?->name ?? 'N/A' }}
        </th>
    </tr>
    <tr><td colspan="6" style="height: 10px;"></td></tr>

    <!-- SECTION 1: DISTRIBUTOR INFO -->
    <tr style="background-color: #1E40AF; color: #FFFFFF; font-weight: bold;">
        <th colspan="6" style="height: 25px; vertical-align: middle; padding-left: 8px;">
            1. THÔNG TIN NHÀ PHÂN PHỐI (DISTRIBUTOR INFORMATION)
        </th>
    </tr>
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9; width: 250px;">Nhà phân phối (Distributor)</td>
        <td colspan="5">CÔNG TY CỔ PHẦN RINGNET / TECH HORIZON</td>
    </tr>
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Distributor AM (Sales phụ trách)</td>
        <td colspan="5">{{ $project->distributor_am ?: ($project->manager?->email . ' | ' . $project->manager?->name) }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Đội ngũ xử lý (Team)</td>
        <td colspan="5">{{ $project->assigned_team === 'po_team' ? 'PO Team (Fortinet / FTN)' : 'PM Team (Non-FTN)' }}</td>
    </tr>
    <tr><td colspan="6" style="height: 10px;"></td></tr>

    <!-- SECTION 2: PARTNER INFO -->
    <tr style="background-color: #1E40AF; color: #FFFFFF; font-weight: bold;">
        <th colspan="6" style="height: 25px; vertical-align: middle; padding-left: 8px;">
            2. THÔNG TIN ĐỐI TÁC / ĐẠI LÝ (PARTNER / SI INFORMATION)
        </th>
    </tr>
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Hình thức hợp tác</td>
        <td colspan="5">{{ $project->collaborate_type === 'partner' ? 'Qua đối tác / Đại lý (Tier-2 / SI)' : 'Bán trực tiếp End-User' }}</td>
    </tr>
    @if($project->collaborate_type === 'partner')
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Tên công ty đối tác (Partner)</td>
        <td colspan="5">{{ $project->collaborate_company ?: ($project->collaborateCustomer?->name ?? 'N/A') }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Mã số thuế đối tác</td>
        <td colspan="5">{{ $project->collaborate_tax_code ?: ($project->collaborateCustomer?->tax_code ?? 'N/A') }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Người liên hệ (PIC)</td>
        <td colspan="2">{{ $project->collaborate_pic_name ?: 'N/A' }}</td>
        <td style="font-weight: bold; background-color: #F1F5F9;">Chức danh</td>
        <td colspan="2">{{ $project->collaborate_pic_title ?: 'N/A' }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Số điện thoại PIC</td>
        <td colspan="2">{{ $project->collaborate_pic_phone ?: 'N/A' }}</td>
        <td style="font-weight: bold; background-color: #F1F5F9;">Email PIC</td>
        <td colspan="2">{{ $project->collaborate_pic_email ?: 'N/A' }}</td>
    </tr>
    @else
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Ghi chú hợp tác</td>
        <td colspan="5">Bán trực tiếp cho khách hàng cuối (End-User)</td>
    </tr>
    @endif
    <tr><td colspan="6" style="height: 10px;"></td></tr>

    <!-- SECTION 3: END-USER INFO -->
    <tr style="background-color: #1E40AF; color: #FFFFFF; font-weight: bold;">
        <th colspan="6" style="height: 25px; vertical-align: middle; padding-left: 8px;">
            3. THÔNG TIN KHÁCH HÀNG CUỐI (END-USER INFORMATION)
        </th>
    </tr>
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Tên khách hàng (Tiếng Việt)</td>
        <td colspan="5">{{ $project->eu_name_vi ?: ($project->customer_name ?: 'N/A') }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Tên khách hàng (Tiếng Anh)</td>
        <td colspan="5">{{ $project->eu_name_en ?: 'N/A' }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Tên viết tắt</td>
        <td colspan="2">{{ $project->eu_name_abbr ?: 'N/A' }}</td>
        <td style="font-weight: bold; background-color: #F1F5F9;">Mã số thuế / Website</td>
        <td colspan="2">{{ $project->eu_tax_code ?: 'N/A' }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Địa chỉ lắp đặt / triển khai</td>
        <td colspan="5">{{ $project->address ?: 'N/A' }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Tỉnh / Thành phố</td>
        <td colspan="2">{{ $project->eu_province ?: 'N/A' }}</td>
        <td style="font-weight: bold; background-color: #F1F5F9;">Ngành nghề (Industry)</td>
        <td colspan="2">{{ htmlspecialchars($project->eu_industry ? ($industries[$project->eu_industry] ?? $project->eu_industry) : 'N/A') }}</td>
    </tr>
    <tr><td colspan="6" style="height: 10px;"></td></tr>

    <!-- SECTION 4: PROJECT DETAILS -->
    <tr style="background-color: #1E40AF; color: #FFFFFF; font-weight: bold;">
        <th colspan="6" style="height: 25px; vertical-align: middle; padding-left: 8px;">
            4. THÔNG TIN DỰ ÁN &amp; DEAL (PROJECT &amp; OPPORTUNITY DETAILS)
        </th>
    </tr>
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Tên dự án (Project Name)</td>
        <td colspan="5" style="font-weight: bold; color: #0F172A;">{{ $project->name }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Hãng đăng ký (Vendor)</td>
        <td colspan="2" style="font-weight: bold;">{{ $project->vendor?->name ?? 'N/A' }}</td>
        <td style="font-weight: bold; background-color: #F1F5F9;">Mã Deal Hãng (Vendor Deal ID)</td>
        <td colspan="2">{{ $project->vendor_deal_id ?: 'Chưa có' }}</td>
    </tr>
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Loại Deal (Deal Type)</td>
        <td colspan="2">{{ $project->deal_type ? strtoupper($project->deal_type) : 'Standard' }}</td>
        <td style="font-weight: bold; background-color: #F1F5F9;">Ngày chốt dự kiến (Close Date)</td>
        <td colspan="2">{{ $project->end_date ? $project->end_date->format('d/m/Y') : 'N/A' }}</td>
    </tr>
    @if($project->net_to_tech_horizon)
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Net to FTN (NTF)</td>
        <td colspan="5" style="font-weight: bold; color: #2563EB;">{{ number_format($project->net_to_tech_horizon) }} USD</td>
    </tr>
    @endif
    @if($project->budget)
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Ngân sách dự kiến (VND)</td>
        <td colspan="5">{{ number_format($project->budget) }} ₫</td>
    </tr>
    @endif
    @if($project->sn_numbers)
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Note / Serial cũ (Trade-Up/Renewal)</td>
        <td colspan="5">{{ $project->sn_numbers }}</td>
    </tr>
    @endif
    @if($project->special_request_type || $project->special_request_note)
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Yêu cầu đặc biệt tới Hãng</td>
        <td colspan="5">
            <strong>{{ $project->special_request_type ?? 'Yêu cầu' }}:</strong> 
            {{ $project->special_request_note ?? '' }}
        </td>
    </tr>
    @endif
    @if($project->description)
    <tr>
        <td style="font-weight: bold; background-color: #F1F5F9;">Mô tả / Phạm vi dự án</td>
        <td colspan="5">{{ $project->description }}</td>
    </tr>
    @endif
    <tr><td colspan="6" style="height: 10px;"></td></tr>

    <!-- SECTION 5: BOM ITEMS -->
    <tr style="background-color: #1E40AF; color: #FFFFFF; font-weight: bold;">
        <th colspan="6" style="height: 25px; vertical-align: middle; padding-left: 8px;">
            5. DANH MỤC THIẾT BỊ / SẢN PHẨM DỰ ÁN (BILL OF MATERIALS - BOM)
        </th>
    </tr>
    <tr style="background-color: #E2E8F0; font-weight: bold; text-align: center;">
        <th style="width: 50px; border: 1px solid #CBD5E1;">STT</th>
        <th style="width: 200px; border: 1px solid #CBD5E1;">Part Number / SKU</th>
        <th colspan="2" style="width: 350px; border: 1px solid #CBD5E1;">Tên sản phẩm &amp; Mô tả chi tiết</th>
        <th style="width: 80px; border: 1px solid #CBD5E1;">Số lượng</th>
        <th style="width: 150px; border: 1px solid #CBD5E1;">Ghi chú</th>
    </tr>
    @if(!empty($bomItems) && count($bomItems) > 0)
        @foreach($bomItems as $index => $item)
        <tr>
            <td style="text-align: center; border: 1px solid #CBD5E1;">{{ $index + 1 }}</td>
            <td style="font-family: monospace; font-weight: bold; border: 1px solid #CBD5E1;">{{ $item['product_code'] ?? ($item['sku'] ?? 'N/A') }}</td>
            <td colspan="2" style="border: 1px solid #CBD5E1;">{{ $item['product_name'] ?? ($item['name'] ?? ($item['description'] ?? '')) }}</td>
            <td style="text-align: center; font-weight: bold; border: 1px solid #CBD5E1;">{{ $item['quantity'] ?? 1 }}</td>
            <td style="border: 1px solid #CBD5E1;">{{ $item['note'] ?? ($item['notes'] ?? '') }}</td>
        </tr>
        @endforeach
    @else
        <tr>
            <td style="text-align: center; border: 1px solid #CBD5E1;">1</td>
            <td colspan="5" style="border: 1px solid #CBD5E1; color: #64748B; font-style: italic;">
                {{ !empty($project->bom_data) ? $project->bom_data : 'Chi tiết danh mục xem tại file BOM đính kèm.' }}
            </td>
        </tr>
    @endif
</table>
