<?php

if (!defined('GRINDS_APP')) exit;

/**
 * maintenance.php
 * Render maintenance page.
 */
// Get maintenance messages.
$defaultLang = 'en';
if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && stripos($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'ja') !== false) {
  $defaultLang = 'ja';
}
$lang = get_option('site_lang', $defaultLang);
$title = get_option('maintenance_title') ?: theme_t('System Maintenance');
$message = get_option('maintenance_message') ?: theme_t('We are currently updating the system. Please check back in a few minutes.');

// Skin information (optional overrides).
$colors = $skin['colors'] ?? [];
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>" class="h-full antialiased">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="<?= h(get_favicon_url()) ?>">
  <title><?= h($title) ?> | <?= h(get_option('site_name', 'GrindSite')) ?></title>

  <?php if (!get_option('disable_external_assets')): ?>
    <!-- Load fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
  <?php endif; ?>

  <style>
    :root {
      --font-body: 'Inter', 'Noto Sans JP', sans-serif;
      --color-bg: #f8fafc;
      --color-surface: #ffffff;
      --color-text: #334155;
      --color-heading: #0f172a;
      --color-primary: #2563eb;
      --color-border: #e2e8f0;
    }

    body {
      background-color: var(--color-bg);
      color: var(--color-text);
      font-family: var(--font-body);
    }
  </style>
</head>

<body class="flex justify-center items-center px-4 sm:px-6 lg:px-8 py-12 min-h-full">
  <div class="w-full max-w-lg">
    <div class="text-center mb-8">
      <div class="inline-flex justify-center items-center bg-white shadow-sm mb-4 border border-slate-200 rounded-lg w-16 h-16 text-blue-600">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-wrench-screwdriver"></use>
        </svg>
      </div>
      <h2 class="font-bold text-slate-900 text-2xl tracking-tight">
        <?= h(get_option('admin_title') ?: get_option('site_name') ?: CMS_NAME) ?>
      </h2>
    </div>

    <div class="bg-white shadow-lg px-8 sm:px-10 py-10 border border-slate-100 rounded-xl text-center">
      <h1 class="mb-4 font-bold text-slate-900 text-xl">
        <?= h($title) ?>
      </h1>

      <p class="text-slate-600 leading-relaxed">
        <?= h($message) ?>
      </p>
    </div>

    <div class="mt-8 text-center text-slate-400 text-xs">
      &copy; <?= date('Y') ?> <?= h(get_option('site_name')) ?>. All rights reserved.
    </div>
  </div>
</body>

</html>
