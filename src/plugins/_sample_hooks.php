<?php

/**
 * _sample_hooks.php
 *
 * [English]
 * Sample plugin demonstrating the Hook System (Actions & Filters) of GrindSite.
 * To enable this, rename this file to "sample_hooks.php" (remove the underscore).
 *
 * [Japanese]
 * GrindSiteのフックシステム（アクション＆フィルター）を実演するサンプルプラグインです。
 * 有効にするには、ファイル名の先頭の "_" (アンダースコア) を削除してください。
 */
if (!defined('GRINDS_APP')) exit;

// =========================================================================
// 1. The Moment of Entry (入る前) -> 'grinds_init'
// =========================================================================
// Executed immediately after system startup, before any content output.
// Useful for access control, redirects, or setting custom headers.
//
// システムが起動し、設定が読み込まれた直後、画面が表示される前に実行されます。
// 用途: アクセス制限、リダイレクト、カスタムヘッダーの送信など。

add_action('grinds_init', function () {
    // Example: Add a custom signature to the response header.
    // 例: レスポンスヘッダーにオリジナルの署名を追加
    header('X-Powered-By-Grinds: Hooks Enabled');

    // Example: Block specific IP addresses (Commented out)
    // 例: 特定のIP以外をブロックする（コメントアウト中）
    /*
    $allowed_ip = '123.456.789.000';
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($user_ip !== $allowed_ip) {
        http_response_code(403);
        die('Access Denied via Plugin');
    }
    */
});

// =========================================================================
// 2. The Moment of Change (変わる時) -> 'grinds_post_saved'
// =========================================================================
// Executed immediately after a post is created or updated in the database.
// Useful for logging, Slack/Discord notifications, or external API webhooks.
//
// 記事が保存（作成・更新）され、データベースが確定した瞬間に実行されます。
// 用途: ログ記録、Slack/Discord通知、外部APIへのWebhook送信など。

add_action('grinds_post_saved', function ($postId, $data) {
    // Example: Write a simple log to data/logs/hook_sample.log
    // 例: 簡易ログを data/logs/hook_sample.log に書き出す

    $logDir = ROOT_PATH . '/data/logs';

    // Ensure directory exists
    if (function_exists('grinds_secure_dir')) {
        grinds_secure_dir($logDir);
    }

    $logFile = $logDir . '/hook_sample.log';
    $timestamp = date('Y-m-d H:i:s');
    $title = $data['title'] ?? 'Unknown';

    $message = "[{$timestamp}] Post Saved! ID: {$postId}, Title: {$title}" . PHP_EOL;

    // Save log (Suppressed errors with @)
    // エラー抑制(@)付きで追記保存
    @file_put_contents($logFile, $message, FILE_APPEND);
});

// Other hooks available: 'grinds_post_deleted', 'grinds_trash_emptied'
// 他にも: 'grinds_post_deleted'（削除時）, 'grinds_trash_emptied'（ゴミ箱を空にした時）などがあります。

// =========================================================================
// 3. The Moment of Exit (出る前) -> 'grinds_head', 'grinds_footer'
// =========================================================================
// Insert tags or scripts just before HTML output.
// Useful for Google Analytics, custom CSS/JS, social buttons, or copyright notices.
//
// HTMLとして出力される直前に、追加のタグやスクリプトを挿入します。
// 用途: Google Analytics、カスタムCSS/JS、ソーシャルボタン、コピーライトなど。

// Add to <head> tag
// <head>タグ内に追加
add_action('grinds_head', function () {
    echo "\n<!-- Hook Sample: Custom Meta Tag -->\n";
    echo '<meta name="grinds-plugin" content="active">' . "\n";

    // Example: Add custom CSS
    echo '<style>.grinds-hook-badge { position: fixed; bottom: 10px; right: 10px; z-index: 9999; font-family: sans-serif; }</style>' . "\n";
});

// Add before closing </body> tag
// </body>タグの直前に追加
add_action('grinds_footer', function () {
    // Example: Display a small badge at the bottom right
    // 例: 画面右下に小さなバッジを表示
    echo '<div class="grinds-hook-badge" style="background:#000; color:#fff; padding:4px 8px; font-size:10px; border-radius:4px; opacity:0.8;">';
    echo 'Hook Active';
    echo '</div>';

    // Example: Add custom JS
    // echo '<script>console.log("GrindSite Plugin Loaded");</script>';
});

// =========================================================================
// 4. The Moment of Modification (書き換える時) -> Filter Hooks
// =========================================================================
// Filter hooks allow you to modify data before it is rendered or saved.
// Unlike actions, filters MUST return a value.
//
// フィルターフックを使用すると、データが表示または保存される前に変更できます。
// アクションとは異なり、フィルターは必ず値を返す必要があります。

// Example: Modify Post Title
// 例: 記事タイトルの変更
// add_filter('grinds_the_title', function ($title) {
//     return '★ ' . $title;
// });

// Example: Append text to Content
// 例: 記事本文の末尾にテキストを追加
// add_filter('grinds_the_content', function ($content) {
//     return $content . '<p>Thanks for reading!</p>';
// });
