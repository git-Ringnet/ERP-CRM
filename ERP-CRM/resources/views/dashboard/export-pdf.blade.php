<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo Dashboard Kinh Doanh</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        
        .header h1 {
            font-size: 18pt;
            margin-bottom: 5px;
            color: #1a56db;
        }
        
        .header .period {
            font-size: 11pt;
            color: #666;
            margin-bottom: 3px;
        }
        
        .header .generated {
            font-size: 9pt;
            color: #999;
        }
        
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 10px;
            padding: 5px 10px;
            background-color: #f3f4f6;
            border-left: 4px solid #1a56db;
        }
        
        .metrics-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .metric-row {
            display: table-row;
        }
        
        .metric-card {
            display: table-cell;
            width: 50%;
            padding: 10px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }
        
        .metric-label {
            font-size: 9pt;
            color: #6b7280;
            margin-bottom: 3px;
        }
        
        .metric-value {
            font-size: 14pt;
            font-weight: bold;
            color: #111827;
            margin-bottom: 3px;
        }
        
        .metric-growth {
            font-size: 9pt;
        }
        
        .growth-up {
            color: #059669;
        }
        
        .growth-down {
            color: #dc2626;
        }
        
        .growth-neutral {
            color: #6b7280;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .table th {
            background-color: #f3f4f6;
            padding: 8px;
            text-align: left;
            font-size: 9pt;
            border: 1px solid #e5e7eb;
        }
        
        .table td {
            padding: 6px 8px;
            font-size: 9pt;
            border: 1px solid #e5e7eb;
        }
        
        .table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .text-right {
            text-align: right;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8pt;
            color: #999;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
        }
        
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>BÁO CÁO DASHBOARD KINH DOANH</h1>
        <div class="period">Kỳ báo cáo: {{ $period_label }}</div>
        <div class="generated">Ngày xuất: {{ $generated_at }}</div>
    </div>

    {{-- Key Metrics Section --}}
    <div class="section">
        <div class="section-title">Chỉ Số Kinh Doanh Chính</div>
        
        <div class="metrics-grid">
            <div class="metric-row">
                <div class="metric-card">
                    <div class="metric-label">Doanh Thu</div>
                    <div class="metric-value">{{ number_format($data['metrics']['revenue']['current'] ?? 0, 0, ',', '.') }} ₫</div>
                    @if(isset($data['metrics']['revenue']['growth_rate']))
                        @php
                            $growth = $data['metrics']['revenue']['growth_rate'];
                            $class = $growth > 0 ? 'growth-up' : ($growth < 0 ? 'growth-down' : 'growth-neutral');
                            $icon = $growth > 0 ? '↑' : ($growth < 0 ? '↓' : '→');
                        @endphp
                        <div class="metric-growth {{ $class }}">
                            {{ $icon }} {{ number_format(abs($growth), 1, ',', '.') }}% so với kỳ trước
                        </div>
                    @endif
                </div>
                
                <div class="metric-card">
                    <div class="metric-label">Lợi Nhuận</div>
                    <div class="metric-value">{{ number_format($data['metrics']['profit']['current'] ?? 0, 0, ',', '.') }} ₫</div>
                    @if(isset($data['metrics']['profit']['growth_rate']))
                        @php
                            $growth = $data['metrics']['profit']['growth_rate'];
                            $class = $growth > 0 ? 'growth-up' : ($growth < 0 ? 'growth-down' : 'growth-neutral');
                            $icon = $growth > 0 ? '↑' : ($growth < 0 ? '↓' : '→');
                        @endphp
                        <div class="metric-growth {{ $class }}">
                            {{ $icon }} {{ number_format(abs($growth), 1, ',', '.') }}% so với kỳ trước
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="metric-row">
                <div class="metric-card">
                    <div class="metric-label">Tỷ Suất Lợi Nhuận</div>
                    <div class="metric-value">{{ number_format($data['metrics']['profit_margin'] ?? 0, 1, ',', '.') }}%</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-label">Chi Phí Mua Hàng</div>
                    <div class="metric-value">{{ number_format($data['metrics']['purchase_cost']['current'] ?? 0, 0, ',', '.') }} ₫</div>
                    @if(isset($data['metrics']['purchase_cost']['growth_rate']))
                        @php
                            $growth = $data['metrics']['purchase_cost']['growth_rate'];
                            $class = $growth > 0 ? 'growth-up' : ($growth < 0 ? 'growth-down' : 'growth-neutral');
                            $icon = $growth > 0 ? '↑' : ($growth < 0 ? '↓' : '→');
                        @endphp
                        <div class="metric-growth {{ $class }}">
                            {{ $icon }} {{ number_format(abs($growth), 1, ',', '.') }}% so với kỳ trước
                        </div>
                    @endif
                </div>
            </div>
            
            <div class="metric-row">
                <div class="metric-card">
                    <div class="metric-label">Giá Trị Tồn Kho</div>
                    <div class="metric-value">{{ number_format($data['metrics']['inventory_value'] ?? 0, 0, ',', '.') }} ₫</div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-label">Vòng Quay Kho</div>
                    <div class="metric-value">{{ number_format($data['metrics']['inventory_turnover'] ?? 0, 2, ',', '.') }}</div>
                </div>
            </div>
            
            <div class="metric-row">
                <div class="metric-card">
                    <div class="metric-label">Số Đơn Bán Hàng</div>
                    <div class="metric-value">{{ number_format($data['metrics']['sales_count']['current'] ?? 0, 0, ',', '.') }}</div>
                    @if(isset($data['metrics']['sales_count']['growth_rate']))
                        @php
                            $growth = $data['metrics']['sales_count']['growth_rate'];
                            $class = $growth > 0 ? 'growth-up' : ($growth < 0 ? 'growth-down' : 'growth-neutral');
                            $icon = $growth > 0 ? '↑' : ($growth < 0 ? '↓' : '→');
                        @endphp
                        <div class="metric-growth {{ $class }}">
                            {{ $icon }} {{ number_format(abs($growth), 1, ',', '.') }}% so với kỳ trước
                        </div>
                    @endif
                </div>
                
                <div class="metric-card">
                    <div class="metric-label">Số Đơn Mua Hàng</div>
                    <div class="metric-value">{{ number_format($data['metrics']['purchase_orders_count']['current'] ?? 0, 0, ',', '.') }}</div>
                    @if(isset($data['metrics']['purchase_orders_count']['growth_rate']))
                        @php
                            $growth = $data['metrics']['purchase_orders_count']['growth_rate'];
                            $class = $growth > 0 ? 'growth-up' : ($growth < 0 ? 'growth-down' : 'growth-neutral');
                            $icon = $growth > 0 ? '↑' : ($growth < 0 ? '↓' : '→');
                        @endphp
                        <div class="metric-growth {{ $class }}">
                            {{ $icon }} {{ number_format(abs($growth), 1, ',', '.') }}% so với kỳ trước
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Sales Analysis Section --}}
    @if(isset($data['sales_analysis']))
    <div class="section">
        <div class="section-title">Phân Tích Bán Hàng</div>
        
        <table class="table">
            <tr>
                <td><strong>Đơn hoàn thành:</strong></td>
                <td class="text-right">{{ number_format($data['sales_analysis']['completed_count'] ?? 0, 0, ',', '.') }}</td>
                <td><strong>Đơn chờ xử lý:</strong></td>
                <td class="text-right">{{ number_format($data['sales_analysis']['pending_count'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Giá trị trung bình:</strong></td>
                <td class="text-right">{{ number_format($data['sales_analysis']['average_value'] ?? 0, 0, ',', '.') }} ₫</td>
                <td></td>
                <td></td>
            </tr>
        </table>
        
        @if(isset($data['sales_analysis']['top_products']) && count($data['sales_analysis']['top_products']) > 0)
        <div style="margin-top: 15px;">
            <strong>Top 10 Sản Phẩm Bán Chạy:</strong>
            <table class="table" style="margin-top: 5px;">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên Sản Phẩm</th>
                        <th class="text-right">Số Lượng</th>
                        <th class="text-right">Doanh Thu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['sales_analysis']['top_products'] as $index => $product)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $product['name'] ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format($product['quantity'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($product['revenue'] ?? 0, 0, ',', '.') }} ₫</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        
        @if(isset($data['sales_analysis']['top_customers']) && count($data['sales_analysis']['top_customers']) > 0)
        <div style="margin-top: 15px;">
            <strong>Top 10 Khách Hàng:</strong>
            <table class="table" style="margin-top: 5px;">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên Khách Hàng</th>
                        <th class="text-right">Số Đơn</th>
                        <th class="text-right">Tổng Doanh Thu</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['sales_analysis']['top_customers'] as $index => $customer)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $customer['name'] ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format($customer['orders_count'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($customer['revenue'] ?? 0, 0, ',', '.') }} ₫</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endif

    <div class="page-break"></div>

    {{-- Purchase Analysis Section --}}
    @if(isset($data['purchase_analysis']))
    <div class="section">
        <div class="section-title">Phân Tích Mua Hàng</div>
        
        <table class="table">
            <tr>
                <td><strong>Tổng đơn mua:</strong></td>
                <td class="text-right">{{ number_format($data['purchase_analysis']['total_count'] ?? 0, 0, ',', '.') }}</td>
                <td><strong>Đơn hoàn thành:</strong></td>
                <td class="text-right">{{ number_format($data['purchase_analysis']['completed_count'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Đơn chờ xử lý:</strong></td>
                <td class="text-right">{{ number_format($data['purchase_analysis']['pending_count'] ?? 0, 0, ',', '.') }}</td>
                <td><strong>Giá trị trung bình:</strong></td>
                <td class="text-right">{{ number_format($data['purchase_analysis']['average_value'] ?? 0, 0, ',', '.') }} ₫</td>
            </tr>
        </table>
        
        @if(isset($data['purchase_analysis']['top_suppliers']) && count($data['purchase_analysis']['top_suppliers']) > 0)
        <div style="margin-top: 15px;">
            <strong>Top 10 Nhà Cung Cấp:</strong>
            <table class="table" style="margin-top: 5px;">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên Nhà Cung Cấp</th>
                        <th class="text-right">Số Đơn</th>
                        <th class="text-right">Tổng Chi Phí</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['purchase_analysis']['top_suppliers'] as $index => $supplier)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $supplier['name'] ?? 'N/A' }}</td>
                        <td class="text-right">{{ number_format($supplier['orders_count'] ?? 0, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($supplier['cost'] ?? 0, 0, ',', '.') }} ₫</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endif

    {{-- Inventory Analysis Section --}}
    @if(isset($data['inventory_analysis']))
    <div class="section">
        <div class="section-title">Phân Tích Tồn Kho</div>
        
        <table class="table">
            <tr>
                <td><strong>Tổng giá trị tồn kho:</strong></td>
                <td class="text-right">{{ number_format($data['inventory_analysis']['total_value'] ?? 0, 0, ',', '.') }} ₫</td>
                <td><strong>Số sản phẩm:</strong></td>
                <td class="text-right">{{ number_format($data['inventory_analysis']['unique_products'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Tổng số lượng:</strong></td>
                <td class="text-right">{{ number_format($data['inventory_analysis']['total_quantity'] ?? 0, 0, ',', '.') }}</td>
                <td><strong>Vòng quay kho:</strong></td>
                <td class="text-right">{{ number_format($data['inventory_analysis']['turnover_ratio'] ?? 0, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Sản phẩm tồn kho thấp:</strong></td>
                <td class="text-right">{{ number_format($data['inventory_analysis']['low_stock_count'] ?? 0, 0, ',', '.') }}</td>
                <td><strong>Sản phẩm tồn kho cao:</strong></td>
                <td class="text-right">{{ number_format($data['inventory_analysis']['overstock_count'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
    @endif

    <div class="footer">
        <div>Báo cáo được tạo tự động từ hệ thống quản lý</div>
    </div>
</body>
</html>
