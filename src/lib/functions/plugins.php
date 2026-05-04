<?php

/**
 * Load user plugins
 * Include active plugin files from the plugins directory.
 */
if (!defined('GRINDS_APP')) exit;

/**
 * Parse plugin metadata from file header.
 *
 * @param string $plugin_file
 * @return array
 */
if (!function_exists('grinds_get_plugin_data')) {
    function grinds_get_plugin_data(string $plugin_file): array
    {
        $all_headers = [
            'Name' => '',
            'Description' => '',
            'Version' => '',
            'Author' => ''
        ];

        $file_data = @file_get_contents($plugin_file, false, null, 0, 8192);
        if ($file_data === false) {
            return $all_headers;
        }

        $file_data = str_replace(["\r\n", "\r"], "\n", $file_data);
        $lang = function_exists('get_option') ? get_option('site_lang', 'en') : 'en';

        // 1. Standard headers
        foreach ($all_headers as $field => &$value) {
            if (preg_match('/^[ \t\/*#@]*(?:Plugin )?' . preg_quote($field, '/') . '(?:\s*\(' . $lang . '\))?\s*:(.*)$/mi', $file_data, $match)) {
                $value = trim(preg_replace("/\s*(?:\*\/|\?>).*/", '', $match[1]));
            }
        }
        unset($value);

        // 2. Native DocBlock Fallback (GrindSite format)
        if (empty($all_headers['Description']) || empty($all_headers['Name'])) {
            if (preg_match('/\/\*\*(.*?)\*\//s', $file_data, $docBlockMatch)) {
                $cleanText = preg_replace('/^[ \t]*\*[ \t]?/m', '', $docBlockMatch[1]);

                $targetTag = ($lang === 'ja') ? '\[Japanese\]' : '\[English\]';

                // Try to extract language specific block, fallback to English
                if (
                    preg_match('/' . $targetTag . '\s*\n(.*?)(?=\n\[[A-Za-z]+\]|$)/s', $cleanText, $matches) ||
                    preg_match('/\[English\]\s*\n(.*?)(?=\n\[[A-Za-z]+\]|$)/s', $cleanText, $matches)
                ) {
                    $lines = array_filter(array_map('trim', explode("\n", trim($matches[1]))));
                    if (!empty($lines)) {
                        if (empty($all_headers['Name'])) {
                            $all_headers['Name'] = array_shift($lines);
                        }
                        if (empty($all_headers['Description'])) {
                            $all_headers['Description'] = implode("\n", $lines);
                        }
                    }
                }
            }
        }

        return $all_headers;
    }
}

/**
 * Get a list of all available plugins.
 *
 * @return array
 */
if (!function_exists('grinds_get_plugins')) {
    function grinds_get_plugins(): array
    {
        $pluginDir = ROOT_PATH . '/plugins';
        $plugins = [];
        if (is_dir($pluginDir)) {
            // Auto-cleanup orphaned error log files
            $errorFiles = glob($pluginDir . '/.*.error');
            if (is_array($errorFiles)) {
                foreach ($errorFiles as $errFile) {
                    $pluginFileName = preg_replace('/^\.(.+)\.error$/', '$1', basename($errFile));
                    if (!file_exists($pluginDir . '/' . $pluginFileName)) {
                        @unlink($errFile);
                    }
                }
            }

            $files = glob($pluginDir . '/*.php');
            if (is_array($files)) {
                foreach ($files as $file) {
                    $filename = basename($file);
                    // Skip system files
                    if ($filename === 'index.php') continue;

                    $isActive = !str_starts_with($filename, '_');
                    $data = grinds_get_plugin_data($file);

                    if (empty($data['Name'])) {
                        // Clean up filename for display if no header is found
                        $cleanFilename = preg_replace('/^_(?:\d{10,}_)?/', '', $filename);
                        $data['Name'] = ucwords(str_replace(['_', '-'], ' ', str_replace('.php', '', $cleanFilename)));
                    }

                    $plugins[] = [
                        'file' => $filename,
                        'path' => $file,
                        'is_active' => $isActive,
                        'name' => $data['Name'],
                        'description' => $data['Description'],
                        'version' => $data['Version'],
                        'author' => $data['Author']
                    ];
                }
            }
        }
        // Sort active first, then alphabetically
        usort($plugins, function ($a, $b) {
            if ($a['is_active'] === $b['is_active']) {
                return strcasecmp($a['name'], $b['name']);
            }
            return $a['is_active'] ? -1 : 1;
        });
        return $plugins;
    }
}

(function () {
    $pluginDir = ROOT_PATH . '/plugins';
    $currentPlugin = null;

    // Helper closure to handle plugin quarantine (DRY)
    $quarantinePlugin = function ($pluginFile, $errorMsg, $errorLine) use ($pluginDir) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $logMsg = "Plugin error detected -> " . basename($pluginFile) . " (Quarantine skipped due to DEBUG_MODE): " . $errorMsg;
            if (class_exists('GrindsLogger')) GrindsLogger::log($logMsg, 'CRITICAL');
            else error_log($logMsg);
            return;
        }

        $baseName = basename($pluginFile);
        $quarantinedName = $pluginDir . '/_' . $baseName;
        if (file_exists($quarantinedName)) {
            $quarantinedName = $pluginDir . '/_' . time() . '_' . $baseName;
        }
        $renamed = @rename($pluginFile, $quarantinedName);

        if ($renamed) {
            $errorInfo = [
                'message' => $errorMsg,
                'file'    => $baseName,
                'line'    => $errorLine,
                'time'    => time()
            ];
            $errorFile = $pluginDir . '/.' . basename($quarantinedName) . '.error';
            @file_put_contents($errorFile, json_encode($errorInfo, JSON_UNESCAPED_UNICODE));

            if (function_exists('set_flash')) {
                set_flash(function_exists('_t') ? _t('msg_plugin_quarantined_toast') : 'Plugin disabled due to an error.', 'error');
            }
        }

        $logMsg = "System Recovery: Plugin automatically disabled due to an error -> " . $baseName;
        if (class_exists('GrindsLogger')) {
            GrindsLogger::log($logMsg, 'CRITICAL');
            if (!$renamed) GrindsLogger::log("Quarantine Failed for '" . $baseName . "'. Please manually rename via FTP.", 'CRITICAL');
        } else {
            error_log($logMsg);
            if (!$renamed) error_log("Quarantine Failed for '" . $baseName . "'. Please manually rename via FTP.");
        }
    };

    // Register shutdown handler for fatal crashing plugins
    register_shutdown_function(function () use (&$currentPlugin, $quarantinePlugin) {
        if ($currentPlugin) {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
                $quarantinePlugin($currentPlugin, $error['message'], $error['line']);
            }
        }
    });

    // Load plugins
    if (is_dir($pluginDir)) {
        $pluginFiles = glob($pluginDir . '/*.php');

        if (is_array($pluginFiles)) {
            // Sort explicitly to prevent OS-dependent loading order
            sort($pluginFiles);

            foreach ($pluginFiles as $pluginFile) {
                // Skip disabled plugins starting with underscore
                if (str_starts_with(basename($pluginFile), '_')) continue;

                // Record currently processing plugin for shutdown handler
                $currentPlugin = $pluginFile;

                try {
                    // Prevent "garbage characters" (BOM, whitespace) from breaking headers/API
                    ob_start();
                    require_once $pluginFile;
                    $garbageOutput = ob_get_clean();

                    if (trim($garbageOutput) !== '' && defined('DEBUG_MODE') && DEBUG_MODE) {
                        error_log("Plugin Output Warning: '" . basename($pluginFile) . "' generated unexpected output. This can break API responses.");
                    }
                } catch (\Throwable $e) {
                    ob_end_clean();
                    $quarantinePlugin($pluginFile, $e->getMessage(), $e->getLine());
                }

                // Clear active plugin marker after successful load
                $currentPlugin = null;
            }
        }
    }
})();
