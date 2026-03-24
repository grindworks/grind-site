<?php

/**
 * auto_backup.php
 *
 * Trigger automatic backup asynchronously.
 * Intended to be called after login via Ajax to prevent login delay.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Check permissions (Admin only)
if (!current_user_can('manage_settings')) {
    json_response(['success' => true, 'skipped' => true, 'reason' => 'no_permission']);
}

// Configure execution limits for backup
if (function_exists('grinds_set_high_load_mode')) {
    grinds_set_high_load_mode();
}

// Enforce POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

try {
    // Verify CSRF token
    check_csrf_token();

    // 1. Check Time Interval (Prevent frequent backups)
    $lastBackup = (int)get_option('last_auto_backup_time', 0);
    if ((time() - $lastBackup) < 86400) { // 24 hours
        json_response(['success' => true, 'skipped' => true, 'reason' => 'time_interval']);
    }

    // 2. Check Probability
    $frequency = (int)get_option('login_backup_frequency', 10);
    if ($frequency === 0) {
        json_response(['success' => true, 'skipped' => true, 'reason' => 'disabled']);
    }
    if ($frequency < 1) $frequency = 10;

    // If called via Ajax, we rely on probability to avoid backing up on every dashboard refresh
    if (mt_rand(1, $frequency) !== 1) {
        json_response(['success' => true, 'skipped' => true, 'reason' => 'probability']);
    }

    // 3. Check DB Size
    if (!defined('DB_FILE') || !file_exists(DB_FILE)) {
        throw new Exception('DB file not found.');
    }

    clearstatcache(true, DB_FILE);
    $dbSize = filesize(DB_FILE);
    $limitMb = (int)get_option('auto_backup_limit_mb', 50);
    $limitBytes = $limitMb * 1024 * 1024;

    if ($limitMb > 0 && $dbSize > $limitBytes) {
        $_SESSION['backup_skipped_warning'] = true;
        json_response(['success' => true, 'skipped' => true, 'reason' => 'size_limit']);
    }

    // Close session to prevent locking during backup
    session_write_close();

    // 4. Perform Backup
    $filename = 'auto_login_' . date('Ymd_His') . '.db';

    if (function_exists('grinds_create_backup')) {
        grinds_create_backup($filename);
        update_option('last_auto_backup_time', time());
    } else {
        throw new Exception('Backup function not found.');
    }

    // 5. Rotate Backups
    $retention = (int)get_option('backup_retention_limit', 10);
    if (function_exists('grinds_rotate_backups')) {
        grinds_rotate_backups('auto_login_', $retention);
    }

    json_response(['success' => true, 'backup' => $filename]);
} catch (Exception $e) {
    // Log error but don't fail loudly
    error_log("Auto backup failed: " . $e->getMessage());
    json_response(['success' => false, 'error' => $e->getMessage()]);
}
