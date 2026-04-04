<?php

/**
 * posts.php
 *
 * Manage posts, pages, and templates.
 */
require_once __DIR__ . '/bootstrap.php';

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) {
  exit;
}

// Initialize variables
$params = Routing::getParams();
$action = Routing::getString($params, 'action', 'list');
$id = Routing::getString($params, 'id');
if ($id === '') $id = null;
$post = [];

// Define types
$allowed_types = ['post', 'page', 'template'];

// Check permissions
if (!current_user_can('manage_posts')) {
  set_flash(_t('err_access_denied'), 'error');
  redirect('admin/index.php');
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Empty trash
  if (isset($_POST['action']) && $_POST['action'] === 'empty_trash') {
    try {
      $trashType = $_POST['type'] ?? null;
      if ($trashType !== null && !in_array($trashType, $allowed_types, true)) {
        $trashType = null;
      }

      $count = grinds_empty_trash($pdo, $trashType);

      if ($count > 0) {
        set_flash(_t('msg_trash_emptied', $count));

        if (function_exists('clear_page_cache')) {
          clear_page_cache();
        }
      } else {
        set_flash(_t('msg_trash_empty'), 'info');
      }

      $redirectUrl = 'admin/posts.php?status=trash';
      if ($trashType) {
        $redirectUrl .= '&type=' . urlencode($trashType);
      }
      redirect($redirectUrl);
    } catch (Exception $e) {
      $error = $e->getMessage();
    }
  }

  // Handle bulk actions
  if (!isset($_POST['save_post_mode'])) {
    try {
      $result = grinds_process_bulk_actions($pdo, $_POST);
      set_flash($result['message']);

      if (function_exists('clear_page_cache')) {
        clear_page_cache();
      }

      redirect('admin/' . grinds_get_current_list_url());
    } catch (Exception $e) {
      $error = $e->getMessage();
    }
  }

  // Handle create/update
  else {
    try {
      // Check for upload errors (e.g. file size exceeded) before processing
      foreach ($_FILES as $key => $file) {
        if (isset($file['error']) && !is_array($file['error']) && $file['error'] !== UPLOAD_ERR_OK && $file['error'] !== UPLOAD_ERR_NO_FILE) {
          throw new Exception(_t('err_upload_failed') . ' (' . $key . ') (Code: ' . $file['error'] . ')');
        }
      }

      $result = grinds_save_post($pdo, $_POST, $_FILES, $action, $id);
      $postId = $result['id'];
      $finalSlug = $result['slug'];

      set_flash(_t('msg_post_saved'));

      if (function_exists('grinds_clear_specific_cache')) {
        $targetsToClear = [
          'home',
          $finalSlug
        ];

        if (!empty($_POST['category_id'])) {
          $stmtCat = $pdo->prepare("SELECT slug FROM categories WHERE id = ?");
          $stmtCat->execute([$_POST['category_id']]);
          if ($catSlug = $stmtCat->fetchColumn()) {
            $targetsToClear[] = 'category_' . $catSlug;
          }
        }

        // Clear tag pages
        if (!empty($_POST['tags'])) {
          $tags = explode(',', $_POST['tags']);
          foreach ($tags as $tag) {
            $tag = trim($tag);
            if (!empty($tag)) {
              $targetsToClear[] = 'tag_' . $tag;
            }
          }
        }

        grinds_clear_specific_cache($targetsToClear);
      } elseif (function_exists('clear_page_cache')) {
        clear_page_cache();
      }

      if (!empty($_POST['ajax_mode'])) {
        json_response([
          'success' => true,
          'id' => $postId,
          'slug' => $finalSlug,
          'version' => $result['version'] ?? null,
          'updated_at' => $result['updated_at'] ?? null,
          'message' => _t('msg_post_saved')
        ]);
      }

      redirect('admin/posts.php?action=edit&id=' . $postId . '&saved=1');
    } catch (Exception $e) {
      // Check version conflict
      $isConflict = (
        $e->getMessage() === _t('err_conflict') ||
        $e->getMessage() === _t('err_post_conflict')
      );

      if (!empty($_POST['ajax_mode'])) {
        if ($isConflict) {
          json_response(['success' => false, 'error' => _t('err_post_conflict'), 'conflict' => true], 409);
        } else {
          json_response(['success' => false, 'error' => $e->getMessage()]);
        }
      } else {
        $error = $isConflict ? _t('err_post_conflict') : $e->getMessage();
      }
    }
  }
}

// Handle list view
if ($action === 'list') {
  $current_type = Routing::getString($params, 'type', 'post');
  if (!in_array($current_type, $allowed_types)) {
    $current_type = 'post';
  }

  $type_labels = [
    'post' => _t('menu_posts'),
    'page' => _t('type_page'),
    'template' => _t('btn_template')
  ];
  $page_title = $type_labels[$current_type];

  $limit = (int)Routing::getString($params, 'limit', '20');
  if ($limit > 100)
    $limit = 100;
  $page = (int)Routing::getString($params, 'page', '1');

  $status_filter = Routing::getString($params, 'status');

  // Build filters
  $filters = [
    'type' => $current_type,
    'status' => $status_filter ?: 'all',
    'category_id' => Routing::getString($params, 'cat') ?: null,
    'search' => Routing::getString($params, 'q') ?: null,
  ];

  // Set sorting
  $sortable_cols = ['id', 'title', 'status', 'published_at', 'updated_at', 'deleted_at', 'type'];
  $default_sort = ($status_filter === 'trash') ? 'deleted_at' : 'updated_at';
  $sorter = new Sorter($sortable_cols, $default_sort, 'DESC');
  $orderClause = $sorter->getOrderClause();
  $orderBy = str_replace('ORDER BY ', 'p.', $orderClause);

  $search_q = Routing::getString($params, 'q');
  if ($search_q !== '') {
    $sq = grinds_prepare_search_query($pdo, $search_q);
    if (!empty($sq['order'])) {
      $orderBy = $sq['order'] . ', ' . $orderBy;
    }
    // Pass query
    $filters['prepared_search_query'] = $sq;
  }

  $repo = new PostRepository($pdo);
  $result = $repo->paginate($filters, $page, $limit, $orderBy);
  $posts = $result['posts'];
  $paginator = $result['paginator'];
  $total = $result['total'];

  // Pre-calculate view data
  $now = new DateTime();
  foreach ($posts as &$row) {
    $row['is_published'] = ($row['status'] === 'published');
    $pubDate = $row['published_at'] ? new DateTime($row['published_at']) : new DateTime('1970-01-01');
    $row['is_future'] = ($pubDate > $now);
    $row['cat_name'] = $row['category_name'] ?? 'Uncategorized';
    $row['post_type_label'] = _t('type_' . ($row['type'] ?? 'post'));
    $row['post_type_class'] = match ($row['type'] ?? 'post') {
      'page' => 'bg-theme-info/10 text-theme-info border-theme-info/20',
      'template' => 'bg-theme-warning/10 text-theme-warning border-theme-warning/20',
      default => 'bg-theme-text/5 text-theme-text opacity-70 border-theme-border'
    };
  }
  unset($row);

  // Get post counts
  $count_published = $count_draft = $count_trash = '-';
  $filter_cats = [];
  try {
    $counts = $repo->getCountsByStatus($current_type);
    $count_trash = $counts['trash'];
    $count_published = $counts['published'];
    $count_draft = $counts['draft'];

    if ($status_filter !== 'trash') {
      $filter_cats = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
    }
  } catch (Exception $e) {
  }

  ob_start();
  require_once __DIR__ . '/layout/toast.php';
  require_once __DIR__ . '/views/posts_list.php';
  $content = ob_get_clean();
}
// Handle form view
else {
  $page_title = ($action === 'new' ? _t('post_title_new') : _t('post_title_edit'));

  $post = [];
  $currentTags = '';

  if ($action === 'edit' && $id) {
    $repo = new PostRepository($pdo);
    $posts = $repo->fetch(['ids' => [$id], 'status' => 'any']);
    $fetchedPost = $posts[0] ?? null;

    if (!$fetchedPost)
      die(_t('err_post_not_found'));

    if (!empty($fetchedPost['deleted_at'])) {
      set_flash(_t('msg_post_in_trash'), "warning");
      redirect('admin/posts.php?status=trash');
    }

    $post = $fetchedPost;

    $stmtTags = $pdo->prepare("SELECT t.name FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
    $stmtTags->execute([$id]);
    $currentTags = implode(', ', $stmtTags->fetchAll(PDO::FETCH_COLUMN));
  }

  $categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
  $repoLinkable = new PostRepository($pdo);
  $linkablePages = $repoLinkable->fetch(['status' => 'published'], 100, 0, 'p.type DESC, p.created_at DESC');

  // Discover themes
  $available_themes = get_available_themes();

  ob_start();
  require_once __DIR__ . '/layout/toast.php';
  require_once __DIR__ . '/views/posts_form.php';
  $content = ob_get_clean();
}

$current_page = 'posts';
require_once __DIR__ . '/layout/loader.php';
