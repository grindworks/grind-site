<?php

/**
 * _audit_logger.php
 *
 * [English]
 * Audit Trail Logger (Institutional Grade).
 * - Records all critical administrative actions (Logins, Post changes, Deletions, Settings updates).
 * - Saves logs to data/logs/audit.log in an immutable-style format.
 *
 * [Japanese]
 * 監査ログ記録プラグイン（金融・公的機関レベル）。
 * - ログイン、記事の作成・更新・削除、設定変更などのすべての重要な管理者操作を記録します。
 * - ログはデータディレクトリ（data/logs/audit.log）に追記型で安全に保存されます。
 */
if (!defined('GRINDS_APP')) exit;

/**
 * Helper function to write audit logs to a file.
 * 監査ログをファイルに書き込むヘルパー関数
 */
if (!function_exists('grinds_audit_log')) {
    function grinds_audit_log($action, $details = '')
    {
        $logDir = ROOT_PATH . '/data/logs';
        if (function_exists('grinds_secure_dir')) {
            grinds_secure_dir($logDir);
        }
        $logFile = $logDir . '/audit.log';

        $timestamp = gmdate('Y-m-d\TH:i:s\Z'); // ISO 8601 形式 (UTC)
        $ip = function_exists('get_client_ip') ? get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? 'Unknown');
        $user = App::user()['username'] ?? 'System/Guest';
        $userId = App::user()['id'] ?? '0';

        // Log Forging prevention: Remove malicious newline characters.
        // Log Forging (ログインジェクション) 対策: 悪意のある改行文字を除去
        $action = str_replace(["\r", "\n"], ' ', (string)$action);
        $details = str_replace(["\r", "\n"], ' ', (string)$details);

        // Log format: [TIMESTAMP] [IP] [USER(ID)] [ACTION] - DETAILS
        // ログフォーマット: [TIMESTAMP] [IP] [USER(ID)] [ACTION] - DETAILS
        $logEntry = "[{$timestamp}] [IP: {$ip}] [User: {$user}(ID:{$userId})] [{$action}]";
        if (!empty($details)) {
            $logEntry .= " - {$details}";
        }
        $logEntry .= PHP_EOL;

        // Perform write in the background after returning the response to prevent delay from disk I/O.
        // ディスクI/Oによるレスポンス遅延を防ぐため、画面を返した後のバックグラウンドで書き込みを行う
        register_shutdown_function(function () use ($logFile, $logEntry) {
            @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        });
    }
}

// 1. Log when a post is saved (created/updated).
// 1. 記事が保存（作成・更新）された時のログ
add_action('grinds_post_saved', function ($postId, $data) {
    $title = $data['title'] ?? 'Unknown Title';
    $status = $data['status'] ?? 'draft';
    grinds_audit_log('POST_SAVED', "Post ID: {$postId}, Title: '{$title}', Status: {$status}");
});

// 2. Log when a post is moved to the trash.
// 2. 記事がゴミ箱に移動された時のログ
add_action('grinds_post_trashed', function ($postId) {
    grinds_audit_log('POST_TRASHED', "Post ID: {$postId}");
});

// 3. Log when a post is restored from the trash.
// 3. 記事がゴミ箱から復元された時のログ
add_action('grinds_post_restored', function ($postId) {
    grinds_audit_log('POST_RESTORED', "Post ID: {$postId}");
});

// 4. Log when a post is permanently deleted.
// 4. 記事が完全に削除された時のログ
add_action('grinds_post_deleted', function ($postId) {
    grinds_audit_log('POST_PERMANENTLY_DELETED', "Post ID: {$postId}");
});

// 5. Log when the trash is emptied.
// 5. ゴミ箱が空にされた時のログ
add_action('grinds_trash_emptied', function ($count) {
    grinds_audit_log('TRASH_EMPTIED', "Deleted {$count} items from trash");
});

// 6. Log when settings are updated (monitor POST requests).
// 6. 設定が変更された時のログ (POSTリクエストを監視)
add_action('grinds_init', function () {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (str_contains($requestUri, 'settings.php') && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? 'unknown_setting_action';
        // Exclude sensitive information like passwords and tokens from the log.
        // パスワードやトークンなどの機密情報を除外して記録
        $safePostData = $_POST;
        unset($safePostData['csrf_token'], $safePostData['password'], $safePostData['new_password'], $safePostData['current_password'], $safePostData['smtp_pass'], $safePostData['backup_zip_password'], $safePostData['preview_shared_password']);

        $details = "Action: {$action}, Modified Keys: " . implode(', ', array_keys($safePostData));
        grinds_audit_log('SETTINGS_UPDATED', $details);
    }
});

// 7. Log on successful login.
// 7. ログイン成功時のログ
add_action('grinds_post_login', function ($userId) {
    grinds_audit_log('USER_LOGIN', "Successful login for User ID: {$userId}");
});

// 8. Log on manual logout.
// 8. ログアウト時のログ
add_action('grinds_init', function () {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if ((str_contains($requestUri, 'logout.php') || str_contains($scriptName, 'logout.php')) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        grinds_audit_log('USER_LOGOUT', "User logged out manually");
    }
});
