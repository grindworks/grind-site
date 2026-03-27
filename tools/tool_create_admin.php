<?php

/**
 * Admin Creator
 * Creates a temporary super admin account.
 *
 * USAGE:
 * 1. Upload this file to the web root (same level as config.php).
 * 2. Access via browser.
 * 3. Delete immediately after use.
 */
define('GRINDS_APP', true);

// Check PHP Version
if (version_compare(PHP_VERSION, '8.3.0', '<')) {
  http_response_code(500);
  die('GrindSite requires PHP 8.3.0 or higher. Your server is running PHP ' . PHP_VERSION . '.');
}

// Locate root directory.
$rootPath = null;
if (file_exists(__DIR__ . '/config.php')) {
  $rootPath = __DIR__;
}

if (!$rootPath) {
?>
  <!DOCTYPE html>
  <html lang="en" class="bg-slate-50 h-full antialiased">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Error | GrindSite</title>
    <script src="https://cdn.tailwindcss.com">
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
      body {
        font-family: 'Inter', sans-serif;
      }
    </style>
  </head>

  <body class="flex justify-center items-center p-4 min-h-full">
    <div class="bg-white shadow-lg p-8 border border-slate-200 rounded-[0.4rem] w-full max-w-md text-center">
      <div class="flex justify-center items-center bg-[#fef2f2] mx-auto mb-6 rounded-full ring-1 ring-[#fef2f2] w-16 h-16 text-[#ef4444]">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
      </div>
      <h1 class="mb-2 font-bold text-slate-900 text-xl">Configuration Not Found</h1>
      <div class="space-y-4 mb-6 text-left">
        <div class="bg-slate-50 p-4 border border-slate-100 rounded-[0.4rem]">
          <p class="mb-1 font-bold text-slate-700 text-sm">English</p>
          <p class="text-slate-600 text-sm"><code>config.php</code> was not found.<br><span class="font-bold text-[#ef4444]">Please move this tool to the root directory (same level as config.php) before running.</span></p>
        </div>
        <div class="bg-slate-50 p-4 border border-slate-100 rounded-[0.4rem]">
          <p class="mb-1 font-bold text-slate-700 text-sm">日本語</p>
          <p class="text-slate-600 text-sm"><code>config.php</code> が見つかりません。<br><span class="font-bold text-[#ef4444]">セキュリティのため、このツールを <code>config.php</code> と同じ階層（サイトのルート）に移動してから実行してください。</span></p>
        </div>
      </div>
    </div>
  </body>

  </html>
<?php
  exit;
}

// Load configuration.
require_once $rootPath . '/config.php';

if (file_exists($rootPath . '/lib/info.php')) {
  require_once $rootPath . '/lib/info.php';
}
if (!defined('CMS_NAME')) define('CMS_NAME', 'GrindSite');

// Determine base URL.
if (file_exists($rootPath . '/lib/bootstrap_url.php')) {
  require_once $rootPath . '/lib/bootstrap_url.php';
}

// Fallback to detected URL.
if (!defined('BASE_URL')) {
  if (function_exists('is_ssl')) {
    $is_https = is_ssl();
  } else {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  }
  $protocol = $is_https ? "https://" : "http://";
  $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
  define('BASE_URL', $protocol . $domain . $path);
}

// Detect language.
$lang = 'en';
if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'ja') !== false) {
  $lang = 'ja';
}
$trans = [
  'en' => [
    'title' => 'Admin Creator',
    'step_1' => 'Create Account',
    'step_2' => 'Creation Complete',
    'intro' => 'This tool creates a temporary Super Admin account. Please backup your files and database before use.',
    'desc' => 'Use this only if you have been locked out or all accounts were deleted.',
    'lbl_target_url' => 'Target System URL',
    'lbl_target_db' => 'Target Database File',
    'warning' => 'Security Protocol: This file will be <strong>deleted</strong> immediately after account creation.',
    'btn_execute' => 'Create & Delete Tool',
    'label_user' => 'New Username',
    'label_pass' => 'New Password',
    'copy_warn' => 'Please copy these credentials immediately.',
    'msg_deleted' => 'Tool deleted automatically.',
    'msg_delete_fail' => 'Auto-delete failed. Please delete manually.',
    'link_login' => 'Log In Now',
    'err_delete_warn_title' => '⚠️ SECURITY WARNING',
    'err_delete_warn_desc' => 'The tool could not delete itself automatically. <strong>Please connect via FTP and delete this file immediately</strong> to prevent unauthorized access.',
    'err_db' => 'Database Error',
    'lbl_app_key' => 'APP_KEY from config.php',
    'ph_app_key' => 'Paste APP_KEY here',
    'desc_app_key' => 'Required for identity verification. Check your config.php file.',
    'err_app_key' => 'Authentication failed: Invalid APP_KEY.',
    'err_not_writable' => 'Security Error: This file must be writable to auto-delete after execution. Please change file permissions (e.g., to 666) before proceeding.',
    'err_random_bytes' => 'System Error: Cannot generate a secure random token. Please check your server environment.',
  ],
  'ja' => [
    'title' => '緊急管理者作成',
    'step_1' => 'アカウント作成',
    'step_2' => '作成完了',
    'intro' => '一時的なスーパー管理者を作成します。実行前にファイルとデータベースのバックアップを推奨します。',
    'desc' => 'アカウントが削除された場合や、ログインできない緊急時にのみ使用してください。',
    'lbl_target_url' => '操作対象のURL',
    'lbl_target_db' => '操作対象のデータベース',
    'warning' => 'セキュリティプロトコル：アカウント作成後、このファイルは<strong>即座に削除</strong>されます。',
    'btn_execute' => '作成してツールを削除',
    'label_user' => '新規ユーザー名',
    'label_pass' => '新規パスワード',
    'copy_warn' => '必ず今すぐコピーしてください。画面を閉じると二度と表示されません。',
    'msg_deleted' => 'ツールは自動削除されました。',
    'msg_delete_fail' => 'ファイルの削除に失敗しました。手動で削除してください。',
    'link_login' => '今すぐログイン',
    'err_delete_warn_title' => '⚠️ セキュリティ警告',
    'err_delete_warn_desc' => 'ツールを自動削除できませんでした。<strong>不正アクセスを防ぐため、直ちにFTPで接続してこのファイルを削除してください。</strong>',
    'err_db' => 'データベースエラー',
    'lbl_app_key' => 'config.php の APP_KEY',
    'ph_app_key' => '設定ファイル内のAPP_KEYをコピペしてください',
    'desc_app_key' => '※本人確認のため、サーバー上の config.php に記載されたAPP_KEYが必要です。',
    'err_app_key' => '認証に失敗しました: APP_KEYが正しくありません。',
    'err_not_writable' => 'セキュリティエラー：実行後の自動削除を行うため、このファイル自身のパーミッションを書き込み可能（666など）に変更してから再読み込みしてください。',
    'err_random_bytes' => 'システムエラー：安全なランダムトークンを生成できません。サーバー環境を確認してください。',
  ]
];

function t($key)
{
  global $trans, $lang;
  return $trans[$lang][$key] ?? $key;
}

// Define helper.
if (!function_exists('h')) {
  function h($s)
  {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
  }
}

// Handle admin creation.
$step = 'confirm';
$newUser = '';
$newPass = '';
$error = null;
$is_deleted = false;
$is_writable = is_writable(__FILE__);
if (!$is_writable) {
  $error = t('err_not_writable');
}

// Initialize session.
if (session_status() === PHP_SESSION_NONE) {
  $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
  session_set_cookie_params([
    'lifetime' => 1800, // 30 minutes
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => $is_https,
    'httponly' => true,
    'samesite' => 'Lax'
  ]);
  session_start();
}
if (empty($_SESSION['tool_csrf'])) {
  try {
    $_SESSION['tool_csrf'] = bin2hex(random_bytes(32));
  } catch (Exception $e) {
    http_response_code(500);
    die(t('err_random_bytes'));
  }
}

try {
  if (!defined('DB_FILE')) throw new Exception('DB_FILE constant not defined.');

  // Process form.
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_writable) {
      throw new Exception(t('err_not_writable'));
    } elseif (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['tool_csrf']) {
      throw new Exception('Invalid CSRF Token');
    }

    $inputKey = trim($_POST['app_key'] ?? '');
    $appKey = defined('APP_KEY') ? APP_KEY : null;
    if ($appKey === null || !hash_equals($appKey, $inputKey)) {
      throw new Exception(t('err_app_key'));
    }

    // Create credentials.
    $newUser = 'emergency_' . substr(bin2hex(random_bytes(3)), 0, 5);
    $newPass = bin2hex(random_bytes(8));
    $hash = password_hash($newPass, PASSWORD_DEFAULT);

    $pdo = null;
    // Ensure schema.
    if (file_exists($rootPath . '/lib/db.php')) {
      define('GRINDS_SKIP_DB_INIT', true);
      require_once $rootPath . '/lib/db.php';
      if (function_exists('grinds_db_connect')) {
        $pdo = grinds_db_connect();
      }
      if ($pdo && function_exists('grinds_db_migrate')) {
        grinds_db_migrate($pdo);
      }
    }

    if (!$pdo) {
      $pdo = new PDO("sqlite:" . DB_FILE);
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $pdo->exec("PRAGMA busy_timeout = 60000;");
      try {
        $pdo->exec("PRAGMA journal_mode = WAL;");
      } catch (Exception $e) {
        // 共有サーバー等でWALモードが拒否された場合はフォールバック
        $pdo->exec("PRAGMA journal_mode = DELETE;");
      }
      $pdo->exec("PRAGMA foreign_keys = ON;");
    }

    // Save admin user.
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, avatar, role, created_at) VALUES (?, ?, 'emergency@localhost', '', 'admin', ?)");
    $stmt->execute([$newUser, $hash, $now]);

    // Clear locks.
    $pdo->exec("DELETE FROM login_attempts");

    // Delete tool.
    $selfFile = __FILE__;
    if (function_exists('gc_collect_cycles')) gc_collect_cycles();

    // Overwrite content.
    @file_put_contents($selfFile, "<?php http_response_code(403); exit('Deleted.');");

    if (function_exists('opcache_invalidate')) {
      @opcache_invalidate($selfFile, true);
    }

    if (@unlink($selfFile)) {
      clearstatcache(true, $selfFile);
      if (!file_exists($selfFile)) {
        $is_deleted = true;
      }
    }

    // Fallback to rename.
    if (!$is_deleted) {
      $renamed = $selfFile . '.bak';
      if (@rename($selfFile, $renamed)) {
        $is_deleted = true;
        @chmod($renamed, 0444);
      }
    }

    if (!$is_deleted) {
      register_shutdown_function(function () use ($selfFile) {
        if (file_exists($selfFile)) @unlink($selfFile);
      });
    }

    $step = 'result';
  }
} catch (Exception $e) {
  $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>" class="bg-slate-50 h-full antialiased">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h(t('title')) ?> | <?= h(CMS_NAME) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }

    .font-mono {
      font-family: 'JetBrains Mono', monospace;
    }

    .shadow-soft {
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    }

    [x-cloak] {
      display: none !important;
    }
  </style>
</head>

<body class="flex justify-center items-center p-4 min-h-full">
  <div class="bg-white shadow-soft border border-slate-200 rounded-[0.4rem] w-full max-w-md overflow-hidden">

    <!-- Header -->
    <div class="px-8 pt-8 pb-6 border-slate-100 border-b text-center">
      <div class="flex justify-center items-center bg-[#eff6ff] mx-auto mb-5 rounded-full ring-1 ring-[#eff6ff] w-14 h-14 text-[#0f62fe]">
        <!-- Heroicon: user-plus -->
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3.75 15c0-2.878 2.551-5.25 6-5.250 3.448 0 6 2.372 6 5.25v.375a3.375 3.375 0 01-3.375 3.375h-5.25A3.375 3.375 0 013.75 15.375V15z" />
        </svg>
      </div>
      <h1 class="font-bold text-slate-900 text-xl tracking-tight"><?= h(t('title')) ?></h1>
      <p class="mt-2 font-medium text-slate-500 text-sm"><?= h($step === 'confirm' ? t('step_1') : t('step_2')) ?></p>
    </div>

    <div class="p-8">
      <?php if ($error): ?>
        <div class="flex items-start gap-3 bg-[#fef2f2] mb-6 p-4 border border-red-100 rounded-[0.4rem] font-medium text-[#991b1b] text-sm">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mt-0.5 w-5 h-5 shrink-0">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
          </svg>
          <?= h($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($step === 'confirm'): ?>
        <div class="space-y-6">
          <div class="space-y-2">
            <p class="font-medium text-slate-700"><?= h(t('intro')) ?></p>
            <p class="text-slate-500 text-sm leading-relaxed"><?= h(t('desc')) ?></p>
          </div>

          <div class="bg-slate-50 p-4 border border-slate-200 rounded-[0.4rem]">
            <div class="mb-3">
              <p class="mb-1 font-bold text-slate-500 text-xs uppercase tracking-wider"><?= h(t('lbl_target_url')) ?></p>
              <p class="font-mono font-bold text-slate-900 text-sm break-all"><?= h(BASE_URL) ?></p>
            </div>
            <div>
              <p class="mb-1 font-bold text-slate-500 text-xs uppercase tracking-wider"><?= h(t('lbl_target_db')) ?></p>
              <p class="opacity-80 font-mono text-[10px] text-slate-800 break-all leading-tight"><?= h(DB_FILE) ?></p>
            </div>
          </div>

          <div class="flex items-start gap-3 bg-[#fffbeb] p-4 border border-amber-100 rounded-[0.4rem]">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="flex-shrink-0 mt-0.5 w-5 h-5 text-[#f59e0b]">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <p class="text-[#92400e] text-sm leading-snug"><?= t('warning') ?></p>
          </div>

          <form method="post" class="space-y-6">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['tool_csrf']) ?>">

            <div>
              <label class="block mb-1.5 font-bold text-slate-500 text-xs uppercase tracking-wider"><?= h(t('lbl_app_key')) ?></label>
              <input type="text" name="app_key" required class="block bg-slate-50 shadow-sm px-4 py-3 border-slate-300 focus:border-[#0f62fe] rounded-[0.4rem] focus:ring-[#0f62fe] w-full transition-colors" placeholder="<?= h(t('ph_app_key')) ?>">
              <p class="mt-1 text-xs text-slate-400"><?= h(t('desc_app_key')) ?></p>
            </div>

            <button type="submit" <?= !$is_writable ? 'disabled' : '' ?> class="bg-[#0f62fe] hover:bg-[#0353e9] shadow-sm hover:shadow px-4 py-3.5 rounded-[0.4rem] outline-none focus:ring-4 focus:ring-blue-100 w-full font-bold text-white transition <?= !$is_writable ? 'opacity-50 cursor-not-allowed' : '' ?>">
              <?= h(t('btn_execute')) ?>
            </button>
          </form>
        </div>
      <?php elseif ($step === 'result'): ?>
        <div class="space-y-6" x-data="copyCredentials()">
          <div class="space-y-4">
            <div>
              <label class="block mb-1.5 font-bold text-slate-500 text-xs uppercase tracking-wider"><?= h(t('label_user')) ?></label>
              <div class="relative">
                <input type="text" readonly value="<?= h($newUser) ?>" :value="user" class="bg-slate-100 shadow-inner w-full pl-4 pr-12 py-3 border-slate-200 rounded-[0.4rem] font-mono text-slate-800 text-sm select-all focus:outline-none">
                <button @click="copy('user')" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 transition-colors">
                  <span class="sr-only">Copy</span>
                  <svg x-show="!copied.user" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a2.25 2.25 0 01-2.25 2.25h-1.5a2.25 2.25 0 01-2.25-2.25v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                  </svg>
                  <svg x-show="copied.user" x-cloak class="w-5 h-5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                  </svg>
                </button>
              </div>
            </div>
            <div>
              <label class="block mb-1.5 font-bold text-slate-500 text-xs uppercase tracking-wider"><?= h(t('label_pass')) ?></label>
              <div class="relative">
                <input type="text" readonly value="<?= h($newPass) ?>" :value="pass" class="bg-slate-100 shadow-inner w-full pl-4 pr-12 py-3 border-slate-200 rounded-[0.4rem] font-mono text-slate-800 text-sm select-all focus:outline-none">
                <button @click="copy('pass')" class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600 transition-colors">
                  <span class="sr-only">Copy</span>
                  <svg x-show="!copied.pass" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a2.25 2.25 0 01-2.25 2.25h-1.5a2.25 2.25 0 01-2.25-2.25v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                  </svg>
                  <svg x-show="copied.pass" x-cloak class="w-5 h-5 text-emerald-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <div class="bg-[#fef2f2] p-4 border border-red-100 rounded-[0.4rem] text-center">
            <p class="font-bold text-[#991b1b] text-sm"><?= h(t('copy_warn')) ?></p>
          </div>

          <div class="pt-6 border-slate-100 border-t text-center">
            <?php if ($is_deleted): ?>
              <span class="inline-flex items-center bg-slate-100 px-3 py-1 rounded-full font-medium text-slate-500 text-xs">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mr-1.5 w-3.5 h-3.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                <?= h(t('msg_deleted')) ?>
              </span>
            <?php else: ?>
              <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 text-left" role="alert">
                <p class="font-bold"><?= t('err_delete_warn_title') ?></p>
                <p class="text-sm"><?= t('err_delete_warn_desc') ?></p>
              </div>
            <?php endif; ?>
          </div>

          <a href="<?= h(BASE_URL) ?>/admin/login.php" class="block bg-[#0f62fe] hover:bg-[#0353e9] shadow-sm hover:shadow px-4 py-3.5 rounded-[0.4rem] w-full font-bold text-white text-center transition">
            <?= h(t('link_login')) ?>
          </a>
        </div>
      <?php endif; ?>

    </div>
  </div>
  <script>
    document.addEventListener('alpine:init', () => {
      Alpine.data('copyCredentials', () => ({
        user: <?= json_encode($newUser) ?>,
        pass: <?= json_encode($newPass) ?>,
        copied: {
          user: false,
          pass: false
        },
        copy(type) {
          const textToCopy = type === 'user' ? this.user : this.pass;
          navigator.clipboard.writeText(textToCopy).then(() => {
            this.copied[type] = true;
            setTimeout(() => {
              this.copied[type] = false;
            }, 2000);
          });
        }
      }))
    })
  </script>
</body>

</html>
