<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetUserPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:reset-passwords {--password=password : Mật khẩu mới muốn thiết lập} {--force : Bỏ qua các cảnh báo xác nhận}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Đặt lại mật khẩu cho tất cả tài khoản người dùng về một mật khẩu chung (mặc định: password)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $password = $this->option('password');
        
        $this->newLine();
        $this->info('================================================================');
        $this->warn("⚠ CẢNH BÁO: LỆNH NÀY SẼ THAY ĐỔI MẬT KHẨU CỦA TOÀN BỘ TÀI KHOẢN NGƯỜI DÙNG!");
        $this->line("  Mật khẩu mới sẽ là: '{$password}'");
        $this->info('================================================================');
        $this->newLine();

        // Safety check for production environment
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('❌ Lệnh này đang chạy trên môi trường PRODUCTION! Vui lòng sử dụng thêm option --force nếu bạn chắc chắn.');
            return self::FAILURE;
        }

        if (!$this->option('force')) {
            if (!$this->confirm("Bạn có chắc chắn muốn đặt lại mật khẩu của tất cả người dùng thành '{$password}' không?", false)) {
                $this->warn('❌ Đã hủy thao tác.');
                return self::SUCCESS;
            }
        }

        $this->info('🔄 Đang tiến hành đặt lại mật khẩu cho tất cả tài khoản...');

        try {
            $hashedPassword = Hash::make($password);
            $updatedCount = DB::table('users')->update([
                'password' => $hashedPassword
            ]);

            $this->info("✅ Thành công! Đã cập nhật mật khẩu cho {$updatedCount} tài khoản thành '{$password}'.");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Đã xảy ra lỗi: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
