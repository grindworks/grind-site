<?php

/**
 * skin_defaults.php
 *
 * Return the default skin configuration.
 */
if (!defined('GRINDS_APP')) exit;

// Initialize defaults
$defaults = [];

// Load default.json
$skinDir = defined('ROOT_PATH') ? ROOT_PATH . '/admin/skins/' : dirname(__DIR__) . '/skins/';
$defaultSkinFile = $skinDir . 'default.json';

if (file_exists($defaultSkinFile)) {
    $jsonDefaults = json_decode(file_get_contents($defaultSkinFile), true);
    if (is_array($jsonDefaults)) {
        if (isset($jsonDefaults['font_url']) && function_exists('grinds_url_to_view')) {
            $jsonDefaults['font_url'] = grinds_url_to_view($jsonDefaults['font_url']);
        }
        if (isset($jsonDefaults['media_bg_css']) && function_exists('grinds_url_to_view')) {
            $jsonDefaults['media_bg_css'] = grinds_url_to_view($jsonDefaults['media_bg_css']);
        }

        $defaults = array_replace_recursive($defaults, $jsonDefaults);
    }
}

// Provide fallback defaults
if (empty($defaults)) {
    $defaults = [
        'font_url' => '',
        'font'     => 'sans-serif',
        'font_heading' => 'inherit',
        'font_size_base' => '0.875rem',
        'cursor'   => 'auto',
        'sidebar_custom_css' => '',
        'texture' => '',
        'media_bg_css' => '',
        'nav_style' => 'underline',
        'rounded' => '0.5rem',
        'btn_radius' => '0.5rem',
        'add_block_radius' => '0px',
        'shadow' => '0 1px 3px 0 rgba(0, 0, 0, 0.1)',
        'border_width' => '1px',
        'input_padding' => '0.5rem 1rem',
        'scrollbar_width' => '10px',
        'modal_overlay_opacity' => '0.6',
        'sidebar_crt_opacity' => '0.05',
        'sidebar_crt_shadow_x' => '-15px',
        'sidebar_crt_shadow_blur' => '30px',
        'sidebar_crt_shadow_opacity' => '0.9',
        'sidebar_crt_border_width' => '1px',
        'sidebar_crt_border_opacity' => '0.2',
        'colors'   => [
            'bg' => '#f8fafc',
            'surface' => '#ffffff',
            'text' => '#334155',
            'border' => '#e2e8f0',
            'modal_overlay' => '#000000',
            'sidebar' => '#1e293b',
            'sidebar_text' => '#ffffff',
            'sidebar_active_bg' => '#2563eb',
            'sidebar_active_text' => '#ffffff',
            'sidebar_hover_bg' => '#334155',
            'sidebar_hover_text' => '#ffffff',
            'sidebar_border' => '#334155',
            'sidebar_input_bg' => '#334155',
            'sidebar_input_border' => '#475569',
            'primary' => '#2563eb',
            'on_primary' => '#ffffff',
            'secondary' => '#64748b',
            'on_secondary' => '#ffffff',
            'success' => '#22c55e',
            'on_success' => '#ffffff',
            'success_bg' => '#dcfce7',
            'success_text' => '#166534',
            'danger' => '#ef4444',
            'on_danger' => '#ffffff',
            'danger_bg' => '#fee2e2',
            'danger_text' => '#991b1b',
            'warning' => '#eab308',
            'on_warning' => '#ffffff',
            'warning_bg' => '#fefce8',
            'warning_text' => '#854d0e',
            'info' => '#0ea5e9',
            'on_info' => '#ffffff',
            'info_bg' => '#e0f2fe',
            'info_text' => '#075985',
            'input_bg' => '#ffffff',
            'input_text' => '#334155',
            'input_placeholder' => '#94a3b8',
            'input_border' => '#e2e8f0',
            'table_header_bg' => '#f8fafc',
            'table_header_text' => '#334155',
            'table_row_hover_bg' => '#f1f5f9',
            'scrollbar_track' => '#f1f5f9',
            'scrollbar_thumb' => '#cbd5e1',
            'status_draft' => '#f1f5f9',
            'status_published' => '#dcfce7',
            'status_pending' => '#fef9c3',
            'status_private' => '#e0e7ff',
            'status_trash' => '#fee2e2',
            'media_checker_1' => '#e2e8f0',
            'media_checker_2' => '#f8fafc',
            'media_ring' => '#2563eb',
            'sidebar_crt' => '#33ff33',
            'sidebar_crt_shadow' => '#000000',
            'sidebar_crt_border' => '#33ff33',
        ],
    ];
}

return $defaults;
