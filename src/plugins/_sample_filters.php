<?php

/**
 * _sample_filters.php
 *
 * [English]
 * Demonstrates how to use Filter Hooks (e.g., auto-linking keywords, custom shortcodes).
 * To enable this, rename this file to "sample_filters.php" (remove the underscore).
 *
 * [Japanese]
 * フィルターフックを利用して、表示直前の文字列を操作（キーワードの自動リンク化やショートコード展開など）するサンプルです。
 * 有効にするには、ファイル名の先頭の "_" (アンダースコア) を削除してください。
 */
if (!defined('GRINDS_APP')) exit;

// Filter the post content just before it's displayed
// 記事の本文が表示される直前にフィルターをかけます
add_filter('grinds_the_content', function ($content) {

    // 1. Auto-link specific keywords
    // 1. 特定のキーワードを自動でリンクに変換する
    $keywords = [
        'GrindSite' => 'https://example.com/grindsite',
        'PHP' => 'https://www.php.net/'
    ];

    foreach ($keywords as $word => $url) {
        // Simple replacement (Note: This might replace inside HTML tags if not careful.
        // For advanced usage, use regular expressions or DOMDocument).
        // 単純な置換（注意: HTMLタグの中身も置換される可能性があります。より高度な場合は正規表現等を使用してください）。
        $link = "<a href='{$url}' target='_blank' rel='noopener noreferrer' style='color:#3b82f6; text-decoration:underline;'>{$word}</a>";
        $content = str_replace($word, $link, $content);
    }

    // 2. Expand custom shortcodes (e.g., [youtube id="VIDEO_ID"])
    // 2. 独自のショートコードを展開する（例: [youtube id="VIDEO_ID"]）
    // Pattern explanation: Matches [youtube id="..."] and captures the ID
    // パターンの説明: [youtube id="..."] にマッチし、ID部分をキャプチャします
    $pattern = '/\[youtube id="([^"]+)"\]/';

    // Replacement HTML for YouTube iframe
    // YouTubeのiframe用の置換HTML
    $replacement = '<div style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; margin:1.5rem 0;">
                        <iframe src="https://www.youtube.com/embed/$1" style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;" allowfullscreen></iframe>
                    </div>';

    $content = preg_replace($pattern, $replacement, $content);

    return $content; // Filters MUST return the modified data / フィルターは必ず変更後のデータを返す必要があります
});
