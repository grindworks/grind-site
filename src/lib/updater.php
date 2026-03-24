<?php

/**
 * Manage system updates.
 */
if (!defined('GRINDS_APP'))
  exit;

class GrindsUpdater
{
  // Remote update URL
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
    // Fetch remote JSON
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

  public function update($zipFilePath)
  {

    $remoteInfo = $this->check();
    if (function_exists('set_time_limit')) {
      @set_time_limit(0);
    }

    if (!class_exists('ZipArchive')) {
      return false;
    }

    $zipUrl = $remoteInfo['remote']['download_url'];
    $tmpZip = ROOT_PATH . '/data/tmp/update.zip';
    if (!is_dir(dirname($tmpZip)))
      @mkdir(dirname($tmpZip), 0755, true);

    $zipContent = grinds_fetch_url($zipUrl, ['timeout' => 30, 'max_size' => 20 * 1024 * 1024]);
    if ($zipContent === false) return false;
    if (!file_put_contents($zipFilePath, $zipContent)) return false;

    // Validate Cryptographic Hash (Prevent Supply Chain Attacks)
    if (!empty($remoteInfo['remote']['sha256'])) {
      $expectedHash = strtolower(trim($remoteInfo['remote']['sha256']));
      $actualHash = hash_file('sha256', $zipFilePath);

      if (!hash_equals($expectedHash, $actualHash)) {
        grinds_force_unlink($zipFilePath);
        error_log("Updater Error: Update package hash mismatch. Expected {$expectedHash}, got {$actualHash}. Update aborted for security.");
        return false;
      }
    }

    $zip = new ZipArchive;
    if ($zip->open($zipFilePath) === TRUE) {

      // Prevent Zip Bomb (Compression Bomb)
      $maxTotalSize = 100 * 1024 * 1024; // 100MB Hard Limit
      $totalSize = 0;
      for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        if ($stat !== false) {
          $totalSize += $stat['size'];
          if ($totalSize > $maxTotalSize) {
            $zip->close();
            grinds_force_unlink($zipFilePath);
            error_log("Updater Error: Update package exceeds maximum allowed uncompressed size (Zip Bomb Protection).");
            return false;
          }
        }
      }

      // Define extraction path
      $extractPath = ROOT_PATH . '/data/tmp/update_extract/';
      // Clean up temporary directory
      grinds_delete_tree($extractPath);
      if (!is_dir($extractPath))
        @mkdir($extractPath, 0775, true);

      // Extract archive securely (Prevent Zip Slip)
      for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);

        // Block directory traversal attempts (ZIP Slip mitigation)
        if (str_contains($filename, '../') || str_contains($filename, '..\\')) {
          continue;
        }

        // Disable absolute paths (remove leading slashes to ensure extraction within target directory)
        $safeFilename = ltrim(str_replace('\\', '/', $filename), '/');
        if (empty($safeFilename)) {
          continue;
        }

        // Skip macOS metadata files
        if (str_starts_with($safeFilename, '__MACOSX/') || $safeFilename === '__MACOSX') {
          continue;
        }

        $targetPath = $extractPath . $safeFilename;

        if (str_ends_with($filename, '/')) {
          if (file_exists($targetPath) && !is_dir($targetPath)) {
            grinds_force_unlink($targetPath);
          }
          if (!is_dir($targetPath))
            @mkdir($targetPath, 0775, true);
        } else {
          $dir = dirname($targetPath);
          if (file_exists($dir) && !is_dir($dir)) {
            grinds_force_unlink($dir);
          }
          if (!is_dir($dir))
            @mkdir($dir, 0775, true);
          $zip->extractTo($extractPath, $filename);
        }
      }
      $zip->close();

      // Locate root directory
      $files = scandir($extractPath);
      $sourceDir = $extractPath;
      foreach ($files as $f) {
        if ($f !== '.' && $f !== '..' && is_dir($extractPath . $f) && $f !== '__MACOSX') {
          // Check if this directory contains 'src' to confirm it's the package root
          if (is_dir($extractPath . $f . '/src')) {
            $sourceDir = $extractPath . $f . '/';
            break;
          }
        }
      }

      // Use 'src' directory as root
      if (is_dir($sourceDir . 'src')) {
        $sourceDir = $sourceDir . 'src/';
      }

      // Copy files recursively
      $exclude = [
        'config.php',
        'data',
        'assets/uploads',
        '.git',
        '.github',
        '.gitignore',
        '.DS_Store',
        'LICENSE.txt',
        'update.json',
        'README.md'
      ];
      $copySuccess = true;
      try {
        grinds_recursive_copy($sourceDir, ROOT_PATH, $exclude);
      } catch (Exception $e) {
        $copySuccess = false;
        error_log("Updater copy failed: " . $e->getMessage());
      }

      // Clean up
      grinds_delete_tree($extractPath);
      grinds_force_unlink($zipFilePath);

      return $copySuccess;
    }
    return false;
  }
}
