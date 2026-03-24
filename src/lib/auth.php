<?php

/**
 * Manage user authentication and sessions.
 */
if (!defined('GRINDS_APP'))
    exit;

/**
 * Check if current user has capability.
 *
 * @param string $cap Capability name.
 * @return bool
 */
function current_user_can($cap)
{
    $user = App::user();
    if (!$user)
        return false;

    $role = $user['role'] ?? 'editor';

    if ($role === 'admin')
        return true;

    $base_caps = ['dashboard', 'manage_posts', 'manage_media', 'edit_profile'];
    if (in_array($cap, $base_caps))
        return true;

    $perms_json = get_option('editor_permissions', '[]');
    $permissions = json_decode($perms_json, true);
    if (!is_array($permissions))
        $permissions = [];

    return in_array($cap, $permissions);
}

/**
 * Require login for admin pages.
 */
function require_login()
{
    if (!App::user()) {
        // Attempt Remember Me
        if (grinds_check_remember_me()) {
            return;
        }
        redirect_to_login();
    }

    $db_timeout = (int)get_option('session_timeout', 1800);
    $timeout_duration = ($db_timeout > 0) ? $db_timeout : 1800;

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // Attempt to renew session via Remember Me
        if (grinds_check_remember_me()) {
            $_SESSION['last_activity'] = time();
            return;
        }

        grinds_logout();
        redirect_to_login('expired');
    }

    $_SESSION['last_activity'] = time();
}

/**
 * Redirect unauthenticated users to login.
 */
function redirect_to_login($reason = '')
{
    if (is_ajax_request()) {
        json_response(['success' => false, 'error' => 'Session expired. Please log in again.', 'code' => 'SESSION_EXPIRED'], 401);
    }

    $current_url = $_SERVER['REQUEST_URI'];
    $redirect_param = '?redirect_to=' . urlencode($current_url);
    if ($reason === 'expired') {
        $redirect_param .= '&reason=expired';
    }

    redirect('admin/login.php' . $redirect_param);
}

/**
 * Set Remember Me cookie and token.
 *
 * @param int $user_id
 * @return void
 */
function grinds_remember_me($user_id)
{
    $selector = bin2hex(random_bytes(16));
    $validator = bin2hex(random_bytes(32));
    $hashed_validator = hash('sha256', $validator);
    $expires_at = date('Y-m-d H:i:s', time() + 86400 * 30);

    $pdo = App::db();
    if (!$pdo)
        return;

    try {
        $stmt = $pdo->prepare("INSERT INTO user_tokens (user_id, selector, hashed_validator, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $selector, $hashed_validator, $expires_at]);

        // Determine cookie path
        $cookiePath = _grinds_get_cookie_path();

        // Set secure cookie
        $cookie_value = "$selector:$validator";
        $secure = is_ssl();
        setcookie('grinds_remember', $cookie_value, [
            'expires' => time() + 86400 * 30,
            'path' => $cookiePath,
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } catch (Exception $e) {
        error_log("GrindsCMS Remember Me Error: " . $e->getMessage());
    }
}

/**
 * Check Remember Me cookie and log in user.
 *
 * @return bool True if logged in successfully.
 */
function grinds_check_remember_me()
{
    if (empty($_COOKIE['grinds_remember'])) {
        return false;
    }

    $parts = explode(':', $_COOKIE['grinds_remember']);
    if (count($parts) !== 2) {
        return false;
    }

    list($selector, $validator) = $parts;
    $hashed_validator = hash('sha256', $validator);

    $pdo = App::db();
    if (!$pdo)
        return false;

    try {
        // Fetch token
        $stmt = $pdo->prepare("SELECT id, user_id, hashed_validator, expires_at FROM user_tokens WHERE selector = ? AND expires_at > ?");
        $stmt->execute([$selector, date('Y-m-d H:i:s')]);
        $token = $stmt->fetch();

        if ($token && hash_equals($token['hashed_validator'], $hashed_validator)) {
            // Token is valid, fetch user
            $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmtUser->execute([$token['user_id']]);
            $user = $stmtUser->fetch();

            if ($user) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'] ?? 'admin';
                $_SESSION['user_avatar'] = $user['avatar'] ?? '';
                $_SESSION['last_activity'] = time();

                // Skip session/token regeneration on AJAX requests to prevent race conditions
                $isAjax = is_ajax_request();

                if (!$isAjax) {
                    session_regenerate_id(true);
                    $pdo->prepare("DELETE FROM user_tokens WHERE id = ?")->execute([$token['id']]);
                    grinds_remember_me($user['id']);
                }

                return true;
            }
        }
    } catch (Exception $e) {
        error_log("GrindsCMS Check Remember Me Error: " . $e->getMessage());
    }

    return false;
}

/**
 * Securely logout and clear cookies.
 */
function grinds_logout()
{
    $isSecure = function_exists('is_ssl') ? is_ssl() : false;

    // Clear session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params["path"],
            'domain' => $params["domain"],
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        if ($params["path"] !== '/') {
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => '/',
                'domain' => $params["domain"],
                'secure' => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    }
    session_destroy();

    // Clear Remember Me cookie
    if (isset($_COOKIE['grinds_remember'])) {
        $parts = explode(':', $_COOKIE['grinds_remember']);
        if (count($parts) === 2) {
            $selector = $parts[0];
            $pdo = App::db();
            if ($pdo) {
                try {
                    $pdo->prepare("DELETE FROM user_tokens WHERE selector = ?")->execute([$selector]);
                } catch (Exception $e) {
                    // Ignore DB errors during logout
                }
            }
        }
    }

    // Determine cookie path
    $cookiePath = _grinds_get_cookie_path();

    // Delete cookie
    setcookie('grinds_remember', '', [
        'expires' => time() - 3600,
        'path' => $cookiePath,
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}
