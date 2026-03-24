<?php

/**
 * easter_egg.php
 *
 * [English]
 * Displays an engineer-focused Easter egg (console log) in the admin footer.
 * This plugin utilizes the GrindSite Hook System (`grinds_footer`).
 * To disable this, rename this file to "_easter_egg.php" (add an underscore).
 *
 * [Japanese]
 * 管理画面のフッターにエンジニア向けのイースターエッグ（コンソールログ）を表示します。
 * このプラグインは GrindSite のフックシステム（`grinds_footer`）を利用しています。
 * 無効化するにはファイル名の先頭に "_" を追加して "_easter_egg.php" にしてください。
 */
if (!defined('GRINDS_APP')) exit;

// Hook into the admin footer using the GrindSite Hook System
add_action('grinds_footer', function () {
    // Display only to logged-in admins (or authorized users)
    // ログイン中の管理者（または権限を持つユーザー）のみに表示
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

        console.log("%cGrindSite Admin: Keep the codebase clean.", style1);
        console.log("%cThank you for maintaining a bloat-free site.", style2);
    })();
</script>
HTML;
    }
});
