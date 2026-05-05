<?php

namespace App\Http\Controllers;

use App\Models\DatabaseBackup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class DatabaseBackupController extends Controller
{
    /**
     * Display the backup/restore management page.
     */
    public function index()
    {
        $this->authorize('viewAny', \App\Models\Setting::class);
        $backups = DatabaseBackup::with('user')->orderBy('created_at', 'desc')->get();
        return view('settings.database', compact('backups'));
    }

    /**
     * Export the database as an encrypted SQL file.
     */
    public function export(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $this->authorize('update', \App\Models\Setting::class);

        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPassword = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port', '3306');

        // Path to mysqldump
        $mysqldumpPath = 'mysqldump'; 
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $xamppPath = 'C:\xampp\mysql\bin\mysqldump.exe';
            if (file_exists($xamppPath)) {
                $mysqldumpPath = '"' . $xamppPath . '"';
            }
        }

        $timestamp = date('Y-m-d-H-i-s');
        $filename = 'backup-' . $timestamp . '.sql';
        $tempPath = storage_path('app/' . $filename);

        try {
            // Build the command with escaped arguments
            $args = [
                '--user=' . escapeshellarg($dbUser),
                '--host=' . escapeshellarg($dbHost),
                '--port=' . escapeshellarg($dbPort),
            ];

            if ($dbPassword) {
                // Warning: putting password on CLI is not the best but safest for now if we capture output
                $args[] = '--password=' . escapeshellarg($dbPassword);
            }

            $command = sprintf(
                '%s %s %s > %s 2>&1',
                $mysqldumpPath,
                implode(' ', $args),
                escapeshellarg($dbName),
                escapeshellarg($tempPath)
            );

            // Execute the command
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                $errorMessage = implode("\n", $output);
                throw new \Exception('Mysqldump failed: ' . ($errorMessage ?: 'Mã lỗi ' . $returnVar));
            }

            if (!file_exists($tempPath) || filesize($tempPath) === 0) {
                throw new \Exception('File backup tạo ra bị trống hoặc không tồn tại.');
            }

            $sqlContent = file_get_contents($tempPath);
            $size = filesize($tempPath);
            unlink($tempPath); // Delete temp SQL file

            // Encrypt the content
            $encryptedData = $this->encrypt($sqlContent, $request->password);

            $encryptedFilename = 'backup-' . $timestamp . '.sql.enc';
            
            // Log to history
            DatabaseBackup::create([
                'filename' => $encryptedFilename,
                'backup_password' => Crypt::encryptString($request->password),
                'user_id' => Auth::id(),
                'size' => $this->formatBytes($size),
            ]);

            return response($encryptedData)
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="' . $encryptedFilename . '"');

        } catch (\Exception $e) {
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }
            Log::error('Database Export Error: ' . $e->getMessage());
            return back()->with('error', 'Lỗi khi xuất dữ liệu: ' . $e->getMessage());
        }
    }

    /**
     * Import an encrypted SQL file into the database.
     */
    public function import(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file',
            'password' => 'required|string',
            'confirm_restore' => 'required|accepted',
        ]);

        $this->authorize('update', \App\Models\Setting::class);

        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPassword = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port', '3306');

        $tempPath = null;
        $mysqlPath = 'mysql';

        try {
            $file = $request->file('backup_file');
            $encryptedData = file_get_contents($file->getRealPath());
            
            $decryptedContent = $this->decrypt($encryptedData, $request->password);

            if ($decryptedContent === false) {
                return back()->with('error', 'Mật khẩu không chính xác hoặc file đã bị hỏng. Không thể giải mã dữ liệu.');
            }

            // Path to mysql CLI
            $mysqlPath = 'mysql';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $xamppPath = 'C:\xampp\mysql\bin\mysql.exe';
                if (file_exists($xamppPath)) {
                    $mysqlPath = '"' . $xamppPath . '"';
                }
            }

            // Save decrypted content to a temporary file for mysql CLI
            $tempFilename = 'restore-' . time() . '.sql';
            $tempPath = storage_path('app/' . $tempFilename);
            file_put_contents($tempPath, $decryptedContent);

            // Build the command with escaped arguments
            $args = [
                '--user=' . escapeshellarg($dbUser),
                '--host=' . escapeshellarg($dbHost),
                '--port=' . escapeshellarg($dbPort),
            ];

            if ($dbPassword) {
                $args[] = '--password=' . escapeshellarg($dbPassword);
            }

            $command = sprintf(
                '%s %s %s < %s 2>&1',
                $mysqlPath,
                implode(' ', $args),
                escapeshellarg($dbName),
                escapeshellarg($tempPath)
            );

            // Execute the command
            exec($command, $output, $returnVar);
            
            if (file_exists($tempPath)) {
                unlink($tempPath); // Always delete temp SQL file
            }

            if ($returnVar !== 0) {
                $errorMessage = implode("\n", $output);
                throw new \Exception('Mysql restore failed: ' . ($errorMessage ?: 'Mã lỗi ' . $returnVar));
            }

            return back()->with('success', 'Khôi phục dữ liệu thành công.');

        } catch (\Exception $e) {
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }
            Log::error('Database Import Error: ' . $e->getMessage());
            
            // If it's a specific CLI error, try to make it readable
            $msg = $e->getMessage();
            if (str_contains($msg, 'Access denied')) {
                $msg = "Lỗi kết nối cơ sở dữ liệu: Quyền truy cập bị từ chối. Vui lòng kiểm tra lại DB_USERNAME và DB_PASSWORD trong file .env của server này.";
            } elseif (str_contains($msg, 'Unknown database')) {
                $msg = "Lỗi: Cơ sở dữ liệu '" . $dbName . "' không tồn tại trên server này. Vui lòng tạo DB trống trước khi khôi phục.";
            } elseif (str_contains($msg, 'not found') || str_contains($msg, 'not recognized')) {
                $msg = "Lỗi: Không tìm thấy công cụ 'mysql' CLI trên server. Vui lòng cài đặt MySQL Client hoặc XAMPP.";
            }

            return back()->with('error', 'Lỗi khi khôi phục dữ liệu: ' . $msg);
        }
    }

    /**
     * Show the password for a specific backup (requires user re-auth).
     */
    public function showPassword(Request $request, $id)
    {
        $request->validate([
            'current_password' => 'required|string',
        ]);

        $this->authorize('update', \App\Models\Setting::class);

        if (!Hash::check($request->current_password, Auth::user()->getAuthPassword())) {
            return response()->json(['message' => 'Mật khẩu đăng nhập không đúng.'], 403);
        }

        $backup = DatabaseBackup::findOrFail($id);

        try {
            $password = Crypt::decryptString($backup->backup_password);
            return response()->json(['password' => $password]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Không thể giải mã mật khẩu.'], 500);
        }
    }

    /**
     * Delete a backup record from history.
     */
    public function destroy($id)
    {
        $this->authorize('update', \App\Models\Setting::class);
        $backup = DatabaseBackup::findOrFail($id);
        $backup->delete();

        return back()->with('success', 'Đã xóa bản ghi sao lưu khỏi lịch sử.');
    }

    /**
     * Helper to encrypt data using AES-256-CBC.
     */
    private function encrypt($data, $password)
    {
        $method = 'AES-256-CBC';
        $key = hash('sha256', $password);
        $ivSize = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivSize);
        
        $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
        
        // Return IV + encrypted data
        return $iv . $encrypted;
    }

    /**
     * Helper to decrypt data using AES-256-CBC.
     */
    private function decrypt($data, $password)
    {
        $method = 'AES-256-CBC';
        $key = hash('sha256', $password);
        $ivSize = openssl_cipher_iv_length($method);
        
        $iv = substr($data, 0, $ivSize);
        $encrypted = substr($data, $ivSize);
        
        return openssl_decrypt($encrypted, $method, $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * Format bytes to human readable size.
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
