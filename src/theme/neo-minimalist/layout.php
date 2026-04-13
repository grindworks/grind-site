<?php

if (!defined('GRINDS_APP'))
  exit;

/**
 * layout.php
 * Define main theme layout.
 */
$ctx = [
  'type' => $pageType ?? 'home',
  'data' => $pageData ?? []
];

// Language detection: Default to 'en', but switch to 'ja' if browser requests it.
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

  <?php if ($showCanonical && $pageType !== 'search'): ?>
    <link rel="canonical" href="<?= h($canonicalUrl) ?>">
  <?php
  endif; ?>
  <?php if (!empty($prevUrl)): ?>
    <link rel="prev" href="<?= h($prevUrl) ?>">
  <?php
  endif; ?>
  <?php if (!empty($nextUrl)): ?>
    <link rel="next" href="<?= h($nextUrl) ?>">
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

  // Fetch official social links for identity verification (rel="me")
  $socialLinksRaw = get_option('official_social_links', '');
  $socialLinks = array_filter(array_map('trim', explode("\n", $socialLinksRaw)));
  ?>
  <meta name="author" content="<?= h($authorName) ?>">
  <?php foreach ($socialLinks as $link): ?>
    <link rel="me" href="<?= h($link) ?>">
  <?php
  endforeach; ?>
  <meta property="og:site_name" content="<?= h($siteName) ?>">
  <meta property="og:title" content="<?= h($finalTitle) ?>">
  <meta property="og:description" content="<?= h($finalDesc) ?>">
  <meta property="og:type" content="<?= h($ogType) ?>">
  <meta property="og:locale" content="<?= $htmlLang === 'ja' ? 'ja_JP' : 'en_US' ?>">
  <?php if ($ogType === 'article' && isset($pageData['post'])): ?>
    <?php
    $pubTime = $pageData['post']['published_at'] ?? $pageData['post']['created_at'];
    $modTime = $pageData['post']['updated_at'] ?? $pubTime;
    ?>
    <meta property="article:published_time" content="<?= date('c', strtotime($pubTime)) ?>">
    <meta property="article:modified_time" content="<?= date('c', strtotime($modTime)) ?>">
    <meta property="og:updated_time" content="<?= date('c', strtotime($modTime)) ?>">
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
  <?php if ($ogImage): ?>
    <meta property="og:image" content="<?= h($ogImage) ?>">
  <?php
  endif; ?>

  <meta name="twitter:card" content="<?= ($ogImage && empty($isFallbackImage)) ? 'summary_large_image' : 'summary' ?>">
  <meta name="twitter:title" content="<?= h($finalTitle) ?>">
  <meta name="twitter:description" content="<?= h($finalDesc) ?>">
  <?php if ($ogImage): ?>
    <meta name="twitter:image" content="<?= h($ogImage) ?>">
  <?php
  endif; ?>

  <link rel="icon" href="<?= h(get_favicon_url()) ?>">

  <?php if (!empty($robots)): ?>
    <meta name="robots" content="<?= h($robots) ?>">
  <?php
  endif; ?>

  <?php if ($showCanonical): ?>
    <script type="application/ld+json">
      <?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    </script>
  <?php endif; ?>

  <!-- Load theme CSS with Cache Busting -->
  <?php
  $themeName = grinds_get_active_theme();
  ?>
  <link rel="stylesheet" href="<?= grinds_theme_asset_url('css/style.css') ?>">

  <!-- Load Alpine.js and plugins -->
  <?php if (get_option('disable_external_assets')): ?>
    <script defer src="<?= grinds_asset_url('assets/js/vendor/collapse.min.js') ?>"></script>
    <script defer src="<?= grinds_asset_url('assets/js/vendor/alpine.min.js') ?>"></script>
  <?php
  else: ?>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <?php
  endif; ?>

  <style>
    details#toc {
      transition: all 0.3s ease;
    }

    details#toc[open] summary {
      margin-bottom: 0.5rem;
    }

    /* Alpine.js FOUC Prevention */
    [x-cloak] {
      display: none !important;
    }

    body.preload-transitions * {
      transition: none !important;
    }
  </style>

  <?php grinds_head(); ?>
</head>

<body <?php body_class("preload-transitions flex flex-col bg-theme-bg min-h-screen text-theme-text"); ?>>

  <?php if (isset($isPreview) && $isPreview): ?>
    <div class="bg-yellow-400 py-2 text-yellow-900 text-center font-bold">
      <?= theme_t('Preview Mode') ?>
    </div>
  <?php
  endif; ?>

  <?php
  get_template_part('parts/header'); ?>

  <?php get_template_part('parts/hero'); ?>

  <main class="flex-grow mx-auto px-4 py-8 container">
    <?php display_banners('header_top', $ctx); ?>
    <div class="flex lg:flex-row flex-col gap-10">
      <div class="lg:w-3/4">
        <?php display_banners('content_top', $ctx); ?>
        <?= $content ?>
        <?php display_banners('content_bottom', $ctx); ?>
      </div>
      <aside class="lg:w-1/4">
        <?php
        if (function_exists('get_sidebar_widgets')) {
          $widgets = get_sidebar_widgets();
          foreach ($widgets as $widget) {
            render_widget($widget);
          }
        }
        ?>
      </aside>
    </div>
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
