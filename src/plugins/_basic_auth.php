<?php

/**
 * _basic_auth.php
 *
 * [English]
 * Adds Basic Authentication to the entire site or specific pages.
 * To enable this, rename this file to "basic_auth.php" (remove the underscore)
 * and set up your users in the $valid_users array.
 *
 * [Japanese]
 * サイト全体、または特定のページにBasic認証（簡易的なID/パスワード制限）を追加します。
 * 有効にするには、ファイル名の先頭の "_" (アンダースコア) を削除し、
 * $valid_users にユーザー名とパスワードを設定してください。
 */
if (!defined('GRINDS_APP')) exit;

add_action('grinds_init', function () {
    // 1. Define valid users (Username => Password)
    // 1. 許可するユーザーを定義します（ユーザー名 => パスワード）
    $valid_users = [
        'admin' => 'password123',
        'guest' => 'guest2024',
    ];

    // 2. Set the target scope
    // 2. 認証をかける範囲を設定
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';

    // Example: Apply only to specific pages (e.g., /secret-page/)
    // 例: 特定のページにのみ適用する場合
    // $isTargetPage = str_contains($requestUri, '/secret-page/');

    // By default, apply to the entire site (excluding the admin area)
    // デフォルトでは、サイト全体（管理画面を除く）に適用します
    $isTargetPage = !str_contains($requestUri, '/admin/');

    if ($isTargetPage) {
        $user = $_SERVER['PHP_AUTH_USER'] ?? '';
        $pass = $_SERVER['PHP_AUTH_PW'] ?? '';

        // 3. Verify credentials
        // 3. 認証情報の確認
        if (empty($user) || empty($pass) || !isset($valid_users[$user]) || $valid_users[$user] !== $pass) {
            // Send authentication headers and stop execution
            // 認証ヘッダーを送信して処理を停止
            header('WWW-Authenticate: Basic realm="Restricted Area"');
            header('HTTP/1.0 401 Unauthorized');
            die('Unauthorized / 認証に失敗しました。正しいIDとパスワードを入力してください。');
        }
    }
});
