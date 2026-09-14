@extends('layouts.app')

@section('title', 'Quản lý Dữ liệu & Sao lưu')
@section('page-title', 'Quản lý Dữ liệu & Sao lưu')

@section('content')
<div class="space-y-6">
    {{-- Header Section --}}
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-900 flex items-center">
                <i class="fas fa-database mr-3 text-primary"></i>
                Sao lưu & Khôi phục Dữ liệu Hệ thống
            </h3>
            <p class="text-gray-600 mt-2 text-sm leading-relaxed">
                Hệ thống hỗ trợ <strong>Sao lưu toàn diện (Full Backup)</strong> bao gồm cả <strong>Cơ sở dữ liệu (Database)</strong> và <strong>Toàn bộ tệp tin / hình ảnh đính kèm</strong> (Chứng từ UNC thanh toán, Hóa đơn điện tử, Báo giá, Tài liệu dự án, Ảnh thiết bị...). 
                Bản sao lưu được đóng gói và mã hóa bằng thuật toán <strong>AES-256-CBC</strong> bảo mật cấp cao.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Export Section --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-4 bg-gray-50 border-b border-gray-200">
                    <h4 class="font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-download mr-2 text-primary"></i>
                        Xuất Bản Sao lưu (Backup)
                    </h4>
                </div>
                <div class="p-6">
                    <form action="{{ route('settings.database.export') }}" method="POST">
                        @csrf
                        
                        {{-- Scope selection --}}
                        <div class="mb-5">
                            <label class="block text-sm font-semibold text-gray-800 mb-2">
                                Phạm vi sao lưu <span class="text-red-500">*</span>
                            </label>
                            <div class="space-y-3">
                                <label class="flex items-start p-3 border border-primary/30 rounded-lg bg-primary/5 hover:bg-primary/10 cursor-pointer transition-colors">
                                    <input type="radio" name="backup_scope" value="full" checked class="mt-1 text-primary focus:ring-primary border-gray-300">
                                    <div class="ml-3">
                                        <span class="block text-sm font-bold text-gray-900">
                                            Sao lưu toàn diện (Full Backup) <span class="text-xs bg-primary text-white px-2 py-0.5 rounded-full ml-1 font-normal">Khuyến nghị</span>
                                        </span>
                                        <span class="block text-xs text-gray-600 mt-0.5">
                                            Bao gồm toàn bộ Database SQL + Toàn bộ tệp/ảnh đính kèm (UNC, Hóa đơn, Báo giá, Hợp đồng, Ticket...).
                                        </span>
                                    </div>
                                </label>

                                <label class="flex items-start p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                    <input type="radio" name="backup_scope" value="db_only" class="mt-1 text-primary focus:ring-primary border-gray-300">
                                    <div class="ml-3">
                                        <span class="block text-sm font-semibold text-gray-900">
                                            Chỉ sao lưu Cơ sở dữ liệu (Database Only)
                                        </span>
                                        <span class="block text-xs text-gray-500 mt-0.5">
                                            Chỉ xuất cấu trúc và dữ liệu bảng SQL (dung lượng nhẹ, không bao gồm tệp tải lên).
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Encryption Password --}}
                        <div class="mb-5">
                            <label class="block text-sm font-semibold text-gray-800 mb-2">
                                Mật khẩu mã hóa bảo vệ <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="password" required minlength="8"
                                   placeholder="Nhập mật khẩu mã hóa (tối thiểu 8 ký tự)"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                            <p class="text-xs text-gray-500 mt-1.5">
                                Mật khẩu này dùng để mã hóa file sao lưu. Hệ thống sẽ tự động lưu lại mật khẩu này trong lịch sử để bạn có thể xem lại khi cần.
                            </p>
                        </div>

                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-primary text-white font-medium rounded-lg hover:bg-primary-dark shadow-sm transition-colors">
                            <i class="fas fa-file-export mr-2"></i> Xuất bản sao lưu mã hóa (.enc)
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Import Section --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col justify-between">
            <div>
                <div class="p-4 bg-gray-50 border-b border-gray-200">
                    <h4 class="font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-upload mr-2 text-green-600"></i>
                        Khôi phục Dữ liệu (Restore)
                    </h4>
                </div>
                <div class="p-6">
                    <form action="{{ route('settings.database.import') }}" method="POST" enctype="multipart/form-data" 
                          onsubmit="return confirm('CẢNH BÁO NGUY HIỂM: Việc khôi phục sẽ ghi đè lên toàn bộ cơ sở dữ liệu và tệp đính kèm hiện tại. Bạn có chắc chắn muốn tiến hành?')">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-800 mb-2">
                                Chọn file sao lưu (.enc) <span class="text-red-500">*</span>
                            </label>
                            <input type="file" name="backup_file" required accept=".enc"
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-dark cursor-pointer">
                            <p class="text-xs text-gray-500 mt-1.5">
                                Hệ thống sẽ tự động nhận diện gói sao lưu (Toàn diện Database + Files hoặc chỉ Database) để khôi phục chính xác.
                            </p>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-800 mb-2">
                                Mật khẩu giải mã <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="password" required
                                   placeholder="Nhập mật khẩu đã dùng khi xuất bản sao lưu"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                        </div>
                        <div class="mb-6">
                            <label class="flex items-start cursor-pointer">
                                <input type="checkbox" name="confirm_restore" required class="rounded border-gray-300 text-red-600 focus:ring-red-500 mt-0.5 mr-2">
                                <span class="text-xs text-gray-700 font-medium">
                                    Tôi hiểu rằng dữ liệu hiện tại sẽ bị ghi đè hoàn toàn theo bản sao lưu và thao tác này không thể hoàn tác.
                                </span>
                            </label>
                        </div>
                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 shadow-sm transition-colors">
                            <i class="fas fa-undo mr-2"></i> Tiến hành Khôi phục ngay
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Note Banner --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-5">
        <h4 class="font-bold text-blue-900 mb-2 flex items-center text-sm">
            <i class="fas fa-shield-alt mr-2 text-blue-600"></i> Cơ chế Bảo vệ An toàn Dữ liệu:
        </h4>
        <ul class="list-disc list-inside text-blue-800 space-y-1.5 text-xs">
            <li>Bản sao lưu được bảo mật bằng tiêu chuẩn mã hóa đối xứng <strong>AES-256-CBC</strong>.</li>
            <li>Bản sao lưu toàn diện chứa đầy đủ dữ liệu bảng SQL và tất cả tệp lưu trữ trong <code>storage/app/public</code> và <code>storage/app/technical_tickets</code>.</li>
            <li>Hệ thống lưu trữ mật khẩu sao lưu đã mã hóa ở bảng <strong>Lịch sử Sao lưu</strong> bên dưới, chỉ Quản trị viên sau khi xác thực mật khẩu tài khoản mới có thể tra cứu lại.</li>
        </ul>
    </div>

    {{-- History Section --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h4 class="font-semibold text-gray-800 flex items-center">
                <i class="fas fa-history mr-2 text-purple-600"></i>
                Lịch sử Sao lưu
            </h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3">Ngày tạo</th>
                        <th class="px-6 py-3">Tên file sao lưu</th>
                        <th class="px-6 py-3">Loại sao lưu</th>
                        <th class="px-6 py-3">Dung lượng</th>
                        <th class="px-6 py-3">Người thực hiện</th>
                        <th class="px-6 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($backups as $backup)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                            {{ $backup->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 font-mono text-xs text-gray-700">
                            {{ $backup->filename }}
                        </td>
                        <td class="px-6 py-4">
                            @if(str_contains($backup->filename, 'full'))
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">
                                    <i class="fas fa-archive mr-1"></i> Toàn bộ (DB + Files)
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                                    <i class="fas fa-database mr-1"></i> Database Only
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $backup->size ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $backup->user->name ?? 'Hệ thống' }}</td>
                        <td class="px-6 py-4 text-right space-x-3 whitespace-nowrap">
                            <button onclick="requestPassword({{ $backup->id }}, '{{ $backup->filename }}')" 
                                    class="text-primary hover:text-primary-dark font-medium inline-flex items-center">
                                <i class="fas fa-key mr-1"></i> Xem mật khẩu
                            </button>
                            <form action="{{ route('settings.database.destroy', $backup->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700" onclick="return confirm('Xóa bản ghi này khỏi lịch sử? (Lưu ý: Không xóa file vật lý bạn đã tải về máy)')" title="Xóa lịch sử">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                            Chưa có lịch sử sao lưu nào.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Password Retrieval Modal --}}
<div id="passwordModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 overflow-hidden">
        <div class="p-5 border-b border-gray-200 flex justify-between items-center bg-gray-50">
            <h5 class="text-base font-bold text-gray-900 flex items-center">
                <i class="fas fa-key text-primary mr-2"></i> Xem mật khẩu sao lưu
            </h5>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-600 mb-4">
                Để bảo mật, vui lòng nhập <strong>mật khẩu đăng nhập</strong> hiện tại của bạn để hiển thị mật khẩu cho file:<br>
                <code id="targetFilename" class="text-primary font-bold text-xs mt-1 block break-all bg-gray-100 p-1.5 rounded"></code>
            </p>
            <div id="passwordDisplay" class="hidden mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-center">
                <p class="text-xs text-gray-500 mb-1 uppercase font-bold tracking-wider">Mật khẩu của file sao lưu:</p>
                <div class="flex items-center justify-center gap-2">
                    <span id="revealedPassword" class="text-xl font-mono font-bold text-gray-900"></span>
                    <button onclick="copyPassword()" class="text-gray-500 hover:text-primary p-1" title="Sao chép">
                        <i class="fas fa-copy text-lg"></i>
                    </button>
                </div>
            </div>
            <div id="authFormSection">
                <input type="password" id="current_login_password" 
                       placeholder="Nhập mật khẩu đăng nhập của bạn"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                <button onclick="submitPasswordRequest()" id="submitBtn"
                        class="w-full bg-primary text-white py-2 rounded-lg hover:bg-primary-dark transition-colors flex justify-center items-center text-sm font-medium">
                    <span>Xác nhận & Xem mật khẩu</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentBackupId = null;

    function requestPassword(id, filename) {
        currentBackupId = id;
        document.getElementById('targetFilename').innerText = filename;
        document.getElementById('passwordModal').style.display = 'flex';
        document.getElementById('passwordDisplay').classList.add('hidden');
        document.getElementById('authFormSection').classList.remove('hidden');
        document.getElementById('current_login_password').value = '';
    }

    function closeModal() {
        document.getElementById('passwordModal').style.display = 'none';
    }

    async function submitPasswordRequest() {
        const password = document.getElementById('current_login_password').value;
        if (!password) return;

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Đang xác thực...';

        try {
            const response = await fetch(`/settings/database/show-password/${currentBackupId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ current_password: password })
            });

            const data = await response.json();

            if (response.ok) {
                document.getElementById('revealedPassword').innerText = data.password;
                document.getElementById('passwordDisplay').classList.remove('hidden');
                document.getElementById('authFormSection').classList.add('hidden');
            } else {
                alert(data.message || 'Có lỗi xảy ra.');
            }
        } catch (error) {
            alert('Lỗi kết nối server.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<span>Xác nhận & Xem mật khẩu</span>';
        }
    }

    function copyPassword() {
        const pass = document.getElementById('revealedPassword').innerText;
        navigator.clipboard.writeText(pass).then(() => {
            alert('Đã sao chép mật khẩu!');
        });
    }

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
</script>
@endsection
