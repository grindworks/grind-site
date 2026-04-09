<?php

/**
 * preview_html.php
 *
 * API endpoint to render and preview raw HTML/Shortcodes in the admin editor.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Check permissions
if (!current_user_can('manage_posts')) {
    json_response(['success' => false, 'error' => 'Forbidden'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

try {
    check_csrf_token();
    $input = get_json_input();

    // The raw HTML code to preview
    $rawCode = $input['code'] ?? '';

    if (empty(trim($rawCode))) {
        json_response(['success' => true, 'html' => '']);
    }

    // Process the code exactly as it would be processed on the frontend.

    // 1. Resolve URLs
    $resolved = grinds_url_to_view($rawCode);

    // 2. Apply frontend content filters (this expands shortcodes like [amazon], [youtube], etc.)
    if (function_exists('apply_filters')) {
        $resolved = apply_filters('grinds_the_content', $resolved);
    }

    // 3. If the user doesn't have unfiltered_html, sanitize it for safety (same as save logic)
    if (!current_user_can('unfiltered_html')) {
        $resolved = grinds_sanitize_html($resolved);
    }

    json_response([
        'success' => true,
        'html' => $resolved
    ]);
} catch (Exception $e) {
    json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
