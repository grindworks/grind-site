<?php

/**
 * menus.php
 *
 * Manage navigation menu items.
 */
require_once __DIR__ . '/bootstrap.php';

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) {
  exit;
}

// Initialize variables
$params = Routing::getParams();
$edit_id = Routing::getString($params, 'edit_id');
if ($edit_id === '') $edit_id = null;
$current_location = Routing::getString($params, 'location', 'header');

// Validate location
if (!in_array($current_location, ['header', 'footer'])) {
  $current_location = 'header';
}

// Generate URL
function _grinds_menu_list_url($location)
{
  return 'menus.php?location=' . urlencode($location);
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Handle bulk actions
  if (isset($_POST['bulk_action'])) {
    try {
      $location = $_POST['location'] ?? 'header';
      $targetIds = grinds_get_bulk_target_ids($_POST);

      $actionType = $_POST['bulk_action'];
      $count = 0;

      $pdo->beginTransaction();

      if ($actionType === 'delete') {
        $count = grinds_delete_records($pdo, 'nav_menus', $targetIds);
        set_flash(_t('msg_deleted_count', $count));
      }

      $pdo->commit();

      if (function_exists('clear_page_cache')) {
        clear_page_cache();
      }

      redirect('admin/' . _grinds_menu_list_url($location));
    } catch (Exception $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      $error = $e->getMessage();
    }
  }

  // Save orders
  elseif (isset($_POST['action']) && $_POST['action'] === 'save_orders') {
    try {
      $location = $_POST['location'] ?? 'header';
      if (isset($_POST['orders']) && is_array($_POST['orders'])) {
        $pdo->beginTransaction();
        try {
          $stmt = $pdo->prepare("UPDATE nav_menus SET sort_order = ? WHERE id = ?");
          foreach ($_POST['orders'] as $id => $order) {
            $stmt->execute([(int)$order, (int)$id]);
          }
          $pdo->commit();
        } catch (Exception $e) {
          $pdo->rollBack();
          throw $e;
        }
        set_flash(_t('msg_saved'));
        if (function_exists('clear_page_cache'))
          clear_page_cache();
      }
      redirect('admin/' . _grinds_menu_list_url($location));
    } catch (Exception $e) {
      $error = $e->getMessage();
    }
  }

  // Handle create/update
  else {
    try {
      $label = trim(Routing::getString($_POST, 'label'));
      $url = Routing::convertToDbUrl(trim(Routing::getString($_POST, 'url')));
      $sort_order = (int)($_POST['sort_order'] ?? 0);
      $is_external = isset($_POST['is_external']) ? 1 : 0;
      $location = Routing::getString($_POST, 'location', 'header');
      $target_theme = Routing::getString($_POST, 'target_theme', 'all');
      $target_id = Routing::getString($_POST, 'target_id');

      if (empty($label) || empty($url)) {
        throw new Exception(_t('req_label_url'));
      }

      if (preg_match('/^\s*javascript:/i', $url)) {
        throw new Exception(_t('invalid_url_format'));
      }

      if ($target_id) {
        // Update item
        $stmt = $pdo->prepare("UPDATE nav_menus SET label=?, url=?, sort_order=?, is_external=?, location=?, target_theme=? WHERE id=?");
        $stmt->execute([$label, $url, $sort_order, $is_external, $location, $target_theme, $target_id]);
        set_flash(_t('msg_saved'));
        $redirect_url = 'admin/menus.php?location=' . urlencode($location) . '&edit_id=' . $target_id;
      } else {
        // Insert item
        $stmt = $pdo->prepare("INSERT INTO nav_menus (label, url, sort_order, is_external, location, target_theme) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$label, $url, $sort_order, $is_external, $location, $target_theme]);
        set_flash(_t('msg_saved'));
        $redirect_url = 'admin/' . _grinds_menu_list_url($location);
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

// Fetch menus
$menus = [];
$pages_list = [];
$categories_list = [];
$tags_list = [];
try {
  // Fetch menus
  $stmt = $pdo->prepare("SELECT * FROM nav_menus WHERE location = ? ORDER BY sort_order ASC, id ASC");
  $stmt->execute([$current_location]);
  $menus = $stmt->fetchAll();

  // Fetch pages
  $repo = new PostRepository($pdo);
  $pages_list = $repo->fetch([
    'type' => 'page',
    'status' => 'published'
  ], 0, 0, 'p.title ASC');

  // Fetch categories
  $stmtCats = $pdo->query("SELECT name, slug FROM categories ORDER BY sort_order ASC");
  $categories_list = $stmtCats->fetchAll();

  // Fetch tags
  $stmtTags = $pdo->query("SELECT name, slug FROM tags ORDER BY name ASC");
  $tags_list = $stmtTags->fetchAll();
} catch (Exception $e) {
  $error = "Database error: " . $e->getMessage();
}

// Fetch themes
$themes = array_merge(['all' => _t('cond_all')], get_available_themes());

// Prepare edit data
$edit_data = [
  'label' => '',
  'url' => '',
  'sort_order' => 0,
  'is_external' => 0,
  'location' => $current_location,
  'target_theme' => 'all'
];

if ($edit_id && !empty($menus)) {
  foreach ($menus as $m) {
    if ($m['id'] == $edit_id) {
      $edit_data = $m;
      // Convert URL
      $edit_data['url'] = grinds_url_to_view($edit_data['url']);
      break;
    }
  }
}

// Restore input data on error
if (!empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['bulk_action']) && ($_POST['action'] ?? '') !== 'save_orders') {
  $edit_data['label'] = $_POST['label'] ?? $edit_data['label'];
  $edit_data['url'] = $_POST['url'] ?? $edit_data['url'];
  $edit_data['sort_order'] = (int)($_POST['sort_order'] ?? $edit_data['sort_order']);
  $edit_data['is_external'] = isset($_POST['is_external']) ? 1 : 0;
  $edit_data['target_theme'] = $_POST['target_theme'] ?? $edit_data['target_theme'];
}

// Render view
$page_title = _t('menu_menus');
$current_page = 'menus';

ob_start();
require_once __DIR__ . '/layout/toast.php';
require_once __DIR__ . '/views/menus.php';
$content = ob_get_clean();

require_once __DIR__ . '/layout/loader.php';
