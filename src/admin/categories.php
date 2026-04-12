<?php

/**
 * categories.php
 *
 * Manage post categories.
 */
require_once __DIR__ . '/bootstrap.php';

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) {
  exit;
}

// Define reserved slugs
$reserved_slugs = grinds_get_reserved_slugs();

// Ensure default category
$count = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
if ($count == 0) {
  $stmt = $pdo->prepare("INSERT INTO categories (name, slug, sort_order) VALUES (?, ?, ?)");
  $stmt->execute([_t('uncategorized'), 'uncategorized', 1]);
}

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
        $repo = new PostRepository($pdo);
        $reassignId = isset($_POST['reassign_id']) ? (int)$_POST['reassign_id'] : 0;
        $stmtCheck = $pdo->prepare("SELECT slug FROM categories WHERE id = ?");

        $count = grinds_delete_records($pdo, 'categories', $targetIds, [
          'before_delete' => function ($id) use ($stmtCheck, $repo, $reassignId, $pdo) {
            // Skip default category
            $stmtCheck->execute([$id]);
            $cat = $stmtCheck->fetch();
            if ($cat && $cat['slug'] === 'uncategorized') {
              return false;
            }

            // Check associated posts
            if ($repo->count(['category_id' => $id, 'status' => 'all']) > 0) {
              if ($reassignId > 0 && $reassignId !== $id) {
                grinds_reassign_category_posts($pdo, $id, $reassignId);
              } else {
                return false;
              }
            }
            return true;
          }
        ]);

        if ($count === 0 && count($targetIds) > 0) {
          throw new Exception(_t('msg_cant_delete'));
        }

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
  else {
    try {
      $name = trim(Routing::getString($_POST, 'name'));
      $slug = trim(Routing::getString($_POST, 'slug'));
      $sort_order = (int)($_POST['sort_order'] ?? 0);
      $cat_theme = Routing::getString($_POST, 'category_theme');
      $target_id = Routing::getString($_POST, 'target_id');

      // Validate input
      if (empty($name))
        throw new Exception(_t('name_required'));

      // Sanitize slug if provided
      if (!empty($slug)) {
        $slug = sanitize_slug($slug);
      }

      // Generate slug
      if (empty($slug)) {
        $slug = generate_slug($name, null, 'cat-');
      }

      // Check reserved slugs
      if (in_array(strtolower($slug), $reserved_slugs)) {
        throw new Exception(_t('slug_reserved', $slug));
      }

      // Protect default slug
      if ($target_id) {
        $stmt = $pdo->prepare("SELECT slug FROM categories WHERE id = ?");
        $stmt->execute([$target_id]);
        $current = $stmt->fetch();
        if ($current && $current['slug'] === 'uncategorized' && $slug !== 'uncategorized') {
          throw new Exception(_t('default_slug_cannot_change'));
        }
      }

      // Check duplicate slugs
      $slug = grinds_get_unique_slug($pdo, 'categories', $slug, (int)($target_id ?? 0));

      // --- NEW: Custom Fields (Meta Data) Processing ---
      $themeForMeta = !empty($cat_theme) ? $cat_theme : null;
      $customFields = function_exists('grinds_get_theme_custom_fields') ? grinds_get_theme_custom_fields('category', $themeForMeta) : [];
      $rawCatMetaData = $_POST['meta_data'] ?? [];

      $existingMetaData = [];
      if ($target_id) {
        $stmtOldMeta = $pdo->prepare("SELECT meta_data FROM categories WHERE id = ?");
        $stmtOldMeta->execute([$target_id]);
        $oldMetaJson = $stmtOldMeta->fetchColumn();
        if ($oldMetaJson) {
          $decoded = json_decode($oldMetaJson, true);
          if (is_array($decoded)) $existingMetaData = $decoded;
        }
      }
      $metaData = !empty($existingMetaData) ? $existingMetaData : (is_array($_POST['current_meta_data'] ?? []) ? $_POST['current_meta_data'] : []);

      foreach ($customFields as $field) {
        $fName = $field['name'] ?? '';
        $fType = $field['type'] ?? 'text';
        if (!$fName) continue;

        if ($fType === 'image') {
          $uploadFieldName = 'meta_data_' . $fName;
          $currentValInput = $metaData[$fName] ?? '';
          $deleteField = 'delete_' . $uploadFieldName;

          $uploadedUrl = grinds_process_image_upload($pdo, $uploadFieldName, $currentValInput, [
            'post_data' => $_POST,
            'files_data' => $_FILES,
            'throw_error' => true,
            'delete_field' => $deleteField
          ]);
          $metaData[$fName] = Routing::convertToDbUrl($uploadedUrl);
        } elseif ($fType === 'checkbox') {
          $metaData[$fName] = !empty($rawCatMetaData[$fName]) ? '1' : '0';
        } else {
          if (isset($rawCatMetaData[$fName])) {
            $val = strip_tags((string)$rawCatMetaData[$fName]);
            $metaData[$fName] = Routing::convertToDbUrl($val);
          }
        }
      }
      $metaDataJson = json_encode($metaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
      // --------------------------------------------------

      if ($target_id) {
        // Fetch old data
        $stmtOld = $pdo->prepare("SELECT name, slug FROM categories WHERE id = ?");
        $stmtOld->execute([$target_id]);
        $oldData = $stmtOld->fetch();

        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, sort_order = ?, category_theme = ?, meta_data = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $sort_order, $cat_theme, $metaDataJson, $target_id]);

        // Sync menu items and post content URLs
        if ($oldData && ($oldData['name'] !== $name || $oldData['slug'] !== $slug)) {
          grinds_sync_taxonomy_urls($pdo, 'category', $oldData['slug'], $slug, $oldData['name'], $name);
        }

        if (function_exists('grinds_rebuild_post_index')) {
          $stmtPosts = $pdo->prepare("SELECT id FROM posts WHERE category_id = ?");
          $stmtPosts->execute([$target_id]);
          $postIds = $stmtPosts->fetchAll(PDO::FETCH_COLUMN);
          if (!empty($postIds)) {
            grinds_rebuild_post_index($pdo, $postIds);
          }
        }

        set_flash(_t('msg_saved'));
        $redirect_url = 'admin/categories.php?edit_id=' . $target_id;
      } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, sort_order, category_theme, meta_data) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $slug, $sort_order, $cat_theme, $metaDataJson]);
        set_flash(_t('msg_saved'));
        $redirect_url = 'admin/categories.php';
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
        $error = _t('err_duplicate_entry'); // Example: "The name or slug is already in use."
      } else {
        $error = $msg;
      }
    }
  }
}

// Set pagination
$limit = isset($params['limit']) ? (int)$params['limit'] : 20;
$page = isset($params['page']) ? (int)$params['page'] : 1;
$sorter = new Sorter(['id', 'name', 'slug', 'sort_order'], 'sort_order', 'ASC');

$search_q = Routing::getString($params, 'q');
$whereSql = '';
$whereParams = [];
if ($search_q !== '') {
  $escaped_q = grinds_escape_like($search_q);
  $whereSql = "WHERE name LIKE ? ESCAPE '\\' OR slug LIKE ? ESCAPE '\\'";
  $whereParams = ["%{$escaped_q}%", "%{$escaped_q}%"];
}

// Fetch categories using the paginator helper
$paginationResult = grinds_paginate_query($pdo, 'categories', $page, $limit, $sorter, $whereSql, $whereParams);
$categories = $paginationResult['data'];
$paginator = $paginationResult['paginator'];

// Get post counts
if (!empty($categories)) {
  $repo = new PostRepository($pdo);
  $catIds = array_column($categories, 'id');
  $counts = $repo->getPostCountsForCategories($catIds);
  foreach ($categories as &$cat) {
    $cat['post_count'] = $counts[$cat['id']] ?? 0;
  }
  unset($cat);
}

// Fetch all categories
$allCategories = $pdo->query("SELECT id, name FROM categories ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get available themes
$available_themes = get_available_themes();

// Prepare edit data
$edit_data = ['name' => '', 'slug' => '', 'sort_order' => 0, 'category_theme' => '', 'meta_data' => '{}'];

if ($edit_id) {
  $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
  $stmt->execute([$edit_id]);
  $fetched = $stmt->fetch();
  if ($fetched) {
    $edit_data = $fetched;
  }
}

// Render view
$page_title = _t('menu_categories');
$current_page = 'categories';

ob_start();
require_once __DIR__ . '/layout/toast.php';
require_once __DIR__ . '/views/categories.php';
$content = ob_get_clean();

require_once __DIR__ . '/layout/loader.php';
