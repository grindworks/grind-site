<?php

/**
 * Amazon Affiliate Shortcode Plugin
 *
 * [English]
 * Converts [amazon id="ASIN" title="Product Name"] shortcodes into beautiful Amazon affiliate product cards.
 *
 * [Japanese]
 * 記事内の [amazon id="ASIN" title="商品名"] というショートコードを、
 * Amazonアフィリエイトのリッチな商品カードデザイン（HTML）に自動変換するプラグインです。
 */
if (!defined('GRINDS_APP')) exit;

// 1. Filter to expand shortcode during post content output (using grinds_the_content)
// 1. 記事コンテンツの出力時にショートコードを展開するフィルター (grinds_the_contentを使用)
add_filter('grinds_the_content', function ($content) {
    if (!str_contains($content, '[amazon ')) {
        return $content;
    }

    // Get the tracking ID configured in the admin area
    // 管理画面で設定されたトラッキングIDを取得
    $tracking_id = function_exists('get_option') ? get_option('amazon_tracking_id', '') : '';

    // Adjust regex to capture the title attribute as well
    // 正規表現を拡張して title 属性も取得できるように調整
    $pattern = '/\[amazon\s+id="([A-Z0-9]{10})"(?:\s+title="([^"]*)")?\]/i';

    return preg_replace_callback($pattern, function ($matches) use ($tracking_id) {
        $asin = $matches[1];
        // XSS Prevention: Safely escape user input (title) and DB value (tracking_id)
        // XSS対策: ユーザー入力（title）とDB値（tracking_id）を安全にエスケープ
        $title = !empty($matches[2]) ? htmlspecialchars($matches[2], ENT_QUOTES, 'UTF-8') : "Amazonで詳細を見る";
        $safe_tracking_id = htmlspecialchars($tracking_id, ENT_QUOTES, 'UTF-8');

        // Display a warning to the site admin instead of generating a dummy URL if not set
        // 未設定の場合はダミーURLを生成せず、サイト管理者に警告を表示する
        if (empty($safe_tracking_id)) {
            return '<div class="p-4 my-4 border border-theme-warning/50 bg-theme-warning/10 text-theme-warning font-bold rounded text-sm text-center">⚠️ AmazonアフィリエイトIDが未設定です。ヘッダーのツールバーから設定してください。</div>';
        }

        // Generate affiliate link and image URL
        // アフィリエイトリンクと画像URLの生成
        $amazon_url = "https://www.amazon.co.jp/dp/{$asin}?tag={$safe_tracking_id}";
        // * URL for fetching simple images from ASIN (Modify if Amazon changes specifications)
        // ※ASINから簡易的に画像を取得するURL（Amazonの仕様変更により表示されない場合は適宜変更）
        $image_url = "https://images-na.ssl-images-amazon.com/images/P/{$asin}.09.LZZZZZZZ.jpg";

        // HTML to output (Card design using GrindSite Tailwind CSS classes)
        // 出力するHTML（GrindSiteのTailwind CSSクラスを利用したカードデザイン）
        return <<<HTML
<div class="cms-block-amazon-card bg-theme-surface border border-theme-border rounded-theme p-4 sm:p-5 my-6 shadow-theme transition-all hover:shadow-lg group">
    <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 items-center sm:items-start">
        <div class="shrink-0 bg-white p-2 rounded border border-theme-border/50 flex items-center justify-center w-28 h-28 sm:w-32 sm:h-32 overflow-hidden shadow-sm">
            <a href="{$amazon_url}" target="_blank" rel="noopener noreferrer external" class="block w-full h-full">
                <img src="{$image_url}" alt="{$title}" class="w-full h-full object-contain transition-transform group-hover:scale-105" loading="lazy">
            </a>
        </div>
        <div class="flex-1 flex flex-col justify-between min-w-0 text-center sm:text-left w-full">
            <div class="mb-4">
                <a href="{$amazon_url}" target="_blank" rel="noopener noreferrer external" class="block font-bold text-theme-text text-base sm:text-xl hover:text-theme-primary transition-colors leading-snug line-clamp-2 no-underline">
                    {$title}
                </a>
            </div>
            <div class="flex flex-wrap justify-center sm:justify-start gap-3">
                <a href="{$amazon_url}" target="_blank" rel="noopener noreferrer external" class="inline-flex items-center justify-center text-white font-bold text-xs sm:text-sm px-6 py-2 sm:py-2.5 rounded-full shadow-sm hover:opacity-90 transition-opacity no-underline w-full sm:w-auto" style="background-color: #f90;">
                    <svg class="w-4 h-4 mr-1.5" fill="currentColor" viewBox="0 0 24 24"><path d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25"/></svg>
                    Amazonで購入
                </a>
            </div>
        </div>
    </div>
</div>
HTML;
    }, $content);
});

// 2. Save settings process (Admin area only)
// 2. 設定の保存処理（管理画面のみ）
add_action('grinds_init', function () {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $isAdminArea = str_contains($requestUri, '/admin/') || str_contains($scriptName, '/admin/');

    // Receive POST request from the modal and save securely to GrindSite DB
    // モーダルからのPOSTリクエストを受け取り、GrindSiteのDBに安全に保存する
    if ($isAdminArea && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amazon_tracking_id_action'])) {
        if (!function_exists('validate_csrf_token') || !validate_csrf_token($_POST['csrf_token'] ?? '')) {
            die('Security Error: Invalid CSRF token.');
        }

        if (function_exists('update_option')) {
            update_option('amazon_tracking_id', trim($_POST['new_tracking_id']));
        }
        // Redirect back to the original page to reload after saving
        // 保存後に元のページへリダイレクトして再読み込み
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
});

// 3. Add button to toolbar (Using specific hook)
// 3. ツールバーにボタンを追加（専用フックを使用）
add_action('grinds_admin_toolbar', function () {
    $user = class_exists('App') ? App::user() : null;
    if (!$user) return;

    echo <<<HTML
        <button @click="\$dispatch('open-amazon-modal')" type="button" class="flex items-center gap-1.5 hover:bg-theme-bg px-2 py-1.5 rounded-theme text-theme-text/60 hover:text-theme-text transition-colors" title="Amazon ID">
            <svg class="w-4 h-4" style="color: #f90;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            <span class="hidden sm:inline font-bold text-xs whitespace-nowrap">Amazon ID</span>
        </button>
HTML;
});

// 4. Add settings UI (Modal) to the admin area
// 4. 管理画面に設定用UI（モーダル）を追加
add_action('grinds_footer', function () {
    // Display only to logged-in admins
    // ログイン中の管理者のみに表示
    $user = class_exists('App') ? App::user() : null;
    if (!$user) return;

    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if (!(str_contains($requestUri, '/admin/') || str_contains($scriptName, '/admin/'))) return;

    $tracking_id = function_exists('get_option') ? get_option('amazon_tracking_id', '') : '';
    $csrfToken = function_exists('generate_csrf_token') ? generate_csrf_token() : '';

    // Output settings UI utilizing Tailwind CSS and Alpine.js
    // Tailwind CSS と Alpine.js を活用した設定UIの出力
    echo <<<HTML
    <div x-data="{ showAmazonModal: false }" @open-amazon-modal.window="showAmazonModal = true">
        <!-- 設定モーダル -->
        <div x-show="showAmazonModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm transition-opacity" x-cloak>
            <div @click.outside="showAmazonModal = false" class="bg-theme-surface border border-theme-border rounded-xl shadow-2xl p-6 w-full max-w-sm relative">
                <button type="button" @click="showAmazonModal = false" class="absolute top-4 right-4 text-theme-text opacity-50 hover:opacity-100 transition-opacity">&times;</button>
                <h3 class="text-theme-text font-bold text-lg mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5" style="color: #f90;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Amazonアフィリエイト設定
                </h3>
                <p class="text-theme-text opacity-70 text-xs mb-4 leading-relaxed">
                    アソシエイトのトラッキングIDを入力してください。<br>（例: <code>your_id-22</code>）
                </p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="{$csrfToken}">
                    <input type="hidden" name="amazon_tracking_id_action" value="1">
                    <input type="text" name="new_tracking_id" value="{$tracking_id}" placeholder="your_id-22" class="w-full px-3 py-2 bg-theme-bg border border-theme-border rounded text-theme-text text-sm mb-5 focus:ring-2 focus:ring-theme-primary focus:outline-none font-mono" required>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showAmazonModal = false" class="px-4 py-2 border border-theme-border text-theme-text rounded text-xs font-bold hover:bg-theme-bg transition-colors">キャンセル</button>
                        <button type="submit" class="px-4 py-2 bg-theme-primary text-theme-on-primary rounded text-xs font-bold hover:opacity-90 transition-opacity">保存する</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
HTML;
});
