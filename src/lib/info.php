<?php

/**
 * Define core system constants and metadata.
 */
// Define CMS metadata
if (!defined('CMS_NAME'))
    define('CMS_NAME', 'GrindSite');
if (!defined('SITE_NAME')) {
    define('SITE_NAME', CMS_NAME);
}
if (!defined('CMS_VERSION'))
    define('CMS_VERSION', '1.2.0');

// Define limits
if (!defined('MAX_PREVIEW_SIZE'))
    define('MAX_PREVIEW_SIZE', 25 * 1024 * 1024);
if (!defined('MAX_FETCH_SIZE'))
    define('MAX_FETCH_SIZE', 2 * 1024 * 1024);
if (!defined('MAX_IMAGE_PIXELS'))
    define('MAX_IMAGE_PIXELS', 25000000);

// Define SSG settings
if (!defined('GRINDS_SSG_SEARCH_LIMIT'))
    define('GRINDS_SSG_SEARCH_LIMIT', 500);

// Define search config
if (!defined('SEARCH_CONTENT_FALLBACK')) {
    define('SEARCH_CONTENT_FALLBACK', false);
}
if (!defined('SEARCH_INDEX_LIMIT')) {
    define('SEARCH_INDEX_LIMIT', 10000);
}

// Define session timeout
if (!defined('DEFAULT_SESSION_TIMEOUT'))
    define('DEFAULT_SESSION_TIMEOUT', 1800);

// Define default favicon (SVG Data URI)
if (!defined('DEFAULT_FAVICON_URI')) {
    define('DEFAULT_FAVICON_URI', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzMzMyIvPjx0ZXh0IHg9IjUwIiB5PSI1MCIgZm9udC1zaXplPSI1MCIgZmlsbD0iI2ZmZiIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4xZW0iPkc8L3RleHQ+PC9zdmc+');
}
