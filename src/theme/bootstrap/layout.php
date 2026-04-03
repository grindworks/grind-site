<?php

if (!defined('GRINDS_APP'))
  exit;

/**
 * layout.php
 * Define main theme layout.
 */
$ctx = ['type' => $pageType ?? 'home', 'data' => $pageData ?? []];
// Centralized Header Data
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
  <?php endif; ?>
  <?php if ($showCanonical && $nextUrl): ?>
    <link rel="next" href="<?= h($nextUrl) ?>">
  <?php
  endif; ?>

  <!-- OGP meta tags -->
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
    <?php endif; ?>
    <?php if (!empty($pageData['tags'])): ?>
      <?php foreach ($pageData['tags'] as $tag): ?>
        <meta property="article:tag" content="<?= h($tag['name']) ?>"><?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>
  <?php if ($showCanonical): ?>
    <meta property="og:url" content="<?= h($canonicalUrl) ?>">
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

  <?php if (!empty($robots)): ?>
    <meta name="robots" content="<?= h($robots) ?>">
  <?php
  endif; ?>

  <!-- Bootstrap CSS. -->
  <?php if (!get_option('disable_external_assets')): ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons. -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <?php
  endif; ?>

  <!-- Custom CSS. -->
  <link rel="stylesheet" href="<?= grinds_asset_url('theme/' . grinds_get_active_theme() . '/css/style.css') ?>">

  <script type="application/ld+json">
    <?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
  </script>

  <?php grinds_head(); ?>
  <style>
    body.preload-transitions * {
      transition: none !important;
    }
  </style>
</head>

<body <?php body_class("preload-transitions d-flex flex-column bg-light min-vh-100"); ?>>
  <?php if (isset($isPreview) && $isPreview): ?>
    <div class="bg-warning py-2 border-bottom border-warning-subtle text-dark text-center fw-bold">
      <?= theme_t('Preview Mode (Draft)') ?>
    </div>
  <?php
  endif; ?>

  <?php get_template_part('parts/header'); ?>

  <main class="flex-grow-1 py-5 container">
    <?php display_banners('header_top', $ctx); ?>

    <?php
    $sidebarWidgets = function_exists('get_sidebar_widgets') ? get_sidebar_widgets() : [];
    $hasSidebar = !empty($sidebarWidgets);
    ?>
    <div class="row g-5">
      <div class="<?= $hasSidebar ? 'col-lg-9' : 'col-12' ?>" style="min-width: 0;">
        <?php display_banners('content_top', $ctx); ?>
        <?= $content ?>
        <?php display_banners('content_bottom', $ctx); ?>
      </div>

      <?php if ($hasSidebar): ?>
        <?php get_template_part('sidebar'); ?>
      <?php
      endif; ?>
    </div>
  </main>

  <?php get_template_part('parts/footer'); ?>

  <!-- Bootstrap JS. -->
  <?php if (!get_option('disable_external_assets')): ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <?php
  endif; ?>

  <?php grinds_footer(); ?>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      requestAnimationFrame(() => {
        document.body.classList.remove('preload-transitions');
      });
    });
  </script>
</body>

</html>
