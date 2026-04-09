<?php

/**
 * process_queue.php
 *
 * Virtual Publish Queue processor.
 * Processes background tasks (like SSG generation) asynchronously.
 */
require_once __DIR__ . '/api_bootstrap.php';

// Defense in Depth: Prevent unauthenticated/CSRF requests from causing DoS
if (!current_user_can('manage_posts')) {
    json_response(['success' => false, 'error' => 'Unauthorized'], 403);
}
check_csrf_token();

// Prevent timeout on heavy tasks
if (function_exists('grinds_set_high_load_mode')) {
    grinds_set_high_load_mode();
}

// Release session lock so other Ajax requests aren't blocked
session_write_close();

try {
    // Fetch up to 3 pending tasks to process per request
    $stmt = $pdo->query("SELECT * FROM ssg_queue WHERE status = 'pending' ORDER BY updated_at ASC LIMIT 3");
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($tasks)) {
        require_once __DIR__ . '/../../lib/front.php';
        require_once __DIR__ . '/../../lib/GrindsSSG.php';

        // Initialize SSG Config safely to bypass session dependency in API
        $ssgConfig = [
            'base_url' => get_option('ssg_base_url', ''),
            'form_endpoint' => get_option('ssg_form_endpoint', ''),
            'mode' => 'diff'
        ];
        $_SESSION['ssg_config'] = $ssgConfig;
        $ssg = new GrindsSSG($pdo, ['buildId' => 'queue']);

        $processedIds = [];

        foreach ($tasks as $task) {
            // Lock task to prevent race conditions
            $lockStmt = $pdo->prepare("UPDATE ssg_queue SET status = 'processing' WHERE id = ? AND status = 'pending'");
            $lockStmt->execute([$task['id']]);

            if ($lockStmt->rowCount() > 0) {
                try {
                    // Process the specific URL
                    $success = $ssg->buildSinglePage($task['target_url'], $task['action_type']);

                    if ($success) {
                        $pdo->prepare("UPDATE ssg_queue SET status = 'completed', updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$task['id']]);
                    } else {
                        $pdo->prepare("UPDATE ssg_queue SET status = 'failed', error_msg = 'Build failed' WHERE id = ?")->execute([$task['id']]);
                    }
                    $processedIds[] = $task['id'];
                } catch (Exception $e) {
                    $pdo->prepare("UPDATE ssg_queue SET status = 'failed', error_msg = ? WHERE id = ?")->execute([$e->getMessage(), $task['id']]);
                }
            }
        }

        $remainCount = $pdo->query("SELECT COUNT(*) FROM ssg_queue WHERE status = 'pending'")->fetchColumn();
        json_response(['success' => true, 'processed_ids' => $processedIds, 'remaining' => (int)$remainCount]);
    }

    json_response(['success' => true, 'message' => 'Queue empty', 'remaining' => 0]);
} catch (Exception $e) {
    json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
