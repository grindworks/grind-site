<?php

/**
 * Render fullscreen presentation layout.
 */
if (!defined('GRINDS_APP')) exit;

// Extract header context data
$ctx = grinds_get_header_data(['type' => $GLOBALS['pageType'] ?? 'home', 'data' => $GLOBALS['pageData'] ?? []]);
extract($ctx);
?>
<!DOCTYPE html>
<html lang="<?= h($htmlLang) ?>">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($finalTitle) ?></title>
  <meta name="description" content="<?= h($finalDesc) ?>">
  <?php if ($showCanonical): ?>
    <link rel="canonical" href="<?= h($canonicalUrl) ?>">
  <?php endif; ?>
  <meta name="robots" content="<?= h($robots) ?>">
  <meta property="og:title" content="<?= h($finalTitle) ?>">
  <meta property="og:description" content="<?= h($finalDesc) ?>">
  <meta property="og:url" content="<?= h($ogpUrl) ?>">
  <meta property="og:image" content="<?= h($ogImage) ?>">
  <?php grinds_head(); ?>
  <style>
    html,
    body {
      margin: 0;
      padding: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      background-color: #000;
    }

    .presentation-wrapper {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 9999;
      background-color: #000;
    }

    .presentation-wrapper .cms-block-embed,
    .presentation-wrapper .cms-block-embed>div {
      position: absolute !important;
      top: 0 !important;
      left: 0 !important;
      width: 100% !important;
      height: 100% !important;
      max-width: none !important;
      max-height: none !important;
      margin: 0 !important;
      padding: 0 !important;
      border-radius: 0 !important;
      border: none !important;
      aspect-ratio: auto !important;
      box-shadow: none !important;
    }

    .presentation-wrapper iframe {
      width: 100% !important;
      height: 100% !important;
    }
  </style>
</head>

<body>
  <!-- Render main content -->
  <?= $content ?>
  <!-- Inject footer scripts -->
  <?php grinds_footer(); ?>
</body>

</html>
