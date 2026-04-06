<?php

/**
 * _slack_notifier.php
 *
 * [English]
 * Sends a notification to a specified Slack or Discord Webhook URL when a post is saved as "published".
 * To enable this, rename this file to "slack_notifier.php" (remove the underscore)
 * and set your $webhook_url.
 *
 * [Japanese]
 * 記事が「公開 (published)」として保存された際に、指定したSlackやDiscordのWebhook URLへ通知を送ります。
 * 有効にするには、ファイル名の先頭の "_" (アンダースコア) を削除し、
 * $webhook_url を設定してください。
 */
if (!defined('GRINDS_APP')) exit;

add_action('grinds_post_saved', function ($postId, $postData) {
    // 1. Set your Slack or Discord Webhook URL
    // 1. Slack または Discord の Webhook URL を設定してください
    $webhook_url = 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL';

    // Do nothing if it's the default state (URL not set)
    // 初期状態（URLが未設定）の場合は何もしない
    if (str_contains($webhook_url, 'YOUR/WEBHOOK/URL')) {
        return;
    }

    // 2. Notify only when the status is "published"
    // 2. ステータスが「公開 (published)」の場合のみ通知する
    if (($postData['status'] ?? '') === 'published') {

        // Get site name and post information
        // サイト名や記事情報を取得
        $siteName = function_exists('get_option') ? get_option('site_name', 'GrindSite') : 'GrindSite';
        $title = $postData['title'] ?? 'No Title';
        $slug = $postData['slug'] ?? '';

        // Generate the post URL
        // 記事のURLを生成
        $postUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' . ltrim($slug, '/') : $slug;

        // Build the message to send
        // 送信するメッセージの組み立て
        $message = "📢 *【{$siteName}】New post published / 新しい記事が公開・更新されました!*\n\n";
        $message .= "📝 *Title / タイトル:* {$title}\n";
        $message .= "🔗 *URL:* {$postUrl}\n";

        $payload = json_encode(['text' => $message]);

        // Send using cURL (short timeout to avoid blocking the save process)
        // cURLを使用して送信（タイムアウトを短くし、保存処理をブロックしないようにする）
        // さらに register_shutdown_function を使うことで、ユーザーに画面を返した後のバックグラウンドで送信させ、体感の遅延をゼロにします。
        register_shutdown_function(function () use ($webhook_url, $payload) {
            if (function_exists('curl_init')) {
                $ch = curl_init($webhook_url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                // バックグラウンド処理のため、少し長めのタイムアウト（5秒）でもUXに影響しません
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_exec($ch);
                curl_close($ch);
            }
        });
    }
});
