<?php

/**
 * Generate Nginx configuration snippets.
 */
if (!defined('GRINDS_APP'))
    exit;

if (!function_exists('grinds_get_nginx_uploads_rules')) {
    function grinds_get_nginx_uploads_rules($relativePath = '/')
    {
        $path = rtrim($relativePath, '/');
        // Normalize prefix
        $prefix = ($path === '') ? '' : $path;

        return <<<EOT
    # Prevent PHP execution in uploads
    location ^~ {$prefix}/assets/uploads/ {
        location ~ \.php$ {
            deny all; return 404;
        }
        location ~ \.(svg|html|xml)$ {
            add_header Content-Security-Policy "script-src 'none'";
        }
    }
EOT;
    }
}

if (!function_exists('grinds_get_nginx_plugins_rules')) {
    function grinds_get_nginx_plugins_rules($relativePath = '/')
    {
        $path = rtrim($relativePath, '/');
        $prefix = ($path === '') ? '' : $path;
        $prefixRegex = preg_quote($prefix, '/');

        return <<<EOT
    # Block direct access to PHP files in Plugins
    location ~ ^{$prefixRegex}/plugins/.*\.php$ {
        deny all; return 404;
    }
EOT;
    }
}

if (!function_exists('grinds_get_nginx_security_rules')) {
    function grinds_get_nginx_security_rules($relativePath = '/')
    {
        $rel = rtrim($relativePath, '/');
        $prefix = ($rel === '') ? '' : $rel;
        $relSlash = $prefix . '/';
        $relSlashRegex = preg_quote($relSlash, '/');

        return <<<EOT
    # Block access to data directory
    location ^~ {$relSlash}data/ {
        deny all; return 404;
    }
    # Block access to system directories
    location ~ ^{$relSlashRegex}(lib|admin/config|admin/skins|admin/views|theme/.+/parts)/ {
        deny all; return 404;
    }
    # Block direct access to theme PHP files
    location ~ ^{$relSlashRegex}theme/.*\.php$ {
        deny all; return 404;
    }

    # Block access to sensitive files
    location ~ (?:^|/)(\.git|\.env|\.vscode|composer\.json|package\.json|README\.md|LICENSE(\.txt)?|config\.php|nginx\.conf\.sample|\.htpasswd|\.nginx_confirmed) {
        deny all; return 404;
    }

    # Block dot files
    location ~ /\. {
        deny all; return 404;
    }

    # Block specific extensions (DB, Logs)
    location ~ \.(db|sqlite|sqlite3|db-wal|db-shm|db-journal|sql|log|ini|bak|old|temp|swp|zip|tar|gz)$ {
        deny all; return 404;
    }
EOT;
    }
}

if (!function_exists('grinds_get_nginx_config')) {
    function grinds_get_nginx_config($serverName, $rootPath, $relativePath, $fastCgiPass)
    {
        $rel = rtrim($relativePath, '/');
        $prefix = ($rel === '') ? '' : $rel;
        $relSlash = $prefix . '/';
        $relSlashRegex = preg_quote($relSlash, '/');

        $uploadsRules = grinds_get_nginx_uploads_rules($relativePath);
        $pluginsRules = grinds_get_nginx_plugins_rules($relativePath);
        $securityRules = grinds_get_nginx_security_rules($relativePath);

        return <<<EOT
server {
    listen 80;
    server_name {$serverName};
    root "{$rootPath}";
    index index.php index.html;
    client_max_body_size 50M;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Routing
    location {$relSlash} {
        try_files \$uri \$uri/ {$relSlash}index.php\$is_args\$args;
    }

{$securityRules}

{$pluginsRules}

    # [SECURITY] Prevent access to rescue tools & installer
    location ~ ^{$relSlashRegex}tools/.*\.php$ {
        deny all; return 404;
    }
    location ~ ^{$relSlashRegex}(tool_.*|install)\.php$ {
        deny all; return 404;
    }

{$uploadsRules}

    # PHP-FPM Configuration
    location ~ \.php$ {
        try_files \$uri =404;
        include snippets/fastcgi-php.conf;
        fastcgi_pass {$fastCgiPass};
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOT;
    }
}
