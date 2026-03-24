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
        'cursor'   => 'auto',
        'sidebar_custom_css' => '',
        'colors'   => [
            'bg'      => '#f8fafc',
            'text'    => '#334155',
            'primary' => '#2563eb',
            'border'  => '#e2e8f0',
        ],
        'rounded'  => '0.5rem',
    ];
}

return $defaults;
