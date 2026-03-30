<?php

/**
 * tags.php
 *
 * Manage post tags.
 */
require_once __DIR__ . '/bootstrap.php';

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) {
  exit;
}

// Define reserved slugs
$reserved_slugs = grinds_get_reserved_slugs();

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
        $affectedPostIds = [];
        if (function_exists('grinds_rebuild_post_index') && !empty($targetIds)) {
          $inClause = implode(',', array_fill(0, count($targetIds), '?'));
          $stmtPosts = $pdo->prepare("SELECT DISTINCT post_id FROM post_tags WHERE tag_id IN ($inClause)");
          $stmtPosts->execute($targetIds);
          $affectedPostIds = $stmtPosts->fetchAll(PDO::FETCH_COLUMN);
        }

        $count = grinds_delete_records($pdo, 'tags', $targetIds);

        set_flash(_t('msg_deleted_count', $count));

        if (function_exists('grinds_rebuild_post_index')) {
          grinds_rebuild_post_index($pdo, $affectedPostIds);
        }
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
      $name = trim(Routing::getString($_POST, 'name'));
      $slug = trim(Routing::getString($_POST, 'slug'));
      $target_id = Routing::getString($_POST, 'target_id');

      // Validate input
      if ($name === '' || $name === null) {
        throw new Exception(_t('name_required'));
      }

      // Sanitize slug if provided
      if (!empty($slug)) {
        $slug = sanitize_slug($slug);
      }

      // Generate slug
      if (empty($slug)) {
        $slug = generate_slug($name, null, 'tag-');
      }

      // Check reserved slugs
      if (in_array(strtolower($slug), $reserved_slugs, true)) {
        throw new Exception(_t('slug_reserved', $slug));
      }

      // Check duplication
      $slug = grinds_get_unique_slug($pdo, 'tags', $slug, (int)($target_id ?? 0));

      if ($target_id) {
        // Fetch old data
        $stmtOld = $pdo->prepare("SELECT name, slug FROM tags WHERE id = ?");
        $stmtOld->execute([$target_id]);
        $oldData = $stmtOld->fetch();

        $stmt = $pdo->prepare("UPDATE tags SET name = ?, slug = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $target_id]);

        // Sync menu items and post content URLs
        if ($oldData && ($oldData['name'] !== $name || $oldData['slug'] !== $slug)) {
          grinds_sync_taxonomy_urls($pdo, 'tag', $oldData['slug'], $slug, $oldData['name'], $name);
        }

        if (function_exists('grinds_rebuild_post_index')) {
          $stmtPosts = $pdo->prepare("SELECT post_id FROM post_tags WHERE tag_id = ?");
          $stmtPosts->execute([$target_id]);
          $postIds = $stmtPosts->fetchAll(PDO::FETCH_COLUMN);
          if (!empty($postIds)) {
            grinds_rebuild_post_index($pdo, $postIds);
          }
        }

        set_flash(_t('msg_saved'));
        $redirect_url = 'admin/tags.php?edit_id=' . $target_id;
      } else {
        $stmt = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
        $stmt->execute([$name, $slug]);
        set_flash(_t('msg_saved'));
        $redirect_url = 'admin/tags.php';
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
$sorter = new Sorter(['id', 'name', 'slug'], 'name', 'ASC');

$search_q = Routing::getString($params, 'q');
$whereSql = '';
$whereParams = [];
if ($search_q !== '') {
  $whereSql = 'name LIKE ? OR slug LIKE ?';
  $whereParams = ["%$search_q%", "%$search_q%"];
}

// Fetch tags using the paginator helper
$paginationResult = grinds_paginate_query($pdo, 'tags', $page, $limit, $sorter, $whereSql, $whereParams);
$tags = $paginationResult['data'];
$paginator = $paginationResult['paginator'];

// Get post counts
if (!empty($tags)) {
  $repo = new PostRepository($pdo);
  $tagIds = array_column($tags, 'id');
  $counts = $repo->getPostCountsForTags($tagIds);
  foreach ($tags as &$tag) {
    $tag['post_count'] = $counts[$tag['id']] ?? 0;
  }
  unset($tag);
}

// Prepare edit data
$edit_data = ['name' => '', 'slug' => ''];
if ($edit_id) {
  $stmt = $pdo->prepare("SELECT * FROM tags WHERE id = ?");
  $stmt->execute([$edit_id]);
  $fetched = $stmt->fetch();
  if ($fetched) {
    $edit_data = $fetched;
  }
}

// Render view
$page_title = _t('menu_tags');
$current_page = 'tags';

ob_start();
require_once __DIR__ . '/layout/toast.php';
require_once __DIR__ . '/views/tags.php';
$content = ob_get_clean();

require_once __DIR__ . '/layout/loader.php';
