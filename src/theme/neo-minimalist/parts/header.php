<?php

/**
 * header.php
 * Render site header.
 */
// Retrieve header navigation menus
$headerMenus = [];
if (function_exists('get_nav_menus')) {
  $headerMenus = get_nav_menus('header');
}

// Unified navigation logic
$navItems = [];
if (!empty($headerMenus)) {
  foreach ($headerMenus as $m) {
    $navItems[] = [
      'label' => $m['label'],
      'url' => $m['url'],
      'external' => $m['is_external']
    ];
  }
} else {
  // Fallback: Categories
  $homeUrl = resolve_url('/');
  $navItems[] = ['label' => theme_t('home'), 'url' => $homeUrl, 'external' => false];

  if (function_exists('neo_minimalist_get_categories')) {
    $cats = neo_minimalist_get_categories();
    foreach ($cats as $c) {
      $navItems[] = [
        'label' => $c['name'],
        'url' => resolve_url('category/' . $c['slug']),
        'external' => false
      ];
    }
  }
}
?>
<header class="top-0 z-40 sticky bg-white border-b-2 border-slate-900 text-slate-900" x-data="{
      searchOpen: false,
      mobileOpen: false,
      shortcutLabel: navigator.userAgent.indexOf('Mac') !== -1 ? '⌘K' : 'Ctrl K',

      toggleSearch() {
          this.searchOpen = true;
          this.mobileOpen = false;
          this.$nextTick(() => this.$refs.searchInput.focus());
      },
      toggleMenu() {
          this.mobileOpen = !this.mobileOpen;
          if(this.mobileOpen) this.searchOpen = false;
      }
  }" x-init="$watch('searchOpen', value => {
      const shouldLock = value || this.mobileOpen;
      document.body.classList.toggle('overflow-hidden', shouldLock);
      document.documentElement.classList.toggle('overflow-hidden', shouldLock);
  });
  $watch('mobileOpen', value => {
      const shouldLock = value || this.searchOpen;
      document.body.classList.toggle('overflow-hidden', shouldLock);
      document.documentElement.classList.toggle('overflow-hidden', shouldLock);
  })" @resize.window="if(window.innerWidth >= 768) { mobileOpen = false; }"
  @keydown.window.escape="searchOpen = false; mobileOpen = false" @keydown.window.prevent.cmd.k="toggleSearch()"
  @keydown.window.prevent.ctrl.k="toggleSearch()">

  <div class="mx-auto px-4 container">
    <div class="z-50 relative flex justify-between items-center h-16">

      <?php $isHome = (isset($pageType) && $pageType === 'home'); ?>
      <?php if ($isHome): ?>
        <h1 class="m-0 flex items-center">
          <a href="<?= h(resolve_url('/')) ?>"
            class="flex items-center gap-2 font-heading font-black hover:text-brand-600 text-2xl uppercase tracking-tighter transition-colors"
            aria-label="Home">
            <?= h(get_option('site_name', CMS_NAME)) ?>
          </a>
        </h1>
      <?php
      else: ?>
        <a href="<?= h(resolve_url('/')) ?>"
          class="flex items-center gap-2 font-heading font-black hover:text-brand-600 text-2xl uppercase tracking-tighter transition-colors"
          aria-label="Home">
          <?= h(get_option('site_name', CMS_NAME)) ?>
        </a>
      <?php
      endif; ?>

      <nav class="hidden md:flex items-center space-x-6" aria-label="Main Navigation">
        <?php foreach ($navItems as $item): ?>
          <?php
          // Determine active state for menu items
          $isActive = grinds_is_menu_active($item['url']);

          $activeClass = $isActive ? 'text-brand-600 font-bold border-b-2 border-brand-600' : 'text-slate-600 hover:text-slate-900';
          $ariaCurrent = $isActive ? 'aria-current="page"' : '';
          ?>
          <a href="<?= h($item['url']) ?>"
            class="text-sm font-heading font-bold uppercase tracking-wider transition-colors pb-1 <?= $activeClass ?>"
            <?= $item['external'] ? 'target="_blank" rel="noopener"' : '' ?>
            <?= $ariaCurrent ?>>
            <?= h($item['label']) ?>
          </a>
        <?php
        endforeach; ?>

        <button @click="toggleSearch()" aria-label="<?= h(theme_t('search')) ?>"
          class="group flex items-center space-x-2 bg-slate-50 hover:bg-brand-50 border-2 border-slate-900 px-3 py-1.5 font-bold text-slate-900 text-sm transition-all shadow-[2px_2px_0_0_rgba(15,23,42,1)] hover:shadow-[4px_4px_0_0_rgba(15,23,42,1)] hover:-translate-y-0.5 hover:-translate-x-0.5">
          <svg class="w-4 h-4 group-hover:text-brand-600 transition-colors stroke-2" fill="none" stroke="currentColor"
            viewBox="0 0 24 24" aria-hidden="true">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
          </svg>
          <span class="hidden lg:inline">
            <?= theme_t('search') ?>
          </span>
          <span
            class="hidden lg:inline bg-slate-200 border-2 border-slate-900 px-1.5 py-0.5 font-bold text-slate-700 text-[10px]"
            x-text="shortcutLabel"></span>
        </button>

        <a href="<?= h(resolve_url('admin/')) ?>" class="neo-btn neo-btn-primary py-1.5 px-4 text-sm whitespace-nowrap">
          <?= isset($_SESSION['admin_logged_in']) ? theme_t('admin_dashboard') : theme_t('admin_login') ?>
        </a>
      </nav>

      <div class="md:hidden flex items-center gap-3">
        <button @click="toggleSearch()" aria-label="<?= h(theme_t('search')) ?>"
          class="p-2 border-2 border-slate-900 bg-white shadow-sharp focus:outline-none text-slate-900 hover:bg-brand-50 transition active:translate-y-px active:translate-x-px">
          <svg class="w-5 h-5 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
          </svg>
        </button>

        <button @click="toggleMenu()" aria-label="Toggle Menu"
          class="p-2 border-2 border-slate-900 bg-brand-500 shadow-sharp focus:outline-none text-slate-900 transition active:translate-y-px active:translate-x-px"
          :aria-expanded="mobileOpen">
          <svg x-show="!mobileOpen" class="w-6 h-6 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-bars-3"></use>
          </svg>
          <svg x-show="mobileOpen" class="w-6 h-6 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
            style="display:none;">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
          </svg>
        </button>
      </div>
    </div>
  </div>

  <div x-show="searchOpen" class="z-50 fixed inset-0 overflow-y-auto" role="dialog" aria-modal="true"
    aria-label="Search" style="display: none;">

    <div x-show="searchOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
      x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
      x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
      class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="searchOpen = false">
    </div>

    <div class="flex justify-center items-start p-4 sm:p-6 pt-[10vh] min-h-full">
      <div x-show="searchOpen" @click.stop x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 scale-95 translate-y-4"
        class="relative bg-white shadow-2xl border border-gray-200 rounded-xl w-full max-w-2xl overflow-hidden transition-all transform">

        <?php
        $searchAction = (defined('GRINDS_IS_SSG') && GRINDS_IS_SSG) ? 'search.html' : resolve_url('/');
        ?>
        <form action="<?= $searchAction ?>" method="get" class="relative flex items-center grinds-search-form">
          <div class="left-0 absolute inset-y-0 flex items-center pl-5 pointer-events-none">
            <svg class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
            </svg>
          </div>

          <input x-ref="searchInput" type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>"
            aria-label="<?= h(theme_t('search_placeholder')) ?>"
            class="bg-transparent py-4 pr-14 pl-14 border-0 focus:ring-0 w-full h-20 font-medium text-gray-800 text-xl placeholder-gray-500"
            placeholder="<?= h(theme_t('search_placeholder')) ?>" autocomplete="off">

          <button type="button" @click="searchOpen = false" aria-label="Close Search"
            class="right-0 absolute inset-y-0 flex items-center pr-5 text-gray-500 hover:text-gray-700 transition-colors cursor-pointer">
            <span
              class="bg-gray-100 hover:bg-gray-200 px-2 py-1 border border-gray-200 rounded font-bold text-[10px] text-gray-600 transition">ESC</span>
          </button>
        </form>

        <div
          class="flex justify-between items-center bg-gray-50 px-5 py-3 border-gray-100 border-t text-gray-600 text-xs">
          <span class="hidden sm:inline">
            <?= theme_t('search_hint') ?>
          </span>
          <span class="sm:hidden">
            <?= theme_t('search_placeholder') ?>
          </span>
          <div class="flex items-center gap-2">
            <span class="font-bold text-gray-400 tracking-widest">GrindSite</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div x-show="mobileOpen" @click="toggleMenu()" x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-30 bg-black/50 backdrop-blur-sm transition-opacity md:hidden" style="display: none;"
    aria-hidden="true">
  </div>

  <div x-show="mobileOpen" x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
    class="md:hidden top-16 left-0 z-50 absolute bg-grinds-dark/95 shadow-2xl backdrop-blur-xl border-white/10 border-b w-full origin-top max-h-[calc(100vh-4rem)] supports-[height:100dvh]:max-h-[calc(100dvh-4rem)] overflow-y-auto"
    style="display: none;">

    <div class="space-y-2 p-4">
      <?php foreach ($navItems as $item): ?>
        <?php
        // Determine active state for mobile menu items
        $isActive = grinds_is_menu_active($item['url']);
        $activeClassMobile = $isActive ? 'bg-white/10 text-white font-bold border-l-4 border-grinds-red pl-3' : 'text-gray-100 hover:bg-white/10 hover:text-white pl-4';
        ?>
        <a href="<?= h($item['url']) ?>"
          class="block py-3 rounded-r-lg text-base transition-colors <?= $activeClassMobile ?>" <?= $item['external']
                                                                                                  ? 'target="_blank" rel="noopener"' : '' ?>>
          <?= h($item['label']) ?>
        </a>
      <?php
      endforeach; ?>

      <div class="my-2 pt-2 border-white/10 border-t">
        <a href="<?= h(resolve_url('admin/')) ?>"
          class="flex items-center hover:bg-white/5 px-4 py-3 rounded-lg font-bold text-grinds-red text-sm">
          <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-user-circle"></use>
          </svg>
          <?= isset($_SESSION['admin_logged_in']) ? theme_t('admin_dashboard') : theme_t('admin_login') ?>
        </a>
      </div>
    </div>
  </div>
</header>
