<?php

/**
 * export_markdown.php
 *
 * Exports all posts and pages as Markdown files with YAML frontmatter.
 * Perfect for migrating to Astro, Hugo, Gatsby, or other Headless setups.
 *
 * [Refactored to Asynchronous Chunk Architecture to prevent OOM/Timeouts]
 */

// Use the API bootstrap to enforce authentication and CSRF checks
require_once __DIR__ . '/api_bootstrap.php';

// Ensure the user has permission to manage tools (export data)
if (!current_user_can('manage_tools')) {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    if ($action === 'download') {
        while (ob_get_level()) ob_end_clean();
        http_response_code(403);
        exit('Access Denied: You do not have permission to export data.');
    }
    json_response(['success' => false, 'error' => 'Permission denied'], 403);
}

// Reject any direct accesses that are not valid POST calls
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

check_csrf_token();
session_write_close(); // Prevent session locking during export

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
        $md = $safeReplace('/<(b|strong)\b[^>]*+>((?:[^<]++|<(?!\/\1>))*+)<\/\1>/is', '**$2**', $md);
        $md = $safeReplace('/<(i|em)\b[^>]*+>((?:[^<]++|<(?!\/\1>))*+)<\/\1>/is', '*$2*', $md);
        $md = $safeReplace('/<(s|strike|del)\b[^>]*+>((?:[^<]++|<(?!\/\1>))*+)<\/\1>/is', '~~$2~~', $md);
        $md = $safeReplace('/<code\b[^>]*+>((?:[^<]++|<(?!\/code>))*+)<\/code>/is', '`$1`', $md);
        $md = $safeReplace('/<a\b[^>]*+href=["\']([^"\']++)["\'][^>]*+>((?:[^<]++|<(?!\/a>))*+)<\/a>/is', '$2', $md);
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
            case 'table':
                if (!empty($bData['content']) && is_array($bData['content'])) {
                    $md .= "\n\n";
                    foreach ($bData['content'] as $rIdx => $row) {
                        $md .= "|";
                        foreach ($row as $cell) {
                            $cellText = str_replace('|', '&#124;', $htmlToMarkdown($cell ?? ''));
                            $md .= " {$cellText} |";
                        }
                        $md .= "\n";
                        // ヘッダー行の区切り線を追加
                        if ($rIdx === 0 && !empty($bData['withHeadings'])) {
                            $md .= "|";
                            foreach ($row as $cell) {
                                $md .= "---|";
                            }
                            $md .= "\n";
                        }
                    }
                    $md .= "\n\n";
                }
                break;
            case 'proscons':
                $pTitle = $htmlToMarkdown($bData['pros_title'] ?? 'Pros');
                $cTitle = $htmlToMarkdown($bData['cons_title'] ?? 'Cons');
                $md .= "\n\n### {$pTitle}\n";
                foreach ($bData['pros_items'] ?? [] as $item) {
                    if (trim($item)) $md .= "- [x] " . $htmlToMarkdown($item) . "\n";
                }
                $md .= "\n### {$cTitle}\n";
                foreach ($bData['cons_items'] ?? [] as $item) {
                    if (trim($item)) $md .= "- [ ] " . $htmlToMarkdown($item) . "\n";
                }
                $md .= "\n\n";
                break;
            case 'step':
                $md .= "\n\n";
                foreach ($bData['items'] ?? [] as $idx => $item) {
                    $stepTitle = $htmlToMarkdown($item['title'] ?? '');
                    $stepDesc = $htmlToMarkdown($item['desc'] ?? '');
                    $md .= "#### Step " . ($idx + 1) . ": {$stepTitle}\n";
                    if ($stepDesc) $md .= "{$stepDesc}\n";
                    $md .= "\n";
                }
                $md .= "\n";
                break;
            case 'accordion':
                $md .= "\n\n";
                foreach ($bData['items'] ?? [] as $item) {
                    $q = $htmlToMarkdown($item['title'] ?? '');
                    $a = $htmlToMarkdown($item['content'] ?? '');
                    $md .= "**Q. {$q}**\n\n> A. " . str_replace("\n", "\n> ", $a) . "\n\n";
                }
                $md .= "\n";
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

// Prepare ZIP file paths securely tied to the user's session
$uid = substr(hash('sha256', session_id() . ($_SESSION['user_id'] ?? '')), 0, 16);
$tmpDir = ROOT_PATH . '/data/tmp';
if (!is_dir($tmpDir)) @mkdir($tmpDir, 0775, true);
$zipFilename = "markdown_export_{$uid}.zip";
$zipPath = $tmpDir . '/' . $zipFilename;

// Handle file download request
$action = $_POST['action'] ?? $_GET['action'] ?? '';
if ($action === 'download') {
    if (file_exists($zipPath)) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename=grinds_markdown_export_' . date('Ymd_His') . '.zip');
        header('Content-Length: ' . filesize($zipPath));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        if (function_exists('set_time_limit')) @set_time_limit(0);
        $handle = @fopen($zipPath, 'rb');
        if ($handle) {
            while (!feof($handle)) {
                echo fread($handle, 8192);
                flush();
            }
            fclose($handle);
        }
        // Delete temp file after download completes
        @unlink($zipPath);
        exit;
    } else {
        while (ob_get_level()) ob_end_clean();
        http_response_code(404);
        exit('File not found');
    }
}

if (function_exists('grinds_set_high_load_mode')) {
    grinds_set_high_load_mode();
}

if (!class_exists('ZipArchive')) {
    json_response(['success' => false, 'error' => 'PHP Zip extension is required.'], 500);
}

// Ensure Database Connection is active
$pdo = App::db();
if (!$pdo) {
    json_response(['success' => false, 'error' => 'Database connection failed.'], 500);
}

$step = $_POST['step'] ?? 'init';
$currentCsrfToken = $_SESSION['csrf_token'] ?? '';
$data = json_decode($_POST['data'] ?? '{}', true) ?: [];

try {
    if ($step === 'init') {
        // Cleanup old exports to prevent disk space buildup
        foreach (glob($tmpDir . '/markdown_export_*.zip') as $f) {
            if (is_file($f) && filemtime($f) < time() - 3600) {
                @unlink($f);
            }
        }
        if (file_exists($zipPath)) {
            @unlink($zipPath);
        }

        // Get total posts to process
        $repo = new PostRepository($pdo);
        $total = $repo->count([
            'type' => ['post', 'page'],
            'status' => 'all'
        ]);

        json_response([
            'success' => true,
            'total_posts' => $total,
            'csrf_token' => $currentCsrfToken
        ]);
    } elseif ($step === 'process_batch') {
        $offset = (int)($data['offset'] ?? 0);
        $batchSize = 50; // 50 posts per chunk for stability

        $repo = new PostRepository($pdo);
        $posts = $repo->fetch([
            'type' => ['post', 'page'],
            'status' => 'all'
        ], $batchSize, $offset, 'p.id ASC');

        if (empty($posts)) {
            json_response([
                'success' => true,
                'processed' => 0,
                'next_offset' => $offset,
                'done' => true,
                'csrf_token' => $currentCsrfToken
            ]);
        }

        // Preload tags to avoid N+1 queries
        if (function_exists('grinds_attach_tags')) {
            grinds_attach_tags($posts);
        }

        $zip = new ZipArchive();
        // ★ FIX: 追加で作成する場合は CREATE 指定が必須 (空のZIPがない場合のエラー回避)
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            throw new Exception("Cannot open zip file.");
        }

        $zipPassword = function_exists('get_option') ? get_option('backup_zip_password', '') : '';
        if ($zipPassword !== '') {
            $zip->setPassword($zipPassword);
        }

        $count = 0;
        foreach ($posts as $post) {
            // Build YAML Frontmatter
            $fm = "---\n";
            $cleanTitleForLlm = html_entity_decode(strip_tags($post['title'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $cleanTitleForLlm = str_replace(["\r", "\n"], ' ', $cleanTitleForLlm);
            $fm .= "title: \"" . addcslashes($cleanTitleForLlm, '"\\') . "\"\n";
            $fm .= "slug: \"{$post['slug']}\"\n";
            $fm .= "type: \"{$post['type']}\"\n";
            $fm .= "status: \"{$post['status']}\"\n";

            $date = $post['published_at'] ?: $post['created_at'];
            if ($date) $fm .= "date: " . date('c', strtotime($date)) . "\n";

            $updated = $post['updated_at'] ?: $date;
            if ($updated) $fm .= "lastmod: " . date('c', strtotime($updated)) . "\n";

            if (!empty($post['category_name'])) {
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
                    $cleanV = str_replace(["\r", "\n"], ' ', $cleanV);
                    $fm .= "  {$k}: \"{$cleanV}\"\n";
                }
            }

            $fm .= "---\n\n";

            // Convert Content blocks into Markdown
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

            if (!empty($zipPassword) && method_exists($zip, 'setEncryptionName')) {
                $zip->setEncryptionName($filename, ZipArchive::EM_AES_256);
            }
            $count++;
        }

        $zip->close(); // Write chunk to disk

        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        json_response([
            'success' => true,
            'processed' => $count,
            'next_offset' => $offset + $count,
            'done' => ($count < $batchSize),
            'csrf_token' => $currentCsrfToken
        ]);
    } elseif ($step === 'finalize') {
        json_response([
            'success' => true,
            'url' => 'api/export_markdown.php?action=download&csrf_token=' . $currentCsrfToken,
            'csrf_token' => $currentCsrfToken
        ]);
    } else {
        throw new Exception("Unknown step.");
    }
} catch (Throwable $e) {
    // Catch Throwable explicitly to prevent fatal errors bypassing the JSON output
    json_response([
        'success' => false,
        'error' => $e->getMessage() . ' (Line: ' . $e->getLine() . ')'
    ], 500);
}
