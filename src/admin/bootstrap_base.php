<?php

/**
 * bootstrap_base.php
 *
 * Shared initialization logic for admin panel and login page.
 */
if (!defined('GRINDS_APP')) {
    define('GRINDS_APP', true);
}

// Check system permissions
$rootDir = dirname(__DIR__);

// Boot check
if (file_exists($rootDir . '/lib/boot_check.php')) {
    require_once $rootDir . '/lib/boot_check.php';
}

// Load admin menu configuration
require_once __DIR__ . '/admin_menu.php';
require_once $rootDir . '/lib/functions.php';

// Start session (must be called before any $_SESSION access)
if (function_exists('_safe_session_start')) {
    _safe_session_start();
}

// Enable database migrations
define('GRINDS_ENABLE_MIGRATIONS', true);
require_once $rootDir . '/lib/db.php';

// Initialize logger
if (class_exists('GrindsLogger')) {
    GrindsLogger::init();
}

// Apply system settings
if (!defined('GRINDS_SYSTEM_INIT_DONE')) {
    if (function_exists('init_system')) {
        init_system();
    }
    do_action('grinds_init');
    define('GRINDS_SYSTEM_INIT_DONE', true);
}

// Set security headers for admin area
if (!headers_sent()) {
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()");

    // Enterprise Hardening: Send HSTS only when SSL is active to prevent lock-outs on local dev.
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    if ((function_exists('is_ssl') && is_ssl()) || $isHttps) {
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
    }

    // Secure cache headers for Admin area (Prevents back-button sensitive data leak)
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
}
