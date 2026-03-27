<?php

/**
 * assets_loader.php
 *
 * Load CSS/JS assets and define CSS variables based on the active skin.
 */
if (!defined('GRINDS_APP')) {
    exit;
}

$skinAssets = file_exists(__DIR__ . '/../config/skin_assets.php') ? require __DIR__ . '/../config/skin_assets.php' : ['textures' => [], 'media_backgrounds' => []];

// Prepare style variables
$body_extra_style = '';
$texture_key = $skin['texture'] ?? '';
$texture_url = $skinAssets['textures'][$texture_key] ?? (str_starts_with($texture_key, 'data:') ? $texture_key : '');
if (!empty($texture_url)) {
    $body_extra_style .= sprintf("background-image: url('%s'); background-repeat: repeat;", $texture_url);
}

if (!empty($skin['cursor']) && $skin['cursor'] !== 'auto') {
    $body_extra_style .= sprintf("cursor: %s;", $skin['cursor']);
}

$font_family = !empty($skin['font']) ? $skin['font'] : 'sans-serif';

if (!function_exists('css_var_color')) {
    /**
     * Helper to generate RGB and Alpha CSS variables.
     */
    function css_var_color(string $name, string $hex): string
    {
        $str = hex2rgb($hex);
        $parts = explode(' / ', $str);
        $rgb = $parts[0];
        $alpha = $parts[1] ?? '1';
        return sprintf("--color-%s: %s;\n        --color-%s-alpha: %s;", $name, $rgb, $name, $alpha);
    }
}

if (isset($colors) && is_array($colors)) {
    $colors = array_filter($colors, function ($v) {
        return trim((string)$v) !== '';
    });
}

// Auto-generate all color variables from the skin configuration
$css_vars = [];
foreach ($colors as $key => $hex) {
    $cssName = str_replace('_', '-', $key);
    $css_vars[$cssName] = css_var_color($cssName, $hex);
}

// Ensure required variables exist with fallbacks
$required_colors = [
    'bg' => $colors['bg'] ?? '#f8fafc',
    'sidebar' => $colors['sidebar'] ?? $colors['surface'] ?? '#1e293b',
    'surface' => $colors['surface'] ?? '#ffffff',
    'text' => $colors['text'] ?? '#334155',
    'primary' => $colors['primary'] ?? '#2563eb',
    'on-primary' => $colors['on-primary'] ?? '#ffffff',
    'secondary' => $colors['secondary'] ?? '#64748b',
    'on-secondary' => $colors['on-secondary'] ?? '#ffffff',
    'border' => $colors['border'] ?? '#e2e8f0',
    'success' => $colors['success'] ?? '#22c55e',
    'on-success' => $colors['on-success'] ?? '#ffffff',
    'danger' => $colors['danger'] ?? '#ef4444',
    'on-danger' => $colors['on-danger'] ?? '#ffffff',
    'warning' => $colors['warning'] ?? '#eab308',
    'on-warning' => $colors['on-warning'] ?? '#ffffff',
    'info' => $colors['info'] ?? '#0ea5e9',
    'on-info' => $colors['on-info'] ?? '#ffffff',
    'sidebar-text' => $colors['sidebar_text'] ?? ($is_sidebar_dark ? '#ffffff' : '#334155'),
    'sidebar-active-bg' => $colors['sidebar_active_bg'] ?? ($colors['primary'] ?? '#2563eb'),
    'sidebar-active-text' => $colors['sidebar_active_text'] ?? ($colors['on-primary'] ?? '#ffffff'),
    'sidebar-hover-bg' => $colors['sidebar_hover_bg'] ?? ($is_sidebar_dark ? '#334155' : '#f1f5f9'),
    'sidebar-hover-text' => $colors['sidebar_hover_text'] ?? ($is_sidebar_dark ? '#ffffff' : '#2563eb'),
    'sidebar-crt' => $colors['sidebar_crt'] ?? ($colors['primary'] ?? '#33ff33'),
    'sidebar-crt-shadow' => $colors['sidebar_crt_shadow'] ?? '#000000',
    'sidebar-crt-border' => $colors['sidebar_crt_border'] ?? ($colors['primary'] ?? '#33ff33'),
    'input-bg' => $colors['input_bg'] ?? $colors['surface'] ?? '#ffffff',
    'input-text' => $colors['input_text'] ?? $colors['text'] ?? '#334155',
    'input-placeholder' => $colors['input_placeholder'] ?? '#94a3b8',
    'input-border' => $colors['input_border'] ?? $colors['border'] ?? '#e2e8f0',
    'table-header-bg' => $colors['table_header_bg'] ?? $colors['surface'] ?? '#f8fafc',
    'table-header-text' => $colors['table_header_text'] ?? $colors['text'] ?? '#334155',
    'scrollbar-track' => $colors['scrollbar_track'] ?? $colors['bg'] ?? '#f1f5f9',
    'scrollbar-thumb' => $colors['scrollbar_thumb'] ?? $colors['secondary'] ?? '#cbd5e1',
    'modal-overlay' => $colors['modal_overlay'] ?? '#000000',
    'media-checker-1' => $colors['media_checker_1'] ?? '#e2e8f0',
    'media-checker-2' => $colors['media_checker_2'] ?? '#f8fafc',
    'media-ring' => $colors['media_ring'] ?? $colors['primary'] ?? '#2563eb',
];

if (!empty($colors['sidebar_border'])) {
    $required_colors['sidebar-border'] = $colors['sidebar_border'];
}
if (!empty($colors['sidebar_input_bg'])) {
    $required_colors['sidebar-input-bg'] = $colors['sidebar_input_bg'];
}
if (!empty($colors['sidebar_input_border'])) {
    $required_colors['sidebar-input-border'] = $colors['sidebar_input_border'];
}

foreach ($required_colors as $key => $fallbackHex) {
    if (!isset($css_vars[$key])) {
        $css_vars[$key] = css_var_color($key, $fallbackHex);
    }
}

// Convert back to indexed array and add non-color variables
$css_vars = array_values($css_vars);

$css_vars = array_merge($css_vars, [
    sprintf("--sidebar-crt-opacity: %s;", $skin['sidebar_crt_opacity'] ?? '0.05'),
    sprintf("--sidebar-crt-shadow-x: %s;", $skin['sidebar_crt_shadow_x'] ?? '-15px'),
    sprintf("--sidebar-crt-shadow-blur: %s;", $skin['sidebar_crt_shadow_blur'] ?? '30px'),
    sprintf("--sidebar-crt-shadow-opacity: %s;", $skin['sidebar_crt_shadow_opacity'] ?? '0.9'),
    sprintf("--sidebar-crt-border-width: %s;", $skin['sidebar_crt_border_width'] ?? '1px'),
    sprintf("--sidebar-crt-border-opacity: %s;", $skin['sidebar_crt_border_opacity'] ?? '0.2'),
    sprintf("--modal-overlay-opacity: %s;", $skin['modal_overlay_opacity'] ?? '0.6'),
    sprintf("--font-body: %s;", $font_family),
    sprintf("--font-heading: %s;", !empty($skin['font_heading']) ? $skin['font_heading'] : 'inherit'),
    sprintf("--font-size-base: %s;", $skin['font_size_base'] ?? '0.875rem'),
    sprintf("--border-radius: %s;", $skin['rounded'] ?? '0.5rem'),
    sprintf("--btn-radius: %s;", $skin['btn_radius'] ?? $skin['rounded'] ?? '0.5rem'),
    sprintf("--add-block-radius: %s;", $skin['add_block_radius'] ?? '0px'),
    sprintf("--box-shadow: %s;", $skin['shadow'] ?? '0 1px 3px 0 rgba(0, 0, 0, 0.1)'),
    sprintf("--border-width: %s;", $skin['border_width'] ?? '1px'),
    sprintf("--input-padding: %s;", $skin['input_padding'] ?? '0.5rem 1rem'),
    sprintf("--scrollbar-width: %s;", $skin['scrollbar_width'] ?? '10px'),
]);

$css_vars_string = implode("\n        ", $css_vars);

$is_login_page = (isset($current_page) && $current_page === 'login') || (basename($_SERVER['SCRIPT_NAME']) === 'login.php');

$disable_external_assets = get_option('disable_external_assets');
$use_local_assets = $disable_external_assets && file_exists(ROOT_PATH . '/assets/js/vendor/alpine.min.js') && file_exists(ROOT_PATH . '/assets/js/vendor/collapse.min.js');

$media_bg_key = $skin['media_bg_css'] ?? '';
$media_bg_css = $skinAssets['media_backgrounds'][$media_bg_key] ?? $media_bg_key;
?>
<?php if (!empty($skin['font_url']) && !$disable_external_assets): ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?= h(resolve_url($skin['font_url'])) ?>" rel="stylesheet">
<?php
endif; ?>

<link href="<?= grinds_asset_url('assets/css/admin.css') ?>" rel="stylesheet">

<?php if ($use_local_assets): ?>
    <?php if (!$is_login_page): ?>
        <script defer src="<?= grinds_asset_url('assets/js/vendor/collapse.min.js') ?>"></script>
    <?php
    endif; ?>
    <script defer src="<?= grinds_asset_url('assets/js/vendor/alpine.min.js') ?>"></script>
<?php
else: ?>
    <?php if (!$is_login_page): ?>
        <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <?php
    endif; ?>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<?php
endif; ?>

<style>
    [x-cloak] {
        display: none !important;
    }

    :root {
        color-scheme: <?= $color_scheme ?>;
        <?= $css_vars_string ?>
    }

    body {
        background-color: rgb(var(--color-bg) / var(--color-bg-alpha, 1));
        color: rgb(var(--color-text) / var(--color-text-alpha, 1));
        font-family: var(--font-body);
        font-size: var(--font-size-base);
        <?= $body_extra_style ?>
    }

    <?php if (!empty($skin['sidebar_custom_css'])): ?>.bg-theme-sidebar {
        <?= $skin['sidebar_custom_css'] ?>
    }

    <?php
    endif;
    ?>

    /* Scrollbar customization */
    ::-webkit-scrollbar {
        width: var(--scrollbar-width);
        height: var(--scrollbar-width);
    }

    ::-webkit-scrollbar-track {
        background: rgb(var(--color-scrollbar-track) / var(--color-scrollbar-track-alpha, 1));
    }

    ::-webkit-scrollbar-thumb {
        background: rgb(var(--color-scrollbar-thumb) / var(--color-scrollbar-thumb-alpha, 1));
        border-radius: var(--border-radius);
    }

    /* Modal overlay */
    .skin-modal-overlay {
        background-color: rgb(var(--color-modal-overlay) / var(--modal-overlay-opacity)) !important;
    }

    /* Checkerboard pattern for transparent media */
    .bg-checker {
        background-color: rgb(var(--color-media-checker-2) / var(--color-media-checker-2-alpha, 1));
        background-image:
            linear-gradient(45deg, rgb(var(--color-media-checker-1) / var(--color-media-checker-1-alpha, 1)) 25%, transparent 25%, transparent 75%, rgb(var(--color-media-checker-1) / var(--color-media-checker-1-alpha, 1)) 75%, rgb(var(--color-media-checker-1) / var(--color-media-checker-1-alpha, 1))),
            linear-gradient(45deg, rgb(var(--color-media-checker-1) / var(--color-media-checker-1-alpha, 1)) 25%, transparent 25%, transparent 75%, rgb(var(--color-media-checker-1) / var(--color-media-checker-1-alpha, 1)) 75%, rgb(var(--color-media-checker-1) / var(--color-media-checker-1-alpha, 1)));
        background-size: 16px 16px;
        background-position: 0 0, 8px 8px;
    }

    /* Text selection */
    ::selection {
        background-color: rgb(var(--color-primary) / var(--color-primary-alpha, 1));
        color: rgb(var(--color-on-primary) / var(--color-on-primary-alpha, 1));
    }

    /* Slow ping animation for normal system status */
    @keyframes ping-slow {
        0%, 100% { transform: scale(1); opacity: 0.75; }
        50% { transform: scale(1.8); opacity: 0; }
    }
    .animate-ping-slow {
        animation: ping-slow 3s cubic-bezier(0, 0, 0.2, 1) infinite;
    }

    /* Apply custom skin CSS */
    <?= grinds_url_to_view($skin['css'] ?? '') ?>
</style>
