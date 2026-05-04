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
            // Build the command
            $command = sprintf(
                '%s --user=%s --password=%s --host=%s %s > %s',
                $mysqldumpPath,
                $dbUser,
                $dbPassword ? '"' . $dbPassword . '"' : '""',
                $dbHost,
                $dbName,
                '"' . $tempPath . '"'
            );

            // Execute the command
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                throw new \Exception('Mysqldump failed with error code: ' . $returnVar);
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

        try {
            $encryptedData = file_get_contents($request->file('backup_file')->getRealPath());
            
            $decryptedContent = $this->decrypt($encryptedData, $request->password);

            if ($decryptedContent === false) {
                return back()->with('error', 'Mật khẩu không chính xác hoặc file đã bị hỏng.');
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

            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPassword = config('database.connections.mysql.password');
            $dbHost = config('database.connections.mysql.host');

            // Build the command
            $command = sprintf(
                '%s --user=%s --password=%s --host=%s %s < %s',
                $mysqlPath,
                $dbUser,
                $dbPassword ? '"' . $dbPassword . '"' : '""',
                $dbHost,
                $dbName,
                '"' . $tempPath . '"'
            );

            // Execute the command
            exec($command, $output, $returnVar);
            
            unlink($tempPath); // Always delete temp SQL file

            if ($returnVar !== 0) {
                throw new \Exception('Restore failed with error code: ' . $returnVar);
            }

            return back()->with('success', 'Khôi phục dữ liệu thành công.');

        } catch (\Exception $e) {
            Log::error('Database Import Error: ' . $e->getMessage());
            return back()->with('error', 'Lỗi khi khôi phục dữ liệu: ' . $e->getMessage());
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
