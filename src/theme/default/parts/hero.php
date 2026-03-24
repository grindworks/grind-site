<?php

/**
 * hero.php
 * Render hero section.
 */
if (empty($pageData['post']['hero_image'])) return;

$hConf = $pageData['post']['hero_settings_decoded'] ?? [];
$imgUrlRaw = $pageData['post']['hero_image'];
$imgUrl = resolve_url($imgUrlRaw);

$mobileImgUrlRaw = !empty($hConf['mobile_image']) ? $hConf['mobile_image'] : null;
$mobileImgUrl = $mobileImgUrlRaw ? resolve_url($mobileImgUrlRaw) : null;

$layout = $hConf['layout'] ?? 'standard';
$hasOverlay = !empty($hConf['overlay']);
$isFixed = !empty($hConf['fixed_bg']);

$buttons = $hConf['buttons'] ?? [];

if (empty($buttons) && !empty($hConf['btn_text'])) {
  $buttons[] = [
    'text' => $hConf['btn_text'],
    'url'  => $hConf['btn_url'],
    'style' => 'primary'
  ];
}

$containerClass = 'relative w-full overflow-hidden z-0';
$innerClass = 'container mx-auto px-4 py-20 md:py-32';
$heightClass = '';

// Adjust layout classes
if ($layout === 'fullscreen') {
  $heightClass = 'min-h-[80vh] flex items-center justify-center';
  $innerClass = 'container mx-auto px-4 text-center';
} else {
  $innerClass = 'rounded-2xl overflow-hidden py-16 md:py-24 px-6 md:px-12';
}

if ($imgUrl || $mobileImgUrl) {
  if ($hasOverlay) {
    $textColor = 'text-white';
  } else {
    $textColor = 'text-white drop-shadow-md [text-shadow:0_2px_4px_rgba(0,0,0,0.8)]';
  }
} else {
  $textColor = 'text-theme-text';
}

// Define common background styles
$bgCommonClass = "absolute inset-0 ";
if ($isFixed) {
  $bgCommonClass .= "bg-cover bg-center md:bg-fixed";
} else {
  $bgCommonClass .= "w-full h-full object-cover";
}
$radiusClass = ($layout === 'standard') ? 'rounded-2xl' : '';
$altText = !empty($hConf['title']) ? $hConf['title'] : ($pageData['post']['title'] ?? '');
?>

<div class="<?= $containerClass ?>">

  <?php if ($mobileImgUrl): ?>
    <!-- Mobile image -->
    <?php if ($isFixed): ?>
      <div class="<?= $bgCommonClass ?> <?= $radiusClass ?> md:hidden" style="background-image: url('<?= h($mobileImgUrl) ?>'); z-index: -1;">
        <?php if ($hasOverlay): ?><div class="absolute inset-0 bg-black/50 <?= $radiusClass ?>"></div><?php endif; ?>
      </div>
    <?php else: ?>
      <div class="md:hidden z-[-1] absolute inset-0">
        <?php if ($mobileImgUrl): ?>
          <?= get_image_html($mobileImgUrlRaw, ['alt' => $altText, 'class' => $bgCommonClass . ' ' . $radiusClass, 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
        <?php endif; ?>
        <?php if ($hasOverlay): ?><div class="absolute inset-0 bg-black/50 <?= $radiusClass ?>"></div><?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Desktop image -->
    <?php if ($isFixed): ?>
      <div class="<?= $bgCommonClass ?> <?= $radiusClass ?> hidden md:block" style="background-image: url('<?= h($imgUrl) ?>'); z-index: -1;">
        <?php if ($hasOverlay): ?><div class="absolute inset-0 bg-black/50 <?= $radiusClass ?>"></div><?php endif; ?>
      </div>
    <?php else: ?>
      <div class="hidden md:block z-[-1] absolute inset-0">
        <?php if ($imgUrl): ?>
          <?= get_image_html($imgUrlRaw, ['alt' => $altText, 'class' => $bgCommonClass . ' ' . $radiusClass, 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
        <?php endif; ?>
        <?php if ($hasOverlay): ?><div class="absolute inset-0 bg-black/50 <?= $radiusClass ?>"></div><?php endif; ?>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- Default image -->
    <?php if ($isFixed): ?>
      <div class="<?= $bgCommonClass ?> <?= $radiusClass ?>" style="background-image: url('<?= h($imgUrl) ?>'); z-index: -1;">
        <?php if ($hasOverlay): ?><div class="absolute inset-0 bg-black/50 <?= $radiusClass ?>"></div><?php endif; ?>
      </div>
    <?php else: ?>
      <div class="z-[-1] absolute inset-0">
        <?php if ($imgUrl): ?>
          <?= get_image_html($imgUrlRaw, ['alt' => $altText, 'class' => $bgCommonClass . ' ' . $radiusClass, 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
        <?php endif; ?>
        <?php if ($hasOverlay): ?>
          <div class="absolute inset-0 bg-black/50 <?= $radiusClass ?>"></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="relative z-10 <?= $innerClass ?> <?= $heightClass ?>">
    <div class="max-w-3xl <?= $layout === 'fullscreen' ? 'mx-auto' : '' ?>">
      <?php if (!empty($hConf['title'])): ?>
        <?php $headingTag = (isset($pageType) && $pageType === 'home') ? 'h2' : 'h1'; ?>
        <<?= $headingTag ?> class="text-3xl md:text-5xl font-bold mb-4 leading-tight <?= $textColor ?>">
          <?= h($hConf['title']) ?>
        </<?= $headingTag ?>>
      <?php endif; ?>

      <?php if (!empty($hConf['subtext'])): ?>
        <p class="text-lg md:text-xl opacity-90 mb-8 leading-relaxed <?= $textColor ?>">
          <?= nl2br(h($hConf['subtext'])) ?>
        </p>
      <?php endif; ?>

      <?php if (!empty($buttons)): ?>
        <div class="flex flex-wrap gap-4 <?= $layout === 'fullscreen' ? 'justify-center' : '' ?>">
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

            $bClass = "inline-flex items-center justify-center px-8 py-3 rounded-full font-bold shadow-lg transition-all transform hover:-translate-y-0.5 hover:shadow-xl ";

            if ($bStyle === 'primary') {
              $bClass .= "bg-grinds-red text-white hover:bg-red-700";
            } elseif ($bStyle === 'secondary') {
              $bClass .= "bg-gray-800 text-white hover:bg-gray-900";
            } elseif ($bStyle === 'white') {
              $bClass .= "bg-white text-gray-900 hover:bg-gray-100";
            } elseif ($bStyle === 'outline') {
              $bClass .= "border-2 " . ($hasOverlay ? "border-white text-white hover:bg-white hover:text-gray-900" : "border-grinds-red text-grinds-red hover:bg-grinds-red hover:text-white");
            }
            ?>
            <a href="<?= h($bUrl) ?>" class="<?= $bClass ?>">
              <?= h($bText) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
