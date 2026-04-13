<?php

/**
 * Manage application options
 * Handle database-driven settings and configurations.
 */
if (!defined('GRINDS_APP')) exit;

// Global cache for autoloaded options
$GLOBALS['_grinds_options_cache'] = null;

/**
 * Load autoload options into cache.
 */
function _load_all_options()
{
    $pdo = App::db();
    if (!$pdo) return;

    try {
        // Check if autoload column exists (for backward compatibility during migration)
        // We assume it exists if we are running this code, but a try-catch is safer.
        $stmt = $pdo->query("SELECT key, value FROM settings WHERE autoload = 1");
        $GLOBALS['_grinds_options_cache'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {
        // Fallback if column doesn't exist or other error
        $GLOBALS['_grinds_options_cache'] = [];
    }
}

/** Retrieve option value. */
if (!function_exists('get_option')) {
    function get_option($key, $default = '')
    {
        $pdo = App::db();
        if (!$pdo) return $default;

        // Initialize cache if needed
        if ($GLOBALS['_grinds_options_cache'] === null) {
            _load_all_options();
        }

        // Check cache first
        if (array_key_exists($key, $GLOBALS['_grinds_options_cache'])) {
            $cachedVal = $GLOBALS['_grinds_options_cache'][$key];
            return ($cachedVal !== false) ? $cachedVal : $default;
        }

        // If not in cache, query DB directly (for non-autoload options)
        try {
            $stmt = $pdo->prepare("SELECT value FROM settings WHERE key = ?");
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();

            // Cache the raw database result (false if not found) to prevent redundant queries
            $GLOBALS['_grinds_options_cache'][$key] = $value;

            return ($value !== false) ? $value : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}

/** Update or insert option value. */
if (!function_exists('update_option')) {
    function update_option($key, $value, $autoload = null)
    {
        $pdo = App::db();
        if (!$pdo) return false;

        $ownsTransaction = false;
        try {
            // Start transaction to prevent race conditions
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $ownsTransaction = true;
            }

            // 1. Check if the record exists
            $stmtCheck = $pdo->prepare("SELECT 1 FROM settings WHERE key = ?");
            $stmtCheck->execute([$key]);
            $exists = $stmtCheck->fetchColumn();

            if ($exists) {
                // 2. If record exists, UPDATE it
                if ($autoload !== null) {
                    $autoloadVal = $autoload ? 1 : 0;
                    $stmt = $pdo->prepare("UPDATE settings SET value = ?, autoload = ? WHERE key = ?");
                    $result = $stmt->execute([$value, $autoloadVal, $key]);
                } else {
                    $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE key = ?");
                    $result = $stmt->execute([$value, $key]);
                }
            } else {
                // 3. If record does not exist, INSERT it
                $autoloadVal = ($autoload !== null) ? ($autoload ? 1 : 0) : 1; // Default to 1 (autoload)
                $stmt = $pdo->prepare("INSERT INTO settings (key, value, autoload) VALUES (?, ?, ?)");
                $result = $stmt->execute([$key, $value, $autoloadVal]);
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }

            // Update cache if successful
            if ($result && isset($GLOBALS['_grinds_options_cache'])) {
                $GLOBALS['_grinds_options_cache'][$key] = $value;
            }

            return $result;
        } catch (Exception $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Log the error for debugging
            error_log("GrindsCMS update_option Error: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Get default SNS share buttons configuration.
 *
 * @return array
 */
if (!function_exists('get_default_share_buttons')) {
    function get_default_share_buttons()
    {
        return [
            ['id' => 'x', 'name' => 'X (Twitter)', 'url' => 'https://twitter.com/intent/tweet?url={URL}&text={TITLE}', 'icon' => 'icon-twitter-x', 'color' => '#000000', 'enabled' => true],
            ['id' => 'facebook', 'name' => 'Facebook', 'url' => 'https://www.facebook.com/sharer/sharer.php?u={URL}', 'icon' => 'icon-facebook', 'color' => '#1877F2', 'enabled' => true],
            ['id' => 'line', 'name' => 'LINE', 'url' => 'https://social-plugins.line.me/lineit/share?url={URL}', 'icon' => 'icon-line', 'color' => '#06C755', 'enabled' => true],
            ['id' => 'instagram', 'name' => 'Instagram', 'url' => 'https://www.instagram.com/', 'icon' => 'icon-instagram', 'color' => '#E4405F', 'enabled' => true],
            ['id' => 'discord', 'name' => 'Discord', 'url' => 'https://discord.com/', 'icon' => 'icon-discord', 'color' => '#5865F2', 'enabled' => true],
            ['id' => 'threads', 'name' => 'Threads', 'url' => 'https://threads.net/intent/post?text={title}%20{url}', 'icon' => 'icon-threads', 'color' => '#000000', 'enabled' => false],
            ['id' => 'tiktok', 'name' => 'TikTok', 'url' => '', 'icon' => 'icon-tiktok', 'color' => '#000000', 'enabled' => false],
            ['id' => 'linkedin', 'name' => 'LinkedIn', 'url' => 'https://www.linkedin.com/sharing/share-offsite/?url={url}', 'icon' => 'icon-linkedin', 'color' => '#0A66C2', 'enabled' => false],
            ['id' => 'pinterest', 'name' => 'Pinterest', 'url' => 'https://pinterest.com/pin/create/button/?url={url}&description={title}', 'icon' => 'icon-pinterest', 'color' => '#E60023', 'enabled' => false],
            ['id' => 'github', 'name' => 'GitHub', 'url' => '', 'icon' => 'icon-github', 'color' => '#181717', 'enabled' => false],
            ['id' => 'twitch', 'name' => 'Twitch', 'url' => '', 'icon' => 'icon-twitch', 'color' => '#9146FF', 'enabled' => false]
        ];
    }
}

/**
 * Generate DB key for skin setting.
 */
if (!function_exists('grinds_generate_skin_key')) {
    function grinds_generate_skin_key($key, $parent = null)
    {
        if ($parent === 'colors') {
            return 'custom_skin_' . str_replace('-', '_', $key);
        }
        if ($key === 'font') return 'custom_skin_font_family';
        return 'custom_skin_' . $key;
    }
}

/**
 * Map skin configuration to flat DB options array.
 */
if (!function_exists('grinds_map_skin_keys')) {
    function grinds_map_skin_keys($skinConfig)
    {
        $mapped = [];
        foreach ($skinConfig as $key => $val) {
            if ($key === 'colors' && is_array($val)) {
                foreach ($val as $cKey => $cVal) {
                    $mapped[grinds_generate_skin_key($cKey, 'colors')] = $cVal;
                }
            } elseif ($key !== 'css') {
                $mapped[grinds_generate_skin_key($key)] = $val;
            }
        }
        return $mapped;
    }
}

/**
 * Get all system settings with default values.
 *
 * @return array
 */
if (!function_exists('grinds_get_default_settings')) {
    function grinds_get_default_settings($lang = null)
    {
        if ($lang === null) {
            $lang = defined('SITE_LANG') ? SITE_LANG : 'en';
        }

        $isJa = ($lang === 'ja');
        $cmsName = defined('CMS_NAME') ? CMS_NAME : 'GrindSite';

        // Load share buttons default
        $defaultShareButtons = function_exists('get_default_share_buttons') ? get_default_share_buttons() : [];

        // Load skin defaults dynamically to avoid triple management
        $skinSettings = [];
        $skinConfigPath = defined('ROOT_PATH') ? ROOT_PATH . '/admin/config/skin_defaults.php' : dirname(dirname(__DIR__)) . '/admin/config/skin_defaults.php';

        if (file_exists($skinConfigPath)) {
            $skinConfig = require $skinConfigPath;
            if (is_array($skinConfig)) {
                $skinSettings = grinds_map_skin_keys($skinConfig);
            }
        }

        $baseSettings = [
            // General
            'site_name' => $cmsName,
            'admin_title' => '',
            'admin_logo' => '',
            'admin_show_site_name' => '0',
            'admin_show_logo_login' => '1',
            'disable_external_assets' => '0',
            'site_description' => $isJa ? $cmsName . 'で作られたサイトです。' : 'A site built with ' . $cmsName . '.',
            'site_footer_text' => '© ' . date('Y') . ' ' . $cmsName . '.',
            'site_ogp_image' => '',
            'site_favicon' => '',
            'site_noindex' => '0',
            'site_block_ai' => '0',
            'license_key' => '',

            // Display
            'posts_per_page' => '10',
            'title_format' => '{page_title} - {site_name}',
            'date_format' => ($isJa ? 'Y年m月d日' : 'Y-m-d'),
            'site_lang' => $lang,
            'timezone' => ($isJa ? 'Asia/Tokyo' : 'UTC'),
            'editor_debounce_time' => '1000',

            // Theme
            'site_theme' => 'default',
            'admin_skin' => 'default',
            'admin_layout' => 'sidebar',
            'media_max_width' => '1920',
            'media_quality' => '85',
        ];

        $otherSettings = [
            // Mail
            'smtp_host' => '',
            'smtp_user' => '',
            'smtp_pass' => '',
            'smtp_port' => '587',
            'smtp_encryption' => 'tls',
            'smtp_from' => '',
            'smtp_admin_email' => '',
            'contact_recipient_email' => '',
            'contact_subjects' => $isJa ? "製品について\n採用について\nその他" : "Product\nRecruitment\nOther",
            'contact_success_msg' => $isJa ? "お問い合わせを受け付けました。\n担当者より順次ご返信いたしますので、しばらくお待ちください。" : "Your inquiry has been sent successfully.\nWe will get back to you shortly.",
            'contact_autoreply_subject' => $isJa ? '[{site_name}] お問い合わせありがとうございます' : '[{site_name}] Thank you for your inquiry',
            'contact_autoreply_body' => $isJa ? "{name} 様\n\nお問い合わせありがとうございます。\n以下の内容で受け付けました。\n\n{form_details}\n\n後ほど担当者よりご連絡いたします。" : "Dear {name},\n\nThank you for your inquiry. We have received the following:\n\n{form_details}\n\nWe will get back to you shortly.",

            // Security
            'session_timeout' => '1800',
            'security_max_attempts' => '5',
            'security_lockout_time' => '15',
            'secure_preview_mode' => '0',
            'preview_shared_password' => '',
            'iframe_allowed_domains' => '',

            // Integration
            'google_analytics_id' => '',
            'custom_head_scripts' => '',
            'custom_footer_scripts' => '',
            'share_buttons' => $defaultShareButtons,

            // System
            'debug_mode' => '0',
            'trust_proxies' => '0',
            'trusted_proxy_ips' => '',
            'backup_retention_limit' => '10',
            'login_backup_frequency' => '10',
            'backup_zip_password' => '',
            'db_version' => defined('GRINDS_DB_SCHEMA_VERSION') ? constant('GRINDS_DB_SCHEMA_VERSION') : 8,
            'system_base_url' => '',
            'ssg_search_chunk_size' => '500',
        ];

        return array_merge($baseSettings, $skinSettings, $otherSettings);
    }
}
