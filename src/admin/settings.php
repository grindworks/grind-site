<?php

/**
 * settings.php
 *
 * Manage system-wide settings
 */
require_once __DIR__ . '/bootstrap.php';

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) {
  exit;
}

// Load defaults
require_once __DIR__ . '/../lib/sample_data.php';

/**
 * Save options
 */
function grinds_save_settings_from_post($keys = [], $checkboxes = [])
{
  foreach ($keys as $k) {
    if (isset($_POST[$k])) {
      $val = is_array($_POST[$k]) ? json_encode($_POST[$k], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE) : $_POST[$k];
      update_option($k, trim((string)$val));
    }
  }
  foreach ($checkboxes as $k) {
    update_option($k, isset($_POST[$k]) ? '1' : '0');
  }
}

$params = Routing::getParams();

// Discover themes
$available_site_themes = get_available_themes();
if (empty($available_site_themes)) {
  $available_site_themes['default'] = 'Default';
}

// Discover skins
$skin_dir = ROOT_PATH . '/admin/skins/';
$available_admin_skins = [];
if (is_dir($skin_dir)) {
  foreach (new DirectoryIterator($skin_dir) as $fileInfo) {
    if ($fileInfo->isFile() && $fileInfo->getExtension() === 'json') {
      $slug = $fileInfo->getBasename('.json');
      if (preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
        $json = json_decode(file_get_contents($fileInfo->getPathname()), true);
        $label = isset($json['name']) ? $json['name'] : '';
        if (empty($label) || $label === $slug) {
          $label = ucwords(str_replace('_', ' ', $slug));
        }
        $available_admin_skins[$slug] = $label;
      }
    }
  }
}

// Sort skins
ksort($available_admin_skins, SORT_NATURAL | SORT_FLAG_CASE);

if (empty($available_admin_skins)) {
  $available_admin_skins['default'] = 'Default';
}

// Define layouts
$available_layouts = [
  'sidebar' => _t('sidebar'),
  'topbar' => _t('topbar'),
];
$layout_dir = ROOT_PATH . '/admin/layout/';
if (is_dir($layout_dir)) {
  foreach (new DirectoryIterator($layout_dir) as $fileInfo) {
    if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
      $n = $fileInfo->getBasename('.php');
      if (!in_array($n, ['header', 'footer', 'loader', 'toast', 'sidebar', 'topbar', 'assets_loader', 'index'])) {
        $available_layouts[$n] = ucfirst($n);
      }
    }
  }
}

// Load assets
$skin_assets_file = __DIR__ . '/config/skin_assets.php';
$skin_assets = file_exists($skin_assets_file) ? require $skin_assets_file : [];
$available_textures = $skin_assets['textures'] ?? [];
$available_media_bgs = $skin_assets['media_backgrounds'] ?? [];

// Ensure backup dir
$backup_dir = ROOT_PATH . '/data/backups/';

// Define color groups for custom skin
$colorDefGroups = [
  _t('st_cg_base') => ['bg', 'surface', 'text', 'border', 'modal_overlay'],
  _t('st_cg_sidebar') => ['sidebar', 'sidebar_text', 'sidebar_active_bg', 'sidebar_active_text', 'sidebar_hover_bg', 'sidebar_hover_text'],
  _t('st_cg_brand') => ['primary', 'on_primary', 'secondary', 'on_secondary'],
  _t('st_cg_status') => ['success', 'on_success', 'success_bg', 'success_text', 'danger', 'on_danger', 'danger_bg', 'danger_text', 'warning', 'on_warning', 'warning_bg', 'warning_text', 'info', 'on_info', 'info_bg', 'info_text'],
  _t('st_cg_input') => ['input_bg', 'input_text', 'input_placeholder', 'input_border'],
  _t('st_cg_table') => ['table_header_bg', 'table_header_text', 'table_row_hover_bg', 'scrollbar_track', 'scrollbar_thumb'],
  _t('st_cg_badges') => ['status_draft', 'status_published', 'status_pending', 'status_private', 'status_trash'],
  _t('st_cg_media') => ['media_checker_1', 'media_checker_2', 'media_ring'],
  _t('st_cg_crt') => ['sidebar_crt', 'sidebar_crt_shadow', 'sidebar_crt_border']
];

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  $action = Routing::getString($_POST, 'action');

  if ($action !== 'update_profile' && !current_user_can('manage_settings')) {
    if (!empty($_POST['ajax_mode'])) {
      json_response(['success' => false, 'error' => _t('err_access_denied')], 403);
    }
    set_flash(_t('err_access_denied'), 'error');
    redirect('admin/index.php');
  }

  // Generate htpasswd
  if ($action === 'generate_htpasswd') {
    $user = trim(Routing::getString($_POST, 'user'));
    $pass = Routing::getString($_POST, 'pass');

    if (empty($user) || empty($pass)) {
      json_response(['success' => false, 'error' => 'Username and Password are required.']);
    }

    // Generate hash
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $entry = $user . ':' . $hash;

    json_response(['success' => true, 'entry' => $entry]);
  }

  // Save htpasswd
  if ($action === 'save_htpasswd') {
    $content = Routing::getString($_POST, 'content');
    $htpasswdPath = ROOT_PATH . '/.htpasswd';

    if (!empty($content) && file_put_contents($htpasswdPath, $content) !== false) {
      json_response(['success' => true]);
    } else {
      json_response(['success' => false, 'error' => 'Failed to write to .htpasswd. Check permissions.']);
    }
  }

  // Scan links
  if ($action === 'scan_hardcoded_links') {
    if (function_exists('grinds_set_high_load_mode')) {
      grinds_set_high_load_mode();
    }
    try {
      $targetBase = isset($_POST['target_url']) ? rtrim(Routing::getString($_POST, 'target_url'), '/') : BASE_URL;
      $foundPosts = [];

      $repo = new PostRepository($pdo);
      $batchLimit = 50;
      $offset = 0;

      while (true) {
        $posts = $repo->fetch(['status' => 'any'], $batchLimit, $offset, 'p.id ASC', 'p.id, p.title, p.content, p.thumbnail, p.hero_image, p.hero_settings', false);
        if (empty($posts))
          break;

        foreach ($posts as $row) {
          $isHit = false;
          if (strpos($row['content'], $targetBase) !== false) {
            $isHit = true;
          }
          if (!$isHit) {
            if (strpos($row['thumbnail'] ?? '', $targetBase) !== false) $isHit = true;
            elseif (strpos($row['hero_image'] ?? '', $targetBase) !== false) $isHit = true;
            elseif (strpos($row['hero_settings'] ?? '', $targetBase) !== false) $isHit = true;
          }
          if ($isHit) {
            $foundPosts[] = ['id' => $row['id'], 'title' => $row['title']];
          }
        }
        $offset += $batchLimit;
      }

      // Scan menus
      $stmtMenu = $pdo->query("SELECT id, label, url FROM nav_menus");
      while ($row = $stmtMenu->fetch()) {
        if (strpos($row['url'], $targetBase) !== false) {
          $foundPosts[] = ['id' => 'Menu-' . $row['id'], 'title' => 'Menu: ' . ($row['label'] ?: 'ID ' . $row['id'])];
        }
      }

      // Scan banners
      $stmtBanner = $pdo->query("SELECT id, link_url, image_url FROM banners");
      while ($row = $stmtBanner->fetch()) {
        if (strpos($row['link_url'], $targetBase) !== false || strpos($row['image_url'], $targetBase) !== false) {
          $foundPosts[] = ['id' => 'Banner-' . $row['id'], 'title' => 'Banner #' . $row['id']];
        }
      }

      // Scan widgets
      $stmtWidget = $pdo->query("SELECT id, title, content, settings FROM widgets");
      while ($row = $stmtWidget->fetch()) {
        if (strpos($row['content'], $targetBase) !== false || strpos($row['settings'], $targetBase) !== false) {
          $foundPosts[] = ['id' => 'Widget-' . $row['id'], 'title' => 'Widget: ' . ($row['title'] ?: 'ID ' . $row['id'])];
        }
      }

      json_response(['success' => true, 'data' => $foundPosts]);
    } catch (Exception $e) {
      json_response(['success' => false, 'error' => $e->getMessage()]);
    }
  }

  // Replace links
  if ($action === 'replace_hardcoded_links') {
    if (function_exists('grinds_set_high_load_mode')) {
      grinds_set_high_load_mode();
    }
    $targetBase = isset($_POST['target_url']) ? rtrim(Routing::getString($_POST, 'target_url'), '/') : BASE_URL;
    $fixedCount = 0;

    $pdo->beginTransaction();
    try {
      // Update posts
      $repo = new PostRepository($pdo);
      $updateStmt = $pdo->prepare("UPDATE posts SET content = ?, thumbnail = ?, hero_image = ?, hero_settings = ? WHERE id = ?");

      $batchLimit = 50;
      $offset = 0;
      while (true) {
        $posts = $repo->fetch(['status' => 'any'], $batchLimit, $offset, 'p.id ASC', 'p.id, p.content, p.thumbnail, p.hero_image, p.hero_settings', false);
        if (empty($posts))
          break;

        foreach ($posts as $row) {
          $newContent = Routing::convertToDbUrl($row['content'], $targetBase);
          $newThumb = Routing::convertToDbUrl($row['thumbnail'] ?? '', $targetBase);
          $newHeroImg = Routing::convertToDbUrl($row['hero_image'] ?? '', $targetBase);
          $newHeroSet = Routing::convertToDbUrl($row['hero_settings'] ?? '', $targetBase);

          if ($newContent !== $row['content'] || $newThumb !== ($row['thumbnail'] ?? '') || $newHeroImg !== ($row['hero_image'] ?? '') || $newHeroSet !== ($row['hero_settings'] ?? '')) {
            $updateStmt->execute([$newContent, $newThumb, $newHeroImg, $newHeroSet, $row['id']]);
            $fixedCount++;
          }
        }
        $offset += $batchLimit;
      }

      // Update menus
      $stmt = $pdo->query("SELECT id, url FROM nav_menus");
      $updateStmt = $pdo->prepare("UPDATE nav_menus SET url = ? WHERE id = ?");
      while ($row = $stmt->fetch()) {
        $newUrl = Routing::convertToDbUrl($row['url'], $targetBase);
        if ($newUrl !== $row['url']) {
          $updateStmt->execute([$newUrl, $row['id']]);
          $fixedCount++;
        }
      }

      // Update banners
      $stmt = $pdo->query("SELECT id, link_url, image_url FROM banners");
      $updateStmt = $pdo->prepare("UPDATE banners SET link_url = ?, image_url = ? WHERE id = ?");
      while ($row = $stmt->fetch()) {
        $newLink = Routing::convertToDbUrl($row['link_url'], $targetBase);
        $newImg = Routing::convertToDbUrl($row['image_url'], $targetBase);
        if ($newLink !== $row['link_url'] || $newImg !== $row['image_url']) {
          $updateStmt->execute([$newLink, $newImg, $row['id']]);
          $fixedCount++;
        }
      }

      // Update widgets
      $stmt = $pdo->query("SELECT id, content, settings FROM widgets");
      $updateStmt = $pdo->prepare("UPDATE widgets SET content = ?, settings = ? WHERE id = ?");
      while ($row = $stmt->fetch()) {
        $newContent = Routing::convertToDbUrl($row['content'], $targetBase);
        $newSettings = Routing::convertToDbUrl($row['settings'], $targetBase);
        if ($newContent !== $row['content'] || $newSettings !== $row['settings']) {
          $updateStmt->execute([$newContent, $newSettings, $row['id']]);
          $fixedCount++;
        }
      }

      $pdo->commit();
      json_response(['success' => true, 'count' => $fixedCount]);
    } catch (Exception $e) {
      $pdo->rollBack();
      json_response(['success' => false, 'error' => $e->getMessage()]);
    }
  }

  // Fetch logs
  if ($action === 'fetch_system_log') {
    try {
      $logFile = ROOT_PATH . '/data/logs/error.log';
      $lines = [];

      if (file_exists($logFile)) {
        try {
          $fp = fopen($logFile, 'r');
          if ($fp) {
            $pos = filesize($logFile);
            $chunkSize = 8192;
            $buffer = '';

            // Read backwards in chunks to find last 100 lines
            while ($pos > 0) {
              $readSize = min($chunkSize, $pos);
              $pos -= $readSize;
              fseek($fp, $pos);
              $buffer = fread($fp, $readSize) . $buffer;

              if (substr_count($buffer, "\n") >= 100) {
                break;
              }
            }
            fclose($fp);

            $parts = explode("\n", $buffer);
            foreach ($parts as $part) {
              if (trim($part) !== '') {
                $lines[] = trim($part);
              }
            }
            $lines = array_reverse(array_slice($lines, -100));
          }
        } catch (Exception $e) {
          // Ignore errors
        }
      }

      json_response(['success' => true, 'logs' => $lines]);
    } catch (Exception $e) {
      json_response(['success' => false, 'error' => $e->getMessage()]);
    }
  }

  // Create security test file
  if ($action === 'create_security_test') {
    $testFile = ROOT_PATH . '/assets/uploads/security_test.php';
    if (!is_dir(dirname($testFile))) @mkdir(dirname($testFile), 0775, true);
    file_put_contents($testFile, '<?php echo "VULNERABLE"; ?>');
    json_response(['success' => true]);
  }

  // Delete security test file
  if ($action === 'delete_security_test') {
    $testFile = ROOT_PATH . '/assets/uploads/security_test.php';
    if (file_exists($testFile)) @unlink($testFile);
    json_response(['success' => true]);
  }

  $redirectTab = Routing::getString($params, 'tab', 'general');

  try {
    switch ($action) {
      case 'fix_rewrite_base':
        $redirectTab = 'system';
        $htaccessPath = ROOT_PATH . '/.htaccess';

        if (!file_exists($htaccessPath))
          throw new Exception('.htaccess not found.');
        if (!is_writable($htaccessPath))
          throw new Exception('.htaccess is not writable.');

        $content = file_get_contents($htaccessPath);

        // Calculate base path
        $path = parse_url(BASE_URL, PHP_URL_PATH) ?? '/';
        $detectedBase = rtrim($path, '/') . '/';

        // Replace RewriteBase
        $pattern = '/^(\s*)#?\s*RewriteBase\s+.*$/m';

        if (preg_match($pattern, $content)) {
          $newContent = preg_replace($pattern, '$1RewriteBase ' . $detectedBase, $content);
        } else {
          // Insert fallback
          $newContent = preg_replace('/(RewriteEngine On)/i', "$1\n    RewriteBase " . $detectedBase, $content);
        }

        if ($newContent && $newContent !== $content) {
          file_put_contents($htaccessPath, $newContent);
          if (!empty($_POST['ajax_mode'])) {
            // Return response for frontend to handle logout in Ajax mode
            json_response(['success' => true, 'message' => _t('msg_rewritebase_updated', $detectedBase)]);
          } else {
            // Logout and redirect for normal POST submission
            grinds_logout();
            _safe_session_start();
            set_flash(_t('msg_rewritebase_updated', $detectedBase) . ' ' . _t('login_required'));
            redirect('admin/login.php');
            exit;
          }
        } else {
          set_flash(_t('msg_no_changes'));
          if (!empty($_POST['ajax_mode'])) {
            json_response(['success' => true, 'message' => _t('msg_no_changes')]);
          }
        }
        break;

      // Update general
      case 'update_general':
        $redirectTab = 'general';
        $pdo->beginTransaction();
        try {
          grinds_save_settings_from_post(
            ['site_name', 'admin_title', 'site_description', 'site_footer_text', 'license_key', 'media_max_width', 'media_quality'],
            ['site_noindex', 'site_block_ai', 'admin_show_site_name', 'admin_show_logo_login']
          );

          $fileKeys = ['site_ogp_image', 'site_favicon', 'admin_logo'];
          foreach ($fileKeys as $fk) {
            if ($fk === 'site_favicon' && !empty($_FILES[$fk]['name'])) {
              $fileName = is_array($_FILES[$fk]['name']) ? ($_FILES[$fk]['name'][0] ?? '') : $_FILES[$fk]['name'];
              $ext = strtolower(pathinfo((string)$fileName, PATHINFO_EXTENSION));
              if (!in_array($ext, ['ico', 'png'])) {
                throw new Exception(function_exists('_t') ? _t('err_invalid_favicon_ext') : 'Favicon must be .ico or .png');
              }
            }

            $deleteKey = 'delete_' . str_replace('site_', '', $fk);
            $newVal = grinds_process_image_upload($pdo, $fk, get_option($fk), ['delete_field' => $deleteKey]);

            if ($newVal !== get_option($fk)) {
              update_option($fk, $newVal);
              if ($fk === 'admin_logo' && $newVal === '') {
                update_option('admin_show_site_name', '1');
                update_option('admin_show_logo_login', '0');
              }
            }
          }
          $pdo->commit();
        } catch (Exception $e) {
          $pdo->rollBack();
          throw $e;
        }
        set_flash(_t('msg_saved'));
        break;

      // Update display
      case 'update_display':
        $redirectTab = 'display';
        $pdo->beginTransaction();
        try {
          grinds_save_settings_from_post(['title_format', 'date_format', 'site_lang', 'editor_debounce_time']);

          if (isset($_POST['posts_per_page'])) {
            $val = (int)$_POST['posts_per_page'];
            if ($val < 1)
              $val = 1;
            if ($val > 100)
              $val = 100;
            update_option('posts_per_page', $val);
          }

          $tz = $_POST['timezone'] ?? 'UTC';
          if (in_array($tz, DateTimeZone::listIdentifiers())) {
            update_option('timezone', $tz);
          } else {
            throw new Exception(_t('invalid_timezone_selected'));
          }
          $pdo->commit();
        } catch (Exception $e) {
          $pdo->rollBack();
          throw $e;
        }
        set_flash(_t('msg_saved'));
        break;

      // Toggle maintenance
      case 'toggle_maintenance':
        $redirectTab = 'system';
        $file = ROOT_PATH . '/.maintenance';

        $pdo->beginTransaction();
        try {
          // Save maintenance
          if (isset($_POST['maintenance_title'])) {
            update_option('maintenance_title', trim(Routing::getString($_POST, 'maintenance_title')));
          }
          if (isset($_POST['maintenance_message'])) {
            update_option('maintenance_message', trim(Routing::getString($_POST, 'maintenance_message')));
          }

          // Handle toggle
          if (isset($_POST['maintenance_mode']) && $_POST['maintenance_mode'] == '1') {
            if (!file_exists($file)) {
              if (@file_put_contents($file, 'Maintenance mode enabled at ' . date('Y-m-d H:i:s')) === false) {
                throw new Exception(_t('err_permission_denied_core'));
              }
            }
          } else {
            // Disable maintenance
            if (file_exists($file)) {
              grinds_force_unlink($file);
            }
          }
          $pdo->commit();
        } catch (Exception $e) {
          $pdo->rollBack();
          throw $e;
        }
        set_flash(_t('msg_saved'));
        break;

      // Update theme
      case 'update_theme':
        $redirectTab = 'theme';
        $pdo->beginTransaction();
        try {
          $oldTheme = get_option('site_theme');
          grinds_save_settings_from_post(['site_theme', 'admin_layout', 'admin_skin'], ['disable_external_assets']);

          if ($oldTheme !== get_option('site_theme')) {
            update_option('last_ssg_export', '');
          }

          if (isset($_POST['admin_skin']) && $_POST['admin_skin'] === 'custom') {
            // Get custom keys
            $custom_keys = array_filter(array_keys($_POST), function ($k) {
              return strpos((string)$k, 'custom_skin_') === 0;
            });

            foreach ($custom_keys as $ck) {
              $val = is_scalar($_POST[$ck]) ? (string)$_POST[$ck] : '';
              if ($ck === 'custom_skin_media_bg_css') {
                $val = Routing::convertToDbUrl((string)$val);
              }
              update_option($ck, trim((string)$val));
            }
          }
          $pdo->commit();
        } catch (Exception $e) {
          $pdo->rollBack();
          throw $e;
        }

        set_flash(_t('msg_saved'));
        break;

      // Save custom skin
      case 'save_custom_skin':
        $redirectTab = 'theme';
        $skinName = trim(Routing::getString($_POST, 'new_skin_name'));
        if (empty($skinName) || !preg_match('/^[a-z0-9_-]+$/', $skinName)) {
          throw new Exception(_t('skin_name_alphanumeric_only'));
        }
        if ($skinName === 'default') {
          throw new Exception(_t('err_overwrite_default'));
        }

        // Load defaults
        $defaults = require ROOT_PATH . '/admin/config/skin_defaults.php';
        $skinData = [];

        // Set identity fields from the file slug (not inherited from defaults)
        $skinData['name'] = ucwords(str_replace(['_', '-'], ' ', $skinName));
        $skinData['description'] = 'Custom skin created ' . date('Y-m-d');

        // Populate defaults first to ensure we don't miss unchecked boolean fields
        foreach ($defaults as $key => $val) {
          if ($key === 'colors' && is_array($val)) {
            $skinData['colors'] = [];
            foreach ($val as $cKey => $cVal) {
              $skinData['colors'][$cKey] = $cVal;
            }
          } elseif (!in_array($key, ['name', 'description', 'css'], true)) {
            $skinData[$key] = $val;
          }
        }

        // Define color settings keys
        $colorDefKeys = array_merge(...array_values($colorDefGroups));

        // Override with submitted POST data
        foreach ($_POST as $postKey => $postVal) {
          if (str_starts_with($postKey, 'custom_skin_')) {
            $key = str_replace('custom_skin_', '', $postKey);

            if (in_array($key, $colorDefKeys, true)) {
              $cKey = $key; // Keep underscore consistently
              if (!isset($skinData['colors'])) $skinData['colors'] = [];
              $skinData['colors'][$cKey] = trim((string)$postVal);
            } else {
              if ($key === 'font_url') {
                $postVal = filter_var($postVal, FILTER_SANITIZE_URL);
              } elseif ($key === 'media_bg_css') {
                $postVal = Routing::convertToDbUrl(strip_tags(is_scalar($postVal) ? (string)$postVal : ''));
              } elseif (is_array($postVal)) {
                array_walk_recursive($postVal, function (&$item) {
                  $item = strip_tags((string)$item);
                });
              } else {
                $postVal = strip_tags(is_scalar($postVal) ? (string)$postVal : '');
              }
              $skinData[$key] = $postVal;
            }
          }
        }

        $jsonContent = json_encode($skinData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE);
        $filePath = ROOT_PATH . '/admin/skins/' . $skinName . '.json';
        if (!is_dir(dirname($filePath)))
          @mkdir(dirname($filePath), 0755, true);

        if (file_put_contents($filePath, $jsonContent) === false) {
          throw new Exception(_t('failed_to_save_skin_file'));
        }
        update_option('admin_skin', $skinName);
        set_flash(_t('skin_saved_successfully', $skinName));
        break;

      // Duplicate theme
      case 'duplicate_theme':
        $redirectTab = 'theme';
        $srcTheme = trim(Routing::getString($_POST, 'source_theme', 'default'));
        $newTheme = trim(Routing::getString($_POST, 'new_theme_name'));

        // Validate input
        if (empty($srcTheme) || !preg_match('/^[a-zA-Z0-9_-]+$/', $srcTheme)) {
          throw new Exception(_t('err_theme_src_not_found') . ' (Invalid format)');
        }

        if (!is_dir(ROOT_PATH . '/theme/' . $srcTheme)) {
          throw new Exception(_t('err_theme_src_not_found'));
        }
        if (empty($newTheme) || !preg_match('/^[a-zA-Z0-9_-]+$/', $newTheme)) {
          throw new Exception(_t('err_theme_name_invalid'));
        }

        // Check reserved names
        $reserved = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];
        if (in_array(strtoupper($newTheme), $reserved) || strlen($newTheme) > 64) {
          throw new Exception(_t('err_theme_name_invalid'));
        }

        if (is_dir(ROOT_PATH . '/theme/' . $newTheme)) {
          throw new Exception(_t('err_theme_exists', $newTheme));
        }

        try {
          // Copy files
          grinds_recursive_copy(ROOT_PATH . '/theme/' . $srcTheme, ROOT_PATH . '/theme/' . $newTheme);

          // Verify copy
          if (!is_dir(ROOT_PATH . '/theme/' . $newTheme)) {
            throw new Exception('Theme copy failed: Destination incomplete.');
          }

          // Switch theme
          update_option('site_theme', $newTheme);
        } catch (Exception $e) {
          // Cleanup failure
          grinds_delete_tree(ROOT_PATH . '/theme/' . $newTheme);
          throw $e;
        }

        set_flash(_t('msg_theme_duplicated', $newTheme));
        break;

      // Update profile
      case 'update_profile':
        $redirectTab = 'profile';
        $pdo->beginTransaction();
        try {
          $myId = App::user()['id'];
          $email = trim(Routing::getString($_POST, 'email'));
          $currentPass = Routing::getString($_POST, 'current_password');
          $newPass = Routing::getString($_POST, 'new_password');
          $confirmPass = Routing::getString($_POST, 'new_password_confirm');
          $avatar = Routing::getString($_POST, 'current_avatar');

          $adminLayout = Routing::getString($_POST, 'admin_layout');
          $adminSkin = Routing::getString($_POST, 'admin_skin');

          if (empty($currentPass))
            throw new Exception(_t('req_current_password'));
          $stmtUser = $pdo->prepare("SELECT password FROM users WHERE id = ?");
          $stmtUser->execute([$myId]);
          $storedHash = $stmtUser->fetchColumn();
          if (!password_verify($currentPass, $storedHash))
            throw new Exception(_t('err_current_password'));

          if (!empty($email)) {
            $check = $pdo->prepare("SELECT count(*) FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $myId]);
            if ($check->fetchColumn() > 0)
              throw new Exception(_t('err_email_exists'));
          }
          $avatar = grinds_process_image_upload($pdo, 'avatar', $avatar, ['max_width' => 400]);

          if (!empty($newPass)) {
            if ($newPass !== $confirmPass)
              throw new Exception(_t('err_password_mismatch'));
            if (strlen($newPass) < 8 || !preg_match('/[A-Za-z]/', $newPass) || !preg_match('/[0-9]/', $newPass))
              throw new Exception(_t('err_pass_len'));
            if (strlen($newPass) > 256)
              throw new Exception(_t('err_pass_max_len'));
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET email = ?, password = ?, avatar = ?, admin_layout = ?, admin_skin = ? WHERE id = ?");
            $stmt->execute([$email, $hash, $avatar, $adminLayout, $adminSkin, $myId]);

            // Invalidate existing tokens on password change
            $pdo->prepare("DELETE FROM user_tokens WHERE user_id = ?")->execute([$myId]);
            grinds_invalidate_user_sessions($myId);
          } else {
            $stmt = $pdo->prepare("UPDATE users SET email = ?, avatar = ?, admin_layout = ?, admin_skin = ? WHERE id = ?");
            $stmt->execute([$email, $avatar, $adminLayout, $adminSkin, $myId]);
          }
          // Update session
          $_SESSION['user_avatar'] = $avatar;
          $_SESSION['admin_layout'] = $adminLayout;
          $_SESSION['admin_skin'] = $adminSkin;
          $pdo->commit();
        } catch (Exception $e) {
          if ($pdo->inTransaction()) {
            $pdo->rollBack();
          }
          throw $e;
        }
        set_flash(_t('msg_profile_updated'));
        break;

      // Add user
      case 'add_user':
        $redirectTab = 'users';
        $newUser = trim(Routing::getString($_POST, 'new_username'));
        $newEmail = trim(Routing::getString($_POST, 'new_email'));
        $newPass = Routing::getString($_POST, 'password');
        $newPassConf = Routing::getString($_POST, 'password_confirm');
        $role = Routing::getString($_POST, 'role', 'editor');
        if (!in_array($role, ['admin', 'editor']))
          $role = 'editor';

        $useCustomPerms = isset($_POST['use_custom_perms']);
        $userPerms = $useCustomPerms ? json_encode($_POST['user_perms'] ?? [], JSON_UNESCAPED_UNICODE) : null;

        if (empty($newUser) || empty($newPass))
          throw new Exception(_t('username_and_password_required'));
        if (empty($newEmail))
          throw new Exception(_t('email_required'));
        if ($newPass !== $newPassConf)
          throw new Exception(_t('err_password_mismatch'));
        if (strlen($newPass) < 8 || !preg_match('/[A-Za-z]/', $newPass) || !preg_match('/[0-9]/', $newPass))
          throw new Exception(_t('err_pass_len'));
        if (strlen($newPass) > 256)
          throw new Exception(_t('err_pass_max_len'));
        $check = $pdo->prepare("SELECT count(*) FROM users WHERE LOWER(username) = LOWER(?)");
        $check->execute([$newUser]);
        if ($check->fetchColumn() > 0)
          throw new Exception(_t('err_user_exists'));
        if (!empty($newEmail)) {
          $check = $pdo->prepare("SELECT count(*) FROM users WHERE email = ? AND email != ''");
          $check->execute([$newEmail]);
          if ($check->fetchColumn() > 0)
            throw new Exception(_t('err_email_exists'));
        }
        $hash = password_hash($newPass, PASSWORD_DEFAULT);
        // Insert user
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, created_at, permissions) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$newUser, $hash, $newEmail, $role, $now, $userPerms]);
        set_flash(_t('msg_user_added'));
        break;

      case 'delete_user':
        $redirectTab = 'users';
        $delId = (int)$_POST['delete_user'];
        $currentId = App::user()['id'] ?? 0;
        grinds_delete_user($pdo, $delId, $currentId);
        set_flash(_t('msg_user_deleted'));
        break;

      // Edit user
      case 'edit_user':
        $redirectTab = 'users';
        $pdo->beginTransaction();
        try {
          $targetId = (int)$_POST['target_id'];
          $email = trim(Routing::getString($_POST, 'new_email'));
          $newPass = Routing::getString($_POST, 'password');
          $newPassConf = Routing::getString($_POST, 'password_confirm');
          $currentPass = Routing::getString($_POST, 'current_password');
          $role = Routing::getString($_POST, 'role', 'editor');
          if (!in_array($role, ['admin', 'editor']))
            $role = 'editor';

          $useCustomPerms = isset($_POST['use_custom_perms']);
          $userPerms = $useCustomPerms ? json_encode($_POST['user_perms'] ?? [], JSON_UNESCAPED_UNICODE) : null;

          // Verify current password
          if (empty($currentPass))
            throw new Exception(_t('req_current_password'));
          $stmtUser = $pdo->prepare("SELECT password FROM users WHERE id = ?");
          $stmtUser->execute([App::user()['id']]);
          $storedHash = $stmtUser->fetchColumn();
          if (!password_verify($currentPass, $storedHash))
            throw new Exception(_t('err_current_password'));

          // Prevent role change
          if ($targetId == App::user()['id']) {
            // Keep role
            $stmtRole = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmtRole->execute([$targetId]);
            $role = $stmtRole->fetchColumn();
          }

          if (!empty($email)) {
            $check = $pdo->prepare("SELECT count(*) FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $targetId]);
            if ($check->fetchColumn() > 0)
              throw new Exception(_t('err_email_exists'));
          }

          if (!empty($newPass)) {
            if ($newPass !== $newPassConf)
              throw new Exception(_t('err_password_mismatch'));
            if (strlen($newPass) < 8 || !preg_match('/[A-Za-z]/', $newPass) || !preg_match('/[0-9]/', $newPass))
              throw new Exception(_t('err_pass_len'));
            if (strlen($newPass) > 256)
              throw new Exception(_t('err_pass_max_len'));
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            // Update role
            $stmt = $pdo->prepare("UPDATE users SET email = ?, password = ?, role = ?, permissions = ? WHERE id = ?");
            $stmt->execute([$email, $hash, $role, $userPerms, $targetId]);

            // Invalidate existing tokens on password change
            $pdo->prepare("DELETE FROM user_tokens WHERE user_id = ?")->execute([$targetId]);
            grinds_invalidate_user_sessions($targetId);
          } else {
            // Update role
            $stmt = $pdo->prepare("UPDATE users SET email = ?, role = ?, permissions = ? WHERE id = ?");
            $stmt->execute([$email, $role, $userPerms, $targetId]);
          }

          if ($targetId == App::user()['id']) {
            $_SESSION['user_permissions'] = $userPerms;
          }
          $pdo->commit();
        } catch (Exception $e) {
          if ($pdo->inTransaction()) {
            $pdo->rollBack();
          }
          throw $e;
        }
        set_flash(_t('msg_saved'));
        break;

      // Update permissions
      case 'update_permissions':
        $redirectTab = 'users';
        $perms = $_POST['perms'] ?? [];
        update_option('editor_permissions', json_encode($perms, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE));
        set_flash(_t('msg_saved'));
        break;

      // Update mail
      case 'update_mail':
        $redirectTab = 'mail';
        $pdo->beginTransaction();
        try {
          grinds_save_settings_from_post(['smtp_host', 'smtp_user', 'smtp_port', 'smtp_encryption', 'smtp_from', 'smtp_admin_email']);

          if (isset($_POST['smtp_pass']) && $_POST['smtp_pass'] !== '') {
            update_option('smtp_pass', grinds_encrypt($_POST['smtp_pass']));
          }
          $pdo->commit();
        } catch (Exception $e) {
          $pdo->rollBack();
          throw $e;
        }
        if (!empty($_POST['send_test_mail'])) {
          // Load mailer
          require_once __DIR__ . '/../lib/mail.php';

          // Prepare config
          $config = [
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_user' => $_POST['smtp_user'] ?? '',
            'smtp_port' => $_POST['smtp_port'] ?? 587,
            'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
            'smtp_from' => $_POST['smtp_from'] ?? '',
            // Pass password
            'smtp_pass' => !empty($_POST['smtp_pass']) ? $_POST['smtp_pass'] : grinds_decrypt(get_option('smtp_pass'))
          ];

          try {
            $mailer = new SimpleMailer($config);
            $siteName = get_option('site_name', CMS_NAME);
            $subject = "[{$siteName}] SMTP Test Mail";
            $body = "Hello,\n\n";
            $body .= "This is a test email sent from your GrindSite installation.\n";
            $body .= "If you are reading this, your SMTP settings are configured correctly!\n\n";
            $body .= "Site: {$siteName}\n";
            $body .= "URL: " . resolve_url('/') . "\n";
            $body .= "Date: " . date('Y-m-d H:i:s') . "\n";

            $mailer->send($_POST['smtp_admin_email'], $subject, $body);
            set_flash(_t('mail_test_sent'), 'success');
          } catch (Exception $e) {
            set_flash(_t('err_failed_send_email') . ' (' . $e->getMessage() . ')', 'error');
          }
        } else {
          set_flash(_t('msg_saved'));
        }
        break;

      // Update security
      case 'update_security':
        $redirectTab = 'security';
        $pdo->beginTransaction();
        try {
          grinds_save_settings_from_post(
            ['session_timeout', 'security_max_attempts', 'security_lockout_time', 'preview_shared_password', 'iframe_allowed_domains'],
            ['secure_preview_mode']
          );
          $pdo->commit();
        } catch (Exception $e) {
          $pdo->rollBack();
          throw $e;
        }
        set_flash(_t('msg_saved'));
        break;

      // Update integration
      case 'update_integration':
        $redirectTab = 'integration';
        $pdo->beginTransaction();
        try {
          grinds_save_settings_from_post(['google_analytics_id', 'official_social_links']);

          $scriptKeys = ['custom_head_scripts', 'custom_footer_scripts'];
          foreach ($scriptKeys as $k) {
            if (isset($_POST[$k])) {
              // Enable autoload
              update_option($k, Routing::convertToDbUrl($_POST[$k]), 1);
            }
          }
          if (isset($_POST['share_buttons'])) {
            $buttonsJson = $_POST['share_buttons'];
            $buttons = json_decode((string)$buttonsJson, true);
            if (is_array($buttons)) {
              // Sanitize and save
              $sanitized = [];
              foreach ($buttons as $btn) {
                $sanitized[] = [
                  'id' => preg_replace('/[^a-zA-Z0-9_-]/', '', $btn['id'] ?? uniqid()),
                  'name' => $btn['name'] ?? '',
                  'url' => filter_var($btn['url'] ?? '', FILTER_SANITIZE_URL),
                  'icon' => preg_replace('/[^a-zA-Z0-9_-]/', '', $btn['icon'] ?? 'outline-link'),
                  'color' => preg_match('/^#[0-9a-fA-F]{3,6}$/', $btn['color'] ?? '') ? $btn['color'] : '#888888',
                  'enabled' => !empty($btn['enabled'])
                ];
              }
              update_option('share_buttons', json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE));
            }
          }
          $pdo->commit();
        } catch (Exception $e) {
          $pdo->rollBack();
          throw $e;
        }
        set_flash(_t('msg_saved'));
        break;

      // Update backup
      case 'update_backup_settings':
        $redirectTab = 'backup';
        $pdo->beginTransaction();
        try {
          if (isset($_POST['backup_retention_limit'])) {
            $limit = (int)$_POST['backup_retention_limit'];
            update_option('backup_retention_limit', $limit);

            grinds_rotate_backups('grinds_backup_', $limit);
            grinds_rotate_backups('auto_login_', $limit);
          }
          if (isset($_POST['login_backup_frequency'])) {
            update_option('login_backup_frequency', Routing::getString($_POST, 'login_backup_frequency'));
          }
          if (isset($_POST['auto_backup_limit_mb'])) {
            update_option('auto_backup_limit_mb', (int)$_POST['auto_backup_limit_mb']);
          }
          $pdo->commit();
        } catch (Exception $e) {
          $pdo->rollBack();
          throw $e;
        }
        set_flash(_t('msg_saved'));
        break;

      // Create backup
      case 'create_backup':
        $redirectTab = 'backup';
        $note = trim(Routing::getString($_POST, 'backup_note'));
        $safeNote = preg_replace('/[^a-zA-Z0-9\-_]/', '', $note);
        $suffix = $safeNote ? '_' . $safeNote : '';
        $filename = 'grinds_backup_' . date('Ymd_His') . $suffix . '.db';

        try {
          grinds_create_backup($filename);
        } catch (Exception $e) {
          throw new Exception(_t('err_backup_create') . " (" . $e->getMessage() . ")");
        }

        $limit = (int)get_option('backup_retention_limit', 10);
        if ($limit < 1)
          $limit = 10;

        grinds_rotate_backups('grinds_backup_', $limit);

        set_flash(_t('msg_backup_created') . $filename);
        break;

      case 'delete_backup':
        $redirectTab = 'backup';
        $file = basename(Routing::getString($_POST, 'delete_backup'));
        $target = $backup_dir . $file;
        if (file_exists($target) && is_file($target)) {
          grinds_force_unlink($target);
          set_flash(_t('msg_deleted'));
        }
        break;

      case 'restore_backup':
        $redirectTab = 'backup';
        $file = basename(Routing::getString($_POST, 'restore_backup'));
        $source = $backup_dir . $file;
        $dest = DB_FILE;
        $maintenanceFile = ROOT_PATH . '/.maintenance';

        if (file_exists($source)) {
          // Check if maintenance mode was already active
          $wasMaintenance = file_exists($maintenanceFile);

          // Enable maintenance mode to block new requests
          if (!$wasMaintenance) {
            file_put_contents($maintenanceFile, 'Restoring backup...');
          }

          try {
            // Flush WAL to DB before backup to ensure .bak is complete
            $pdo->exec('PRAGMA wal_checkpoint(TRUNCATE)');

            $pdo = null;
            unset($pdo);
            if (class_exists('App'))
              App::bind('db', null);
            if (function_exists('gc_collect_cycles'))
              gc_collect_cycles();
            usleep(500000);
            @copy($dest, $dest . '.bak');
            grinds_force_unlink($dest . '-wal');
            grinds_force_unlink($dest . '-shm');

            // 1. Extract to temp file to prevent corruption on disk full
            $tempDest = $dest . '.tmp';
            if (!copy($source, $tempDest)) {
              throw new Exception(_t('err_restore_failed') . ' (Failed to extract backup)');
            }

            // 2. Replace production DB (try rename, fallback to copy)
            $restored = @rename($tempDest, $dest);
            if (!$restored) {
              $restored = @copy($tempDest, $dest);
            }
            @unlink($tempDest);

            if ($restored) {
              if (!$wasMaintenance && file_exists($maintenanceFile)) {
                grinds_force_unlink($maintenanceFile);
              }
              grinds_logout();
              _safe_session_start();
              set_flash(_t('msg_restore_complete'));
              redirect('admin/login.php');
            } else {
              // Rollback from .bak if replacement fails
              if (file_exists($dest . '.bak')) {
                @copy($dest . '.bak', $dest);
              }
              throw new Exception(_t('err_restore_failed'));
            }
          } catch (Exception $e) {
            if (!$wasMaintenance && file_exists($maintenanceFile)) {
              grinds_force_unlink($maintenanceFile);
            }
            throw $e;
          }
        } else {
          throw new Exception(_t('err_source_file_not_found'));
        }
        break;

      case 'download_backup':
        $redirectTab = 'backup';
        $file = basename(Routing::getString($_POST, 'download_backup'));
        $path = $backup_dir . $file;
        if (file_exists($path) && is_file($path) && strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'db') {
          while (ob_get_level())
            ob_end_clean();
          header('Content-Description: File Transfer');
          header('Content-Type: application/octet-stream');
          header('Content-Disposition: attachment; filename="' . basename($path) . '"');
          header('Content-Length: ' . filesize($path));

          header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
          header('Pragma: no-cache');
          header('Expires: 0');

          if (function_exists('set_time_limit'))
            set_time_limit(0);
          $handle = fopen($path, 'rb');
          while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
          }
          fclose($handle);
          exit;
        } else {
          throw new Exception(_t('err_file_not_found'));
        }

        // Optimize DB
      case 'optimize_db':
        $redirectTab = 'system';

        try {
          grinds_optimize_database(false);
          set_flash(_t('msg_opt_db_success'));
          if (!empty($_POST['ajax_mode'])) {
            json_response(['success' => true, 'message' => _t('msg_opt_db_success')]);
          }
        } catch (Exception $e) {
          $error = $e->getMessage();
          if (!empty($_POST['ajax_mode'])) {
            json_response(['success' => false, 'error' => $error]);
          }
        }

        break;

      case 'clear_all_cache':
        $redirectTab = 'system';
        if (function_exists('clear_page_cache')) {
          clear_page_cache();
        }
        unset($_SESSION['grinds_health_report_cache']);
        update_option('grinds_dangerous_files_cache', '', false);
        set_flash(_t('msg_cache_cleared'));
        break;

      case 'update_debug_mode':
        $redirectTab = 'system';
        $pdo->beginTransaction();
        try {
          grinds_save_settings_from_post([], ['debug_mode']);
          $pdo->commit();
        } catch (Exception $e) {
          $pdo->rollBack();
          throw $e;
        }
        set_flash(_t('msg_saved'));
        break;

      case 'update_proxy_settings':
        $redirectTab = 'system';
        $pdo->beginTransaction();
        try {
          grinds_save_settings_from_post(['trusted_proxy_ips'], ['trust_proxies']);
          $pdo->commit();
        } catch (Exception $e) {
          $pdo->rollBack();
          throw $e;
        }
        set_flash(_t('msg_saved'));
        break;

      case 'install_sample_templates':
        $redirectTab = 'system';
        $pdo->beginTransaction();
        try {
          // Load sample data
          Grinds_InstallSampleData($pdo, get_option('site_lang', 'en'), get_option('site_name', 'GrindSite'));
          $pdo->commit();
          set_flash(_t('msg_sample_data_installed'));
        } catch (Exception $e) {
          $pdo->rollBack();
          throw $e;
        }
        break;

      case 'delete_installer':
        $redirectTab = 'system';
        $installer = ROOT_PATH . '/install.php';
        if (file_exists($installer)) {
          if (grinds_force_unlink($installer)) {
            unset($_SESSION['grinds_health_report_cache']);
            set_flash(_t('msg_deleted'));
          } else {
            throw new Exception(_t('err_delete_installer_failed'));
          }
        } else {
          set_flash('Install file not found.');
        }
        break;

      case 'reset_settings':
        // Preserve license key
        $licenseKey = get_option('license_key');
        $dbVersion = get_option('db_version', defined('GRINDS_DB_SCHEMA_VERSION') ? GRINDS_DB_SCHEMA_VERSION : 1);

        $pdo->exec("DELETE FROM settings");
        $initLang = defined('SITE_LANG') ? SITE_LANG : 'en';
        $stmtInit = $pdo->prepare("INSERT INTO settings (key, value) VALUES (?, ?)");
        $stmtInit->execute(['site_lang', $initLang]);
        $stmtInit->execute(['site_name', defined('SITE_NAME') ? SITE_NAME : 'GrindSite']);
        $stmtInit->execute(['db_version', $dbVersion]);

        if ($licenseKey) {
          $stmtInit->execute(['license_key', $licenseKey]);
        }
        grinds_logout();
        redirect('admin/login.php');
        break;

      // Perform update
      case 'perform_update':
        $redirectTab = 'update';
        // Load updater
        require_once __DIR__ . '/../lib/updater.php';
        $updater = new GrindsUpdater($pdo);
        $check = $updater->check();
        if ($check['has_update']) {
          $zipUrl = $check['remote']['download_url'];
          $tmpZip = ROOT_PATH . '/data/tmp/update.zip';
          if (!is_dir(dirname($tmpZip)))
            mkdir(dirname($tmpZip), 0755, true);
          $zipContent = grinds_fetch_url($zipUrl, [
            'timeout' => 30,
            'max_size' => 20 * 1024 * 1024
          ]);
          if ($zipContent === false)
            throw new Exception(_t('err_download_failed'));
          file_put_contents($tmpZip, $zipContent);
          if ($updater->update($tmpZip))
            set_flash(_t('msg_update_success', $check['remote']['version']));
          else
            throw new Exception(_t('err_update_failed'));
        } else {
          throw new Exception(_t('msg_no_update_needed'));
        }
        break;
    }

    // Clear cache
    if (!in_array($action, ['create_backup', 'delete_backup', 'download_backup', 'clear_all_cache'])) {
      if (function_exists('clear_page_cache')) {
        clear_page_cache();
      }
      unset($_SESSION['grinds_health_report_cache']);
    }

    // Redirect
    redirect('admin/settings.php?tab=' . $redirectTab);
  } catch (Exception $e) {
    $msg = $e->getMessage();
    // Check for unique constraint violation from DB
    if (stripos($msg, 'UNIQUE constraint failed') !== false || stripos($msg, 'Duplicate entry') !== false) {
      $error = _t('err_duplicate_entry');
    } else {
      $error = $msg;
    }

    if (!isset($pdo) || $pdo === null) {
      // Reconnect DB
      $pdo = grinds_db_connect();
      App::bind('db', $pdo);
    }

    $params['tab'] = $redirectTab;
  }
}

// Prepare settings
$opt = [];
$system_defaults = Grinds_GetDefaultSettings(get_option('site_lang', 'en'));
$settings_keys = array_keys($system_defaults);

// Map keys
foreach ($settings_keys as $k) {
  $viewKey = $k;
  if ($k == 'site_name')
    $viewKey = 'name';
  if ($k == 'site_description')
    $viewKey = 'desc';
  if ($k == 'site_footer_text')
    $viewKey = 'footer';
  if ($k == 'site_ogp_image')
    $viewKey = 'ogp_img';
  if ($k == 'admin_show_site_name')
    $viewKey = 'show_site_name';
  if ($k == 'admin_show_logo_login')
    $viewKey = 'show_logo_login';
  if ($k == 'site_favicon')
    $viewKey = 'favicon';
  if ($k == 'posts_per_page')
    $viewKey = 'per_page';
  if ($k == 'title_format')
    $viewKey = 'title_fmt';
  if ($k == 'date_format')
    $viewKey = 'date_fmt';
  if ($k == 'site_lang')
    $viewKey = 'lang';
  if ($k == 'site_theme')
    $viewKey = 'theme';
  if ($k == 'admin_skin')
    $viewKey = 'skin';
  if ($k == 'admin_layout')
    $viewKey = 'layout';
  if ($k == 'smtp_admin_email')
    $viewKey = 'smtp_admin';
  if ($k == 'smtp_encryption')
    $viewKey = 'smtp_enc';
  if ($k == 'session_timeout')
    $viewKey = 'sess_timeout';
  if ($k == 'security_max_attempts')
    $viewKey = 'sec_attempts';
  if ($k == 'security_lockout_time')
    $viewKey = 'sec_time';
  if ($k == 'secure_preview_mode')
    $viewKey = 'secure_preview';
  if ($k == 'preview_shared_password')
    $viewKey = 'preview_password';
  if ($k == 'iframe_allowed_domains')
    $viewKey = 'iframe_domains';
  if ($k == 'google_analytics_id')
    $viewKey = 'ga_id';
  if ($k == 'custom_head_scripts')
    $viewKey = 'head_scripts';
  if ($k == 'custom_footer_scripts')
    $viewKey = 'footer_scripts';
  if ($k == 'backup_retention_limit')
    $viewKey = 'bk_limit';
  if ($k == 'login_backup_frequency')
    $viewKey = 'login_bk_freq';
  if (strpos($k, 'custom_skin_') === 0) {
    $viewKey = str_replace('custom_skin_', 'c_', $k);
  }
  if ($k == 'media_max_width')
    $viewKey = 'media_max_width';
  if ($k == 'media_quality')
    $viewKey = 'media_quality';

  // Get option
  $val = get_option($k);
  if ($val === '' && isset($system_defaults[$k])) {
    $val = $system_defaults[$k];
  }

  if (in_array($k, ['custom_head_scripts', 'custom_footer_scripts', 'custom_skin_font_url', 'custom_skin_media_bg_css'])) {
    $val = grinds_url_to_view($val);
  } elseif ($k === 'smtp_pass') {
    $val = !empty($val);
  }
  $opt[$viewKey] = $val;
}

// Pre-populate custom skin editor with active skin values
$activeSkinKey = $opt['skin'] ?? 'default';
if ($activeSkinKey !== 'custom') {
  $activeSkinFile = ROOT_PATH . '/admin/skins/' . basename($activeSkinKey) . '.json';
  if (file_exists($activeSkinFile)) {
    $activeSkinJson = json_decode(file_get_contents($activeSkinFile), true);
    if (is_array($activeSkinJson)) {
      foreach ($activeSkinJson as $key => $val) {
        if ($key === 'colors' && is_array($val)) {
          foreach ($val as $cKey => $cVal) {
            $optKey = 'c_' . str_replace('-', '_', $cKey);
            $opt[$optKey] = $cVal;
          }
        } elseif (!in_array($key, ['name', 'description', 'css', 'is_dark', 'is_sidebar_dark'], true)) {
          $optKey = 'c_' . $key;
          if ($key === 'font')
            $optKey = 'c_font_family';
          if (in_array($key, ['font_url', 'media_bg_css'], true) && function_exists('grinds_url_to_view')) {
            $val = grinds_url_to_view($val);
          }
          $opt[$optKey] = is_scalar($val) ? $val : '';
        }
      }
    }
  }
}

// Define tabs
$tabs = [
  'general' => ['label' => 'tab_general', 'icon' => 'outline-cog-6-tooth'],
  'display' => ['label' => 'tab_display', 'icon' => 'outline-tv'],
  'theme' => ['label' => 'tab_theme', 'icon' => 'outline-swatch'],
  'profile' => ['label' => 'st_profile_title', 'icon' => 'outline-user-circle'],
  'users' => ['label' => 'tab_users', 'icon' => 'outline-users'],
  'mail' => ['label' => 'tab_mail', 'icon' => 'outline-envelope'],
  'security' => ['label' => 'tab_security', 'icon' => 'outline-shield-check'],
  'integration' => ['label' => 'tab_integration', 'icon' => 'outline-chart-bar-square'],
  'backup' => ['label' => 'tab_backup', 'icon' => 'outline-server'],
  'system' => ['label' => 'tab_system', 'icon' => 'outline-cpu-chip'],
  'update' => ['label' => 'tab_update', 'icon' => 'outline-arrow-path'],
];

// Filter tabs
if (!current_user_can('manage_settings')) {
  $tabs = array_filter($tabs, function ($key) {
    return $key === 'profile';
  }, ARRAY_FILTER_USE_KEY);
}

$init_tab = Routing::getString($params, 'tab', 'general');

// Validate tab
if (!array_key_exists($init_tab, $tabs)) {
  $init_tab = array_key_first($tabs);
}

$page_title = _t('menu_settings');
$current_page = 'settings';

ob_start();
require_once __DIR__ . '/views/settings.php';
$content = ob_get_clean();

require_once __DIR__ . '/layout/loader.php';
