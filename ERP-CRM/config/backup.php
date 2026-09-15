<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mysqldump Executable Path
    |--------------------------------------------------------------------------
    |
    | Path to the mysqldump binary on Linux or Windows.
    | If left null or 'auto', the system will auto-detect based on OS.
    |
    */
    'mysqldump_path' => env('MYSQLDUMP_PATH', null),

    /*
    |--------------------------------------------------------------------------
    | Backup Storage / Attachment Directories
    |--------------------------------------------------------------------------
    |
    | List of directories containing uploaded files/attachments to backup.
    | Supports paths outside the project source code (e.g., mounted external disks,
    | /mnt/storage, D:/ERP_STORAGE, etc.) and follows symlinks.
    |
    */
    'attachment_paths' => array_filter(array_map('trim', explode(',', env('BACKUP_ATTACHMENT_PATHS', '')))),

    /*
    |--------------------------------------------------------------------------
    | Default Included Internal Storage Paths
    |--------------------------------------------------------------------------
    |
    | Standard Laravel storage paths that contain attachments.
    |
    */
    'include_public_storage' => env('BACKUP_INCLUDE_PUBLIC_STORAGE', true),
    'include_tickets_storage' => env('BACKUP_INCLUDE_TICKETS_STORAGE', true),

    /*
    |--------------------------------------------------------------------------
    | Backup Destination Directory
    |--------------------------------------------------------------------------
    |
    | Directory where completed backup files (.zip / .enc) will be stored.
    | Can be set to a folder on another drive or dedicated backup volume.
    |
    */
    'destination_path' => env('BACKUP_DESTINATION_PATH', storage_path('app/backups')),

    /*
    |--------------------------------------------------------------------------
    | Retention Policy (Days)
    |--------------------------------------------------------------------------
    |
    | Automatically delete backup archives older than this number of days
    | to save disk space. Set to 0 to disable automatic cleanup.
    |
    */
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Default Encryption Password (Optional)
    |--------------------------------------------------------------------------
    |
    | If provided, automated backups will be encrypted with AES-256-CBC.
    | If null/empty, backups are packaged as standard password-free ZIP archives.
    |
    */
    'encryption_password' => env('BACKUP_ENCRYPTION_PASSWORD', null),
];
