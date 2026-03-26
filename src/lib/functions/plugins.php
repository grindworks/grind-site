<?php

/**
 * Load user plugins
 * Include active plugin files from the plugins directory.
 */
if (!defined('GRINDS_APP')) exit;

(function () {
    $pluginDir = ROOT_PATH . '/plugins';
    $currentPlugin = null;

    // Register shutdown handler to quarantine fatal crashing plugins
    register_shutdown_function(function () use (&$currentPlugin, $pluginDir) {
        if ($currentPlugin) {
            $error = error_get_last();
            // Trigger quarantine only on fatal script termination
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
                // Skip quarantine in debug mode for better developer experience
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    if (class_exists('GrindsLogger')) {
                        GrindsLogger::log("Plugin crash detected -> " . basename($currentPlugin) . " (Quarantine skipped due to DEBUG_MODE)", 'CRITICAL');
                    } else {
                        error_log("Plugin crash detected -> " . basename($currentPlugin) . " (Quarantine skipped due to DEBUG_MODE)");
                    }
                    return;
                }

                $quarantinedName = $pluginDir . '/_' . basename($currentPlugin);
                $renamed = @rename($currentPlugin, $quarantinedName);

                if (class_exists('GrindsLogger')) {
                    GrindsLogger::log("System Recovery: Plugin automatically disabled due to a fatal crash -> " . basename($currentPlugin), 'CRITICAL');
                    if (!$renamed) {
                        GrindsLogger::log("System Recovery Failed: Could not rename plugin. Please manually rename or delete '" . basename($currentPlugin) . "' via FTP.", 'CRITICAL');
                    }
                } else {
                    error_log("GrindSite Recovery: Plugin disabled -> " . basename($currentPlugin));
                    if (!$renamed) {
                        error_log("GrindSite Recovery Failed: Could not rename plugin. Please manually rename or delete '" . basename($currentPlugin) . "' via FTP.");
                    }
                }
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
                    require_once $pluginFile;
                } catch (\Throwable $e) {
                    // Skip quarantine in debug mode
                    if (defined('DEBUG_MODE') && DEBUG_MODE) {
                        if (class_exists('GrindsLogger')) {
                            GrindsLogger::log('Plugin load error [' . basename($pluginFile) . '] (Quarantine skipped due to DEBUG_MODE): ' . $e->getMessage(), 'ERROR');
                        } else {
                            error_log('Plugin load error [' . basename($pluginFile) . '] (Quarantine skipped due to DEBUG_MODE): ' . $e->getMessage());
                        }
                    } else {
                        // Quarantine plugin immediately on catchable errors
                        $quarantinedName = dirname($pluginFile) . '/_' . basename($pluginFile);
                        $renamed = @rename($pluginFile, $quarantinedName);

                        if (class_exists('GrindsLogger')) {
                            GrindsLogger::log('Plugin load error & quarantined [' . basename($pluginFile) . ']: ' . $e->getMessage(), 'ERROR');
                            if (!$renamed) {
                                GrindsLogger::log("Quarantine Failed: Could not rename plugin. Please manually rename or delete '" . basename($pluginFile) . "' via FTP.", 'ERROR');
                            }
                        } else {
                            error_log('Plugin load error & quarantined [' . basename($pluginFile) . ']: ' . $e->getMessage());
                            if (!$renamed) {
                                error_log("Quarantine Failed: Could not rename plugin. Please manually rename or delete '" . basename($pluginFile) . "' via FTP.");
                            }
                        }
                    }
                }

                // Clear active plugin marker after successful load
                $currentPlugin = null;
            }
        }
    }
})();
