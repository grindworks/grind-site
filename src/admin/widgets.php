<?php

/**
 * widgets.php
 *
 * Manage sidebar widgets.
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

// Define types
$widget_types = [
  'profile' => _t('wg_profile'),
  'search' => _t('wg_search'),
  'categories' => _t('wg_categories'),
  'tags' => _t('wg_tags'),
  'recent' => _t('wg_recent'),
  'banner' => _t('wg_banner'),
  'html' => _t('wg_html'),
];

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
        $count = grinds_delete_records($pdo, 'widgets', $targetIds);
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
  if (isset($_POST['action']) && $_POST['action'] === 'save') {
    try {
      $type = Routing::getString($_POST, 'type', 'html');
      $title = trim(Routing::getString($_POST, 'title'));

      // Decode Base64 content if present (WAF bypass)
      if (!empty($_POST['content_is_base64']) && !empty($_POST['content'])) {
        $decoded = base64_decode(str_replace(' ', '+', $_POST['content']));
        if ($decoded !== false) {
          $_POST['content'] = $decoded;
        }
      }

      // Sanitize HTML
      if (!current_user_can('unfiltered_html')) {
        if ($type === 'html' && isset($_POST['content'])) {
          $_POST['content'] = grinds_sanitize_html($_POST['content']);
        }
        if ($type === 'profile' && isset($_POST['profile_text'])) {
          $_POST['profile_text'] = grinds_sanitize_html($_POST['profile_text']);
        }
      }

      $content = Routing::convertToDbUrl(Routing::getString($_POST, 'content'));
      $sort_order = (int)($_POST['sort_order'] ?? 0);
      $is_active = isset($_POST['is_active']) ? 1 : 0;
      $target_theme = Routing::getString($_POST, 'target_theme', 'all');
      $target_id = Routing::getString($_POST, 'target_id');

      if (!array_key_exists($type, $widget_types)) {
        throw new Exception(_t('invalid_widget_type'));
      }

      // Prepare settings
      $settings = [];

      if ($type === 'profile') {
        $settings['name'] = trim(Routing::getString($_POST, 'profile_name'));
        $settings['text'] = Routing::convertToDbUrl(Routing::getString($_POST, 'profile_text'));
      } elseif ($type === 'banner') {
        $settings['title'] = trim(Routing::getString($_POST, 'banner_title'));
        $settings['link'] = Routing::convertToDbUrl(trim(Routing::getString($_POST, 'banner_link')));
        $settings['alt'] = trim(Routing::getString($_POST, 'banner_alt'));
      } elseif ($type === 'recent') {
        $settings['limit'] = max(1, (int)($_POST['limit'] ?? 5));
      }

      // Handle upload
      if ($type === 'profile' || $type === 'banner') {
        $imgField = ($type === 'profile') ? 'profile_image' : 'banner_image';

        // Get current image
        $current_image_db = '';
        if ($target_id) {
          $stmtGet = $pdo->prepare("SELECT settings FROM widgets WHERE id = ?");
          $stmtGet->execute([$target_id]);
          $row = $stmtGet->fetch();
          if ($row) {
            $s = json_decode($row['settings'], true);
            $current_image_db = $s['image'] ?? '';
          }
        }

        $settings['image'] = grinds_process_image_upload($pdo, $imgField, $current_image_db);
      }

      $json_settings = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE);

      if ($target_id) {
        // Preserve content
        if ($type !== 'html' && $type !== 'profile') {
          $content = '';
        }

        $stmt = $pdo->prepare("UPDATE widgets SET type=?, title=?, content=?, settings=?, sort_order=?, is_active=?, target_theme=? WHERE id=?");
        $stmt->execute([$type, $title, $content, $json_settings, $sort_order, $is_active, $target_theme, $target_id]);
        set_flash(_t('msg_saved'));
        $redirect_url = 'admin/widgets.php?edit_id=' . $target_id;
      } else {
        $stmt = $pdo->prepare("INSERT INTO widgets (type, title, content, settings, sort_order, is_active, target_theme) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$type, $title, $content, $json_settings, $sort_order, $is_active, $target_theme]);
        set_flash(_t('msg_saved'));
        $redirect_url = 'admin/widgets.php';
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

$search_q = Routing::getString($params, 'q');
$whereSql = '';
$whereParams = [];
if ($search_q !== '') {
  $escaped_q = grinds_escape_like($search_q);
  $whereSql = "WHERE title LIKE ? ESCAPE '\\' OR type LIKE ? ESCAPE '\\'";
  $whereParams = ["%{$escaped_q}%", "%{$escaped_q}%"];
}

// Fetch widgets
$stmt = $pdo->prepare("SELECT * FROM widgets $whereSql ORDER BY sort_order ASC");
$stmt->execute($whereParams);
$widgets = $stmt->fetchAll();

// Prepare edit data
$edit_data = [
  'type' => 'profile',
  'title' => '',
  'content' => '',
  'settings' => [],
  'sort_order' => 0,
  'is_active' => 1,
  'target_theme' => 'all'
];

// Load widget data
if ($edit_id) {
  foreach ($widgets as $w) {
    if ($w['id'] == $edit_id) {
      $edit_data = $w;
      $edit_data['settings'] = json_decode($w['settings'] ?? '{}', true);
      break;
    }
  }
}

// Fetch themes
$themes = array_merge(['all' => _t('cond_all')], get_available_themes());

// Restore input data on error
if (!empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['bulk_action']) && ($_POST['action'] ?? '') === 'save') {
  $edit_data['type'] = $_POST['type'] ?? $edit_data['type'];
  $edit_data['title'] = $_POST['title'] ?? $edit_data['title'];
  $edit_data['content'] = $_POST['content'] ?? $edit_data['content'];
  if (isset($settings)) $edit_data['settings'] = $settings;
  $edit_data['sort_order'] = (int)($_POST['sort_order'] ?? $edit_data['sort_order']);
  $edit_data['is_active'] = isset($_POST['is_active']) ? 1 : 0;
  $edit_data['target_theme'] = $_POST['target_theme'] ?? $edit_data['target_theme'];
}

// Render view
$page_title = _t('menu_widgets');
$current_page = 'widgets';

ob_start();
require_once __DIR__ . '/layout/toast.php';
require_once __DIR__ . '/views/widgets.php';
$content = ob_get_clean();

require_once __DIR__ . '/layout/loader.php';
