<?php

/**
 * hero.php
 * Render hero section.
 */
if (!defined('GRINDS_APP')) exit;

$hConf = json_decode($pageData['post']['hero_settings'] ?? '{}', true);
$imgUrlRaw = $pageData['post']['hero_image'] ?? '';
$imgUrl = !empty($imgUrlRaw) ? resolve_url($imgUrlRaw) : '';

// Retrieve mobile image and fixed background settings
$mobileImgUrlRaw = $hConf['mobile_image'] ?? null;
$mobileImgUrl = !empty($mobileImgUrlRaw) ? resolve_url($mobileImgUrlRaw) : null;

if (empty($imgUrlRaw) && empty($mobileImgUrlRaw)) return;

$isFixed = !empty($hConf['fixed_bg']);

$layout = $hConf['layout'] ?? 'standard';
$overlayOpacity = isset($hConf['overlay_opacity']) ? (int)$hConf['overlay_opacity'] : 50;
$textColor = 'text-white';

// Define layout styles
$layoutStyle = "position: relative;";
if ($layout === 'fullscreen') {
  $layoutStyle .= "min-height: 80vh; display: flex; align-items: center;";
} else {
  $layoutStyle .= "padding: 6rem 0;";
}

// Define common background styles
$bgBaseStyle = "position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0;";
if ($isFixed) {
  $bgBaseStyle .= " background-size: cover; background-position: center; background-attachment: fixed;";
} else {
  $bgBaseStyle .= " object-fit: cover;";
}

// Define container classes
$containerClasses = "hero-bg position-relative";
if ($layout === 'standard') {
  // Prevent image overflow on rounded corners
  $containerClasses .= " container rounded-3 mt-4 overflow-hidden";
}
$altText = h(!empty($hConf['title']) ? $hConf['title'] : ($pageData['post']['title'] ?? ''));
?>

<div class="mb-4 p-0 container-fluid">
  <div class="<?= $containerClasses ?>" style="<?= $layoutStyle ?>">

    <?php if ($mobileImgUrl): ?>
      <!-- Mobile image -->
      <?php if ($isFixed): ?>
        <div class="d-md-none" style="<?= $bgBaseStyle ?> background-image: url('<?= h($mobileImgUrl) ?>');"></div>
      <?php else: ?>
        <?= get_image_html($mobileImgUrlRaw, ['alt' => $altText, 'class' => 'd-md-none', 'style' => $bgBaseStyle, 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
      <?php endif; ?>

      <!-- Desktop image -->
      <?php if ($isFixed): ?>
        <?php if ($imgUrl): ?>
          <div class="d-md-block d-none" style="<?= $bgBaseStyle ?> background-image: url('<?= h($imgUrl) ?>');"></div>
        <?php endif; ?>
      <?php else: ?>
        <?php if ($imgUrl): ?>
          <?= get_image_html($imgUrlRaw, ['alt' => $altText, 'class' => 'd-md-block d-none', 'style' => $bgBaseStyle, 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
        <?php endif; ?>
      <?php endif; ?>
    <?php else: ?>
      <!-- Default image -->
      <?php if ($isFixed): ?>
        <?php if ($imgUrl): ?>
          <div class="" style="<?= $bgBaseStyle ?> background-image: url('<?= h($imgUrl) ?>');"></div>
        <?php endif; ?>
      <?php else: ?>
        <?php if ($imgUrl): ?>
          <?= get_image_html($imgUrlRaw, ['alt' => $altText, 'class' => '', 'style' => $bgBaseStyle, 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>

    <!-- Overlay -->
    <div class="top-0 position-absolute bg-dark w-100 h-100 start-0" style="opacity: <?= $overlayOpacity / 100 ?>; z-index: 1;"></div>

    <div class="position-relative text-center container" style="z-index: 2;">
      <?php $headingTag = (isset($pageType) && $pageType === 'home') ? 'h2' : 'h1'; ?>
      <?php if (!empty($hConf['title'])): ?>
        <<?= $headingTag ?> class="display-4 fw-bold mb-3 <?= $textColor ?> text-shadow"><?= h($hConf['title']) ?></<?= $headingTag ?>>
      <?php endif; ?>

      <?php if (!empty($hConf['subtext'])): ?>
        <p class="lead mb-4 <?= $textColor ?> text-shadow"><?= nl2br(h($hConf['subtext'])) ?></p>
      <?php endif; ?>

      <?php
      $buttons = [];
      if (!empty($hConf['button_text']) && !empty($hConf['button_link'])) {
        $buttons[] = [
          'text' => $hConf['button_text'],
          'url' => $hConf['button_link'],
          'style' => 'primary'
        ];
      } elseif (!empty($hConf['buttons'])) {
        $buttons = $hConf['buttons'];
      }
      ?>

      <?php if (!empty($buttons)): ?>
        <div class="d-flex flex-wrap justify-content-center gap-3">
          <?php foreach ($buttons as $btn): ?>
            <?php
            $bUrlRaw = $btn['url'] ?? '#';
            if (preg_match('/^\s*javascript:/i', $bUrlRaw)) {
              $bUrlRaw = '#';
            }

            $bStyle = $btn['style'] === 'primary' ? 'btn-danger' : 'btn-light';
            if ($btn['style'] === 'outline') $bStyle = 'btn-outline-light';
            ?>
            <a href="<?= h(resolve_url($bUrlRaw)) ?>" class="btn <?= $bStyle ?> btn-lg rounded-pill px-5 shadow">
              <?= h($btn['text']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<style>
  .text-shadow {
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
  }
</style>
