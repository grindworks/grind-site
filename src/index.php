<?php

declare(strict_types=1);

// Enforce PHP version requirement.
if (version_compare(PHP_VERSION, '8.3.0', '<')) {
  http_response_code(500);
  header('Content-Type: text/html; charset=utf-8');
  die(sprintf(
    '<div style="font-family:sans-serif;padding:2em;text-align:center;color:#d32f2f;"><h1>PHP Version Error</h1><p>GrindSite requires <strong>PHP 8.3</strong> or higher.</p><p>Your current PHP version is: <strong>%s</strong></p></div>',
    htmlspecialchars(PHP_VERSION)
  ));
}

(new FrontController())->run();

/**
 * Front Controller.
 * Handle public requests, routing, caching, and preview modes.
 */
class FrontController
{
  private const CACHE_DIR = __DIR__ . '/data/cache/pages/';
  private const CACHE_TTL = 3600;

  private bool $isAdmin = false;
  private bool $isMaintenance = false;
  private bool $isPreviewMode = false;
  private ?array $previewData = null;

  private string $requestUri;
  private string $normalizedUri;
  private string $cacheFile;

  public function run(): void
  {
    $this->bootstrap();
    $this->initSession();
    $this->analyzeRequest();
    $this->handlePreviewMode();
    $this->setSecurityHeaders();

    if ($this->tryServeCache()) {
      return;
    }

    $this->render();
  }

  private function bootstrap(): void
  {
    if (!defined('GRINDS_APP')) {
      define('GRINDS_APP', true);
    }

    // Check boot restrictions.
    if (file_exists(__DIR__ . '/lib/boot_check.php')) {
      require_once __DIR__ . '/lib/boot_check.php';
    }

    // Load configuration.
    require_once __DIR__ . '/config.php';

    if (!defined('DB_FILE')) {
      $this->fatalError('System Error: Configuration file is invalid or corrupt. Please restore config.php.');
    }

    if (!file_exists(__DIR__ . '/lib/front.php')) {
      $this->fatalError('System Error: Core library (lib/front.php) is missing.');
    }

    require_once __DIR__ . '/lib/front.php';
  }

  private function initSession(): void
  {
    if (function_exists('_safe_session_start')) {
      _safe_session_start();
    }

    // Ensure CSRF token generation.
    if (function_exists('generate_csrf_token')) {
      generate_csrf_token();
    }

    // Initialize system hooks and settings.
    if (!defined('GRINDS_SYSTEM_INIT_DONE')) {
      init_system();
      do_action('grinds_init');
      define('GRINDS_SYSTEM_INIT_DONE', true);
    }

    $this->isAdmin = !empty($_SESSION['admin_logged_in']);
    $this->isMaintenance = file_exists(__DIR__ . '/.maintenance');
    $this->requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
  }

  private function analyzeRequest(): void
  {
    // Normalize path.
    $path = Routing::getRelativePath($this->requestUri);
    $path = rtrim($path, '/') ?: '/';

    // Analyze query params.
    $allowedParams = ['page', 'url', 'preview', 'q', 'sort', 'order'];
    $analysis = Routing::analyzeParams($allowedParams);
    $query = $analysis['query'];

    // Build normalized URI for caching.
    $canonicalQueryString = http_build_query($query);
    $this->normalizedUri = $path . ($query ? '?' . $canonicalQueryString : '');

    // Determine cache file path.
    $rawHost = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');

    // Generate a safe prefix from the path name.
    $safePath = preg_replace('/[^a-zA-Z0-9_-]/', '_', trim($path, '/'));
    if ($safePath === '') {
      $safePath = 'home';
    }

    $basePath = Routing::getBasePath();
    $cacheKey = $safePath . '_' . md5($rawHost . $basePath . $this->normalizedUri);
    $this->cacheFile = self::CACHE_DIR . $cacheKey . '.html';
  }

  private function handlePreviewMode(): void
  {
    $previewToken = (string)($_GET['preview'] ?? '');
    if ($previewToken === '') {
      return;
    }

    $isSecure = function_exists('get_option') ? (bool)get_option('secure_preview_mode') : false;

    if (preg_match('/^[a-f0-9]{32}$/', $previewToken)) {
      $this->validatePreviewToken($previewToken, $isSecure);
    }

    // Enforce access control for preview mode.
    if (!$this->isAdmin) {
      $sharedPassword = function_exists('get_option') ? (string)get_option('preview_shared_password', '') : '';

      if ($this->isPreviewMode) {
        if ($sharedPassword !== '') {
          $this->handlePreviewPasswordAuth($sharedPassword);
        } elseif ($isSecure) {
          $redirectParam = urlencode($this->requestUri);
          header("Location: " . resolve_url("admin/login.php?redirect_to=" . $redirectParam));
          exit;
        }
      } elseif ($isSecure) {
        $redirectParam = urlencode($this->requestUri);
        header("Location: " . resolve_url("admin/login.php?redirect_to=" . $redirectParam));
        exit;
      }
    }

    // Extend preview session.
    if ($this->isPreviewMode && $this->isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grinds_preview_extend'])) {
      $this->extendPreviewSession($previewToken);
    }
  }

  private function validatePreviewToken(string $token, bool $isSecure): void
  {
    $previewFile = ROOT_PATH . '/data/tmp/preview/preview_' . $token . '.json';

    if (!file_exists($previewFile)) {
      return;
    }

    $maxSize = defined('MAX_PREVIEW_SIZE') ? (int)MAX_PREVIEW_SIZE : 10 * 1024 * 1024;
    if (filesize($previewFile) > $maxSize) {
      return;
    }

    $json = file_get_contents($previewFile);
    $data = json_decode($json ?: '', true);

    if (!is_array($data)) {
      return;
    }

    $expires = $data['__expires_at'] ?? 0;

    // Validate token expiration.
    if ($this->isAdmin || $expires > time()) {
      $this->isPreviewMode = true;
      $this->previewData = $data;
      header("X-Robots-Tag: noindex, nofollow");
    }
  }

  private function handlePreviewPasswordAuth(string $sharedPassword): void
  {
    if (session_status() === PHP_SESSION_NONE && function_exists('_safe_session_start')) {
      _safe_session_start();
    }

    $currentAuthHash = hash('sha256', $sharedPassword . 'grinds_preview');

    // Skip if already authenticated.
    if (!empty($_SESSION['grinds_preview_auth']) && $_SESSION['grinds_preview_auth'] === $currentAuthHash) {
      return;
    }

    $error = '';

    // Check lockout state.
    if (isset($_SESSION['preview_lockout_time']) && time() < (int)$_SESSION['preview_lockout_time']) {
      $lockoutTime = (int)$_SESSION['preview_lockout_time'];
      $wait = (int)ceil(($lockoutTime - time()) / 60);
      $error = "Too many attempts. Please try again in " . (string)$wait . " minute(s).";
      $this->renderPreviewPasswordForm($error);
      return;
    } elseif (isset($_SESSION['preview_lockout_time'])) {
      // Release lockout.
      unset($_SESSION['preview_lockout_time']);
      unset($_SESSION['preview_attempts']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview_pass'])) {
      if (!function_exists('validate_csrf_token') || !validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = function_exists('_t') ? _t('err_invalid_csrf_token') : 'Invalid CSRF token';
      } else {
        // Verify password.
        $inputHash = hash('sha256', $_POST['preview_pass']);
        $correctHash = hash('sha256', $sharedPassword);

        if (hash_equals($correctHash, $inputHash)) {
          // Regenerate session ID.
          session_regenerate_id(true);

          $_SESSION['grinds_preview_auth'] = $currentAuthHash;
          unset($_SESSION['preview_attempts']);

          // Redirect to prevent POST resubmission.
          header("Location: " . $this->requestUri);
          exit;
        } else {
          // Record failed attempt.
          $attempts = ($_SESSION['preview_attempts'] ?? 0) + 1;
          $_SESSION['preview_attempts'] = $attempts;

          // Lock out after 5 failures.
          if ($attempts >= 5) {
            $_SESSION['preview_lockout_time'] = time() + 900;
            $error = "Too many failed attempts. Locked for 15 minutes.";
          } else {
            $error = function_exists('_t') ? _t('err_preview_password') : 'Incorrect password.';
          }
        }
      }
    }

    $this->renderPreviewPasswordForm($error);
  }

  private function renderPreviewPasswordForm(string $error): void
  {
    $lang = function_exists('grinds_detect_language') ? grinds_detect_language() : 'en';
    $title = function_exists('_t') ? _t('preview_auth_title') : 'Preview Authentication';
    $desc = function_exists('_t') ? _t('preview_auth_desc') : 'Please enter the password to view this preview.';
    $ph = function_exists('_t') ? _t('ph_preview_password') : 'Password';
    $btn = function_exists('_t') ? _t('btn_preview_login') : 'View';
    $csrf = function_exists('generate_csrf_token') ? generate_csrf_token() : '';

    // Set 401 status to prevent indexing.
    http_response_code(401);

    $viewFile = __DIR__ . '/preview_auth.php';
    if (file_exists($viewFile)) {
      require $viewFile;
    } else {
      die('System Error: Preview authentication view is missing.');
    }
    exit;
  }

  private function extendPreviewSession(string $token): void
  {
    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if (!validate_csrf_token($csrfToken)) {
      die('Invalid CSRF token.');
    }

    $duration = (int)$_POST['grinds_preview_extend'];
    $previewFile = ROOT_PATH . '/data/tmp/preview/preview_' . $token . '.json';

    if (file_exists($previewFile)) {
      $maxSize = defined('MAX_PREVIEW_SIZE') ? (int)MAX_PREVIEW_SIZE : 10 * 1024 * 1024;
      if (filesize($previewFile) <= $maxSize) {
        $json = file_get_contents($previewFile);
        $pData = json_decode($json ?: '', true);
        if (is_array($pData)) {
          $currentExpires = (int)($pData['__expires_at'] ?? 0);
          if ($currentExpires < time()) {
            $currentExpires = time();
          }
          $pData['__expires_at'] = $currentExpires + $duration;
          file_put_contents($previewFile, json_encode($pData));
        }
      }
    }

    header("Location: " . $this->requestUri);
    exit;
  }

  private function setSecurityHeaders(): void
  {
    // Rely on global server configuration for security headers.
  }

  private function tryServeCache(): bool
  {
    if (!$this->isCacheable()) {
      return false;
    }

    if (!file_exists($this->cacheFile)) {
      return false;
    }

    $mtime = filemtime($this->cacheFile);
    if ((time() - $mtime) >= self::CACHE_TTL) {
      return false;
    }

    // Check for scheduled posts.
    if (!$this->validateCacheAgainstSchedule($mtime)) {
      return false;
    }

    $this->serveCacheFile($this->cacheFile, $mtime);
    return true;
  }

  private function isCacheable(): bool
  {
    $logicalPath = Routing::getRelativePath($this->requestUri);
    // Exclude contact page to prevent CSRF issues.
    $isContactPage = (trim($logicalPath, '/') === 'contact');

    return $_SERVER['REQUEST_METHOD'] === 'GET'
      && !$this->isAdmin
      && !$this->isMaintenance
      && !$this->isPreviewMode
      && !$isContactPage
      && !str_starts_with($logicalPath, '/admin/')
      && $logicalPath !== '/admin'
      && !isset($_GET['q']);
  }

  private function validateCacheAgainstSchedule(int $cacheMtime): bool
  {
    $pdo = App::db();
    if (!$pdo) {
      return true;
    }

    try {
      $stmt = $pdo->prepare("SELECT 1 FROM posts WHERE status = 'published' AND published_at > ? AND published_at <= ? LIMIT 1");
      $stmt->execute([
        date('Y-m-d H:i:s', $cacheMtime),
        date('Y-m-d H:i:s')
      ]);
      return !$stmt->fetchColumn();
    } catch (\Throwable $e) {
      return true;
    }
  }

  private function serveCacheFile(string $file, int $mtime): void
  {
    $size = (int)filesize($file);
    $etag = '"' . md5((string)$mtime . '-' . (string)$size) . '"';

    header_remove('Pragma');
    header_remove('Expires');
    header("Cache-Control: public, max-age=0, must-revalidate");
    header("ETag: {$etag}");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s", $mtime) . " GMT");

    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
      header("HTTP/1.1 304 Not Modified");
      exit;
    }

    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
      $modifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
      if ($modifiedSince !== false && $modifiedSince >= $mtime) {
        header("HTTP/1.1 304 Not Modified");
        exit;
      }
    }

    header('X-Grinds-Cache: HIT');
    readfile($file);
    exit;
  }

  private function render(): void
  {
    // Release session lock.
    if (session_status() === PHP_SESSION_ACTIVE) {
      $_SESSION['last_activity'] = time();
      session_write_close();
    }

    $lockFile = $this->cacheFile . '.lock';
    $lockAcquired = false;
    $fp = null;

    if ($this->isCacheable()) {
      $dir = dirname($this->cacheFile);
      if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
      }

      $fp = @fopen($lockFile, 'c');
      if ($fp && flock($fp, LOCK_EX | LOCK_NB)) {
        $lockAcquired = true;
      } else {
        // Serve existing cache if lock is not acquired.
        if (file_exists($this->cacheFile)) {
          if ($fp) {
            fclose($fp);
            $fp = null;
          }
          $this->serveCacheFile($this->cacheFile, filemtime($this->cacheFile));
          return;
        }

        if ($fp) {
          flock($fp, LOCK_SH);
          flock($fp, LOCK_UN);
          fclose($fp);
          $fp = null;
          if (file_exists($this->cacheFile)) {
            $this->serveCacheFile($this->cacheFile, filemtime($this->cacheFile));
            return;
          }
        }
      }
    }

    $requestUrl = Routing::getResolvedPath();
    $html = grinds_render_page($requestUrl, [
      'preview_data' => $this->previewData
    ]);

    echo $html;

    if ($lockAcquired) {
      $this->cacheResponse($html);
      flock($fp, LOCK_UN);
      fclose($fp);
      @unlink($lockFile);
    } elseif ($fp) {
      fclose($fp);
    }
  }

  private function cacheResponse(string $html): void
  {
    if (http_response_code() !== 200 || !$this->isCacheable()) {
      return;
    }

    // Skip cache for pages with CSRF tokens.
    if (str_contains($html, 'name="csrf_token"')) {
      return;
    }

    $dir = dirname($this->cacheFile);
    if (!is_dir($dir)) {
      if (!mkdir($dir, 0775, true) && !is_dir($dir)) {
        return;
      }
    }

    $tempFile = tempnam($dir, 'tmp_cache_');
    if ($tempFile === false) {
      return;
    }

    if (file_put_contents($tempFile, $html) !== false) {
      @chmod($tempFile, 0664);
      if (!@rename($tempFile, $this->cacheFile)) {
        if (file_exists($this->cacheFile)) {
          @unlink($this->cacheFile);
        }
        if (!@rename($tempFile, $this->cacheFile)) {
          @copy($tempFile, $this->cacheFile);
        }
        if (function_exists('grinds_force_unlink')) {
          grinds_force_unlink($tempFile);
        } else {
          @unlink($tempFile);
        }
      }
    } else {
      @unlink($tempFile);
    }
  }

  private function fatalError(string $msg): void
  {
    http_response_code(500);
    die($msg);
  }
}
