<?php

/**
 * dismiss_alert.php
 *
 * API endpoint to dismiss admin alerts stored in the session.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Check permissions
if (!current_user_can('manage_settings') && !current_user_can('manage_tools')) {
  json_response(['success' => false, 'error' => 'Forbidden'], 403);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Validate CSRF token
  check_csrf_token();

  $alertId = $_POST['alert_id'] ?? '';

  if ($alertId === 'migration') {
    // Clear migration alert
    if (isset($_SESSION['migration_alert'])) {
      unset($_SESSION['migration_alert']);
    }
  } elseif ($alertId === 'backup') {
    // Clear backup skipped warning
    if (isset($_SESSION['backup_skipped_warning'])) {
      unset($_SESSION['backup_skipped_warning']);
    }
  } elseif ($alertId === 'nginx') {
    // Create .nginx_confirmed file to suppress the warning permanently
    $dataDir = ROOT_PATH . '/data';
    if (!is_dir($dataDir)) {
      @mkdir($dataDir, 0775, true);
    }
    @file_put_contents($dataDir . '/.nginx_confirmed', date('Y-m-d H:i:s'));
  }

  json_response(['success' => true]);
}

json_response(['success' => false, 'error' => 'Method Not Allowed'], 405);
