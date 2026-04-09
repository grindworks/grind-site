<?php

/**
 * Password Recovery
 * Forces a password update for user accounts.
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
    'title' => 'Password Recovery',
    'step_1' => 'New Credentials',
    'step_2' => 'Reset Complete',
    'intro' => 'This tool forces a password update. Please backup your files and database before use.',
    'lbl_target_url' => 'Target System URL',
    'lbl_target_db' => 'Target Database File',
    'lbl_identity' => 'Target Username or Email',
    'lbl_new' => 'New Password',
    'lbl_confirm' => 'Confirm Password',
    'ph_identity' => 'e.g. admin',
    'ph_new' => 'Min 8 characters',
    'warning' => 'Security Protocol: This file will be <strong>deleted</strong> immediately after the reset.',
    'btn_submit' => 'Reset Password',
    'msg_success' => 'Password has been updated.',
    'msg_deleted' => 'File deleted automatically.',
    'msg_delete_fail' => 'Auto-delete failed. Please delete manually.',
    'link_login' => 'Return to Login',
    'err_csrf' => 'Invalid CSRF Token.',
    'err_empty' => 'All fields are required.',
    'err_mismatch' => 'Passwords do not match.',
    'err_short' => 'Password must be at least 8 characters and contain both letters and numbers.',
    'err_user_not_found' => 'User not found.',
    'err_db' => 'Database Error: ',
    'err_delete_warn_title' => '⚠️ SECURITY WARNING',
    'err_delete_warn_desc' => 'The tool could not delete itself automatically. <strong>Please connect via FTP and delete this file immediately</strong> to prevent unauthorized access.',
    'lbl_app_key' => 'APP_KEY from config.php',
    'ph_app_key' => 'Paste APP_KEY here',
    'desc_app_key' => 'Required for identity verification. Check your config.php file.',
    'err_app_key' => 'Authentication failed: Invalid APP_KEY.',
    'err_not_writable' => 'Security Error: This file must be writable to auto-delete after execution. Please change file permissions (e.g., to 666) before proceeding.',
    'err_random_bytes' => 'System Error: Cannot generate a secure random token. Please check your server environment.',
  ],
  'ja' => [
    'title' => 'パスワード復旧',
    'step_1' => '新しいパスワード',
    'step_2' => 'リセット完了',
    'intro' => 'パスワードを強制的に書き換えます。実行前にファイルとデータベースのバックアップを推奨します。',
    'lbl_target_url' => '操作対象のURL',
    'lbl_target_db' => '操作対象のデータベース',
    'lbl_identity' => '対象のユーザー名 または メール',
    'lbl_new' => '新しいパスワード',
    'lbl_confirm' => 'パスワード（確認）',
    'ph_identity' => '例: admin',
    'ph_new' => '8文字以上',
    'warning' => 'セキュリティプロトコル：リセット完了後、このファイルは<strong>即座に削除</strong>されます。',
    'btn_submit' => 'パスワードを変更する',
    'msg_success' => 'パスワードを変更しました。',
    'msg_deleted' => 'ファイルは自動削除されました。',
    'msg_delete_fail' => 'ファイルの削除に失敗しました。手動で削除してください。',
    'link_login' => 'ログイン画面へ戻る',
    'err_csrf' => '不正なリクエストです。',
    'err_empty' => 'すべての項目を入力してください。',
    'err_mismatch' => 'パスワードが一致しません。',
    'err_short' => 'パスワードは8文字以上の英文字と数字を含む必要があります。',
    'err_user_not_found' => 'ユーザーが見つかりません。',
    'err_db' => 'データベースエラー: ',
    'err_delete_warn_title' => '⚠️ セキュリティ警告',
    'err_delete_warn_desc' => 'ツールを自動削除できませんでした。<strong>不正アクセスを防ぐため、直ちにFTPで接続してこのファイルを削除してください。</strong>',
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

// Handle password reset.
$step = 'form';
$error = '';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$is_writable) {
    $error = t('err_not_writable');
  } elseif (!isset($_POST['csrf']) || !hash_equals($_SESSION['tool_csrf'], $_POST['csrf'])) {
    $error = t('err_csrf');
  } else {
    $inputKey = trim($_POST['app_key'] ?? '');
    if (!defined('APP_KEY') || !hash_equals(hash('sha256', APP_KEY), hash('sha256', $inputKey))) {
      $error = t('err_app_key');
    } else {
      $identity = trim($_POST['identity'] ?? '');
      $new_pass = $_POST['new_pass'] ?? '';
      $confirm_pass = $_POST['confirm_pass'] ?? '';

      if (empty($identity) || empty($new_pass) || empty($confirm_pass)) {
        $error = t('err_empty');
      } elseif ($new_pass !== $confirm_pass) {
        $error = t('err_mismatch');
      } elseif (strlen($new_pass) < 8 || !preg_match('/[A-Za-z]/', $new_pass) || !preg_match('/[0-9]/', $new_pass)) {
        $error = t('err_short');
      } else {
        try {
          if (!defined('DB_FILE') || !file_exists(DB_FILE)) {
            throw new Exception("Database file not found.");
          }

          $pdo = new PDO("sqlite:" . DB_FILE);
          $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
          $pdo->exec("PRAGMA busy_timeout = 60000;");
          try {
            $mode = $pdo->query("PRAGMA journal_mode = WAL;")->fetchColumn();
            if (strtoupper($mode) !== 'WAL') {
              throw new Exception("WAL mode not supported");
            }
          } catch (Exception $e) {
            // 共有サーバー等でWALモードが拒否された場合はフォールバック
            $pdo->exec("PRAGMA journal_mode = DELETE;");
          }
          $pdo->exec("PRAGMA foreign_keys = ON;");

          // Search user.
          $normalized_identity = mb_strtolower($identity, 'UTF-8');
          $stmt = $pdo->prepare("SELECT id, username FROM users WHERE LOWER(username) = ? OR LOWER(email) = ? LIMIT 1");
          $stmt->execute([$normalized_identity, $normalized_identity]);
          $user = $stmt->fetch(PDO::FETCH_ASSOC);

          if (!$user) {
            $error = t('err_user_not_found');
          } else {
            // Update password.
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmtUpdate = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtUpdate->execute([$new_hash, $user['id']]);

            // Invalidate existing tokens.
            $pdo->prepare("DELETE FROM user_tokens WHERE user_id = ?")->execute([$user['id']]);

            // Reset login attempts.
            $pdo->prepare("DELETE FROM username_login_attempts WHERE username = ?")->execute([$user['username']]);

            // Clear locks.
            $pdo->exec("DELETE FROM login_attempts");

            // Clear active sessions.
            $sessionPath = $rootPath . '/data/sessions';
            if (is_dir($sessionPath)) {
              $sessions = glob($sessionPath . '/sess_*');
              if ($sessions) {
                foreach ($sessions as $sessFile) {
                  if (is_file($sessFile)) @unlink($sessFile);
                }
              }
            }

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
          $error = t('err_db') . htmlspecialchars($e->getMessage());
        }
      }
    }
  }
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
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

    <div class="px-8 pt-8 pb-6 border-slate-100 border-b text-center">
      <div class="flex justify-center items-center bg-[#eff6ff] mx-auto mb-5 rounded-full ring-1 ring-[#eff6ff] w-14 h-14 text-[#0f62fe]">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-7 h-7">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
        </svg>
      </div>
      <h1 class="font-bold text-slate-900 text-xl tracking-tight"><?= h(t('title')) ?></h1>
      <p class="mt-2 font-medium text-slate-500 text-sm"><?= h($step === 'form' ? t('step_1') : t('step_2')) ?></p>
    </div>

    <div class="p-8">
      <?php if ($step === 'form'): ?>
        <?php if ($error): ?>
          <div class="flex gap-2 bg-[#fef2f2] mb-6 p-4 border border-red-100 rounded-[0.4rem] font-medium text-[#991b1b] text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mt-0.5 w-5 h-5 shrink-0">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <?= h($error) ?>
          </div>
        <?php endif; ?>

        <p class="mb-6 text-slate-600 text-sm leading-relaxed"><?= h(t('intro')) ?></p>

        <div class="bg-[#eff6ff] mb-6 p-4 border border-blue-100 rounded-[0.4rem]">
          <div class="mb-3">
            <p class="mb-1 font-bold text-blue-500 text-xs uppercase tracking-wider"><?= h(t('lbl_target_url')) ?></p>
            <p class="font-mono font-bold text-blue-900 text-sm break-all"><?= h(BASE_URL) ?></p>
          </div>
          <div>
            <p class="mb-1 font-bold text-blue-500 text-xs uppercase tracking-wider"><?= h(t('lbl_target_db')) ?></p>
            <p class="opacity-80 font-mono text-[10px] text-blue-800 break-all leading-tight"><?= h(DB_FILE) ?></p>
          </div>
        </div>

        <form method="post" class="space-y-5">
          <input type="hidden" name="csrf" value="<?= h($_SESSION['tool_csrf']) ?>">

          <div>
            <label class="block mb-1.5 font-bold text-slate-500 text-xs uppercase tracking-wider"><?= h(t('lbl_app_key')) ?></label>
            <input type="text" name="app_key" required class="block bg-slate-50 shadow-sm px-4 py-3 border-slate-300 focus:border-[#0f62fe] rounded-[0.4rem] focus:ring-[#0f62fe] w-full transition-colors" placeholder="<?= h(t('ph_app_key')) ?>">
            <p class="mt-1 text-xs text-slate-400"><?= h(t('desc_app_key')) ?></p>
          </div>

          <div>
            <label class="block mb-2 ml-1 font-bold text-slate-500 text-xs uppercase tracking-wider"><?= h(t('lbl_identity')) ?></label>
            <input type="text" name="identity" required class="block bg-slate-50 shadow-sm px-4 py-3 border-slate-300 focus:border-[#0f62fe] rounded-[0.4rem] focus:ring-[#0f62fe] w-full transition-colors" placeholder="<?= h(t('ph_identity')) ?>">
          </div>

          <div x-data="{ show: false }">
            <label class="block mb-2 ml-1 font-bold text-slate-500 text-xs uppercase tracking-wider"><?= h(t('lbl_new')) ?></label>
            <div class="relative">
              <input :type="show ? 'text' : 'password'" name="new_pass" required class="font-mono block bg-slate-50 shadow-sm px-4 py-3 pr-10 border-slate-300 focus:border-[#0f62fe] rounded-[0.4rem] focus:ring-[#0f62fe] w-full transition-colors" placeholder="<?= h(t('ph_new')) ?>">
              <button type="button" @click="show = !show" class="right-0 absolute inset-y-0 flex items-center px-3 text-slate-400 hover:text-slate-600 transition-colors">
                <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                </svg>
              </button>
            </div>
          </div>

          <div>
            <label class="block mb-2 ml-1 font-bold text-slate-500 text-xs uppercase tracking-wider"><?= h(t('lbl_confirm')) ?></label>
            <input type="password" name="confirm_pass" required class="font-mono block bg-slate-50 shadow-sm px-4 py-3 border-slate-300 focus:border-[#0f62fe] rounded-[0.4rem] focus:ring-[#0f62fe] w-full transition-colors">
          </div>

          <div class="flex items-start gap-3 bg-[#fffbeb] mt-6 p-4 border border-amber-100 rounded-[0.4rem]">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="flex-shrink-0 mt-0.5 w-5 h-5 text-[#f59e0b]">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <p class="text-[#92400e] text-sm leading-snug"><?= t('warning') ?></p>
          </div>

          <button type="submit" <?= !$is_writable ? 'disabled' : '' ?> class="bg-[#0f62fe] hover:bg-[#0353e9] shadow-sm hover:shadow-md mt-4 px-4 py-3.5 rounded-[0.4rem] outline-none focus:ring-4 focus:ring-blue-100 w-full font-bold text-white transition <?= !$is_writable ? 'opacity-50 cursor-not-allowed' : '' ?>">
            <?= h(t('btn_submit')) ?>
          </button>
        </form>

      <?php else: ?>
        <div class="space-y-6 text-center">
          <div class="flex justify-center items-center bg-[#ecfdf5] mx-auto rounded-full ring-1 ring-emerald-100 w-16 h-16">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-[#10b981]">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="space-y-2">
            <h3 class="font-bold text-slate-900 text-lg"><?= h(t('msg_success')) ?></h3>
            <p class="text-slate-500 text-sm">You can now log in with your new password.</p>
          </div>

          <div class="py-4">
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
</body>

</html>
