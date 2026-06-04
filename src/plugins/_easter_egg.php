<?php

/**
 * Plugin Name: Easter Egg
 *
 * Author: Grind Works Inc.
 * Version: 1.0.0
 *
 * [English]
 * Displays an engineer-focused Easter egg (console log) in the admin footer.
 * This plugin utilizes the GrindSite Hook System (`grinds_footer`).
 * To enable this, rename this file to "easter_egg.php" (remove the underscore).
 *
 * [Japanese]
 * 管理画面のフッターにエンジニア向けのイースターエッグ（コンソールログ）を表示します。
 * このプラグインは GrindSite のフックシステム（`grinds_footer`）を利用しています。
 * 有効にするには、ファイル名の先頭の "_" を削除して "easter_egg.php" にしてください。
 */
if (!defined('GRINDS_APP')) exit;

// Hook into the admin footer using the GrindSite Hook System
add_action('grinds_footer', function () {
    // Return early if not in admin area to prevent showing on frontend
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($requestUri, '/admin/') === false) {
        return;
    }

    // Display only to logged-in admins (or authorized users)
    // ログイン中のユーザーにのみ表示
    $user = App::user();

    if ($user) {
        // Injected script here since it's loaded in the admin footer
        // 管理画面のフッターで読み込まれるため、ここにスクリプトを注入します
        echo <<<'HTML'
<script>
    (async function() {
        // Styling for Chrome DevTools etc.
        // Chromeのデベロッパーツールなどでスタイルを適用するための工夫
        const titleStyle = "color: #3b82f6; font-weight: bold; font-size: 13px; font-family: monospace;";
        const tagStyle   = "font-weight: bold; font-family: monospace;";
        const textStyle  = "color: #94a3b8; font-family: monospace; font-size: 12px;";

        // Helper for timing delays
        const sleep = (ms) => new Promise(resolve => setTimeout(resolve, ms));

        console.log("\n");
        console.log("%c✦ GRINDSITE CORE ONLINE", titleStyle);
        await sleep(400);
        console.log("%c[ SYSTEM ]%c All primary modules initialized successfully.", tagStyle + " color: #10b981;", textStyle);
        await sleep(400);
        console.log("%c[  AUTH  ]%c Administrator privileges verified.", tagStyle + " color: #eab308;", textStyle);
        await sleep(600);
        console.log("%c[  INFO  ]%c Awaiting further instructions...", tagStyle + " color: #64748b;", textStyle);
        console.log("\n");
    })();
</script>
HTML;
    }
});
