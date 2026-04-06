<?php

/**
 * _rate_limiter.php
 *
 * [English]
 * Lightweight Rate Limiting Plugin for API and Contact Forms.
 * Uses file-based storage in data/tmp/ to avoid SQLite database locks.
 * Protects against basic DoS attacks and form spam.
 *
 * [Japanese]
 * APIやお問い合わせフォーム向けの軽量レートリミット（スロットリング）プラグインです。
 * SQLiteのロックエラーを回避するため、data/tmp/ディレクトリへのファイル書き込みを使用します。
 * 簡易的なDoS攻撃やフォームのスパム送信を防御します。
 */
if (!defined('GRINDS_APP')) exit;

add_action('grinds_init', function () {
    // Configuration / レートリミットの設定
    $config = [
        // API access limits (e.g., max 60 requests per minute)
        // APIへのアクセス制限 (例: 1分間に60回まで)
        'api' => ['limit' => 60, 'window' => 60],
        // Contact form POST limits (e.g., max 3 requests per 5 minutes)
        // コンタクトフォーム(POST)の制限 (例: 5分間に3回まで)
        'contact' => ['limit' => 3, 'window' => 300],
    ];

    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    $ruleType = null;

    // Determine if it's an API access
    // APIへのアクセスか判定
    if (str_contains($requestUri, '/api/')) {
        $ruleType = 'api';
    }
    // Determine if it's a contact form submission (POST request containing 'contact')
    // コンタクトフォームの送信か判定（POSTリクエストかつ、contactという文字が含まれるパス）
    elseif ($method === 'POST' && str_contains($requestUri, '/contact')) {
        $ruleType = 'contact';
    }

    if ($ruleType && isset($config[$ruleType])) {
        $limit = $config[$ruleType]['limit'];
        $window = $config[$ruleType]['window'];

        // Get the client's IP address (using GrindSite core function)
        // クライアントのIPアドレスを取得（GrindSiteコア関数を使用）
        $ip = function_exists('get_client_ip') ? get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        // Hash IP and rule type for the filename to reduce collisions
        // IPとルールの種類でハッシュ化し、ファイル名にする（ファイル競合を減らす）
        $hash = md5($ip . '_' . $ruleType);

        $tmpDir = ROOT_PATH . '/data/tmp/ratelimit';

        // Create and secure the directory (use core function if available)
        // ディレクトリの作成と安全確保（可能であればコア関数を使用）
        if (function_exists('grinds_secure_dir')) {
            grinds_secure_dir($tmpDir);
        } elseif (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
            @file_put_contents($tmpDir . '/.htaccess', "Require all denied\n");
        }

        $limitFile = $tmpDir . '/' . $hash . '.json';
        $now = time();
        $attempts = [];

        // Exclusive file lock control (prevent race conditions)
        // ファイルの排他ロック制御（競合状態を防ぐ）
        $fp = @fopen($limitFile, 'c+');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                // Use stream_get_contents to avoid filesize() cache issues
                // filesize() のキャッシュ問題を回避するため stream_get_contents を使用
                $content = stream_get_contents($fp);
                if ($content) {
                    $attempts = json_decode($content, true) ?: [];
                }

                // Remove old records that passed the valid window
                // 有効期間（ウィンドウ）を過ぎた古い記録を削除
                $validAttempts = array_filter($attempts, function ($timestamp) use ($now, $window) {
                    return ($now - $timestamp) < $window;
                });

                // Check if access count reached the limit
                // アクセス回数が制限に達しているかチェック
                if (count($validAttempts) >= $limit) {
                    $retryAfter = $window - ($now - min($validAttempts));

                    http_response_code(429); // 429 Too Many Requests
                    header('Retry-After: ' . $retryAfter);
                    header('Content-Type: application/json; charset=utf-8');

                    $msg = (function_exists('get_option') && get_option('site_lang') === 'ja')
                        ? "アクセスが制限されました。しばらく待ってから再度お試しください。"
                        : "Too Many Requests. Please try again later.";

                    // Release lock and exit
                    // ロックを解除して終了
                    flock($fp, LOCK_UN);
                    fclose($fp);

                    die(json_encode(['success' => false, 'error' => $msg]));
                }

                // Record the current access and save
                // 今回のアクセスを記録して保存
                $validAttempts[] = $now;

                // Clear file content and write
                // ファイル内容をクリアして書き込み
                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, json_encode(array_values($validAttempts)));

                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
    }

    // Garbage Collection: Run at 1% probability to auto-delete old rate limit files
    // ガベージコレクション: 1%の確率で実行し、古いレートリミットファイルを自動削除する
    if (random_int(1, 100) === 1) {
        // Register shutdown function (background processing) to prevent response delay
        // レスポンス遅延を防ぐため、シャットダウン関数（バックグラウンド処理）として登録
        register_shutdown_function(function () {
            $tmpDir = ROOT_PATH . '/data/tmp/ratelimit';
            if (is_dir($tmpDir)) {
                $now = time();
                foreach (glob($tmpDir . '/*.json') as $file) {
                    // Clear stat cache of filemtime while checking
                    // filemtime の stat キャッシュをクリアしながらチェック
                    clearstatcache(true, $file);
                    if (is_file($file) && filemtime($file) < ($now - 3600)) { // Delete files older than 1 hour / 1時間以上古いファイルは削除
                        @unlink($file);
                    }
                }
            }
        });
    }
});
