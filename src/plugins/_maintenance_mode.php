<?php

/**
 * _maintenance_mode.php
 *
 * [English]
 * Displays a "Maintenance Mode" screen (503 status) to non-logged-in users.
 * To enable this, rename this file to "maintenance_mode.php" (remove the underscore).
 *
 * [Japanese]
 * ログインしていない一般ユーザーに対して「メンテナンス中」の画面（503ステータス）を表示します。
 * 有効にするには、ファイル名の先頭の "_" (アンダースコア) を削除してください。
 */
if (!defined('GRINDS_APP')) exit;

add_action('grinds_init', function () {
    // 1. Check if the user is logged in
    // 1. ユーザーがログインしているか確認
    // Uses the App class if available (as seen in easter_egg.php)
    // easter_egg.php で使用されている App クラスを利用します
    $isLoggedIn = class_exists('App') && App::user() !== null;

    // Get the current request URI
    // 現在のリクエストURIを取得
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    // 2. Exclude admin areas and login pages from maintenance mode
    // 2. 管理画面やログインページはメンテナンスモードから除外する
    $isAdminArea = str_contains($requestUri, '/admin/') || str_contains($requestUri, 'login');

    // 3. If not logged in and not in the admin area, show maintenance screen
    // 3. 未ログインかつ管理画面へのアクセスでなければ、メンテナンス画面を表示
    if (!$isLoggedIn && !$isAdminArea) {
        // Set 503 Service Unavailable status (good for SEO)
        // 503 Service Unavailable ステータスを設定（SEO的にも推奨）
        http_response_code(503);
        header('Retry-After: 3600'); // Tell search engines to check back in an hour / 1時間後に再確認するよう検索エンジンに伝える
        header('Content-Type: text/html; charset=utf-8');

        // Output the maintenance screen HTML and stop execution
        // メンテナンス画面のHTMLを出力して処理を停止
        die("
            <div style='font-family:sans-serif; padding:50px; text-align:center; color:#333; max-width:600px; margin:0 auto; margin-top:10vh;'>
                <h1 style='color:#475569; font-size:2rem; margin-bottom:1rem;'>We'll be right back!</h1>
                <p style='line-height:1.6; color:#64748b;'>
                    Currently undergoing scheduled maintenance.<br>
                    Please check back soon.
                </p>
                <p style='margin-top:2rem; font-size:0.875rem; color:#94a3b8;'>ただいまメンテナンス中です。しばらく経ってから再度アクセスしてください。</p>
            </div>
        ");
    }
});
