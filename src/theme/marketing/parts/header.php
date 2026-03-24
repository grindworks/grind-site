<?php

if (!defined('GRINDS_APP')) exit;

/**
 * header.php
 * Render site header.
 */
?>
<header x-data="{ scrolled: false, mobileOpen: false, searchOpen: false }"
  x-init="$watch('mobileOpen', value => {
      const shouldLock = value || searchOpen;
      document.body.classList.toggle('overflow-hidden', shouldLock);
      document.documentElement.classList.toggle('overflow-hidden', shouldLock);
  });
  $watch('searchOpen', value => {
      const shouldLock = value || mobileOpen;
      document.body.classList.toggle('overflow-hidden', shouldLock);
      document.documentElement.classList.toggle('overflow-hidden', shouldLock);
      if(value) $nextTick(() => $refs.searchInput.focus());
  })"
  @scroll.window="scrolled = (window.pageYOffset > 20)"
  class="top-0 z-50 fixed w-full transition-all duration-300"
  :class="scrolled ? 'bg-white/90 backdrop-blur-md shadow-sm py-2' : 'bg-transparent py-4'">
  <div class="relative flex justify-between items-center mx-auto px-6 container">

    <!-- Render logo. -->
    <?php $isHome = (isset($pageType) && $pageType === 'home'); ?>
    <?php if ($isHome): ?>
      <h1 class="m-0 flex items-center">
        <a href="<?= h(resolve_url('/')) ?>" class="group flex items-center gap-2 font-heading font-black text-2xl tracking-tighter">
          <div class="flex justify-center items-center bg-gradient-to-br from-brand-600 to-brand-400 shadow-lg rounded-lg w-8 h-8 text-white group-hover:scale-110 transition-transform">
            D
          </div>
          <span :class="scrolled ? 'text-slate-900' : 'text-slate-900'"><?= h(get_option('site_name', CMS_NAME)) ?></span>
        </a>
      </h1>
    <?php else: ?>
      <a href="<?= h(resolve_url('/')) ?>" class="group flex items-center gap-2 font-heading font-black text-2xl tracking-tighter">
        <div class="flex justify-center items-center bg-gradient-to-br from-brand-600 to-brand-400 shadow-lg rounded-lg w-8 h-8 text-white group-hover:scale-110 transition-transform">
          D
        </div>
        <span :class="scrolled ? 'text-slate-900' : 'text-slate-900'"><?= h(get_option('site_name', CMS_NAME)) ?></span>
      </a>
    <?php endif; ?>

    <!-- Render desktop nav. -->
    <nav class="hidden md:flex items-center space-x-8">
      <?php
      // Pre-calculate paths.
      $reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
      $currentPath = rtrim($reqPath, '/') ?: '/';
      $hPathRaw = parse_url(BASE_URL, PHP_URL_PATH) ?? '/';
      $homePath = rtrim($hPathRaw, '/') ?: '/';

      $menus = function_exists('get_nav_menus') ? get_nav_menus('header') : [];
      foreach ($menus as $menu): ?>
        <?php
        $menuUrl = $menu['url'];
        $isActive = false;

        if (strpos($menuUrl, '#') !== 0 && strpos($menuUrl, 'javascript:') !== 0) {
          $isActive = grinds_is_menu_active($menuUrl);
        }
        ?>
        <a href="<?= h($menu['url']) ?>" class="text-sm font-bold <?= $isActive ? 'text-brand-600' : 'text-slate-600' ?> hover:text-brand-600 transition-colors" <?= $menu['is_external'] ? 'target="_blank" rel="noopener"' : '' ?>>
          <?= h($menu['label']) ?>
        </a>
      <?php endforeach; ?>

      <!-- Toggle search. -->
      <button @click.stop="searchOpen = !searchOpen" class="focus:outline-none text-slate-600 hover:text-brand-600 transition-colors" aria-label="Search">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </button>

      <!-- Render CTA. -->
      <a href="<?= h(resolve_url('contact')) ?>" class="bg-slate-900 hover:bg-slate-800 shadow-md hover:shadow-lg px-5 py-2.5 rounded-full font-bold text-white text-sm transition-all">
        <?= theme_t('get_started') ?>
      </a>
    </nav>

    <!-- Mobile toggles. -->
    <div class="md:hidden flex items-center gap-4">
      <button @click.stop="searchOpen = !searchOpen" class="focus:outline-none text-slate-800">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </button>
      <button @click="mobileOpen = !mobileOpen" class="focus:outline-none text-slate-800">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </div>
  </div>

  <!-- Search overlay. -->
  <div x-show="searchOpen" x-transition.opacity
    @click.outside="searchOpen = false"
    class="top-full left-0 z-40 absolute bg-white shadow-lg px-6 py-4 border-slate-100 border-t w-full">
    <form action="<?= h(resolve_url('/')) ?>" method="get" class="flex gap-2 mx-auto max-w-3xl container grinds-search-form">
      <input x-ref="searchInput" type="text" name="q" placeholder="<?= h(theme_t('search_placeholder', 'Search...')) ?>"
        class="bg-slate-50 px-4 py-3 border border-slate-200 focus:border-brand-500 rounded-lg outline-none focus:ring-2 focus:ring-brand-200 w-full transition-all">
      <button type="submit" class="bg-brand-600 hover:bg-brand-700 px-6 py-3 rounded-lg font-bold text-white transition-colors">
        <?= theme_t('search', 'Search') ?>
      </button>
    </form>
  </div>

  <!-- Mobile nav. -->
  <div x-show="mobileOpen" x-collapse class="md:hidden top-full left-0 absolute bg-white shadow-xl border-slate-100 border-b w-full">
    <div class="flex flex-col space-y-4 px-6 py-4">
      <?php foreach ($menus as $menu): ?>
        <?php
        $menuUrl = $menu['url'];
        $isActive = false;

        if (strpos($menuUrl, '#') !== 0 && strpos($menuUrl, 'javascript:') !== 0) {
          $isActive = grinds_is_menu_active($menuUrl);
        }
        ?>
        <a href="<?= h($menu['url']) ?>" class="text-base font-bold <?= $isActive ? 'text-brand-600' : 'text-slate-700' ?> block py-2 border-b border-slate-50" <?= $menu['is_external'] ? 'target="_blank" rel="noopener"' : '' ?>><?= h($menu['label']) ?></a>
      <?php endforeach; ?>
      <a href="<?= h(resolve_url('contact')) ?>" class="bg-brand-600 py-3 rounded-lg w-full font-bold text-white text-center"><?= theme_t('contact_us') ?></a>
    </div>
  </div>
</header>
