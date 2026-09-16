<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TechnicalTicket;
use App\Models\TechnicalSupportLog;
use App\Models\TechnicalTicketComment;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Project;
use App\Models\Opportunity;
use App\Models\Sale;
use Carbon\Carbon;

class TechnicalTicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();
        $adminId = $admin ? $admin->id : 1;

        $techEngineers = User::whereHas('roles', fn($q) => $q->whereIn('slug', ['technical_engineer', 'technical_lead', 'technical_manager', 'technical']))
            ->get();
        if ($techEngineers->isEmpty()) {
            $techEngineers = User::take(5)->get();
        }

        $salesUsers = User::whereHas('roles', fn($q) => $q->whereIn('slug', ['sales_staff', 'sales_manager', 'sales_lead', 'sales']))
            ->get();
        if ($salesUsers->isEmpty()) {
            $salesUsers = User::take(3)->get();
        }

        $teamLead = $techEngineers->first();
        $customers = Customer::all();
        $suppliers = Supplier::all();
        $projects = Project::all();
        $opportunities = Opportunity::all();
        $sales = Sale::all();

        $sampleTickets = [
            [
                'title' => 'Triển khai lắp đặt & Cấu hình Cụm Firewall HA FortiGate 200F tại Datacenter',
                'description' => "Yêu cầu kỹ thuật:\n1. Lắp đặt 02 thiết bị FortiGate 200F lên Rack 42U tại Datacenter.\n2. Cấu hình High Availability (HA Active-Passive).\n3. Cấu hình phân chia VLAN, Policy bảo mật và SD-WAN liên kết 2 đường truyền ISP.\n4. Bàn giao tài liệu cấu hình và hướng dẫn vận hành sơ bộ.",
                'work_type' => 'deployment',
                'priority' => 'urgent',
                'status' => 'in_progress',
                'sla_days' => 5,
                'logs' => [
                    [
                        'support_content' => 'Tiếp nhận yêu cầu, khảo sát sơ đồ mạng và kiểm tra thiết bị tại kho.',
                        'status' => 'assigned',
                        'notes' => 'Đã kiểm tra CO/CQ và serial number trùng khớp với phiếu xuất kho.',
                    ],
                    [
                        'support_content' => 'Onsite Datacenter: Gắn thiết bị lên rack, đấu nối cáp quang 10G và cáp đồng HA Heartbeat.',
                        'status' => 'open',
                        'notes' => 'Nguồn điện Redundant A+B hoạt động ổn định. Đèn status xanh.',
                    ],
                    [
                        'support_content' => 'Cấu hình hoàn tất Cluster HA, đồng bộ session và test failover thành công dưới 1 giây.',
                        'status' => 'pending',
                        'notes' => 'Đang chờ khách hàng nghiệm thu kiểm tra lưu lượng thực tế vào buổi tối.',
                    ]
                ],
                'comments' => [
                    'Khách hàng yêu cầu thực hiện cắt chuyển hệ thống chính vào khung giờ 22:00 - 24:00 để tránh ảnh hưởng giao dịch.',
                    'Đã chuẩn bị sẵn cấu hình Rollback dự phòng nếu có sự cố phát sinh.'
                ]
            ],
            [
                'title' => 'POC giải pháp Giám sát An ninh mạng SIEM & SOC cho Khách hàng',
                'description' => "Mục tiêu POC:\n- Triển khai máy ảo phân tích Log và phát hiện xâm nhập trái phép.\n- Tích hợp thu thập log từ 50 máy chủ Windows/Linux và 10 thiết bị mạng.\n- Đánh giá khả năng cảnh báo sớm các nguy cơ ransomware và tấn công brute-force.",
                'work_type' => 'POC',
                'priority' => 'high',
                'status' => 'assigned',
                'sla_days' => 14,
                'logs' => [
                    [
                        'support_content' => 'Họp kick-off POC với phòng IT khách hàng, thống nhất phạm vi và tiêu chí đánh giá.',
                        'status' => 'assigned',
                        'notes' => 'Khách hàng cung cấp 1 VM 16 vCPU, 32GB RAM để cài đặt Collector.',
                    ],
                    [
                        'support_content' => 'Cài đặt xong Agent thu thập dữ liệu trên 20 máy chủ quan trọng ban đầu.',
                        'status' => 'open',
                        'notes' => 'Log đang đẩy về Dashboard SIEM thời gian thực.',
                    ]
                ],
                'comments' => [
                    'Đã gửi checklist các kịch bản tấn công mẫu để khách hàng nghiệm thu tính năng.'
                ]
            ],
            [
                'title' => 'Khảo sát và tư vấn nâng cấp hạ tầng mạng Core Switch 100G cho Nhà máy',
                'description' => "Nội dung công việc:\n- Khảo sát thực địa hệ thống tủ Rack và đường cáp quang liên tòa nhà.\n- Đo kiểm suy hao đường truyền quang Multimode / Singlemode.\n- Lập sơ đồ topology đề xuất nâng cấp mạng Core lên chuẩn 40G/100G đáp ứng lưu lượng dây chuyền tự động hóa.",
                'work_type' => 'survey',
                'priority' => 'medium',
                'status' => 'completed',
                'sla_days' => 7,
                'logs' => [
                    [
                        'support_content' => 'Khảo sát thực địa 6 phòng máy phụ (IDF) và 1 phòng máy chính (MDF).',
                        'status' => 'completed',
                        'notes' => 'Tất cả đường cáp quang hiện tại đạt chuẩn OM3, đáp ứng tốt Module 40G-SR4.',
                    ],
                    [
                        'support_content' => 'Hoàn thành bản vẽ Topology mạng đề xuất và bảng tổng hợp khối lượng thiết bị.',
                        'status' => 'completed',
                        'notes' => 'Đã gửi bàn giao cho Sales Owner để làm báo giá cho khách hàng.',
                    ]
                ],
                'comments' => [
                    'Khảo sát đã hoàn thành trước thời hạn SLA 2 ngày.',
                    'Đã cập nhật file bản vẽ AutoCAD và Sơ đồ mạng Visio vào hồ sơ dự án.'
                ]
            ],
            [
                'title' => 'Hỗ trợ khẩn cấp: Khắc phục sự cố gián đoạn đường truyền VPN Site-to-Site',
                'description' => "Mô tả sự cố:\n- Chi nhánh mất kết nối tới máy chủ ERP tại Head Office từ 08:30 sáng.\n- Nghi ngờ IP WAN tại chi nhánh bị đổi sau sự cố bảo trì của nhà mạng Viettel.\n- Cần can thiệp cấu hình lại IPsec VPN và kiểm tra bảng định tuyến.",
                'work_type' => 'after_sales',
                'priority' => 'urgent',
                'status' => 'completed',
                'sla_days' => 1,
                'logs' => [
                    [
                        'support_content' => 'Remote vào Firewall chi nhánh qua 4G dự phòng, xác định IP WAN mới từ nhà mạng.',
                        'status' => 'completed',
                        'notes' => 'Cập nhật lại Peer IP trên Firewall trung tâm và Phase 1/Phase 2 IPsec.',
                    ],
                    [
                        'support_content' => 'Đường hầm VPN đã UP trở lại, ping thông suốt và truy cập ERP bình thường.',
                        'status' => 'closed',
                        'notes' => 'Thời gian downtime tổng cộng 25 phút. Hệ thống hoạt động ổn định.',
                    ]
                ],
                'comments' => [
                    'Khuyến nghị khách hàng đăng ký gói IP tĩnh (Static IP) cho chi nhánh để tránh lặp lại tình trạng này.'
                ]
            ],
            [
                'title' => 'Tư vấn bóc tách cấu hình BOM & Sizing hệ thống Lưu trữ SAN Storage',
                'description' => "Yêu cầu bóc tách giải pháp:\n- Dung lượng Usable tối thiểu 100TB All-Flash hoặc Hybrid NVMe/SSD.\n- Hỗ trợ kết nối Fibre Channel 32Gbps và iSCSI 25GbE.\n- Tính toán IOPS dự kiến cho 150 VM cơ sở dữ liệu Oracle & SQL Server.",
                'work_type' => 'BOM',
                'priority' => 'medium',
                'status' => 'open',
                'sla_days' => 3,
                'logs' => [
                    [
                        'support_content' => 'Tiếp nhận thông tin sizing từ Sales, đang chạy công cụ tính toán dung lượng hãng.',
                        'status' => 'open',
                        'notes' => 'Sử dụng công cụ Sizer Tool của Dell EMC và HPE để so sánh 2 phương án tối ưu.',
                    ]
                ],
                'comments' => [
                    'Cần xác nhận lại với khách hàng về tỷ lệ nén dữ liệu (Data Reduction/Deduplication) kỳ vọng.'
                ]
            ],
            [
                'title' => 'Đào tạo chuyển giao công nghệ quản trị hệ thống Cloud Backup & Disaster Recovery',
                'description' => "Khóa đào tạo 02 buổi bao gồm:\n- Buổi 1: Kiến trúc hệ thống sao lưu dự phòng, tạo Backup Job định kỳ và cơ chế mã hóa dữ liệu bất biến (Immutability).\n- Buổi 2: Diễn tập quy trình phục hồi thảm họa (Disaster Recovery Drill), Instant VM Recovery khi máy chủ chính gặp sự cố.",
                'work_type' => 'training',
                'priority' => 'low',
                'status' => 'waiting',
                'sla_days' => 10,
                'logs' => [
                    [
                        'support_content' => 'Biên soạn slide đào tạo và tài liệu Lab thực hành cho 5 kỹ sư IT của đối tác.',
                        'status' => 'pending',
                        'notes' => 'Đang chờ đối tác chốt danh sách học viên và phòng họp đào tạo.',
                    ]
                ],
                'comments' => [
                    'Lịch đào tạo dự kiến dời sang tuần sau theo đề nghị của khách hàng.'
                ]
            ],
            [
                'title' => 'Speaker kỹ thuật: Trình bày chuyên đề Bảo mật Zero Trust tại Hội thảo Doanh nghiệp',
                'description' => "Nội dung thuyết trình:\n- Thực trạng tấn công chuỗi cung ứng và rò rỉ dữ liệu trong kỷ nguyên Hybrid Work.\n- 5 trụ cột kiến trúc mạng Zero Trust (ZTNA, SASE, IAM, Micro-segmentation, Threat Intelligence).\n- Demo live kịch bản phòng chống tấn công lừa đảo qua email (Phishing Simulation).",
                'work_type' => 'event',
                'priority' => 'medium',
                'status' => 'completed',
                'sla_days' => 5,
                'logs' => [
                    [
                        'support_content' => 'Chuẩn bị bài thuyết trình 45 phút và bộ kit demo mạng phòng thủ.',
                        'status' => 'completed',
                        'notes' => 'Đã duyệt nội dung bài thuyết trình với Ban Giám đốc.',
                    ],
                    [
                        'support_content' => 'Thực hiện thuyết trình tại sự kiện, trả lời câu hỏi chuyên sâu từ hơn 60 khách mời.',
                        'status' => 'completed',
                        'notes' => 'Sự kiện diễn ra thành công, nhiều đối tác quan tâm đặt lịch demo trực tiếp.',
                    ]
                ],
                'comments' => [
                    'Có 4 khách hàng tiềm năng đã yêu cầu tư vấn giải pháp ZTNA sau hội thảo.'
                ]
            ],
            [
                'title' => 'Cập nhật Firmware và vá lỗ hổng bảo mật định kỳ cho Cụm Core Router',
                'description' => "Kế hoạch bảo trì:\n- Cập nhật phiên bản hệ điều hành ổn định (Recommended Patch Release) cho cụm Router chính.\n- Kiểm tra khả năng tương thích của các giao thức định tuyến động BGP/OSPF.\n- Sao lưu Full Configuration trước và sau khi thực hiện nâng cấp.",
                'work_type' => 'documentation',
                'priority' => 'high',
                'status' => 'in_progress',
                'sla_days' => 4,
                'logs' => [
                    [
                        'support_content' => 'Tải Firmware chính hãng, kiểm tra mã Hash SHA-256 và dựng Lab kiểm thử trước.',
                        'status' => 'open',
                        'notes' => 'Lab test chạy ổn định trong 48 giờ, không ghi nhận crash hay leak memory.',
                    ]
                ],
                'comments' => [
                    'Lịch bảo trì chính thức đã gửi thông báo đến các phòng ban liên quan.'
                ]
            ]
        ];

        $createdCount = 0;

        foreach ($sampleTickets as $idx => $ticketData) {
            $engineer = $techEngineers->get($idx % $techEngineers->count());
            $salesOwner = $salesUsers->get($idx % $salesUsers->count());
            $customer = $customers->isNotEmpty() ? $customers->get($idx % $customers->count()) : null;
            $project = $projects->isNotEmpty() ? $projects->get($idx % $projects->count()) : null;
            $opportunity = $opportunities->isNotEmpty() ? $opportunities->get($idx % $opportunities->count()) : null;
            $supplier = $suppliers->isNotEmpty() ? $suppliers->get($idx % $suppliers->count()) : null;
            $sale = $sales->isNotEmpty() ? $sales->get($idx % $sales->count()) : null;

            $code = 'TCK-' . date('Ym') . '-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT);

            $ticket = TechnicalTicket::updateOrCreate(
                ['title' => $ticketData['title']],
                [
                    'code' => $code,
                    'description' => $ticketData['description'],
                    'work_type' => $ticketData['work_type'],
                    'priority' => $ticketData['priority'],
                    'status' => $ticketData['status'],
                    'customer_id' => $customer?->id,
                    'project_id' => $project?->id,
                    'opportunity_id' => $opportunity?->id,
                    'sale_id' => $sale?->id,
                    'supplier_id' => $supplier?->id,
                    'assigned_to' => $engineer?->id ?? $adminId,
                    'team_lead_id' => $teamLead?->id ?? $adminId,
                    'sales_owner_id' => $salesOwner?->id ?? $adminId,
                    'created_by' => $adminId,
                    'department' => 'Technical',
                    'project_name' => $project?->name ?? 'Dự án Doanh nghiệp tiêu chuẩn',
                    'sla_deadline' => now()->addDays($ticketData['sla_days']),
                    'resolved_at' => in_array($ticketData['status'], ['completed', 'closed']) ? now()->subDays(rand(1, 3)) : null,
                ]
            );

            // Gán kỹ sư phụ trách
            if ($engineer) {
                $ticket->assignedEngineers()->syncWithoutDetaching([$engineer->id]);
            }

            // Tạo Support Logs
            if (isset($ticketData['logs']) && $ticket->supportLogs()->count() === 0) {
                foreach ($ticketData['logs'] as $logIdx => $logData) {
                    TechnicalSupportLog::create([
                        'technical_ticket_id' => $ticket->id,
                        'user_id' => $engineer?->id ?? $adminId,
                        'log_date' => now()->subDays(max(0, 3 - $logIdx)),
                        'serial_number' => 'FG-200F-LAB-' . str_pad($ticket->id * 10 + $logIdx, 4, '0', STR_PAD_LEFT),
                        'support_content' => $logData['support_content'],
                        'status' => $logData['status'],
                        'customer_info' => $customer ? $customer->name : 'Khách hàng Doanh nghiệp',
                        'contact_info' => $customer ? ($customer->phone ?? '0901234567') : '0901234567',
                        'notes' => $logData['notes'],
                    ]);
                }
            }

            // Tạo Comments
            if (isset($ticketData['comments']) && $ticket->comments()->count() === 0) {
                foreach ($ticketData['comments'] as $cIdx => $commentText) {
                    TechnicalTicketComment::create([
                        'technical_ticket_id' => $ticket->id,
                        'user_id' => ($cIdx % 2 === 0) ? ($salesOwner?->id ?? $adminId) : ($engineer?->id ?? $adminId),
                        'comment' => $commentText,
                    ]);
                }
            }

            $createdCount++;
        }

        $this->command->info("Đã tạo thành công {$createdCount} phiếu yêu cầu kỹ thuật (Technical Tickets) chất lượng cao!");
    }
}
