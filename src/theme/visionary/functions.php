<?php
if (!defined('GRINDS_APP')) exit;

if (!function_exists('theme_t')) {
    function theme_t($key)
    {
        static $texts = null;
        if ($texts === null) {
            $lang = function_exists('get_option') ? get_option('site_lang', 'ja') : 'ja';
            $file = __DIR__ . "/lang/{$lang}.php";
            if (!file_exists($file)) {
                $file = __DIR__ . "/lang/en.php";
            }
            $texts = file_exists($file) ? require($file) : [];
        }
        return $texts[$key] ?? (function_exists('_t') ? _t($key) : $key);
    }
}
