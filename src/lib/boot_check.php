<?php

/**
 * Verify permissions and secure data files.
 */

// Check installation
$rootPath = dirname(__DIR__);
$configFile = $rootPath . '/config.php';

if (!file_exists($configFile)) {
    if (file_exists($rootPath . '/install.php')) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Determine base path
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

        if (basename($scriptDir) === 'admin') {
            $scriptDir = dirname($scriptDir);
        }

        $installUrlPath = rtrim($scriptDir, '/');

        header("Location: $scheme://$host$installUrlPath/install.php");
        exit;
    } else {
        // Handle missing config
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        if (!function_exists('grinds_detect_language')) {
            require_once __DIR__ . '/functions/system.php';
        }
        $lang = function_exists('grinds_detect_language') ? grinds_detect_language() : 'en';
        $title = ($lang === 'ja') ? 'システムエラー' : 'System Error';
        $msg = ($lang === 'ja') ? '設定ファイル (config.php) が見つかりません。' : 'Configuration file (config.php) is missing.';
        $sub = ($lang === 'ja') ? '再インストールが必要な場合は、install.php を配置してください。' : 'Please upload install.php to reinstall.';

        echo <<<HTML
<!DOCTYPE html>
<html lang="{$lang}">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>{$title}</title></head>
<body style="font-family:sans-serif;padding:50px;text-align:center;background:#f9fafb;color:#333;">
<h1 style="color:#e11d48;">{$title}</h1><p>{$msg}</p><p>{$sub}</p>
</body></html>
HTML;
        exit;
    }
}

if (!defined('GRINDS_APP')) define('GRINDS_APP', true);

// Load system check logic
require_once __DIR__ . '/functions/system.php';

$lang = grinds_detect_language();

require_once __DIR__ . '/i18n.php';
I18n::init($lang);

$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Detect server software
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';
$isNginx = str_contains(strtolower($serverSoftware), 'nginx');

// Calculate relative path
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDir = str_replace('\\', '/', dirname($scriptName));
if (basename($scriptDir) === 'admin') {
    $scriptDir = dirname($scriptDir);
}
$relPath = ($scriptDir === '/' || $scriptDir === '.') ? '/' : rtrim($scriptDir, '/');

// Load Nginx helper
$nginxHelperPath = __DIR__ . '/nginx_helper.php';
$nginxRules = '';
if ($isNginx && file_exists($nginxHelperPath)) {
    require_once $nginxHelperPath;
    $nginxRules = grinds_get_nginx_uploads_rules($relPath) . "\n" .
        grinds_get_nginx_plugins_rules($relPath) . "\n" .
        grinds_get_nginx_security_rules($relPath);
    $nginxRules = nl2br(htmlspecialchars($nginxRules));
}


$trans = [
    'en' => [
        'title' => 'Startup Error',
        'status' => 'PERMISSION_ERROR',
        'desc'  => 'Required file permissions are missing. Please resolve the following issues.',
        'type_missing' => 'Directory Missing',
        'type_perm'    => 'Permission Denied',
        'msg_missing'  => 'Directory <strong>%s</strong> could not be created.',
        'msg_perm'     => 'Directory <strong>%s</strong> is not writable.',
        'msg_file_perm' => 'File <strong>%s</strong> is not writable.',
        'advice_mkdir' => 'Please create this directory manually and set permissions to <strong>755</strong>, <strong>775</strong>, or <strong>777</strong>.',
        'advice_perm'  => 'Please change the permissions of this directory to <strong>755</strong>, <strong>775</strong>, or <strong>777</strong> via FTP.',
        'advice_file_perm' => 'Please change the permissions of this <strong>file itself</strong> to <strong>666</strong> or <strong>604</strong> via FTP. (File permission is required, not just directory.)',
        'type_module'  => 'Module Missing',
        'msg_module'   => 'Apache module <strong>%s</strong> is not enabled.',
        'advice_module' => 'Please enable <strong>%s</strong> to ensure security headers (CSP) are applied.',
        'type_ext'     => 'Extension Missing',
        'msg_ext'      => 'PHP extension <strong>%s</strong> is not loaded.',
        'advice_ext'   => 'Please enable <strong>%s</strong> in your php.ini.',
        'type_nginx'   => 'Nginx Configuration',
        'msg_nginx'    => 'Nginx detected. PHP execution in uploads directory must be blocked.',
        'advice_nginx' => 'Nginx ignores .htaccess files. You must add the following rules to your server configuration:<br><code class="block my-2 p-2 bg-red-100 rounded">' . $nginxRules . '</code>After configuring, create an empty file named <strong>.nginx_confirmed</strong> inside the <strong>data</strong> directory.',
        'btn_reload'   => 'Resolved, Reload Page',
        'footer'       => 'GrindSite Self-Diagnostic Tool',
    ],
    'ja' => [
        'title' => '起動エラー',
        'status' => 'PERMISSION_ERROR',
        'desc'  => 'システムの実行に必要なファイル権限が不足しています。以下の問題を解決してください。',
        'type_missing' => 'ディレクトリ未検出',
        'type_perm'    => '書き込み権限なし',
        'msg_missing'  => 'ディレクトリ <strong>%s</strong> が作成できません。',
        'msg_perm'     => 'ディレクトリ <strong>%s</strong> に書き込み権限がありません。',
        'msg_file_perm' => 'ファイル <strong>%s</strong> に書き込み権限がありません。',
        'advice_mkdir' => 'FTPソフト等でディレクトリを作成し、パーミッションを <strong>755</strong>, <strong>775</strong>, または <strong>777</strong> に設定してください。',
        'advice_perm'  => 'FTPソフト等でこのディレクトリのパーミッション（属性）を <strong>755</strong>, <strong>775</strong>, または <strong>777</strong> に変更してください。',
        'advice_file_perm' => 'FTPソフト等で、この<strong>ファイル自体</strong>のパーミッションを <strong>666</strong> または <strong>604</strong> に変更してください。（ディレクトリだけでなくファイルの権限も必要です）',
        'type_module'  => 'モジュール未検出',
        'msg_module'   => 'Apacheモジュール <strong>%s</strong> が有効になっていません。',
        'advice_module' => 'セキュリティヘッダー（CSP）を適用するため、<strong>%s</strong> を有効にしてください。',
        'type_ext'     => '拡張モジュール未検出',
        'msg_ext'      => 'PHP拡張 <strong>%s</strong> が有効になっていません。',
        'advice_ext'   => 'システムを正常に動作させるために、php.ini で <strong>%s</strong> を有効にしてください。',
        'type_nginx'   => 'Nginx 設定未確認',
        'msg_nginx'    => 'Nginx 環境が検知されました。アップロードディレクトリでのPHP実行禁止設定が必要です。',
        'advice_nginx' => 'Nginx は .htaccess を無視するため、以下の設定を追加してセキュリティを確保してください：<br><code class="block my-2 p-2 bg-red-100 rounded">' . $nginxRules . '</code>設定完了後、<strong>data</strong> ディレクトリ内に <strong>.nginx_confirmed</strong> という空ファイルを作成すると、この警告は解除されます。',
        'btn_reload'   => '解決したので再読み込みする',
        'footer'       => 'GrindSite 自己診断ツール',
    ]
];

$t = $trans[$lang];

$errors = [];

// Skip checks if installed
$isInstalled = (defined('INSTALLED') && INSTALLED);
if (!$isInstalled && file_exists($configFile)) {
    require_once $configFile;
    $isInstalled = (defined('INSTALLED') && INSTALLED);
}

if (!$isInstalled) {
    // Check permissions
    $paths = GrindsSystemCheck::getCriticalPaths();
    foreach ($paths as $path) {
        $res = GrindsSystemCheck::checkDirectory($path);
        $displayPath = str_replace('/../', '/', $path);

        if ($res['status'] === 'missing') {
            $errors[] = [
                'type' => $t['type_missing'],
                'msg' => sprintf($t['msg_missing'], $displayPath),
                'advice' => $t['advice_mkdir']
            ];
        } elseif ($res['status'] === 'unwritable') {
            $errors[] = [
                'type' => $t['type_perm'],
                'msg' => sprintf($t['msg_perm'], $displayPath),
                'advice' => $t['advice_perm']
            ];
        }
    }

    // Check DB permissions
    $dataPath = __DIR__ . '/../data';
    if (is_dir($dataPath)) {
        $dbFiles = glob($dataPath . '/*.db');
        if ($dbFiles) {
            foreach ($dbFiles as $dbFile) {
                if (!is_writable($dbFile)) {
                    $dbName = 'data/' . basename($dbFile);
                    $errors[] = [
                        'type' => $t['type_perm'],
                        'msg' => sprintf($t['msg_file_perm'], $dbName),
                        'advice' => $t['advice_file_perm']
                    ];
                }
            }
        }
    }

    // Check PHP version
    $minPhp = GrindsSystemCheck::getRequiredPhpVersion();
    if (version_compare(phpversion(), $minPhp, '<')) {
        $errors[] = [
            'type' => 'PHP Version Error',
            'msg' => "PHP <strong>{$minPhp}</strong> or higher is required. (Current: " . phpversion() . ")",
            'advice' => "Please upgrade your PHP version."
        ];
    }

    // Check PHP extensions
    $reqExts = GrindsSystemCheck::getRequiredExtensions();
    foreach ($reqExts as $ext) {
        if (!extension_loaded($ext)) {
            $errors[] = [
                'type' => $t['type_ext'],
                'msg' => sprintf($t['msg_ext'], $ext),
                'advice' => sprintf($t['advice_ext'], $ext)
            ];
        }
    }

    // Check Nginx confirmation
    if ($isNginx) {
        $nginxConfirmFile = __DIR__ . '/../data/.nginx_confirmed';
        if (!file_exists($nginxConfirmFile)) {
            $errors[] = [
                'type' => $t['type_nginx'],
                'msg' => $t['msg_nginx'],
                'advice' => $t['advice_nginx']
            ];
        }
    }
}

// Render error page
if (!empty($errors)) {
    // Prepare message HTML
    $msgHtml = '<p class="mb-3 font-bold">' . $t['desc'] . '</p>';
    $msgHtml .= '<ul class="space-y-2">';
    foreach ($errors as $err) {
        $msgHtml .= '<li class="bg-white/60 p-3 rounded border border-red-200 text-sm">';
        $msgHtml .= '<div class="font-bold text-red-800">[' . $err['type'] . '] ' . $err['msg'] . '</div>';
        $msgHtml .= '<div class="mt-1 text-red-600 text-xs flex items-start gap-1"><svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . grinds_asset_url('assets/img/sprite.svg') . '#outline-light-bulb"></use></svg><span>' . $err['advice'] . '</span></div>';
        $msgHtml .= '</li>';
    }
    $msgHtml .= '</ul>';

    grinds_render_error_page($t['title'], $msgHtml, $t['status'], 503, ['reload' => $t['btn_reload']]);
}
