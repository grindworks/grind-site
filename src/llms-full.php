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

        private function generateAndCache(): void
        {
            $tempFile = @tempnam(dirname($this->cacheFile), 'tmp_llms_');
            if ($tempFile === false) {
                $this->sendError(500);
                return;
            }

            $fp = fopen($tempFile, 'w');
            if ($fp === false) {
                $this->sendError(500);
                return;
            }

            $this->writeContent($fp);
            fclose($fp);

            if (!$this->isSsgMode) {
                if (rename($tempFile, $this->cacheFile)) {
                    chmod($this->cacheFile, 0644);
                } else {
                    grinds_force_unlink($tempFile);
                }
                $this->sendHeaders();
                readfile($this->cacheFile);
            } else {
                $this->sendHeaders();
                readfile($tempFile);
                grinds_force_unlink($tempFile);
            }
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
                    // Fetch all posts.
                    $sql = "SELECT p.id, p.title, p.slug, p.content, p.published_at, p.updated_at, p.description, p.hero_settings, c.name as category_name,
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
                ORDER BY p.published_at DESC";

                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$now]);

                    while ($row = $stmt->fetch()) {
                        $slug = (string)($row['slug'] ?? '');
                        $title = (string)($row['title'] ?? '');

                        if ($this->isSsgMode) {
                            $ssgSlug = mb_strtolower($slug, 'UTF-8');
                            if (pathinfo($ssgSlug, PATHINFO_EXTENSION) === '') $ssgSlug .= '.html';
                            $url = $this->baseUrl !== '' ? $this->baseUrl . '/' . ltrim($ssgSlug, '/') : '/' . ltrim($ssgSlug, '/');
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
            return str_replace(["\r", "\n", "[", "]"], [' ', ' ', '\[', '\]'], html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
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

            if (class_exists('DOMDocument')) {
                $dom = new DOMDocument();
                $internalErrors = libxml_use_internal_errors(true);

                // Wrap in a div to ensure a single root element and proper encoding.
                $loaded = @$dom->loadHTML('<?xml encoding="UTF-8"><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><div id="grinds-ai-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

                if ($loaded) {
                    $xpath = new DOMXPath($dom);

                    // Protect code blocks.
                    $preNodes = $xpath->query('//pre');
                    foreach ($preNodes as $preNode) {
                        $placeholder = '___GRINDS_CODE_BLOCK_' . count($codeBlocks) . '___';
                        $codeBlocks[$placeholder] = $preNode->textContent;
                        $textNode = $dom->createTextNode($placeholder);
                        $preNode->parentNode->replaceChild($textNode, $preNode);
                    }

                    // Remove noise tags and handle embedded media.
                    $noiseNodes = $xpath->query('//script | //style | //svg | //head | //noscript | //link | //meta | //comment() | //iframe | //audio | //video | //form | //nav | //header | //footer | //aside');
                    $nodesToRemove = [];
                    foreach ($noiseNodes as $node) {
                        if ($node instanceof DOMElement) {
                            $tagName = strtolower($node->nodeName);
                            if (in_array($tagName, ['iframe', 'audio', 'video'])) {
                                $src = $node->getAttribute('src');
                                if ($src) {
                                    $mediaType = 'Media';
                                    if (str_contains($src, 'youtube.com') || str_contains($src, 'youtu.be')) $mediaType = 'YouTube Video';
                                    elseif (str_contains($src, 'twitter.com') || str_contains($src, 'x.com')) $mediaType = 'X (Twitter) Post';
                                    elseif (str_contains($src, 'instagram.com')) $mediaType = 'Instagram Post';
                                    elseif ($tagName === 'audio') $mediaType = 'Audio Player';

                                    $textNode = $dom->createTextNode("[Embedded {$mediaType}: {$src}]");
                                    $node->parentNode->replaceChild($textNode, $node);
                                    continue;
                                }
                            }
                        }
                        $nodesToRemove[] = $node;
                    }
                    foreach ($nodesToRemove as $node) {
                        if ($node->parentNode) {
                            $node->parentNode->removeChild($node);
                        }
                    }

                    // Clean Base64 images, convert URLs, and strip attributes.
                    $elements = $xpath->query('//*');

                    foreach ($elements as $node) {
                        if (!($node instanceof DOMElement)) continue;

                        $tagName = strtolower($node->nodeName);

                        if ($tagName === 'img') {
                            $src = $node->getAttribute('src');
                            if (str_starts_with(strtolower($src), 'data:image/')) {
                                $alt = trim($node->getAttribute('alt'));
                                $replacementText = $alt ? "[Image excluded: {$alt}]" : "[Image excluded]";
                                $textNode = $dom->createTextNode($replacementText);
                                $node->parentNode->replaceChild($textNode, $node);
                                continue;
                            }
                        }

                        // Convert URLs to absolute.
                        foreach (['href', 'src'] as $attrName) {
                            if ($node->hasAttribute($attrName)) {
                                $url = $node->getAttribute($attrName);
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
                                    $node->setAttribute($attrName, $url);
                                } elseif (str_starts_with($url, '//')) {
                                    $parsedBase = parse_url($this->serverRoot);
                                    $scheme = $parsedBase['scheme'] ?? 'https';
                                    $node->setAttribute($attrName, $scheme . ':' . $url);
                                }
                            }
                        }

                        if ($tagName === 'img') {
                            $src = $node->getAttribute('src');
                            $alt = trim($node->getAttribute('alt'));
                            $safeAlt = str_replace(['[', ']'], ['\(', '\)'], $alt);
                            $replacementText = $alt ? "![{$safeAlt}]({$src})" : "![Image]({$src})";
                            $textNode = $dom->createTextNode($replacementText);
                            $node->parentNode->replaceChild($textNode, $node);
                            continue;
                        }

                        // Clean attributes.
                        $attrsToRemove = [];
                        foreach ($node->attributes as $attr) {
                            $name = strtolower($attr->name);
                            if (in_array($name, ['class', 'style', 'id', 'width', 'height'])) {
                                $attrsToRemove[] = $attr->name;
                            } elseif (str_starts_with($name, 'data-') && !in_array($name, ['data-ai-generated', 'data-ai-source'])) {
                                $attrsToRemove[] = $attr->name;
                            }
                        }
                        foreach ($attrsToRemove as $attrName) {
                            $node->removeAttribute($attrName);
                        }
                    }

                    // Extract HTML from the root div.
                    $html = '';
                    $root = $dom->getElementById('grinds-ai-root');
                    if ($root) {
                        foreach ($root->childNodes as $child) {
                            $html .= $dom->saveHTML($child);
                        }
                    }
                }
                libxml_clear_errors();
                libxml_use_internal_errors($internalErrors);
            }

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
            $html = preg_replace_callback('/<h([1-6])[^>]*>(.*?)<\/h\1>/is', function ($m) {
                return "\n\n" . str_repeat('#', (int)$m[1]) . ' ' . trim(strip_tags($m[2])) . "\n\n";
            }, $html) ?? $html;

            // Restore code blocks.
            foreach ($codeBlocks as $placeholder => $codeBlock) {
                $html = str_replace($placeholder, "\n```\n" . trim($codeBlock) . "\n```\n", $html);
            }

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
$isSsgMode = defined('GRINDS_IS_SSG') && GRINDS_IS_SSG;
$baseUrl = defined('BASE_URL') ? (string)BASE_URL : '';
if ($isSsgMode && isset($ssgBaseUrl) && $ssgBaseUrl !== '') {
    $baseUrl = $ssgBaseUrl;
}
$pdo = App::db();

$generator = new LlmsFullGenerator($pdo, $baseUrl, $isSsgMode);
$generator->run();
