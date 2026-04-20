<?php

declare(strict_types=1);

/**
 * Generate full content archive for AI ingestion.
 */
ini_set('display_errors', '0');
error_reporting(0);
set_time_limit(0);

// Load bootstrap.
if (!require __DIR__ . '/lib/bootstrap_public.php') {
    http_response_code(404);
    exit;
}

if (!class_exists('LlmsFullGenerator')) {
    class LlmsFullGenerator
    {
        use GeneratorCacheTrait;

        private const CACHE_TTL = 3600;

        private ?PDO $pdo;
        private string $baseUrl;
        private bool $isSsgMode;
        private string $cacheFile;
        private string $siteName;
        private string $serverRoot;

        public function __construct(?PDO $pdo, string $baseUrl, bool $isSsgMode = false)
        {
            $this->pdo = $pdo;
            $this->isSsgMode = $isSsgMode;
            $this->siteName = function_exists('get_option') ? (string)get_option('site_name') : 'GrindSite';

            // Ensure absolute URL.
            if ($this->isSsgMode && empty($baseUrl)) {
                $this->baseUrl = '';
                $this->serverRoot = '';
            } else {
                if (empty($baseUrl) || !preg_match('/^https?:\/\//i', $baseUrl)) {
                    $fallback = defined('BASE_URL') ? BASE_URL : 'https://example.com';
                    $baseUrl = rtrim($fallback, '/');
                }
                $this->baseUrl = rtrim($baseUrl, '/');

                $urlParts = parse_url($this->baseUrl);
                $this->serverRoot = (is_array($urlParts) && isset($urlParts['scheme'], $urlParts['host']))
                    ? $urlParts['scheme'] . '://' . $urlParts['host'] . (isset($urlParts['port']) ? ':' . $urlParts['port'] : '')
                    : $this->baseUrl;
            }

            $cacheDir = __DIR__ . '/data/cache/pages';
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0775, true);
            }
            $this->cacheFile = $cacheDir . '/llms-full.txt';
        }

        public function run(): void
        {
            // Check block status.
            if ($this->shouldBlockAi()) {
                $this->sendHeaders();
                echo "# Content Unavailable\n";
                echo "> This site is configured to block AI crawling or indexing.\n";
                return;
            }

            // Serve from cache if available.
            if ($this->serveFromCache()) {
                return;
            }

            // Generate and cache content.
            $this->generateAndCache();
        }

        private function shouldBlockAi(): bool
        {
            $isNoIndex = function_exists('get_option') ? (bool)get_option('site_noindex') : false;
            $isBlockAi = function_exists('get_option') ? (bool)get_option('site_block_ai') : false;
            return $isNoIndex || $isBlockAi;
        }

        public function generateToFile(string $filePath): void
        {
            if ($this->shouldBlockAi()) {
                file_put_contents($filePath, "# Content Unavailable\n> This site is configured to block AI crawling or indexing.\n");
                return;
            }
            $fp = fopen($filePath, 'w');
            if ($fp !== false) {
                $this->writeContent($fp);
                fclose($fp);
            }
        }

        public function generateAsString(): string
        {
            $fp = fopen('php://temp', 'r+');
            $this->writeContent($fp);
            rewind($fp);
            $content = stream_get_contents($fp);
            fclose($fp);
            return $content;
        }

        private function generateAndCache(): void
        {
            $content = $this->generateAsString();

            if (!$this->isSsgMode) {
                $tempFile = @tempnam(dirname($this->cacheFile), 'tmp_llms_');
                if ($tempFile !== false) {
                    file_put_contents($tempFile, $content);
                    if (rename($tempFile, $this->cacheFile)) {
                        chmod($this->cacheFile, 0644);
                    } else {
                        grinds_force_unlink($tempFile);
                    }
                }
            }

            $this->sendHeaders();
            echo $content;
        }

        private function writeContent($fp): void
        {
            fwrite($fp, "# {$this->siteName} - Full Content Archive\n");
            fwrite($fp, "> This file contains the full text of all published articles for AI context loading.\n");
            fwrite($fp, "> The content is provided in clean HTML format to preserve semantic structure.\n");
            fwrite($fp, "Generated: " . date('Y-m-d H:i:s') . "\n\n");

            $socialLinksRaw = function_exists('get_option') ? (string)get_option('official_social_links', '') : '';
            $socialLinks = array_filter(array_map('trim', explode("\n", $socialLinksRaw)));
            if (!empty($socialLinks)) {
                fwrite($fp, "## Official Profiles (E-E-A-T)\n");
                foreach ($socialLinks as $link) {
                    fwrite($fp, "- {$link}\n");
                }
                fwrite($fp, "\n");
            }

            $indexData = [];
            if ($this->pdo) {
                try {
                    $now = date('Y-m-d H:i:s');
                    $batchSize = 100;
                    $offset = 0;

                    while (true) {
                        $sql = "SELECT p.id, p.title, p.slug, p.content, p.published_at, p.updated_at, p.description, p.hero_settings, p.meta_data, c.name as category_name,
                                       GROUP_CONCAT(t.name, ', ') as tags_str
                                FROM posts p
                                LEFT JOIN categories c ON p.category_id = c.id
                                LEFT JOIN post_tags pt ON p.id = pt.post_id
                                LEFT JOIN tags t ON pt.tag_id = t.id
                                WHERE p.status = 'published' AND p.is_noindex = 0 AND p.type IN ('post', 'page')
                                AND (p.is_hide_llms = 0 OR p.is_hide_llms IS NULL)
                                AND (p.is_noarchive = 0 OR p.is_noarchive IS NULL)
                                AND (p.published_at <= ? OR p.published_at IS NULL) AND p.deleted_at IS NULL
                                GROUP BY p.id
                                ORDER BY p.published_at DESC
                                LIMIT {$batchSize} OFFSET {$offset}";

                        $stmt = $this->pdo->prepare($sql);
                        $stmt->execute([$now]);
                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (empty($rows)) {
                            break;
                        }

                        foreach ($rows as $row) {
                            $slug = (string)($row['slug'] ?? '');
                            $title = (string)($row['title'] ?? '');

                            if ($this->isSsgMode) {
                                $ssgSlug = mb_strtolower($slug, 'UTF-8');
                                if (pathinfo($ssgSlug, PATHINFO_EXTENSION) === '') $ssgSlug .= '.html';

                                $parts = explode('/', ltrim($ssgSlug, '/'));
                                $encodedParts = array_map('rawurlencode', $parts);
                                $encodedSlug = implode('/', $encodedParts);
                                $url = $this->baseUrl !== '' ? $this->baseUrl . '/' . $encodedSlug : '/' . $encodedSlug;
                            } else {
                                $url = function_exists('get_permalink') ? get_permalink($slug) : ($this->baseUrl . '/' . $slug);
                                if (!is_string($url)) {
                                    $url = $this->baseUrl . '/' . $slug;
                                }
                            }

                            $publishedAt = (string)($row['published_at'] ?? $now);
                            $updatedAt = (string)($row['updated_at'] ?? $publishedAt);

                            $date = date('Y-m-d', strtotime($publishedAt) ?: time());
                            $modDate = date('Y-m-d', strtotime($updatedAt) ?: time());

                            $indexData[] = [
                                'title' => $title,
                                'date' => $modDate,
                                'url' => $url
                            ];

                            $heroSettings = json_decode($row['hero_settings'] ?? '{}', true);

                            $contentData = json_decode($row['content'] ?? '{}', true);
                            $extractedAuthor = is_array($contentData) && function_exists('grinds_extract_author_from_content') ? grinds_extract_author_from_content($contentData) : null;
                            $extractedAuthorName = $extractedAuthor['name'] ?? '';

                            $author = $extractedAuthorName ?: (!empty($heroSettings['seo_author']) ? $heroSettings['seo_author'] : $this->siteName);
                            $category = $row['category_name'] ?? 'Uncategorized';
                            $tagsStr = $row['tags_str'] ?? '';

                            // Clean strings.
                            $cleanTitle = $this->cleanString($title);
                            $cleanAuthor = $this->cleanString($author);
                            $cleanCategory = $this->cleanString($category);

                            $descRaw = (!empty($row['description']) && is_string($row['description'])) ? trim($row['description']) : '';
                            if ($descRaw !== '') {
                                $cleanDesc = $this->cleanString($descRaw);
                            } else {
                                // Auto-summarize content.
                                $rawContentForSum = (string)($row['content'] ?? '');
                                $plainContent = function_exists('grinds_extract_text_from_content')
                                    ? grinds_extract_text_from_content($rawContentForSum)
                                    : strip_tags($rawContentForSum);
                                $plainContent = trim((string)preg_replace('/\s+/', ' ', $plainContent));
                                $decodedContent = html_entity_decode($plainContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                $cleanDesc = mb_strimwidth($decodedContent, 0, 200, '...', 'UTF-8');
                                $cleanDesc = str_replace(["\r", "\n"], ' ', $cleanDesc);
                            }

                            fwrite($fp, "-----------------------------------------------------------------\n");
                            fwrite($fp, "# {$cleanTitle}\n\n");
                            fwrite($fp, "## Metadata\n");
                            fwrite($fp, "- **Date:** {$date}\n");
                            fwrite($fp, "- **Last Modified:** {$modDate}\n");
                            fwrite($fp, "- **Author:** {$cleanAuthor}\n");
                            fwrite($fp, "- **Category:** {$cleanCategory}\n");
                            fwrite($fp, "- **URL:** {$url}\n");
                            if ($tagsStr) {
                                $cleanTags = $this->cleanString($tagsStr);
                                fwrite($fp, "- **Tags:** {$cleanTags}\n");
                            }
                            if ($cleanDesc !== '') {
                                fwrite($fp, "- **Summary:** {$cleanDesc}\n");
                            }

                            $metaData = json_decode($row['meta_data'] ?? '{}', true);
                            if (is_array($metaData) && !empty($metaData)) {
                                fwrite($fp, "\n## Custom Fields\n");
                                foreach ($metaData as $k => $v) {
                                    $valStr = is_scalar($v) ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE);
                                    if (str_contains($valStr, '{{CMS_URL}}')) {
                                        if (function_exists('grinds_url_to_view') && function_exists('resolve_url')) {
                                            $valStr = (string)resolve_url(grinds_url_to_view($valStr));
                                            if ($this->isSsgMode && function_exists('grinds_ssg_replace_base_url')) {
                                                $valStr = grinds_ssg_replace_base_url($valStr, $this->baseUrl);
                                            }
                                        }
                                    }
                                    $cleanV = $this->cleanString($valStr);
                                    fwrite($fp, "- **{$k}:** {$cleanV}\n");
                                }
                            }
                            fwrite($fp, "\n## Content\n\n");

                            // Render content (Filter out password protected blocks for AI).
                            $rawContent = (string)($row['content'] ?? '');

                            // Try to decode JSON and snip off anything after a password_protect block
                            $decodedForAi = json_decode($rawContent, true);
                            if (is_array($decodedForAi) && isset($decodedForAi['blocks'])) {
                                $visibleBlocks = [];
                                foreach ($decodedForAi['blocks'] as $block) {
                                    if (($block['type'] ?? '') === 'password_protect') {
                                        break;
                                    }
                                    $visibleBlocks[] = $block;
                                }
                                $decodedForAi['blocks'] = $visibleBlocks;
                                $rawContent = json_encode($decodedForAi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            }

                            $content = function_exists('render_content') ? render_content($rawContent) : $rawContent;

                            fwrite($fp, $this->cleanHtml((string)$content));
                            fwrite($fp, "\n\n");
                        }

                        $offset += $batchSize;
                        unset($rows);
                        if (function_exists('gc_collect_cycles')) {
                            gc_collect_cycles();
                        }
                    }
                } catch (\Throwable $e) {
                    if (class_exists('GrindsLogger')) {
                        GrindsLogger::log('LlmsFullGenerator DB Error: ' . $e->getMessage(), 'ERROR');
                    }
                }
            }

            if (!empty($indexData)) {
                fwrite($fp, "\n## Archive Index (Last Modified Dates)\n\n");
                fwrite($fp, "| Title | Last Modified | URL |\n");
                fwrite($fp, "|---|---|---|\n");
                foreach ($indexData as $entry) {
                    $decodedTitle = html_entity_decode(strip_tags($entry['title']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $safeTitle = str_replace('|', '&#124;', $decodedTitle);
                    fwrite($fp, "| {$safeTitle} | {$entry['date']} | {$entry['url']} |\n");
                }
                fwrite($fp, "\n");
            }

            fwrite($fp, "\n---\n*Generated by GrindSite CMS*");
        }

        private function cleanString(string $text): string
        {
            // Removed over-sanitization of brackets '[' and ']' to keep metadata noise-free
            return str_replace(["\r", "\n"], ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        /**
         * Clean HTML for LLM ingestion.
         */
        private function cleanHtml(string $html): string
        {
            if (empty(trim($html))) {
                return '';
            }

            if (function_exists('grinds_url_to_view')) {
                $html = grinds_url_to_view($html);
            }

            $codeBlocks = [];
            $preIndex = 0;

            // 1. Protect <pre> / <code> blocks
            $html = preg_replace_callback('/<pre\b[^>]*+>((?:[^<]++|<(?!\/pre>))*+)<\/pre>/is', function ($matches) use (&$codeBlocks, &$preIndex) {
                $placeholder = '___GRINDS_CODE_BLOCK_' . $preIndex++ . '___';
                $codeBlocks[$placeholder] = $matches[1];
                return $placeholder;
            }, $html) ?? $html;

            // 2. Remove noise tags completely (including content)
            $removeContentTagsAll = 'script|style|svg|head|noscript|form|nav|footer';
            $html = preg_replace('/<(' . $removeContentTagsAll . ')\b[^>]*+>(?:[^<]++|<(?!\/\1>))*+<\/\1>/is', '', $html) ?? $html;
            $html = preg_replace('/<(meta|link|comment)\b[^>]*+>/is', '', $html) ?? $html;
            $html = preg_replace('/<!--(?:[^-]++|-(?!->))*+-->/s', '', $html) ?? $html;

            // 3. Handle embedded media
            $html = preg_replace_callback('/<(iframe|audio|video)\b([^>]*+)>(?:[^<]++|<(?!\/\1>))*+<\/\1>/is', function ($matches) {
                $tagName = strtolower($matches[1]);
                $attrs = $matches[2];
                if (preg_match('/src=["\']([^"\']+)["\']/i', $attrs, $srcMatch)) {
                    $src = $srcMatch[1];
                    $mediaType = 'Media';
                    if (str_contains($src, 'youtube.com') || str_contains($src, 'youtu.be')) $mediaType = 'YouTube Video';
                    elseif (str_contains($src, 'twitter.com') || str_contains($src, 'x.com')) $mediaType = 'X (Twitter) Post';
                    elseif (str_contains($src, 'instagram.com')) $mediaType = 'Instagram Post';
                    elseif ($tagName === 'audio') $mediaType = 'Audio Player';
                    return "[Embedded {$mediaType}: {$src}]";
                }
                return '';
            }, $html) ?? $html;

            // 4. Handle images
            $html = preg_replace_callback('/<img\b([^>]*+)>/is', function ($matches) {
                $attrs = $matches[1];
                $src = '';
                $alt = '';
                if (preg_match('/src=["\']([^"\']++)["\']/i', $attrs, $srcMatch)) {
                    $src = $srcMatch[1];
                }
                if (preg_match('/alt=["\']([^"\']*+)["\']/i', $attrs, $altMatch)) {
                    $alt = trim($altMatch[1]);
                }

                if (str_starts_with(strtolower($src), 'data:image/')) {
                    $replacementText = $alt ? "[Image excluded: {$alt}]" : "[Image excluded]";
                    return $replacementText;
                }

                // URL conversion for src
                if (!preg_match('/^(https?:\/\/|\/\/|mailto:|tel:|data:|#)/i', $src)) {
                    if (function_exists('resolve_url')) {
                        $src = (string)resolve_url($src);
                        if ($this->isSsgMode && function_exists('grinds_ssg_replace_base_url')) {
                            $src = grinds_ssg_replace_base_url($src, $this->baseUrl);
                        }
                    } else {
                        if (!str_starts_with($src, '/')) {
                            $src = '/' . ltrim($src, './');
                        }
                    }
                    if (str_starts_with($src, '/')) {
                        $src = $this->serverRoot !== '' ? $this->serverRoot . $src : $src;
                    }
                } elseif (str_starts_with($src, '//')) {
                    $parsedBase = parse_url($this->serverRoot);
                    $scheme = $parsedBase['scheme'] ?? 'https';
                    $src = $scheme . ':' . $src;
                }

                // Escape Markdown brackets correctly to prevent syntax breakage
                $safeAlt = str_replace(['[', ']'], ['\[', '\]'], $alt);
                return $alt ? "![{$safeAlt}]({$src})" : "![Image]({$src})";
            }, $html) ?? $html;

            // 5. Convert other href/src URLs to absolute
            $html = preg_replace_callback('/(href|src)=["\']([^"\']++)["\']/i', function ($matches) {
                $attr = $matches[1];
                $url = $matches[2];
                if (!preg_match('/^(https?:\/\/|\/\/|mailto:|tel:|data:|#)/i', $url)) {
                    if (function_exists('resolve_url')) {
                        $url = (string)resolve_url($url);
                        if ($this->isSsgMode && function_exists('grinds_ssg_replace_base_url')) {
                            $url = grinds_ssg_replace_base_url($url, $this->baseUrl);
                        }
                    } else {
                        if (!str_starts_with($url, '/')) {
                            $url = '/' . ltrim($url, './');
                        }
                    }
                    if (str_starts_with($url, '/')) {
                        $url = $this->serverRoot !== '' ? $this->serverRoot . $url : $url;
                    }
                } elseif (str_starts_with($url, '//')) {
                    $parsedBase = parse_url($this->serverRoot);
                    $scheme = $parsedBase['scheme'] ?? 'https';
                    $url = $scheme . ':' . $url;
                }
                return "{$attr}=\"{$url}\"";
            }, $html) ?? $html;

            // Strip non-semantic layout tags.
            $allowedTags = '<p><br><h1><h2><h3><h4><h5><h6><ul><ol><li><dl><dt><dd><table><thead><tbody><tfoot><tr><th><td><blockquote><a><strong><b><i><em><time><address><figure><figcaption><details><summary><code>';
            $html = strip_tags($html, $allowedTags);

            // Remove nested empty tags.
            $previous_html = '';
            $iterations = 0;
            while ($previous_html !== $html && $iterations < 3) {
                $previous_html = $html;
                $html = preg_replace('/<(?!(?:td|th|tr|table|thead|tbody|tfoot|ul|ol|li|dl|dt|dd)\b)([a-z0-9\-]+)\s*>\s*<\/\1>/i', '', $html) ?? $html;
                $iterations++;
            }

            // Convert heading tags to Markdown for better RAG chunking.
            $html = preg_replace_callback('/<h([1-6])[^>]*+>((?:[^<]++|<(?!\/h\1>))*+)<\/h\1>/is', function ($m) {
                return "\n\n" . str_repeat('#', (int)$m[1]) . ' ' . trim(strip_tags($m[2])) . "\n\n";
            }, $html) ?? $html;

            // Restore code blocks.
            foreach ($codeBlocks as $placeholder => $codeBlock) {
                $html = str_replace((string)$placeholder, "\n\n```\n" . trim((string)$codeBlock) . "\n```\n\n", $html);
            }

            // Decode HTML entities to maximize LLM token efficiency
            $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $html = preg_replace('/<\s*([a-zA-Z0-9]+)\s+>/i', '<$1>', $html) ?? $html;
            $html = str_replace('&nbsp;', ' ', $html);
            $html = preg_replace("/\n{3,}/", "\n\n", $html) ?? $html;

            return trim($html);
        }

        protected function sendHeaders(): void
        {
            if ($this->isSsgMode || headers_sent()) {
                return;
            }
            header('Content-Type: text/plain; charset=UTF-8');
            header(sprintf("Cache-Control: public, max-age=%d", self::CACHE_TTL));
            header("X-Robots-Tag: noindex");
            header("X-Content-Type-Options: nosniff");
            header("X-Frame-Options: DENY");
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

    $generator = new LlmsFullGenerator($pdo, $baseUrl, $isSsgMode);
    $generator->run();
}
