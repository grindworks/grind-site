<?php

/**
 * Handle system installation.
 */

declare(strict_types=1);

if (version_compare(PHP_VERSION, '8.3.0', '<')) {
  http_response_code(500);
  die('GrindSite requires PHP 8.3 or higher. Your current PHP version is: ' . PHP_VERSION);
}

if (file_exists(__DIR__ . '/config.php')) {
  if (!defined('GRINDS_APP')) {
    define('GRINDS_APP', true);
  }
  include __DIR__ . '/config.php';

  $lang = (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ja'])) ? $_GET['lang'] : ((isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && str_contains($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'ja')) ? 'ja' : 'en');

  $cmsName = defined('CMS_NAME') ? CMS_NAME : 'GrindSite';

  $txt = [
    'en' => [
      'title' => 'Already Installed',
      'msg' => $cmsName . ' is already configured.',
      'alert' => 'Security Warning',
      'alert_desc' => 'Please delete <strong>install.php</strong> immediately to prevent unauthorized re-initialization.',
      'btn_admin' => 'Go to Dashboard',
      'btn_home' => 'View Site',
      'reinstall_hint' => 'To reinstall, please delete <strong>config.php</strong> and <strong>.htaccess</strong>.',
    ],
    'ja' => [
      'title' => 'インストール済み',
      'msg' => $cmsName . ' はすでにセットアップされています。',
      'alert' => 'セキュリティ警告',
      'alert_desc' => '不正な再インストールを防ぐため、<strong>install.php</strong> を直ちに削除してください。',
      'btn_admin' => '管理画面へ',
      'btn_home' => 'サイトを表示',
      'reinstall_hint' => '再インストールを行う場合は、<strong>config.php</strong> と <strong>.htaccess</strong> を削除してください。',
    ]
  ];
  $t = $txt[$lang];

  $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
  $baseUrl = ($scriptDir === '/') ? '' : rtrim($scriptDir, '/');

  http_response_code(403);
?>
  <html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>"
    class="bg-slate-950 h-full antialiased selection:bg-primary-500/30">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
      <?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?> |
      <?= htmlspecialchars($cmsName, ENT_QUOTES, 'UTF-8') ?>
    </title>
    <link rel="stylesheet"
      href="<?= htmlspecialchars($baseUrl . '/assets/css/install.css', ENT_QUOTES, 'UTF-8') ?>?v=<?= (string)(filemtime(__DIR__ . '/assets/css/install.css') ?: time()) ?>">
  </head>

  <body class="relative flex justify-center items-center p-4 min-h-full text-slate-300 overflow-hidden">
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
      <div
        class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-primary-600/20 rounded-full mix-blend-screen filter blur-[100px] opacity-70 animate-blob">
      </div>
      <div
        class="absolute top-[20%] right-[-10%] w-[35%] h-[35%] bg-purple-600/20 rounded-full mix-blend-screen filter blur-[100px] opacity-70 animate-blob animation-delay-2000">
      </div>
      <div
        class="absolute bottom-[-10%] left-[20%] w-[40%] h-[40%] bg-blue-600/20 rounded-full mix-blend-screen filter blur-[100px] opacity-70 animate-blob animation-delay-4000">
      </div>
    </div>

    <div class="glass-panel relative z-10 p-8 rounded-2xl w-full max-w-md text-center">
      <div
        class="flex justify-center items-center bg-red-500/10 mx-auto mb-6 rounded-full border border-red-500/20 shadow-[0_0_30px_rgba(239,68,68,0.2)] w-16 h-16">
        <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 01-3-3h19.5a3 3 0 01-3 3m-13.5 0h13.5m-13.5 0a3 3 0 11-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6" />
        </svg>
      </div>
      <h1 class="mb-2 font-bold text-white text-3xl tracking-tight">
        <?= htmlspecialchars($t['title'], ENT_QUOTES, 'UTF-8') ?>
      </h1>
      <p class="mb-8 text-slate-400 text-sm">
        <?= htmlspecialchars($t['msg'], ENT_QUOTES, 'UTF-8') ?>
      </p>
      <div class="bg-red-500/10 mb-8 p-4 border border-red-500/20 rounded-xl text-left backdrop-blur-md">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="font-bold text-red-400 text-sm drop-shadow-sm">
              <?= htmlspecialchars($t['alert'], ENT_QUOTES, 'UTF-8') ?>
            </h3>
            <div class="mt-1 text-red-100/80 text-sm leading-relaxed">
              <?= $t['alert_desc'] ?>
            </div>
          </div>
        </div>
      </div>
      <p class="mb-6 text-slate-500 text-xs">
        <?= $t['reinstall_hint'] ?>
      </p>
      <div class="space-y-3">
        <a href="<?= htmlspecialchars($baseUrl . '/admin/login.php', ENT_QUOTES, 'UTF-8') ?>"
          class="block bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-500 hover:to-primary-400 shadow-[0_0_20px_rgba(15,98,254,0.3)] hover:shadow-[0_0_25px_rgba(15,98,254,0.5)] px-4 py-3.5 rounded-xl w-full font-bold text-white text-sm transition-all duration-300 transform hover:-translate-y-0.5">
          <?= htmlspecialchars($t['btn_admin'], ENT_QUOTES, 'UTF-8') ?>
        </a>
        <a href="<?= htmlspecialchars($baseUrl . '/', ENT_QUOTES, 'UTF-8') ?>"
          class="block bg-white/5 hover:bg-white/10 border border-white/10 px-4 py-3.5 rounded-xl w-full font-bold text-white text-sm transition-all duration-300">
          <?= htmlspecialchars($t['btn_home'], ENT_QUOTES, 'UTF-8') ?>
        </a>
      </div>
    </div>
  </body>

  </html>
  <?php
  exit;
}

$defaults = [
  'name' => 'GrindSite',
  'quote' => 'Grind Your Site to Perfection.',
  'sub' => 'Less system, more soul. Built in your palms.',
];

if (!function_exists('h')) {
  function h(string $s): string
  {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
  }
}

$infoFile = __DIR__ . '/lib/info.php';
if (file_exists($infoFile)) {
  require_once $infoFile;
}

$sysFile = __DIR__ . '/lib/functions/system.php';
if (file_exists($sysFile)) {
  if (!defined('GRINDS_APP')) {
    define('GRINDS_APP', true);
  }
  require_once $sysFile;
}

$optsFile = __DIR__ . '/lib/functions/options.php';
if (file_exists($optsFile)) {
  require_once $optsFile;
}

if (!defined('CMS_NAME')) {
  define('CMS_NAME', $defaults['name']);
}
if (!defined('CMS_VERSION')) {
  define('CMS_VERSION', 'Unknown');
}
if (!defined('CMS_QUOTE_EN')) {
  define('CMS_QUOTE_EN', $defaults['quote']);
}
if (!defined('CMS_QUOTE_SUB')) {
  define('CMS_QUOTE_SUB', $defaults['sub']);
}

(new Installer())->run();

class Installer
{
  private string $langCode = 'en';
  private array $trans = [];
  private array $messages = ['error' => ''];
  private bool $isInstalled = false;
  private string $deletedMsg = '';
  private bool $isDeleteFailed = false;
  private string $licenseText = '';
  private ?string $existingDbFile = null;
  private ?PDO $pdo = null;

  private bool $isSubDir = false;
  private string $subDirPath = '';
  private string $baseUrl = '';
  private bool $isNginx = false;
  private string $csrfToken = '';

  /**
   * Initialize installer.
   *
   * @return void
   */
  public function __construct()
  {
    $this->langCode = self::getLanguage();
    $this->loadTranslations();
    if (file_exists(__DIR__ . '/LICENSE.txt')) {
      $rawLicense = file_get_contents(__DIR__ . '/LICENSE.txt');

      // Extract common header part.
      $header = '';
      if (preg_match('/^(.*?)(?=-{10,})/s', $rawLicense, $m)) {
        $header = trim($m[1]) . "\n\n";
      }

      // Extract body according to selected language.
      if ($this->langCode === 'ja') {
        // Extract text after Japanese marker.
        if (preg_match('/【日本語】[^-]*-+(.*?)(?=\z)/s', $rawLicense, $m)) {
          $this->licenseText = $header . trim($m[1]);
        } else {
          $this->licenseText = $rawLicense;
        }
      } else {
        // Extract text after English marker.
        if (preg_match('/\[English\][^-]*-+(.*?)(?=-{10,}|$)/s', $rawLicense, $m)) {
          $this->licenseText = $header . trim($m[1]);
        } else {
          $this->licenseText = $rawLicense;
        }
      }
    } else {
      $this->licenseText = "License file (LICENSE.txt) not found.";
    }
    $dbs = glob(__DIR__ . '/data/*.db');
    if (!empty($dbs)) {
      $this->existingDbFile = basename($dbs[0]);
    }

    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $this->baseUrl = ($scriptDir === '/') ? '' : rtrim($scriptDir, '/');
    if ($this->baseUrl !== '') {
      $this->isSubDir = true;
      $this->subDirPath = $this->baseUrl . '/';
    }

    if (isset($_SERVER['SERVER_SOFTWARE']) && str_contains(strtolower($_SERVER['SERVER_SOFTWARE']), 'nginx')) {
      $this->isNginx = true;
    }

    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }
    if (empty($_SESSION['install_csrf'])) {
      $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
    }
    $this->csrfToken = $_SESSION['install_csrf'];
  }

  /**
   * Execute installation.
   *
   * @return void
   */
  public function run(): void
  {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_nginx') {
      $dataDir = __DIR__ . '/data';
      if (!is_dir($dataDir) && is_writable(__DIR__)) {
        @mkdir($dataDir, 0775, true);
      }
      if (is_writable($dataDir)) {
        file_put_contents($dataDir . '/.nginx_confirmed', date('Y-m-d H:i:s'));
      }
      $url = $_SERVER['REQUEST_URI'];
      header("Location: " . $url);
      exit;
    }

    $requirements = $this->checkServerRequirements();
    $canInstall = !in_array(false, array_column($requirements, 'check'), true);
    if ($canInstall && $_SERVER['REQUEST_METHOD'] === 'POST') {
      $this->handleInstallation();
    }
    $this->render($requirements, $canInstall);
  }

  /**
   * Detect client language.
   *
   * @return string Returns 'en' or 'ja'
   */
  public static function getLanguage(): string
  {
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ja'])) {
      return $_GET['lang'];
    }
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && str_contains($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'ja')) {
      return 'ja';
    }
    return 'en';
  }

  /**
   * Translate string.
   *
   * @param string $key
   * @return string
   */
  private function t(string $key): string
  {
    return $this->trans[$this->langCode][$key] ?? $key;
  }

  /**
   * Get string parameter safely.
   */
  private function getParam(string $key, string $default = ''): string
  {
    return isset($_POST[$key]) && is_scalar($_POST[$key]) ? (string)$_POST[$key] : $default;
  }

  /**
   * Process installation form.
   *
   * @return void
   */
  private function handleInstallation(): void
  {
    $data = [
      'site_name' => trim($this->getParam('site_name')),
      'site_lang' => $this->getParam('site_lang', 'en'),
      'timezone' => $this->getParam('timezone', 'UTC'),
      'username' => trim($this->getParam('username', 'admin')),
      'email' => trim($this->getParam('email')),
      'password' => $this->getParam('password'),
      'password_confirm' => $this->getParam('password_confirm'),
      'install_templates' => isset($_POST['install_templates']),
      'terms_agree' => isset($_POST['terms_agree']),
      'use_existing_db' => isset($_POST['use_existing_db']),
      'disable_wal' => isset($_POST['disable_wal']),
      'enable_options' => isset($_POST['enable_options']),
    ];

    if (!hash_equals($_SESSION['install_csrf'] ?? '', $this->getParam('csrf_token'))) {
      $this->messages['error'] = 'Invalid CSRF token.';
      return;
    }

    if (!$this->existingDbFile || !$data['use_existing_db']) {
      if (empty($data['site_name']) || empty($data['username']) || empty($data['email']) || empty($data['password'])) {
        $this->messages['error'] = $this->t('err_required');
        return;
      }
      if ($data['password'] !== $data['password_confirm']) {
        $this->messages['error'] = $this->t('err_pass_match');
        return;
      }
      if (strlen($data['password']) < 8 || !preg_match('/[A-Za-z]/', $data['password']) || !preg_match('/[0-9]/', $data['password'])) {
        $this->messages['error'] = $this->t('err_pass_len');
        return;
      }
      if (strlen($data['password']) > 256) {
        $this->messages['error'] = $this->t('err_pass_max_len');
        return;
      }
    }
    if (!$data['terms_agree']) {
      $this->messages['error'] = $this->t('err_terms');
      return;
    }
    try {
      if ($this->existingDbFile && $data['use_existing_db']) {
        $dbFilename = $this->existingDbFile;
        $this->createConfigFile($dbFilename, $data);
        $this->migrateDatabase($dbFilename, $data);
      } else {
        $dbHash = bin2hex(random_bytes(16));
        $dbFilename = "grinds_{$dbHash}.db";
        $this->createConfigFile($dbFilename, $data);
        $this->initDatabase($dbFilename, $data);
      }
      $this->setupHtaccess($data);
      $this->isInstalled = true;
      $this->selfDestruct();
    } catch (Exception $e) {
      if ($this->pdo && $this->pdo->inTransaction()) {
        $this->pdo->rollBack();
      }
      if (is_file(__DIR__ . '/config.php') && is_writable(__DIR__ . '/config.php')) {
        unlink(__DIR__ . '/config.php');
      }
      $this->messages['error'] = $this->t('err_db_init') . $e->getMessage();
    }
  }

  /**
   * Setup access rules.
   *
   * @param array $data
   * @return void
   */
  private function setupHtaccess(array $data = []): void
  {
    // Protect uploads directory.
    $uploadsDir = __DIR__ . '/assets/uploads';
    if (!is_dir($uploadsDir)) {
      if (is_writable(dirname($uploadsDir))) {
        mkdir($uploadsDir, 0775, true);
      }
    }
    $uploadsHtaccess = $uploadsDir . '/.htaccess';
    $upIndexes = (!empty($data['enable_options'])) ? 'Options -Indexes' : '# Options -Indexes';

    $uploadsContent = '';

    if (file_exists($uploadsHtaccess) && is_readable($uploadsHtaccess)) {
      $uploadsContent = file_get_contents($uploadsHtaccess);
    }

    if (empty($uploadsContent)) {
      $uploadsContent = "# GrindSite - Uploads Security\n" .
        "<FilesMatch \"\.(?i:php|phtml|pl|py|jsp|asp|htm|html|shtml|sh|cgi)$\">\n" .
        "    <IfModule mod_authz_core.c>\n" .
        "        Require all denied\n" .
        "    </IfModule>\n" .
        "    <IfModule !mod_authz_core.c>\n" .
        "        Order allow,deny\n" .
        "        Deny from all\n" .
        "    </IfModule>\n" .
        "</FilesMatch>";
    }

    $uploadsContent = preg_replace('/^#?\s*Options\s+-Indexes.*$/m', '', $uploadsContent);
    $uploadsContent = str_replace('# Disable directory listing', '', $uploadsContent);
    $uploadsContent = trim($uploadsContent);

    $finalContent = $uploadsContent . "\n\n# Disable directory listing\n" . $upIndexes;

    if (is_writable(dirname($uploadsHtaccess)) || is_writable($uploadsHtaccess)) {
      file_put_contents($uploadsHtaccess, $finalContent);
    }

    $target = __DIR__ . '/.htaccess';
    if (file_exists($target)) {
      return;
    }

    require_once __DIR__ . '/lib/htaccess_generator.php';
    $content = grinds_get_htaccess_content(!empty($data['enable_options']));

    if (is_writable(dirname($target)) || is_writable($target)) {
      file_put_contents($target, $content);
    }
  }

  /**
   * Create configuration file.
   *
   * @param string $dbFilename
   * @param array  $data
   * @return void
   * @throws Exception
   */
  private function createConfigFile(string $dbFilename, array $data): void
  {
    $walMode = (!empty($data['disable_wal'])) ? 'false' : 'true';
    $appKey = bin2hex(random_bytes(32));
    $dirConst = "__DIR__";

    $content = <<<PHP
<?php
/**
 * System configuration file.
 */
if (!defined('GRINDS_APP')) {
    http_response_code(403);
    exit;
}

// Define application key.
if (!defined('APP_KEY')) define('APP_KEY', '{$appKey}');

// Define database settings.
if (!defined('ENABLE_WAL_MODE')) define('ENABLE_WAL_MODE', {$walMode});

// Define database filename for external backup scripts.
if (!defined('DB_FILENAME')) define('DB_FILENAME', '{$dbFilename}');

// Define database path.
if (!defined('DB_FILE')) define('DB_FILE', {$dirConst} . '/data/' . DB_FILENAME);

// Set installation flag.
if (!defined('INSTALLED')) define('INSTALLED', true);

// Set debug mode.
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);

// Trust reverse proxy.
// if (!defined('TRUST_PROXIES')) define('TRUST_PROXIES', true);
PHP;
    if (file_put_contents(__DIR__ . '/config.php', $content) === false) {
      throw new Exception($this->t('err_write_conf'));
    }
    if (function_exists('opcache_invalidate')) {
      opcache_invalidate(__DIR__ . '/config.php', true);
    }
  }

  /**
   * Initialize database.
   *
   * @param string $dbFilename
   * @param array  $data
   * @return void
   */
  private function initDatabase(string $dbFilename, array $data): void
  {
    $disableWal = !empty($data['disable_wal']);
    $this->connectDB($dbFilename, $disableWal);
    $this->createTables();

    $sampleFile = __DIR__ . '/lib/sample_data.php';
    if (file_exists($sampleFile)) {
      require_once $sampleFile;
    }

    $this->insertInitialData($data);
    if (!empty($data['install_templates'])) {
      if (function_exists('Grinds_InstallSampleData')) {
        Grinds_InstallSampleData($this->pdo, $data['site_lang'], $data['site_name']);

        $now = date('Y-m-d H:i:s');
        $this->pdo->prepare("UPDATE posts SET published_at = ?, created_at = ?, updated_at = ?")->execute([$now, $now, $now]);
      }
    }
  }

  /**
   * Migrate existing database.
   *
   * @param string $dbFilename
   * @param array  $data
   * @return void
   */
  private function migrateDatabase(string $dbFilename, array $data): void
  {
    $disableWal = !empty($data['disable_wal']);
    $this->connectDB($dbFilename, $disableWal);
    $this->createTables();
  }

  /**
   * Connect to database.
   *
   * @param string $dbFilename
   * @param bool   $disableWal
   * @return void
   */
  private function connectDB(string $dbFilename, bool $disableWal = false): void
  {
    if (!defined('GRINDS_APP')) {
      define('GRINDS_APP', true);
    }
    // Load configuration.
    if (file_exists(__DIR__ . '/config.php')) {
      require_once __DIR__ . '/config.php';
    }
    if (!defined('DB_FILE')) {
      define('DB_FILE', __DIR__ . '/data/' . $dbFilename);
    }
    if (!defined('ROOT_PATH')) {
      define('ROOT_PATH', __DIR__);
    }

    if (!defined('ENABLE_WAL_MODE')) {
      define('ENABLE_WAL_MODE', !$disableWal);
    }
    if (!defined('GRINDS_SKIP_DB_INIT')) {
      define('GRINDS_SKIP_DB_INIT', true);
    }

    require_once __DIR__ . '/lib/db.php';

    $this->pdo = grinds_db_connect();
  }

  /**
   * Create tables.
   *
   * @return void
   */
  private function createTables(): void
  {
    if (function_exists('grinds_db_migrate')) {
      grinds_db_migrate($this->pdo);
    }
  }

  /**
   * Insert initial data.
   *
   * @param array $data
   * @return void
   */
  private function insertInitialData(array $data): void
  {
    $isJa = ($data['site_lang'] === 'ja');
    $timezone = $data['timezone'];
    date_default_timezone_set($timezone);
    $now = date('Y-m-d H:i:s');
    $hash = password_hash($data['password'], PASSWORD_DEFAULT);

    $this->pdo->prepare("INSERT INTO users (username, password, email, avatar, role, created_at) VALUES (?, ?, ?, '', 'admin', ?)")->execute([$data['username'], $hash, $data['email'], $now]);

    if (!defined('BASE_URL')) {
      require_once __DIR__ . '/lib/bootstrap_url.php';
    }
    $currentBaseUrl = BASE_URL;

    $settings = Grinds_GetDefaultSettings($data['site_lang'], [
      'site_name' => $data['site_name'],
      'system_base_url' => $currentBaseUrl,
      'timezone' => $timezone,
    ]);

    $stmt = $this->pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)");
    foreach ($settings as $key => $val) {
      if (is_array($val)) {
        $val = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
      }
      $stmt->execute([$key, $val]);
    }

    $catCount = $this->pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($catCount == 0) {
      $cat1 = ($isJa ? '未分類' : 'Uncategorized');
      $cat2 = ($isJa ? 'ニュース' : 'News');
      $cat3 = ($isJa ? 'チュートリアル' : 'Tutorials');

      $stmtCat = $this->pdo->prepare("INSERT INTO categories (name, slug, sort_order, category_theme) VALUES (?, ?, ?, 'all')");
      $stmtCat->execute([$cat1, 'uncategorized', 1]);
      $stmtCat->execute([$cat2, 'news', 2]);
      $stmtCat->execute([$cat3, 'tutorials', 3]);
    }

    if (empty($data['install_templates'])) {
      $menuCount = $this->pdo->query("SELECT COUNT(*) FROM nav_menus")->fetchColumn();
      if ($menuCount == 0) {
        $label = $isJa ? 'ホーム' : 'HOME';
        $stmtMenu = $this->pdo->prepare("INSERT INTO nav_menus (location, label, url, sort_order, target_theme) VALUES (?, ?, ?, ?, 'all')");
        $stmtMenu->execute(['header', $label, '/', 1]);
      }
    }
  }

  /**
   * Self-destruct installer.
   *
   * @return void
   */
  private function selfDestruct(): void
  {
    $file = __FILE__;
    register_shutdown_function(static function () use ($file): void {
      if (is_file($file)) {
        if (function_exists('opcache_invalidate')) {
          opcache_invalidate($file, true);
        }
        if (is_writable($file) && is_writable(dirname($file))) {
          unlink($file);
        }
      }
    });

    // Overwrite file content.
    $wiped = false;
    if (is_writable(__FILE__)) {
      $wiped = (file_put_contents(__FILE__, "<?php http_response_code(403); exit('System installed.');") !== false);
      if ($wiped && function_exists('opcache_invalidate')) {
        opcache_invalidate(__FILE__, true);
      }
    }

    $unlinked = false;
    if (is_writable(__FILE__) && is_writable(dirname(__FILE__))) {
      $unlinked = unlink(__FILE__);
    }

    if ($unlinked) {
      $this->deletedMsg = '<span class="font-bold text-green-600">✔ ' . $this->t('msg_deleted') . '</span>';
    } else {
      // Attempt fallback rename.
      $renamed = __FILE__ . '.bak';
      $renamedSuccess = false;
      if (is_writable(__FILE__) && is_writable(dirname(__FILE__))) {
        $renamedSuccess = @rename(__FILE__, $renamed);
      }

      if ($renamedSuccess) {
        // Secure renamed file.
        @chmod($renamed, 0444);
        $this->deletedMsg = '<span class="font-bold text-green-600">✔ ' . $this->t('msg_deleted') . '</span>';
      } elseif ($wiped) {
        $this->deletedMsg = '<span class="font-bold text-green-600">✔ ' . $this->t('msg_deleted') . ' (Content wiped)</span>';
      } else {
        $this->isDeleteFailed = true;
        $this->deletedMsg = '<span class="font-bold text-red-600">⚠️ ' . $this->t('msg_delete_fail') . '</span><br><span class="block mt-1 text-red-500 text-xs">Please delete <strong>install.php</strong> manually via FTP.</span>';
      }
    }
  }

  /**
   * Check server requirements.
   *
   * @return array<string, array{label: string, check: bool, msg: string}>
   */
  private function checkServerRequirements(): array
  {
    $reqs = [];

    $minPhp = class_exists('GrindsSystemCheck') ? GrindsSystemCheck::getRequiredPhpVersion() : '8.3.0';
    $reqs['php'] = [
      'label' => $this->t('req_php'),
      'check' => version_compare(PHP_VERSION, $minPhp, '>='),
      'msg' => PHP_VERSION
    ];

    $requiredExtensions = class_exists('GrindsSystemCheck') ? GrindsSystemCheck::getRequiredExtensions() : ['mbstring', 'zip', 'gd', 'pdo_sqlite', 'dom', 'libxml', 'openssl', 'json'];

    $extMap = [
      'pdo_sqlite' => ['label' => 'req_sqlite', 'msg' => 'msg_req_db'],
      'gd' => ['label' => 'req_gd', 'msg' => 'msg_req_img'],
      'mbstring' => ['label' => 'req_mbstring', 'msg' => 'msg_req_mbstring'],
      'json' => ['label' => 'req_json', 'msg' => 'msg_req_json'],
      'zip' => ['label' => 'req_zip', 'msg' => 'msg_req_zip'],
      'dom' => ['label' => 'req_dom', 'msg' => 'msg_req_dom'],
      'libxml' => ['label' => 'req_libxml', 'msg' => 'msg_req_libxml'],
      'openssl' => ['label' => 'req_openssl', 'msg' => 'msg_req_openssl'],
    ];

    foreach ($requiredExtensions as $ext) {
      $labelKey = $extMap[$ext]['label'] ?? "req_$ext";
      $msgKey = $extMap[$ext]['msg'] ?? "msg_req_$ext";

      $label = isset($extMap[$ext]) ? $this->t($extMap[$ext]['label']) : $ext;
      $msg = isset($extMap[$ext]) ? $this->t($extMap[$ext]['msg']) : "Required: $ext";

      $check = extension_loaded($ext);

      $reqs[$ext] = [
        'label' => $label,
        'check' => $check,
        'msg' => $msg
      ];
    }

    $reqs['write_root'] = ['label' => $this->t('req_root_write'), 'check' => is_writable(__DIR__), 'msg' => $this->t('msg_req_conf')];
    $reqs['write_data'] = ['label' => $this->t('req_data_write'), 'check' => is_writable(__DIR__ . '/data') || (is_writable(__DIR__) && !file_exists(__DIR__ . '/data')), 'msg' => $this->t('msg_req_perm')];
    $reqs['write_uploads'] = ['label' => $this->t('req_upload_write'), 'check' => is_writable(__DIR__ . '/assets/uploads') || (is_writable(__DIR__ . '/assets') && !file_exists(__DIR__ . '/assets/uploads')), 'msg' => $this->t('msg_req_perm')];

    return $reqs;
  }

  /**
   * Load translations.
   *
   * @return void
   */
  private function loadTranslations(): void
  {
    $quote = CMS_QUOTE_EN . '<br><span class="block opacity-60 mt-2 text-xs">' . CMS_QUOTE_SUB . '</span>';
    $quoteJa = 'Grind Your Site to Perfection.<br><span class="block opacity-60 mt-2 text-xs">Less system, more soul. Built in your palms.</span>';

    $this->trans = [
      'en' => [
        'title' => 'Installation',
        'quote_design' => $quote,
        'form_title' => 'Installation Setup',
        'form_desc' => 'Please fill in the details below to configure your new CMS.',
        'server_check' => 'Server Check',
        'req_php' => 'PHP Version >= 8.3',
        'req_sqlite' => 'SQLite Extension',
        'req_gd' => 'GD Library',
        'req_mbstring' => 'MBString Extension',
        'req_json' => 'JSON Extension',
        'req_zip' => 'Zip Extension',
        'req_dom' => 'DOM Extension',
        'req_libxml' => 'libxml Extension',
        'req_openssl' => 'OpenSSL Extension',
        'req_root_write' => 'Root Directory Writable',
        'req_data_write' => 'Data Directory Writable',
        'req_upload_write' => 'Uploads Directory Writable',
        'msg_req_db' => 'Required for database',
        'msg_req_img' => 'Required for image resizing',
        'msg_req_mbstring' => 'Required for multibyte string',
        'msg_req_json' => 'Required for data handling',
        'msg_req_zip' => 'Required for backups/updates',
        'msg_req_dom' => 'Required for SVG & SSG',
        'msg_req_libxml' => 'Required for HTML parsing',
        'msg_req_openssl' => 'Required for encryption',
        'msg_req_conf' => 'Need permissions to create config.php',
        'msg_req_perm' => 'Directory permissions',
        'install_blocked_msg' => 'Please fix the server requirements (NG items) and reload.',
        'err_required' => 'All fields are required.',
        'err_pass_match' => 'Passwords do not match.',
        'err_pass_len' => 'Password must be at least 8 characters.',
        'err_pass_max_len' => 'Password is too long (maximum 256 characters).',
        'err_db_init' => 'Database initialization failed: ',
        'err_write_conf' => 'Failed to write config.php.',
        'step_site_config' => '1. Site Configuration',
        'lbl_site_name' => 'Site Name',
        'lbl_lang' => 'Language',
        'lbl_timezone' => 'Timezone',
        'step_admin_account' => '2. Administrator Account',
        'lbl_username' => 'Username',
        'lbl_email' => 'Email Address',
        'lbl_pass' => 'Admin Password',
        'lbl_pass_conf' => 'Confirm Password',
        'ph_site_name' => 'My Awesome Site',
        'note_pass' => '* Password must be at least 8 characters.',
        'note_existing_db' => '* Site name and settings will be loaded from the existing database.',
        'btn_install' => 'Agree & Install',
        'complete_title' => 'Installation Complete!',
        'complete_msg' => 'Initial setup is complete.<br>Please log in to the admin panel.',
        'complete_hint' => 'Note: To reinstall, please delete <strong>config.php</strong> and <strong>.htaccess</strong>.',
        'link_login' => 'Log in to Admin Panel',
        'msg_deleted' => 'For security, install.php has been deleted.',
        'msg_delete_fail' => 'Failed to delete install.php. Please delete it manually.',
        'lbl_templates' => 'Install Sample Data',
        'desc_templates' => 'Includes sample articles, menus, and widgets.',
        'lbl_terms' => 'License Agreement',
        'desc_terms' => 'I agree to the Terms of Service and License Agreement.',
        'link_terms' => 'Read License',
        'req_read_terms' => 'Please read the license to enable this checkbox.',
        'err_terms' => 'You must agree to the License Agreement.',
        'lbl_existing_db' => 'Use an existing database',
        'desc_existing_db' => 'Found a database. Check to migrate data.',
        'lbl_wal_mode' => 'Database Lock Workaround (Disable WAL)',
        'desc_wal_mode' => 'Check this ONLY if "Database is locked" errors occur. Required for some shared hosting. Usually not needed for local dev/VPS.',
        'footer_secure' => 'SECURE INSTALLER',
        'lbl_options_check' => 'Enable "Options -MultiViews"',
        'desc_options_check' => 'Disabled by default for maximum compatibility. Recommended to enable (check) for local dev and VPS.',
        'btn_close' => 'Close',
        'subdir_warn_title' => 'Subdirectory detected',
        'subdir_warn_msg' => 'Installed in "<strong>%s</strong>".',
        'subdir_note_title' => 'Subdirectory Installation Note',
        'subdir_robots_note' => 'Search engines do not automatically detect robots.txt in subdirectories. Please add the following to your domain root robots.txt:',
        'nginx_warn_title' => 'Nginx Detected',
        'nginx_warn_msg' => 'Nginx does not support .htaccess. You must configure routing manually to avoid 404 errors.',
        'nginx_sample' => 'Nginx Config Sample',
        'btn_confirm_nginx' => 'I have configured Nginx (Hide Warning)',
        'js_rewrite_warn_title' => '⚠️ URL Rewrite Error Potential',
        'js_rewrite_warn_msg' => 'Due to server specifications, articles might return 404 errors.<br>Please open <code>src/.htaccess</code> and remove the <code>#</code> at the beginning of the <code># RewriteBase</code> line to uncomment it.',
      ],
      'ja' => [
        'title' => 'インストール',
        'quote_design' => $quoteJa,
        'form_title' => 'インストール設定',
        'form_desc' => '以下の項目を入力して、CMSのセットアップを行ってください。',
        'server_check' => 'サーバー要件チェック',
        'req_php' => 'PHP バージョン >= 8.3',
        'req_sqlite' => 'SQLite 拡張',
        'req_gd' => 'GD ライブラリ',
        'req_mbstring' => 'MBString 拡張 (日本語対応)',
        'req_json' => 'JSON 拡張',
        'req_zip' => 'Zip 拡張 (バックアップ用)',
        'req_dom' => 'DOM 拡張',
        'req_libxml' => 'libxml 拡張',
        'req_openssl' => 'OpenSSL 拡張',
        'req_root_write' => 'ルート書き込み権限',
        'req_data_write' => 'Dataディレクトリ書き込み権限',
        'req_upload_write' => 'Uploadsディレクトリ書き込み権限',
        'msg_req_db' => 'データベースに必須',
        'msg_req_img' => '画像処理に必須',
        'msg_req_mbstring' => '日本語処理に必須',
        'msg_req_json' => 'データ処理に必須',
        'msg_req_zip' => 'バックアップ機能に必須',
        'msg_req_dom' => 'SVG・SSG機能に必須',
        'msg_req_libxml' => 'HTML解析に必須',
        'msg_req_openssl' => '暗号化機能に必須',
        'msg_req_conf' => 'config.php作成に必要',
        'msg_req_perm' => 'ディレクトリ権限',
        'install_blocked_msg' => 'サーバー要件(NGの項目)を満たしてから再読み込みしてください。',
        'err_required' => 'すべての項目を入力してください。',
        'err_pass_match' => 'パスワードが一致しません。',
        'err_pass_len' => 'パスワードは8文字以上で設定してください。',
        'err_pass_max_len' => 'パスワードが長すぎます（最大256文字）。',
        'err_db_init' => 'データベース初期化エラー: ',
        'err_write_conf' => 'config.phpの書き込みに失敗しました。',
        'step_site_config' => '1. サイト設定',
        'lbl_site_name' => 'サイト名',
        'lbl_lang' => '言語設定',
        'lbl_timezone' => 'タイムゾーン',
        'step_admin_account' => '2. 管理者アカウント設定',
        'lbl_username' => 'ユーザー名',
        'lbl_email' => 'メールアドレス',
        'lbl_pass' => '管理者パスワード',
        'lbl_pass_conf' => 'パスワード (確認)',
        'ph_site_name' => '私の素晴らしいサイト',
        'note_pass' => '※ パスワードは8文字以上で設定してください。',
        'note_existing_db' => '※ サイト名などの設定は既存のデータベースから読み込まれます。',
        'btn_install' => '規約に同意してインストール',
        'complete_title' => 'インストール完了！',
        'complete_msg' => '初期設定が完了しました。<br>管理画面にログインしてください。',
        'complete_hint' => '※ 再インストールを行う場合は、<strong>config.php</strong> と <strong>.htaccess</strong> を削除してください。',
        'link_login' => '管理画面へログイン',
        'msg_deleted' => 'セキュリティのため install.php は削除されました。',
        'msg_delete_fail' => 'install.php の削除に失敗しました。手動で削除してください。',
        'lbl_templates' => 'サンプルデータを導入する',
        'desc_templates' => '記事、メニュー、ウィジェットのサンプルを追加します。',
        'lbl_terms' => '利用規約',
        'desc_terms' => '使用許諾契約書 (EULA) に同意します。',
        'link_terms' => '規約を読む',
        'req_read_terms' => 'チェックを有効にするには規約を最後までお読みください。',
        'err_terms' => '利用規約への同意が必要です。',
        'lbl_existing_db' => '既存のデータベースを使用',
        'desc_existing_db' => '以前のデータが見つかりました。引き継ぐ場合はチェックしてください。',
        'lbl_wal_mode' => 'データベースロック回避（WAL無効化）',
        'desc_wal_mode' => '「Database is locked」エラーが頻発する場合にのみチェックしてください。ローカル環境やVPSではチェック不要です。',
        'footer_secure' => 'SECURE INSTALLER',
        'lbl_options_check' => 'Options -MultiViews を有効化',
        'desc_options_check' => '一般的な共用サーバーではエラーの原因となるため、デフォルトは無効です。ローカル環境やVPSでは有効化（チェック）を推奨します。',
        'btn_close' => '閉じる',
        'subdir_warn_title' => 'サブディレクトリ検知',
        'subdir_warn_msg' => '「<strong>%s</strong>」にインストールされました。',
        'subdir_note_title' => 'サブディレクトリ運用の注意点',
        'subdir_robots_note' => '検索エンジンはサブディレクトリ内の robots.txt を自動検出しません。ドメインルートの robots.txt に以下を追加してください。',
        'nginx_warn_title' => 'Nginx 環境を検知',
        'nginx_warn_msg' => 'Nginx では .htaccess が機能しません。トップページ以外へのアクセス（404エラー）を防ぐため、以下の設定をサーバー設定ファイル（nginx.conf 等）に追加してください。',
        'btn_confirm_nginx' => '設定完了（警告を隠す）',
        'js_rewrite_warn_title' => '⚠️ URLリライトエラーの可能性',
        'js_rewrite_warn_msg' => 'サーバー環境の仕様により、現在記事ページが 404 エラーになる可能性があります。<br><code>src/.htaccess</code> を開き、<code># RewriteBase</code> の先頭の <code>#</code> を削除（コメントアウトを解除）して保存してください。',
      ]
    ];
  }

  /**
   * Render UI.
   *
   * @param array $requirements
   * @param bool  $canInstall
   * @return void
   */
  private function render(array $requirements, bool $canInstall): void
  {
    $this->prepareQuoteDesign();
  ?>
    <!DOCTYPE html>
    <html lang="<?= h($this->langCode) ?>" class="bg-slate-950 h-full antialiased selection:bg-primary-500/30">
    <?php $this->renderHtmlHead(); ?>

    <body
      class="relative flex justify-center items-center px-4 sm:px-6 lg:px-8 py-12 min-h-full text-slate-300 overflow-x-hidden"
      x-data="{ modalOpen: false, useExisting: <?= $this->existingDbFile ? 'true' : 'false' ?>, initialLoad: true, termsRead: false }"
      x-init="setTimeout(() => initialLoad = false, 800); $watch('modalOpen', val => { if (val) $nextTick(() => { const el = document.getElementById('terms_content'); if (el && el.scrollHeight <= el.clientHeight + 20) termsRead = true; }) })" @keydown.escape.window="modalOpen = false">
      <?php $this->renderBackgroundAnimation(); ?>

      <div class="top-4 right-4 z-50 absolute flex space-x-2" x-show="!initialLoad"
        x-transition:enter="transition ease-out duration-700 delay-300"
        x-transition:enter-start="opacity-0 translate-y-[-10px]" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
        <a href="?lang=en"
          class="glass-card backdrop-blur-md px-3 py-1 rounded-full text-sm font-bold transition-colors <?= $this->langCode === 'en' ? 'bg-primary-600/80 text-white border-primary-500/50 shadow-[0_0_15px_rgba(15,98,254,0.3)]' : 'text-slate-400 hover:text-white hover:bg-white/10' ?>">English</a>
        <a href="?lang=ja"
          class="glass-card backdrop-blur-md px-3 py-1 rounded-full text-sm font-bold transition-colors <?= $this->langCode === 'ja' ? 'bg-primary-600/80 text-white border-primary-500/50 shadow-[0_0_15px_rgba(15,98,254,0.3)]' : 'text-slate-400 hover:text-white hover:bg-white/10' ?>">日本語</a>
      </div>

      <div class="relative z-10 items-start gap-12 grid grid-cols-1 lg:grid-cols-12 w-full max-w-5xl">
        <div class="lg:top-12 lg:sticky space-y-8 lg:col-span-4">
          <?php $this->renderSidebar($requirements); ?>
        </div>

        <div class="lg:col-span-8 w-full" x-show="!initialLoad"
          x-transition:enter="transition ease-out duration-700 delay-500" x-transition:enter-start="opacity-0 translate-y-4"
          x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
          <div class="glass-panel rounded-2xl overflow-hidden relative">
            <!-- Apply glassmorphism shine. -->
            <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent opacity-50 pointer-events-none">
            </div>
            <?php $this->renderMainContent($requirements, $canInstall); ?>
          </div>
        </div>
      </div>

      <?php $this->renderTermsModal(); ?>
      <?php $this->renderScripts(); ?>
    </body>

    </html>
  <?php
  }

  private function prepareQuoteDesign(): void
  {
    if ($this->isInstalled) {
      $pQuote = 'The wheel is spinning. From your palms, to the world.';
      $pSub = 'Your masterpiece begins here.';
      $html = '<div class="py-4">' .
        '<span class="text-lg md:text-xl font-medium leading-relaxed text-white drop-shadow-sm">' . h($pQuote) . '</span>' .
        '<br>' .
        '<span class="block opacity-50 mt-5 text-[10px] font-bold tracking-[0.3em] uppercase italic">' . h($pSub) . '</span>' .
        '</div>';
      $this->trans[$this->langCode]['quote_design'] = $html;
    }
  }

  private function renderHtmlHead(): void
  {
  ?>

    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Install <?= h(CMS_NAME) ?></title>
      <script src="https://cdn.tailwindcss.com"></script>
      <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
      <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
      <script>
        tailwind.config = {
          theme: {
            extend: {
              fontFamily: {
                sans: ['Inter', 'Noto Sans JP', 'sans-serif']
              },
              colors: {
                primary: {
                  50: '#eff6ff',
                  100: '#dbeafe',
                  200: '#bfdbfe',
                  300: '#93c5fd',
                  400: '#60a5fa',
                  500: '#3b82f6',
                  600: '#0f62fe',
                  700: '#0353e9',
                  800: '#1e40af',
                  900: '#1e3a8a'
                }
              }
            }
          }
        }
      </script>
      <style>
        ::-webkit-scrollbar {
          width: 8px;
        }

        ::-webkit-scrollbar-track {
          background: transparent;
        }

        ::-webkit-scrollbar-thumb {
          background: rgba(255, 255, 255, 0.2);
          border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
          background: rgba(255, 255, 255, 0.3);
        }

        [x-cloak] {
          display: none !important;
        }

        @keyframes blob {
          0% {
            transform: translate(0px, 0px) scale(1);
          }

          33% {
            transform: translate(30px, -50px) scale(1.1);
          }

          66% {
            transform: translate(-20px, 20px) scale(0.9);
          }

          100% {
            transform: translate(0px, 0px) scale(1);
          }
        }

        .animate-blob {
          animation: blob 7s infinite;
        }

        .animation-delay-2000 {
          animation-delay: 2s;
        }

        .animation-delay-4000 {
          animation-delay: 4s;
        }

        .glass-panel {
          background: rgba(30, 41, 59, 0.4);
          backdrop-filter: blur(20px);
          -webkit-backdrop-filter: blur(20px);
          border: 1px solid rgba(255, 255, 255, 0.08);
          box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .glass-card {
          background: rgba(255, 255, 255, 0.03);
          border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .glass-input {
          background: rgba(15, 23, 42, 0.6);
          border: 1px solid rgba(255, 255, 255, 0.1);
          color: #fff;
          transition: all 0.3s ease;
        }

        .glass-input:focus {
          background: rgba(15, 23, 42, 0.8);
          border-color: #3b82f6;
          box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
          outline: none;
        }

        .glass-input::placeholder {
          color: rgba(255, 255, 255, 0.3);
        }
      </style>
    </head>
  <?php
  }

  private function renderBackgroundAnimation(): void
  {
  ?>
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none" x-show="!initialLoad" x-transition.opacity.duration.1000ms x-cloak>
      <div class="absolute top-[-10%] left-[-10%] w-[40vw] h-[40vw] max-w-[600px] max-h-[600px] bg-primary-600/20 rounded-full mix-blend-screen filter blur-[100px] opacity-70 animate-blob"></div>
      <div class="absolute top-[20%] right-[-10%] w-[35vw] h-[35vw] max-w-[500px] max-h-[500px] bg-purple-600/20 rounded-full mix-blend-screen filter blur-[100px] opacity-70 animate-blob animation-delay-2000"></div>
      <div class="absolute bottom-[-10%] left-[20%] w-[40vw] h-[40vw] max-w-[600px] max-h-[600px] bg-blue-600/20 rounded-full mix-blend-screen filter blur-[100px] opacity-70 animate-blob animation-delay-4000"></div>
    </div>
  <?php
  }

  private function renderSidebar(array $requirements): void
  {
  ?>
    <div x-show="!initialLoad" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
      <div class="flex items-center gap-3 mb-6">
        <div class="flex justify-center items-center bg-gradient-to-br from-primary-500 to-primary-700 shadow-[0_0_20px_rgba(15,98,254,0.4)] rounded-xl w-12 h-12 font-bold text-white text-xl">DC</div>
        <h1 class="font-bold text-white text-3xl tracking-tight drop-shadow-md"><?= h(CMS_NAME) ?></h1>
      </div>
      <p class="text-slate-400 text-base leading-relaxed drop-shadow-sm"><?= $this->t('quote_design') ?></p>
    </div>

    <div x-show="initialLoad" x-transition:leave="transition ease-in duration-700" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-110 blur-xl" class="fixed inset-0 flex flex-col justify-center items-center z-[100] pointer-events-none bg-slate-950">
      <div class="w-16 h-16 mb-6 rounded-full border-t-2 border-r-2 border-primary-500 animate-spin shadow-[0_0_15px_rgba(59,130,246,0.5)]"></div>
      <div class="font-medium text-white text-sm tracking-[0.4em] uppercase font-mono animate-pulse drop-shadow-[0_0_15px_rgba(59,130,246,0.6)] text-center">SECURE<br><span class="text-primary-400">INSTALLER</span></div>
    </div>

    <?php if (!$this->isInstalled): ?>
      <div x-show="!initialLoad" x-transition:enter="transition ease-out duration-700 delay-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="glass-panel p-6 rounded-2xl" x-cloak>
        <h3 class="mb-5 pb-2 border-white/10 border-b font-bold text-slate-400 text-xs uppercase tracking-wider"><?= h($this->t('server_check')) ?></h3>
        <ul class="space-y-3">
          <?php foreach ($requirements as $req): ?>
            <li class="flex justify-between items-start text-sm">
              <span class="font-medium text-slate-300"><?= htmlspecialchars($req['label']) ?></span>
              <div class="flex items-center pt-0.5">
                <?php if ($req['check']): ?>
                  <svg class="w-5 h-5 text-emerald-400 drop-shadow-[0_0_8px_rgba(52,211,153,0.5)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                  </svg>
                <?php else: ?>
                  <span class="inline-flex items-center bg-red-500/20 px-2.5 py-1 rounded-md border border-red-500/30 font-medium text-red-400 text-xs shadow-[0_0_10px_rgba(239,68,68,0.2)]"><?= htmlspecialchars($req['msg']) ?></span>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  <?php
  }

  private function renderMainContent(array $requirements, bool $canInstall): void
  {
    if ($this->isInstalled) {
      $this->renderSuccessState();
    } else {
      $this->renderInstallForm($canInstall);
    }
  }

  private function renderSuccessState(): void
  {
  ?>
    <div class="relative z-10 flex flex-col justify-center items-center px-8 py-20 text-center">
      <div class="flex justify-center items-center bg-emerald-500/10 mb-6 rounded-full border border-emerald-500/20 shadow-[0_0_30px_rgba(16,185,129,0.3)] w-20 h-20 animate-[pulse_3s_ease-in-out_infinite]">
        <svg class="w-10 h-10 text-emerald-400 drop-shadow-[0_0_8px_rgba(52,211,153,0.8)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
      </div>
      <h2 class="mb-4 font-bold text-white text-4xl tracking-tight drop-shadow-md"><?= h($this->t('complete_title')) ?></h2>
      <p class="mb-8 max-w-md text-slate-300 text-lg leading-relaxed"><?= $this->t('complete_msg') ?></p>
      <div class="glass-card mb-8 px-5 py-4 border border-white/5 rounded-xl text-slate-400 text-sm"><?= $this->t('complete_hint') ?></div>

      <?php if ($this->isSubDir): ?>
        <div class="group relative overflow-hidden bg-gradient-to-br from-cyan-500/10 to-blue-500/10 hover:from-cyan-500/20 hover:to-blue-500/20 mb-8 p-5 border border-cyan-500/20 rounded-xl w-full max-w-lg text-left backdrop-blur-md transition-all duration-300">
          <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-gradient-to-br from-cyan-500/20 to-blue-500/20 rounded-full blur-2xl"></div>
          <div class="relative flex items-start">
            <div class="flex-shrink-0 mr-4">
              <div class="flex justify-center items-center w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-500/20 to-blue-500/20 border border-cyan-500/30 text-cyan-300 shadow-[0_0_15px_rgba(6,182,212,0.15)]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
              </div>
            </div>
            <div>
              <h3 class="mb-1 font-bold text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-400 text-sm tracking-wide uppercase text-[11px] pt-0.5"><?= h($this->t('subdir_warn_title')) ?></h3>
              <p class="text-cyan-200/70 text-sm leading-relaxed mb-3"><?= sprintf($this->t('subdir_warn_msg'), htmlspecialchars($this->subDirPath)) ?></p>

              <div class="mt-2 pt-3 border-t border-cyan-500/20">
                <p class="flex items-center gap-1 text-cyan-200/90 text-xs font-bold mb-1.5">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <?= h($this->t('subdir_note_title')) ?>
                </p>
                <p class="text-cyan-200/70 text-xs leading-relaxed mb-2"><?= h($this->t('subdir_robots_note')) ?></p>
                <?php
                $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
                $scheme = $isHttps ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $sitemapUrl = $scheme . $host . rtrim($this->baseUrl, '/') . '/sitemap.xml';
                ?>
                <code class="block bg-slate-900/80 mt-2 p-2 border border-cyan-500/30 rounded-lg font-mono text-[11px] text-cyan-300 select-all shadow-inner">Sitemap: <?= h($sitemapUrl) ?></code>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($this->deletedMsg): ?>
        <div class="flex items-start <?= $this->isDeleteFailed ? 'bg-red-500/10 border-red-500/20' : 'bg-emerald-500/10 border-emerald-500/20' ?> mb-8 p-5 border rounded-xl w-full max-w-lg text-left backdrop-blur-md">
          <svg class="flex-shrink-0 mt-0.5 mr-3 w-5 h-5 <?= $this->isDeleteFailed ? 'text-red-400' : 'text-emerald-400' ?> drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
          </svg>
          <span class="<?= $this->isDeleteFailed ? 'text-red-300' : 'text-emerald-300' ?> text-sm leading-relaxed"><?= $this->deletedMsg ?></span>
        </div>
      <?php endif; ?>

      <div id="rewrite-warning-container" class="w-full max-w-lg mb-8" style="display: none;"></div>

      <a href="<?= h($this->baseUrl . '/admin/login.php?installed=1') ?>" class="mt-4 inline-flex justify-center items-center bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-500 hover:to-primary-400 shadow-[0_0_30px_rgba(15,98,254,0.4)] hover:shadow-[0_0_40px_rgba(15,98,254,0.6)] px-10 py-4 rounded-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500 focus-visible:outline-offset-2 font-bold text-white text-base transition-all duration-300 transform hover:-translate-y-1">
        <?= h($this->t('link_login')) ?>
        <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
        </svg>
      </a>

      <script>
        document.addEventListener('DOMContentLoaded', () => {
          fetch('./robots.txt', {
              method: 'HEAD',
              cache: 'no-store'
            })
            .then(response => {
              if (response.status === 404 || response.status === 500) {
                const container = document.getElementById('rewrite-warning-container');
                if (container) {
                  container.innerHTML = `
                    <div class="flex items-start bg-amber-500/10 p-5 border border-amber-500/20 rounded-xl backdrop-blur-md text-left">
                      <svg class="flex-shrink-0 mt-0.5 mr-3 w-5 h-5 text-amber-500 drop-shadow-[0_0_5px_rgba(245,158,11,0.5)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                      </svg>
                      <div>
                        <h3 class="font-bold text-amber-400 text-sm mb-1 drop-shadow-sm"><?= h($this->t('js_rewrite_warn_title')) ?></h3>
                        <p class="text-amber-200/80 text-sm leading-relaxed"><?= $this->t('js_rewrite_warn_msg') ?></p>
                      </div>
                    </div>
                  `;
                  container.style.display = 'block';
                }
              }
            })
            .catch(e => console.error('Health check failed', e));
        });
      </script>
    </div>
  <?php
  }

  private function renderInstallForm(bool $canInstall): void
  {
    $error = $this->messages['error'];
  ?>
    <div class="sm:p-12 px-8 py-10 relative z-10">
      <?php if ($this->isNginx && !file_exists(__DIR__ . '/data/.nginx_confirmed')): ?>
        <?php $this->renderNginxWarning(); ?>
      <?php endif; ?>

      <div class="mb-10 text-center">
        <h2 class="font-bold text-white text-3xl tracking-tight drop-shadow-md"><?= h($this->t('form_title')) ?></h2>
        <p class="mt-3 text-slate-400 text-base"><?= h($this->t('form_desc')) ?></p>
      </div>

      <?php if ($error): ?>
        <div class="flex items-start bg-red-500/10 mb-8 p-5 border border-red-500/20 rounded-xl backdrop-blur-md">
          <svg class="flex-shrink-0 mt-0.5 mr-3 w-5 h-5 text-red-500 drop-shadow-[0_0_5px_rgba(239,68,68,0.5)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <div class="font-medium text-red-300 text-sm leading-relaxed"><?= h($error) ?></div>
        </div>
      <?php endif; ?>

      <?php if (!$canInstall): ?>
        <div class="bg-amber-500/10 mb-8 p-5 border border-amber-500/20 rounded-xl backdrop-blur-md">
          <div class="flex items-start">
            <svg class="flex-shrink-0 mt-0.5 mr-3 w-5 h-5 text-amber-500 drop-shadow-[0_0_5px_rgba(245,158,11,0.5)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="text-amber-300 text-sm leading-relaxed"><?= h($this->t('install_blocked_msg')) ?></p>
          </div>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-8 relative z-10 p-2">
        <input type="hidden" name="csrf_token" value="<?= h($this->csrfToken) ?>">
        <!-- Step 1 -->
        <div class="glass-panel p-6 rounded-2xl">
          <h3 class="flex items-center mb-6 font-semibold text-white text-base drop-shadow-md">
            <span class="flex justify-center items-center bg-primary-600/20 mr-3 border border-primary-500/30 shadow-[0_0_10px_rgba(15,98,254,0.2)] rounded-full w-8 h-8 text-primary-400 text-sm">1</span>
            <?= h($this->t('step_site_config')) ?>
          </h3>

          <?php if ($this->existingDbFile): ?>
            <div class="flex items-start gap-4 bg-blue-500/10 mb-6 p-5 border border-blue-500/20 rounded-xl backdrop-blur-md transition-all hover:bg-blue-500/20">
              <div class="flex items-center h-6 mt-1">
                <input id="use_existing_db" name="use_existing_db" type="checkbox" value="1" x-model="useExisting" class="bg-slate-900/50 border-white/20 rounded focus:ring-primary-500 focus:ring-offset-slate-900 w-5 h-5 text-primary-500 cursor-pointer form-checkbox transition">
              </div>
              <div class="text-sm leading-6 flex-1">
                <label for="use_existing_db" class="font-bold text-white cursor-pointer drop-shadow-sm"><?= h($this->t('lbl_existing_db')) ?></label>
                <p class="mt-1 text-slate-400">
                  <?= h($this->t('desc_existing_db')) ?>
                  <span class="inline-block bg-slate-900/80 ml-2 px-2 py-0.5 border border-white/10 rounded font-mono text-xs text-primary-300 shadow-inner"><?= h($this->existingDbFile) ?></span>
                </p>
              </div>
            </div>
          <?php endif; ?>

          <div class="gap-6 grid grid-cols-1" x-show="!useExisting" x-cloak>
            <div>
              <label class="block mb-2 font-medium text-slate-300 text-sm drop-shadow-sm"><?= h($this->t('lbl_site_name')) ?></label>
              <input type="text" name="site_name" class="glass-input block w-full px-4 py-3 rounded-xl sm:text-sm sm:leading-6" placeholder="<?= h($this->t('ph_site_name')) ?>" :required="!useExisting" <?= !$canInstall ? 'disabled' : '' ?>>
            </div>

            <div>
              <label class="block mb-2 font-medium text-slate-300 text-sm drop-shadow-sm"><?= h($this->t('lbl_lang')) ?></label>
              <select name="site_lang" class="glass-input block w-full px-4 py-3 rounded-xl sm:text-sm sm:leading-6 cursor-pointer appearance-none bg-no-repeat bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2224%22%20height%3D%2224%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20stroke%3D%22%2394a3b8%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22M6%209l6%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[position:right_1rem_center] pr-10" <?= !$canInstall ? 'disabled' : '' ?>>
                <option value="ja" <?= $this->langCode === 'ja' ? 'selected' : '' ?> class="bg-slate-800 text-white">Japanese (日本語)</option>
                <option value="en" <?= $this->langCode === 'en' ? 'selected' : '' ?> class="bg-slate-800 text-white">English</option>
              </select>
            </div>

            <div>
              <label class="block mb-2 font-medium text-slate-300 text-sm drop-shadow-sm"><?= h($this->t('lbl_timezone')) ?></label>
              <select name="timezone" class="glass-input block w-full px-4 py-3 rounded-xl sm:text-sm sm:leading-6 cursor-pointer appearance-none bg-no-repeat bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2224%22%20height%3D%2224%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20stroke%3D%22%2394a3b8%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpath%20d%3D%22M6%209l6%206%206-6%22%2F%3E%3C%2Fsvg%3E')] bg-[position:right_1rem_center] pr-10" <?= !$canInstall ? 'disabled' : '' ?>>
                <?php
                $timezones = DateTimeZone::listIdentifiers();
                $defaultTz = ($this->langCode === 'ja') ? 'Asia/Tokyo' : 'UTC';
                foreach ($timezones as $tz):
                  $selected = ($tz === $defaultTz) ? 'selected' : '';
                ?>
                  <option value="<?= h($tz) ?>" <?= $selected ?> class="bg-slate-800 text-white"><?= h($tz) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="space-y-3 mt-2">
              <div class="relative flex items-start bg-slate-800/40 p-4 border border-white/5 hover:border-white/10 rounded-xl transition-colors">
                <div class="flex items-center h-6 mt-0.5">
                  <input id="disable_wal" name="disable_wal" type="checkbox" value="1" class="bg-slate-900/50 border-white/20 rounded focus:ring-primary-500 focus:ring-offset-slate-900 w-5 h-5 text-primary-500 cursor-pointer form-checkbox transition">
                </div>
                <div class="ml-3 text-sm leading-6">
                  <label for="disable_wal" class="font-medium text-white cursor-pointer drop-shadow-sm"><?= h($this->t('lbl_wal_mode')) ?></label>
                  <p class="text-slate-400 text-xs mt-1 leading-relaxed"><?= h($this->t('desc_wal_mode')) ?></p>
                </div>
              </div>

              <div class="relative flex items-start bg-slate-800/40 p-4 border border-white/5 hover:border-white/10 rounded-xl transition-colors">
                <div class="flex items-center h-6 mt-0.5">
                  <input id="enable_options" name="enable_options" type="checkbox" value="1" class="bg-slate-900/50 border-white/20 rounded focus:ring-primary-500 focus:ring-offset-slate-900 w-5 h-5 text-primary-500 cursor-pointer form-checkbox transition">
                </div>
                <div class="ml-3 text-sm leading-6">
                  <label for="enable_options" class="font-medium text-white cursor-pointer drop-shadow-sm"><?= h($this->t('lbl_options_check')) ?></label>
                  <p class="text-slate-400 text-xs mt-1 leading-relaxed"><?= h($this->t('desc_options_check')) ?></p>
                </div>
              </div>
            </div>
          </div>
          <div x-show="useExisting" x-cloak class="mt-4 text-emerald-400 border border-emerald-500/20 bg-emerald-500/10 p-3 rounded-lg text-sm drop-shadow-sm">
            <svg class="w-4 h-4 inline-block -mt-0.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <?= h($this->t('note_existing_db')) ?>
          </div>
        </div>
        <div x-show="!useExisting" class="glass-panel p-6 rounded-2xl relative mt-8" x-cloak>
          <h3 class="flex items-center mb-6 font-semibold text-white text-base drop-shadow-md">
            <span class="flex justify-center items-center bg-primary-600/20 mr-3 border border-primary-500/30 shadow-[0_0_10px_rgba(15,98,254,0.2)] rounded-full w-8 h-8 text-primary-400 text-sm">2</span>
            <?= h($this->t('step_admin_account')) ?>
          </h3>

          <div class="gap-6 grid grid-cols-1 md:grid-cols-2">
            <div class="col-span-1 md:col-span-2">
              <label class="block mb-2 font-medium text-slate-300 text-sm drop-shadow-sm"><?= h($this->t('lbl_username')) ?></label>
              <input type="text" name="username" class="glass-input block w-full px-4 py-3 rounded-xl sm:text-sm sm:leading-6" placeholder="admin" :required="!useExisting" <?= !$canInstall ? 'disabled' : '' ?>>
            </div>

            <div class="col-span-1 md:col-span-2">
              <label class="block mb-2 font-medium text-slate-300 text-sm drop-shadow-sm"><?= h($this->t('lbl_email')) ?></label>
              <input type="email" name="email" class="glass-input block w-full px-4 py-3 rounded-xl sm:text-sm sm:leading-6" placeholder="admin@example.com" :required="!useExisting" <?= !$canInstall ? 'disabled' : '' ?>>
            </div>

            <div x-data="{ show: false }">
              <label class="block mb-2 font-medium text-slate-300 text-sm drop-shadow-sm"><?= h($this->t('lbl_pass')) ?></label>
              <div class="relative">
                <input :type="show ? 'text' : 'password'" name="password" class="glass-input block w-full px-4 py-3 pr-10 rounded-xl sm:text-sm sm:leading-6" placeholder="••••••••" :required="!useExisting" <?= !$canInstall ? 'disabled' : '' ?>>
                <button type="button" @click="show = !show" class="right-0 absolute inset-y-0 flex items-center px-3 focus:outline-none text-slate-400 hover:text-primary-400 transition-colors" tabindex="-1">
                  <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                  </svg>
                </button>
              </div>
            </div>

            <div x-data="{ show: false }">
              <label class="block mb-2 font-medium text-slate-300 text-sm drop-shadow-sm"><?= h($this->t('lbl_pass_conf')) ?></label>
              <div class="relative">
                <input :type="show ? 'text' : 'password'" name="password_confirm" class="glass-input block w-full px-4 py-3 pr-10 rounded-xl sm:text-sm sm:leading-6" placeholder="••••••••" :required="!useExisting" <?= !$canInstall ? 'disabled' : '' ?>>
                <button type="button" @click="show = !show" class="right-0 absolute inset-y-0 flex items-center px-3 focus:outline-none text-slate-400 hover:text-primary-400 transition-colors" tabindex="-1">
                  <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <svg x-show="show" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
          <p class="mt-4 text-emerald-400/80 text-xs text-right"><?= h($this->t('note_pass')) ?></p>
        </div>

        <div class="glass-panel p-6 rounded-2xl mt-8">
          <div class="relative flex items-start bg-slate-800/40 mb-6 p-5 border border-white/5 hover:border-white/10 rounded-xl transition-colors" x-show="!useExisting" x-cloak>
            <div class="flex items-center h-6 mt-0.5">
              <input id="install_templates" name="install_templates" type="checkbox" value="1" checked class="bg-slate-900/50 border-white/20 rounded focus:ring-primary-500 focus:ring-offset-slate-900 w-5 h-5 text-primary-500 cursor-pointer form-checkbox transition">
            </div>
            <div class="ml-3 text-sm leading-6">
              <label for="install_templates" class="font-medium text-white cursor-pointer drop-shadow-sm"><?= h($this->t('lbl_templates')) ?></label>
              <p class="text-slate-400 mt-1 leading-relaxed"><?= h($this->t('desc_templates')) ?></p>
            </div>
          </div>

          <div class="relative flex items-start bg-slate-800/40 p-5 border border-white/5 hover:border-white/10 rounded-xl transition-colors mb-8" :class="{ 'opacity-70': !termsRead }">
            <div class="flex items-center h-6 mt-0.5">
              <input id="terms_agree" name="terms_agree" type="checkbox" value="1" required :disabled="!termsRead" class="bg-slate-900/50 border-white/20 rounded focus:ring-primary-500 focus:ring-offset-slate-900 w-5 h-5 text-primary-500 cursor-pointer form-checkbox transition disabled:opacity-50 disabled:cursor-not-allowed">
            </div>
            <div class="ml-3 text-sm leading-6">
              <label for="terms_agree" class="font-medium text-white cursor-pointer drop-shadow-sm" :class="{ 'cursor-not-allowed': !termsRead }"><?= h($this->t('lbl_terms')) ?></label>
              <div class="text-slate-400 mt-1 leading-relaxed">
                <div class="flex flex-wrap items-center gap-x-1.5">
                  <span><?= h($this->t('desc_terms')) ?></span>
                  <button type="button" @click="modalOpen = true" class="font-bold text-primary-400 hover:text-primary-300 hover:underline transition-colors drop-shadow-sm" :class="{ 'animate-pulse text-amber-400 hover:text-amber-300': !termsRead }"><?= h($this->t('link_terms')) ?></button>
                </div>
                <div x-show="!termsRead" class="text-amber-400/80 text-xs mt-1 font-bold"><?= h($this->t('req_read_terms')) ?></div>
              </div>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row justify-between items-center pt-2 gap-4">
            <span class="font-mono text-slate-500 text-sm">v<?= h(CMS_VERSION) ?></span>
            <button type="submit" class="w-full sm:w-auto bg-gradient-to-r from-primary-600 to-primary-500 hover:from-primary-500 hover:to-primary-400 disabled:opacity-50 shadow-[0_0_20px_rgba(15,98,254,0.3)] hover:shadow-[0_0_30px_rgba(15,98,254,0.5)] px-10 py-4 rounded-xl font-bold text-white text-base transition-all duration-300 transform hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:transform-none select-none" <?= !$canInstall ? 'disabled' : '' ?>><?= h($this->t('btn_install')) ?></button>
          </div>
        </div>
      </form>
    </div>
  <?php
  }

  private function renderNginxWarning(): void
  {
    $ngLoc = $this->isSubDir ? $this->subDirPath : '/';
    $ngIndex = $this->isSubDir ? $this->subDirPath . 'index.php' : '/index.php';
    $ngBlock = $this->isSubDir ? '^' . rtrim($this->subDirPath, '/') . '/' : '^/';
  ?>
    <div class="bg-blue-500/10 mb-8 p-5 border border-blue-500/20 rounded-xl backdrop-blur-md">
      <h3 class="flex items-center mb-2 font-bold text-blue-400 text-sm drop-shadow-sm">
        <svg class="mr-2 w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
        </svg>
        <?= h($this->t('nginx_warn_title')) ?>
      </h3>
      <p class="mb-4 text-blue-200/80 text-sm leading-relaxed"><?= h($this->t('nginx_warn_msg')) ?></p>
      <div class="bg-slate-900/80 p-4 border border-white/5 rounded-lg shadow-inner overflow-x-auto">
        <div class="flex justify-between items-center mb-2">
          <span class="font-mono text-slate-500 text-xs"><?= h($this->t('nginx_sample')) ?></span>
        </div>
        <pre class="font-mono text-emerald-400/90 text-[13px] leading-relaxed">location <?= $ngLoc ?> {
    try_files $uri $uri/ <?= $ngIndex ?>$is_args$args;
}

<span class="text-slate-500"># Block sensitive files</span>
location ~ (?:^|/)(\.git|\.env|\.vscode|composer\.json|package\.json|README\.md|LICENSE(\.txt)?|config\.php|nginx\.conf\.sample|\.htpasswd|\.nginx_confirmed) {
    deny all; return 404;
}

<span class="text-slate-500"># Block system directories</span>
location ^~ <?= $ngLoc ?>data/ {
    deny all; return 404;
}
location ~ <?= $ngBlock ?>(lib|admin/config|admin/skins|admin/views|theme/.+/parts)/ {
    deny all; return 404;
}

<span class="text-slate-500"># Block direct access to PHP files</span>
location ~ <?= $ngBlock ?>theme/.*\.php$ {
    deny all; return 404;
}
location ~ <?= $ngBlock ?>plugins/.*\.php$ {
    deny all; return 404;
}</pre>
      </div>
      <div class="mt-4 pt-4 border-t border-blue-400/30">
        <form method="POST">
          <input type="hidden" name="action" value="confirm_nginx">
          <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors shadow-lg"><?= h($this->t('btn_confirm_nginx')) ?></button>
        </form>
      </div>
    </div>
  <?php
  }

  private function renderTermsModal(): void
  {
  ?>
    <div x-show="modalOpen" class="z-[100] fixed inset-0 flex justify-center items-center p-4 sm:p-6" x-cloak>
      <div x-show="modalOpen" x-transition.opacity.duration.300ms class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="modalOpen = false"></div>
      <div x-show="modalOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 translate-y-8 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 translate-y-4 scale-95" class="z-10 relative flex flex-col bg-slate-900/90 border border-white/10 shadow-[0_0_50px_rgba(0,0,0,0.5)] rounded-2xl w-full max-w-2xl max-h-[85vh] overflow-hidden backdrop-blur-xl">
        <div class="flex justify-between items-center p-5 border-b border-white/5 bg-slate-800/50">
          <h3 class="font-bold text-white text-lg drop-shadow-sm flex items-center">
            <svg class="w-5 h-5 mr-2 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <?= h($this->t('lbl_terms')) ?>
          </h3>
          <button @click="modalOpen = false" class="text-slate-400 hover:text-white bg-white/5 hover:bg-white/10 w-8 h-8 rounded-full flex items-center justify-center transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <div id="terms_content" @scroll="if ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 20) termsRead = true" class="flex-1 bg-slate-950/50 p-6 overflow-y-auto font-mono text-slate-300 text-sm leading-relaxed custom-scrollbar">
          <pre class="whitespace-pre-wrap font-mono text-[13px] text-slate-400"><?= h($this->licenseText) ?></pre>
        </div>
        <div class="bg-slate-800/50 p-5 border-t border-white/5 text-right flex justify-end">
          <button @click="modalOpen = false" class="bg-white/10 hover:bg-white/20 border border-white/10 px-8 py-2.5 rounded-xl font-bold text-white text-sm transition-all duration-200"><?= h($this->t('btn_close')) ?></button>
        </div>
      </div>
    </div>
  <?php
  }

  private function renderScripts(): void
  {
  ?>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const inputs = document.querySelectorAll('form input:not([type="password"]), form select');
        inputs.forEach(el => {
          if (!el.name) return;
          const key = 'grind_install_' + el.name;
          const saved = sessionStorage.getItem(key);
          if (saved !== null) {
            if (el.type === 'checkbox' || el.type === 'radio') {
              el.checked = (saved === 'true');
              el.dispatchEvent(new Event('change', {
                bubbles: true
              }));
            } else {
              el.value = saved;
              el.dispatchEvent(new Event('input', {
                bubbles: true
              }));
            }
          }
          const saveState = () => {
            if (el.type === 'checkbox' || el.type === 'radio') {
              sessionStorage.setItem(key, el.checked);
            } else {
              sessionStorage.setItem(key, el.value);
            }
          };
          el.addEventListener('change', saveState);
          el.addEventListener('input', saveState);
        });
        const form = document.querySelector('form');
        if (form) {
          form.addEventListener('submit', () => {
            sessionStorage.clear();
          });
        }
      });
    </script>
<?php
  }
}
?>
