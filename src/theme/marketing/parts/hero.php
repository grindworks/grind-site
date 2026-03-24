<?php

/**
 * hero.php
 * Render hero section.
 */
if (!defined('GRINDS_APP')) exit;

$hConf = json_decode($pageData['post']['hero_settings'] ?? '{}', true);
$imgUrl = !empty($pageData['post']['hero_image']) ? resolve_url($pageData['post']['hero_image']) : null;

// Retrieve settings.
$mobileImgUrl = !empty($hConf['mobile_image']) ? resolve_url($hConf['mobile_image']) : null;
$isFixed = !empty($hConf['fixed_bg']);

$title = $hConf['title'] ?? '';
$sub = $hConf['subtext'] ?? '';

// Set default title.
if (empty($title)) {
  if ($pageType === 'home' && !isset($_GET['page'])) {
    $title = theme_t('hero_default_title');
  } else {
    $title = $pageData['post']['title'] ?? '';
  }
}

if (empty($sub) && $pageType === 'home' && !isset($_GET['page'])) {
  $sub = theme_t('hero_default_sub');
}

// Configure buttons.
$buttons = [];
if (!empty($hConf['button_text']) && !empty($hConf['button_link'])) {
  $buttons[] = [
    'text' => $hConf['button_text'],
    'url' => $hConf['button_link'],
    'style' => 'primary'
  ];
} elseif (!empty($hConf['buttons'])) {
  $buttons = $hConf['buttons'];
} elseif ($pageType === 'home' && !isset($_GET['page'])) {
  $buttons[] = ['text' => theme_t('btn_explore'), 'url' => '#features', 'style' => 'primary'];
  $buttons[] = ['text' => theme_t('btn_sales'), 'url' => 'contact', 'style' => 'secondary'];
}

// Determine layout.
// Check fullscreen.
$isSplitLayout = !$isFixed;

// Adjust classes.
$sectionClass = "relative overflow-hidden";
if ($isFixed) {
  $sectionClass .= " min-h-[600px] flex items-center justify-center py-20 text-center";
} else {
  $sectionClass .= " pt-32 pb-20 lg:pt-48 lg:pb-32 bg-slate-50";
}

// Define bg classes.
$bgFixedClass = "absolute inset-0 bg-cover bg-center md:bg-fixed";
?>

<section class="<?= $sectionClass ?>">

  <?php if ($isFixed): ?>
    <!-- Fixed background. -->
    <?php if ($mobileImgUrl): ?>
      <div class="<?= $bgFixedClass ?> md:hidden" style="background-image: url(<?= h(json_encode($mobileImgUrl)) ?>);"></div>
      <?php if ($imgUrl): ?>
        <div class="<?= $bgFixedClass ?> hidden md:block" style="background-image: url(<?= h(json_encode($imgUrl)) ?>);"></div>
      <?php endif; ?>
    <?php elseif ($imgUrl): ?>
      <div class="<?= $bgFixedClass ?>" style="background-image: url(<?= h(json_encode($imgUrl)) ?>);"></div>
    <?php endif; ?>

    <!-- Overlay -->
    <div class="z-0 absolute inset-0 bg-slate-900/60"></div>
  <?php else: ?>
    <!-- Decorative background. -->
    <div class="top-0 right-0 absolute bg-brand-100 opacity-50 blur-3xl -mt-20 -mr-20 rounded-full w-96 h-96 mix-blend-multiply filter"></div>
    <div class="bottom-0 left-0 absolute opacity-50 blur-3xl -mb-20 -ml-20 rounded-full w-96 h-96 bg-accent-100 mix-blend-multiply filter"></div>
  <?php endif; ?>

  <div class="z-10 relative mx-auto px-6 container">
    <div class="<?= $isSplitLayout ? 'flex flex-col lg:flex-row items-center gap-12 lg:gap-20' : '' ?>">

      <!-- Text Content -->
      <div class="<?= $isSplitLayout ? 'lg:w-1/2 text-center lg:text-left' : 'w-full max-w-4xl mx-auto' ?>">

        <?php if (!empty($imgUrl) && $pageType === 'home' && $isSplitLayout): ?>
          <span class="inline-block bg-brand-50 mb-6 px-3 py-1 border border-brand-100 rounded-full font-bold text-brand-600 text-xs uppercase tracking-wider">
            <?= theme_t('badge_new_release') ?>
          </span>
        <?php endif; ?>

        <!-- Toggle title color. -->
        <?php $headingTag = (isset($pageType) && $pageType === 'home') ? 'h2' : 'h1'; ?>
        <<?= $headingTag ?> class="text-4xl lg:text-6xl font-black leading-tight mb-6 font-heading <?= $isFixed ? 'text-white drop-shadow-md' : 'text-slate-900' ?>">
          <?= nl2br(h($title)) ?>
        </<?= $headingTag ?>>

        <?php if ($sub): ?>
          <p class="text-lg mb-8 leading-relaxed <?= $isSplitLayout ? 'max-w-2xl mx-auto lg:mx-0 text-slate-600' : 'text-slate-100 drop-shadow-sm' ?>">
            <?= nl2br(h($sub)) ?>
          </p>
        <?php endif; ?>

        <?php if (!empty($buttons)): ?>
          <div class="flex flex-col sm:flex-row gap-4 justify-center <?= $isSplitLayout ? 'lg:justify-start' : '' ?>">
            <?php foreach ($buttons as $btn):
              $bUrlRaw = $btn['url'] ?? '#';
              if (preg_match('/^\s*javascript:/i', $bUrlRaw)) {
                $bUrlRaw = '#';
              }

              $bStyle = ($btn['style'] === 'primary') ? 'bg-brand-600 text-white hover:bg-brand-700 shadow-lg hover:-translate-y-1' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50';
            ?>
              <a href="<?= h(resolve_url($bUrlRaw)) ?>" class="px-8 py-4 rounded-full font-bold text-base transition-all duration-300 <?= $bStyle ?>">
                <?= h($btn['text']) ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if ($pageType === 'home' && $isSplitLayout): ?>
          <div class="flex justify-center lg:justify-start items-center gap-6 mt-10 font-medium text-slate-500 text-sm">
            <span class="flex items-center"><svg class="mr-2 w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg> <?= theme_t('feat_no_card') ?></span>
            <span class="flex items-center"><svg class="mr-2 w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg> <?= theme_t('feat_free_trial') ?></span>
          </div>
        <?php endif; ?>
      </div>

      <!-- Image content. -->
      <?php if ($isSplitLayout): ?>
        <div class="relative lg:w-1/2">
          <?php if ($imgUrl || $mobileImgUrl): ?>
            <div class="relative shadow-2xl border-4 border-white rounded-2xl overflow-hidden rotate-2 hover:rotate-0 transition-transform duration-500 transform">

              <?php if ($mobileImgUrl): ?>
                <!-- Mobile image. -->
                <img src="<?= h($mobileImgUrl) ?>" alt="<?= h($title) ?>" class="md:hidden w-full h-auto object-cover" loading="eager" fetchpriority="high">
                <!-- Desktop image. -->
                <img src="<?= h($imgUrl ?: $mobileImgUrl) ?>" alt="<?= h($title) ?>" class="hidden md:block w-full h-auto object-cover" loading="eager" fetchpriority="high">
              <?php else: ?>
                <!-- Default image. -->
                <img src="<?= h($imgUrl) ?>" alt="<?= h($title) ?>" class="w-full h-auto object-cover" loading="eager" fetchpriority="high">
              <?php endif; ?>

              <!-- Decorative elements. -->
              <?php if ($pageType === 'home'): ?>
                <div class="-bottom-6 -left-6 absolute flex items-center gap-3 bg-white shadow-xl p-4 rounded-xl animate-bounce" style="animation-duration: 3s;">
                  <div class="flex justify-center items-center bg-green-100 rounded-full w-10 h-10 text-green-600">📈</div>
                  <div>
                    <div class="font-bold text-slate-400 text-xs"><?= theme_t('lbl_growth') ?></div>
                    <div class="font-bold text-slate-900 text-sm">+128%</div>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          <?php elseif ($pageType === 'home'): ?>
            <div class="relative flex justify-center items-center bg-gradient-to-br from-brand-500 to-indigo-700 shadow-2xl rounded-2xl aspect-square overflow-hidden text-white">
              <div class="p-10 text-center">
                <svg class="opacity-50 mx-auto mb-4 w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <p class="opacity-80 font-bold text-xl"><?= theme_t('msg_upload_hero') ?></p>
              </div>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</section>
