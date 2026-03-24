<?php

/**
 * run_gc.php
 * Asynchronous system garbage collection endpoint.
 */

require_once __DIR__ . '/api_bootstrap.php';

// Check permissions
if (!current_user_can('manage_settings') && !current_user_can('manage_tools')) {
    json_response(['success' => true, 'skipped' => true, 'reason' => 'no_permission']);
}

// Enforce POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

// Verify CSRF token
check_csrf_token();

// Process garbage collection only with a 20% probability to reduce background load
if (mt_rand(1, 100) > 20) {
    json_response(['success' => true, 'skipped' => true]);
}

// Prevent timeout on heavy directories
if (function_exists('grinds_set_high_load_mode')) {
    grinds_set_high_load_mode();
}

// Release session lock so other Ajax requests aren't blocked during the scan
session_write_close();

try {
    if (function_exists('grinds_run_garbage_collection')) {
        grinds_run_garbage_collection();
    }
    json_response(['success' => true, 'skipped' => false]);
} catch (Exception $e) {
    json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
