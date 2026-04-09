<?php

/**
 * Initialize public-facing scripts.
 */

// Remove PHP version exposure header early in the lifecycle
if (!headers_sent()) {
    header_remove('X-Powered-By');
}

if (!defined('GRINDS_APP')) {
    define('GRINDS_APP', true);
}

// Prevent recursive initialization
if (defined('GRINDS_SYSTEM_INIT_DONE')) {
    return true;
}

$rootDir = dirname(__DIR__);

// Check installation
if (!file_exists($rootDir . '/config.php')) {
    return false;
}

require_once $rootDir . '/config.php';
require_once $rootDir . '/lib/functions.php';
require_once $rootDir . '/lib/db.php';

if (function_exists('init_system')) {
    init_system();
}

return true;
