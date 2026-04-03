<?php

/**
 * layout.php
 * Define main theme layout.
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

// Apply filters (Classic theme specific)
if (function_exists('apply_filters')) {
    $finalTitle = apply_filters('grinds_page_title', $finalTitle);
    $canonicalUrl = apply_filters('grinds_canonical_url', $canonicalUrl);
}

?>
<!DOCTYPE html>
<html lang="<?= h($htmlLang) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($finalTitle) ?></title>
    <meta name="description" content="<?= h($finalDesc) ?>">
    <?php if ($showCanonical && $pageType !== 'search'): ?>
        <link rel="canonical" href="<?= h($canonicalUrl) ?>">
    <?php
    endif; ?>
    <?php if ($showCanonical && $prevUrl): ?>
        <link rel="prev" href="<?= h($prevUrl) ?>"><?php
                                                endif; ?>
    <?php if ($showCanonical && $nextUrl): ?>
        <link rel="next" href="<?= h($nextUrl) ?>"><?php
                                                endif; ?>

    <!-- OGP meta. -->
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
                <meta property="article:tag" content="<?= h($tag['name']) ?>"><?php
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
    <?php if ($ogImage): ?>
        <meta property="og:image" content="<?= h($ogImage) ?>"><?php
                                                            endif; ?>
    <meta property="og:site_name" content="<?= h($siteName) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= h($finalTitle) ?>">
    <meta name="twitter:description" content="<?= h($finalDesc) ?>">
    <?php if ($ogImage): ?>
        <meta name="twitter:image" content="<?= h($ogImage) ?>"><?php
                                                            endif; ?>

    <link rel="icon" href="<?= h(get_favicon_url('/favicon.ico')) ?>">
    <link rel="stylesheet" href="<?= grinds_theme_asset_url('style.css') ?>">
    <?php
    if (!empty($robots)): ?>
        <meta name="robots" content="<?= h($robots) ?>">
    <?php
    endif; ?>
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

<body <?php body_class('preload-transitions'); ?>>

    <?php if (isset($isPreview) && $isPreview): ?>
        <div style="background:#ffc107; color:#000; text-align:center; padding:10px; font-weight:bold;">
            <?= theme_t('Preview Mode') ?>
        </div>
    <?php
    endif; ?>

    <header class="site-header">
        <div class="container">
            <h1 class="site-title"><a href="<?= site_url() ?>"><?= h(get_option('site_name')) ?></a></h1>
            <p class="site-description"><?= h(get_option('site_description')) ?></p>

            <nav class="main-navigation">
                <?php wp_nav_menu(['theme_location' => 'header']); ?>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if (function_exists('display_banners'))
            display_banners('header_top'); ?>
    </div>

    <div class="site-content container">
        <main class="main-area">
            <?php if (!is_home()): ?>
                <div class="breadcrumb-area">
                    <?= classic_get_breadcrumb_html() ?>
                </div>
            <?php
            endif; ?>

            <?php if (function_exists('display_banners'))
                display_banners('content_top'); ?>

            <?= $content ?? '' ?>

            <?php if (function_exists('display_banners'))
                display_banners('content_bottom'); ?>
        </main>

        <aside class="sidebar-area">
            <?php if (function_exists('display_banners'))
                display_banners('sidebar_top'); ?>
            <?php dynamic_sidebar(); ?>
            <?php if (function_exists('display_banners'))
                display_banners('sidebar_bottom'); ?>
        </aside>
    </div>

    <footer class="site-footer">
        <div class="container">
            <?php if (function_exists('display_banners'))
                display_banners('footer'); ?>
            <?php if ($copyright = get_option('footer_copyright')): ?>
                <p><?= h($copyright) ?></p>
            <?php
            elseif ($footerText = get_option('site_footer_text')): ?>
                <p><?= h($footerText) ?></p>
            <?php
            else: ?>
                <p>&copy; <?= date('Y') ?> <?= h(get_option('site_name')) ?></p>
            <?php
            endif; ?>
            <?php wp_nav_menu(['theme_location' => 'footer', 'depth' => 1]); ?>
        </div>
    </footer>

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
