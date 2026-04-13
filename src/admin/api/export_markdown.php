<?php

/**
 * export_markdown.php
 *
 * Exports all posts and pages as Markdown files with YAML frontmatter.
 * Perfect for migrating to Astro, Hugo, Gatsby, or other Headless setups.
 */

// Use the API bootstrap to enforce authentication and CSRF checks
require_once __DIR__ . '/api_bootstrap.php';

// Ensure the user has permission to manage tools (export data)
if (!current_user_can('manage_tools')) {
    http_response_code(403);
    exit('Access Denied: You do not have permission to export data.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

check_csrf_token();

// Relax limits for potentially heavy export operations
if (function_exists('grinds_set_high_load_mode')) {
    grinds_set_high_load_mode();
}

if (!class_exists('ZipArchive')) {
    grinds_render_error_page('Missing Extension', 'Error: PHP Zip extension is required for exporting.', '500 System Error', 500);
    exit;
}

/**
 * Helper to safely convert GrindSite JSON blocks to clean Markdown.
 */
function grinds_blocks_to_markdown($contentJson)
{
    $data = json_decode($contentJson, true);
    if (!is_array($data) || empty($data['blocks'])) {
        // Fallback for classic editor or raw HTML
        $text = strip_tags($contentJson, '<p><br><h1><h2><h3><h4><ul><ol><li><blockquote><pre><code>');
        $text = str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n\n", $text);
        return trim(strip_tags($text));
    }

    // Convert inline HTML tags to beautiful Markdown syntax
    $htmlToMarkdown = function ($html) {
        $safeReplace = function ($pattern, $replacement, $subject) {
            if (!is_string($subject)) return '';
            $result = @preg_replace($pattern, $replacement, $subject);
            return ($result === null) ? $subject : $result;
        };
        $md = str_replace(['<br>', '<br/>', '<br />'], "\n", $html ?? '');
        $md = $safeReplace('/<(b|strong)\b[^>]*>(.*?)<\/\1>/is', '**$2**', $md);
        $md = $safeReplace('/<(i|em)\b[^>]*>(.*?)<\/\1>/is', '*$2*', $md);
        $md = $safeReplace('/<(s|strike|del)\b[^>]*>(.*?)<\/\1>/is', '~~$2~~', $md);
        $md = $safeReplace('/<code\b[^>]*>(.*?)<\/code>/is', '`$1`', $md);
        $md = $safeReplace('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', '$2', $md);
        return strip_tags($md);
    };

    $md = "";
    foreach ($data['blocks'] as $block) {
        $type = $block['type'] ?? '';
        $bData = $block['data'] ?? [];

        if ($type === 'password_protect') {
            $md .= "\n\n> **[Password Protected Content Below]**\n\n";
            break; // Stop parsing blocks if password protected
        }

        switch ($type) {
            case 'header':
                $levelStr = str_replace('h', '', $bData['level'] ?? '2');
                $level = (int)$levelStr ?: 2;
                $prefix = str_repeat('#', $level);
                $text = $htmlToMarkdown($bData['text'] ?? '');
                $md .= "\n\n{$prefix} {$text}\n\n";
                break;
            case 'paragraph':
                $text = $htmlToMarkdown($bData['text'] ?? '');
                $md .= "\n\n{$text}\n\n";
                break;
            case 'list':
                $style = $bData['style'] ?? 'unordered';
                $items = $bData['items'] ?? [];
                $md .= "\n\n";
                foreach ($items as $idx => $item) {
                    $itemText = $htmlToMarkdown($item);
                    if ($style === 'ordered') {
                        $md .= ($idx + 1) . ". {$itemText}\n";
                    } else {
                        $md .= "- {$itemText}\n";
                    }
                }
                $md .= "\n\n";
                break;
            case 'image':
                $url = $bData['url'] ?? '';
                $alt = $bData['alt'] ?? $bData['caption'] ?? 'image';
                $md .= "\n\n![{$alt}]({$url})\n\n";
                break;
            case 'code':
                $lang = $bData['language'] ?? 'plaintext';
                $code = $bData['code'] ?? '';
                $md .= "\n\n```{$lang}\n{$code}\n```\n\n";
                break;
            case 'quote':
                $text = $htmlToMarkdown($bData['text'] ?? '');
                $cite = $bData['cite'] ?? '';
                $md .= "\n\n> " . str_replace("\n", "\n> ", $text);
                if ($cite) $md .= "\n> — {$cite}";
                $md .= "\n\n";
                break;
            case 'divider':
                $md .= "\n\n---\n\n";
                break;
            case 'html':
                $md .= "\n\n" . ($bData['code'] ?? '') . "\n\n";
                break;
            default:
                // For complex blocks, try to extract plain text as fallback
                $fallback = '';
                array_walk_recursive($bData, function ($value, $key) use (&$fallback) {
                    if (is_string($value) && !preg_match('/^https?:\/\//i', $value)) {
                        $fallback .= strip_tags($value) . " ";
                    }
                });
                $fallback = trim($fallback);
                if ($fallback) $md .= "\n\n<!-- {$type} block -->\n{$fallback}\n\n";
                break;
        }
    }

    // Clean up excessive newlines
    return preg_replace("/\n{3,}/", "\n\n", trim($md));
}

// Create temporary ZIP file
$tmpDir = ROOT_PATH . '/data/tmp';
if (!is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);
$zipFilename = 'grinds_markdown_export_' . date('Ymd_His') . '.zip';
$zipPath = $tmpDir . '/' . $zipFilename;

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    http_response_code(500);
    exit('Failed to create ZIP archive.');
}

// Setup ZIP Encryption (AES-256)
$zipPassword = '';
if (function_exists('get_option')) {
    $zipPassword = get_option('backup_zip_password', '');
}
if (!empty($zipPassword)) {
    $zip->setPassword($zipPassword);
}

try {
    // Query all posts and pages in batches to prevent Out of Memory (OOM) crashes
    $repo = new PostRepository($pdo);
    $batchSize = 100;
    $offset = 0;

    while (true) {
        $posts = $repo->fetch([
            'type' => ['post', 'page'],
            'status' => 'all' // Exclude trashed posts
        ], $batchSize, $offset, 'p.id ASC');

        if (empty($posts)) {
            break; // No more posts to process
        }

        if (function_exists('grinds_attach_tags')) {
            grinds_attach_tags($posts);
        }

        foreach ($posts as $post) {
            // Build YAML Frontmatter
            $fm = "---\n";
            $cleanTitleForLlm = html_entity_decode(strip_tags($post['title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $fm .= "title: \"" . addcslashes($cleanTitleForLlm, '"\\') . "\"\n";
            $fm .= "slug: \"{$post['slug']}\"\n";
            $fm .= "type: \"{$post['type']}\"\n";
            $fm .= "status: \"{$post['status']}\"\n";

            $date = $post['published_at'] ?: $post['created_at'];
            if ($date) $fm .= "date: " . date('c', strtotime($date)) . "\n";

            // Add lastmod to frontmatter to convey information freshness to AI/SSG
            $updated = $post['updated_at'] ?: $date;
            if ($updated) $fm .= "lastmod: " . date('c', strtotime($updated)) . "\n";

            if ($post['category_name']) {
                $fm .= "category: \"{$post['category_name']}\"\n";
            }

            if (!empty($post['tags'])) {
                $tagNames = array_column($post['tags'], 'name');
                $fm .= "tags:\n";
                foreach ($tagNames as $tag) {
                    $fm .= "  - \"{$tag}\"\n";
                }
            }

            if (!empty($post['description'])) {
                $cleanDescForLlm = html_entity_decode(strip_tags($post['description']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $fm .= "description: \"" . addcslashes(str_replace(["\r", "\n"], ' ', $cleanDescForLlm), '"\\') . "\"\n";
            }

            if (!empty($post['thumbnail'])) {
                $thumbUrl = Routing::restoreViewUrl($post['thumbnail']);
                $fm .= "image: \"{$thumbUrl}\"\n";
            }

            $metaData = json_decode($post['meta_data'] ?? '{}', true);
            if (is_array($metaData) && !empty($metaData)) {
                $fm .= "custom_fields:\n";
                foreach ($metaData as $k => $v) {
                    if (!is_scalar($v)) {
                        $v = json_encode($v, JSON_UNESCAPED_UNICODE);
                    }
                    $vStr = (string)$v;
                    if (preg_match('/^assets\/uploads\//i', $vStr) || str_contains($vStr, '{{CMS_URL}}')) {
                        $vStr = resolve_url(Routing::restoreViewUrl($vStr));
                    }
                    $cleanV = addcslashes(html_entity_decode(strip_tags($vStr), ENT_QUOTES | ENT_HTML5, 'UTF-8'), '"\\');
                    $fm .= "  {$k}: \"{$cleanV}\"\n";
                }
            }

            $fm .= "---\n\n";

            // Convert Content
            $contentUrlFixed = Routing::restoreViewUrl($post['content']);
            $markdownBody = grinds_blocks_to_markdown($contentUrlFixed);

            $fileContent = $fm . $markdownBody;

            // Determine path in ZIP (group posts by category, and separate pages)
            $folder = ($post['type'] === 'page') ? 'pages' : 'posts';
            if ($post['type'] === 'post' && !empty($post['category_slug'])) {
                $safeCat = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower($post['category_slug']));
                if ($safeCat !== '') {
                    $folder .= '/' . $safeCat;
                }
            }

            $filename = "{$folder}/{$post['slug']}.md";
            $zip->addFromString($filename, $fileContent);

            // Apply encryption to the added file if password is set
            if (!empty($zipPassword)) {
                $zip->setEncryptionName($filename, ZipArchive::EM_AES_256);
            }
        }

        // Free memory for the next batch
        $offset += $batchSize;
        unset($posts);
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    $zip->close();

    // Stream download
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename=' . $zipFilename);
    header('Content-Length: ' . filesize($zipPath));
    header('Pragma: no-cache');
    header('Expires: 0');

    @readfile($zipPath);
    @unlink($zipPath);
    exit;
} catch (Exception $e) {
    if ($zip) $zip->close();
    if (file_exists($zipPath)) @unlink($zipPath);
    grinds_render_error_page('Export Failed', $e->getMessage(), '500 System Error', 500);
    exit;
}
