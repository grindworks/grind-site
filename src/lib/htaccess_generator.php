<?php

/**
 * Generate .htaccess content.
 */
if (!defined('GRINDS_APP'))
    exit;

if (!function_exists('grinds_get_htaccess_content')) {
    /**
     * Generate .htaccess content.
     *
     * @param bool $enableOptions Whether to enable Options -Indexes and -MultiViews.
     * @return string
     */
    function grinds_get_htaccess_content($enableOptions = false, $customHeaders = [])
    {
        $indexesDirective = '# Options -Indexes';
        $optionsDirective = "<IfModule mod_negotiation.c>\n    # Options -MultiViews\n</IfModule>";

        if ($enableOptions) {
            $indexesDirective = 'Options -Indexes';
            $optionsDirective = "<IfModule mod_negotiation.c>\n    Options -MultiViews\n</IfModule>";
        }

        // Set default security headers
        $headers = [
            'X-Frame-Options' => '"SAMEORIGIN"',
            'X-Content-Type-Options' => '"nosniff"',
            'Referrer-Policy' => '"strict-origin-when-cross-origin"',
            'Permissions-Policy' => '"geolocation=(), microphone=(), camera=(), payment=()"',
            'Strict-Transport-Security' => '"max-age=31536000; includeSubDomains" env=HTTPS',
        ];

        if (!empty($customHeaders) && is_array($customHeaders)) {
            $headers = array_merge($headers, $customHeaders);
        }

        $headersBlock = '';
        foreach ($headers as $key => $val) {
            $headersBlock .= "    Header always set {$key} {$val}\n";
        }
        $headersBlock .= "    Header unset X-Powered-By";

        // Calculate base path for RewriteBase hint
        $basePath = '/';
        if (defined('BASE_URL')) {
            $parsed = parse_url(BASE_URL, PHP_URL_PATH);
            if ($parsed) $basePath = rtrim($parsed, '/') . '/';
        }

        return <<<EOT
# =================================================================
# GrindSite - Main .htaccess (Portable & Secure)
# =================================================================

# ----------------------------------------------------------------------
# 1. Basic Security Settings
# ----------------------------------------------------------------------

ServerSignature Off
{$indexesDirective}

{$optionsDirective}

# Define default index files
DirectoryIndex index.php index.html

<IfModule mod_headers.c>
    # Security Headers
{$headersBlock}
</IfModule>

# ----------------------------------------------------------------------
# 2. Access Control
# ----------------------------------------------------------------------

# Block sensitive files
<IfModule mod_authz_core.c>
<FilesMatch "^(\.git|\.env|\.vscode|composer\.json|package\.json|README\.md|LICENSE(\.txt)?|config\.php|nginx\.conf\.sample|\.htpasswd|\.nginx_confirmed)$">
    Require all denied
</FilesMatch>
</IfModule>
<IfModule !mod_authz_core.c>
<FilesMatch "^(\.git|\.env|\.vscode|composer\.json|package\.json|README\.md|LICENSE(\.txt)?|config\.php|nginx\.conf\.sample|\.htpasswd|\.nginx_confirmed)$">
    Order allow,deny
    Deny from all
</FilesMatch>
</IfModule>

# Block system data
<IfModule mod_authz_core.c>
<FilesMatch "\.(db|db-wal|db-shm|db-journal|sql|log|ini|bak|old|temp|swp|zip|tar|gz)$">
    Require all denied
</FilesMatch>
</IfModule>
<IfModule !mod_authz_core.c>
<FilesMatch "\.(db|db-wal|db-shm|db-journal|sql|log|ini|bak|old|temp|swp|zip|tar|gz)$">
    Order allow,deny
    Deny from all
</FilesMatch>
</IfModule>

# Block rescue tools & installer
# Uncomment the lines below to enable access to rescue tools temporarily.
<IfModule mod_authz_core.c>
<FilesMatch "^(tool_.*|install)\.php$">
    Require all denied
</FilesMatch>
</IfModule>
<IfModule !mod_authz_core.c>
<FilesMatch "^(tool_.*|install)\.php$">
    Order allow,deny
    Deny from all
</FilesMatch>
</IfModule>

# ----------------------------------------------------------------------
# 3. Routing (URL Rewrite) - PORTABLE MODE
# ----------------------------------------------------------------------
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Define the base path for relative rewrites
    # If you experience 404/500 errors on shared hosts (e.g., Sakura Internet)
    # or when deploying to a subdirectory, uncomment the line below.
    # RewriteBase {$basePath}

    # Block TRACE/TRACK methods
    RewriteCond %{REQUEST_METHOD} ^(TRACE|TRACK)
    RewriteRule .* - [F]

    # Virtual file rewrites
    RewriteRule ^sitemap\.xml$ ./sitemap.php [L]
    RewriteRule ^llms\.txt$ ./llms.php [L]
    RewriteRule ^llms-full\.txt$ ./llms-full.php [L]
    RewriteRule ^robots\.txt$ ./robots.php [L]
    RewriteRule ^rss\.xml$ ./rss.php [L]

    # Block direct access to system directories
    RewriteRule ^lib($|/) - [F,L]
    RewriteRule ^data($|/) - [F,L]

    # Block PHP execution in plugin/theme directories
    RewriteRule ^plugins/.*\.php$ - [F,L]
    RewriteRule ^theme/.*\.php$ - [F,L]

    # Block PHP execution in uploads directory (Security)
    RewriteRule ^assets/uploads/.*\.php$ - [F,L]
    RewriteRule ^admin/(config|skins|views)/ - [F,L]

    # Front controller — route all non-file/non-directory requests to index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ ./index.php [QSA,L]
</IfModule>

# ----------------------------------------------------------------------
# 4. Performance (Browser Cache)
# ----------------------------------------------------------------------
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresDefault "access plus 0 seconds"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType image/x-icon "access plus 1 year"
    ExpiresByType application/pdf "access plus 1 month"
    ExpiresByType font/woff2 "access plus 1 year"
    ExpiresByType font/woff "access plus 1 year"
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType text/javascript "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
</IfModule>
EOT;
    }
}
