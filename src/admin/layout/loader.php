<?php

/**
 * loader.php
 *
 * Dynamically load the selected admin layout (sidebar or topbar).
 */
if (!defined('GRINDS_APP'))
  exit;

// Get layout setting (user preference vs global setting)
$layout = get_option('admin_layout', 'sidebar');
$currentUser = App::user();
if ($currentUser && isset($currentUser['id'])) {
  $pdo = App::db();
  if ($pdo) {
    try {
      $stmt = $pdo->prepare("SELECT admin_layout FROM users WHERE id = ?");
      $stmt->execute([$currentUser['id']]);
      $userLayout = $stmt->fetchColumn();
      if (!empty($userLayout) && $userLayout !== 'system') {
        $layout = $userLayout;
      }
    }
    catch (Exception $e) {
    // Ignore and fallback
    }
  }
}

// Sanitize layout name
if (!is_string($layout) || !preg_match('/^[a-z0-9_]+$/', $layout)) {
  $layout = 'sidebar';
}

// Define layout path
$layoutFile = __DIR__ . '/' . $layout . '.php';

// Fallback to default
if (!file_exists($layoutFile)) {
  $layoutFile = __DIR__ . '/sidebar.php';
}

// Load layout
require_once $layoutFile;
