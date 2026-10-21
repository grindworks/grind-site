<?php

/**
 * Plugin Name: Strict Session
 *
 * Author: GRIND WORKS, Y.K.
 * Version: 1.0.0
 *
 * [English]
 * Strict Session Management (Institutional Grade).
 * - Enforces IP and User-Agent binding to the session to prevent hijacking.
 * - Disallows concurrent logins for the same user account.
 *
 * Note: IP binding may cause frequent forced logouts on mobile networks where IP addresses change frequently.
 *
 * [Japanese]
 * 厳格なセッション管理プラグイン（金融・公的機関レベル）。
 * - ログイン中のIPアドレスやブラウザ(User-Agent)が変更された場合、ハイジャックとみなして強制ログアウトします。
 * - 同一ユーザーによる複数端末からの同時ログインを禁止します（後からログインした端末を優先し、古いセッションを破棄）。
 *
 * 注意: モバイル回線などIPアドレスが頻繁に変わる環境では、意図しないログアウトが頻発する可能性があります。
 */
if (!defined('GRINDS_APP')) exit;

// 1. Bind IP/UA immediately after session start and prevent concurrent logins
// 1. セッション開始直後にIPとUAをバインド、および同時ログインの排除
add_action('grinds_init', function () {
    // Target only access to the admin area
    // 管理画面へのアクセスのみを対象
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $isAdminArea = str_contains($requestPath, '/admin/') || str_contains($scriptName, '/admin/');

    if (!$isAdminArea) return;

    // Skip during logout process
    // ログアウト処理中はスキップ
    if (grinds_is_admin_logout_request()) return;

    // Check only if already logged in
    // ログイン済みの場合のみチェック
    if (!empty($_SESSION['admin_logged_in']) && !empty($_SESSION['user_id'])) {
        $userId = (int)$_SESSION['user_id'];
        $currentIp = function_exists('get_client_ip') ? get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '');
        $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // --- A: IP/UA Binding Check (Prevent Session Hijacking) ---
        // --- A: IP/UA Binding Check (セッションハイジャック防止) ---
        if (!isset($_SESSION['bound_ip'])) {
            // Bind on first access
            // 初回アクセス時にバインド
            $_SESSION['bound_ip'] = $currentIp;
            $_SESSION['bound_ua'] = $currentUa;
        } else {
            // Force logout if bound information does not match
            // バインドされた情報と異なる場合は強制ログアウト
            if ($_SESSION['bound_ip'] !== $currentIp || $_SESSION['bound_ua'] !== $currentUa) {
                if (class_exists('GrindsLogger')) {
                    GrindsLogger::log("Security Alert: Session hijacked or network changed. User ID: {$userId}, Expected IP: {$_SESSION['bound_ip']}, Actual IP: {$currentIp}", 'CRITICAL');
                }
                grinds_logout();
                die("Security Error: Your network or browser changed during the session. For your protection, you have been logged out.");
            }
        }

        // --- B: Concurrent Login Prevention (Eliminate simultaneous logins) ---
        // --- B: Concurrent Login Prevention (同時ログインの排除) ---
        // Compare a per-login token kept in the session, not the session ID:
        // the core changes the session ID every 15 minutes.
        // セッションIDではなく、ログインごとのトークンで比較します（セッションIDは15分ごとに変わるため）
        $pdo = App::db();
        if ($pdo) {
            if (empty($_SESSION['grinds_strict_token'])) {
                $_SESSION['grinds_strict_token'] = bin2hex(random_bytes(16));
            }
            $sessionToken = $_SESSION['grinds_strict_token'];
            $sessionKey = 'active_session_user_' . $userId;
            $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = ?");
            $stmt->execute([$sessionKey]);
            $recordedSession = $stmt->fetchColumn();

            if (!$recordedSession || !str_starts_with((string)$recordedSession, 'token:')) {
                // 初回ログイン時、または旧形式（セッションID）の記録
                $pdo->prepare("INSERT OR REPLACE INTO settings (key, value, autoload) VALUES (?, ?, 0)")
                    ->execute([$sessionKey, 'token:' . $sessionToken]);
            } elseif (!hash_equals((string)$recordedSession, 'token:' . $sessionToken)) {
                // 別のセッションIDが記録されている場合（別の端末でログインされた）
                if (class_exists('GrindsLogger')) {
                    GrindsLogger::log("Security Alert: Concurrent login detected. Terminating older session for User ID: {$userId}", 'WARNING');
                }
                grinds_logout();
                die("Security Notice: Your account was accessed from another device. You have been logged out.");
            }
        }
    }
});

// 2. Record a new login token upon successful login
// 2. ログイン成功時に新しいトークンを記録する
add_action('grinds_post_login', function ($userId) {
    $_SESSION['grinds_strict_token'] = bin2hex(random_bytes(16));
    $pdo = App::db();
    if ($pdo) {
        $sessionKey = 'active_session_user_' . $userId;
        $pdo->prepare("INSERT OR REPLACE INTO settings (key, value, autoload) VALUES (?, ?, 0)")
            ->execute([$sessionKey, 'token:' . $_SESSION['grinds_strict_token']]);
    }
});
