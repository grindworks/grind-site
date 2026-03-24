<?php

if (!defined('GRINDS_APP')) exit;

/**
 * footer.php
 * Render site footer.
 */
?>
<footer class="bg-slate-900 py-16 border-slate-800 border-t text-slate-300">
  <div class="mx-auto px-6 container">
    <?php if (function_exists('display_banners')) display_banners('footer', $ctx ?? []); ?>
    <div class="gap-12 grid grid-cols-1 md:grid-cols-4 mb-12">
      <!-- Render brand. -->
      <div class="col-span-1 md:col-span-2">
        <a href="<?= h(resolve_url('/')) ?>" class="block mb-4 font-heading font-black text-white text-2xl tracking-tighter">
          <?= h(get_option('site_name', CMS_NAME)) ?>
        </a>
        <p class="mb-6 max-w-sm text-slate-400 text-sm leading-relaxed">
          <?= h(get_option('site_description')) ?>
        </p>
        <div class="flex gap-4">
          <?php $spriteUrl = resolve_url('assets/img/sprite.svg') . '?v=' . CMS_VERSION; ?>
          <!-- Render social. -->
          <a href="#" class="flex justify-center items-center bg-slate-800 hover:bg-brand-600 rounded-full w-10 h-10 text-white transition-colors" aria-label="X">
            <svg class="w-5 h-5" fill="currentColor">
              <use href="<?= $spriteUrl ?>#icon-twitter-x"></use>
            </svg>
          </a>
          <!-- Facebook -->
          <a href="#" class="flex justify-center items-center bg-slate-800 hover:bg-brand-600 rounded-full w-10 h-10 text-white transition-colors" aria-label="Facebook">
            <svg class="w-5 h-5" fill="currentColor">
              <use href="<?= $spriteUrl ?>#icon-facebook"></use>
            </svg>
          </a>
          <!-- Instagram -->
          <a href="#" class="flex justify-center items-center bg-slate-800 hover:bg-brand-600 rounded-full w-10 h-10 text-white transition-colors" aria-label="Instagram">
            <svg class="w-5 h-5" fill="currentColor">
              <use href="<?= $spriteUrl ?>#icon-instagram"></use>
            </svg>
          </a>
          <!-- LINE -->
          <a href="#" class="flex justify-center items-center bg-slate-800 hover:bg-brand-600 rounded-full w-10 h-10 text-white transition-colors" aria-label="LINE">
            <svg class="w-5 h-5" fill="currentColor">
              <use href="<?= $spriteUrl ?>#icon-line"></use>
            </svg>
          </a>
          <!-- Discord -->
          <a href="#" class="flex justify-center items-center bg-slate-800 hover:bg-brand-600 rounded-full w-10 h-10 text-white transition-colors" aria-label="Discord">
            <svg class="w-5 h-5" fill="currentColor">
              <use href="<?= $spriteUrl ?>#icon-discord"></use>
            </svg>
          </a>
        </div>
      </div>

      <!-- Render menu. -->
      <div>
        <h4 class="mb-6 font-bold text-white"><?= theme_t('menu') ?></h4>
        <ul class="space-y-3 text-sm">
          <?php $menus = function_exists('get_nav_menus') ? get_nav_menus('footer') : []; ?>
          <?php foreach ($menus as $menu): ?>
            <li><a href="<?= h($menu['url']) ?>" class="hover:text-brand-400 transition-colors" <?= $menu['is_external'] ? 'target="_blank" rel="noopener"' : '' ?>><?= h($menu['label']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Render contact info. -->
      <div>
        <h4 class="mb-6 font-bold text-white"><?= theme_t('contact_us') ?></h4>
        <ul class="space-y-3 text-slate-400 text-sm">
          <li><?= theme_t('footer_email') ?></li>
          <li><?= theme_t('footer_phone') ?></li>
          <li><?= theme_t('footer_address') ?></li>
        </ul>
      </div>
    </div>

    <div class="flex md:flex-row flex-col justify-between items-center pt-8 border-slate-800 border-t text-slate-500 text-xs">
      <p>
        <?php if ($copyright = get_option('footer_copyright')): ?>
          <?= h($copyright) ?>
        <?php else: ?>
          <?= h(get_option('site_footer_text')) ?>
        <?php endif; ?>
      </p>
      <div class="flex gap-6 mt-4 md:mt-0">
        <a href="#" class="hover:text-slate-300"><?= theme_t('link_privacy') ?></a>
        <a href="#" class="hover:text-slate-300"><?= theme_t('link_terms') ?></a>
      </div>
    </div>
  </div>
</footer>
