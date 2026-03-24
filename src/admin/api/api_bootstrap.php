<?php

/**
 * api_bootstrap.php
 *
 * Common initialization for Admin API endpoints.
 * Handle configuration loading, authentication, and system initialization.
 */

ob_start();

// Prevent caching of API responses
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

// Disable error display for JSON output to prevent invalid JSON
ini_set('display_errors', 0);
error_reporting(0);

define('GRINDS_APP', true);

// Load core dependencies
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/functions.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';

// Enable error reporting if in debug mode, but keep display_errors OFF for API to prevent JSON corruption
if ((defined('DEBUG_MODE') && DEBUG_MODE) || (function_exists('get_option') && get_option('debug_mode'))) {
    ini_set('log_errors', 1);
    error_reporting(E_ALL);
}

// Check POST size limits globally for APIs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (function_exists('grinds_check_post_max_size')) {
            grinds_check_post_max_size();
        }
    } catch (Exception $e) {
        json_response(['success' => false, 'error' => $e->getMessage()], 413);
    }
}

// Start session (Centralized session management)
if (function_exists('_safe_session_start')) {
    _safe_session_start();
}

// Initialize system (timezone, settings, etc.)
if (!defined('GRINDS_SYSTEM_INIT_DONE')) {
    init_system();
    do_action('grinds_init');
    define('GRINDS_SYSTEM_INIT_DONE', true);
}

// Enforce authentication
// Includes timeout check and handles AJAX response automatically
require_login();
