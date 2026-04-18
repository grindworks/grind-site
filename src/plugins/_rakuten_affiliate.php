<?php

/**
 * Rakuten Affiliate Shortcode Plugin
 *
 * [English]
 * Converts [rakuten url="URL" title="Name" image="IMG_URL"] shortcodes into beautiful product cards.
 * NOTE: Rakuten Affiliate is a service for the Japanese market only. It is disabled by default.
 * Rename to "rakuten_affiliate.php" (remove the underscore) to enable.
 *
 * [Japanese]
 * 記事内の [rakuten url="商品URL" title="商品名" image="画像URL"] というショートコードを、
 * 楽天アフィリエイトのリッチな商品カードデザイン（HTML）に自動変換するプラグインです。
 * 注意: 楽天アフィリエイトは日本国内向けのサービスのため、デフォルトでは無効化されています。
 * 有効にするには "_" を削除して "rakuten_affiliate.php" にリネームしてください。
 */
if (!defined('GRINDS_APP')) exit;

// Translation helper for this plugin
if (!function_exists('grinds_rakuten_t')) {
    function grinds_rakuten_t($key)
    {
        $lang = function_exists('get_option') ? get_option('site_lang', 'en') : 'en';
        $texts = [
            'en' => [
                'not_set' => '⚠️ Rakuten Affiliate ID is not set. Please configure it from the header toolbar.',
                'invalid_url' => '⚠️ Rakuten Product URL is missing or invalid.',
                'buy_on_rakuten' => 'Buy on Rakuten',
                'modal_title' => 'Rakuten Affiliate Settings',
                'modal_desc' => 'Enter your Rakuten Affiliate ID.<br>(e.g., <code>1a2b3c4d.5e6f7g8h...</code>)',
                'modal_usage' => 'How to use shortcode',
                'modal_usage_desc' => 'Enter the following in the editor (e.g., Paragraph block):',
                'cancel' => 'Cancel',
                'save' => 'Save',
                'insert_tooltip' => 'Insert Rakuten Shortcode',
            ],
            'ja' => [
                'not_set' => '⚠️ 楽天アフィリエイトIDが未設定です。ヘッダーのツールバーから設定してください。',
                'invalid_url' => '⚠️ 楽天商品URLが未設定または不正な形式です。',
                'buy_on_rakuten' => '楽天市場で購入',
                'modal_title' => '楽天アフィリエイト設定',
                'modal_desc' => '楽天アフィリエイトIDを入力してください。<br>（例: <code>1a2b3c4d.5e6f7g8h...</code>）',
                'modal_usage' => 'ショートコードの使い方',
                'modal_usage_desc' => '記事のエディタ（段落ブロックなど）に以下のように入力します。',
                'cancel' => 'キャンセル',
                'save' => '保存する',
                'insert_tooltip' => '楽天ショートコードを挿入',
            ],
            'de' => [
                'not_set' => '⚠️ Rakuten Affiliate ID ist nicht festgelegt. Bitte konfigurieren Sie sie in der Header-Symbolleiste.',
                'invalid_url' => '⚠️ Rakuten Produkt-URL fehlt oder ist ungültig.',
                'buy_on_rakuten' => 'Bei Rakuten kaufen',
                'modal_title' => 'Rakuten Affiliate Einstellungen',
                'modal_desc' => 'Geben Sie Ihre Rakuten Affiliate ID ein.<br>(z.B. <code>1a2b3c4d.5e6f7g8h...</code>)',
                'modal_usage' => 'Wie man Shortcodes verwendet',
                'modal_usage_desc' => 'Geben Sie Folgendes im Editor ein (z.B. Absatzblock):',
                'cancel' => 'Abbrechen',
                'save' => 'Speichern',
                'insert_tooltip' => 'Rakuten Shortcode einfügen',
            ],
        ];
        $l = array_key_exists($lang, $texts) ? $lang : 'en';
        return $texts[$l][$key] ?? $key;
    }
}

// 1. Filter to expand shortcode during post content output (using grinds_the_content)
// 1. 記事コンテンツの出力時にショートコードを展開するフィルター
add_filter('grinds_the_content', function ($content) {
    if (!str_contains($content, '[rakuten ')) {
        return $content;
    }

    // Get the affiliate ID configured in the admin area
    // 管理画面で設定されたアフィリエイトIDを取得
    $aff_id = function_exists('get_option') ? get_option('rakuten_affiliate_id', '') : '';

    // Robust attribute parser (supports url, title, and image in any order)
    // 属性の順序に依存しない堅牢なパーサー（url, title, image を取得）
    $pattern = '/\[rakuten\s+([^\]]+)\]/i';

    return preg_replace_callback($pattern, function ($matches) use ($aff_id) {
        preg_match_all('/([a-zA-Z0-9_]+)="([^"]*)"/', $matches[1], $attr_matches);
        $atts = [];
        foreach ($attr_matches[1] as $index => $key) {
            $atts[strtolower($key)] = $attr_matches[2][$index];
        }

        $url = $atts['url'] ?? '';
        $title = $atts['title'] ?? '楽天市場で詳細を見る';
        $image = $atts['image'] ?? '';

        // XSS Prevention: Safely escape user inputs and validate URLs
        // XSS対策: ユーザー入力のエスケープとURLの厳格な検証
        $safe_aff_id = htmlspecialchars($aff_id, ENT_QUOTES, 'UTF-8');
        $safe_title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safe_url = filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
        $safe_image = filter_var($image, FILTER_VALIDATE_URL) ? $image : '';

        // Display warnings to the admin if required data is missing
        if (empty($safe_aff_id)) {
            $msg = grinds_rakuten_t('not_set');
            return '<div class="p-4 my-4 border border-theme-warning/50 bg-theme-warning/10 text-theme-warning font-bold rounded text-sm text-center">' . $msg . '</div>';
        }
        if (empty($safe_url)) {
            $msg = grinds_rakuten_t('invalid_url');
            return '<div class="p-4 my-4 border border-theme-warning/50 bg-theme-warning/10 text-theme-warning font-bold rounded text-sm text-center">' . $msg . '</div>';
        }

        // Generate Rakuten affiliate link dynamically
        // 楽天アフィリエイトURLの動的生成 (アフィリエイトID + エンコードした商品URL)
        $affiliate_url = "https://hb.afl.rakuten.co.jp/ichiba/{$safe_aff_id}/?pc=" . urlencode($safe_url);

        // Generate Image HTML (Fallback to SVG icon if no image provided)
        // 画像HTMLの生成（画像URLがない場合は汎用のショッピングアイコンを表示）
        $sprite_url = resolve_url('assets/img/sprite.svg');
        if (!empty($safe_image)) {
            $image_html = '<img src="' . htmlspecialchars($safe_image, ENT_QUOTES, 'UTF-8') . '" alt="' . $safe_title . '" class="w-full h-full object-contain transition-transform group-hover:scale-105" loading="lazy">';
        } else {
            $image_html = '<svg class="w-12 h-12 text-theme-text/20 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . $sprite_url . '#outline-shopping-bag"></use></svg>';
        }

        // HTML to output (Card design using GrindSite Tailwind CSS classes with Rakuten Red #bf0000)
        // 楽天カラー（#bf0000）を使用したリッチなカードデザインの出力
        return <<<HTML
<div class="cms-block-rakuten-card bg-theme-surface border border-theme-border rounded-theme p-4 sm:p-5 my-6 shadow-theme transition-all hover:shadow-lg group">
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
                <a href="{$affiliate_url}" target="_blank" rel="noopener noreferrer external" class="inline-flex items-center justify-center bg-theme-danger text-theme-on-danger font-bold text-xs sm:text-sm px-6 py-2 sm:py-2.5 rounded-full shadow-sm hover:opacity-90 transition-opacity no-underline w-full sm:w-auto">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="{$sprite_url}#outline-shopping-bag"></use></svg>
                    ' . grinds_rakuten_t('buy_on_rakuten') . '
                </a>
            </div>
        </div>
    </div>
</div>
HTML;
    }, $content);
});

// 2. Save settings process (Admin area only)
add_action('grinds_init', function () {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $isAdminArea = str_contains($requestUri, '/admin/') || str_contains($scriptName, '/admin/');

    if ($isAdminArea && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rakuten_affiliate_id_action'])) {
        if (!function_exists('validate_csrf_token') || !validate_csrf_token($_POST['csrf_token'] ?? '')) {
            die('Security Error: Invalid CSRF token.');
        }
        if (function_exists('update_option')) {
            update_option('rakuten_affiliate_id', trim($_POST['new_rakuten_id']));
        }
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }
});

// 3. Add button to toolbar
add_action('grinds_admin_toolbar', function () {
    if (!class_exists('App') || !App::user()) return;
    $sprite_url = function_exists('grinds_asset_url') ? grinds_asset_url('assets/img/sprite.svg') : resolve_url('assets/img/sprite.svg');
    echo <<<HTML
        <button @click="\$dispatch('open-rakuten-modal')" type="button" class="flex items-center gap-1.5 hover:bg-theme-bg px-2 py-1.5 rounded-theme text-theme-text/60 hover:text-theme-text transition-colors" title="Rakuten ID">
            <svg class="w-4 h-4 text-theme-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="{$sprite_url}#outline-shopping-bag"></use></svg>
            <span class="hidden sm:inline font-bold text-xs whitespace-nowrap">Rakuten ID</span>
        </button>
HTML;
});

// 4. Add settings UI (Modal) to the admin area
add_action('grinds_footer', function () {
    if (!class_exists('App') || !App::user()) return;
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    if (!(str_contains($requestUri, '/admin/') || str_contains($scriptName, '/admin/'))) return;

    $aff_id = function_exists('get_option') ? get_option('rakuten_affiliate_id', '') : '';
    $csrfToken = function_exists('generate_csrf_token') ? generate_csrf_token() : '';
    $sprite_url = function_exists('grinds_asset_url') ? grinds_asset_url('assets/img/sprite.svg') : resolve_url('assets/img/sprite.svg');

    $t_modal_title = grinds_rakuten_t('modal_title');
    $t_modal_desc = grinds_rakuten_t('modal_desc');
    $t_modal_usage = grinds_rakuten_t('modal_usage');
    $t_modal_usage_desc = grinds_rakuten_t('modal_usage_desc');
    $t_cancel = grinds_rakuten_t('cancel');
    $t_save = grinds_rakuten_t('save');

    echo <<<HTML
    <div x-data="{ showRakutenModal: false }" @open-rakuten-modal.window="showRakutenModal = true" @keydown.escape.window="showRakutenModal = false">
        <div x-show="showRakutenModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm transition-opacity" x-cloak>
            <div @click.outside="showRakutenModal = false" class="bg-theme-surface border border-theme-border rounded-xl shadow-2xl p-6 w-full max-w-md relative">
                <button type="button" @click="showRakutenModal = false" class="absolute top-4 right-4 text-theme-text opacity-50 hover:opacity-100 transition-opacity">&times;</button>
                <h3 class="text-theme-text font-bold text-lg mb-2 flex items-center gap-2">
                    <svg class="w-5 h-5 text-theme-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="{$sprite_url}#outline-shopping-bag"></use></svg>
                    {$t_modal_title}
                </h3>
                <p class="text-theme-text opacity-70 text-xs mb-4 leading-relaxed">
                    {$t_modal_desc}
                </p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="{$csrfToken}">
                    <input type="hidden" name="rakuten_affiliate_id_action" value="1">
                    <input type="text" name="new_rakuten_id" value="{$aff_id}" placeholder="12345678.9abcdef0..." class="w-full px-3 py-2 bg-theme-bg border border-theme-border rounded text-theme-text text-sm mb-4 focus:ring-2 focus:ring-theme-primary focus:outline-none font-mono" required>

                    <div class="bg-theme-bg/50 border border-theme-border rounded-lg p-4 mb-6 text-xs text-theme-text leading-relaxed">
                        <p class="font-bold mb-2 flex items-center gap-1"><svg class="w-4 h-4 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="{$sprite_url}#outline-information-circle"></use></svg> {$t_modal_usage}</p>
                        <p class="mb-2 opacity-80">{$t_modal_usage_desc}</p>
                        <div class="font-mono text-[11px] bg-theme-surface p-3 rounded border border-theme-border/50 break-all font-bold text-theme-text">[rakuten url="商品URL" title="商品名" image="画像URL"]</div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showRakutenModal = false" class="px-4 py-2 border border-theme-border text-theme-text rounded text-xs font-bold hover:bg-theme-bg transition-colors">{$t_cancel}</button>
                        <button type="submit" class="px-4 py-2 bg-theme-primary text-theme-on-primary rounded text-xs font-bold hover:opacity-90 transition-opacity">{$t_save}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
HTML;
});

// 5. Add button to HTML block tools
add_action('grinds_html_block_tools', function () {
    if (!class_exists('App') || !App::user()) return;
    $sprite_url = function_exists('grinds_asset_url') ? grinds_asset_url('assets/img/sprite.svg') : resolve_url('assets/img/sprite.svg');
    $t_insert_tooltip = grinds_rakuten_t('insert_tooltip');
    echo <<<HTML
      <button type="button" @click="
        const el = document.getElementById('block-' + block.id + '-code');
        const text = block.data.code || '';
        block.data.code = text + (text ? '\\n' : '') + '[rakuten url=\'商品URL\' title=\'商品名\' image=\'画像URL\']';
        \$nextTick(() => { el.focus(); el.setSelectionRange(block.data.code.indexOf('商品URL'), block.data.code.indexOf('商品URL') + 5); });
      " class="inline-flex items-center gap-1.5 px-2.5 py-1.5 hover:bg-theme-bg/50 border border-theme-border rounded-theme text-theme-text text-[10px] font-bold transition-colors" title="{$t_insert_tooltip}">
        <svg class="w-3.5 h-3.5 text-theme-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="{$sprite_url}#outline-shopping-bag"></use>
        </svg>
        Rakuten
      </button>
HTML;
});
