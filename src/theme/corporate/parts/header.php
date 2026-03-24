<?php

if (!defined('GRINDS_APP')) exit;

/**
 * header.php
 * Render site header.
 */
$menus = function_exists('get_nav_menus') ? get_nav_menus('header') : [];
$logo = get_option('admin_logo');

// Fetch categories.
$cats = [];
if (empty($menus)) {
  global $pdo;
  if (isset($pdo)) {
    try {
      $cats = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
    } catch (Exception $e) {
    }
  }
}
?>
<header class="top-0 z-50 sticky bg-white shadow-sm/50 border-slate-200 border-b"
  x-data="{
    mobileOpen: false,
    searchOpen: false,
    toggleSearch() { this.searchOpen = !this.searchOpen; if(this.searchOpen) this.mobileOpen = false; },
    toggleMenu() { this.mobileOpen = !this.mobileOpen; if(this.mobileOpen) this.searchOpen = false; }
  }"
  x-init="$watch('mobileOpen', value => {
    document.body.classList.toggle('overflow-hidden', value);
    document.documentElement.classList.toggle('overflow-hidden', value);
  }); $watch('searchOpen', value => {
    if(value) $nextTick(() => $refs.searchInput.focus());
  })">
  <div class="flex justify-between items-center mx-auto px-4 h-20 container">
    <!-- Render logo. -->
    <?php $isHome = (isset($pageType) && $pageType === 'home'); ?>
    <?php if ($isHome): ?>
      <h1 class="m-0 flex items-center">
        <a href="<?= h(resolve_url('/')) ?>" class="group flex items-center gap-2 font-bold text-slate-900 text-2xl tracking-tight">
          <?php if ($logo): ?>
            <?= get_image_html(grinds_url_to_view($logo), ['alt' => h(get_option('site_name')), 'class' => 'w-auto h-8', 'loading' => 'eager']) ?>
          <?php else: ?>
            <div class="flex justify-center items-center bg-slate-900 group-hover:bg-sky-700 rounded w-8 h-8 text-white transition-colors"><?= h(mb_substr(get_option('site_name'), 0, 1)) ?></div>
          <?php endif; ?>
          <span><?= h(get_option('site_name')) ?></span>
        </a>
      </h1>
    <?php else: ?>
      <a href="<?= h(resolve_url('/')) ?>" class="group flex items-center gap-2 font-bold text-slate-900 text-2xl tracking-tight">
        <?php if ($logo): ?>
          <?= get_image_html(grinds_url_to_view($logo), ['alt' => h(get_option('site_name')), 'class' => 'w-auto h-8', 'loading' => 'eager']) ?>
        <?php else: ?>
          <div class="flex justify-center items-center bg-slate-900 group-hover:bg-sky-700 rounded w-8 h-8 text-white transition-colors"><?= h(mb_substr(get_option('site_name'), 0, 1)) ?></div>
        <?php endif; ?>
        <span><?= h(get_option('site_name')) ?></span>
      </a>
    <?php endif; ?>

    <?php
    $reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $currentPath = rtrim($reqPath, '/') ?: '/';
    $hPathRaw = parse_url(BASE_URL, PHP_URL_PATH) ?: '/';
    $homePath = rtrim($hPathRaw, '/') ?: '/';
    ?>

    <!-- Render desktop nav. -->
    <nav class="hidden md:flex items-center gap-8">
      <?php if (empty($menus)): ?>
        <a href="<?= h(resolve_url('/')) ?>" class="text-sm font-bold <?= ($currentPath === $homePath) ? 'text-sky-700' : 'text-slate-600' ?> hover:text-sky-700 transition-colors relative group">
          <?= theme_t('home') ?>
          <span class="absolute -bottom-1 left-0 <?= ($currentPath === $homePath) ? 'w-full' : 'w-0' ?> h-0.5 bg-sky-700 transition-all group-hover:w-full"></span>
        </a>
        <?php
        foreach ($cats as $c):
          $catUrl = resolve_url('category/' . $c['slug']);
          $isActive = grinds_is_menu_active($catUrl);
        ?>
          <a href="<?= h($catUrl) ?>" class="text-sm font-bold <?= $isActive ? 'text-sky-700' : 'text-slate-600' ?> hover:text-sky-700 transition-colors relative group">
            <?= h($c['name']) ?>
            <span class="absolute -bottom-1 left-0 <?= $isActive ? 'w-full' : 'w-0' ?> h-0.5 bg-sky-700 transition-all group-hover:w-full"></span>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <?php foreach ($menus as $menu): ?>
          <?php
          $menuUrl = $menu['url'];
          $isActive = false;
          if (strpos($menuUrl, '#') !== 0 && strpos($menuUrl, 'javascript:') !== 0) {
            $isActive = grinds_is_menu_active($menuUrl);
          }
          ?>
          <a href="<?= h($menu['url']) ?>" class="text-sm font-bold <?= $isActive ? 'text-sky-700' : 'text-slate-600' ?> hover:text-sky-700 transition-colors relative group" <?= $menu['is_external'] ? 'target="_blank" rel="noopener"' : '' ?>>
            <?= h($menu['label']) ?>
            <span class="absolute -bottom-1 left-0 <?= $isActive ? 'w-full' : 'w-0' ?> h-0.5 bg-sky-700 transition-all group-hover:w-full"></span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>

      <button @click.stop="toggleSearch()" class="text-slate-600 hover:text-sky-700 transition-colors" aria-label="Search">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
      </button>

      <a href="<?= h(resolve_url('contact')) ?>" class="inline-flex justify-center items-center bg-slate-900 hover:bg-slate-800 shadow-md hover:shadow-lg px-5 py-2.5 rounded font-bold text-white text-sm transition-all hover:-translate-y-0.5 transform">
        <?= theme_t('contact') ?>
      </a>
    </nav>

    <!-- Mobile actions. -->
    <div class="md:hidden flex items-center gap-4">
      <button @click="searchOpen = !searchOpen" class="text-slate-900" aria-label="Search">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
      </button>
      <button class="hover:bg-slate-100 p-2 rounded focus:outline-none text-slate-900" @click="mobileOpen = !mobileOpen">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!mobileOpen">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="mobileOpen" x-cloak>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </div>

  <!-- Search overlay. -->
  <div x-show="searchOpen" x-transition.opacity class="top-full left-0 z-40 absolute bg-white shadow-lg p-4 border-slate-200 border-b w-full" style="display: none;" @click.outside="searchOpen = false">
    <div class="mx-auto max-w-3xl container">
      <form action="<?= h(resolve_url('/')) ?>" method="get" class="relative">
        <input x-ref="searchInput" type="text" name="q" class="bg-slate-50 py-3 pr-4 pl-12 border border-slate-200 focus:border-sky-500 rounded-full focus:outline-none focus:ring-1 focus:ring-sky-500 w-full transition-all" placeholder="<?= theme_t('search_placeholder') ?>">
        <button type="submit" class="top-1/2 left-4 absolute text-slate-400 hover:text-sky-600 transition-colors -translate-y-1/2" aria-label="<?= theme_t('search') ?>">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </button>
      </form>
    </div>
  </div>

  <!-- Render mobile nav. -->
  <div x-show="mobileOpen" x-collapse class="md:hidden absolute bg-white shadow-xl p-4 border-slate-100 border-t w-full max-h-[calc(100vh-5rem)] supports-[height:100dvh]:max-h-[calc(100dvh-5rem)] overflow-y-auto" x-cloak>
    <nav class="flex flex-col gap-2">
      <?php if (empty($menus)): ?>
        <a href="<?= h(resolve_url('/')) ?>" class="text-base font-bold <?= ($currentPath === $homePath) ? 'text-sky-700 bg-slate-50' : 'text-slate-700' ?> block py-3 px-4 rounded hover:bg-slate-50 hover:text-sky-700">
          <?= theme_t('home') ?>
        </a>
        <?php foreach ($cats as $c):
          $catUrl = resolve_url('category/' . $c['slug']);
          $isActive = grinds_is_menu_active($catUrl);
        ?>
          <a href="<?= h($catUrl) ?>" class="text-base font-bold <?= $isActive ? 'text-sky-700 bg-slate-50' : 'text-slate-700' ?> block py-3 px-4 rounded hover:bg-slate-50 hover:text-sky-700">
            <?= h($c['name']) ?>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <?php foreach ($menus as $menu): ?>
          <?php
          $menuUrl = $menu['url'];
          $isActive = false;
          if (strpos($menuUrl, '#') !== 0 && strpos($menuUrl, 'javascript:') !== 0) {
            $isActive = grinds_is_menu_active($menuUrl);
          }
          ?>
          <a href="<?= h($menu['url']) ?>" class="text-base font-bold <?= $isActive ? 'text-sky-700 bg-slate-50' : 'text-slate-700' ?> block py-3 px-4 rounded hover:bg-slate-50 hover:text-sky-700" <?= $menu['is_external'] ? 'target="_blank" rel="noopener"' : '' ?>>
            <?= h($menu['label']) ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
      <a href="<?= h(resolve_url('contact')) ?>" class="block bg-slate-900 mt-2 px-4 py-3 rounded font-bold text-white text-base text-center">
        <?= theme_t('contact') ?>
      </a>
    </nav>
  </div>
</header>
