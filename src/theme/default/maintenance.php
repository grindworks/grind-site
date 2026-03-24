<?php

if (!defined('GRINDS_APP')) exit;

/**
 * maintenance.php
 * Render maintenance page layout.
 */
// Get maintenance messages from options.
$lang = get_option('site_lang', grinds_detect_language());
$title = get_option('maintenance_title') ?: theme_t('System Maintenance');
$message = get_option('maintenance_message') ?: theme_t('We are currently updating the system. Please check back in a few minutes.');

// Skin information (passed from lib/front.php).
$colors = $skin['colors'] ?? [];
$font_family = !empty($skin['font']) ? $skin['font'] : 'sans-serif';
?>
<!DOCTYPE html>
<html lang="<?= h($lang) ?>" class="h-full antialiased">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($title) ?> | <?= h(get_option('site_name', 'GrindSite')) ?></title>

  <!-- Load font if specified -->
  <?php if (!empty($skin['font_url']) && !get_option('disable_external_assets')): ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="<?= h($skin['font_url']) ?>" rel="stylesheet">
  <?php endif; ?>

  <link rel="icon" href="<?= h(get_favicon_url()) ?>">

  <?php if (!get_option('disable_external_assets')): ?>
    <script src="https://cdn.tailwindcss.com"></script>
  <?php endif; ?>

  <style>
    :root {
      --color-bg: <?= hex2rgb($colors['bg'] ?? '#f8fafc') ?>;
      --color-surface: <?= hex2rgb($colors['surface'] ?? '#ffffff') ?>;
      --color-text: <?= hex2rgb($colors['text'] ?? '#334155') ?>;
      --color-primary: <?= hex2rgb($colors['primary'] ?? '#2563eb') ?>;
      --color-border: <?= hex2rgb($colors['border'] ?? '#e2e8f0') ?>;
      --color-info: <?= hex2rgb($colors['info'] ?? '#0ea5e9') ?>;
      --font-body: <?= $font_family ?>;
      --border-radius: <?= $skin['rounded'] ?? '0.5rem' ?>;
      --box-shadow: <?= $skin['shadow'] ?? '0 1px 3px 0 rgba(0, 0, 0, 0.1)' ?>;
    }

    body {
      background-color: rgb(var(--color-bg) / var(--color-bg-alpha, 1));
      font-family: var(--font-body);
      <?= !empty($skin['texture']) ? "background-image: url('{$skin['texture']}'); background-repeat: repeat;" : '' ?>
    }

    .bg-theme-surface {
      background-color: rgb(var(--color-surface) / var(--color-surface-alpha, 1));
    }

    .text-theme-text {
      color: rgb(var(--color-text) / var(--color-text-alpha, 1));
    }

    .text-theme-primary {
      color: rgb(var(--color-primary) / var(--color-primary-alpha, 1));
    }

    .border-theme-border {
      border-color: rgb(var(--color-border) / var(--color-border-alpha, 1));
    }

    .bg-theme-info-light {
      background-color: rgba(var(--color-info), 0.1);
    }

    .text-theme-info {
      color: rgb(var(--color-info) / var(--color-info-alpha, 1));
    }

    .rounded-theme {
      border-radius: var(--border-radius);
    }

    .shadow-theme {
      box-shadow: var(--box-shadow);
    }
  </style>
</head>

<body class="flex justify-center items-center px-4 sm:px-6 lg:px-8 py-12 min-h-full">
  <!-- Render maintenance page content -->
  <div class="space-y-8 w-full max-w-lg">
    <div class="text-center">
      <div class="flex justify-center items-center bg-theme-surface shadow-theme mx-auto mb-4 border border-theme-border rounded-theme w-12 h-12 font-bold text-theme-primary text-xl">GS</div>
      <h2 class="font-bold text-theme-text text-2xl tracking-tight">
        <?= h(get_option('admin_title') ?: get_option('site_name') ?: CMS_NAME) ?>
      </h2>
    </div>

    <div class="bg-theme-surface shadow-theme px-8 sm:px-10 py-10 border border-theme-border rounded-theme text-center">
      <div class="flex justify-center items-center bg-theme-info-light mx-auto mb-6 rounded-full w-12 h-12 text-theme-info">
        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-wrench-screwdriver"></use>
        </svg>
      </div>

      <h1 class="mb-4 font-bold text-theme-text text-2xl">
        <?= h($title) ?>
      </h1>

      <p class="opacity-70 text-theme-text leading-relaxed">
        <?= h($message) ?>
      </p>
    </div>

    <div class="mt-8 text-center text-theme-text opacity-60 text-xs">
      &copy; <?= date('Y') ?> <?= h(get_option('site_name')) ?>. All rights reserved.
    </div>
  </div>
</body>

</html>
