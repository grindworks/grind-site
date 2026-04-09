<?php

/**
 * settings.php
 *
 * Deliver global site settings in JSON format.
 * Useful for rendering headers, footers, and meta tags in headless applications.
 */

// Define app constant
define('GRINDS_APP', true);

// Load bootstrap
if (!require_once __DIR__ . '/../lib/bootstrap_public.php') {
    http_response_code(404);
    exit;
}

// Set headers
$allowed_origin = defined('API_ALLOWED_ORIGIN') ? constant('API_ALLOWED_ORIGIN') : '*';
header("Access-Control-Allow-Origin: $allowed_origin");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Max-Age: 86400");
    http_response_code(200);
    exit;
}

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: public, max-age=3600"); // Settings change rarely, so caching is effective

// Disable errors
ini_set('display_errors', 0);
error_reporting(0);

try {
    // Fetch global settings using the core get_option function
    $data = [
        'site_name' => function_exists('get_option') ? get_option('site_name', 'GrindSite') : 'GrindSite',
        'site_desc' => function_exists('get_option') ? get_option('site_desc', '') : '',
        'site_lang' => function_exists('get_option') ? get_option('site_lang', 'en') : 'en',
        // Add any other necessary options here (e.g., logo URL, SNS links)
    ];

    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server Error']);
}
