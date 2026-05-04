@extends('layouts.app')

@section('title', 'Quản lý Dữ liệu')
@section('page-title', 'Quản lý Dữ liệu')

@section('content')
<div class="space-y-6">
    {{-- Header Section --}}
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-xl font-bold text-gray-900">
                <i class="fas fa-database mr-2 text-primary"></i>
                Sao lưu & Khôi phục Dữ liệu
            </h3>
            <p class="text-gray-600 mt-2">
                Hệ thống hỗ trợ xuất file SQL được mã hóa để bảo mật dữ liệu. Vui lòng ghi nhớ mật khẩu mã hóa vì không thể khôi phục dữ liệu nếu mất mật khẩu.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Export Section --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 bg-gray-50 border-b border-gray-200">
                <h4 class="font-semibold text-gray-800">
                    <i class="fas fa-download mr-1 text-primary"></i>
                    Xuất Dữ liệu (Backup)
                </h4>
            </div>
            <div class="p-6">
                <form action="{{ route('settings.database.export') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Mật khẩu mã hóa <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="password" required minlength="8"
                               placeholder="Nhập mật khẩu (ít nhất 8 ký tự)"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:outline-none">
                        <p class="text-xs text-gray-500 mt-2">
                            Mật khẩu này dùng để mã hóa file SQL. Bạn sẽ cần nó khi muốn khôi phục dữ liệu.
                        </p>
                    </div>
                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                        <i class="fas fa-file-export mr-2"></i> Xuất file SQL đã mã hóa (.enc)
                    </button>
                </form>
            </div>
        </div>

        {{-- Import Section --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 bg-gray-50 border-b border-gray-200">
                <h4 class="font-semibold text-gray-800">
                    <i class="fas fa-upload mr-1 text-green-600"></i>
                    Khôi phục Dữ liệu (Restore)
                </h4>
            </div>
            <div class="p-6">
                <form action="{{ route('settings.database.import') }}" method="POST" enctype="multipart/form-data" 
                      onsubmit="return confirm('CẢNH BÁO: Việc khôi phục sẽ ghi đè lên dữ liệu hiện tại. Bạn có chắc chắn muốn tiếp tục?')">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Chọn file sao lưu (.enc) <span class="text-red-500">*</span>
                        </label>
                        <input type="file" name="backup_file" required accept=".enc"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-dark">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Mật khẩu giải mã <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="password" required
                               placeholder="Nhập mật khẩu đã dùng khi xuất file"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="confirm_restore" required class="rounded border-gray-300 text-primary focus:ring-primary mr-2">
                            <span class="text-sm text-gray-700 font-medium">Tôi hiểu rằng dữ liệu hiện tại sẽ bị thay đổi và không thể hoàn tác.</span>
                        </label>
                    </div>
                    <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-undo mr-2"></i> Khôi phục ngay
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h4 class="font-bold text-blue-900 mb-3">
            <i class="fas fa-info-circle mr-2"></i> Lưu ý quan trọng:
        </h4>
        <ul class="list-disc list-inside text-blue-800 space-y-2 text-sm">
            <li>Dữ liệu được mã hóa bằng thuật toán <strong>AES-256-CBC</strong> cực kỳ an toàn.</li>
            <li>Hệ thống lưu lại mật khẩu đã dùng trong <strong>Lịch sử sao lưu</strong> bên dưới (đã được mã hóa bảo vệ).</li>
            <li>Bạn có thể xem lại mật khẩu nếu cần khôi phục dữ liệu bằng cách xác nhận mật khẩu đăng nhập của mình.</li>
        </ul>
    </div>

    {{-- History Section --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-200">
            <h4 class="font-semibold text-gray-800">
                <i class="fas fa-history mr-1 text-purple-600"></i>
                Lịch sử Sao lưu
            </h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3">Ngày tạo</th>
                        <th class="px-6 py-3">Tên file</th>
                        <th class="px-6 py-3">Dung lượng</th>
                        <th class="px-6 py-3">Người thực hiện</th>
                        <th class="px-6 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($backups as $backup)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">{{ $backup->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 font-mono text-xs text-gray-600">{{ $backup->filename }}</td>
                        <td class="px-6 py-4">{{ $backup->size ?? 'N/A' }}</td>
                        <td class="px-6 py-4">{{ $backup->user->name ?? 'Hệ thống' }}</td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button onclick="requestPassword({{ $backup->id }}, '{{ $backup->filename }}')" 
                                    class="text-primary hover:text-primary-dark font-medium underline">
                                Xem mật khẩu
                            </button>
                            <form action="{{ route('settings.database.destroy', $backup->id) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-700" onclick="return confirm('Xóa bản ghi này khỏi lịch sử?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400">
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
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h5 class="text-lg font-bold text-gray-900">Xem mật khẩu sao lưu</h5>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-600 mb-4">
                Để bảo mật, vui lòng xác nhận **mật khẩu đăng nhập** hiện tại của bạn để xem mật khẩu cho file:<br>
                <code id="targetFilename" class="text-primary mt-1 block"></code>
            </p>
            <div id="passwordDisplay" class="hidden mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-center">
                <p class="text-xs text-gray-500 mb-1 uppercase font-bold tracking-wider">Mật khẩu của file:</p>
                <div class="flex items-center justify-center gap-2">
                    <span id="revealedPassword" class="text-xl font-bold text-gray-900"></span>
                    <button onclick="copyPassword()" class="text-gray-400 hover:text-primary">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
            <div id="authFormSection">
                <input type="password" id="current_login_password" 
                       placeholder="Nhập mật khẩu đăng nhập của bạn"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:ring-2 focus:ring-primary focus:outline-none">
                <button onclick="submitPasswordRequest()" id="submitBtn"
                        class="w-full bg-primary text-white py-2 rounded-lg hover:bg-primary-dark transition-colors flex justify-center items-center">
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

    // Close on escape
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });
</script>
    </div>
</div>
@endsection
