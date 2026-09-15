<?php

namespace App\Services;

use App\Models\DatabaseBackup;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

class SystemBackupService
{
    /**
     * Locate mysqldump binary executable.
     */
    public function getMysqldumpPath(): string
    {
        $customPath = config('backup.mysqldump_path');
        if (!empty($customPath) && $customPath !== 'auto') {
            return '"' . $customPath . '"';
        }

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $commonWindowsPaths = [
                'C:\xampp\mysql\bin\mysqldump.exe',
                'D:\xampp\mysql\bin\mysqldump.exe',
                'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe',
                'C:\laragon\bin\mysql\current\bin\mysqldump.exe',
            ];

            foreach ($commonWindowsPaths as $path) {
                if (file_exists($path)) {
                    return '"' . $path . '"';
                }
            }
            return 'mysqldump';
        }

        // Linux / Unix paths
        $commonLinuxPaths = [
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            '/opt/lampp/bin/mysqldump',
        ];

        foreach ($commonLinuxPaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return escapeshellarg($path);
            }
        }

        return 'mysqldump';
    }

    /**
     * Gather all attachment directories (standard + symlinks + external drives).
     */
    public function getAttachmentDirectories(): array
    {
        $paths = [];

        // 1. Standard public storage (UNC, avatars, quotes, invoices, order attachments)
        if (config('backup.include_public_storage', true)) {
            $publicPath = storage_path('app/public');
            if (file_exists($publicPath)) {
                $paths['storage_public'] = $publicPath;
            }
        }

        // 2. Technical tickets storage
        if (config('backup.include_tickets_storage', true)) {
            $ticketsPath = storage_path('app/technical_tickets');
            if (file_exists($ticketsPath)) {
                $paths['storage_tickets'] = $ticketsPath;
            }
        }

        // 3. Custom external directories (pointed to external drives / mounts)
        $externalPaths = config('backup.attachment_paths', []);
        foreach ($externalPaths as $idx => $path) {
            if (!empty($path) && file_exists($path)) {
                $folderName = 'external_' . basename(rtrim(str_replace('\\', '/', $path), '/'));
                if (isset($paths[$folderName])) {
                    $folderName .= '_' . ($idx + 1);
                }
                $paths[$folderName] = $path;
            }
        }

        return $paths;
    }

    /**
     * Dump the MySQL Database to a .sql file.
     */
    public function dumpDatabase(string $targetSqlPath): void
    {
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPassword = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', '3306');

        $mysqldumpPath = $this->getMysqldumpPath();

        $args = [
            '--user=' . escapeshellarg($dbUser),
            '--host=' . escapeshellarg($dbHost),
            '--port=' . escapeshellarg($dbPort),
            '--default-character-set=utf8mb4',
            '--routines',
            '--triggers',
            '--single-transaction',
            '--quick',
        ];

        if (!empty($dbPassword)) {
            $args[] = '--password=' . escapeshellarg($dbPassword);
        }

        $errPath = $targetSqlPath . '.err';
        $command = sprintf(
            '%s %s %s > %s 2> %s',
            $mysqldumpPath,
            implode(' ', $args),
            escapeshellarg($dbName),
            escapeshellarg($targetSqlPath),
            escapeshellarg($errPath)
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0 || !file_exists($targetSqlPath) || filesize($targetSqlPath) === 0) {
            $errorMessage = file_exists($errPath) ? file_get_contents($errPath) : 'Mã lỗi: ' . $returnVar;
            if (file_exists($errPath)) @unlink($errPath);
            throw new \Exception('Không thể xuất dữ liệu CSDL (mysqldump): ' . $errorMessage);
        }

        if (file_exists($errPath)) {
            @unlink($errPath);
        }
    }

    /**
     * Perform a full backup: SQL dump + all attachments across disks + packaging.
     * 
     * @param string $scope 'full' or 'db_only'
     * @param string|null $destinationDir Custom destination path (defaults to config)
     * @param string|null $encryptionPassword If set, encrypts with AES-256-CBC
     * @param int|null $userId User ID initiating the backup (null for automated cronjob)
     * @return array Backup results
     */
    public function runBackup(string $scope = 'full', ?string $destinationDir = null, ?string $encryptionPassword = null, ?int $userId = null): array
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', '3600');
        set_time_limit(3600);

        $destinationDir = $destinationDir ?: config('backup.destination_path', storage_path('app/backups'));
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0777, true);
        }

        $timestamp = date('Y-m-d-His');
        $tempDir = storage_path('app/backup_temp_' . $timestamp);
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $tempSqlPath = $tempDir . '/database.sql';
        $tempZipPath = $tempDir . '/backup.zip';
        $finalFilename = '';
        $finalPath = '';
        $finalSize = 0;
        $attachmentStats = [];

        try {
            // 1. Dump MySQL Database
            $this->dumpDatabase($tempSqlPath);
            $dbSize = filesize($tempSqlPath);

            if ($scope === 'full') {
                // 2. Package Database + Attachments to ZIP
                $zip = new ZipArchive();
                if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                    throw new \Exception('Không thể khởi tạo file nén ZIP.');
                }

                // Add database dump
                $zip->addFile($tempSqlPath, 'database.sql');

                // Add attachments across all disks/paths (supporting external paths and following symlinks)
                $attachmentFolders = $this->getAttachmentDirectories();
                foreach ($attachmentFolders as $zipPrefix => $dirPath) {
                    $addedCount = $this->addDirectoryToZip($dirPath, $zip, $zipPrefix);
                    $attachmentStats[$zipPrefix] = [
                        'source_path' => $dirPath,
                        'files_count' => $addedCount,
                    ];
                }

                // Add manifest metadata
                $manifest = [
                    'app' => 'Mini ERP-CRM',
                    'version' => '5.0',
                    'backup_type' => 'full',
                    'created_at' => now()->toDateTimeString(),
                    'db_name' => config('database.connections.mysql.database'),
                    'scope' => 'full',
                    'attachment_sources' => $attachmentStats,
                ];
                $zip->addFromString('backup_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $zip->close();

                $rawPackagePath = $tempZipPath;
                $suffix = '-full';
            } else {
                $rawPackagePath = $tempSqlPath;
                $suffix = '-db';
            }

            // 3. Encrypt or Save directly
            $passwordToUse = $encryptionPassword ?? config('backup.encryption_password');

            if (!empty($passwordToUse)) {
                $finalFilename = 'backup-' . $timestamp . $suffix . '.enc';
                $finalPath = $destinationDir . DIRECTORY_SEPARATOR . $finalFilename;

                $rawPayload = file_get_contents($rawPackagePath);
                $encryptedPayload = $this->encrypt($rawPayload, $passwordToUse);
                file_put_contents($finalPath, $encryptedPayload);
                unset($rawPayload);
                unset($encryptedPayload);
            } else {
                $extension = ($scope === 'full') ? '.zip' : '.sql';
                $finalFilename = 'backup-' . $timestamp . $suffix . $extension;
                $finalPath = $destinationDir . DIRECTORY_SEPARATOR . $finalFilename;
                copy($rawPackagePath, $finalPath);
            }

            $finalSize = file_exists($finalPath) ? filesize($finalPath) : 0;

            // 4. Record to Database History Table
            DatabaseBackup::create([
                'filename' => $finalFilename,
                'backup_password' => !empty($passwordToUse) ? Crypt::encryptString($passwordToUse) : '',
                'user_id' => $userId,
                'size' => $this->formatBytes($finalSize),
            ]);


            // 5. Cleanup temporary folder
            $this->deleteDirectory($tempDir);

            // 6. Purge old backups according to retention policy
            $retentionDays = (int) config('backup.retention_days', 30);
            $deletedOldFiles = 0;
            if ($retentionDays > 0) {
                $deletedOldFiles = $this->purgeOldBackups($destinationDir, $retentionDays);
            }

            return [
                'success' => true,
                'filename' => $finalFilename,
                'path' => $finalPath,
                'size' => $this->formatBytes($finalSize),
                'size_bytes' => $finalSize,
                'db_size' => $this->formatBytes($dbSize),
                'scope' => $scope,
                'encrypted' => !empty($passwordToUse),
                'attachments_included' => $attachmentStats,
                'purged_old_backups' => $deletedOldFiles,
                'created_at' => now()->toDateTimeString(),
            ];

        } catch (\Exception $e) {
            $this->deleteDirectory($tempDir);
            Log::error('System Backup Service Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Recursively add a folder to ZipArchive, following symlinks and skipping unreadable files.
     */
    private function addDirectoryToZip(string $sourcePath, ZipArchive $zip, string $zipPrefix = ''): int
    {
        if (!is_dir($sourcePath)) {
            return 0;
        }

        $realSource = realpath($sourcePath) ?: $sourcePath;
        $fileCount = 0;

        $flags = FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS;
        $directoryIterator = new RecursiveDirectoryIterator($sourcePath, $flags);
        $iterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $file) {
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
                $fileCount++;
            }
        }

        return $fileCount;
    }

    /**
     * Purge backup files older than X days.
     */
    public function purgeOldBackups(string $directory, int $days): int
    {
        if (!is_dir($directory) || $days <= 0) {
            return 0;
        }

        $deleted = 0;
        $cutoffTime = time() - ($days * 86400);

        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $directory . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath)) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['enc', 'zip', 'sql', 'gz']) && filemtime($filePath) < $cutoffTime) {
                    @unlink($filePath);
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Encrypt data using AES-256-CBC.
     */
    private function encrypt(string $data, string $password): string
    {
        $method = 'AES-256-CBC';
        $key = hash('sha256', $password);
        $ivSize = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivSize);

        $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
        return $iv . $encrypted;
    }

    /**
     * Delete directory recursively.
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    /**
     * Format bytes to human readable size.
     */
    public function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
