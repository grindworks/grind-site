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
$cpts = function_exists('grinds_get_theme_post_types') ? grinds_get_theme_post_types() : [];
foreach ($cpts as $cptSlug => $cptData) {
  $allowed_types[] = $cptSlug;
}

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
      $error = _t($e->getMessage());
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
          'status' => $_POST['status'] ?? 'draft',
          'type' => $_POST['type'] ?? 'post',
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
        $error = $isConflict ? _t('err_post_conflict') : _t($e->getMessage());
      }
    }
  }
}

// Handle list view
if ($action === 'list') {
  $currentType = Routing::getString($params, 'type', 'post');
  if (!in_array($currentType, $allowed_types)) {
    $currentType = 'post';
  }

  $typeLabels = [
    'post' => _t('menu_posts'),
    'page' => _t('type_page'),
    'template' => _t('btn_template')
  ];
  foreach ($cpts as $cptSlug => $cptData) {
    $typeLabels[$cptSlug] = function_exists('_t') && isset($cptData['label']) ? _t($cptData['label']) : ($cptData['label'] ?? ucfirst($cptSlug));
  }
  $page_title = $typeLabels[$currentType] ?? ucfirst($currentType);

  $limit = (int)Routing::getString($params, 'limit', '20');
  if ($limit > 100)
    $limit = 100;
  $page = (int)Routing::getString($params, 'page', '1');

  $statusFilter = Routing::getString($params, 'status');

  // Build filters
  $filters = [
    'type' => ($statusFilter === 'trash') ? $allowed_types : $currentType,
    'status' => $statusFilter ?: 'all',
    'category_id' => Routing::getString($params, 'cat') ?: null,
    'search' => Routing::getString($params, 'q') ?: null,
  ];

  // Set sorting
  $sortableCols = ['id', 'title', 'status', 'published_at', 'updated_at', 'deleted_at', 'type'];
  $defaultSort = ($statusFilter === 'trash') ? 'deleted_at' : 'updated_at';
  $sorter = new Sorter($sortableCols, $defaultSort, 'DESC');
  $orderClause = $sorter->getOrderClause();
  $orderBy = str_replace('ORDER BY ', 'p.', $orderClause);

  $searchQ = Routing::getString($params, 'q');
  if ($searchQ !== '') {
    $sq = grinds_prepare_search_query($pdo, $searchQ);
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
  $countPublished = $countDraft = $countTrash = '-';
  $filterCats = [];
  try {
    $counts = $repo->getCountsByStatus($currentType);
    $countPublished = $counts['published'];
    $countDraft = $counts['draft'];

    $countTrash = $repo->count(['status' => 'trash', 'type' => $allowed_types]);

    if ($statusFilter !== 'trash') {
      $filterCats = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
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

  if ($action === 'new') {
    $post['type'] = Routing::getString($params, 'type', 'post');
  }

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

  $allTagsList = $pdo->query("SELECT name FROM tags ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
  if (!is_array($allTagsList)) {
    $allTagsList = [];
  }

  // Discover themes
  $available_themes = get_available_themes();

  // Restore input data on error
  if (!empty($error) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_post_mode'])) {
    $post['title'] = $_POST['title'] ?? ($post['title'] ?? '');
    $post['slug'] = $_POST['slug'] ?? ($post['slug'] ?? '');
    $post['description'] = $_POST['description'] ?? ($post['description'] ?? '');

    // Restore content (Handling Base64 encoded payload for WAF bypass)
    $contentVal = $_POST['content'] ?? '';
    if (!empty($_POST['content_is_base64']) && $contentVal !== '') {
      $decoded = base64_decode(str_replace(' ', '+', $contentVal));
      if ($decoded !== false) $contentVal = $decoded;
    }
    $post['content'] = $contentVal;

    $post['status'] = $_POST['status'] ?? ($post['status'] ?? 'draft');
    $post['type'] = $_POST['type'] ?? ($post['type'] ?? 'post');
    $post['category_id'] = $_POST['category_id'] ?? ($post['category_id'] ?? null);
    $post['published_at'] = $_POST['published_at'] ?? ($post['published_at'] ?? null);
    $post['page_theme'] = $_POST['page_theme'] ?? ($post['page_theme'] ?? '');
    $post['show_category'] = isset($_POST['show_category']) ? 1 : 0;
    $post['show_date'] = isset($_POST['show_date']) ? 1 : 0;
    $post['show_share_buttons'] = isset($_POST['show_share_buttons']) ? 1 : 0;
    $post['show_toc'] = isset($_POST['show_toc']) ? 1 : 0;
    $post['toc_title'] = $_POST['toc_title'] ?? ($post['toc_title'] ?? '');
    $post['is_noindex'] = isset($_POST['is_noindex']) ? 1 : 0;
    $post['is_nofollow'] = isset($_POST['is_nofollow']) ? 1 : 0;
    $post['is_noarchive'] = isset($_POST['is_noarchive']) ? 1 : 0;
    $post['is_hide_rss'] = isset($_POST['is_hide_rss']) ? 1 : 0;
    $post['is_hide_llms'] = isset($_POST['is_hide_llms']) ? 1 : 0;

    if (isset($_POST['tags'])) $currentTags = $_POST['tags'];

    // Restore hero settings
    $hero = json_decode($post['hero_settings'] ?? '{}', true) ?: [];
    $hero['layout'] = $_POST['hero_layout'] ?? ($hero['layout'] ?? 'standard');
    $hero['title'] = $_POST['hero_title'] ?? ($hero['title'] ?? '');
    $hero['subtext'] = $_POST['hero_subtext'] ?? ($hero['subtext'] ?? '');
    $hero['overlay'] = isset($_POST['hero_overlay']) ? 1 : 0;
    $hero['fixed_bg'] = isset($_POST['hero_fixed_bg']) ? 1 : 0;
    $hero['seo_author'] = $_POST['seo_author'] ?? ($hero['seo_author'] ?? '');
    if (isset($_POST['hero_buttons_json'])) $hero['buttons'] = json_decode($_POST['hero_buttons_json'], true) ?: [];
    if (isset($_POST['hero_image_mobile_url'])) $hero['mobile_image'] = $_POST['hero_image_mobile_url'];
    $post['hero_settings'] = json_encode($hero, JSON_UNESCAPED_UNICODE);

    if (isset($_POST['hero_image_url'])) $post['hero_image'] = $_POST['hero_image_url'];
    if (isset($_POST['current_thumbnail'])) $post['thumbnail'] = $_POST['current_thumbnail'];

    // Restore custom fields
    $metaData = json_decode($post['meta_data'] ?? '{}', true) ?: [];
    if (isset($_POST['meta_data']) && is_array($_POST['meta_data'])) $metaData = array_merge($metaData, $_POST['meta_data']);
    foreach ($_POST as $k => $v) {
      if (str_starts_with($k, 'meta_data_') && str_ends_with($k, '_url')) {
        $metaData[str_replace(['meta_data_', '_url'], '', $k)] = $v;
      }
    }
    $post['meta_data'] = json_encode($metaData, JSON_UNESCAPED_UNICODE);
  }

  ob_start();
  require_once __DIR__ . '/layout/toast.php';
  require_once __DIR__ . '/views/posts_form.php';
  $content = ob_get_clean();
}

$post_type_for_menu = $currentType ?? ($post['type'] ?? '');
if (isset($cpts[$post_type_for_menu])) {
  $current_page = 'cpt_' . $post_type_for_menu;
} else {
  $current_page = 'posts';
}

require_once __DIR__ . '/layout/loader.php';
