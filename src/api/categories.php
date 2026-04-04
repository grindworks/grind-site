<?php

/**
 * categories.php
 *
 * Deliver a list of categories in JSON format.
 * Useful for building navigation menus or category filters in headless applications.
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
header("Cache-Control: public, max-age=300");

// Disable errors
ini_set('display_errors', 0);
error_reporting(0);

try {
    $pdo = App::db();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Fetch categories (assuming a standard 'categories' table structure)
    $stmt = $pdo->query("SELECT id, name, slug FROM categories ORDER BY id ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $categories], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    http_response_code(500);
    // For debugging, you might output $e->getMessage() instead of 'Server Error'
    echo json_encode(['success' => false, 'error' => 'Server Error']);
}
