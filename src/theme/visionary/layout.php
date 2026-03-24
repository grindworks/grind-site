<?php
if (!defined('GRINDS_APP')) exit;

// Get header data
$ctx = ['type' => $pageType ?? 'home', 'data' => $pageData ?? []];
$headerData = grinds_get_header_data($ctx);
extract($headerData);
?>
<!DOCTYPE html>
<html lang="<?= h($htmlLang) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($finalTitle) ?></title>
    <meta name="description" content="<?= h($finalDesc) ?>">
    <!-- Output head tags -->
    <?php grinds_head(); ?>

    <!-- Load theme CSS -->
    <?php $themeName = grinds_get_active_theme(); ?>
    <link rel="stylesheet" href="<?= grinds_asset_url('theme/' . $themeName . '/css/style.css') ?>">
</head>

<body <?php body_class("flex flex-col min-h-screen bg-theme-bg text-theme-text font-sans"); ?>>

    <!-- Include header -->
    <?php get_template_part('parts/header'); ?>

    <!-- Include hero section -->
    <?php get_template_part('parts/hero'); ?>

    <main class="flex-grow mx-auto px-4 py-10 container max-w-6xl">
        <?php display_banners('header_top', $ctx); ?>

        <div class="flex flex-col lg:flex-row gap-12">
            <div class="lg:w-8/12 w-full">
                <?php display_banners('content_top', $ctx); ?>

                <?= $content ?>

                <?php display_banners('content_bottom', $ctx); ?>
            </div>

            <aside class="lg:w-4/12 w-full space-y-8">
                <?php
                // Render sidebar widgets
                if (function_exists('get_sidebar_widgets')) {
                    foreach (get_sidebar_widgets() as $widget) {
                        render_widget($widget);
                    }
                }
                ?>
                <?php display_banners('sidebar_bottom', $ctx); ?>
            </aside>
        </div>
    </main>

    <!-- Include footer -->
    <?php get_template_part('parts/footer'); ?>

    <!-- Output footer scripts -->
    <?php grinds_footer(); ?>
</body>

</html>
