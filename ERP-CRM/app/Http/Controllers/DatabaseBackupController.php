<?php

namespace App\Http\Controllers;

use App\Models\DatabaseBackup;
use App\Services\DataArchivingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class DatabaseBackupController extends Controller
{
    /**
     * Display the backup/restore and data archiving management page.
     */
    public function index(DataArchivingService $archivingService)
    {
        $this->authorize('viewAny', \App\Models\Setting::class);
        $backups = DatabaseBackup::with('user')->orderBy('created_at', 'desc')->get();
        
        $archiveConn = $archivingService->checkArchiveConnection();
        $yearlyStats = $archivingService->getYearlyStats();

        return view('settings.database', compact('backups', 'archiveConn', 'yearlyStats'));
    }

    /**
     * Export the database and attachments as an encrypted backup file.
     */
    public function export(Request $request)
    {
        // Increase limits for large file handling
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', '1800');
        ini_set('max_input_time', '1800');
        set_time_limit(1800);
        
        $request->validate([
            'password' => 'required|string|min:8',
            'backup_scope' => 'nullable|in:full,db_only',
        ]);

        $this->authorize('update', \App\Models\Setting::class);

        $backupScope = $request->input('backup_scope', 'full');
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
        $sqlFilename = 'database-' . $timestamp . '.sql';
        $tempSqlPath = storage_path('app/' . $sqlFilename);
        $tempZipPath = storage_path('app/backup-' . $timestamp . '.zip');

        try {
            // 1. Export MySQL Database
            $args = [
                '--user=' . escapeshellarg($dbUser),
                '--host=' . escapeshellarg($dbHost),
                '--port=' . escapeshellarg($dbPort),
                '--default-character-set=utf8mb4',
                '--routines',
                '--triggers',
            ];

            if ($dbPassword) {
                $args[] = '--password=' . escapeshellarg($dbPassword);
            }

            $errPath = $tempSqlPath . '.err';
            $command = sprintf(
                '%s %s %s > %s 2> %s',
                $mysqldumpPath,
                implode(' ', $args),
                escapeshellarg($dbName),
                escapeshellarg($tempSqlPath),
                escapeshellarg($errPath)
            );

            // Execute mysqldump
            exec($command, $unusedOutput, $returnVar);

            if ($returnVar !== 0) {
                $errorMessage = file_exists($errPath) ? file_get_contents($errPath) : 'Mã lỗi ' . $returnVar;
                if (file_exists($errPath)) @unlink($errPath);
                throw new \Exception('Mysqldump failed: ' . $errorMessage);
            }

            if (file_exists($errPath)) @unlink($errPath);

            if (!file_exists($tempSqlPath) || filesize($tempSqlPath) === 0) {
                throw new \Exception('File backup SQL tạo ra bị trống hoặc không tồn tại.');
            }

            $rawPayload = '';
            $finalSize = 0;
            $encryptedFilename = '';

            if ($backupScope === 'full') {
                // 2. Package Database SQL + All Attachments into a single ZIP archive
                $zip = new ZipArchive();
                if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    throw new \Exception('Không thể tạo file nén ZIP cho bản sao lưu toàn bộ.');
                }

                // Add SQL dump
                $zip->addFile($tempSqlPath, 'database.sql');

                // Add public storage attachments (UNC, invoices, quotes, avatars, etc.)
                $publicStoragePath = storage_path('app/public');
                if (file_exists($publicStoragePath)) {
                    $this->addDirectoryToZip($publicStoragePath, $zip, 'storage_public');
                }

                // Add technical tickets attachments
                $ticketsStoragePath = storage_path('app/technical_tickets');
                if (file_exists($ticketsStoragePath)) {
                    $this->addDirectoryToZip($ticketsStoragePath, $zip, 'storage_tickets');
                }

                // Add any configured external drive attachment paths
                $externalPaths = config('backup.attachment_paths', []);
                foreach ($externalPaths as $idx => $path) {
                    if (!empty($path) && file_exists($path)) {
                        $folderName = 'external_' . basename(rtrim(str_replace('\\', '/', $path), '/'));
                        $this->addDirectoryToZip($path, $zip, $folderName);
                    }
                }

                // Add manifest metadata
                $manifest = [
                    'app' => 'Mini ERP-CRM',
                    'version' => '5.0',
                    'backup_type' => 'full',

                    'created_at' => now()->toDateTimeString(),
                    'db_name' => $dbName,
                    'has_database' => true,
                    'has_attachments' => true,
                ];
                $zip->addFromString('backup_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                $zip->close();

                $rawPayload = file_get_contents($tempZipPath);
                $finalSize = filesize($tempZipPath);
                $encryptedFilename = 'backup-' . $timestamp . '-full.enc';

                // Cleanup unencrypted temp files
                @unlink($tempSqlPath);
                @unlink($tempZipPath);
            } else {
                // DB only
                $rawPayload = file_get_contents($tempSqlPath);
                $finalSize = filesize($tempSqlPath);
                $encryptedFilename = 'backup-' . $timestamp . '-db.sql.enc';

                @unlink($tempSqlPath);
            }

            // 3. Encrypt the entire package using AES-256-CBC
            $encryptedData = $this->encrypt($rawPayload, $request->password);
            
            // Log to history
            DatabaseBackup::create([
                'filename' => $encryptedFilename,
                'backup_password' => Crypt::encryptString($request->password),
                'user_id' => Auth::id(),
                'size' => $this->formatBytes($finalSize),
            ]);

            return response($encryptedData)
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="' . $encryptedFilename . '"');

        } catch (\Exception $e) {
            if (isset($tempSqlPath) && file_exists($tempSqlPath)) {
                @unlink($tempSqlPath);
            }
            if (isset($tempZipPath) && file_exists($tempZipPath)) {
                @unlink($tempZipPath);
            }
            Log::error('Database Export Error: ' . $e->getMessage());
            return back()->with('error', 'Lỗi khi xuất dữ liệu: ' . $e->getMessage());
        }
    }

    /**
     * Import an encrypted backup file (Full archive or SQL dump) into the system.
     */
    public function import(Request $request)
    {
        // Increase limits for large file handling
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', '1800');
        ini_set('max_input_time', '1800');
        set_time_limit(1800);
        
        $request->validate([
            'backup_file' => 'required|file|max:1048576', // up to 1GB
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
        $tempZipPath = null;
        $tempExtractDir = null;

        try {
            $file = $request->file('backup_file');
            $encryptedData = file_get_contents($file->getRealPath());
            
            $decryptedContent = $this->decrypt($encryptedData, $request->password);

            if ($decryptedContent === false || strlen($decryptedContent) === 0) {
                return back()->with('error', 'Mật khẩu không chính xác hoặc file đã bị hỏng. Không thể giải mã dữ liệu.');
            }

            // Detect if decrypted content is a ZIP archive (starts with PK\x03\x04)
            $isZipArchive = (substr($decryptedContent, 0, 4) === "PK\x03\x04");
            $restoredItemsSummary = [];

            if ($isZipArchive) {
                // 1. Extract ZIP archive
                $tempZipPath = storage_path('app/restore-' . time() . '.zip');
                file_put_contents($tempZipPath, $decryptedContent);
                unset($decryptedContent); // Free memory

                $zip = new ZipArchive();
                if ($zip->open($tempZipPath) !== true) {
                    throw new \Exception('Không thể mở gói nén sao lưu sau khi giải mã.');
                }

                $tempExtractDir = storage_path('app/restore_extracted_' . time());
                if (!is_dir($tempExtractDir)) {
                    mkdir($tempExtractDir, 0777, true);
                }

                $zip->extractTo($tempExtractDir);
                $zip->close();
                @unlink($tempZipPath);

                // 2. Restore Database SQL
                $sqlFiles = glob($tempExtractDir . '/*.sql');
                if (!empty($sqlFiles)) {
                    $targetSqlFile = $sqlFiles[0];
                    $this->executeSqlRestore($targetSqlFile, $dbName, $dbUser, $dbPassword, $dbHost, $dbPort);
                    $restoredItemsSummary[] = 'Cơ sở dữ liệu (Database)';
                }

                // 3. Restore Public Attachments (storage_public)
                $extractedPublicPath = $tempExtractDir . '/storage_public';
                if (is_dir($extractedPublicPath)) {
                    $targetPublicStorage = storage_path('app/public');
                    if (!is_dir($targetPublicStorage)) {
                        mkdir($targetPublicStorage, 0777, true);
                    }
                    $this->copyDirectoryRecursively($extractedPublicPath, $targetPublicStorage);
                    $restoredItemsSummary[] = 'Toàn bộ tệp & ảnh đính kèm (UNC, Hóa đơn, Báo giá, Tài liệu...)';
                }

                // 4. Restore Technical Tickets Attachments (storage_tickets)
                $extractedTicketsPath = $tempExtractDir . '/storage_tickets';
                if (is_dir($extractedTicketsPath)) {
                    $targetTicketsStorage = storage_path('app/technical_tickets');
                    if (!is_dir($targetTicketsStorage)) {
                        mkdir($targetTicketsStorage, 0777, true);
                    }
                    $this->copyDirectoryRecursively($extractedTicketsPath, $targetTicketsStorage);
                    $restoredItemsSummary[] = 'Tệp đính kèm Ticket kỹ thuật';
                }

                // Ensure public storage symlink is intact
                try {
                    Artisan::call('storage:link');
                } catch (\Throwable $t) {
                    // Ignore if symlink already exists
                }

                // Clean up extraction directory
                $this->deleteDirectoryRecursively($tempExtractDir);

            } else {
                // Legacy SQL Dump Restore
                if (str_contains($decryptedContent, 'mysqldump: [Warning]')) {
                    $lines = explode("\n", $decryptedContent);
                    $filteredLines = array_filter($lines, function($line) {
                        return !str_contains($line, 'mysqldump: [Warning]');
                    });
                    $decryptedContent = implode("\n", $filteredLines);
                }

                $tempFilename = 'restore-' . time() . '.sql';
                $tempPath = storage_path('app/' . $tempFilename);
                file_put_contents($tempPath, $decryptedContent);
                unset($decryptedContent);

                $this->executeSqlRestore($tempPath, $dbName, $dbUser, $dbPassword, $dbHost, $dbPort);
                if (file_exists($tempPath)) {
                    @unlink($tempPath);
                }
                $restoredItemsSummary[] = 'Cơ sở dữ liệu (Database)';
            }

            $summaryText = implode(', ', $restoredItemsSummary);
            return back()->with('success', 'Khôi phục dữ liệu thành công: ' . $summaryText . '.');

        } catch (\Exception $e) {
            if (isset($tempPath) && file_exists($tempPath)) {
                @unlink($tempPath);
            }
            if (isset($tempZipPath) && file_exists($tempZipPath)) {
                @unlink($tempZipPath);
            }
            if (isset($tempExtractDir) && is_dir($tempExtractDir)) {
                $this->deleteDirectoryRecursively($tempExtractDir);
            }

            Log::error('Database Import Error: ' . $e->getMessage());
            
            $msg = $e->getMessage();
            if (str_contains($msg, 'Access denied')) {
                $msg = "Lỗi kết nối cơ sở dữ liệu: Quyền truy cập bị từ chối. Vui lòng kiểm tra lại DB_USERNAME và DB_PASSWORD trong file .env.";
            } elseif (str_contains($msg, 'Unknown database')) {
                $msg = "Lỗi: Cơ sở dữ liệu '" . $dbName . "' không tồn tại. Vui lòng tạo DB trống trước khi khôi phục.";
            } elseif (str_contains($msg, 'not found') || str_contains($msg, 'not recognized')) {
                $msg = "Lỗi: Không tìm thấy công cụ 'mysql' CLI trên server. Vui lòng cài đặt MySQL Client hoặc XAMPP.";
            }

            return back()->with('error', 'Lỗi khi khôi phục dữ liệu: ' . $msg);
        }
    }

    /**
     * Helper to execute SQL restore using mysql CLI.
     */
    private function executeSqlRestore(string $sqlFilePath, string $dbName, string $dbUser, ?string $dbPassword, string $dbHost, string $dbPort): void
    {
        $mysqlPath = 'mysql';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $xamppPath = 'C:\xampp\mysql\bin\mysql.exe';
            if (file_exists($xamppPath)) {
                $mysqlPath = '"' . $xamppPath . '"';
            }
        }

        $args = [
            '--user=' . escapeshellarg($dbUser),
            '--host=' . escapeshellarg($dbHost),
            '--port=' . escapeshellarg($dbPort),
            '--default-character-set=utf8mb4',
        ];

        if ($dbPassword) {
            $args[] = '--password=' . escapeshellarg($dbPassword);
        }

        $command = sprintf(
            '%s %s %s < %s 2>&1',
            $mysqlPath,
            implode(' ', $args),
            escapeshellarg($dbName),
            escapeshellarg($sqlFilePath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            $errorMessage = implode("\n", $output);
            throw new \Exception('Mysql restore failed: ' . ($errorMessage ?: 'Mã lỗi ' . $returnVar));
        }
    }

    /**
     * Recursively add a folder to ZipArchive.
     */
    private function addDirectoryToZip(string $sourcePath, ZipArchive $zip, string $zipPrefix = ''): void
    {
        if (!file_exists($sourcePath)) {
            return;
        }

        $realSource = realpath($sourcePath) ?: $sourcePath;
        $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS;

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourcePath, $flags),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            if (!$filePath || !file_exists($filePath)) {
                continue;
            }

            $relativePath = substr($filePath, strlen($realSource) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);
            $zipEntryPath = $zipPrefix ? ($zipPrefix . '/' . $relativePath) : $relativePath;

            if ($file->isDir()) {
                $zip->addEmptyDir($zipEntryPath);
            } elseif ($file->isFile()) {
                $zip->addFile($filePath, $zipEntryPath);
            }
        }
    }


    /**
     * Recursively copy directory contents.
     */
    private function copyDirectoryRecursively(string $src, string $dst): void
    {
        if (!is_dir($src)) {
            return;
        }
        if (!is_dir($dst)) {
            mkdir($dst, 0777, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr($item->getRealPath(), strlen(realpath($src)) + 1);
            $targetPath = $dst . DIRECTORY_SEPARATOR . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0777, true);
                }
            } else {
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0777, true);
                }

                // If target file already exists and is not writable, attempt to fix permissions or ignore if it's .gitignore
                if (file_exists($targetPath)) {
                    if (basename($targetPath) === '.gitignore') {
                        continue; // Keep existing .gitignore safely
                    }
                    @chmod($targetPath, 0666);
                }

                try {
                    @copy($item->getRealPath(), $targetPath);
                } catch (\Throwable $e) {
                    Log::warning("Không thể ghi đè file khi khôi phục ({$targetPath}): " . $e->getMessage());
                }
            }

        }
    }

    /**
     * Recursively delete directory.
     */
    private function deleteDirectoryRecursively(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            @$todo($fileinfo->getRealPath());
        }

        @rmdir($dir);
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

        if (empty($backup->backup_password)) {
            return response()->json(['password' => 'Bản sao lưu này không đặt mật khẩu (Gói nén ZIP tiêu chuẩn).']);
        }

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
     * Archive data of a specific year to Archive DB.
     */
     public function archiveYear(Request $request, DataArchivingService $archivingService)
     {
         $this->authorize('update', \App\Models\Setting::class);

         $request->validate([
             'year' => 'required|integer|min:2000|max:' . date('Y'),
             'purge_from_live' => 'nullable|boolean',
         ]);

         $year = (int) $request->input('year');
         $purgeFromLive = $request->boolean('purge_from_live', true);

         try {
             $result = $archivingService->archiveYear($year, $purgeFromLive);
             return redirect()->route('settings.database.index')
                 ->with('success', $result['message']);
         } catch (\Exception $e) {
             Log::error("Data Archiving Error for year {$year}: " . $e->getMessage());
             return redirect()->route('settings.database.index')
                 ->with('error', 'Lỗi khi đóng gói dữ liệu năm ' . $year . ': ' . $e->getMessage());
         }
     }

     /**
      * Restore/Unarchive data of a specific year from Archive DB back to Live DB.
      */
     public function restoreYear(Request $request, DataArchivingService $archivingService)
     {
         $this->authorize('update', \App\Models\Setting::class);

         $request->validate([
             'year' => 'required|integer|min:2000|max:' . date('Y'),
         ]);

         $year = (int) $request->input('year');

         try {
             $result = $archivingService->restoreYear($year);
             return redirect()->route('settings.database.index')
                 ->with('success', $result['message']);
         } catch (\Exception $e) {
             Log::error("Data Restore Error for year {$year}: " . $e->getMessage());
             return redirect()->route('settings.database.index')
                 ->with('error', 'Lỗi khi phục hồi dữ liệu năm ' . $year . ': ' . $e->getMessage());
         }
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

