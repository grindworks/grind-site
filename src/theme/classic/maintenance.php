<?php

if (!defined('GRINDS_APP')) exit;

/**
 * maintenance.php
 * Render maintenance page.
 */
$defaultLang = 'en';
if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && stripos($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'ja') !== false) {
    $defaultLang = 'ja';
}
$lang = get_option('site_lang', $defaultLang);
$defaultTitle = 'System Maintenance';
$defaultMsg = 'We are currently updating the system. Please check back in a few minutes.';

$title = get_option('maintenance_title') ?: (function_exists('theme_t') ? theme_t('System Maintenance') : $defaultTitle);
$message = get_option('maintenance_message') ?: (function_exists('theme_t') ? theme_t('We are currently updating the system. Please check back in a few minutes.') : $defaultMsg);

$siteName = h(get_option('site_name', 'GrindSite'));
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= h(get_favicon_url('/favicon.ico')) ?>">
    <title><?= h($title) ?> | <?= $siteName ?></title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .maintenance-container {
            background: #fff;
            padding: 40px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            max-width: 600px;
            width: 100%;
            text-align: center;
            border-top: 4px solid #333;
        }

        h1 {
            margin-top: 0;
            color: #111;
            font-size: 24px;
        }

        p {
            color: #666;
            margin-bottom: 0;
        }

        .site-name {
            margin-top: 30px;
            font-size: 0.85em;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>

<body>
    <div class="maintenance-container">
        <h1><?= h($title) ?></h1>
        <p><?= h($message) ?></p>
        <div class="site-name"><?= $siteName ?></div>
    </div>
</body>

</html>
