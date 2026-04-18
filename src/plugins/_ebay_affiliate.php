<?php

/**
 * eBay Affiliate Shortcode Plugin
 *
 * [English]
 * Converts [ebay url="URL" title="Name" image="IMG_URL"] shortcodes into beautiful product cards.
 * To enable this, rename this file to "ebay_affiliate.php" (remove the underscore).
 *
 * [Japanese]
 * 記事内の [ebay url="商品URL" title="商品名" image="画像URL"] というショートコードを、
 * eBayアフィリエイトのリッチな商品カードデザイン（HTML）に自動変換するプラグインです。
 * 有効にするには、ファイル名の先頭の "_" を削除して "ebay_affiliate.php" にしてください。
 */
if (!defined('GRINDS_APP')) exit;

// Translation helper for this plugin
if (!function_exists('grinds_ebay_t')) {
    function grinds_ebay_t($key)
    {
        $lang = function_exists('get_option') ? get_option('site_lang', 'en') : 'en';
        $texts = [
            'en' => [
                'not_set' => '⚠️ eBay Campaign ID is not set. Please configure it in the admin toolbar.',
                'invalid_url' => '⚠️ eBay Product URL is missing or invalid.',
                'buy_on_ebay' => 'Buy on eBay',
                'modal_title' => 'eBay Affiliate Settings',
                'modal_desc' => 'Enter your eBay Partner Network Campaign ID.<br>（e.g., <code>5338000000</code>）',
                'modal_usage' => 'How to use shortcode',
                'cancel' => 'Cancel',
                'save' => 'Save',
                'insert_tooltip' => 'Insert eBay Shortcode',
            ],
            'ja' => [
                'not_set' => '⚠️ eBayキャンペーンIDが未設定です。管理ツールバーから設定してください。',
                'invalid_url' => '⚠️ eBay商品URLが未設定または不正な形式です。',
                'buy_on_ebay' => 'eBayで購入',
                'modal_title' => 'eBayアフィリエイト設定',
                'modal_desc' => 'eBay Partner NetworkのキャンペーンIDを入力してください。<br>（例: <code>5338000000</code>）',
                'modal_usage' => 'ショートコードの使い方',
                'cancel' => 'キャンセル',
                'save' => '保存する',
                'insert_tooltip' => 'eBayショートコードを挿入',
            ],
            'de' => [
                'not_set' => '⚠️ eBay-Kampagnen-ID ist nicht festgelegt. Bitte konfigurieren Sie sie in der Admin-Symbolleiste.',
                'invalid_url' => '⚠️ eBay-Produkt-URL fehlt oder ist ungültig.',
                'buy_on_ebay' => 'Bei eBay kaufen',
                'modal_title' => 'eBay Affiliate Einstellungen',
                'modal_desc' => 'Geben Sie Ihre eBay Partner Network Kampagnen-ID ein.<br>(z.B. <code>5338000000</code>)',
                'modal_usage' => 'Wie man Shortcodes verwendet',
                'cancel' => 'Abbrechen',
                'save' => 'Speichern',
                'insert_tooltip' => 'eBay Shortcode einfügen',
            ],
        ];
        $l = array_key_exists($lang, $texts) ? $lang : 'en';
        return $texts[$l][$key] ?? $key;
    }
}

// 1. Filter to expand shortcode during post content output
add_filter('grinds_the_content', function ($content) {
    if (!str_contains($content, '[ebay ')) {
        return $content;
    }

    // Get the Campaign ID configured in the admin area
    $camp_id = function_exists('get_option') ? get_option('ebay_campaign_id', '') : '';

    // Robust attribute parser (supports url, title, and image in any order)
    $pattern = '/\[ebay\s+([^\]]+)\]/i';

    return preg_replace_callback($pattern, function ($matches) use ($camp_id) {
        preg_match_all('/([a-zA-Z0-9_]+)="([^"]*)"/', $matches[1], $attr_matches);
        $atts = [];
        foreach ($attr_matches[1] as $index => $key) {
            $atts[strtolower($key)] = $attr_matches[2][$index];
        }

        $url = $atts['url'] ?? '';
        $title = $atts['title'] ?? 'View on eBay';
        $image = $atts['image'] ?? '';

        // XSS Prevention: Safely escape user inputs and validate URLs
        $safe_camp_id = htmlspecialchars($camp_id, ENT_QUOTES, 'UTF-8');
        $safe_title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safe_url = filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
        $safe_image = filter_var($image, FILTER_VALIDATE_URL) ? $image : '';

        // Display warnings to the admin if required data is missing
        if (empty($safe_camp_id)) {
            $msg = grinds_ebay_t('not_set');
            return '<div class="p-4 my-4 border border-theme-warning/50 bg-theme-warning/10 text-theme-warning font-bold rounded text-sm text-center">' . $msg . '</div>';
        }
        if (empty($safe_url)) {
            $msg = grinds_ebay_t('invalid_url');
            return '<div class="p-4 my-4 border border-theme-warning/50 bg-theme-warning/10 text-theme-warning font-bold rounded text-sm text-center">' . $msg . '</div>';
        }

        // Generate eBay affiliate link dynamically using eBay Partner Network (Rover) format
        $affiliate_url = "https://rover.ebay.com/rover/1/711-53200-19255-0/1?mpre=" . urlencode($safe_url) . "&campid=" . $safe_camp_id . "&toolid=10001";

        // Generate Image HTML (Fallback to SVG icon if no image provided)
        $sprite_url = resolve_url('assets/img/sprite.svg');
        if (!empty($safe_image)) {
            $image_html = '<img src="' . htmlspecialchars($safe_image, ENT_QUOTES, 'UTF-8') . '" alt="' . $safe_title . '" class="w-full h-full object-contain transition-transform group-hover:scale-105" loading="lazy">';
        } else {
            $image_html = '<svg class="w-12 h-12 text-theme-text/20 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . $sprite_url . '#outline-shopping-bag"></use></svg>';
        }

        $ebay_color = '#0064d2'; // eBay Blue

        // HTML to output (eBay Blue #0064d2)
        return <<<HTML
<div class="cms-block-ebay-card bg-theme-surface border border-theme-border rounded-theme p-4 sm:p-5 my-6 shadow-theme transition-all hover:shadow-lg group">
    <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 items-center sm:items-start">
        <div class="shrink-0 bg-white p-2 rounded border border-theme-border/50 flex items-center justify-center w-28 h-28 sm:w-32 sm:h-32 overflow-hidden shadow-sm">
            <a href="{$affiliate_url}" target="_blank" rel="noopener noreferrer external" class="flex w-full h-full items-center justify-center">
                {$image_html}
            </a>
        </div>
        <div class="flex-1 flex flex-col justify-between min-w-0 text-center sm:text-left w-full">
            <div class="mb-4">
                <a href="{$affiliate_url}" target="_blank" rel="noopener noreferrer external" class="block font-bold text-theme-text text-base sm:text-xl hover:text-theme-primary transition-colors leading-snug line-clamp-2 no-underline">
                    {$safe_title}
                </a>
            </div>
            <div class="flex flex-wrap justify-center sm:justify-start gap-3">
                <a href="{$affiliate_url}" target="_blank" rel="noopener noreferrer external" class="inline-flex items-center justify-center text-white font-bold text-xs sm:text-sm px-6 py-2 sm:py-2.5 rounded-full shadow-sm hover:opacity-90 transition-opacity no-underline w-full sm:w-auto" style="background-color: {$ebay_color};">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="{$sprite_url}#outline-shopping-bag"></use></svg>
                    ' . grinds_ebay_t('buy_on_ebay') . '
                </a>
            </div>
        </div>
    </div>
</div>
HTML;
    }, $content);
});

// 2. Save settings process
add_action('grinds_init', function () {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if ((str_contains($requestUri, '/admin/') || str_contains($scriptName, '/admin/')) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ebay_campaign_id_action'])) {
        if (!function_exists('validate_csrf_token') || !validate_csrf_token($_POST['csrf_token'] ?? '')) die('Security Error: Invalid CSRF token.');
        if (function_exists('update_option')) update_option('ebay_campaign_id', trim($_POST['new_ebay_id']));
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
});

// 3. Add button to admin toolbar
add_action('grinds_admin_toolbar', function () {
    if (!class_exists('App') || !App::user()) return;
    $sprite_url = function_exists('grinds_asset_url') ? grinds_asset_url('assets/img/sprite.svg') : resolve_url('assets/img/sprite.svg');
    echo <<<HTML
        <button @click="\$dispatch('open-ebay-modal')" type="button" class="flex items-center gap-1.5 hover:bg-theme-bg px-2 py-1.5 rounded-theme text-theme-text/60 hover:text-theme-text transition-colors" title="eBay ID">
            <svg class="w-4 h-4 text-theme-info" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="{$sprite_url}#outline-shopping-bag"></use></svg>
            <span class="hidden sm:inline font-bold text-xs whitespace-nowrap">eBay ID</span>
        </button>
HTML;
});

// 4. Add settings UI (Modal) to the admin area
add_action('grinds_footer', function () {
    if (!class_exists('App') || !App::user()) return;
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if (!(str_contains($requestUri, '/admin/') || str_contains($scriptName, '/admin/'))) return;

    $camp_id = function_exists('get_option') ? get_option('ebay_campaign_id', '') : '';
    $csrfToken = function_exists('generate_csrf_token') ? generate_csrf_token() : '';
    $sprite_url = function_exists('grinds_asset_url') ? grinds_asset_url('assets/img/sprite.svg') : resolve_url('assets/img/sprite.svg');

    $t_modal_title = grinds_ebay_t('modal_title');
    $t_modal_desc = grinds_ebay_t('modal_desc');
    $t_modal_usage = grinds_ebay_t('modal_usage');
    $t_cancel = grinds_ebay_t('cancel');
    $t_save = grinds_ebay_t('save');

    echo <<<HTML
    <div x-data="{ showEbayModal: false }" @open-ebay-modal.window="showEbayModal = true" @keydown.escape.window="showEbayModal = false">
        <div x-show="showEbayModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm transition-opacity" x-cloak>
            <div @click.outside="showEbayModal = false" class="bg-theme-surface border border-theme-border rounded-xl shadow-2xl p-6 w-full max-w-md relative">
                <button type="button" @click="showEbayModal = false" class="absolute top-4 right-4 text-theme-text opacity-50 hover:opacity-100 transition-opacity">&times;</button>
                <h3 class="text-theme-text font-bold text-lg mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-theme-info" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="{$sprite_url}#outline-shopping-bag"></use></svg>
                    {$t_modal_title}
                </h3>
                <p class="text-theme-text opacity-70 text-xs mb-4 leading-relaxed">
                    {$t_modal_desc}
                </p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="{$csrfToken}">
                    <input type="hidden" name="ebay_campaign_id_action" value="1">
                    <input type="text" name="new_ebay_id" value="{$camp_id}" placeholder="5338000000" class="w-full px-3 py-2 bg-theme-bg border border-theme-border rounded text-theme-text text-sm mb-4 focus:ring-2 focus:ring-theme-primary focus:outline-none font-mono" required>

                    <div class="bg-theme-bg/50 border border-theme-border rounded-lg p-4 mb-6 text-xs text-theme-text leading-relaxed">
                        <p class="font-bold mb-2 flex items-center gap-1"><svg class="w-4 h-4 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="{$sprite_url}#outline-information-circle"></use></svg> {$t_modal_usage}</p>
                        <div class="font-mono text-[11px] bg-theme-surface p-3 rounded border border-theme-border/50 break-all font-bold text-theme-text">[ebay url="Product URL" title="Product Name" image="Image URL"]</div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showEbayModal = false" class="px-4 py-2 border border-theme-border text-theme-text rounded text-xs font-bold hover:bg-theme-bg transition-colors">{$t_cancel}</button>
                        <button type="submit" class="px-4 py-2 bg-theme-primary text-theme-on-primary rounded text-xs font-bold hover:opacity-90 transition-opacity">{$t_save}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
HTML;
});

// 5. Add button to HTML block tools automatically (Hooks logic)
add_action('grinds_html_block_tools', function () {
    if (!class_exists('App') || !App::user()) return;
    $sprite_url = function_exists('grinds_asset_url') ? grinds_asset_url('assets/img/sprite.svg') : resolve_url('assets/img/sprite.svg');
    $t_insert_tooltip = grinds_ebay_t('insert_tooltip');
    echo <<<HTML
      <button type="button" @click="
        const el = document.getElementById('block-' + block.id + '-code');
        const text = block.data.code || '';
        block.data.code = text + (text ? '\\n' : '') + '[ebay url=\'URL\' title=\'Name\' image=\'IMG_URL\']';
        \$nextTick(() => { el.focus(); el.setSelectionRange(block.data.code.indexOf('URL'), block.data.code.indexOf('URL') + 3); });
      " class="inline-flex items-center gap-1.5 px-2.5 py-1.5 hover:bg-theme-bg/50 border border-theme-border rounded-theme text-theme-text text-[10px] font-bold transition-colors" title="{$t_insert_tooltip}">
        <svg class="w-3.5 h-3.5 text-theme-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="{$sprite_url}#outline-shopping-bag"></use>
        </svg>
        eBay
      </button>
HTML;
});
