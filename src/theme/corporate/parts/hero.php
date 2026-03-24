<?php

/**
 * hero.php
 * Render hero section.
 */
if (!defined('GRINDS_APP')) exit;

$hConf = json_decode($pageData['post']['hero_settings'] ?? '{}', true);

// Check image existence.
$hasDesktop = !empty($pageData['post']['hero_image']);
$hasMobile = !empty($hConf['mobile_image']);
if (!$hasDesktop && !$hasMobile) return;

$imgUrlRaw = $hasDesktop ? $pageData['post']['hero_image'] : '';
$imgUrl = $hasDesktop ? resolve_url($imgUrlRaw) : '';

// Retrieve mobile settings.
$mobileImgUrlRaw = $hasMobile ? $hConf['mobile_image'] : null;
$mobileImgUrl = $hasMobile ? resolve_url($mobileImgUrlRaw) : null;
$isFixed = !empty($hConf['fixed_bg']);

$title = $hConf['title'] ?? '';
$sub = $hConf['subtext'] ?? '';
$overlayOpacity = isset($hConf['overlay_opacity']) ? (int)$hConf['overlay_opacity'] : 60;
$btnText = $hConf['button_text'] ?? '';
$btnLink = $hConf['button_link'] ?? '';
if (preg_match('/^\s*javascript:/i', $btnLink)) {
  $btnLink = '#';
}

// Define base classes.
$bgBaseClass = "absolute inset-0 transition-transform duration-1000 md:group-hover:scale-105";
if ($isFixed) {
  // Disable zoom on desktop when fixed background is active to prevent jitter
  $bgBaseClass .= " bg-cover bg-center md:bg-fixed md:group-hover:scale-100";
} else {
  $bgBaseClass .= " w-full h-full object-cover";
}
$altText = h($title ?: ($pageData['post']['title'] ?? ''));
?>

<div class="group relative bg-slate-900 w-full h-64 md:h-96 overflow-hidden">

  <?php if ($mobileImgUrl): ?>
    <!-- Mobile image. -->
    <?php if ($isFixed): ?>
      <div class="<?= $bgBaseClass ?> md:hidden" style="background-image: url(<?= h(json_encode($mobileImgUrl)) ?>);"></div>
    <?php else: ?>
      <?= get_image_html($mobileImgUrlRaw, ['alt' => $altText, 'class' => $bgBaseClass . ' md:hidden']) ?>
    <?php endif; ?>

    <!-- Desktop image. -->
    <?php if ($isFixed): ?>
      <?php if ($imgUrl): ?>
        <div class="<?= $bgBaseClass ?> hidden md:block" style="background-image: url(<?= h(json_encode($imgUrl)) ?>);"></div>
      <?php endif; ?>
    <?php else: ?>
      <?php if ($imgUrl): ?>
        <?= get_image_html($imgUrlRaw, ['alt' => $altText, 'class' => $bgBaseClass . ' hidden md:block']) ?>
      <?php endif; ?>
    <?php endif; ?>
  <?php else: ?>
    <!-- Default image. -->
    <?php if ($isFixed): ?>
      <div class="<?= $bgBaseClass ?>" style="background-image: url(<?= h(json_encode($imgUrl)) ?>);"></div>
    <?php else: ?>
      <?= get_image_html($imgUrlRaw, ['alt' => $altText, 'class' => $bgBaseClass]) ?>
    <?php endif; ?>
  <?php endif; ?>

  <!-- Overlay. -->
  <div class="absolute inset-0 bg-slate-900" style="opacity: <?= $overlayOpacity / 100 ?>;"></div>

  <!-- Content. -->
  <div class="absolute inset-0 flex justify-center items-center px-4 text-center">
    <div class="z-10 max-w-4xl animate-fade-in-up">
      <?php if ($title): ?>
        <h1 class="drop-shadow-md mb-4 font-bold text-white text-3xl md:text-5xl tracking-tight">
          <?= h($title) ?>
        </h1>
      <?php endif; ?>

      <?php if ($sub): ?>
        <p class="drop-shadow-sm mb-6 font-medium text-slate-100 text-lg md:text-xl tracking-wide"><?= nl2br(h($sub)) ?></p>
      <?php endif; ?>

      <?php if ($btnText && $btnLink): ?>
        <a href="<?= resolve_url($btnLink) ?>" class="inline-block bg-white hover:bg-slate-100 shadow-lg px-8 py-3 rounded-full font-bold text-slate-900 transition-all hover:-translate-y-1 transform">
          <?= h($btnText) ?>
        </a>
      <?php endif; ?>
    </div>
  </div>
</div>
