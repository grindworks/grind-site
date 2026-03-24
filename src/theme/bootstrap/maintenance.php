<?php

if (!defined('GRINDS_APP')) exit;

/**
 * maintenance.php
 * Render maintenance page.
 */
$title = theme_t('System Maintenance');
$msg = theme_t('We are currently updating the system. Please check back in a few minutes.');
$siteName = h(get_option('site_name', 'GrindSite'));

// Language detection
$defaultLang = function_exists('grinds_detect_language') ? grinds_detect_language() : 'en';
$htmlLang = get_option('site_lang', $defaultLang);
?>
<!DOCTYPE html>
<html lang="<?= h($htmlLang) ?>">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="<?= h(get_favicon_url()) ?>">
  <title><?= h($title) ?> | <?= $siteName ?></title>
  <?php if (!get_option('disable_external_assets')): ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <?php endif; ?>
</head>

<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 p-4">

  <div class="card shadow-lg border-0 text-center" style="max-width: 500px; width: 100%;">
    <div class="card-body p-5">
      <div class="mb-4 text-primary">
        <i class="bi bi-cone-striped" style="font-size: 4rem;"></i>
      </div>

      <h1 class="h3 fw-bold mb-3"><?= h($title) ?></h1>
      <p class="text-muted mb-4">
        <?= h($msg) ?>
      </p>

      <hr class="my-4">
      <div class="small text-secondary font-monospace">
        <?= $siteName ?>
      </div>
    </div>
  </div>

</body>

</html>
