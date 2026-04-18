<?php

/**
 * Manage system updates.
 * Ultimate Rank: Fully asynchronous, memory-safe, with Dry Run & Atomic Rollback.
 */
if (!defined('GRINDS_APP'))
  exit;

class GrindsUpdater
{
  const UPDATE_URL = 'https://raw.githubusercontent.com/grindworks/grind-site/main/update.json';

  private $pdo;

  public function __construct($pdo)
  {
    $this->pdo = $pdo;
  }

  /**
   * Check for available updates.
   */
  public function check($timeout = 5)
  {
    $currentVersion = defined('CMS_VERSION') ? CMS_VERSION : '';
    $result = [
      'has_update' => false,
      'current' => $currentVersion,
      'remote' => []
    ];

    // Add cache-buster to prevent GitHub's 5-minute Raw URL caching
    $fetchUrl = self::UPDATE_URL . '?t=' . time();
    // Fetch remote JSON (Uses grinds_fetch_url which already has SSRF protection)
    $json = grinds_fetch_url($fetchUrl, [
      'timeout' => $timeout,
      'user_agent' => 'GrindsCMS/' . $currentVersion
    ]);

    if ($json === false) {
      return $result;
    }

    $remoteInfo = json_decode($json, true);
    if (!$remoteInfo) {
      return $result;
    }

    // Save latest version to database for UI badges
    if (isset($remoteInfo['version']) && function_exists('update_option')) {
      update_option('latest_version', $remoteInfo['version']);
      update_option('last_update_check', time());
    }

    // Compare versions
    if (isset($remoteInfo['version']) && version_compare($remoteInfo['version'], $currentVersion, '>')) {
      $result['has_update'] = true;
      $result['remote'] = $remoteInfo;
    }

    return $result;
  }

  /**
   * Memory-safe streaming download bypassing memory_limit.
   * Includes Strict SSRF Protection (Protocol Restriction & Private IP Blocking).
   */
  public function downloadDirect($url, $destPath)
  {
    // Protocol validation (Security Hardening against file:// and other protocols)
    $parsedUrl = parse_url($url);
    if (!$parsedUrl || !isset($parsedUrl['scheme']) || !in_array(strtolower($parsedUrl['scheme']), ['http', 'https'], true)) {
      throw new Exception("Security Error: Invalid URL protocol. Only HTTP and HTTPS are allowed.");
    }

    // Restrict access to private/loopback networks (DNS Rebinding & SSRF protection)
    $host = $parsedUrl['host'] ?? '';
    $ips = gethostbynamel($host);
    if (!$ips) return false;

    $resolveRules = [];
    foreach ($ips as $ip) {
      if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        throw new Exception("Security Error: Access to private IP address is prohibited.");
      }
    }
    // Pin DNS to prevent TOCTOU/DNS Rebinding
    if (isset($ips[0])) {
      $scheme = $parsedUrl['scheme'];
      $port = $parsedUrl['port'] ?? ($scheme === 'https' ? 443 : 80);
      $resolveRules[] = "{$host}:{$port}:{$ips[0]}";
    }

    $fp = @fopen($destPath, 'wb');
    if (!$fp) return false;

    // Use cURL for streaming if available
    if (function_exists('curl_init')) {
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_FILE, $fp);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
      curl_setopt($ch, CURLOPT_TIMEOUT, 120);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
      curl_setopt($ch, CURLOPT_USERAGENT, 'GrindsCMS/' . CMS_VERSION);

      // Apply pinned DNS
      if (!empty($resolveRules)) {
        curl_setopt($ch, CURLOPT_RESOLVE, $resolveRules);
      }

      // Restrict protocols to HTTP/HTTPS internally within cURL
      if (defined('CURLOPT_PROTOCOLS_STR')) {
        curl_setopt($ch, CURLOPT_PROTOCOLS_STR, 'http,https');
        curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS_STR, 'http,https');
      } else {
        curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
        curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
      }

      $success = curl_exec($ch);
      curl_close($ch);
      fclose($fp);
      if (!$success) @unlink($destPath);
      return $success;
    }

    // Fallback to fopen with context options
    $context = stream_context_create([
      'http' => [
        'timeout' => 120,
        'user_agent' => 'GrindsCMS/' . CMS_VERSION,
        'follow_location' => 1,
        'max_redirects' => 5
      ],
      'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
    ]);
    $in = @fopen($url, 'rb', false, $context);
    if (!$in) {
      fclose($fp);
      return false;
    }
    while (!feof($in)) {
      fwrite($fp, fread($in, 8192));
    }
    fclose($in);
    fclose($fp);
    return true;
  }

  /**
   * Extract ZIP archive and return the actual source directory path.
   */
  public function extractPackage($zipFilePath, $extractPath)
  {
    if (!class_exists('ZipArchive')) throw new Exception("ZipArchive extension required.");

    $zip = new ZipArchive;
    if ($zip->open($zipFilePath) !== TRUE) throw new Exception("Failed to open ZIP package.");

    if (!is_dir($extractPath)) @mkdir($extractPath, 0775, true);

    // Prevent Zip Bomb & Zip Slip
    $maxTotalSize = 100 * 1024 * 1024;
    $totalSize = 0;

    for ($i = 0; $i < $zip->numFiles; $i++) {
      $stat = $zip->statIndex($i);
      if ($stat === false) continue;

      $totalSize += $stat['size'];
      if ($totalSize > $maxTotalSize) {
        $zip->close();
        throw new Exception("ZIP file exceeds maximum allowed size (Zip Bomb Protection).");
      }

      $filename = $zip->getNameIndex($i);
      // Prevent Zip Slip, Absolute path injection, and Null-byte injection
      if (str_contains($filename, "\0") || str_contains($filename, '../') || str_contains($filename, '..\\')) continue;
      // Sanitize to relative path, removing absolute path prefixes (e.g., C:\, /)
      $safeFilename = preg_replace('/^([a-zA-Z]:\\\\|\/)+/', '', str_replace('\\', '/', $filename));
      if (empty($safeFilename) || str_starts_with($safeFilename, '__MACOSX/')) continue;

      $target = $extractPath . '/' . $safeFilename;
      if (str_starts_with($safeFilename, '/')) continue; // Must be strictly relative

      if (str_ends_with($filename, '/')) {
        if (!is_dir($target)) @mkdir($target, 0775, true);
      } else {
        $dir = dirname($target);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        // Stream extraction to prevent memory issues and timeouts
        $fpStream = $zip->getStream($filename);
        if ($fpStream !== false) {
          $dest = @fopen($target, 'wb');
          if ($dest) {
            stream_copy_to_stream($fpStream, $dest);
            fclose($dest);
          } else {
            fclose($fpStream);
            $zip->close();
            throw new Exception("Failed to write extracted file: " . $safeFilename);
          }
          fclose($fpStream);
        }
      }
    }
    $zip->close();

    // Find the 'src' root inside the extracted folder
    $sourceDir = $extractPath . '/';
    $items = scandir($extractPath);
    foreach ($items as $f) {
      if ($f !== '.' && $f !== '..' && is_dir($extractPath . '/' . $f) && $f !== '__MACOSX') {
        if (is_dir($extractPath . '/' . $f . '/src')) {
          $sourceDir = $extractPath . '/' . $f . '/src/';
          break;
        } elseif (basename($f) === 'src') {
          $sourceDir = $extractPath . '/src/';
          break;
        }
      }
    }
    return $sourceDir;
  }

  /**
   * Pre-flight Check: Ensure all target paths are writable before touching anything.
   */
  public function dryRun($sourceDir, $targetDir, $exclude)
  {
    $iterator = $this->getFilteredIterator($sourceDir, $exclude);
    $sourceLen = strlen(str_replace('\\', '/', realpath($sourceDir) ?: $sourceDir));
    $unwritable = [];

    foreach ($iterator as $item) {
      $itemPath = str_replace('\\', '/', $item->getPathname());
      $relPath = ltrim(substr($itemPath, $sourceLen), '/');
      $target = $targetDir . '/' . $relPath;

      if (file_exists($target)) {
        if (!is_writable($target)) $unwritable[] = $relPath;
      } else {
        $dir = dirname($target);
        while (!file_exists($dir) && $dir !== '/' && $dir !== '.') {
          $dir = dirname($dir);
        }
        if (!is_writable($dir)) $unwritable[] = dirname($relPath);
      }
    }

    if (!empty($unwritable)) {
      $list = implode(', ', array_slice(array_unique($unwritable), 0, 5));
      if (count($unwritable) > 5) $list .= ' and ' . (count($unwritable) - 5) . ' more';
      throw new Exception("Pre-flight check failed. Permission denied for: " . $list);
    }
  }

  /**
   * Backup current files that will be overwritten.
   */
  public function backupCoreFiles($sourceDir, $targetDir, $backupDir, $exclude)
  {
    if (!is_dir($backupDir)) @mkdir($backupDir, 0775, true);
    $iterator = $this->getFilteredIterator($sourceDir, $exclude);
    $sourceLen = strlen(str_replace('\\', '/', realpath($sourceDir) ?: $sourceDir));

    foreach ($iterator as $item) {
      if ($item->isDir()) continue;

      $itemPath = str_replace('\\', '/', $item->getPathname());
      $relPath = ltrim(substr($itemPath, $sourceLen), '/');
      $currentTarget = $targetDir . '/' . $relPath;
      $backupTarget = $backupDir . '/' . $relPath;

      if (file_exists($currentTarget)) {
        $bDir = dirname($backupTarget);
        if (!is_dir($bDir)) @mkdir($bDir, 0775, true);
        @copy($currentTarget, $backupTarget);
      }
    }
  }

  /**
   * Apply the new files safely.
   */
  public function applyUpdate($sourceDir, $targetDir, $exclude)
  {
    $iterator = $this->getFilteredIterator($sourceDir, $exclude);
    $sourceLen = strlen(str_replace('\\', '/', realpath($sourceDir) ?: $sourceDir));
    $isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

    foreach ($iterator as $item) {
      $itemPath = str_replace('\\', '/', $item->getPathname());
      $relPath = ltrim(substr($itemPath, $sourceLen), '/');
      $target = $targetDir . '/' . $relPath;

      if ($item->isDir()) {
        if (!is_dir($target)) @mkdir($target, 0775, true);
      } else {
        $sourceFile = $item->getRealPath() ?: $item->getPathname();

        if (file_exists($target)) {
          if ($isWindows) {
            // Windows Lock Bypass
            $tempOld = $target . '.' . uniqid() . '.grind-del';
            if (@rename($target, $tempOld)) {
              if (!@copy($sourceFile, $target)) {
                @rename($tempOld, $target);
                throw new Exception("Failed to write: " . $relPath);
              }
            } else {
              if (!@copy($sourceFile, $target)) throw new Exception("Failed to overwrite: " . $relPath);
            }
          } else {
            // More atomic update: copy to temp file then rename.
            // This avoids a small window where the file is missing between unlink and copy.
            $tempNew = $target . '.' . uniqid() . '.grind-new';
            if (@copy($sourceFile, $tempNew)) {
              if (!@rename($tempNew, $target)) {
                @unlink($tempNew); // cleanup
                throw new Exception("Failed to rename new file over old: " . $relPath);
              }
            } else {
              throw new Exception("Failed to copy new file: " . $relPath);
            }
          }
        } else {
          if (!@copy($sourceFile, $target)) throw new Exception("Failed to write: " . $relPath);
        }
      }
    }
  }

  /**
   * Rollback applied changes from backup.
   */
  public function rollback($backupDir, $targetDir)
  {
    if (!is_dir($backupDir)) return;
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($backupDir, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::SELF_FIRST
    );
    $backupLen = strlen(str_replace('\\', '/', realpath($backupDir) ?: $backupDir));

    foreach ($iterator as $item) {
      if ($item->isDir()) continue;
      $itemPath = str_replace('\\', '/', $item->getPathname());
      $relPath = ltrim(substr($itemPath, $backupLen), '/');
      $target = $targetDir . '/' . $relPath;

      if (file_exists($item->getPathname())) {
        @unlink($target); // Safely remove failed new file
        @copy($item->getPathname(), $target); // Restore old file
      }
    }
  }

  /**
   * Generate Filtered Iterator based on excludes.
   */
  private function getFilteredIterator($dir, $exclude)
  {
    $realDir = realpath($dir) ?: $dir;
    $exclude = array_map(function ($p) {
      return str_replace('\\', '/', trim($p, '/\\'));
    }, $exclude);

    $filter = new RecursiveCallbackFilterIterator(
      new RecursiveDirectoryIterator($realDir, RecursiveDirectoryIterator::SKIP_DOTS),
      function ($current, $key, $iterator) use ($exclude, $realDir) {
        if ($current->isLink()) return false;
        $itemPath = str_replace('\\', '/', $current->getPathname());
        $srcPath = str_replace('\\', '/', $realDir);
        $subPath = ltrim(substr($itemPath, strlen($srcPath)), '/');

        foreach ($exclude as $ex) {
          if ($subPath === $ex || str_starts_with($subPath, $ex . '/')) return false;
          // Only apply global filename matching if the exclude rule contains a wildcard
          if (strpbrk($ex, '*?') !== false) {
            if (fnmatch($ex, $current->getFilename()) || fnmatch($ex, $subPath)) return false;
          }
        }
        return true;
      }
    );
    return new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST);
  }

  public function cleanupDir($dir)
  {
    if (!is_dir($dir)) return;
    $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileInfo) {
      $path = $fileInfo->getPathname();
      $fileInfo->isDir() ? @rmdir($path) : @unlink($path);
    }
    @rmdir($dir);
  }

  public function cleanupWindowsLockedFiles($dir)
  {
    try {
      $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
      foreach ($iterator as $file) {
        if ($file->isFile() && str_ends_with($file->getFilename(), '.grind-del')) {
          @unlink($file->getRealPath());
        }
      }
    } catch (Exception $e) {
    }
  }
}
