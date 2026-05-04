<?php

/**
 * plugins.php
 *
 * Manage system plugins.
 */
require_once __DIR__ . '/bootstrap.php';

// Check permissions
if (!current_user_can('manage_settings')) {
    set_flash(_t('err_access_denied'), 'error');
    redirect('admin/index.php');
}

$pluginDir = ROOT_PATH . '/plugins';
$isWritable = is_writable($pluginDir);

// Handle POST request (Toggle Plugin State)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    try {
        // Validate CSRF token
        check_csrf_token();

        if (!$isWritable) {
            throw new Exception(_t('err_plugin_rename'));
        }

        $pluginFile = Routing::getString($_POST, 'plugin_file');
        $targetState = Routing::getString($_POST, 'target_state'); // 'enable' or 'disable'

        // Prevent directory traversal
        if (empty($pluginFile) || str_contains($pluginFile, '/') || str_contains($pluginFile, '\\') || $pluginFile === '.' || $pluginFile === '..') {
            throw new Exception(_t('err_plugin_not_found'));
        }

        // Prevent targeting non-PHP files (like .htaccess) or index.php
        if (!str_ends_with(strtolower($pluginFile), '.php') || $pluginFile === 'index.php') {
            throw new Exception(_t('err_plugin_not_found'));
        }

        $currentPath = $pluginDir . '/' . $pluginFile;
        if (!file_exists($currentPath)) {
            throw new Exception(_t('err_plugin_not_found'));
        }

        if ($targetState === 'enable') {
            if (str_starts_with($pluginFile, '_')) {
                $newName = preg_replace('/^_(?:\d{10,}_)?/', '', $pluginFile);
                $newPath = $pluginDir . '/' . $newName;

                if (file_exists($newPath)) {
                    throw new Exception(sprintf(function_exists('_t') ? _t('err_plugin_exists') : "Conflict: A plugin named '%s' already exists.", $newName));
                }

                if (@rename($currentPath, $newPath)) {
                    // Clean up old error file if it exists
                    $errorFile = $pluginDir . '/.' . $pluginFile . '.error';
                    if (file_exists($errorFile)) {
                        @unlink($errorFile);
                    }
                    set_flash(_t('msg_plugin_enabled'));
                } else {
                    throw new Exception(_t('err_plugin_rename'));
                }
            }
        } elseif ($targetState === 'disable') {
            if (!str_starts_with($pluginFile, '_')) {
                $newName = '_' . $pluginFile;
                $newPath = $pluginDir . '/' . $newName;

                if (file_exists($newPath)) {
                    throw new Exception(sprintf(function_exists('_t') ? _t('err_plugin_exists') : "Conflict: A plugin named '%s' already exists.", $newName));
                }

                if (@rename($currentPath, $newPath)) {
                    set_flash(_t('msg_plugin_disabled'));
                } else {
                    throw new Exception(_t('err_plugin_rename'));
                }
            }
        }

        // Clear page cache to reflect plugin changes on frontend
        if (function_exists('clear_page_cache')) {
            clear_page_cache();
        }

        redirect('admin/plugins.php');
    } catch (Exception $e) {
        set_flash($e->getMessage(), 'error');
        redirect('admin/plugins.php');
    }
}

// Fetch Plugins
$plugins = grinds_get_plugins();

$page_title = _t('menu_plugins');
$current_page = 'plugins';

ob_start();
require_once __DIR__ . '/layout/toast.php';
require_once __DIR__ . '/views/plugins.php';
$content = ob_get_clean();

require_once __DIR__ . '/layout/loader.php';
