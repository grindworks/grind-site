<?php

/**
 * heartbeat.php
 *
 * API endpoint to keep the session alive and return the current CSRF token.
 */
require_once __DIR__ . '/api_bootstrap.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

json_response([
    'success' => true,
    'csrf_token' => $_SESSION['csrf_token'] ?? '',
    'timestamp' => time()
]);
