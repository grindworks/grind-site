<?php

/**
 * Handle database connection and schema migrations.
 */
if (!defined('DB_FILE'))
  require_once __DIR__ . '/../config.php';

require_once __DIR__ . '/App.php';
require_once __DIR__ . '/functions.php';

if (!defined('GRINDS_DB_SCHEMA_VERSION'))
  define('GRINDS_DB_SCHEMA_VERSION', 8);

try {
  if (!defined('GRINDS_SKIP_DB_INIT')) {
    $pdo = grinds_db_connect();

    // Bind PDO
    App::bind('db', $pdo);

    // Run migrations
    if (defined('GRINDS_ENABLE_MIGRATIONS') && GRINDS_ENABLE_MIGRATIONS) {
      grinds_db_migrate($pdo);
    }
  }
} catch (Exception $e) {

  if (!headers_sent())
    http_response_code(500);
  error_log("GrindsCMS DB Error: " . $e->getMessage());
  $errorMsg = $e->getMessage();
  $isDebug = defined('DEBUG_MODE') && DEBUG_MODE;
  if (!$isDebug) {
    $errorMsg = "Database Connection Error.";
    if (str_contains($e->getMessage(), 'readonly database')) {
      $errorMsg = "Database is read-only.";
    }
  }

  $isAjax = function_exists('is_ajax_request') ? is_ajax_request() : false;

  $isReadOnly = (str_contains($e->getMessage(), 'readonly database') || str_contains($e->getMessage(), 'attempt to write'));

  $lang = function_exists('grinds_detect_language') ? grinds_detect_language() : 'en';

  if ($isAjax) {
    $jsonError = 'Database Connection Error';
    $jsonMsg = $isReadOnly ? 'Database file is read-only. Please check file permissions.' : $errorMsg;

    if ($lang === 'ja') {
      $jsonError = 'データベース接続エラー';
      $jsonMsg = $isReadOnly ? 'データベースファイルが読み取り専用になっています。パーミッションを確認してください。' : ($isDebug ? $errorMsg : 'データベースに接続できません。');
    }

    json_response([
      'success' => false,
      'error' => $jsonError,
      'message' => $jsonMsg
    ], 500);
  }

  $errData = [
    'en' => [
      'title' => '500 - Database Connection Error',
      'header' => 'System Error',
      'status' => '500 System Error',
      'heading' => 'Database Connection Failed',
      'message' => 'The system could not connect to the database.<br>Please ensure the <code>data</code> directory is writable and the database file exists.',
      'hint_readonly' => '<div class="bg-yellow-50 mt-4 p-3 border border-yellow-200 rounded text-yellow-800 text-sm"><strong>Hint:</strong> The database file is read-only. Check permissions.</div>',
      'action_title' => 'Suggested Actions',
      'reload' => 'Reload Page',
    ],
    'ja' => [
      'title' => '500 - データベース接続エラー',
      'header' => 'システムエラー',
      'status' => '500 System Error',
      'heading' => 'データベースに接続できません',
      'message' => 'システムがデータベースファイルを開けませんでした。<br><code>data</code> ディレクトリの書き込み権限を確認してください。',
      'hint_readonly' => '<div class="bg-yellow-50 mt-4 p-3 border border-yellow-200 rounded text-yellow-800 text-sm"><strong>ヒント：</strong>データベースファイルが読み取り専用になっています。パーミッションを確認してください。</div>',
      'action_title' => '推奨される操作',
      'reload' => 'ページを再読み込み',
    ]
  ];

  $t = $errData[$lang] ?? $errData['en'];

  if ($isReadOnly) {
    $t['message'] .= $t['hint_readonly'];
  }

  $errorLayoutPath = __DIR__ . '/layout/error_config.php';
  if (file_exists($errorLayoutPath)) {
    require_once $errorLayoutPath;
  } else {
    echo "<h1>System Error</h1><p>" . htmlspecialchars($errorMsg) . "</p>";
    if ($isReadOnly) {
      echo $t['hint_readonly'];
    }
  }
  exit;
}

/**
 * Connect to the database and apply optimizations.
 *
 * @return PDO
 * @throws Exception
 */
function grinds_db_connect()
{
  if (!defined('DB_FILE')) {
    throw new Exception("System Error: DB_FILE is not defined.");
  }

  // Resolve absolute path for DB_FILE to ensure cron/CLI compatibility
  $db_path = grinds_get_db_path();

  // Secure data directory.
  $dbDir = dirname($db_path);
  if (!grinds_secure_dir($dbDir)) {
    throw new Exception('Failed to create or secure data directory.');
  }

  if (file_exists($db_path) && !is_writable($db_path)) {
    throw new Exception("Database file is read-only. Please check file permissions.");
  }

  // Connect to database.
  $pdo = new PDO("sqlite:" . $db_path, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_TIMEOUT => 60 // 60 seconds
  ]);

  // Enable Foreign Keys
  $pdo->exec("PRAGMA foreign_keys = ON;");

  // Optimize performance.
  $enableWal = defined('ENABLE_WAL_MODE') ? ENABLE_WAL_MODE : true;
  try {
    $pdo->exec("PRAGMA busy_timeout = 30000;");

    if ($enableWal) {
      $stmt = $pdo->query("PRAGMA journal_mode = WAL;");
      $mode = $stmt->fetchColumn();
      if (strtoupper($mode) !== 'WAL') {
        throw new Exception("SQLite refused WAL mode. Current mode: " . $mode);
      }
      $pdo->exec("PRAGMA synchronous = NORMAL;");

      // Register shutdown handler to checkpoint WAL periodically
      // This prevents WAL bloat and reduces risk for manual backups (FTP)
      if (!defined('GRINDS_WAL_CHECKPOINT_REGISTERED')) {
        define('GRINDS_WAL_CHECKPOINT_REGISTERED', true);
        register_shutdown_function(function () use ($db_path) {
          // Adjust probability based on request type
          // Admin/API: 10%, Frontend: 1% to prevent blocking user navigation
          $script = $_SERVER['SCRIPT_NAME'] ?? '';
          $isAdmin = str_contains($script, '/admin/') || str_contains($script, '/api/');
          $chance = $isAdmin ? 10 : 1;

          if (mt_rand(1, 100) <= $chance) {
            if (function_exists('fastcgi_finish_request')) {
              @fastcgi_finish_request();
            }
            try {
              $shutdownPdo = new PDO("sqlite:" . $db_path);
              $shutdownPdo->exec("PRAGMA wal_checkpoint(PASSIVE);");
            } catch (Exception $e) { /* Ignore */
            }
          }
        });
      }
    } else {
      $pdo->exec("PRAGMA journal_mode = DELETE;");
      $pdo->exec("PRAGMA synchronous = FULL;");
    }
  } catch (Exception $e) {
    // Fallback to DELETE mode
    try {
      $pdo->exec("PRAGMA journal_mode = DELETE;");
      $pdo->exec("PRAGMA synchronous = FULL;");
      if ($enableWal) {
        error_log("GrindsCMS Warning: WAL mode failed, fell back to DELETE mode. (" . $e->getMessage() . ")");
      }
    } catch (Exception $e2) {
      throw new Exception("Failed to set database journal mode: " . $e2->getMessage());
    }
  }

  return $pdo;
}

/**
 * Get current SQLite journal mode.
 */
function grinds_get_db_journal_mode()
{
  try {
    $pdo = App::db();
    if (!$pdo)
      return 'UNKNOWN';
    $stmt = $pdo->query("PRAGMA journal_mode");
    $mode = $stmt->fetchColumn();
    return $mode ? strtoupper($mode) : 'UNKNOWN';
  } catch (Exception $e) {
    return 'ERROR';
  }
}

/**
 * Check if FTS5 is enabled.
 * @return bool
 */
function grinds_is_fts5_enabled()
{
  static $is_enabled = null;
  if ($is_enabled !== null) {
    return $is_enabled;
  }

  try {
    $pdo = App::db();
    if (!$pdo) {
      return $is_enabled = true; // Assume enabled if DB is not available, to avoid false positives
    }
    // Try to create a temporary FTS5 table
    $pdo->exec("CREATE VIRTUAL TABLE fts5_test_temp USING fts5(content)");
    $pdo->exec("DROP TABLE fts5_test_temp");
    $is_enabled = true;
  } catch (Exception $e) {
    $is_enabled = false;
  }
  return $is_enabled;
}

/**
 * Execute database migrations.
 *
 * @param PDO $pdo
 * @return void
 */
function grinds_db_migrate($pdo)
{
  $current_db_version = GRINDS_DB_SCHEMA_VERSION;
  $installed_version = 0;

  try {
    // Optimization: Use get_option to avoid sqlite_master query and leverage caching
    if (function_exists('get_option')) {
      $ver = get_option('db_version');
      if ($ver !== false && $ver !== '' && $ver !== null) {
        $installed_version = (int)$ver;
      }
    }
  } catch (Exception $e) {
  }

  // Check schema mismatches
  if ($installed_version < $current_db_version) {
    // Begin transaction
    $pdo->beginTransaction();
    try {
      // Create tables
      $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              slug TEXT UNIQUE,
              title TEXT,
              content TEXT,
              search_text TEXT DEFAULT '',
              description TEXT,
              category_id INTEGER,
              type TEXT DEFAULT 'post',
              status TEXT DEFAULT 'draft',
              thumbnail TEXT,
              hero_image TEXT DEFAULT '',
              hero_settings TEXT DEFAULT '',
              page_theme TEXT DEFAULT '',
              published_at DATETIME,
              deleted_at DATETIME DEFAULT NULL,
              is_noindex INTEGER DEFAULT 0,
              is_nofollow INTEGER DEFAULT 0,
              is_noarchive INTEGER DEFAULT 0,
              is_hide_rss INTEGER DEFAULT 0,
              is_hide_llms INTEGER DEFAULT 0,
              show_toc INTEGER DEFAULT 0,
              toc_title TEXT DEFAULT 'Contents',
              show_category INTEGER DEFAULT 1,
              show_date INTEGER DEFAULT 1,
              show_share_buttons INTEGER DEFAULT 1,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              version INTEGER DEFAULT 1
          )");

      $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              name TEXT,
              slug TEXT UNIQUE,
              sort_order INTEGER DEFAULT 0,
              category_theme TEXT DEFAULT ''
          )");

      $pdo->exec("CREATE TABLE IF NOT EXISTS banners (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              position TEXT,
              image_url TEXT,
              link_url TEXT,
              target_type TEXT DEFAULT 'all',
              target_id INTEGER DEFAULT 0,
              sort_order INTEGER DEFAULT 0,
              is_active INTEGER DEFAULT 1,
              type TEXT DEFAULT 'image',
              html_code TEXT DEFAULT '',
              image_width INTEGER DEFAULT 100
          )");

      $pdo->exec("CREATE TABLE IF NOT EXISTS nav_menus (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              location TEXT,
              label TEXT,
              url TEXT,
              sort_order INTEGER DEFAULT 0,
              is_external INTEGER DEFAULT 0,
              target_theme TEXT DEFAULT 'all'
          )");

      $pdo->exec("CREATE TABLE IF NOT EXISTS tags (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              name TEXT,
              slug TEXT UNIQUE
          )");

      $pdo->exec("CREATE TABLE IF NOT EXISTS post_tags (
              post_id INTEGER,
              tag_id INTEGER,
              PRIMARY KEY (post_id, tag_id),
              FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE,
              FOREIGN KEY(tag_id) REFERENCES tags(id) ON DELETE CASCADE
          )");

      $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
              key TEXT PRIMARY KEY,
              value TEXT,
              autoload INTEGER DEFAULT 1
          )");

      $pdo->exec("CREATE TABLE IF NOT EXISTS users (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              username TEXT UNIQUE,
              password TEXT,
              email TEXT,
              avatar TEXT,
              created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
              role TEXT DEFAULT 'admin',
              admin_layout TEXT DEFAULT '',
              admin_skin TEXT DEFAULT '',
              permissions TEXT DEFAULT NULL
          )");

      $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
              ip_address TEXT PRIMARY KEY,
              attempts INTEGER DEFAULT 0,
              last_attempt_at DATETIME,
              locked_until DATETIME
          )");

      $pdo->exec("CREATE TABLE IF NOT EXISTS username_login_attempts (
              username TEXT PRIMARY KEY,
              attempts INTEGER DEFAULT 0,
              last_attempt_at DATETIME,
              locked_until DATETIME
          )");

      $pdo->exec("CREATE TABLE IF NOT EXISTS user_tokens (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              user_id INTEGER,
              selector TEXT,
              hashed_validator TEXT,
              expires_at DATETIME,
              FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
          )");

      $pdo->exec(
        "CREATE TABLE IF NOT EXISTS media (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT,
            filepath TEXT,
            file_type TEXT,
            file_size INTEGER,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            metadata TEXT DEFAULT '{}')"
      );

      $pdo->exec("CREATE TABLE IF NOT EXISTS widgets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            type TEXT,
            title TEXT,
            content TEXT,
            settings TEXT,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            target_theme TEXT DEFAULT 'all'
        )");

      $pdo->exec("CREATE TABLE IF NOT EXISTS media_tags (
            media_id INTEGER,
            tag_id INTEGER,
            PRIMARY KEY (media_id, tag_id),
            FOREIGN KEY(media_id) REFERENCES media(id) ON DELETE CASCADE,
            FOREIGN KEY(tag_id) REFERENCES tags(id) ON DELETE CASCADE
        )");

      // Update schema
      $rebuild_index = false;
      if ($installed_version > 0) {
        $cols = array_map('strtolower', $pdo->query("PRAGMA table_info(posts)")->fetchAll(PDO::FETCH_COLUMN, 1));
        if (!in_array('hero_image', $cols)) {
          $pdo->exec("ALTER TABLE posts ADD COLUMN hero_image TEXT DEFAULT ''");
        }
        if (!in_array('hero_settings', $cols)) {
          $pdo->exec("ALTER TABLE posts ADD COLUMN hero_settings TEXT DEFAULT ''");
        }
        if (!in_array('page_theme', $cols)) {
          $pdo->exec("ALTER TABLE posts ADD COLUMN page_theme TEXT DEFAULT ''");
        }
        if (!in_array('deleted_at', $cols)) {
          $pdo->exec("ALTER TABLE posts ADD COLUMN deleted_at DATETIME DEFAULT NULL");
        }
        if (!in_array('search_text', $cols)) {
          $pdo->exec("ALTER TABLE posts ADD COLUMN search_text TEXT DEFAULT ''");
          $rebuild_index = true;
        }

        if (!in_array('show_share_buttons', $cols)) {
          $pdo->exec("ALTER TABLE posts ADD COLUMN show_share_buttons INTEGER DEFAULT 1");
        }
        if (!in_array('is_hide_llms', $cols)) {
          $pdo->exec("ALTER TABLE posts ADD COLUMN is_hide_llms INTEGER DEFAULT 0");
        }
        if (!in_array('version', $cols)) {
          $pdo->exec("ALTER TABLE posts ADD COLUMN version INTEGER DEFAULT 1");
        }

        $cols = array_map('strtolower', $pdo->query("PRAGMA table_info(categories)")->fetchAll(PDO::FETCH_COLUMN, 1));
        if (!in_array('category_theme', $cols)) {
          $pdo->exec("ALTER TABLE categories ADD COLUMN category_theme TEXT DEFAULT ''");
        }

        $cols = array_map('strtolower', $pdo->query("PRAGMA table_info(nav_menus)")->fetchAll(PDO::FETCH_COLUMN, 1));
        if (!in_array('target_theme', $cols)) {
          $pdo->exec("ALTER TABLE nav_menus ADD COLUMN target_theme TEXT DEFAULT 'all'");
        }

        $cols = array_map('strtolower', $pdo->query("PRAGMA table_info(widgets)")->fetchAll(PDO::FETCH_COLUMN, 1));
        if (!in_array('target_theme', $cols)) {
          $pdo->exec("ALTER TABLE widgets ADD COLUMN target_theme TEXT DEFAULT 'all'");
        }

        $cols = array_map('strtolower', $pdo->query("PRAGMA table_info(banners)")->fetchAll(PDO::FETCH_COLUMN, 1));
        if (!in_array('target_type', $cols)) {
          $pdo->exec("ALTER TABLE banners ADD COLUMN target_type TEXT DEFAULT 'all'");
          $pdo->exec("ALTER TABLE banners ADD COLUMN target_id INTEGER DEFAULT 0");
        }
        if (!in_array('type', $cols)) {
          $pdo->exec("ALTER TABLE banners ADD COLUMN type TEXT DEFAULT 'image'");
        }
        if (!in_array('html_code', $cols)) {
          $pdo->exec("ALTER TABLE banners ADD COLUMN html_code TEXT DEFAULT ''");
        }
        if (!in_array('image_width', $cols)) {
          $pdo->exec("ALTER TABLE banners ADD COLUMN image_width INTEGER DEFAULT 100");
        }

        $cols = array_map('strtolower', $pdo->query("PRAGMA table_info(users)")->fetchAll(PDO::FETCH_COLUMN, 1));
        if (!in_array('avatar', $cols)) {
          $pdo->exec("ALTER TABLE users ADD COLUMN avatar TEXT DEFAULT ''");
        }
        if (!in_array('role', $cols)) {
          $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT DEFAULT 'admin'");
        }
        if (!in_array('admin_layout', $cols)) {
          $pdo->exec("ALTER TABLE users ADD COLUMN admin_layout TEXT DEFAULT ''");
        }
        if (!in_array('admin_skin', $cols)) {
          $pdo->exec("ALTER TABLE users ADD COLUMN admin_skin TEXT DEFAULT ''");
        }
        if (!in_array('permissions', $cols)) {
          $pdo->exec("ALTER TABLE users ADD COLUMN permissions TEXT DEFAULT NULL");
        }

        $cols = array_map('strtolower', $pdo->query("PRAGMA table_info(media)")->fetchAll(PDO::FETCH_COLUMN, 1));
        if (!in_array('metadata', $cols)) {
          $pdo->exec("ALTER TABLE media ADD COLUMN metadata TEXT DEFAULT '{}'");
        }

        $cols = array_map('strtolower', $pdo->query("PRAGMA table_info(settings)")->fetchAll(PDO::FETCH_COLUMN, 1));
        if (!in_array('autoload', $cols)) {
          $pdo->exec("ALTER TABLE settings ADD COLUMN autoload INTEGER DEFAULT 1");
        }
      }

      // Remove redundant index that is covered by idx_posts_front_list
      $pdo->exec("DROP INDEX IF EXISTS idx_posts_type_status");

      $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_category_id ON posts (category_id)");
      $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_published_at ON posts (published_at)");
      $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_deleted_at ON posts (deleted_at)");
      $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_updated_at ON posts (updated_at)");
      $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_created_at ON posts (created_at)");
      $pdo->exec("CREATE INDEX IF NOT EXISTS idx_post_tags_tag_id ON post_tags (tag_id)");
      $pdo->exec("CREATE INDEX IF NOT EXISTS idx_categories_slug ON categories (slug)");
      $pdo->exec("CREATE INDEX IF NOT EXISTS idx_media_tags_tag_id ON media_tags (tag_id)");
      $pdo->exec("CREATE INDEX IF NOT EXISTS idx_tags_slug ON tags (slug)");
      $pdo->exec("CREATE INDEX IF NOT EXISTS idx_login_attempts_last_attempt ON login_attempts (last_attempt_at)");
      $pdo->exec("CREATE INDEX IF NOT EXISTS idx_username_login_attempts_last_attempt ON username_login_attempts (last_attempt_at)");
      $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_tokens_selector ON user_tokens (selector)");
      $pdo->exec("CREATE INDEX IF NOT EXISTS idx_posts_front_list ON posts (type, status, deleted_at, published_at DESC)");
      $pdo->exec("CREATE INDEX IF NOT EXISTS idx_media_uploaded_at ON media (uploaded_at)");
      $pdo->exec("CREATE INDEX IF NOT EXISTS idx_media_filepath ON media (filepath)");

      try {
        $pdo->exec("DROP TABLE IF EXISTS posts_fts");
        $pdo->exec("DROP TRIGGER IF EXISTS posts_ai");
        $pdo->exec("DROP TRIGGER IF EXISTS posts_ad");
        $pdo->exec("DROP TRIGGER IF EXISTS posts_au");
      } catch (Exception $e) {
        // FTS5 disabled.
      }

      // Rebuild search index
      if ($rebuild_index && function_exists('grinds_rebuild_post_index')) {
        if (function_exists('set_time_limit'))
          @set_time_limit(300);
        grinds_rebuild_post_index($pdo);
      }

      try {
        $pdo->exec("CREATE VIRTUAL TABLE posts_fts USING fts5(title, search_text, content='posts', content_rowid='id', tokenize='unicode61 remove_diacritics 0')");

        $pdo->exec("CREATE TRIGGER posts_ai AFTER INSERT ON posts BEGIN
                INSERT INTO posts_fts(rowid, title, search_text) VALUES (new.id, new.title, COALESCE(new.search_text, ''));
            END;");
        $pdo->exec("CREATE TRIGGER posts_ad AFTER DELETE ON posts BEGIN
                INSERT INTO posts_fts(posts_fts, rowid, title, search_text) VALUES('delete', old.id, old.title, COALESCE(old.search_text, ''));
            END;");
        $pdo->exec("CREATE TRIGGER posts_au AFTER UPDATE ON posts BEGIN
                INSERT INTO posts_fts(posts_fts, rowid, title, search_text) VALUES('delete', old.id, old.title, COALESCE(old.search_text, ''));
                INSERT INTO posts_fts(rowid, title, search_text) VALUES (new.id, new.title, COALESCE(new.search_text, ''));
            END;");

        $pdo->exec("INSERT INTO posts_fts(rowid, title, search_text) SELECT id, title, search_text FROM posts");
      } catch (Exception $e) {
        // FTS5 disabled.
      }

      // Update version
      $stmt = $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES ('db_version', ?)");
      $stmt->execute([$current_db_version]);

      // Commit transaction
      $pdo->commit();
    } catch (Exception $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      throw $e;
    }
  }
}
