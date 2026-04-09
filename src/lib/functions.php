<?php

/**
 * Load function modules.
 */
if (!defined('GRINDS_APP')) exit;

if (!defined('ROOT_PATH')) {
  // Define root path
  define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)));
}

// Load utilities (needed for URL logic)
require_once __DIR__ . '/functions/utils.php';

// Load URL logic
require_once __DIR__ . '/bootstrap_url.php';

// Load core classes
require_once __DIR__ . '/Enums.php';
require_once __DIR__ . '/App.php';
require_once __DIR__ . '/BlockRenderer.php';
require_once __DIR__ . '/file_manager.php';
require_once __DIR__ . '/GeneratorCacheTrait.php';
require_once __DIR__ . '/Routing.php';

// Define constants
if (!defined('PLACEHOLDER_IMG')) {
  define('PLACEHOLDER_IMG', 'data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PSIwIDAgODAwIDUwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJtMCAwaDgwMHY1MDBoLThiMDB6IiBmaWxsPSIjMmQyZDJkIi8+PHBhdGggZD0ibTI4NS42IDE2Ny4xdjE2NS43aDIyOC43di0xNjUuN3ptMjE3LjMgMTAuNXY4NC45bC02MS41LTU1LjctNjEuMyA1Ny4yLTMxLjMtMjkuMi01MS43IDQ4LjR2LTEwNS42em0tMjA1LjggMTIwLjUgNTEuOS00OC40IDc3LjUgNzIuM2gtMTI5LjR6bTE0NS42IDI0LjEtNTQuNC01MC44IDUzLjMtNDkuNyA2MS41IDU1Ljd2NDQuOHoiIGZpbGw9IiM1NjU2NTYiLz48L3N2Zz4=');
}

/**
 * Convert database media path to absolute URL for frontend display.
 */
if (!function_exists('get_media_url')) {
  function get_media_url($path)
  {
    if (empty($path)) {
      return '';
    }
    return resolve_url(grinds_url_to_view($path));
  }
}

// Load core libraries
require_once __DIR__ . '/info.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/i18n.php';

// Load function modules
require_once __DIR__ . '/functions/hooks.php';

require_once __DIR__ . '/functions/security.php';
require_once __DIR__ . '/functions/options.php';
require_once __DIR__ . '/functions/template.php';
require_once __DIR__ . '/functions/system.php';
require_once __DIR__ . '/functions/posts.php';
require_once __DIR__ . '/functions/users.php';
require_once __DIR__ . '/functions/theme.php';

// Load user plugins
require_once __DIR__ . '/functions/plugins.php';
