<?php

/**
 * Manage post content
 * Handle post creation, updates, deletion, and retrieval.
 */
if (!defined('GRINDS_APP'))
    exit;

/**
 * Internal helper to collect media URLs from posts for cleanup.
 *
 * @param PDO $pdo
 * @param array|null $postIds If null, collects from all trashed posts.
 * @return array List of media URLs/paths.
 */
function _grinds_collect_media_from_posts(PDO $pdo, ?array $postIds = null): array
{
    $candidates = [];

    $processRow = function ($row) use (&$candidates) {
        if (!empty($row['thumbnail'])) $candidates[] = $row['thumbnail'];
        if (!empty($row['hero_image'])) $candidates[] = $row['hero_image'];
        if (!empty($row['hero_settings'])) {
            $hs = json_decode($row['hero_settings'], true);
            if (is_array($hs) && !empty($hs['mobile_image'])) {
                $candidates[] = $hs['mobile_image'];
            }
        }
        if (!empty($row['content']) && function_exists('grinds_extract_urls')) {
            $urls = grinds_extract_urls($row['content']);
            foreach ($urls as $u) {
                $candidates[] = $u;
            }
        }
    };

    try {
        if ($postIds !== null) {
            if (empty($postIds)) {
                return [];
            }
            // Chunk IDs to avoid SQLite placeholder limit (default 999)
            $chunks = array_chunk($postIds, 900);
            foreach ($chunks as $chunk) {
                $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                $stmt = $pdo->prepare("SELECT thumbnail, hero_image, hero_settings, content FROM posts WHERE id IN ($placeholders)");
                $stmt->execute($chunk);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $processRow($row);
                }
            }
        } else {
            $stmt = $pdo->query("SELECT thumbnail, hero_image, hero_settings, content FROM posts WHERE deleted_at IS NOT NULL");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $processRow($row);
            }
        }
    } catch (Exception $e) {
    }
    return array_unique($candidates);
}

/**
 * Empty trash.
 *
 * @param PDO $pdo
 * @return int Number of items deleted.
 * @throws Exception
 */
function grinds_empty_trash(PDO $pdo): int
{
    // Collect file candidates before deletion
    $candidates = _grinds_collect_media_from_posts($pdo);

    // Count items to be deleted.
    $stmt = $pdo->query("SELECT id FROM posts WHERE deleted_at IS NOT NULL");
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $count = count($ids);

    if ($count === 0) {
        return 0;
    }

    // Fire pre-delete hooks while records still exist.
    foreach ($ids as $id) {
        do_action('grinds_before_post_delete', $id);
    }

    $pdo->beginTransaction();
    try {
        // Delete posts permanently.
        $pdo->exec("DELETE FROM posts WHERE deleted_at IS NOT NULL");
        $pdo->commit();
    } catch (Exception $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        throw $e;
    }

    // Fire post-delete hooks AFTER commit
    foreach ($ids as $id) {
        do_action('grinds_post_deleted', $id);
    }

    // Cleanup unused files
    grinds_cleanup_unused_media_files($pdo, $candidates);

    do_action('grinds_trash_emptied', $count);

    return $count;
}

/**
 * Process bulk actions for posts.
 *
 * @param PDO $pdo
 * @param array $data Request data (usually $_POST)
 * @return array Result ['count' => int, 'message' => string]
 * @throws Exception
 */
function grinds_process_bulk_actions(PDO $pdo, array $data): array
{
    $bulkAction = $data['bulk_action'] ?? '';

    try {
        $targetIds = grinds_get_bulk_target_ids($data);
    } catch (Exception $e) {
        $targetIds = [];
    }

    if (empty($targetIds) || empty($bulkAction)) {
        throw new Exception(_t('no_items_selected_or_invalid_action'));
    }

    $count = 0;
    $now = date('Y-m-d H:i:s');
    $message = '';
    $filesToCleanup = [];
    $trashedIds = [];
    $restoredIds = [];
    $deletedIds = [];

    // Fire pre-delete hooks before transaction
    if ($bulkAction === 'delete') {
        foreach ($targetIds as $tid) {
            do_action('grinds_before_post_delete', $tid);
        }
    }

    $pdo->beginTransaction();

    try {
        switch ($bulkAction) {
            case 'trash':
                // Rename slug to avoid conflicts.
                $stmtGet = $pdo->prepare("SELECT slug FROM posts WHERE id = ?");
                $stmtUpdate = $pdo->prepare("UPDATE posts SET deleted_at = ?, slug = ? WHERE id = ?");
                $stmtUpdateSimple = $pdo->prepare("UPDATE posts SET deleted_at = ? WHERE id = ?");

                foreach ($targetIds as $tid) {
                    $stmtGet->execute([$tid]);
                    $slug = $stmtGet->fetchColumn();
                    if ($slug) {
                        // Rename slug if needed.
                        if (!str_contains($slug, '__trashed')) {
                            $randSuffix = random_int(100, 999);
                            $newSlug = $slug . '__trashed-' . time() . '-' . $randSuffix;
                            $stmtUpdate->execute([$now, $newSlug, $tid]);
                        } else {
                            // Update timestamp.
                            $stmtUpdateSimple->execute([$now, $tid]);
                        }
                        $count++;
                        $trashedIds[] = $tid;
                    }
                }
                $message = _t('msg_deleted_count', $count);
                break;

            case 'restore':
                // Restore slug with conflict resolution.
                $stmtGet = $pdo->prepare("SELECT slug FROM posts WHERE id = ?");
                $stmtCheck = $pdo->prepare("SELECT count(*) FROM posts WHERE slug = ? AND id != ?");
                $stmtRestore = $pdo->prepare("UPDATE posts SET deleted_at = NULL, slug = ? WHERE id = ?");

                foreach ($targetIds as $tid) {
                    $stmtGet->execute([$tid]);
                    $currentSlug = $stmtGet->fetchColumn();

                    if ($currentSlug) {
                        // Remove trash suffix.
                        $baseSlug = preg_replace('/__trashed-\d+-\d+$/', '', $currentSlug);
                        $restoreSlug = $baseSlug;

                        // Check for duplicates.
                        $stmtCheck->execute([$restoreSlug, $tid]);
                        if ($stmtCheck->fetchColumn() > 0) {
                            // Simple collision fix for restore
                            $restoreSlug = $baseSlug . '-' . time();
                        }

                        // Retry logic for restore slug collision
                        $retryCount = 0;
                        $saved = false;

                        while (!$saved && $retryCount < 5) {
                            $pdo->exec("SAVEPOINT grinds_restore_retry");
                            try {
                                $stmtRestore->execute([$restoreSlug, $tid]);
                                $pdo->exec("RELEASE SAVEPOINT grinds_restore_retry");
                                $saved = true;
                            } catch (PDOException $e) {
                                $pdo->exec("ROLLBACK TO SAVEPOINT grinds_restore_retry");
                                if ($e->getCode() == 23000 || $e->getCode() == 19) {
                                    $restoreSlug = $baseSlug . '-' . time() . '-' . mt_rand(100, 999);
                                    $baseSleep = 10000; // 10ms
                                    $jitter = mt_rand(0, 20000);
                                    $sleepTime = ($baseSleep * (1 << $retryCount)) + $jitter;
                                    usleep($sleepTime);
                                    $retryCount++;
                                } else {
                                    throw $e;
                                }
                            }
                        }

                        if ($saved) {
                            $count++;
                            $restoredIds[] = $tid;
                        }
                    }
                }
                $message = _t('msg_restored_count', $count);
                break;

            case 'delete':
                // Collect file candidates before deletion
                $filesToCleanup = _grinds_collect_media_from_posts($pdo, $targetIds);

                $stmtPost = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                foreach ($targetIds as $tid) {
                    $stmtPost->execute([$tid]);
                    $count++;
                    $deletedIds[] = $tid;
                }
                $message = _t('msg_perm_deleted_count', $count);
                break;

            case 'publish':
                $stmt = $pdo->prepare("UPDATE posts SET status = 'published', published_at = COALESCE(published_at, ?), updated_at = ?, version = version + 1 WHERE id = ?");
                foreach ($targetIds as $tid) {
                    $stmt->execute([$now, $now, $tid]);
                    $count++;
                }
                $message = _t('msg_published_count', $count);
                break;

            case 'draft':
                $stmt = $pdo->prepare("UPDATE posts SET status = 'draft', updated_at = ?, version = version + 1 WHERE id = ?");
                foreach ($targetIds as $tid) {
                    $stmt->execute([$now, $tid]);
                    $count++;
                }
                $message = _t('msg_drafted_count', $count);
                break;

            case 'change_category':
                $newCatId = isset($data['new_category_id']) ? (int)$data['new_category_id'] : 0;
                if ($newCatId <= 0) {
                    throw new Exception(_t('invalid_category_id'));
                }
                $stmtCatCheck = $pdo->prepare("SELECT id, name FROM categories WHERE id = ?");
                $stmtCatCheck->execute([$newCatId]);
                $catInfo = $stmtCatCheck->fetch(PDO::FETCH_ASSOC);

                if (!$catInfo) {
                    throw new Exception(_t('selected_category_does_not_exist'));
                }
                $newCatName = $catInfo['name'] ?? '';

                $stmtGet = $pdo->prepare("SELECT title, description, content FROM posts WHERE id = ?");
                $stmtTags = $pdo->prepare("SELECT t.name FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");
                $stmt = $pdo->prepare("UPDATE posts SET category_id = ?, search_text = ?, updated_at = ?, version = version + 1 WHERE id = ?");

                foreach ($targetIds as $tid) {
                    $stmtGet->execute([$tid]);
                    $post = $stmtGet->fetch(PDO::FETCH_ASSOC);

                    if (!$post) {
                        continue;
                    }

                    $stmtTags->execute([$tid]);
                    $tags = $stmtTags->fetchAll(PDO::FETCH_COLUMN);
                    $searchText = grinds_generate_search_text($post['title'], $post['description'], $post['content'], $newCatName, $tags);
                    $stmt->execute([$newCatId, $searchText, $now, $tid]);
                    $count++;
                }
                $message = _t('msg_saved') . " ($count items updated)";
                break;

            case 'duplicate':
                $stmtSrc = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
                $stmtTagNames = $pdo->prepare("SELECT t.name FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id = ?");

                foreach ($targetIds as $sourceId) {
                    $stmtSrc->execute([$sourceId]);
                    $source = $stmtSrc->fetch(PDO::FETCH_ASSOC);

                    if ($source) {
                        // Fetch tag names for the source post
                        $stmtTagNames->execute([$sourceId]);
                        $tagNames = $stmtTagNames->fetchAll(PDO::FETCH_COLUMN);
                        $tagsString = implode(',', $tagNames);

                        // Prepare data for grinds_save_post
                        $newData = [
                            'title' => $source['title'] . ' (Copy)',
                            'content' => $source['content'],
                            'description' => $source['description'],
                            'status' => 'draft',
                            'type' => $source['type'],
                            'category_id' => $source['category_id'],
                            'slug' => preg_replace('/__trashed-\d+-\d+$/', '', $source['slug']) . '-copy-' . time(),
                            'page_theme' => $source['page_theme'],
                            'published_at' => null,
                            'toc_title' => $source['toc_title'],
                            'current_thumbnail' => $source['thumbnail'],
                            'current_hero_image' => $source['hero_image'],
                            'tags' => $tagsString,
                        ];

                        // Handle boolean fields
                        $boolFields = ['is_noindex', 'is_nofollow', 'is_noarchive', 'is_hide_rss', 'is_hide_llms', 'show_toc', 'show_category', 'show_date', 'show_share_buttons'];
                        foreach ($boolFields as $f) {
                            if (!empty($source[$f])) {
                                $newData[$f] = 1;
                            }
                        }

                        // Unpack hero settings
                        $hs = json_decode($source['hero_settings'] ?? '{}', true);
                        if (is_array($hs)) {
                            $newData['hero_title'] = $hs['title'] ?? '';
                            $newData['hero_subtext'] = $hs['subtext'] ?? '';
                            $newData['hero_layout'] = $hs['layout'] ?? 'standard';
                            if (!empty($hs['overlay']))
                                $newData['hero_overlay'] = 1;
                            if (!empty($hs['fixed_bg']))
                                $newData['hero_fixed_bg'] = 1;
                            $newData['current_hero_image_mobile'] = $hs['mobile_image'] ?? '';
                            $newData['hero_buttons_json'] = $hs['buttons'] ?? [];
                        }

                        grinds_save_post($pdo, $newData, [], 'new');
                        $count++;
                    }
                }
                $message = "$count items duplicated.";
                break;

            default:
                throw new Exception(_t('err_unknown_action') . ": " . htmlspecialchars($bulkAction));
        }

        $pdo->commit();

        // Fire hooks after commit
        foreach ($trashedIds as $id) {
            do_action('grinds_post_trashed', $id);
        }
        foreach ($restoredIds as $id) {
            do_action('grinds_post_restored', $id);
        }
        foreach ($deletedIds as $id) {
            do_action('grinds_post_deleted', $id);
        }

        // Cleanup unused files (only for delete action)
        grinds_cleanup_unused_media_files($pdo, $filesToCleanup);

        return ['count' => $count, 'message' => $message];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Build hero settings array.
 *
 * @param array $data Input data (e.g. $_POST)
 * @param string $mobileImage Processed mobile image path
 * @return array
 * @throws Exception
 */
function grinds_build_hero_settings($data, $mobileImage = ''): array
{
    $hero_buttons = [];
    if (!empty($data['hero_buttons_json'])) {
        $json_raw = $data['hero_buttons_json'];
        // Handle both string (JSON) and array input
        $decoded = null;
        if (is_array($json_raw)) {
            $decoded = $json_raw;
        } else {
            $decoded = json_decode($json_raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception(_t('err_json_decode', json_last_error_msg()));
            }
        }

        if (is_array($decoded)) {
            foreach ($decoded as &$btn) {
                if (isset($btn['url'])) {
                    $btn['url'] = trim((string)$btn['url']);
                    $btn['url'] = Routing::convertToDbUrl($btn['url']);
                }
            }
            unset($btn);
            $hero_buttons = $decoded;
        }
    }

    return [
        'title' => $data['hero_title'] ?? '',
        'subtext' => $data['hero_subtext'] ?? '',
        'layout' => $data['hero_layout'] ?? 'standard',
        'buttons' => $hero_buttons,
        'overlay' => isset($data['hero_overlay']) ? 1 : 0,
        'mobile_image' => $mobileImage,
        'fixed_bg' => isset($data['hero_fixed_bg']) ? 1 : 0,
        'seo_author' => $data['seo_author'] ?? ''
    ];
}

/**
 * Prepare post data from request.
 *
 * @param array $data Request data (e.g. $_POST)
 * @return array Prepared data array
 */
function grinds_prepare_post_data_from_request(array $data): array
{
    $title = trim((string)($data['title'] ?? ''));
    $content = $data['content'] ?? '';
    $content = is_string($content) ? $content : '';
    $description = trim((string)($data['description'] ?? ''));
    $status = $data['status'] ?? 'draft';
    $type = $data['type'] ?? 'post';
    if (!in_array($type, ['post', 'page', 'template'])) {
        $type = 'post';
    }

    if ($type === 'template') {
        $status = 'private';
    }

    $categoryId = !empty($data['category_id']) ? (int)$data['category_id'] : 0;
    $slug = trim((string)($data['slug'] ?? ''));
    $pageTheme = $data['page_theme'] ?? '';

    // Decode Base64 content if present.
    if (!empty($data['content_is_base64']) && !empty($content)) {
        $content = grinds_decode_post_content($content);
    }

    $publishedAtRaw = isset($data['published_at']) && is_string($data['published_at']) ? trim($data['published_at']) : '';

    if ($publishedAtRaw === '') {
        $publishedAt = ($status === 'published') ? date('Y-m-d H:i:s') : null;
    } else {
        try {
            $date = new DateTime($publishedAtRaw);
            $publishedAt = $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $publishedAt = ($status === 'published') ? date('Y-m-d H:i:s') : null;
        }
    }

    return [
        'title' => $title,
        'content' => $content,
        'description' => $description,
        'status' => $status,
        'type' => $type,
        'category_id' => $categoryId,
        'slug' => $slug,
        'page_theme' => $pageTheme,
        'published_at' => $publishedAt,
        'is_noindex' => isset($data['is_noindex']) ? 1 : 0,
        'is_nofollow' => isset($data['is_nofollow']) ? 1 : 0,
        'is_noarchive' => isset($data['is_noarchive']) ? 1 : 0,
        'is_hide_rss' => isset($data['is_hide_rss']) ? 1 : 0,
        'is_hide_llms' => isset($data['is_hide_llms']) ? 1 : 0,
        'show_toc' => isset($data['show_toc']) ? 1 : 0,
        'toc_title' => $data['toc_title'] ?? 'Contents',
        'show_category' => isset($data['show_category']) ? 1 : 0,
        'show_date' => isset($data['show_date']) ? 1 : 0,
        'show_share_buttons' => isset($data['show_share_buttons']) ? 1 : 0,
    ];
}

/**
 * Create or update post.
 *
 * @param PDO $pdo
 * @param array $data Post data
 * @param array $files File data (usually $_FILES)
 * @param string $action 'new' or 'edit'
 * @param int|null $id Post ID for edit
 * @return array Result ['id' => int, 'slug' => string, 'message' => string]
 * @throws Exception
 */
function grinds_save_post(PDO $pdo, array $data, array $files, string $action, ?int $id = null): array
{
    $postData = grinds_prepare_post_data_from_request($data);

    $rawContent = trim((string)$postData['content']);

    // Optimize: Data pipeline for JSON content to prevent redundant encode/decode
    $firstChar = mb_substr($rawContent, 0, 1, 'UTF-8');
    $lastChar  = mb_substr($rawContent, -1, 1, 'UTF-8');

    if (($firstChar === '{' && $lastChar === '}') || ($firstChar === '[' && $lastChar === ']')) {
        $contentArray = json_decode($rawContent, true);

        if (is_array($contentArray)) {
            // 1. URL Replacement
            if (method_exists('Routing', 'convertArrayToDbUrl')) {
                $contentArray = Routing::convertArrayToDbUrl($contentArray);
            }

            // 2. Security Sanitization
            if (!current_user_can('unfiltered_html')) {
                if (function_exists('grinds_sanitize_post_content_array')) {
                    $contentArray = grinds_sanitize_post_content_array($contentArray);
                } else {
                    grinds_validate_content_security(json_encode($contentArray, JSON_UNESCAPED_UNICODE));
                }
            }

            // 3. Re-encode once
            $postData['content'] = json_encode($contentArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE);
        } else {
            // Abort save for invalid JSON to prevent data loss / corruption
            throw new Exception(function_exists('_t') ? _t('err_invalid_json') : "Invalid Content JSON. Save aborted to prevent data loss.");
        }
    } else {
        // Fallback for non-JSON strings (Classic Editor)
        $postData['content'] = Routing::convertToDbUrl($rawContent);
        if (!current_user_can('unfiltered_html')) {
            $postData['content'] = function_exists('grinds_sanitize_post_content') ? grinds_sanitize_post_content($postData['content']) : $postData['content'];
        }
    }

    // Check for potential broken links in HTML blocks (root-relative paths in subdirectory)
    $pathWarning = '';
    if (defined('BASE_URL')) {
        $basePath = parse_url(BASE_URL, PHP_URL_PATH);
        if ($basePath && $basePath !== '/' && $basePath !== '') {
            if (preg_match('/(?:href|src|action)\s*=\s*(["\'])(\\\\?\/)(?!\/)[^"\']+\1/i', $postData['content'])) {
                $pathWarning = ' (' . _t('msg_html_block_relative_path') . ')';
            }
        }
    }

    // Sanitize slug to prevent directory traversal and SSG conflicts.
    $postData['slug'] = sanitize_slug($postData['slug']);
    if (empty($postData['slug'])) {
        $postData['slug'] = generate_slug($postData['title']);
    }

    $reserved_slugs = grinds_get_reserved_slugs();

    if (in_array(strtolower($postData['slug']), $reserved_slugs)) {
        throw new Exception(sprintf(_t('err_slug_reserved_msg'), $postData['slug']));
    }

    // Check for conflict with physical directories (SSG safety)
    if (defined('ROOT_PATH') && is_dir(ROOT_PATH . '/' . $postData['slug'])) {
        throw new Exception(sprintf(_t('err_slug_reserved_msg'), $postData['slug']));
    }

    $startedTransaction = false;

    try {
        $tagsInput = $data['tags'] ?? '';
        $tagNames = grinds_parse_tag_string($tagsInput);

        $thumbnail = grinds_process_image_upload($pdo, 'thumbnail', $data['current_thumbnail'] ?? '', [
            'post_data' => $data,
            'files_data' => $files,
            'throw_error' => true
        ]);
        $thumbnail = Routing::convertToDbUrl($thumbnail);

        $hero_image = grinds_process_image_upload($pdo, 'hero_image', $data['current_hero_image'] ?? '', [
            'post_data' => $data,
            'files_data' => $files,
            'throw_error' => true
        ]);
        $hero_image = Routing::convertToDbUrl($hero_image);

        // --- Mobile Hero Image Handling ---
        $hero_image_mobile = grinds_process_image_upload($pdo, 'hero_image_mobile', $data['current_hero_image_mobile'] ?? '', [
            'post_data' => $data,
            'files_data' => $files,
            'throw_error' => true
        ]);
        $hero_image_mobile = Routing::convertToDbUrl($hero_image_mobile);

        $hero_settings = grinds_build_hero_settings($data, $hero_image_mobile);
        $hero_settings_json = json_encode($hero_settings, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE | JSON_THROW_ON_ERROR);

        $category_name = '';
        if (!empty($postData['category_id'])) {
            $stmtCat = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
            $stmtCat->execute([$postData['category_id']]);
            $category_name = $stmtCat->fetchColumn() ?: '';
        }
        $search_text = grinds_generate_search_text($postData['title'], $postData['description'], $postData['content'], (string)$category_name, $tagNames);

        $current_time = date('Y-m-d H:i:s');

        $params = [
            ':title' => $postData['title'],
            ':content' => $postData['content'],
            ':search_text' => $search_text,
            ':description' => $postData['description'],
            ':category_id' => $postData['category_id'],
            ':status' => $postData['status'],
            ':type' => $postData['type'],
            ':thumbnail' => $thumbnail,
            ':hero_image' => $hero_image,
            ':hero_settings' => $hero_settings_json,
            ':page_theme' => $postData['page_theme'],
            ':published_at' => $postData['published_at'],
            ':is_noindex' => $postData['is_noindex'],
            ':is_nofollow' => $postData['is_nofollow'],
            ':is_noarchive' => $postData['is_noarchive'],
            ':is_hide_rss' => $postData['is_hide_rss'],
            ':is_hide_llms' => $postData['is_hide_llms'],
            ':show_toc' => $postData['show_toc'],
            ':toc_title' => $postData['toc_title'],
            ':show_category' => $postData['show_category'],
            ':show_date' => $postData['show_date'],
            ':show_share_buttons' => $postData['show_share_buttons'],
            ':updated_at' => $current_time,
        ];

        $postId = null;
        $newVersion = 1;

        $idCheck = ($action === 'edit' && $id) ? $id : 0;
        $finalSlug = grinds_get_unique_slug($pdo, 'posts', $postData['slug'], $idCheck);

        $saved = false;
        $retryCount = 0;
        $maxRetries = 10;

        $inTransaction = $pdo->inTransaction();

        while (!$saved && $retryCount < $maxRetries) {
            if (!$inTransaction) {
                $pdo->beginTransaction();
                $startedTransaction = true;
            } else {
                $pdo->exec("SAVEPOINT grinds_save_post_retry");
            }
            try {
                $tagIds = grinds_get_or_create_tags($pdo, $tagNames);

                $params[':slug'] = $finalSlug;

                if ($action === 'edit' && $id) {
                    // Optimistic Locking: Check for concurrent updates
                    // Also fetch full content for revision history
                    $stmtTs = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
                    $stmtTs->execute([$id]);
                    $current = $stmtTs->fetch(PDO::FETCH_ASSOC);

                    if (!$current) {
                        throw new Exception(_t('err_conflict'));
                    }
                    $dbUpdated = $current['updated_at'] ?? '';
                    $dbVersion = $current['version'] ?? 0;

                    // Calculate new version to save
                    $newVersion = $dbVersion + 1;

                    $formUpdated = $data['original_updated_at'] ?? '';
                    $formVersion = $data['original_version'] ?? 0;

                    // If timestamps mismatch and force_overwrite is not set
                    $isConflict = false;
                    if ($dbVersion && $formVersion) {
                        if ((int)$dbVersion !== (int)$formVersion) {
                            $isConflict = true;
                        }
                    } elseif ($dbUpdated && $formUpdated && $dbUpdated !== $formUpdated) {
                        $isConflict = true;
                    }

                    if ($isConflict && empty($data['force_overwrite'])) {
                        throw new Exception(_t('err_conflict'));
                    }

                    $sql = "UPDATE posts SET
                          title = :title,
                          slug = :slug,
                          content = :content,
                          search_text = :search_text,
                          description = :description,
                          category_id = :category_id,
                          status = :status,
                          type = :type,
                          thumbnail = :thumbnail,
                          hero_image = :hero_image,
                          hero_settings = :hero_settings,
                          page_theme = :page_theme,
                          published_at = :published_at,
                          is_noindex = :is_noindex,
                          is_nofollow = :is_nofollow,
                          is_noarchive = :is_noarchive,
                          is_hide_rss = :is_hide_rss,
                          is_hide_llms = :is_hide_llms,
                          show_toc = :show_toc,
                          toc_title = :toc_title,
                          show_category = :show_category,
                          show_date = :show_date,
                          show_share_buttons = :show_share_buttons,
                          updated_at = :updated_at,
                          version = version + 1
                        WHERE id = :id";

                    $params[':id'] = $id;

                    if (empty($data['force_overwrite'])) {
                        if ($formVersion) {
                            $sql .= " AND version = :expected_version";
                            $params[':expected_version'] = $formVersion;
                        } elseif ($formUpdated) {
                            $sql .= " AND updated_at = :expected_updated";
                            $params[':expected_updated'] = $formUpdated;
                        }
                    }

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    if ($stmt->rowCount() === 0) {
                        throw new Exception(_t('err_conflict'));
                    }
                    $postId = $id;
                } else {
                    $sql = "INSERT INTO posts (
                          title, slug, content, search_text, description, category_id, status, type, thumbnail,
                          hero_image, hero_settings, page_theme,
                          published_at,
                          is_noindex, is_nofollow, is_noarchive, is_hide_rss, is_hide_llms, show_toc, toc_title,
                          show_category, show_date, show_share_buttons,
                          created_at, updated_at, version
                        ) VALUES (
                          :title, :slug, :content, :search_text, :description, :category_id, :status, :type, :thumbnail,
                          :hero_image, :hero_settings, :page_theme,
                          :published_at,
                          :is_noindex, :is_nofollow, :is_noarchive, :is_hide_rss, :is_hide_llms, :show_toc, :toc_title,
                          :show_category, :show_date, :show_share_buttons,
                          :created_at, :updated_at, 1
                        )";
                    $params[':created_at'] = $current_time;
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $postId = $pdo->lastInsertId();
                }

                $pdo->prepare("DELETE FROM post_tags WHERE post_id = ?")->execute([$postId]);
                if (!empty($tagIds)) {
                    $stmtLink = $pdo->prepare("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                    foreach ($tagIds as $tId) {
                        $stmtLink->execute([$postId, $tId]);
                    }
                }

                if (!$inTransaction) {
                    $pdo->commit();
                } else {
                    $pdo->exec("RELEASE SAVEPOINT grinds_save_post_retry");
                }
                $saved = true;
            } catch (PDOException $e) {
                if (!$inTransaction) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                } else {
                    $pdo->exec("ROLLBACK TO SAVEPOINT grinds_save_post_retry");
                }

                if ($e->getCode() == 23000 || $e->getCode() == 19) {
                    // Collision detected (Race condition).
                    // Fallback to random suffix to avoid further collisions in high concurrency.
                    $baseSlug = preg_replace('/-\d+$/', '', $finalSlug);
                    $finalSlug = $baseSlug . '-' . random_int(1000, 99999);
                    $baseSleep = 10000; // 10ms
                    $jitter = mt_rand(0, 20000);
                    $sleepTime = ($baseSleep * (1 << $retryCount)) + $jitter;
                    usleep($sleepTime);
                    $retryCount++;
                } else {
                    throw $e;
                }
            }
        }

        if (!$saved) {
            throw new Exception(_t('err_slug_conflict'));
        }
    } catch (Exception $e) {
        // Rollback only if this function started the main transaction
        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        } elseif (isset($inTransaction) && $inTransaction && $pdo->inTransaction()) {
            // If inside an external transaction, rollback to the savepoint to keep the parent transaction safe
            try {
                $pdo->exec("ROLLBACK TO SAVEPOINT grinds_save_post_retry");
            } catch (Exception $ex) {
                // Ignore if savepoint does not exist
            }
        }
        throw $e;
    }

    do_action('grinds_post_saved', $postId, $data);

    return [
        'id' => $postId,
        'slug' => $finalSlug,
        'version' => $newVersion,
        'updated_at' => $current_time,
        'message' => _t('msg_saved') . $pathWarning
    ];
}

/**
 * Reassign posts to category.
 *
 * @param PDO $pdo
 * @param int $fromCatId
 * @param int $toCatId
 * @return int Number of posts updated.
 * @throws Exception
 */
function grinds_reassign_category_posts(PDO $pdo, int $fromCatId, int $toCatId): int
{
    // Verify destination category exists
    $stmtCheck = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmtCheck->execute([$toCatId]);
    $newCatName = $stmtCheck->fetchColumn();
    if ($newCatName === false) {
        throw new Exception(_t('selected_category_does_not_exist'));
    }

    // Get posts to update
    $stmtGet = $pdo->prepare("SELECT id, title, description, content FROM posts WHERE category_id = ?");
    $stmtGet->execute([$fromCatId]);
    $posts = $stmtGet->fetchAll(PDO::FETCH_ASSOC);

    if (empty($posts)) {
        return 0;
    }

    // Preload tags to avoid N+1 query
    if (function_exists('grinds_attach_tags')) {
        grinds_attach_tags($posts);
    }

    $now = date('Y-m-d H:i:s');
    $stmtUpdate = $pdo->prepare("UPDATE posts SET category_id = ?, search_text = ?, updated_at = ?, version = version + 1 WHERE id = ?");

    $count = 0;
    foreach ($posts as $post) {
        $tags = isset($post['tags']) ? array_column($post['tags'], 'name') : [];

        $searchText = grinds_generate_search_text($post['title'], $post['description'], $post['content'], $newCatName, $tags);

        $stmtUpdate->execute([$toCatId, $searchText, $now, $post['id']]);
        $count++;
    }

    return $count;
}

/**
 * Rebuild post search index.
 *
 * @param PDO $pdo
 * @param int|array|null $post_id Specific post ID (or IDs) to rebuild.
 * @param int $limit Limit for batch processing.
 * @param int $offset Offset for batch processing.
 * @return int Number of processed items.
 */
function grinds_rebuild_post_index(PDO $pdo, $post_id = null, $limit = 0, $offset = 0): int
{
    // Optimization: If array is provided, process in chunks
    if (is_array($post_id)) {
        $totalProcessed = 0;
        $chunks = array_chunk($post_id, 50);
        foreach ($chunks as $chunk) {
            $totalProcessed += _grinds_rebuild_index_chunk($pdo, $chunk, count($chunk), 0);
        }
        return $totalProcessed;
    }

    // Optimization: If specific ID is provided, process immediately without looping
    if ($post_id !== null) {
        return _grinds_rebuild_index_chunk($pdo, $post_id, 1, 0);
    }

    // If limit is specified, process just that chunk (legacy behavior preserved for API calls)
    if ($limit > 0) {
        return _grinds_rebuild_index_chunk($pdo, $post_id, $limit, $offset);
    }

    // If limit is 0 (process all), loop through chunks to avoid memory exhaustion
    $chunkSize = 50;
    $totalProcessed = 0;

    while (true) {
        $count = _grinds_rebuild_index_chunk($pdo, $post_id, $chunkSize, $offset);
        $totalProcessed += $count;
        $offset += $chunkSize;

        if ($count < $chunkSize)
            break;
    }

    return $totalProcessed;
}

/**
 * Internal helper to process a chunk of posts for index rebuilding.
 */
function _grinds_rebuild_index_chunk(PDO $pdo, $post_id, $limit, $offset): int
{
    $repo    = new PostRepository($pdo);
    $filters = ['type' => ['post', 'page']];
    if ($post_id !== null) {
        $filters['ids'] = is_array($post_id) ? $post_id : [$post_id];
    }

    $posts = $repo->fetch($filters, $limit, $offset, 'p.id ASC', 'p.id, p.title, p.description, p.content, p.search_text');

    if (empty($posts)) {
        return 0;
    }

    if (function_exists('grinds_attach_tags')) {
        grinds_attach_tags($posts);
    }

    // Prepare data (Outside transaction for performance)
    $updates = [];
    foreach ($posts as $post) {
        $tags = isset($post['tags']) ? array_column($post['tags'], 'name') : [];
        $text = grinds_generate_search_text($post['title'], $post['description'], $post['content'], $post['category_name'], $tags);

        // Force update to trigger FTS rebuild even if content is same
        $updates[] = ['id' => $post['id'], 'text' => $text];
    }

    if (empty($updates)) {
        return count($posts);
    }

    // Execute updates (Inside transaction securely)
    $inTransaction = $pdo->inTransaction();
    if (!$inTransaction) {
        $pdo->beginTransaction();
    }
    try {
        $stmtUpdate = $pdo->prepare("UPDATE posts SET search_text = ? WHERE id = ?");
        foreach ($updates as $u) {
            $stmtUpdate->execute([$u['text'], $u['id']]);
        }
        if (!$inTransaction) {
            $pdo->commit();
        }
    } catch (Exception $e) {
        if (!$inTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return count($posts);
}

/**
 * Generate SQL WHERE clause for post search.
 *
 * @param string $query Search keywords.
 * @param array $params Reference to parameters array.
 * @param string $alias Table alias (e.g. 'p').
 * @return string SQL condition.
 */
function grinds_get_post_search_condition($query, &$params, $alias = ''): string
{
    $prefix = $alias ? $alias . '.' : '';
    return grinds_build_search_query($query, function ($word) use (&$params, $prefix) {
        $escapedWord = grinds_escape_like($word);

        $params[] = "%{$escapedWord}%";
        $params[] = "%{$escapedWord}%";

        $bigramWord = function_exists('grinds_get_bigram') ? grinds_get_bigram($word) : $word;
        $searchTextConditions = [];

        if ($bigramWord !== '') {
            $tokens = explode(' ', $bigramWord);
            foreach ($tokens as $token) {
                if (trim($token) !== '') {
                    $params[] = "%" . grinds_escape_like($token) . "%";
                    $searchTextConditions[] = "{$prefix}search_text LIKE ? ESCAPE '\\'";
                }
            }
        }

        if (empty($searchTextConditions)) {
            $params[] = "%{$escapedWord}%";
            $searchTextConditions[] = "{$prefix}search_text LIKE ? ESCAPE '\\'";
        }

        $searchTextSql = implode(' AND ', $searchTextConditions);

        return "({$prefix}title LIKE ? ESCAPE '\\' OR {$prefix}slug LIKE ? ESCAPE '\\' OR ({$searchTextSql}))";
    });
}

/**
 * Prepare search query components.
 *
 * @param PDO $pdo
 * @param string $query
 * @return array {
 *   'where' => string,
 *   'params' => array,
 *   'join' => string,
 *   'order' => string,
 *   'use_fts' => bool
 * }
 */
function grinds_prepare_search_query(PDO $pdo, $query): array
{
    static $useFts = null;

    if ($useFts === null) {
        $useFts = false;
        try {
            if ($pdo->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='posts_fts'")->fetchColumn()) {
                $useFts = true;
            }
        } catch (Exception $e) {
        }
    }

    if ($useFts) {
        // Fallback to legacy LIKE search if any keyword is a single multibyte character.
        // FTS5 with Bigram cannot match single CJK characters reliably.
        $rawKeywords = grinds_split_search_keywords($query);
        foreach ($rawKeywords as $word) {
            if (mb_strlen($word, 'UTF-8') === 1 && strlen($word) > 1) {
                $useFts = false;
                break;
            }
        }
    }

    if ($useFts) {
        // Normalize query using Bigram logic
        if (function_exists('grinds_get_bigram')) {
            $bg = grinds_get_bigram($query);
            $keywords = ($bg !== '') ? explode(' ', $bg) : [];
        } else {
            $keywords = grinds_split_search_keywords($query);
        }
        $limitedKeywords = array_slice($keywords, 0, 10);
        $ftsTerms = [];
        foreach ($limitedKeywords as $word) {
            $word = str_replace('"', '""', $word);
            // Use prefix search only for ASCII words to maintain partial match behavior.
            // For multibyte (N-gram tokens), use exact match to avoid performance penalty of PREFIX scan.
            if (strlen($word) === mb_strlen($word, 'UTF-8')) {
                $ftsTerms[] = '"' . $word . '"*';
            } else {
                $ftsTerms[] = '"' . $word . '"';
            }
        }

        if (empty($ftsTerms)) {
            return [
                'where' => "1 = 0",
                'params' => [],
                'join' => "",
                'order' => "",
                'use_fts' => true
            ];
        }

        $ftsQuery = implode(' AND ', $ftsTerms);

        return [
            'where' => "posts_fts MATCH ?",
            'params' => [$ftsQuery],
            'join' => "JOIN posts_fts fts ON p.id = fts.rowid",
            'order' => "fts.rank",
            'use_fts' => true
        ];
    } else {
        // Legacy LIKE Search
        $params = [];
        $where = '';

        // Check for content fallback option
        $useContentFallback = defined('SEARCH_CONTENT_FALLBACK') && SEARCH_CONTENT_FALLBACK;

        $rawKeywords = grinds_split_search_keywords($query);

        if ($useContentFallback) {
            if (!empty($rawKeywords)) {
                $limitedKeywords = array_slice($rawKeywords, 0, 10);
                $where = grinds_build_search_query($limitedKeywords, function ($word) use (&$params) {
                    $escapedWord = grinds_escape_like($word);
                    $params[] = "%{$escapedWord}%";
                    $params[] = "%{$escapedWord}%";
                    $params[] = "%{$escapedWord}%";
                    return "(p.title LIKE ? ESCAPE '\\' OR p.description LIKE ? ESCAPE '\\' OR p.content LIKE ? ESCAPE '\\')";
                });
            }
        } else {
            // Full content search using bigrammed search_text (Default)
            $where = grinds_get_post_search_condition($query, $params, 'p');
        }

        return [
            'where' => $where,
            'params' => $params,
            'join' => "",
            'order' => "",
            'use_fts' => false
        ];
    }
}

/**
 * Generate search text for post.
 *
 * @param string $title
 * @param string $description
 * @param string $content
 * @param string $category_name
 * @param array $tags
 * @return string
 */
function grinds_generate_search_text($title, $description = '', $content = '', $category_name = '', $tags = []): string
{
    $body = grinds_extract_text_from_content($content);

    $cleanTitle = html_entity_decode(strip_tags((string)$title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $cleanDesc = html_entity_decode(strip_tags((string)$description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $cleanCat = html_entity_decode(strip_tags((string)$category_name), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $tagStr = is_array($tags) ? implode(' ', $tags) : '';

    $raw = "{$cleanTitle}\n{$cleanCat}\n{$tagStr}\n{$cleanDesc}\n{$body}";

    return function_exists('grinds_get_bigram') ? grinds_get_bigram($raw) : $raw;
}

/**
 * Attach tags to a list of posts to avoid N+1 queries.
 */
function grinds_attach_tags(&$posts): void
{
    $pdo = App::db();

    if (!$pdo || empty($posts)) return;

    $ids = array_column($posts, 'id');
    if (empty($ids)) return;

    $ids = array_unique($ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT pt.post_id, t.id, t.name, t.slug FROM tags t JOIN post_tags pt ON t.id = pt.tag_id WHERE pt.post_id IN ($placeholders)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);
    $tagsMap = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

    foreach ($posts as &$post) {
        $post['tags'] = $tagsMap[$post['id']] ?? [];
    }
    unset($post);
}

/**
 * Cleanup unused media files from a list of candidates.
 *
 * @param PDO $pdo
 * @param array $paths List of file paths/URLs to check and delete if unused.
 * @return void
 */
function grinds_cleanup_unused_media_files(PDO $pdo, array $paths): void
{
    if (empty($paths) || !class_exists('FileManager')) {
        return;
    }

    $cleanPaths = [];
    foreach ($paths as $path) {
        $cleanPath = str_replace('{{CMS_URL}}', '', $path);
        $cleanPath = ltrim($cleanPath, '/');

        if (str_starts_with($cleanPath, 'assets/uploads/')) {
            $cleanPaths[] = $cleanPath;
        }
    }

    $cleanPaths = array_unique($cleanPaths);
    if (!empty($cleanPaths)) {
        $usageMap = FileManager::getBulkFileUsage($pdo, $cleanPaths);

        foreach ($cleanPaths as $cleanPath) {
            if (!isset($usageMap[$cleanPath])) {
                if (FileManager::delete($cleanPath)) {
                    $pdo->prepare("DELETE FROM media WHERE filepath = ?")->execute([$cleanPath]);
                }
            }
        }
    }
}

/**
 * Manage post database operations.
 */
if (!class_exists('PostRepository')) {
    readonly class PostRepository
    {
        public function __construct(private PDO $pdo) {}

        /**
         * Build query components based on filters.
         */
        protected function buildQuery(array $filters)
        {
            $where = [];
            $params = [];
            $joins = [];

            // 1. Status & Deletion
            $status = $filters['status'] ?? null;
            $now = date('Y-m-d H:i:s');

            if ($status === 'any') {
                // Include everything (active + trash)
            } elseif ($status === 'trash') {
                $where[] = "p.deleted_at IS NOT NULL";
            } else {
                // Default: Active only
                $where[] = "p.deleted_at IS NULL";

                if ($status && !in_array($status, ['all', 'any', 'trash'])) {
                    if ($status === 'published') {
                        $where[] = "p.status = 'published'";
                        if (!isset($filters['check_schedule']) || $filters['check_schedule']) {
                            $where[] = "(p.published_at <= ? OR p.published_at IS NULL)";
                            $params[] = $now;
                        }
                    } elseif ($status === 'reserved') {
                        $where[] = "p.status = 'published' AND p.published_at > ?";
                        $params[] = $now;
                    } elseif ($status === 'draft') {
                        $where[] = "p.status = 'draft'";
                    } else {
                        $where[] = "p.status = ?";
                        $params[] = $status;
                    }
                }
            }

            // 2. Post Type
            if (isset($filters['type'])) {
                if (is_array($filters['type'])) {
                    $placeholders = implode(',', array_fill(0, count($filters['type']), '?'));
                    $where[] = "p.type IN ($placeholders)";
                    $params = array_merge($params, $filters['type']);
                } elseif ($filters['type'] === 'post') {
                    $where[] = "p.type = 'post'";
                } else {
                    $where[] = "p.type = ?";
                    $params[] = $filters['type'];
                }
            } elseif (!isset($filters['ignore_type']) && empty($filters['ids'])) {
                // Default exclude templates if not specified
                $where[] = "p.type != 'template'";
            }

            // 4. Category
            if (!empty($filters['category_id'])) {
                $where[] = "p.category_id = ?";
                $params[] = $filters['category_id'];
            }
            // Category Slug (Subquery)
            if (!empty($filters['category_slug'])) {
                $where[] = "p.category_id IN (SELECT id FROM categories WHERE slug = ?)";
                $params[] = $filters['category_slug'];
            }

            // 5. Tag
            if (!empty($filters['tag_id'])) {
                $joins['post_tags'] = "JOIN post_tags pt ON p.id = pt.post_id";
                $where[] = "pt.tag_id = ?";
                $params[] = $filters['tag_id'];
            }
            // Tag Slug (Subquery for performance)
            if (!empty($filters['tag_slug'])) {
                $where[] = "p.id IN (SELECT pt.post_id FROM post_tags pt JOIN tags t ON pt.tag_id = t.id WHERE t.slug = ?)";
                $params[] = $filters['tag_slug'];
            }

            // 6. Search
            if (!empty($filters['search'])) {
                if (isset($filters['prepared_search_query'])) {
                    $sq = $filters['prepared_search_query'];
                } else {
                    $sq = grinds_prepare_search_query($this->pdo, $filters['search']);
                }
                if ($sq['where']) {
                    $where[] = $sq['where'];
                    $params = array_merge($params, $sq['params']);
                    if (!empty($sq['join'])) {
                        $joins['search'] = $sq['join'];
                    }
                }
            }

            // 7. Specific ID/Slug
            if (!empty($filters['ids'])) {
                $ids = (array)$filters['ids'];
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $where[] = "p.id IN ($placeholders)";
                $params = array_merge($params, $ids);
            }

            if (!empty($filters['slug'])) {
                $where[] = "p.slug = ?";
                $params[] = $filters['slug'];
            }

            // 8. No Index (for sitemap etc)
            if (isset($filters['is_noindex'])) {
                $where[] = "p.is_noindex = ?";
                $params[] = (int)$filters['is_noindex'];
            }

            // 9. Updated After (for SSG diff)
            if (!empty($filters['updated_after'])) {
                $where[] = "p.updated_at > ?";
                $params[] = $filters['updated_after'];
            }

            // 10. Created Before (for stats)
            if (!empty($filters['created_before'])) {
                $where[] = "p.created_at < ?";
                $params[] = $filters['created_before'];
            }

            // 11. Created After (for stats)
            if (!empty($filters['created_after'])) {
                $where[] = "p.created_at >= ?";
                $params[] = $filters['created_after'];
            }

            return [
                'where' => implode(' AND ', $where),
                'params' => $params,
                'joins' => implode(' ', $joins)
            ];
        }

        public function count(array $filters = [])
        {
            $q = $this->buildQuery($filters);
            $distinct = !empty($q['joins']) ? 'DISTINCT p.id' : '*';
            $sql = "SELECT COUNT({$distinct}) FROM posts p {$q['joins']} WHERE {$q['where']}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($q['params']);
            return (int)$stmt->fetchColumn();
        }

        public function paginate(array $filters = [], $page = 1, $limit = 10, $orderBy = 'p.published_at DESC', $select = 'p.*', $includeCategory = true)
        {
            $total = $this->count($filters);
            $paginator = new Paginator($total, $limit, $page);

            $q = $this->buildQuery($filters);
            $sql = $this->getBaseSql($select, $q, $orderBy, $limit, $paginator->getOffset(), $includeCategory);

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($q['params']);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (function_exists('grinds_attach_tags')) {
                grinds_attach_tags($posts);
            }

            return ['posts' => $posts, 'paginator' => $paginator, 'total' => $total];
        }

        public function fetch(array $filters = [], $limit = 0, $offset = 0, $orderBy = 'p.published_at DESC', $select = 'p.*', $includeCategory = true)
        {
            $q = $this->buildQuery($filters);
            $sql = $this->getBaseSql($select, $q, $orderBy, $limit, $offset, $includeCategory);

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($q['params']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        /**
         * Generate base SQL for fetching posts.
         *
         * @param string $select
         * @param array $q Result from buildQuery
         * @param string $orderBy
         * @param int $limit
         * @param int $offset
         * @param bool $includeCategory
         * @return string
         */
        protected function getBaseSql($select, $q, $orderBy, $limit = 0, $offset = 0, $includeCategory = true)
        {
            $catSelect = '';
            $catJoin = '';
            if ($includeCategory) {
                $catSelect = ", c.name as category_name, c.slug as category_slug, c.category_theme";
                $catJoin = "LEFT JOIN categories c ON p.category_id = c.id";
            }

            $groupBy = !empty($q['joins']) ? 'GROUP BY p.id' : '';

            $sql = "SELECT $select{$catSelect}
                    FROM posts p
                    {$catJoin}
                    {$q['joins']}
                    WHERE {$q['where']}
                    {$groupBy}
                    ORDER BY $orderBy";

            if ($limit > 0) {
                $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            }

            return $sql;
        }

        public function getLatestPostTimestamp(array $filters = [])
        {
            $q = $this->buildQuery($filters);
            $sql = "SELECT MAX(p.updated_at), MAX(p.published_at) FROM posts p {$q['joins']} WHERE {$q['where']}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($q['params']);
            $row = $stmt->fetch(PDO::FETCH_NUM);

            if (!$row) return null;

            $updated = $row[0] ?? null;
            $published = $row[1] ?? null;

            if ($updated === null && $published === null) return null;
            if ($updated === null) return $published;
            if ($published === null) return $updated;

            return ($updated > $published) ? $updated : $published;
        }

        public function findForSitemap()
        {
            $filters = [
                'status' => 'published',
                'is_noindex' => 0,
            ];
            $q = $this->buildQuery($filters);
            $sql = "SELECT p.slug, p.updated_at, p.type, p.thumbnail
                    FROM posts p
                    {$q['joins']}
                    WHERE {$q['where']}
                    ORDER BY p.updated_at DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($q['params']);
            return $stmt;
        }

        public function findCategoriesForSitemap()
        {
            $filters = [
                'status' => 'published',
                'type' => 'post',
                'is_noindex' => 0,
            ];
            $q = $this->buildQuery($filters);

            $sql = "SELECT c.slug, MAX(p.updated_at) AS last_updated
                    FROM categories c
                    JOIN posts p ON c.id = p.category_id
                    {$q['joins']}
                    WHERE {$q['where']}
                    GROUP BY c.slug
                    ORDER BY c.sort_order ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($q['params']);
            return $stmt;
        }

        public function findTagsForSitemap()
        {
            $filters = ['status' => 'published', 'type' => 'post', 'is_noindex' => 0];
            $q = $this->buildQuery($filters);
            $sql = "SELECT t.slug, MAX(p.updated_at) AS last_updated FROM tags t JOIN post_tags pt ON t.id = pt.tag_id JOIN posts p ON pt.post_id = p.id {$q['joins']} WHERE {$q['where']} GROUP BY t.slug ORDER BY t.name ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($q['params']);
            return $stmt;
        }

        public function findSlugs(array $filters = [])
        {
            $q = $this->buildQuery($filters);
            $sql = "SELECT p.slug FROM posts p {$q['joins']} WHERE {$q['where']}";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($q['params']);
            return $stmt;
        }

        public function getDailyPostCounts($days = 30)
        {
            $start = date('Y-m-d', strtotime("-" . ($days - 1) . " days"));

            $qDaily = $this->buildQuery(['status' => 'all', 'created_after' => $start]);

            // SQLite specific date function
            $sql = "SELECT strftime('%Y-%m-%d', p.created_at) as post_date, COUNT(p.id) as count
                    FROM posts p
                    {$qDaily['joins']}
                    WHERE {$qDaily['where']}
                    GROUP BY post_date";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($qDaily['params']);
            $daily = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            // Baseline count before the period
            $totalBefore = $this->count(['status' => 'all', 'created_before' => $start]);

            return ['daily' => $daily, 'total_before' => $totalBefore];
        }

        public function getPostCountsForCategories(array $categoryIds, array $extraFilters = [])
        {
            if (empty($categoryIds)) {
                return [];
            }
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $filters = array_merge(['status' => 'all', 'type' => 'post'], $extraFilters);
            $q = $this->buildQuery($filters);

            $sql = "SELECT category_id, COUNT(id) as count
                    FROM posts p
                    {$q['joins']}
                    WHERE category_id IN ($placeholders) AND {$q['where']}
                    GROUP BY category_id";

            $params = array_merge($categoryIds, $q['params']);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        public function getPostCountsForTags(array $tagIds, array $extraFilters = [])
        {
            if (empty($tagIds)) {
                return [];
            }
            $placeholders = implode(',', array_fill(0, count($tagIds), '?'));
            $filters = array_merge(['status' => 'all', 'type' => 'post'], $extraFilters);
            $q = $this->buildQuery($filters);

            $sql = "SELECT pt.tag_id, COUNT(p.id) as count
                    FROM posts p
                    JOIN post_tags pt ON p.id = pt.post_id
                    {$q['joins']}
                    WHERE pt.tag_id IN ($placeholders) AND {$q['where']}
                    GROUP BY pt.tag_id";

            $params = array_merge($tagIds, $q['params']);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }
    }
}
