<?php

/**
 * System Repair Tool
 * Resets core configuration and recovers from lockouts.
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
      <div
        class="flex justify-center items-center bg-[#fef2f2] mx-auto mb-6 rounded-full ring-1 ring-[#fef2f2] w-16 h-16 text-[#ef4444]">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
          class="w-8 h-8">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
      </div>
      <h1 class="mb-2 font-bold text-slate-900 text-xl">Configuration Not Found</h1>
      <div class="space-y-4 mb-6 text-left">
        <div class="bg-slate-50 p-4 border border-slate-100 rounded-[0.4rem]">
          <p class="mb-1 font-bold text-slate-700 text-sm">English</p>
          <p class="text-slate-600 text-sm"><code>config.php</code> was not found.<br><span
              class="font-bold text-[#ef4444]">Please move this tool to the root directory (same level as config.php)
              before running.</span></p>
        </div>
        <div class="bg-slate-50 p-4 border border-slate-100 rounded-[0.4rem]">
          <p class="mb-1 font-bold text-slate-700 text-sm">日本語</p>
          <p class="text-slate-600 text-sm"><code>config.php</code> が見つかりません。<br><span
              class="font-bold text-[#ef4444]">セキュリティのため、このツールを <code>config.php</code>
              と同じ階層（サイトのルート）に移動してから実行してください。</span></p>
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
if (!defined('CMS_NAME'))
  define('CMS_NAME', 'GrindSite');

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
    'title' => 'System Repair',
    'step_1' => 'Diagnostic & Repair',
    'step_2' => 'Repair Complete',
    'intro' => 'This tool resets core configuration. Please backup your files and database before use.',
    'lbl_target_url' => 'Target System URL',
    'lbl_target_db' => 'Target Database File',
    'action_list' => 'Actions to be performed',
    'act_1' => 'Reset Admin Layout to "Sidebar"',
    'act_2' => 'Turn OFF Debug Mode',
    'act_3' => 'Clear Login Lockouts',
    'act_4' => 'Regenerate .htaccess (Overwrites file, backup created)',
    'act_5' => 'Configure RewriteBase (Subdirectory detected)',
    'warning' => 'Security Protocol: This file will be <strong>automatically deleted</strong> immediately after execution.',
    'btn_execute' => 'Run Repair Tool',
    'msg_success' => 'System repaired successfully.',
    'msg_deleted' => 'File deleted automatically.',
    'msg_delete_fail' => 'Auto-delete failed. Please delete manually.',
    'link_login' => 'Return to Login',
    'err_db' => 'Database Connection Failed',
    'trouble_title' => 'Still seeing 404/500 errors?',
    'trouble_desc' => 'You may need to uncomment <strong>RewriteBase</strong> in your <code>.htaccess</code> file.',
    'nginx_warn' => '⚠️ For Nginx Users: If you changed the directory, please update the server configuration file path manually.',
    'lbl_app_key' => 'APP_KEY from config.php',
    'ph_app_key' => 'Paste APP_KEY here',
    'desc_app_key' => 'Required for identity verification. Check your config.php file.',
    'err_app_key' => 'Authentication failed: Invalid APP_KEY.',
    'err_not_writable' => 'Security Error: This file must be writable to auto-delete after execution. Please change file permissions (e.g., to 666) before proceeding.',
    'err_random_bytes' => 'System Error: Cannot generate a secure random token. Please check your server environment.',
  ],
  'ja' => [
    'title' => 'システム修復',
    'step_1' => '診断と修復',
    'step_2' => '修復完了',
    'intro' => '設定を初期値に戻します。実行前にファイルとデータベースのバックアップを推奨します。',
    'lbl_target_url' => '操作対象のURL',
    'lbl_target_db' => '操作対象のデータベース',
    'action_list' => '実行される処理',
    'act_1' => '管理画面レイアウトを「サイドバー」にリセット',
    'act_2' => 'デバッグモードを「OFF」にする',
    'act_3' => 'ログインロック（試行回数制限）を解除する',
    'act_4' => '.htaccess を再生成する（上書きされますが、バックアップを作成します）',
    'act_5' => 'RewriteBase を設定する（サブディレクトリ検知時）',
    'warning' => 'セキュリティプロトコル：このファイルは実行完了後、<strong>自動的に削除</strong>されます。',
    'btn_execute' => '修復を実行する',
    'msg_success' => 'システムの修復が完了しました。',
    'msg_deleted' => 'ファイルは自動削除されました。',
    'msg_delete_fail' => 'ファイルの削除に失敗しました。手動で削除してください。',
    'link_login' => 'ログイン画面へ戻る',
    'err_db' => 'データベース接続エラー',
    'trouble_title' => 'まだエラーが表示されますか？',
    'trouble_desc' => '下層ページで 404/500 エラーが出る場合、<code>.htaccess</code> の <strong>RewriteBase</strong> のコメントを外してみてください。',
    'nginx_warn' => '⚠️ Nginx環境の方へ: ディレクトリを移動した場合、サーバー側の設定ファイル（nginx.conf等）のパスも変更してください。',
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

// Handle repair.
$step = 'confirm';
$results = [];
$error = null;
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
  if (!defined('DB_FILE') || !file_exists(DB_FILE)) {
    throw new Exception(t('err_db') . ": DB_FILE not found.");
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

  // Apply fixes.
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_writable) {
      throw new Exception(t('err_not_writable'));
    } elseif (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['tool_csrf']) {
      throw new Exception('Invalid CSRF Token');
    }

    $inputKey = trim($_POST['app_key'] ?? '');
    if (!defined('APP_KEY') || !hash_equals(hash('sha256', APP_KEY), hash('sha256', $inputKey))) {
      throw new Exception(t('err_app_key'));
    }

    // Reset layout.
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (key, value, autoload) VALUES ('admin_layout', 'sidebar', 1)");
    $stmt->execute();
    $results[] = t('act_1');

    // Disable debug.
    $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (key, value, autoload) VALUES ('debug_mode', '0', 1)");
    $stmt->execute();
    $results[] = t('act_2');

    // Reset login attempts.
    $stmt = $pdo->prepare("DELETE FROM login_attempts");
    $stmt->execute();
    $pdo->exec("DELETE FROM username_login_attempts");
    $results[] = t('act_3');

    // Backup .htaccess.
    if (file_exists($rootPath . '/.htaccess')) {
      @copy($rootPath . '/.htaccess', $rootPath . '/.htaccess_' . date('YmdHis') . '.bak');
    }

    // Generate .htaccess.
    if (file_exists($rootPath . '/lib/htaccess_generator.php')) {
      require_once $rootPath . '/lib/htaccess_generator.php';
      $htaccessContent = grinds_get_htaccess_content(false);
    } else {
      throw new Exception("htaccess_generator.php not found.");
    }

    if (@file_put_contents($rootPath . '/.htaccess', $htaccessContent)) {
      $results[] = t('act_4');
    }

    // Disable Basic Auth to fix 500 error.
    if (file_exists($rootPath . '/admin/.htaccess')) {
      @rename($rootPath . '/admin/.htaccess', $rootPath . '/admin/.htaccess_bak_' . date('YmdHis'));
      $results[] = 'Disabled Basic Auth (Renamed admin/.htaccess to fix 500 error)';
      if ($lang === 'ja') {
        $results[count($results) - 1] = 'Basic認証を無効化しました（パスズレによる500エラー解消）';
      }
    }

    // Delete tool.
    $selfFile = __FILE__;
    if (function_exists('gc_collect_cycles'))
      gc_collect_cycles();

    // Overwrite content.
    @file_put_contents($selfFile, "<?php http_response_code(403); exit('Deleted.');");

    if (function_exists('opcache_invalidate')) {
      @opcache_invalidate($selfFile, true);
    }

    $isDeleted = false;
    if (@unlink($selfFile)) {
      clearstatcache(true, $selfFile);
      if (!file_exists($selfFile)) {
        $isDeleted = true;
        $results[] = '<span class="text-slate-400">' . t('msg_deleted') . '</span>';
      }
    }

    // Fallback to rename.
    if (!$isDeleted) {
      $renamed = $selfFile . '.bak';
      if (@rename($selfFile, $renamed)) {
        $isDeleted = true;
        @chmod($renamed, 0444);
      }
    }

    if (!$isDeleted) {
      register_shutdown_function(function () use ($selfFile) {
        if (file_exists($selfFile))
          @unlink($selfFile);
      });
      $results[] = '<span class="font-bold text-rose-600">' . t('msg_delete_fail') . '</span>';
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
  <title>
    <?= h(t('title')) ?> |
    <?= h(CMS_NAME) ?>
  </title>
  <script src="https://cdn.tailwindcss.com">
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }

    .shadow-soft {
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    }
  </style>
</head>

<body class="flex justify-center items-center p-4 min-h-full">
  <div class="bg-white shadow-soft border border-slate-200 rounded-[0.4rem] w-full max-w-lg overflow-hidden">

    <div class="px-8 pt-8 pb-6 border-slate-100 border-b text-center">
      <div
        class="flex justify-center items-center bg-[#eff6ff] mx-auto mb-5 rounded-full ring-1 ring-[#eff6ff] w-14 h-14 text-[#0f62fe]">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
          class="w-7 h-7">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z" />
        </svg>
      </div>
      <h1 class="font-bold text-slate-900 text-xl tracking-tight">
        <?= h(t('title')) ?>
      </h1>
      <p class="mt-2 font-medium text-slate-500 text-sm">
        <?= h($step === 'confirm' ? t('step_1') : t('step_2')) ?>
      </p>
    </div>

    <div class="p-8">
      <?php if ($error): ?>
        <div class="flex items-start gap-3 bg-[#fef2f2] mb-6 p-4 border border-red-100 rounded-[0.4rem] font-medium text-[#991b1b] text-sm">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
            class="mt-0.5 w-5 h-5 shrink-0">
            <path stroke-linecap="round" stroke-linejoin="round"
              d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
          </svg>
          <?= h($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($step === 'confirm'): ?>
        <div class="space-y-6">
          <p class="text-slate-600 text-sm leading-relaxed">
            <?= h(t('intro')) ?>
          </p>

          <div class="bg-[#eff6ff] p-4 border border-blue-100 rounded-[0.4rem]">
            <div class="mb-3">
              <p class="mb-1 font-bold text-blue-500 text-xs uppercase tracking-wider">
                <?= h(t('lbl_target_url')) ?>
              </p>
              <p class="font-mono font-bold text-blue-900 text-sm break-all">
                <?= h(BASE_URL) ?>
              </p>
            </div>
            <div>
              <p class="mb-1 font-bold text-blue-500 text-xs uppercase tracking-wider">
                <?= h(t('lbl_target_db')) ?>
              </p>
              <p class="opacity-80 font-mono text-[10px] text-blue-800 break-all leading-tight">
                <?= h(DB_FILE) ?>
              </p>
            </div>
          </div>

          <?php if (stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'nginx') !== false): ?>
            <div class="bg-yellow-50 p-4 border border-yellow-200 rounded-[0.4rem] text-yellow-800 text-sm font-bold">
              <?= h(t('nginx_warn')) ?>
            </div>
          <?php endif; ?>

          <div>
            <h3 class="mb-3 ml-1 font-bold text-slate-400 text-xs uppercase tracking-wider">
              <?= h(t('action_list')) ?>
            </h3>
            <div class="bg-slate-50 border border-slate-200 rounded-[0.4rem] divide-y divide-slate-200">
              <div class="flex items-center gap-3 p-3.5 text-slate-700 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                  stroke="currentColor" class="w-5 h-5 text-[#0f62fe] shrink-0">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <?= h(t('act_1')) ?>
              </div>
              <div class="flex items-center gap-3 p-3.5 text-slate-700 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                  stroke="currentColor" class="w-5 h-5 text-[#0f62fe] shrink-0">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <?= h(t('act_2')) ?>
              </div>
              <div class="flex items-center gap-3 p-3.5 text-slate-700 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                  stroke="currentColor" class="w-5 h-5 text-[#0f62fe] shrink-0">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <?= h(t('act_3')) ?>
              </div>
              <div class="flex items-center gap-3 p-3.5 text-slate-700 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                  stroke="currentColor" class="w-5 h-5 text-[#0f62fe] shrink-0">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                <?= h(t('act_4')) ?>
              </div>
            </div>
          </div>

          <div class="flex items-start gap-3 bg-[#fffbeb] p-4 border border-amber-100 rounded-[0.4rem]">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
              stroke="currentColor" class="flex-shrink-0 mt-0.5 w-5 h-5 text-[#f59e0b]">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <p class="text-[#92400e] text-sm leading-snug">
              <?= t('warning') ?>
            </p>
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
        <div class="space-y-6">
          <div class="bg-[#ecfdf5] p-4 border border-emerald-100 rounded-[0.4rem] text-center">
            <p class="font-bold text-[#065f46] text-lg">
              <?= h(t('msg_success')) ?>
            </p>
          </div>

          <div class="space-y-3">
            <?php foreach ($results as $res): ?>
              <div
                class="flex items-center gap-3 bg-white shadow-sm p-2 border border-slate-100 rounded-[0.4rem] text-slate-600 text-sm">
                <div class="flex justify-center items-center bg-emerald-100 rounded-full w-6 h-6 shrink-0">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                    stroke="currentColor" class="w-3.5 h-3.5 text-[#10b981]">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                  </svg>
                </div>
                <?= $res ?>
              </div>
            <?php
            endforeach; ?>
          </div>

          <div class="pt-6 border-slate-100 border-t">
            <div class="flex items-center gap-2 mb-2">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-4 h-4 text-slate-400">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
              </svg>
              <h3 class="font-bold text-slate-700 text-xs uppercase tracking-wide">
                <?= h(t('trouble_title')) ?>
              </h3>
            </div>
            <p class="bg-slate-50 p-3 border border-slate-100 rounded-[0.4rem] text-slate-500 text-sm leading-relaxed">
              <?= t('trouble_desc') ?>
            </p>
          </div>

          <a href="<?= h(BASE_URL) ?>/admin/login.php"
            class="block bg-[#0f62fe] hover:bg-[#0353e9] shadow-sm hover:shadow px-4 py-3.5 rounded-[0.4rem] w-full font-bold text-white text-center transition">
            <?= h(t('link_login')) ?>
          </a>
        </div>
      <?php
      endif; ?>
    </div>
  </div>
</body>

</html>
