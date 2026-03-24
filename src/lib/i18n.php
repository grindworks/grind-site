<?php

declare(strict_types=1);

/**
 * Manage language files and translations.
 */
if (!defined('GRINDS_APP')) exit;

final class I18n
{
    private static array $messages = [];

    private function __construct() {}

    /** Initialize language system. */
    public static function init($lang = 'ja')
    {
        // Sanitize language code
        $lang = preg_replace('/[^a-zA-Z0-9]/', '', $lang);

        // Load language file
        $file = __DIR__ . "/lang/{$lang}.php";
        if (!file_exists($file)) {
            $file = __DIR__ . "/lang/en.php";
        }

        self::$messages = file_exists($file) ? require $file : [];
    }

    /** Retrieve translated string. */
    public static function get($key, $params = [])
    {
        $text = self::$messages[$key] ?? $key;

        if (!empty($params)) {
            if (!is_array($params)) $params = [$params];
            try {
                $result = vsprintf($text, $params);
                return $result !== false ? $result : $text;
            } catch (Throwable $e) {
                return $text;
            }
        }

        return $text;
    }
}

/** Global translation helper. */
if (!function_exists('_t')) {
    function _t($key, ...$params)
    {
        // Handle backward compatibility
        if (isset($params[0]) && is_array($params[0]) && count($params) === 1) {
            $params = $params[0];
        }
        return I18n::get($key, $params);
    }
}
