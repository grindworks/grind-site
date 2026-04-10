<?php

/**
 * Amazon Affiliate Shortcode Plugin
 *
 * [English]
 * Converts [amazon id="ASIN" title="Product" region="com"] shortcodes into beautiful product cards.
 *
 * [Japanese]
 * 記事内の [amazon id="ASIN" title="商品名" region="co.jp"] というショートコードを、
 * リッチな商品カードデザインに自動変換します。region指定で世界各国のAmazonに対応可能です。
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

    // Robust attribute parser (supports id, title, region in any order)
    // 属性の順序に依存しない堅牢なパーサー（id, title, region を取得）
    $pattern = '/\[amazon\s+([^\]]+)\]/i';

    return preg_replace_callback($pattern, function ($matches) use ($tracking_id) {
        preg_match_all('/([a-zA-Z0-9_]+)="([^"]*)"/', $matches[1], $attr_matches);
        $atts = [];
        foreach ($attr_matches[1] as $index => $key) {
            $atts[strtolower($key)] = $attr_matches[2][$index];
        }

        $asin = $atts['id'] ?? '';
        $title = $atts['title'] ?? 'View on Amazon';
        $region = $atts['region'] ?? 'co.jp'; // Default to Japan

        // XSS Prevention: Safely escape user inputs and DB value
        // XSS対策: ユーザー入力とDB値を安全にエスケープ
        $safe_asin = htmlspecialchars(strtoupper($asin), ENT_QUOTES, 'UTF-8');
        $safe_title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safe_region = htmlspecialchars(strtolower($region), ENT_QUOTES, 'UTF-8');
        $safe_tracking_id = htmlspecialchars($tracking_id, ENT_QUOTES, 'UTF-8');

        if (empty($safe_asin) || !preg_match('/^[A-Z0-9]{10}$/', $safe_asin)) {
            return '<div class="p-4 my-4 border border-theme-warning/50 bg-theme-warning/10 text-theme-warning font-bold rounded text-sm text-center">⚠️ Invalid or missing Amazon ASIN.</div>';
        }

        // Display a warning to the site admin instead of generating a dummy URL if not set
        // 未設定の場合はダミーURLを生成せず、サイト管理者に警告を表示する
        if (empty($safe_tracking_id)) {
            return '<div class="p-4 my-4 border border-theme-warning/50 bg-theme-warning/10 text-theme-warning font-bold rounded text-sm text-center">⚠️ AmazonアフィリエイトIDが未設定です。ヘッダーのツールバーから設定してください。</div>';
        }

        // Generate affiliate link and image URL
        // アフィリエイトリンクと画像URLの生成
        $amazon_url = "https://www.amazon.{$safe_region}/dp/{$safe_asin}?tag={$safe_tracking_id}";
        // * URL for fetching simple images from ASIN (Modify if Amazon changes specifications)
        // ※ASINから簡易的に画像を取得するURL（Amazonの仕様変更により表示されない場合は適宜変更）
        $image_url = "https://images-na.ssl-images-amazon.com/images/P/{$safe_asin}.09.LZZZZZZZ.jpg";

        // Determine button text based on region / リージョンに基づいてボタンテキストを変更
        $btn_text = ($safe_region === 'co.jp') ? 'Amazonで購入' : 'Buy on Amazon';
        $sprite_url = resolve_url('assets/img/sprite.svg');

        // HTML to output (Card design using GrindSite Tailwind CSS classes)
        // 出力するHTML（GrindSiteのTailwind CSSクラスを利用したカードデザイン）
        return <<<HTML
<div class="cms-block-amazon-card bg-theme-surface border border-theme-border rounded-theme p-4 sm:p-5 my-6 shadow-theme transition-all hover:shadow-lg group">
    <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 items-center sm:items-start">
        <div class="shrink-0 bg-white p-2 rounded border border-theme-border/50 flex items-center justify-center w-28 h-28 sm:w-32 sm:h-32 overflow-hidden shadow-sm">
            <a href="{$amazon_url}" target="_blank" rel="noopener noreferrer external" class="block w-full h-full">
                <img src="{$image_url}" alt="{$safe_title}" class="w-full h-full object-contain transition-transform group-hover:scale-105" loading="lazy">
            </a>
        </div>
        <div class="flex-1 flex flex-col justify-between min-w-0 text-center sm:text-left w-full">
            <div class="mb-4">
                <a href="{$amazon_url}" target="_blank" rel="noopener noreferrer external" class="block font-bold text-theme-text text-base sm:text-xl hover:text-theme-primary transition-colors leading-snug line-clamp-2 no-underline">
                    {$safe_title}
                </a>
            </div>
            <div class="flex flex-wrap justify-center sm:justify-start gap-3">
                <a href="{$amazon_url}" target="_blank" rel="noopener noreferrer external" class="inline-flex items-center justify-center bg-theme-primary text-theme-on-primary font-bold text-xs sm:text-sm px-6 py-2 sm:py-2.5 rounded-full shadow-sm hover:opacity-90 transition-opacity no-underline w-full sm:w-auto">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="{$sprite_url}#outline-shopping-bag"></use></svg>
                    {$btn_text}
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
    $sprite_url = function_exists('grinds_asset_url') ? grinds_asset_url('assets/img/sprite.svg') : resolve_url('assets/img/sprite.svg');

    echo <<<HTML
        <button @click="\$dispatch('open-amazon-modal')" type="button" class="flex items-center gap-1.5 hover:bg-theme-bg px-2 py-1.5 rounded-theme text-theme-text/60 hover:text-theme-text transition-colors" title="Amazon ID">
            <svg class="w-4 h-4 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="{$sprite_url}#outline-shopping-bag"></use></svg>
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
    $sprite_url = function_exists('grinds_asset_url') ? grinds_asset_url('assets/img/sprite.svg') : resolve_url('assets/img/sprite.svg');

    // Output settings UI utilizing Tailwind CSS and Alpine.js
    // Tailwind CSS と Alpine.js を活用した設定UIの出力
    echo <<<HTML
    <div x-data="{ showAmazonModal: false }" @open-amazon-modal.window="showAmazonModal = true" @keydown.escape.window="showAmazonModal = false">
        <!-- 設定モーダル -->
        <div x-show="showAmazonModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm transition-opacity" x-cloak>
            <div @click.outside="showAmazonModal = false" class="bg-theme-surface border border-theme-border rounded-xl shadow-2xl p-6 w-full max-w-md relative">
                <button type="button" @click="showAmazonModal = false" class="absolute top-4 right-4 text-theme-text opacity-50 hover:opacity-100 transition-opacity">&times;</button>
                <h3 class="text-theme-text font-bold text-lg mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="{$sprite_url}#outline-shopping-bag"></use></svg>
                    Amazonアフィリエイト設定
                </h3>
                <p class="text-theme-text opacity-70 text-xs mb-4 leading-relaxed">
                    アソシエイトのトラッキングIDを入力してください。<br>（例: <code>your_id-22</code>）
                </p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="{$csrfToken}">
                    <input type="hidden" name="amazon_tracking_id_action" value="1">
                    <input type="text" name="new_tracking_id" value="{$tracking_id}" placeholder="your_id-22" class="w-full px-3 py-2 bg-theme-bg border border-theme-border rounded text-theme-text text-sm mb-4 focus:ring-2 focus:ring-theme-primary focus:outline-none font-mono" required>

                    <div class="bg-theme-bg/50 border border-theme-border rounded-lg p-4 mb-6 text-xs text-theme-text leading-relaxed">
                        <p class="font-bold mb-2 flex items-center gap-1"><svg class="w-4 h-4 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="{$sprite_url}#outline-information-circle"></use></svg> ショートコードの使い方</p>
                        <p class="mb-2 opacity-80">記事のエディタ（段落ブロックなど）に以下のように入力します。</p>
                        <ul class="space-y-2 font-mono text-[11px] bg-theme-surface p-3 rounded border border-theme-border/50">
                            <li><span class="opacity-50 inline-block w-20">基本:</span><code class="text-theme-text font-bold">[amazon id="ASIN"]</code></li>
                            <li><span class="opacity-50 inline-block w-20">商品名指定:</span><code class="text-theme-text font-bold">[amazon id="ASIN" title="商品名"]</code></li>
                            <li><span class="opacity-50 inline-block w-20">海外Amazon:</span><code class="text-theme-text font-bold">[amazon id="ASIN" title="商品名" region="com"]</code></li>
                        </ul>
                    </div>
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

// 5. Add button to HTML block tools automatically (Hooks logic)
// 5. HTMLブロックのツールバーに挿入ボタンを追加
add_action('grinds_html_block_tools', function () {
    if (!class_exists('App') || !App::user()) return;
    $sprite_url = function_exists('grinds_asset_url') ? grinds_asset_url('assets/img/sprite.svg') : resolve_url('assets/img/sprite.svg');
    echo <<<HTML
      <button type="button" @click="
        const el = document.getElementById('block-' + block.id + '-code');
        const text = block.data.code || '';
        block.data.code = text + (text ? '\\n' : '') + '[amazon id=\'ASIN\' title=\'商品名\']';
        \$nextTick(() => { el.focus(); el.setSelectionRange(block.data.code.indexOf('ASIN'), block.data.code.indexOf('ASIN') + 4); });
      " class="inline-flex items-center gap-1.5 px-2.5 py-1.5 hover:bg-theme-bg/50 border border-theme-border rounded-theme text-theme-text text-[10px] font-bold transition-colors" title="Insert Amazon Shortcode">
        <svg class="w-3.5 h-3.5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="{$sprite_url}#outline-shopping-bag"></use>
        </svg>
        Amazon
      </button>
HTML;
});
