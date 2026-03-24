<?php

/**
 * Manage event hooks
 * Provide lightweight action and filter hook system.
 */
if (!defined('GRINDS_APP')) exit;

// Global storage for hooks
$GLOBALS['grinds_hooks'] = [];
$GLOBALS['grinds_filters'] = [];
$GLOBALS['grinds_hooks_sorted'] = [];
$GLOBALS['grinds_filters_sorted'] = [];

/**
 * Register action hook.
 *
 * @param string   $tag             The name of the action to hook into (e.g., 'post_saved').
 * @param callable $callback        The function to call.
 * @param int      $priority        Execution priority (lower numbers run earlier). Default 10.
 */
if (!function_exists('add_action')) {
    function add_action($tag, $callback, $priority = 10)
    {
        $GLOBALS['grinds_hooks'][$tag][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
        $GLOBALS['grinds_hooks_sorted'][$tag] = false;
    }
}

/**
 * Execute action hook.
 *
 * @param string $tag     The name of the action to execute.
 * @param mixed  ...$args Arguments to pass to the callback functions.
 */
if (!function_exists('do_action')) {
    function do_action($tag, ...$args)
    {
        if (empty($GLOBALS['grinds_hooks'][$tag])) {
            return;
        }

        if (empty($GLOBALS['grinds_hooks_sorted'][$tag])) {
            usort($GLOBALS['grinds_hooks'][$tag], function ($a, $b) {
                return $a['priority'] <=> $b['priority'];
            });
            $GLOBALS['grinds_hooks_sorted'][$tag] = true;
        }

        // Execute callbacks
        foreach ($GLOBALS['grinds_hooks'][$tag] as $hook) {
            if (is_callable($hook['callback'])) {
                try {
                    call_user_func_array($hook['callback'], $args);
                } catch (\Throwable $e) {
                    // Log error but do not stop execution of other hooks/system
                    error_log("Grinds Hook Error [{$tag}]: " . $e->getMessage());
                }
            }
        }
    }
}

/**
 * Register filter hook.
 *
 * @param string   $tag             The name of the filter to hook into.
 * @param callable $callback        The function to call.
 * @param int      $priority        Execution priority. Default 10.
 */
if (!function_exists('add_filter')) {
    function add_filter($tag, $callback, $priority = 10)
    {
        $GLOBALS['grinds_filters'][$tag][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
        $GLOBALS['grinds_filters_sorted'][$tag] = false;
    }
}

/**
 * Execute filter hook.
 *
 * @param string $tag     The name of the filter to execute.
 * @param mixed  $value   The value to filter.
 * @param mixed  ...$args Additional arguments.
 * @return mixed          The filtered value.
 */
if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value, ...$args)
    {
        if (empty($GLOBALS['grinds_filters'][$tag])) {
            return $value;
        }

        if (empty($GLOBALS['grinds_filters_sorted'][$tag])) {
            usort($GLOBALS['grinds_filters'][$tag], function ($a, $b) {
                return $a['priority'] <=> $b['priority'];
            });
            $GLOBALS['grinds_filters_sorted'][$tag] = true;
        }

        foreach ($GLOBALS['grinds_filters'][$tag] as $hook) {
            if (is_callable($hook['callback'])) {
                try {
                    $callArgs = array_merge([$value], $args);
                    $value = call_user_func_array($hook['callback'], $callArgs);
                } catch (\Throwable $e) {
                    error_log("Grinds Filter Error [{$tag}]: " . $e->getMessage());
                }
            }
        }
        return $value;
    }
}
