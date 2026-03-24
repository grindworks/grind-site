<?php

/**
 * _custom_helpers.php
 *
 * [English]
 * Useful Custom Functions Sample.
 * Rename this file to "custom_helpers.php" (remove the underscore) to enable it.
 * You can call these functions from your theme files (e.g. single.php).
 *
 * [Japanese]
 * 便利なカスタム関数集のサンプルです。
 * このファイル名の先頭の "_"（アンダースコア）を削除して "custom_helpers.php" にすると有効になります。
 * テーマファイル（single.phpなど）から、ここで定義した関数を自由に呼び出せます。
 */
if (!defined('GRINDS_APP')) exit;

/**
 * 1. Debug Helper (Dump and Die)
 * Displays variable contents formatted and stops execution.
 *
 * [JP] 変数の中身をきれいに表示して処理を止めます。開発時に便利です。
 * Usage: dd($pageData);
 */
if (!function_exists('dd')) {
    function dd($var)
    {
        echo '<div style="background:#1e293b; color:#e2e8f0; padding:20px; font-family:monospace; z-index:99999; position:relative; text-align:left; font-size:14px; line-height:1.5;">';
        echo '<pre>';
        var_dump($var);
        echo '</pre>';
        echo '</div>';
        exit;
    }
}

/**
 * 2. Display Reading Time
 * Calculates and displays estimated reading time based on content length.
 * Automatically switches language based on site settings.
 *
 * [JP] 記事の「読了時間」を表示する関数です。
 * 本文の文字数から計算します。サイトの言語設定に応じて表示を切り替えます。
 *
 * Usage: <?php the_reading_time($pageData['post']['content']); ?>
 */
if (!function_exists('the_reading_time')) {
    function the_reading_time($content)
    {
        $text_content = grinds_extract_text_from_content($content);
        $length = mb_strlen($text_content, 'UTF-8');

        // EN: 200 words, JA: 600 chars
        $per_minute = 600;
        $minutes = ceil($length / $per_minute);

        if ($minutes < 1) $minutes = 1;

        $lang = get_option('site_lang', 'en');

        echo '<span class="text-xs text-gray-500 flex items-center gap-1">';
        echo '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';

        if ($lang === 'ja') {
            echo "この記事は 約{$minutes}分 で読めます";
        } else {
            echo "Read time: approx {$minutes} min";
        }

        echo '</span>';
    }
}

/**
 * 3. Check if "New" Post
 * Returns true if the post was published within the specified days.
 *
 * [JP] 記事が「新着」かどうか判定する関数です。
 * 公開日から指定した日数以内であれば true を返します。
 *
 * Usage:
 * <?php if (is_new_post($post['published_at'])): ?>
 *   <span class="text-red-500 font-bold">NEW!</span>
 * <?php endif; ?>
 */
if (!function_exists('is_new_post')) {
    function is_new_post($dateString, $days = 7)
    {
        if (empty($dateString)) return false;

        $postTime = strtotime($dateString);
        $limitTime = time() - ($days * 24 * 60 * 60);

        return $postTime >= $limitTime;
    }
}
