<?php

/**
 * banners.php
 *
 * Manage banners.
 */
require_once __DIR__ . '/bootstrap.php';

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) {
  exit;
}

// Define position labels.
$pos_labels = [
  'header_top' => _t('pos_header_top'),
  'content_top' => _t('pos_content_top'),
  'content_bottom' => _t('pos_content_bottom'),
  'footer' => _t('pos_footer'),
];

$banner_positions = $pos_labels;

// Initialize variables
$params = Routing::getParams();
$edit_id = Routing::getString($params, 'edit_id');
if ($edit_id === '') $edit_id = null;

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Handle bulk actions
  if (isset($_POST['bulk_action'])) {
    try {
      $targetIds = grinds_get_bulk_target_ids($_POST);

      $actionType = $_POST['bulk_action'];
      $count = 0;

      $pdo->beginTransaction();

      if ($actionType === 'delete') {
        $count = grinds_delete_records($pdo, 'banners', $targetIds);
        set_flash(_t('msg_deleted_count', $count));
      }

      $pdo->commit();

      if (function_exists('clear_page_cache')) {
        clear_page_cache();
      }

      redirect('admin/' . grinds_get_current_list_url());
    } catch (Exception $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      $error = $e->getMessage();
    }
  }

  // Handle create/update
  elseif (isset($_POST['action']) && $_POST['action'] === 'save') {
    try {
      $position = Routing::getString($_POST, 'position', 'sidebar_top');
      $type = Routing::getString($_POST, 'type', 'image');

      // Decode Base64 content if present (WAF bypass)
      $html_code_val = Routing::getString($_POST, 'html_code');
      if (!empty($_POST['html_code_is_base64']) && $html_code_val !== '') {
        $decoded = base64_decode(str_replace(' ', '+', $html_code_val));
        if ($decoded !== false) {
          $html_code_val = $decoded;
        }
      }

      // Sanitize HTML
      if (!current_user_can('unfiltered_html')) {
        if ($type === 'html' && $html_code_val !== '') {
          $html_code_val = grinds_sanitize_html($html_code_val);
        }
      }

      $html_code = Routing::convertToDbUrl($html_code_val);
      $image_width = (int)($_POST['image_width'] ?? 100);
      if ($image_width < 10)
        $image_width = 10;
      if ($image_width > 100)
        $image_width = 100;

      // Normalize URL
      $link_url = Routing::convertToDbUrl(trim(Routing::getString($_POST, 'link_url')));

      $sort_order = (int)($_POST['sort_order'] ?? 0);
      $is_active = isset($_POST['is_active']) ? 1 : 0;

      // Set targeting
      $target_type = Routing::getString($_POST, 'target_type', 'all');
      if (!in_array($target_type, ['all', 'home', 'category', 'page'])) {
        $target_type = 'all';
      }
      $target_theme = Routing::getString($_POST, 'target_theme', 'all');
      $target_val = 0;

      if ($target_type === 'category') {
        $target_val = (int)($_POST['target_cat_id'] ?? 0);
        if ($target_val <= 0) {
          throw new Exception(_t('err_select_category'));
        }
      } elseif ($target_type === 'page') {
        $target_val = (int)($_POST['target_post_id'] ?? 0);
        if ($target_val <= 0) {
          throw new Exception(_t('err_select_post'));
        }
      }

      $target_id_db = Routing::getString($_POST, 'target_id');
      if ($target_id_db === '') $target_id_db = null;

      // Get current image
      $current_image_db = '';
      if ($target_id_db) {
        $stmtGet = $pdo->prepare("SELECT image_url FROM banners WHERE id = ?");
        $stmtGet->execute([$target_id_db]);
        $current_image_db = $stmtGet->fetchColumn() ?: '';
      }

      // Validate URL
      if (!empty($link_url) && preg_match('/^\s*javascript:/i', $link_url)) {
        throw new Exception(_t('invalid_url_format'));
      }

      $image_path = grinds_process_image_upload($pdo, 'image', $current_image_db);

      // Validate image
      if ($type === 'image' && empty($image_path) && !$target_id_db) {
        throw new Exception(_t('image_required_for_new_banners'));
      }

      // Update/Insert banner
      if ($target_id_db) {
        $stmt = $pdo->prepare("UPDATE banners SET position=?, link_url=?, image_url=?, sort_order=?, is_active=?, target_type=?, target_id=?, type=?, html_code=?, image_width=?, target_theme=? WHERE id=?");
        $stmt->execute([$position, $link_url, $image_path, $sort_order, $is_active, $target_type, $target_val, $type, $html_code, $image_width, $target_theme, $target_id_db]);
        set_flash(_t('msg_saved'));
        $redirect_url = 'admin/banners.php?edit_id=' . $target_id_db;
      } else {
        $stmt = $pdo->prepare("INSERT INTO banners (position, link_url, image_url, sort_order, is_active, target_type, target_id, type, html_code, image_width, target_theme) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$position, $link_url, $image_path, $sort_order, $is_active, $target_type, $target_val, $type, $html_code, $image_width, $target_theme]);
        set_flash(_t('msg_saved'));
        $redirect_url = 'admin/banners.php';
      }

      // Clear page cache
      if (function_exists('clear_page_cache')) {
        clear_page_cache();
      }

      redirect($redirect_url);
    } catch (Exception $e) {
      $msg = $e->getMessage();
      // Check for unique constraint violation from DB
      if (stripos($msg, 'UNIQUE constraint failed') !== false || stripos($msg, 'Duplicate entry') !== false) {
        $error = _t('err_duplicate_entry');
      } else {
        $error = $msg;
      }
    }
  }
}

// Set pagination
$limit = isset($params['limit']) ? (int)$params['limit'] : 20;
$page = isset($params['page']) ? (int)$params['page'] : 1;
$sorter = new Sorter(['id', 'position', 'link_url', 'sort_order', 'is_active'], 'position', 'ASC');

// Fetch banners using the paginator helper
$paginationResult = grinds_paginate_query($pdo, 'banners', $page, $limit, $sorter);
$banners = $paginationResult['data'];
$paginator = $paginationResult['paginator'];

// Fetch options
$cats_list = $pdo->query("SELECT id, name FROM categories ORDER BY sort_order ASC")->fetchAll();
$repo = new PostRepository($pdo);
$posts_list = $repo->fetch([], 200, 0, 'p.created_at DESC');

// Fetch themes
$themes = array_merge(['all' => _t('cond_all')], get_available_themes());

// Prepare edit data
$edit_data = [
  'position' => 'sidebar_top',
  'type' => 'image',
  'html_code' => '',
  'image_width' => 100,
  'link_url' => '',
  'image_url' => '',
  'sort_order' => 0,
  'is_active' => 1,
  'target_type' => 'all',
  'target_id' => 0,
  'target_theme' => 'all'
];

if ($edit_id) {
  $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
  $stmt->execute([$edit_id]);
  $fetched = $stmt->fetch();
  if ($fetched) {
    $edit_data = $fetched;
  }
}

// Render view
$page_title = _t('menu_banners');
$current_page = 'banners';

ob_start();
require_once __DIR__ . '/layout/toast.php';
require_once __DIR__ . '/views/banners.php';
$content = ob_get_clean();

require_once __DIR__ . '/layout/loader.php';
