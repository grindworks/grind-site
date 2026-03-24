<?php

/**
 * hero.php
 * Render hero section.
 */
if (empty($pageData['post']['hero_image']))
  return;

$hConf = $pageData['post']['hero_settings_decoded'] ?? [];
$imgUrl = resolve_url($pageData['post']['hero_image']);
// Retrieve mobile image and fixed background settings
$mobileImgUrl = !empty($hConf['mobile_image']) ? resolve_url($hConf['mobile_image']) : null;

$layout = $hConf['layout'] ?? 'standard';
$hasOverlay = !empty($hConf['overlay']);
$isFixed = !empty($hConf['fixed_bg']);

$buttons = $hConf['buttons'] ?? [];

if (empty($buttons) && !empty($hConf['btn_text'])) {
  $buttons[] = [
    'text' => $hConf['btn_text'],
    'url' => $hConf['btn_url'],
    'style' => 'primary'
  ];
}

$containerClass = 'relative w-full overflow-hidden';
$innerClass = 'container mx-auto px-4 py-20 md:py-32';
$heightClass = '';

// Adjust layout classes
if ($layout === 'fullscreen') {
  $heightClass = 'min-h-[80vh] flex items-center justify-center';
  $innerClass = 'container mx-auto px-4 text-center';
}
else {
  // Adjust for standard and wide layouts
  if ($layout === 'standard') {
    $containerClass = 'container mx-auto px-4 mt-12 mb-12 relative';
  }
  $innerClass = 'rounded-none border-2 border-slate-900 shadow-sharp bg-brand-50 overflow-hidden py-16 md:py-24 px-6 md:px-12';
}

$textColor = 'text-slate-900';

// Define common background styles
$bgCommonClass = "absolute inset-0 ";
if ($isFixed) {
  $bgCommonClass .= "bg-cover bg-center bg-fixed";
}
else {
  $bgCommonClass .= "w-full h-full object-cover";
}
$radiusClass = ($layout === 'standard') ? 'rounded-none' : '';
$altText = h(!empty($hConf['title']) ? $hConf['title'] : ($pageData['post']['title'] ?? ''));
?>

<div class="<?= $containerClass?>">

  <?php if ($mobileImgUrl): ?>
  <!-- Mobile image -->
  <?php if ($isFixed): ?>
  <div class="<?= $bgCommonClass?> <?= $radiusClass?> md:hidden"
    style="background-image: url('<?= h($mobileImgUrl)?>'); z-index: -1;">
    <?php if ($hasOverlay): ?>
    <div class="absolute inset-0 bg-black/50 <?= $radiusClass?>"></div>
    <?php
    endif; ?>
  </div>
  <?php
  else: ?>
  <div class="md:hidden z-[-1] absolute inset-0">
    <?php if ($mobileImgUrl): ?>
    <?= get_image_html($mobileImgUrl, ['alt' => $altText, 'class' => $bgCommonClass . ' ' . $radiusClass, 'loading' => 'eager', 'fetchpriority' => 'high'])?>
    <?php
    endif; ?>
    <?php if ($hasOverlay): ?>
    <div class="absolute inset-0 bg-black/50 <?= $radiusClass?>"></div>
    <?php
    endif; ?>
  </div>
  <?php
  endif; ?>

  <!-- Desktop image -->
  <?php if ($isFixed): ?>
  <div class="<?= $bgCommonClass?> <?= $radiusClass?> hidden md:block"
    style="background-image: url('<?= h($imgUrl)?>'); z-index: -1;">
    <?php if ($hasOverlay): ?>
    <div class="absolute inset-0 bg-black/50 <?= $radiusClass?>"></div>
    <?php
    endif; ?>
  </div>
  <?php
  else: ?>
  <div class="hidden md:block z-[-1] absolute inset-0">
    <?php if ($imgUrl): ?>
    <?= get_image_html($imgUrl, ['alt' => $altText, 'class' => $bgCommonClass . ' ' . $radiusClass, 'loading' => 'eager', 'fetchpriority' => 'high'])?>
    <?php
    endif; ?>
    <?php if ($hasOverlay): ?>
    <div class="absolute inset-0 bg-black/50 <?= $radiusClass?>"></div>
    <?php
    endif; ?>
  </div>
  <?php
  endif; ?>

  <?php
else: ?>
  <!-- Default image -->
  <?php if ($isFixed): ?>
  <div class="<?= $bgCommonClass?> <?= $radiusClass?>"
    style="background-image: url('<?= h($imgUrl)?>'); z-index: -1;">
    <?php if ($hasOverlay): ?>
    <div class="absolute inset-0 bg-black/50 <?= $radiusClass?>"></div>
    <?php
    endif; ?>
  </div>
  <?php
  else: ?>
  <div class="z-[-1] absolute inset-0">
    <?php if ($imgUrl): ?>
    <?= get_image_html($imgUrl, ['alt' => $altText, 'class' => $bgCommonClass . ' ' . $radiusClass, 'loading' => 'eager', 'fetchpriority' => 'high'])?>
    <?php
    endif; ?>
    <?php if ($hasOverlay): ?>
    <div class="absolute inset-0 bg-black/50 <?= $radiusClass?>"></div>
    <?php
    endif; ?>
  </div>
  <?php
  endif; ?>
  <?php
endif; ?>

  <div class="relative z-10 <?= $innerClass?> <?= $heightClass?>">
    <div class="max-w-3xl <?= $layout === 'fullscreen' ? 'mx-auto' : ''?>">
      <?php if (!empty($hConf['title'])): ?>
      <?php $headingTag = (isset($pageType) && $pageType === 'home') ? 'h2' : 'h1'; ?>
      <<?= $headingTag?> class="text-4xl md:text-6xl font-heading font-extrabold tracking-tight mb-6 leading-[1.15]
        <?= $textColor?>">
        <?= h($hConf['title'])?>
      </<?= $headingTag?>>
      <?php
endif; ?>

      <?php if (!empty($hConf['subtext'])): ?>
      <p class="text-lg md:text-xl font-medium mb-10 leading-relaxed <?= $textColor?>">
        <?= nl2br(h($hConf['subtext']))?>
      </p>
      <?php
endif; ?>

      <?php if (!empty($buttons)): ?>
      <div class="flex flex-wrap gap-4 <?= $layout === 'fullscreen' ? 'justify-center' : ''?>">
        <?php foreach ($buttons as $btn): ?>
        <?php
    $bText = $btn['text'] ?? '';

    $bUrlRaw = $btn['url'] ?? '#';
    // Security: Prevent javascript: scheme
    if (preg_match('/^\s*javascript:/i', $bUrlRaw)) {
      $bUrlRaw = '#';
    }
    $bUrl = function_exists('resolve_url') ? resolve_url($bUrlRaw) : $bUrlRaw;

    $bStyle = $btn['style'] ?? 'primary';

    $bClass = "neo-btn ";

    if ($bStyle === 'primary') {
      $bClass .= "neo-btn-primary";
    }
    elseif ($bStyle === 'secondary') {
      $bClass .= "neo-btn-secondary";
    }
    elseif ($bStyle === 'white') {
      $bClass .= "neo-btn-secondary";
    }
    else {
      $bClass .= "neo-btn-dark";
    }
?>
        <a href="<?= h($bUrl)?>" class="<?= $bClass?>">
          <?= h($bText)?>
        </a>
        <?php
  endforeach; ?>
      </div>
      <?php
endif; ?>
    </div>
  </div>
</div>
