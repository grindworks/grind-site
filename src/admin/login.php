<?php
ob_start();

/**
 * login.php
 *
 * Handle admin user authentication
 */
define('GRINDS_APP', true);

// Initialize admin environment
require_once __DIR__ . '/bootstrap_base.php';
require_once ROOT_PATH . '/lib/auth.php';

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) {
    exit;
}

// Attempt remember me login
if (empty($_SESSION['admin_logged_in']) && function_exists('grinds_check_remember_me')) {
    grinds_check_remember_me();
}

// Redirect if logged in
if (!empty($_SESSION['admin_logged_in'])) {
    redirect('admin/index.php');
}

// Load skin
$skin = require __DIR__ . '/load_skin.php';
if (!is_array($skin))
    $skin = [];

$colors = $skin['colors'] ?? [];
$font_family = !empty($skin['font']) ? $skin['font'] : 'sans-serif';

// Determine color scheme
$is_dark_mode = $skin['is_dark'] ?? false;
$color_scheme = $is_dark_mode ? 'dark' : 'light';
$is_sidebar_dark = $skin['is_sidebar_dark'] ?? true;

$body_extra_style = '';
$texture_url = $skin['texture'] ?? '';
if (!empty($texture_url)) {
    $body_extra_style .= "background-image: url('" . $texture_url . "'); background-repeat: repeat;";
}

// Parse query parameters
$queryParams = Routing::getParams();

// Configure login limits
$max_attempts = (int)get_option('security_max_attempts', 5);
$lockout_time = (int)get_option('security_lockout_time', 15);
$ip_address = get_client_ip();

// Set higher IP lockout threshold to prevent NAT collateral damage
$ip_max_attempts = max($max_attempts * 5, 20);

// Set current timestamp
$now_str = gmdate('Y-m-d H:i:s');

$error = '';
$is_locked = false;
$input_user = '';

// Check lockouts
$check_lockout = function ($table, $column, $value) use ($pdo, &$is_locked, &$error) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$value]);
        $attempt = $stmt->fetch();

        if ($attempt) {
            $lockedUntilTs = $attempt['locked_until'] ? strtotime($attempt['locked_until'] . ' UTC') : 0;

            if ($attempt['locked_until'] && $lockedUntilTs > time()) {
                $is_locked = true;
                $remaining_time = ceil(($lockedUntilTs - time()) / 60);
                $error = _t('already_locked', $remaining_time);
            } elseif ($attempt['locked_until'] && $lockedUntilTs <= time()) {
                $pdo->prepare("UPDATE {$table} SET locked_until = NULL, attempts = 0 WHERE {$column} = ?")->execute([$value]);
                $attempt['attempts'] = 0;
            }
        }
        return $attempt;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return null;
    }
};

$attempt = $check_lockout('login_attempts', 'ip_address', $ip_address);

// Garbage collect login attempts
if (rand(1, 20) === 1) {
    try {
        $yesterday = gmdate('Y-m-d H:i:s', time() - 86400);
        $pdo->prepare("DELETE FROM login_attempts WHERE last_attempt_at < ?")->execute([$yesterday]);
        $pdo->prepare("DELETE FROM username_login_attempts WHERE last_attempt_at < ?")->execute([$yesterday]);

        // Clean up expired remember me tokens
        $pdo->prepare("DELETE FROM user_tokens WHERE expires_at < ?")->execute([date('Y-m-d H:i:s')]);
    } catch (Exception $e) {
        // Ignore cleanup errors
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {
    // Validate CSRF token
    if (!validate_csrf_token(Routing::getString($_POST, 'csrf_token'))) {
        $error = _t('session_expired');
    } else {
        $input_user = trim(Routing::getString($_POST, 'username'));
        $input_pass = Routing::getString($_POST, 'password');
        $normalized_user = mb_strtolower($input_user, 'UTF-8');

        // Prevent Hash DoS by rejecting excessively long passwords
        if (strlen($input_pass) > 256) {
            $input_pass = '';
        }

        // Check user lockout
        $userAttempt = $check_lockout('username_login_attempts', 'username', $normalized_user);

        if (!$is_locked) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?)");
                $stmt->execute([$input_user]);
                $user = $stmt->fetch();

                // Set dummy hash for timing attack mitigation
                $dummy_hash = '$2y$10$abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1';

                if ($user) {
                    $isValid = password_verify($input_pass, $user['password']);
                } else {
                    // Perform dummy verification to prevent timing attacks
                    password_verify($input_pass, $dummy_hash);
                    $isValid = false;
                }

                if ($isValid) {

                    // Auto-rehash password if algorithm or cost changed
                    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                        $newHash = password_hash($input_pass, PASSWORD_DEFAULT);
                        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $user['id']]);
                    }

                    // Process successful login
                    session_regenerate_id(true);

                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'] ?? 'admin';
                    $_SESSION['user_avatar'] = $user['avatar'] ?? '';
                    $_SESSION['last_activity'] = time();

                    // Process remember me
                    if (!empty($_POST['remember_me']) && function_exists('grinds_remember_me')) {
                        grinds_remember_me($user['id']);
                    }

                    // Clear failed attempts
                    $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip_address]);
                    $pdo->prepare("DELETE FROM username_login_attempts WHERE username = ?")->execute([$normalized_user]);

                    // Determine redirect destination
                    $dest = 'admin/index.php';

                    if (isset($queryParams['installed'])) {
                        $dest = 'admin/index.php?installed=1';
                    } elseif (($t = Routing::getString($queryParams, 'redirect_to')) !== '') {
                        // Validate redirect URL
                        if (strpos($t, '/') === 0 && strpos($t, '//') !== 0 && strpos($t, '\\') === false) {
                            $dest = $t;
                        }
                    }

                    redirect($dest);
                } else {
                    // Process failed login
                    $current_attempts = ($attempt['attempts'] ?? 0) + 1;
                    $user_attempts = ($userAttempt['attempts'] ?? 0) + 1;
                    $locked_until = null;
                    $user_locked_until = null;
                    $error_msg = _t('auth_fail');
                    $is_new_lockout = false;

                    if ($current_attempts >= $ip_max_attempts) {
                        if ($current_attempts == $ip_max_attempts) $is_new_lockout = true;
                        $locked_until = gmdate('Y-m-d H:i:s', strtotime("+{$lockout_time} minutes"));
                        $is_locked = true;
                        $error_msg = _t('lock_alert', $lockout_time);
                    }

                    if ($user_attempts >= $max_attempts) {
                        if ($user_attempts == $max_attempts) $is_new_lockout = true;
                        $user_locked_until = gmdate('Y-m-d H:i:s', strtotime("+{$lockout_time} minutes"));
                        $is_locked = true;
                        $error_msg = _t('lock_alert', $lockout_time);
                    }

                    if (!$is_locked) {
                        $remain = min($ip_max_attempts - $current_attempts, $max_attempts - $user_attempts);
                        $error_msg .= " " . _t('remain', $remain);
                    }

                    $stmt = $pdo->prepare("INSERT OR REPLACE INTO login_attempts (ip_address, attempts, last_attempt_at, locked_until) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$ip_address, $current_attempts, $now_str, $locked_until]);
                    $stmt = $pdo->prepare("INSERT OR REPLACE INTO username_login_attempts (username, attempts, last_attempt_at, locked_until) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$normalized_user, $user_attempts, $now_str, $user_locked_until]);
                    $error = $error_msg;

                    // Send lockout alert email to admin on first occurrence
                    if ($is_new_lockout) {
                        $admin_email = get_option('smtp_admin_email');
                        $smtp_host = get_option('smtp_host');
                        if (!empty($admin_email) && !empty($smtp_host)) {
                            if (!class_exists('SimpleMailer')) {
                                require_once ROOT_PATH . '/lib/mail.php';
                            }
                            try {
                                $mailer = new SimpleMailer();
                                $siteName = get_option('site_name', CMS_NAME);
                                $subject = "[{$siteName}] Security Alert: Login Lockout";
                                $body = "A user account or IP address has been locked out due to multiple failed login attempts.\n\n";
                                $body .= "Time: " . gmdate('Y-m-d H:i:s') . " UTC\n";
                                $body .= "IP Address: {$ip_address}\n";
                                $body .= "Username: {$input_user}\n";
                                $body .= "Lockout Duration: {$lockout_time} minutes\n\n";
                                $body .= "If this was not you, please check your system logs.";
                                $mailer->send($admin_email, $subject, $body);
                            } catch (Exception $e) {
                                error_log("Failed to send lockout alert email: " . $e->getMessage());
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                $error = "System Error: " . h($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= h(get_option('site_lang', grinds_detect_language())) ?>" class="h-full antialiased">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <title>Login |
        <?= defined('SITE_NAME') ? h(SITE_NAME) : 'GrindSite' ?>
    </title>
    <link rel="icon" href="<?= h(get_favicon_url()) ?>">
    <?php require __DIR__ . '/layout/assets_loader.php'; ?>
    <style>
        /* Force form elements to inherit root color scheme */
        input:not([type="checkbox"]):not([type="radio"]),
        textarea,
        select {
            color-scheme: inherit;
            background-color: rgb(var(--color-input-bg) / var(--color-input-bg-alpha, 1)) !important;
            color: rgb(var(--color-input-text) / var(--color-input-text-alpha, 1)) !important;
            border: var(--border-width) solid rgb(var(--color-input-border) / var(--color-input-border-alpha, 1)) !important;
            border-radius: var(--border-radius) !important;
        }

        input[type="checkbox"],
        input[type="radio"] {
            background-color: rgb(var(--color-input-bg) / var(--color-input-bg-alpha, 1));
            border-color: rgb(var(--color-input-border) / var(--color-input-border-alpha, 1)) !important;
            color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
        }

        input:focus,
        textarea:focus,
        select:focus {
            border-color: rgb(var(--color-primary) / var(--color-primary-alpha, 1)) !important;
        }

        .btn-primary {
            background-color: rgb(var(--color-primary) / var(--color-primary-alpha, 1));
            color: rgb(var(--color-on-primary) / var(--color-on-primary-alpha, 1));
            border-radius: var(--btn-radius) !important;
            box-shadow: var(--box-shadow);
        }

        .btn-primary:hover {
            filter: brightness(1.1);
        }

        input::placeholder {
            color: rgb(var(--color-input-placeholder) / var(--color-input-placeholder-alpha, 1)) !important;
            opacity: 1;
        }

        .rounded-theme {
            border-radius: var(--border-radius);
        }

        .shadow-theme {
            box-shadow: var(--box-shadow);
        }
    </style>
</head>

<body class="flex justify-center items-center px-4 sm:px-6 lg:px-8 py-12 min-h-full">
    <div class="space-y-8 w-full max-w-md">
        <div class="text-center">
            <?php
            $logo = get_option('admin_logo');
            $showLogoLogin = get_option('admin_show_logo_login');
            if ($showLogoLogin === false)
                $showLogoLogin = '1';
            if ($logo && !preg_match('/^https?:\/\//', $logo)) {
                $logo = resolve_url($logo);
            }
            ?>
            <?php if ((string)$showLogoLogin === '1'): ?>
                <?php if ($logo): ?>
                    <img src="<?= h($logo) ?>" alt="<?= _t('lbl_logo') ?>" class="mx-auto mb-4 w-auto h-16 object-contain">
                <?php
                else: ?>
                    <div
                        class="flex justify-center items-center bg-theme-surface shadow-theme mx-auto mb-4 border border-theme-border rounded-theme w-12 h-12 font-bold text-theme-primary text-xl">
                        <?= h(mb_substr(get_option('site_name') ?: CMS_NAME, 0, 1)) ?>
                    </div>
                <?php
                endif; ?>
            <?php
            endif; ?>
            <h2 class="font-bold text-theme-text text-2xl tracking-tight">
                <?= h(get_option('admin_title') ?: get_option('site_name') ?: CMS_NAME) ?>
            </h2>
            <p class="opacity-60 mt-2 text-theme-text text-sm">
                <?= h(_t('admin_access')) ?>
            </p>
        </div>
        <div class="bg-theme-surface shadow-theme px-8 sm:px-10 py-10 border border-theme-border rounded-theme">
            <?php if (isset($queryParams['redirect_to'])): ?>
                <div
                    class="flex items-center gap-3 bg-theme-info/10 mb-6 p-4 border border-theme-info/20 rounded-theme text-theme-info">
                    <svg class="flex-shrink-0 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    <div class="font-medium text-sm">
                        <?php
                        if (isset($queryParams['reason']) && $queryParams['reason'] === 'expired') {
                            echo h(_t('session_expired'));
                        } else {
                            echo h(_t('login_required'));
                        }
                        ?>
                    </div>
                </div>
            <?php
            endif; ?>
            <?php if ($error): ?>
                <div
                    class="flex items-start gap-3 bg-theme-danger-light mb-6 p-4 border border-theme-danger-light rounded-theme text-theme-danger">
                    <svg class="flex-shrink-0 mt-0.5 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <div class="font-bold text-sm">
                        <?= $error ?>
                    </div>
                </div>
            <?php
            endif; ?>
            <?php if (!$is_locked): ?>
                <form method="post"
                    action="login.php<?= !empty($queryParams['redirect_to']) ? '?redirect_to=' . h($queryParams['redirect_to']) : '' ?>"
                    @submit="setTimeout(() => loading = true, 10)" class="space-y-6" x-data="{ loading: false }">
                    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                    <div>
                        <label for="username" class="block opacity-80 mb-2 font-bold text-theme-text text-sm">
                            <?= h(_t('username')) ?>
                        </label>
                        <div class="mt-1">
                            <input id="username" name="username" type="text" autocomplete="username" required
                                class="block px-4 py-3 w-full sm:text-sm transition-shadow"
                                placeholder="<?= h(_t('user_ph')) ?>" value="<?= h($input_user) ?>">
                        </div>
                    </div>
                    <div x-data="{ show: false }">
                        <label for="password" class="block opacity-80 mb-2 font-bold text-theme-text text-sm">
                            <?= h(_t('password')) ?>
                        </label>
                        <div class="relative mt-1">
                            <input type="password" :type="show ? 'text' : 'password'" id="password" name="password" autocomplete="current-password" required
                                class="block px-4 py-3 pr-10 w-full sm:text-sm transition-shadow"
                                placeholder="<?= h(_t('pass_ph')) ?>">
                            <button type="button" @click="show = !show"
                                class="right-0 absolute inset-y-0 flex items-center opacity-40 hover:opacity-100 px-3 focus:outline-none text-theme-text transition-opacity"
                                tabindex="-1">
                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-4 mb-4">
                        <div class="flex items-center">
                            <input id="remember_me" name="remember_me" type="checkbox"
                                class="bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-4 h-4 text-theme-primary form-checkbox">
                            <label for="remember_me" class="mt-0.5 ml-2 block text-sm text-theme-text opacity-80">
                                <?= h(_t('remember_me')) ?>
                            </label>
                        </div>
                    </div>
                    <div>
                        <button type="submit" :disabled="loading"
                            class="flex justify-center disabled:opacity-70 px-6 py-2.5 w-full font-bold text-sm transition-all disabled:cursor-not-allowed btn-primary"
                            :class="loading ? 'opacity-70 cursor-not-allowed' : ''">
                            <span x-show="!loading">
                                <?= h(_t('login')) ?>
                            </span>
                            <span x-show="loading" x-cloak class="flex items-center">
                                <svg class="mr-2 -ml-1 w-4 h-4 text-theme-on-primary animate-spin" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                <?= h(_t('processing')) ?>
                            </span>
                        </button>
                    </div>
                </form>
            <?php
            else: ?>
                <div class="bg-theme-bg p-6 border border-theme-border rounded-theme text-center">
                    <div
                        class="flex justify-center items-center bg-theme-surface opacity-50 mx-auto mb-4 border border-theme-border rounded-full w-12 h-12 text-theme-text">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                    </div>
                    <p class="opacity-80 mb-6 font-medium text-theme-text text-sm">
                        <?= h(_t('locked_msg')) ?>
                    </p>
                    <button onclick="location.reload()"
                        class="font-bold text-theme-primary text-sm hover:underline transition-colors">
                        <?= h(_t('reload')) ?>
                    </button>
                </div>
            <?php
            endif; ?>
        </div>
        <div class="text-center">
            <?php
            $back_url = resolve_url('/');
            if (!empty($queryParams['redirect_to'])) {
                $rt = $queryParams['redirect_to'];
                if (strpos($rt, '/') === 0 && strpos($rt, '//') !== 0) {
                    $back_url = $rt;
                }
            }
            ?>
            <a href="<?= h($back_url) ?>"
                class="flex justify-center items-center gap-2 opacity-60 hover:opacity-100 font-bold text-theme-text text-sm transition-opacity">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                <?= h(_t('back_site')) ?>
            </a>
        </div>
    </div>
</body>

</html>
