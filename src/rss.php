<?php

declare(strict_types=1);

/**
 * Generate RSS 2.0 feed for published posts.
 */
ini_set('display_errors', '0');
error_reporting(0);

// Load bootstrap.
if (!require __DIR__ . '/lib/bootstrap_public.php') {
  http_response_code(404);
  exit;
}

if (!class_exists('RssGenerator')) {
  class RssGenerator
  {
    private const CACHE_TTL = 3600;
    private const MAX_ITEMS = 50;

    private ?PDO $pdo;
    private string $baseUrl;
    private string $serverRoot;
    private string $cacheFile;
    private bool $isSsgMode;
    private string $siteName;
    private string $siteDesc;
    private string $siteLang;

    public function __construct(?PDO $pdo, string $baseUrl, bool $isSsgMode = false)
    {
      $this->pdo = $pdo;
      $this->isSsgMode = $isSsgMode;

      // Ensure absolute URL.
      if (!preg_match('/^https?:\/\//i', $baseUrl)) {
        $scheme = (function_exists('is_ssl') && is_ssl()) ? 'https://' : 'http://';
        $host = preg_replace('/[^a-zA-Z0-9.:_-]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
        $baseUrl = $scheme . $host . rtrim($baseUrl, '/');
      }
      $this->baseUrl = rtrim($baseUrl, '/');

      $urlParts = parse_url($this->baseUrl);
      $this->serverRoot = (is_array($urlParts) && isset($urlParts['scheme'], $urlParts['host']))
        ? $urlParts['scheme'] . '://' . $urlParts['host'] . (isset($urlParts['port']) ? ':' . $urlParts['port'] : '')
        : $this->baseUrl;

      $this->siteName = function_exists('get_option') ? (string)get_option('site_name', 'GrindSite') : 'GrindSite';
      $this->siteDesc = function_exists('get_option') ? (string)get_option('site_description', '') : '';
      $this->siteLang = function_exists('get_option') ? (string)get_option('site_lang', 'en') : 'en';

      $cacheDir = __DIR__ . '/data/cache/pages';
      if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0775, true);
      }
      $this->cacheFile = $cacheDir . '/rss.xml';
    }

    public function run(): void
    {
      if (!$this->pdo) {
        $this->sendResponse(500);
        return;
      }

      if (function_exists('get_option') && get_option('site_noindex')) {
        $this->sendResponse(404);
        return;
      }

      if ($this->serveFromCache()) {
        return;
      }

      $this->generateAndCacheFeed();
    }

    private function serveFromCache(): bool
    {
      if (!file_exists($this->cacheFile)) {
        return false;
      }

      try {
        $cacheMtime = filemtime($this->cacheFile);
        if ($cacheMtime === false) {
          return false;
        }

        $lastContentUpdate = $this->getLastContentUpdateTime();
        if ($lastContentUpdate !== null && $cacheMtime >= $lastContentUpdate) {
          $this->sendHeaders();
          readfile($this->cacheFile);
          return true;
        }
      } catch (\Throwable $e) {
        if (class_exists('GrindsLogger')) {
          GrindsLogger::log('RssGenerator Cache Error: ' . $e->getMessage(), 'WARNING');
        }
      }

      return false;
    }

    /**
     * @throws \Exception
     */
    private function getLastContentUpdateTime(): ?int
    {
      if (!class_exists('PostRepository')) {
        require_once __DIR__ . '/lib/functions/posts.php';
      }
      $repo = new PostRepository($this->pdo);
      $latest = $repo->getLatestPostTimestamp([
        'status' => 'published',
        'type' => 'post',
        'is_noindex' => 0
      ]);
      return $latest ? strtotime($latest) : null;
    }

    private function generateAndCacheFeed(): void
    {
      if (!$this->isSsgMode) {
        while (ob_get_level()) {
          ob_end_clean();
        }
      }
      ob_start();
      $this->sendHeaders();

      echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
      <rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/">
        <channel>
          <title><?= htmlspecialchars($this->siteName, ENT_XML1, 'UTF-8') ?></title>
          <link><?= htmlspecialchars($this->baseUrl, ENT_XML1, 'UTF-8') ?>/</link>
          <description><?= htmlspecialchars($this->siteDesc, ENT_XML1, 'UTF-8') ?></description>
          <language><?= htmlspecialchars($this->siteLang, ENT_XML1, 'UTF-8') ?></language>
          <atom:link href="<?= htmlspecialchars($this->baseUrl . '/rss.xml', ENT_XML1, 'UTF-8') ?>" rel="self" type="application/rss+xml" />
          <lastBuildDate><?= date(DATE_RSS) ?></lastBuildDate>
          <generator>GrindSite</generator>
          <?php $this->generateFeedItems(); ?>
        </channel>
      </rss>
<?php
      $content = ob_get_flush();

      if (!$this->isSsgMode && $content && is_writable(dirname($this->cacheFile))) {
        $tempFile = tempnam(dirname($this->cacheFile), 'rss_');
        if ($tempFile) {
          if (file_put_contents($tempFile, $content) !== false) {
            chmod($tempFile, 0664);
            if (!rename($tempFile, $this->cacheFile)) {
              grinds_force_unlink($tempFile);
            }
          } else {
            grinds_force_unlink($tempFile);
          }
        }
      }
    }

    private function generateFeedItems(): void
    {
      try {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT p.*, c.name AS category_name
                    FROM posts p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.status = 'published'
                      AND (p.published_at <= ? OR p.published_at IS NULL)
                      AND p.deleted_at IS NULL
                      AND p.type = 'post'
                      AND (p.is_hide_rss = 0 OR p.is_hide_rss IS NULL)
                      AND (p.is_noindex = 0 OR p.is_noindex IS NULL)
                    ORDER BY p.published_at DESC
                    LIMIT " . self::MAX_ITEMS;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$now]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Preload image metadata to prevent N+1 queries.
        if (function_exists('grinds_preload_image_meta')) {
          $imageUrls = [];
          foreach ($rows as $r) {
            if (!empty($r['thumbnail'])) {
              $imageUrls[] = $r['thumbnail'];
            }
            $blocks = json_decode($r['content'] ?? '', true);
            if (is_array($blocks) && isset($blocks['blocks'])) {
              foreach ($blocks['blocks'] as $block) {
                if (($block['type'] ?? '') === 'image' && !empty($block['data']['url'])) {
                  $imageUrls[] = $block['data']['url'];
                }
              }
            }
          }
          if (!empty($imageUrls)) {
            grinds_preload_image_meta($imageUrls);
          }
        }

        foreach ($rows as $row) {
          echo $this->renderItem($row);
        }
      } catch (\Throwable $e) {
        if (class_exists('GrindsLogger')) {
          GrindsLogger::log('RssGenerator DB Error: ' . $e->getMessage(), 'ERROR');
        }
      }
    }

    private function renderItem(array $row): string
    {
      $link = $this->generateItemUrl((string)$row['slug']);
      $safeLink = htmlspecialchars($link, ENT_XML1, 'UTF-8');

      // Process dates.
      $pubTimestamp = strtotime((string)($row['published_at'] ?? $row['created_at']));
      $modTimestamp = strtotime((string)($row['updated_at'] ?? $row['published_at'] ?? $row['created_at']));
      $pubDate = $pubTimestamp ? date(DATE_RSS, $pubTimestamp) : null;
      $modDate = $modTimestamp ? date(DATE_ATOM, $modTimestamp) : null;

      // Process author.
      $heroSettings = json_decode($row['hero_settings'] ?? '{}', true);
      $authorRaw = !empty($heroSettings['seo_author']) ? $heroSettings['seo_author'] : $this->siteName;
      $author = htmlspecialchars($this->sanitizeString($authorRaw), ENT_XML1, 'UTF-8');

      // Process content and summary.
      $rawContent = function_exists('render_content') ? render_content((string)$row['content']) : (string)$row['content'];
      $cleanHtml = $this->sanitizeContentForRss($rawContent);
      $summary = $this->createSummary((string)($row['description'] ?? ''), (string)($row['content'] ?? ''));

      $title = htmlspecialchars($this->sanitizeString((string)$row['title']), ENT_XML1, 'UTF-8');
      $categoryRaw = $this->sanitizeString((string)($row['category_name'] ?? ''));
      $category = $categoryRaw ? htmlspecialchars($categoryRaw, ENT_XML1, 'UTF-8') : '';
      $contentEncoded = str_replace(']]>', ']]]]><![CDATA[>', $this->sanitizeString($cleanHtml, false));
      $guid = htmlspecialchars($this->baseUrl . '/?id=' . $row['id'], ENT_XML1, 'UTF-8');

      $itemXml = "    <item>\n";
      $itemXml .= "      <title>{$title}</title>\n";
      $itemXml .= "      <link>{$safeLink}</link>\n";
      $itemXml .= "      <dc:creator>{$author}</dc:creator>\n";
      $itemXml .= "      <guid isPermaLink=\"false\">{$guid}</guid>\n";
      if ($pubDate) {
        $itemXml .= "      <pubDate>{$pubDate}</pubDate>\n";
      }
      if ($modDate) {
        $itemXml .= "      <atom:updated>{$modDate}</atom:updated>\n";
        $itemXml .= "      <dc:date>{$modDate}</dc:date>\n";
      }
      $itemXml .= "      <description>{$summary}</description>\n";
      $itemXml .= "      <content:encoded><![CDATA[{$contentEncoded}]]></content:encoded>\n";
      if ($category) {
        $itemXml .= "      <category>{$category}</category>\n";
      }

      $enclosure = $this->getEnclosureTag($row['thumbnail'] ?? '');
      if ($enclosure) {
        $itemXml .= "      {$enclosure}\n";
      }

      $itemXml .= "    </item>\n";

      return $itemXml;
    }

    private function generateItemUrl(string $slug): string
    {
      if ($this->isSsgMode) {
        $slug = mb_strtolower($slug, 'UTF-8');
        if (pathinfo($slug, PATHINFO_EXTENSION) === '') {
          $slug .= '.html';
        }
        // Encode path parts.
        $parts = explode('/', $slug);
        $encodedParts = array_map('rawurlencode', $parts);
        $slug = implode('/', $encodedParts);
        return $this->baseUrl . '/' . $slug;
      }

      return function_exists('get_permalink')
        ? (string)get_permalink($slug)
        : $this->baseUrl . '/' . $slug;
    }

    private function sanitizeString(string $str, bool $stripTags = true): string
    {
      if ($stripTags) {
        $str = html_entity_decode(strip_tags($str), ENT_QUOTES | ENT_HTML5, 'UTF-8');
      }
      return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $str);
    }

    private function getEnclosureTag(string $thumbnail): string
    {
      if (empty($thumbnail)) {
        return '';
      }

      $length = 0;

      // Fetch file size from database for internal URLs.
      if (!preg_match('/^https?:\/\//i', $thumbnail) && $this->pdo) {
        $cleanPath = ltrim(str_replace('{{CMS_URL}}/', '', $thumbnail), '/');
        try {
          $stmt = $this->pdo->prepare("SELECT file_size FROM media WHERE filepath = ?");
          $stmt->execute([$cleanPath]);
          $dbSize = $stmt->fetchColumn();
          if ($dbSize) {
            $length = (int)$dbSize;
          } elseif (defined('ROOT_PATH')) {
            // Fallback to physical file size.
            $physPath = ROOT_PATH . '/' . $cleanPath;
            if (file_exists($physPath)) {
              $length = (int)@filesize($physPath);
            }
          }
        } catch (\Throwable $e) {
        }
      }

      $thumbUrl = function_exists('grinds_url_to_view') ? grinds_url_to_view($thumbnail) : $thumbnail;
      $resolvedThumb = (string)resolve_url($thumbUrl);

      // Ensure absolute URL.
      if (str_starts_with($resolvedThumb, '//')) {
        $parsedBase = parse_url($this->serverRoot);
        $scheme = $parsedBase['scheme'] ?? 'https';
        $resolvedThumb = $scheme . ':' . $resolvedThumb;
      } elseif (str_starts_with($resolvedThumb, '/')) {
        $resolvedThumb = $this->serverRoot . $resolvedThumb;
      }

      $path = parse_url($resolvedThumb, PHP_URL_PATH);
      $ext = strtolower(pathinfo((string)$path, PATHINFO_EXTENSION));
      $mime = match ($ext) {
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        default => 'image/jpeg'
      };

      $safeUrl = htmlspecialchars($resolvedThumb, ENT_XML1, 'UTF-8');
      return sprintf('<enclosure url="%s" type="%s" length="%d" />', $safeUrl, $mime, $length);
    }

    private function createSummary(string $description, string $content): string
    {
      $raw = !empty($description) ? $description : $content;
      $text = function_exists('grinds_extract_text_from_content') ? grinds_extract_text_from_content($raw) : strip_tags($raw);
      $text = $this->sanitizeString((string)$text, false); // Tags already stripped
      $text = trim(preg_replace('/\s+/u', ' ', $text));
      return htmlspecialchars(mb_strimwidth($text, 0, 200, '...', 'UTF-8'), ENT_XML1, 'UTF-8');
    }

    private function sanitizeContentForRss(string $html): string
    {
      // Resolve shortcodes.
      if (function_exists('grinds_url_to_view')) {
        $html = grinds_url_to_view($html);
      }

      // Strip dangerous or noisy tags.
      $html = preg_replace('/<(script|style|iframe|object|embed|applet|form|noscript)\b[^>]*+>(?:[^<]++|<(?!\/\1>))*+<\/\1>/i', '', $html) ?? $html;
      $html = preg_replace('/<svg\b[^>]*+>(?:[^<]++|<(?!\/svg>))*+<\/svg>/i', '', $html) ?? $html;

      // Remove HTML comments.
      $html = preg_replace('/<!--(?:[^-]++|-(?!->))*+-->/', '', $html) ?? $html;

      // Strip picture and source tags while keeping images.
      $html = preg_replace('/<\/?picture[^>]*>/i', '', $html) ?? $html;
      $html = preg_replace('/<source[^>]*>/i', '', $html) ?? $html;

      // Strip Base64 inline images.
      $html = preg_replace('/<img[^>]+src=["\']data:image\/[^"\']+["\'][^>]*>/i', '', $html) ?? $html;

      // Convert relative URLs to absolute.
      $html = preg_replace_callback('/(href|src)=["\']([^"\']+)["\']/i', function ($m) {
        $url = $m[2];
        if (preg_match('/^(https?:\/\/|\/\/|mailto:|tel:|data:|#)/i', $url)) {
          return $m[0];
        }
        if (function_exists('resolve_url')) {
          $url = (string)resolve_url($url);
        } else {
          if (!str_starts_with($url, '/')) {
            $url = '/' . ltrim($url, './');
          }
        }
        if (str_starts_with($url, '/')) {
          $url = $this->serverRoot . $url;
        }
        return $m[1] . '="' . $url . '"';
      }, $html) ?? $html;

      // Convert protocol-relative URLs.
      $html = preg_replace_callback('/(href|src)=["\']\/\/([^"\']+)["\']/i', function ($m) {
        $parsedBase = parse_url($this->serverRoot);
        $scheme = $parsedBase['scheme'] ?? 'https';
        return $m[1] . '="' . $scheme . '://' . $m[2] . '"';
      }, $html) ?? $html;

      // Convert srcset URLs.
      $html = preg_replace_callback('/srcset=["\']([^"\']+)["\']/i', function ($matches) {
        $srcset = preg_replace_callback('/(^|,\s*)([^\s,]+)/', function ($m) {
          $url = $m[2];
          if (preg_match('/^(https?:\/\/|\/\/|data:)/i', $url)) {
            return $m[0];
          }
          if (function_exists('resolve_url')) {
            $url = (string)resolve_url($url);
          } else {
            if (!str_starts_with($url, '/')) {
              $url = '/' . ltrim($url, './');
            }
          }
          if (str_starts_with($url, '/')) {
            $url = $this->serverRoot . $url;
          }
          return $m[1] . $url;
        }, $matches[1]);
        return 'srcset="' . ($srcset ?? $matches[1]) . '"';
      }, $html) ?? $html;

      // Normalize line breaks.
      $html = str_replace("\r\n", "\n", $html);
      $html = preg_replace("/\n{3,}/", "\n\n", $html) ?? $html;

      return trim($html);
    }

    private function sendHeaders(): void
    {
      if ($this->isSsgMode || headers_sent()) {
        return;
      }
      header("Content-Type: application/rss+xml; charset=utf-8");
      header(sprintf("Cache-Control: public, max-age=%d", self::CACHE_TTL));
    }

    private function sendResponse(int $code): void
    {
      if ($this->isSsgMode) {
        return;
      }
      http_response_code($code);
      exit;
    }
  }
}

// Execute generator.
$isSsgMode = defined('GRINDS_IS_SSG') && GRINDS_IS_SSG;
$baseUrl = defined('BASE_URL') ? (string)BASE_URL : '';
if ($isSsgMode && isset($ssgBaseUrl) && $ssgBaseUrl !== '') {
  $baseUrl = $ssgBaseUrl;
}
$pdo = App::db();

$generator = new RssGenerator($pdo, $baseUrl, $isSsgMode);
$generator->run();
