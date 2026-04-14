<?php

/**
 * ssg_process.php
 *
 * Handle Static Site Generation (SSG) tasks.
 * Serves as a thin API controller, delegating core logic to GrindsSSG library.
 */
// Define SSG flag
define('GRINDS_IS_SSG', true);

require_once __DIR__ . '/api_bootstrap.php';

try {
    require_once __DIR__ . '/../../lib/front.php';
    require_once __DIR__ . '/../../lib/GrindsSSG.php';

    // Ensure PDO available
    /** @var PDO $pdo */
    $pdo = App::db();
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Check permissions
    if (!function_exists('current_user_can') || !current_user_can('manage_tools')) {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        if ($action === 'download') {
            http_response_code(403);
            exit('Permission denied');
        }
        throw new Exception('Permission denied', 403);
    }

    if (function_exists('grinds_set_high_load_mode')) {
        grinds_set_high_load_mode();
    }

    // Handle download
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    if ($action === 'download') {
        $csrf_token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        if (!validate_csrf_token($csrf_token)) {
            grinds_clean_output_buffer();
            http_response_code(403);
            exit('Invalid CSRF Token');
        }

        $buildId = $_POST['build_id'] ?? $_GET['build_id'] ?? '';
        $zipFile = ROOT_PATH . '/data/tmp/static_site.zip';

        if (!empty($buildId) && preg_match('/^[a-zA-Z0-9_]+$/', $buildId)) {
            $zipFile = ROOT_PATH . '/data/tmp/static_site_' . $buildId . '.zip';
        }

        if (file_exists($zipFile)) {
            // Clear stat cache to ensure accurate filesize reading for newly generated files
            clearstatcache(true, $zipFile);

            // God-Rank Polish: Release session lock before long-running file download to prevent tab freeze
            session_write_close();

            grinds_clean_output_buffer();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="static_site_' . date('Ymd_His') . '.zip"');
            header('Content-Length: ' . filesize($zipFile));
            header('Pragma: no-cache');
            header('Expires: 0');

            if (function_exists('set_time_limit')) @set_time_limit(0);
            @readfile($zipFile);
            grinds_force_unlink($zipFile);
            exit;
        } else {
            grinds_clean_output_buffer();
            http_response_code(404);
            exit(function_exists('_t') ? _t('err_file_not_found') : 'File not found');
        }
    }

    // Handle cleanup of temporary files if user closes modal without downloading
    if ($action === 'cleanup') {
        $csrf_token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        if (!validate_csrf_token($csrf_token)) {
            grinds_clean_output_buffer();
            http_response_code(403);
            exit(json_encode(['success' => false, 'error' => 'Invalid CSRF Token']));
        }

        $buildId = $_POST['build_id'] ?? $_GET['build_id'] ?? '';
        if (!empty($buildId) && preg_match('/^[a-zA-Z0-9_]+$/', $buildId)) {
            $zipFile = ROOT_PATH . '/data/tmp/static_site_' . $buildId . '.zip';
            $exportDir = ROOT_PATH . '/data/tmp/static_export_' . $buildId;

            if (file_exists($zipFile)) @unlink($zipFile);
            if (is_dir($exportDir) && function_exists('grinds_delete_tree')) {
                @grinds_delete_tree($exportDir);
            }
        }
        grinds_clean_output_buffer();
        exit(json_encode(['success' => true]));
    }

    // Handle API Actions
    check_csrf_token();

    $inputData = json_decode($_POST['data'] ?? '{}', true);
    if (!is_array($inputData)) {
        $inputData = [];
    }
    $step = $_POST['step'] ?? '';

    $ssg = new GrindsSSG($pdo, array_merge($inputData, ['step' => $step]));
    $response = $ssg->run($step, $inputData);

    json_response($response);
} catch (Throwable $e) {
    // Log error details
    error_log("SSG API Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    error_log("Stack Trace: " . $e->getTraceAsString());

    // Return HTTP 200 to properly convey error messages to the frontend
    $code = (int)$e->getCode() === 403 ? 403 : 200;

    $errorData = [
        'success' => false,
        'error' => $e->getMessage(),
        'type' => get_class($e),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ];

    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        $errorData['trace'] = $e->getTraceAsString();
    }

    json_response($errorData, $code);
}
