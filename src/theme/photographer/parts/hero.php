<?php

/**
 * hero.php
 * Render hero section.
 */
if (!defined('GRINDS_APP')) exit;

$hConf = json_decode($pageData['post']['hero_settings'] ?? '{}', true);
$imgUrl = !empty($pageData['post']['hero_image']) ? resolve_url($pageData['post']['hero_image']) : '';

// Retrieve settings.
$mobileImgUrl = !empty($hConf['mobile_image']) ? resolve_url($hConf['mobile_image']) : null;

if (empty($imgUrl) && empty($mobileImgUrl)) return;

$isFixed = !empty($hConf['fixed_bg']);

$title = $hConf['title'] ?? '';
$sub = $hConf['subtext'] ?? '';
$overlayOpacity = isset($hConf['overlay_opacity']) ? (int)$hConf['overlay_opacity'] : 20;

// Define classes.
$bgBaseClass = "absolute inset-0 transition-transform duration-[2000ms] ease-out group-hover:scale-105";

if ($isFixed) {
    $bgBaseClass .= " bg-cover bg-center md:bg-fixed md:group-hover:scale-100";
} else {
    $bgBaseClass .= " w-full h-full object-cover";
}
$altText = h($title ?: ($pageData['post']['title'] ?? ''));
?>

<div class="group relative mb-16 w-full h-[70vh] md:h-[80vh] overflow-hidden">

    <?php if ($mobileImgUrl): ?>
        <!-- Mobile image. -->
        <?php if ($isFixed): ?>
            <div class="<?= $bgBaseClass ?> md:hidden" style="background-image: url(<?= h(json_encode($mobileImgUrl)) ?>);"></div>
        <?php else: ?>
            <?= get_image_html($mobileImgUrl, ['alt' => $altText, 'class' => $bgBaseClass . ' md:hidden']) ?>
        <?php endif; ?>

        <!-- Desktop image. -->
        <?php if ($isFixed): ?>
            <?php if ($imgUrl): ?>
                <div class="<?= $bgBaseClass ?> hidden md:block" style="background-image: url(<?= h(json_encode($imgUrl)) ?>);"></div>
            <?php endif; ?>
        <?php else: ?>
            <?php if ($imgUrl): ?>
                <?= get_image_html($imgUrl, ['alt' => $altText, 'class' => $bgBaseClass . ' hidden md:block']) ?>
            <?php endif; ?>
        <?php endif; ?>
    <?php else: ?>
        <!-- Default image. -->
        <?php if ($isFixed): ?>
            <?php if ($imgUrl): ?>
                <div class="<?= $bgBaseClass ?>" style="background-image: url(<?= h(json_encode($imgUrl)) ?>);"></div>
            <?php endif; ?>
        <?php else: ?>
            <?php if ($imgUrl): ?>
                <?= get_image_html($imgUrl, ['alt' => $altText, 'class' => $bgBaseClass]) ?>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Overlay. -->
    <div class="absolute inset-0 bg-black" style="opacity: <?= $overlayOpacity / 100 ?>;"></div>

    <!-- Content. -->
    <?php if ($title || $sub): ?>
        <div class="absolute inset-0 flex flex-col justify-center items-center px-6 text-center">
            <div class="slide-in-from-bottom-8 z-10 max-w-3xl animate-in duration-1000 fade-in">
                <?php if ($title): ?>
                    <h1 class="drop-shadow-lg mb-6 font-serif text-white text-4xl md:text-6xl lg:text-7xl tracking-tight">
                        <?= h($title) ?>
                    </h1>
                <?php endif; ?>

                <?php if ($sub): ?>
                    <p class="drop-shadow-md mx-auto max-w-xl font-light text-gray-200 text-lg md:text-xl tracking-wide"><?= nl2br(h($sub)) ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
