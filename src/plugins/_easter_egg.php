<?php

/**
 * _easter_egg.php
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
    // Display only to logged-in admins (or authorized users)
    // ログイン中のユーザーにのみ表示
    $user = App::user();

    if ($user) {
        // Injected script here since it's loaded in the admin footer
        // 管理画面のフッターで読み込まれるため、ここにスクリプトを注入します
        echo <<<HTML
<script>
    (function() {
        // Styling for Chrome DevTools etc.
        // Chromeのデベロッパーツールなどでスタイルを適用するための工夫
        const style1 = "color: #10b981; font-size: 14px; font-weight: bold;";
        const style2 = "color: #94a3b8; font-size: 12px;";

        console.log("%cGrindSite core loaded. All primary systems online. Awaiting instructions.", style1);
        console.log("%cThank you for using our system. Have a great day!", style2);
    })();
</script>
HTML;
    }
});
