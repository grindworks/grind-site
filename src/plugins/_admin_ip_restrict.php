<?php

/**
 * _admin_ip_restrict.php
 *
 * [English]
 * Restricts access to the admin area (/admin/) to specific IP addresses (e.g., office or home).
 * To enable this, rename this file to "admin_ip_restrict.php" (remove the underscore)
 * and add allowed IPs to the $allowed_ips array.
 *
 * [Japanese]
 * 管理画面（/admin/）へのアクセスを、特定のIPアドレス（オフィスや自宅など）からのみに制限します。
 * 有効にするには、ファイル名の先頭の "_" (アンダースコア) を削除し、
 * $allowed_ips に許可するIPアドレスを追加してください。
 */
if (!defined('GRINDS_APP')) exit;

add_action('grinds_init', function () {
    // 1. List of allowed IP addresses
    // 1. 許可するIPアドレスのリストを記述してください
    $allowed_ips = [
        '127.0.0.1',   // Local environment IPv4 / ローカル環境IPv4
        '::1',         // Local environment IPv6 / ローカル環境IPv6
        // '192.168.1.10', // Example: Office fixed IP / 例: オフィスの固定IP
        // '203.0.113.50'  // Example: Home fixed IP / 例: 自宅の固定IP
    ];

    // Get the current request URI and script name
    // 現在のリクエストURIを取得
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

    // Check if the URL or script path contains '/admin/'
    // URLやスクリプトパスに '/admin/' が含まれているか判定
    $isAdminArea = str_contains($requestUri, '/admin/') || str_contains($scriptName, '/admin/');

    // If you want to exclude API endpoints (e.g., AJAX requests), add the following condition:
    // APIエンドポイントへのアクセス（AJAX通信など）は除外したい場合は以下の条件を追加します
    // $isApi = str_contains($requestUri, '/api/') || str_contains($scriptName, '/api/');

    if ($isAdminArea) {
        // Use GrindSite core's get_client_ip() function to get accurate IP even behind proxies
        // GrindSiteコアの get_client_ip() 関数を利用して、プロキシ越しのIPも正確に取得
        $client_ip = function_exists('get_client_ip') ? get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '');

        // Block access if the IP is not in the allowed list
        // IPが許可リストに存在しない場合はアクセスを遮断
        if (!in_array($client_ip, $allowed_ips, true)) {
            http_response_code(403);
            header('Content-Type: text/html; charset=utf-8');
            die("<div style='font-family:sans-serif; padding:50px; text-align:center; color:#333;'>
                    <h1 style='color:#e11d48;'>403 Access Denied</h1>
                    <p>Your IP address (<strong>" . htmlspecialchars($client_ip) . "</strong>) is not allowed to access the admin area.</p>
                 </div>");
        }
    }
});
