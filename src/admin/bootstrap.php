<?php

/**
 * bootstrap.php
 *
 * Initialize admin panel environment.
 */

// Load base bootstrap
require_once __DIR__ . '/bootstrap_base.php';
$rootDir = ROOT_PATH;

// Ensure admin area is strictly not indexed
require_once $rootDir . '/lib/functions/system.php';
grinds_send_noindex_headers();

// Load libraries
require_once $rootDir . '/lib/auth.php';
require_once $rootDir . '/lib/paginator.php';
require_once $rootDir . '/lib/sorter.php';

// Enforce auth and CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    grinds_check_post_max_size();
  } catch (Exception $e) {
    $msg = $e->getMessage();
    if (function_exists('is_ajax_request') && is_ajax_request()) {
      json_response(['success' => false, 'error' => $msg], 413);
    } else {
      die('Error: ' . htmlspecialchars($msg));
    }
  }
}

require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf_token();
}

// Check RBAC permissions
$script_name = basename($_SERVER['SCRIPT_NAME']);
$access_map = [
  'categories.php' => 'manage_categories',
  'tags.php' => 'manage_categories',
  'menus.php' => 'manage_menus',
  'widgets.php' => 'manage_widgets',
  'banners.php' => 'manage_banners',
  'static_export.php' => 'manage_tools',
];

if (isset($access_map[$script_name])) {
  if (!current_user_can($access_map[$script_name])) {
    error_log("Access Denied: User {$_SESSION['username']} tried to access {$script_name} without {$access_map[$script_name]} capability.");
    redirect('admin/index.php');
  }
}

// Handle settings permissions
if ($script_name === 'settings.php') {
  $params = Routing::getParams();
  $tab = $params['tab'] ?? 'general';
  if ($tab !== 'profile' && !current_user_can('manage_settings')) {
    redirect('admin/settings.php?tab=profile');
  }
}

// Get admin menu
$admin_menu = get_admin_menu();

// Initialize flash messages
$message = '';
$error = '';

if (function_exists('get_flash')) {
  $flash = get_flash();
  if ($flash) {
    if ($flash['type'] === 'success')
      $message = $flash['msg'];
    else
      $error = $flash['msg'];
  }
}

// Load active theme functions for admin hooks
grinds_load_theme_functions();
