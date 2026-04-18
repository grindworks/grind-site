<?php

declare(strict_types=1);

/**
 * Generate XML sitemap for search engines.
 */
ini_set('display_errors', '0');
error_reporting(0);

// Load bootstrap.
if (!require __DIR__ . '/lib/bootstrap_public.php') {
  http_response_code(404);
  exit;
}

if (!class_exists('SitemapGenerator')) {
  class SitemapGenerator
  {
    use GeneratorCacheTrait;

    private const SITEMAP_LIMIT = 50000;
    private const CACHE_TTL = 3600;
    private const XML_NS = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    private ?PDO $pdo;
    private string $baseUrl;
    private string $cacheFile;
    private bool $isSsgMode;

    public function __construct(?PDO $pdo, string $baseUrl, bool $isSsgMode = false)
    {
      $this->pdo = $pdo;
      $this->isSsgMode = $isSsgMode;

      // Ensure absolute URL.
      if (empty($baseUrl) || !preg_match('/^https?:\/\//i', $baseUrl)) {
        $scheme = (function_exists('is_ssl') && is_ssl()) ? 'https://' : 'http://';
        $host = preg_replace('/[^a-zA-Z0-9.:_-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
        $fallback = defined('BASE_URL') ? BASE_URL : '';
        if (empty($fallback) || !preg_match('/^https?:\/\//i', $fallback)) {
          $fallback = $scheme . $host . rtrim($fallback, '/');
        }
        $baseUrl = rtrim($fallback, '/');
      }
      $this->baseUrl = rtrim($baseUrl, '/');

      $cacheDir = __DIR__ . '/data/cache/pages';
      if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
      }
      $this->cacheFile = $cacheDir . '/sitemap.xml';
    }

    public function run(): void
    {
      if (!$this->pdo) {
        $this->sendError(500);
        return;
      }

      if ($this->shouldNoIndex()) {
        $this->sendError(404);
        return;
      }

      if ($this->serveFromCache()) {
        return;
      }

      $this->generateAndCache();
    }

    private function shouldNoIndex(): bool
    {
      return function_exists('get_option') && (bool)get_option('site_noindex');
    }

    public function generateToFile(string $filePath): void
    {
      if ($this->shouldNoIndex()) {
        return;
      }

      $writer = new XMLWriter();
      $writer->openUri($filePath);
      $writer->startDocument('1.0', 'UTF-8');
      $writer->setIndent(true);

      $writer->startElement('urlset');
      $writer->writeAttribute('xmlns', self::XML_NS);
      $writer->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');

      // Add home URL.
      $this->renderUrl($writer, $this->baseUrl . '/', time(), '1.0', 'daily');

      // Add post URLs.
      $this->generatePostUrls($writer);

      // Add category and tag URLs.
      $this->generateCategoryUrls($writer);
      $this->generateTagUrls($writer);

      $writer->endElement();
      $writer->endDocument();
      $writer->flush();
    }

    public function generateAsString(): string
    {
      $writer = new XMLWriter();
      $writer->openMemory();
      $writer->startDocument('1.0', 'UTF-8');
      $writer->setIndent(true);

      $writer->startElement('urlset');
      $writer->writeAttribute('xmlns', self::XML_NS);
      $writer->writeAttribute('xmlns:image', 'http://www.google.com/schemas/sitemap-image/1.1');

      // Add home URL.
      $this->renderUrl($writer, $this->baseUrl . '/', time(), '1.0', 'daily');

      // Add post URLs.
      $this->generatePostUrls($writer);

      // Add category and tag URLs.
      $this->generateCategoryUrls($writer);
      $this->generateTagUrls($writer);

      $writer->endElement();
      $writer->endDocument();

      return $writer->outputMemory(true);
    }

    private function generateAndCache(): void
    {
      $content = $this->generateAsString();

      if (!$this->isSsgMode) {
        $dir = dirname($this->cacheFile);
        $tempFile = tempnam($dir, 'sitemap_');
        if ($tempFile !== false) {
          if (file_put_contents($tempFile, $content) !== false) {
            chmod($tempFile, 0644);
            if (!rename($tempFile, $this->cacheFile)) {
              grinds_force_unlink($tempFile);
            }
          } else {
            grinds_force_unlink($tempFile);
          }
        }
      }

      if (!$this->isSsgMode) {
        while (ob_get_level()) {
          ob_end_clean();
        }
      }
      $this->sendHeaders();
      echo $content;
    }

    private function generatePostUrls(XMLWriter $writer): void
    {
      $now = date('Y-m-d H:i:s');
      if (!class_exists('PostRepository')) {
        require_once __DIR__ . '/lib/functions/posts.php';
      }
      $repo = new PostRepository($this->pdo);
      $stmt = $repo->findForSitemap(self::SITEMAP_LIMIT);

      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $slug = (string)$row['slug'];
        $url = $this->generateUrl($slug);

        $updated = !empty($row['updated_at']) ? $row['updated_at'] : ($row['published_at'] ?? $now);
        $lastMod = strtotime($updated) ?: time();

        // Calculate priority heuristics.
        $priority = ($row['type'] === 'page') ? '0.8' : '0.6';
        $changefreq = 'monthly';

        // Boost priority for recent updates.
        if (time() - $lastMod < 7 * 86400) {
          $priority = '0.9';
          $changefreq = 'daily';
        }

        $images = [];
        if (!empty($row['thumbnail'])) {
          $images[] = $row['thumbnail'];
        }

        // Extract images from block editor content.
        if (!empty($row['content'])) {
          $contentData = json_decode($row['content'], true);
          if (is_array($contentData) && !empty($contentData['blocks'])) {
            $visibleBlocks = [];
            foreach ($contentData['blocks'] as $block) {
              if (($block['type'] ?? '') === 'password_protect') {
                break;
              }
              $visibleBlocks[] = $block;
            }

            $extracted = BlockRenderer::extractImages($visibleBlocks);
            $images = array_merge($images, $extracted);
          }
        }

        $images = array_unique($images);
        $this->renderUrl($writer, $url, $lastMod, $priority, $changefreq, array_slice($images, 0, 50));
      }
    }

    private function generateCategoryUrls(XMLWriter $writer): void
    {
      if (!class_exists('PostRepository')) {
        require_once __DIR__ . '/lib/functions/posts.php';
      }
      $repo = new PostRepository($this->pdo);
      $stmt = $repo->findCategoriesForSitemap();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $url = $this->generateUrl('category/' . ltrim($row['slug'], '/'));
        $lastMod = strtotime($row['last_updated']) ?: time();
        $this->renderUrl($writer, $url, $lastMod, '0.5', 'weekly');
      }
    }

    private function generateTagUrls(XMLWriter $writer): void
    {
      if (!class_exists('PostRepository')) {
        require_once __DIR__ . '/lib/functions/posts.php';
      }
      $repo = new PostRepository($this->pdo);
      $stmt = $repo->findTagsForSitemap();
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $url = $this->generateUrl('tag/' . ltrim($row['slug'], '/'));
        $lastMod = strtotime($row['last_updated']) ?: time();
        $this->renderUrl($writer, $url, $lastMod, '0.5', 'weekly');
      }
    }

    private function renderUrl(XMLWriter $writer, string $loc, int $lastMod, string $priority, string $changefreq, array $images = []): void
    {
      $date = date('c', $lastMod);

      $writer->startElement('url');
      $writer->writeElement('loc', $loc);
      $writer->writeElement('lastmod', $date);
      $writer->writeElement('changefreq', $changefreq);
      $writer->writeElement('priority', $priority);

      // Output image information.
      if (!empty($images)) {
        foreach ($images as $img) {
          $imgUrl = $this->resolveImageUrl($img);
          if ($imgUrl !== '') {
            $writer->startElement('image:image');
            $writer->writeElement('image:loc', $imgUrl);
            $writer->endElement();
          }
        }
      }

      $writer->endElement();
    }

    private function resolveImageUrl(string $path): string
    {
      if (empty($path)) return '';

      if (function_exists('grinds_url_to_view') && function_exists('resolve_url')) {
        $url = resolve_url(grinds_url_to_view($path));

        if ($this->isSsgMode && function_exists('grinds_ssg_replace_base_url')) {
          $url = grinds_ssg_replace_base_url($url, $this->baseUrl);
        }

        if (str_starts_with($url, '//')) {
          $parsedBase = parse_url($this->baseUrl);
          $scheme = $parsedBase['scheme'] ?? 'https';
          $url = $scheme . ':' . $url;
        }
        return $url;
      }
      return '';
    }

    private function generateUrl(string $slug): string
    {
      if ($this->isSsgMode) {
        $slug = mb_strtolower($slug, 'UTF-8');
        if (pathinfo($slug, PATHINFO_EXTENSION) === '') {
          $slug .= '.html';
        }

        $parts = explode('/', ltrim($slug, '/'));
        $encodedParts = array_map('rawurlencode', $parts);
        $encodedSlug = implode('/', $encodedParts);
        return $this->baseUrl . '/' . $encodedSlug;
      }

      return function_exists('get_permalink')
        ? (string)get_permalink($slug)
        : $this->baseUrl . '/' . $slug;
    }

    private function writeCache(string $content): void {}

    protected function sendHeaders(): void
    {
      if ($this->isSsgMode || headers_sent()) {
        return;
      }
      header('Content-Type: application/xml; charset=utf-8');
      header(sprintf("Cache-Control: public, max-age=%d", self::CACHE_TTL));
      header("X-Content-Type-Options: nosniff");
    }
  }
}

// Execute generator.
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
  $isSsgMode = defined('GRINDS_IS_SSG') && GRINDS_IS_SSG;
  $baseUrl = defined('BASE_URL') ? (string)BASE_URL : '';
  if ($isSsgMode && isset($ssgBaseUrl) && $ssgBaseUrl !== '') {
    $baseUrl = $ssgBaseUrl;
  }
  $pdo = App::db();

  $generator = new SitemapGenerator($pdo, $baseUrl, $isSsgMode);
  $generator->run();
}
