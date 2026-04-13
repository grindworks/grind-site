<?php

/**
 * layout.php
 * Define main layout.
 */
if (!defined('GRINDS_APP'))
  exit;

$ctx = ['type' => $pageType ?? 'home', 'data' => $pageData ?? []];

// Centralized Header Data
$headerData = grinds_get_header_data($ctx);
extract($headerData);
?>
<!DOCTYPE html>
<html lang="<?= h($htmlLang) ?>" class="scroll-pt-20 scroll-smooth">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>
    <?= h($finalTitle) ?>
  </title>
  <meta name="description" content="<?= h($finalDesc) ?>">
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
  <meta name="twitter:title" content="<?= h($finalTitle) ?>">
  <meta name="twitter:description" content="<?= h($finalDesc) ?>">
  <?php if ($ogImage): ?>
    <meta name="twitter:image" content="<?= h($ogImage) ?>">
  <?php
  endif; ?>
  <link rel="icon" href="<?= h(get_favicon_url()) ?>">

  <!-- Load fonts. -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Montserrat:wght@700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap"
    rel="stylesheet" media="print" onload="this.media='all'">
  <noscript>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Montserrat:wght@700;900&family=Noto+Sans+JP:wght@400;500;700&display=swap" rel="stylesheet">
  </noscript>

  <!-- Load CSS. -->
  <link rel="stylesheet" href="<?= grinds_theme_asset_url('css/style.css') ?>">
  <?php if (get_option('disable_external_assets')): ?>
    <script defer src="<?= grinds_asset_url('assets/js/vendor/collapse.min.js') ?>"></script>
    <script defer src="<?= grinds_asset_url('assets/js/vendor/alpine.min.js') ?>"></script>
  <?php
  else: ?>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <?php
  endif; ?>

  <?php if (!empty($robots)): ?>
    <meta name="robots" content="<?= h($robots) ?>">
  <?php
  endif; ?>

  <?php if ($showCanonical): ?>
    <script type="application/ld+json">
      <?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    </script>
  <?php endif; ?>

  <style>
    .font-heading {
      font-family: 'Montserrat', 'Noto Sans JP', sans-serif;
    }

    [x-cloak] {
      display: none !important;
    }

    body.preload-transitions * {
      transition: none !important;
    }
  </style>
  <?php grinds_head(); ?>
</head>

<body <?php body_class("preload-transitions flex flex-col bg-white min-h-screen font-sans text-slate-700 antialiased"); ?>>
  <?php if (isset($isPreview) && $isPreview): ?>
    <div
      class="right-6 bottom-20 z-[100] fixed bg-yellow-400 shadow-xl px-6 py-3 border-2 border-white rounded-full font-bold text-yellow-900 pointer-events-none">
      <?= theme_t('preview_mode') ?>
    </div>
  <?php
  endif; ?>

  <?php get_template_part('parts/header'); ?>

  <main class="flex-grow">
    <?php display_banners('header_top', $ctx); ?>
    <?php
    if ($pageType === 'home' && !isset($_GET['page'])) {
      display_banners('content_top', $ctx);
      echo $content;
      display_banners('content_bottom', $ctx);
    } else {
    ?>
      <div class="mx-auto px-6 py-12 lg:py-20 container">
        <div class="flex lg:flex-row flex-col gap-12">
          <div class="w-full lg:w-2/3 min-w-0">
            <?php display_banners('content_top', $ctx); ?>
            <?= $content ?>
            <?php display_banners('content_bottom', $ctx); ?>
          </div>
          <aside class="space-y-12 w-full lg:w-1/3 shrink-0">
            <?php display_banners('sidebar_top', $ctx); ?>
            <?php
            if (function_exists('get_sidebar_widgets')) {
              $widgets = get_sidebar_widgets();
              foreach ($widgets as $widget) {
                render_widget($widget);
              }
            }
            ?>
            <?php display_banners('sidebar_bottom', $ctx); ?>
          </aside>
        </div>
      </div>
    <?php
    } ?>
  </main>

  <?php get_template_part('parts/footer'); ?>
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
