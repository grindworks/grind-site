<?php

/**
 * media.php
 *
 * Media Library interface.
 */
require_once __DIR__ . '/bootstrap.php';

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) {
    exit;
}

// Render view
$page_title = _t('title_media_library');
$current_page = 'media';

// Fetch filter months
// Use session cache to avoid full table scan on every request
$mediaMonths = [];
$cacheKey = 'grinds_media_months';

if (!empty($_SESSION[$cacheKey]) && !isset($_GET['refresh'])) {
    $mediaMonths = $_SESSION[$cacheKey];
} else {
    try {
        $stmt = $pdo->query("SELECT DISTINCT substr(uploaded_at, 1, 7) as m FROM media WHERE uploaded_at IS NOT NULL ORDER BY m DESC");
        if ($stmt) {
            $mediaMonths = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $_SESSION[$cacheKey] = $mediaMonths;
        }
    } catch (Exception $e) {
        // Ignore empty table
    }
}

ob_start();
require_once __DIR__ . '/layout/toast.php';
require_once __DIR__ . '/views/media.php';
$content = ob_get_clean();

require_once __DIR__ . '/layout/loader.php';
