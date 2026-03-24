<?php

if (!defined('GRINDS_APP')) exit;

/**
 * maintenance.php
 * Render maintenance page.
 */
$lang = get_option('site_lang', 'en');
$defaultTitle = 'System Maintenance';
$defaultMsg = 'We are currently updating the system. Please check back in a few minutes.';

$title = get_option('maintenance_title') ?: (function_exists('theme_t') ? theme_t('maintenance_title', $defaultTitle) : $defaultTitle);
$message = get_option('maintenance_message') ?: (function_exists('theme_t') ? theme_t('maintenance_message', $defaultMsg) : $defaultMsg);

$siteName = h(get_option('site_name', 'GrindSite'));
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>" class="h-full">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="<?= h(get_favicon_url()) ?>">
  <title><?= h($title) ?> | <?= $siteName ?></title>
  <?php if (!get_option('disable_external_assets')): ?>
    <script src="https://cdn.tailwindcss.com"></script>
  <?php endif; ?>
  <style>
    body {
      font-family: sans-serif;
    }
  </style>
</head>

<body class="bg-slate-900 text-white h-full flex items-center justify-center p-6">
  <div class="max-w-lg w-full text-center">
    <div class="mb-8">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-600 text-white mb-6 shadow-lg shadow-blue-900/50">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-wrench-screwdriver"></use>
        </svg>
      </div>
      <h1 class="text-3xl md:text-4xl font-bold mb-4 tracking-tight"><?= h($title) ?></h1>
      <p class="text-slate-400 text-lg leading-relaxed"><?= h($message) ?></p>
    </div>
    <div class="w-16 h-1 bg-slate-800 mx-auto mb-8 rounded-full"></div>
    <div class="text-sm text-slate-600 font-bold tracking-widest uppercase">
      <?= $siteName ?>
    </div>
  </div>
</body>

</html>
