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

// Load core configuration
require_once __DIR__ . '/config.php';
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
}
