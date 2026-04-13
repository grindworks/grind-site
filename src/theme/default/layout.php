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
<html lang="<?= h($htmlLang) ?>" dir="ltr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#1e293b" media="(prefers-color-scheme: dark)">

  <title>
    <?= h($finalTitle) ?>
  </title>
  <meta name="description" content="<?= h($finalDesc) ?>">

  <?php if ($showCanonical && $pageType !== 'search'): ?>
    <link rel="canonical" href="<?= h($canonicalUrl) ?>">
  <?php
  endif; ?>

  <?php if ($showCanonical && !empty($prevUrl)): ?>
    <link rel="prev" href="<?= h($prevUrl) ?>">
  <?php endif; ?>
  <?php if ($showCanonical && !empty($nextUrl)): ?>
    <link rel="next" href="<?= h($nextUrl) ?>">
  <?php endif; ?>

  <?php if ($showCanonical): ?>
    <meta property="og:url" content="<?= h($ogpUrl) ?>">
  <?php endif; ?>
  <?php
  $postAuthor = '';
  if (isset($pageData['post'])) {
    $hs = $pageData['post']['hero_settings_decoded'] ?? json_decode($pageData['post']['hero_settings'] ?? '{}', true);
    $postAuthor = $hs['seo_author'] ?? '';
  }
  $authorName = $postAuthor ?: $siteName;

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
    if (!empty($postAuthor)): ?>
      <meta property="article:author" content="<?= h($postAuthor) ?>">
    <?php endif;
    $pubTime = $pageData['post']['published_at'] ?? $pageData['post']['created_at'];
    $modTime = $pageData['post']['updated_at'] ?? $pubTime;
    $pubTs = strtotime((string)$pubTime) ?: time();
    $modTs = strtotime((string)$modTime) ?: $pubTs;
    ?>
    <meta property="article:published_time" content="<?= date('c', $pubTs) ?>">
    <meta property="article:modified_time" content="<?= date('c', $modTs) ?>">
    <meta property="og:updated_time" content="<?= date('c', $modTs) ?>">
    <?php if (!empty($pageData['post']['category_name'])): ?>
      <meta property="article:section" content="<?= h($pageData['post']['category_name']) ?>">
    <?php
    endif; ?>
    <?php if (!empty($pageData['tags'])): ?>
      <?php foreach ($pageData['tags'] as $tag): ?>
        <meta property="article:tag" content="<?= h($tag['name']) ?>">
      <?php endforeach; ?>
    <?php endif; ?>
  <?php endif; ?>

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
  <?php
  foreach ($socialLinks as $link) {
    if (preg_match('/(?:twitter\.com|x\.com)\/([a-zA-Z0-9_]+)/i', $link, $matches)) {
      echo '<meta name="twitter:site" content="@' . h($matches[1]) . '">' . "\n";
      break;
    }
  } ?>

  <link rel="icon" href="<?= h(get_favicon_url()) ?>">

  <?php if (!empty($robots)): ?>
    <meta name="robots" content="<?= h($robots) ?>">
  <?php
  endif; ?>

  <?php if (!empty($tdmReservation)): ?>
    <meta name="tdm-reservation" content="1">
  <?php endif; ?>
  <?php if ($showCanonical): ?>
    <script type="application/ld+json">
      <?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    </script>
  <?php endif; ?>

  <!-- Load theme CSS with Cache Busting -->
  <?php
  $themeName = grinds_get_active_theme();
  $themeCssRelPath = 'theme/' . $themeName . '/css/style.css';

  // Fallback to default theme CSS if active theme doesn't provide its own
  if (!file_exists(ROOT_PATH . '/' . $themeCssRelPath)) {
    $themeCssRelPath = 'theme/default/css/style.css';
  }
  ?>
  <link rel="stylesheet" href="<?= grinds_asset_url($themeCssRelPath) ?>">

  <!-- Load Alpine.js and plugins -->
  <?php if (get_option('disable_external_assets') && file_exists(ROOT_PATH . '/assets/js/vendor/alpine.min.js') && file_exists(ROOT_PATH . '/assets/js/vendor/collapse.min.js')): ?>
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
