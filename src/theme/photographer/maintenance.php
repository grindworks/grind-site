<?php

if (!defined('GRINDS_APP')) exit;

/**
 * maintenance.php
 * Display maintenance page.
 */
$defaultLang = function_exists('grinds_detect_language') ? grinds_detect_language() : 'en';
$lang = get_option('site_lang', $defaultLang);
$defaultTitle = 'Maintenance';
$defaultMsg = 'We are currently performing scheduled maintenance.';

$title = get_option('maintenance_title') ?: (function_exists('theme_t') ? theme_t('Maintenance') : $defaultTitle);
$message = get_option('maintenance_message') ?: (function_exists('theme_t') ? theme_t('We are currently performing scheduled maintenance.') : $defaultMsg);

$siteName = h(get_option('site_name', 'GrindSite'));
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="<?= h(get_favicon_url()) ?>">
    <title><?= h($title) ?> | <?= $siteName ?></title>
    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: "Helvetica Neue", Arial, sans-serif;
            height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }

        h1 {
            font-weight: 300;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        p {
            color: #888;
            max-width: 500px;
            line-height: 1.6;
            font-weight: 300;
        }

        .brand {
            margin-top: 50px;
            font-size: 11px;
            color: #444;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    <h1><?= h($title) ?></h1>
    <p><?= h($message) ?></p>
    <div class="brand"><?= $siteName ?></div>
</body>

</html>
