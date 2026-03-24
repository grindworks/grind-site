<?php

/**
 * layout.php
 * Define main layout.
 */
if (!defined('GRINDS_APP'))
  exit;

// Centralized Header Data
$ctx = [
  'type' => $pageType ?? 'home',
  'data' => $pageData ?? []
];
$headerData = grinds_get_header_data($ctx);
extract($headerData);
?>

<!DOCTYPE html>
<html lang="<?= h($htmlLang) ?>">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>
    <?= h($finalTitle) ?>
  </title>
  <meta name="description" content="<?= h($finalDesc) ?>">
  <link rel="icon" href="<?= h(get_favicon_url()) ?>">
  <?php if ($showCanonical && $pageType !== 'search'): ?>
    <link rel="canonical" href="<?= h($canonicalUrl) ?>">
  <?php
  endif; ?>
  <?php if ($showCanonical && $prevUrl): ?>
    <link rel="prev" href="<?= h($prevUrl) ?>">
  <?php
  endif; ?>
  <?php if ($showCanonical && $nextUrl): ?>
    <link rel="next" href="<?= h($nextUrl) ?>">
  <?php
  endif; ?>

  <!-- OGP tags. -->
  <meta property="og:title" content="<?= h($finalTitle) ?>">
  <meta property="og:description" content="<?= h($finalDesc) ?>">
  <meta property="og:type" content="<?= h($ogType) ?>">
  <?php if ($ogType === 'article' && isset($pageData['post'])): ?>
    <?php
    $pubTime = $pageData['post']['published_at'] ?? $pageData['post']['created_at'];
    $modTime = $pageData['post']['updated_at'] ?? $pubTime;
    ?>
    <meta property="article:published_time" content="<?= date('c', strtotime($pubTime)) ?>">
    <meta property="article:modified_time" content="<?= date('c', strtotime($modTime)) ?>">
    <?php if (!empty($pageData['post']['category_name'])): ?>
      <meta property="article:section" content="<?= h($pageData['post']['category_name']) ?>">
    <?php
    endif; ?>
    <?php if (!empty($pageData['tags'])): ?>
      <?php foreach ($pageData['tags'] as $tag): ?>
        <meta property="article:tag" content="<?= h($tag['name']) ?>">
      <?php
      endforeach; ?>
    <?php
    endif; ?>
  <?php
  endif; ?>
  <?php if ($showCanonical): ?>
    <meta property="og:url" content="<?= h($ogpUrl) ?>">
  <?php
  endif; ?>
  <?php
  $postAuthor = '';
  if (isset($pageData['post'])) {
    $hs = $pageData['post']['hero_settings_decoded'] ?? json_decode($pageData['post']['hero_settings'] ?? '{}', true);
    $postAuthor = $hs['seo_author'] ?? '';
  }
  $authorName = $postAuthor ?: (defined('CMS_AUTHOR') ? constant('CMS_AUTHOR') : $siteName);
  ?>
  <meta name="author" content="<?= h($authorName) ?>">
  <meta property="og:image" content="<?= h($ogImage) ?>">
  <meta property="og:site_name" content="<?= h($siteName) ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= h($finalTitle) ?>">
  <meta name="twitter:description" content="<?= h($finalDesc) ?>">
  <?php if ($ogImage): ?>
    <meta name="twitter:image" content="<?= h($ogImage) ?>">
  <?php
  endif; ?>

  <?php
  $robots = [];
  if (get_option('site_noindex')) {
    $robots[] = 'noindex';
    $robots[] = 'nofollow';
  }
  if (isset($pageData['post'])) {
    if (!empty($pageData['post']['is_noindex']))
      $robots[] = 'noindex';
    if (!empty($pageData['post']['is_nofollow']))
      $robots[] = 'nofollow';
    if (!empty($pageData['post']['is_noarchive']))
      $robots[] = 'noarchive';
  }
  if ($pageType === 'search') {
    $robots[] = 'noindex';
  }
  if (!empty($robots)): ?>
    <meta name="robots" content="<?= implode(', ', $robots) ?>">
  <?php
  endif; ?>

  <!-- Load fonts. -->
  <?php if (!get_option('disable_external_assets')): ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Playfair+Display:ital,wght@0,400;0,600;1,400&display=swap"
      rel="stylesheet">
  <?php
  endif; ?>

  <!-- Load CSS. -->
  <link rel="stylesheet" href="<?= grinds_asset_url('theme/photographer/css/style.css') ?>">
  <?php if (get_option('disable_external_assets')): ?>
    <script defer src="<?= grinds_asset_url('assets/js/vendor/alpine.min.js') ?>"></script>
  <?php
  else: ?>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <?php
  endif; ?>

  <style>
    [x-cloak] {
      display: none !important;
    }
  </style>

  <?php grinds_head(); ?>
</head>

<body class="bg-white font-sans text-photo-black antialiased" x-data="{ mobileOpen: false }" x-init="$watch('mobileOpen', value => {
    document.body.classList.toggle('overflow-hidden', value);
    document.documentElement.classList.toggle('overflow-hidden', value);
  })">

  <?php if (isset($isPreview) && $isPreview): ?>
    <div
      class="right-6 bottom-20 z-[100] fixed bg-yellow-400 shadow-xl px-6 py-3 border-2 border-white rounded-full font-serif text-yellow-900 italic pointer-events-none">
      <?= theme_t('Preview Mode') ?>
    </div>
  <?php
  endif; ?>
  <?php include __DIR__ . '/parts/header.php'; ?>

  <!-- Render content. -->
  <main class="flex flex-col lg:ml-80 pt-16 lg:pt-0 min-h-screen transition-all duration-300">
    <?php if (function_exists('display_banners'))
      display_banners('header_top', $ctx ?? []); ?>
    <div class="flex-1 mx-auto p-6 md:p-12 lg:p-16 w-full max-w-[1600px]">
      <?= $content ?>
    </div>
    <?php include __DIR__ . '/parts/footer.php'; ?>
  </main>

  <?php grinds_footer(); ?>
</body>

</html>
