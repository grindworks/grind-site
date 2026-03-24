<?php

if (!defined('GRINDS_APP')) exit;

/**
 * header.php
 * Render site header.
 */
$menus = [];
if (function_exists('get_nav_menus')) {
  $menus = get_nav_menus('header');
}
$siteName = get_option('site_name', CMS_NAME);
$logo = get_option('admin_logo');

$reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$currentPath = rtrim($reqPath, '/') ?: '/';
$hPathRaw = parse_url(BASE_URL, PHP_URL_PATH) ?? '/';
$homePath = rtrim($hPathRaw, '/') ?: '/';

// Determine active state using core helper
$is_active_menu = 'grinds_is_menu_active';
$isHome = (isset($pageType) && $pageType === 'home');
?>

<!-- Mobile wrapper. -->
<div x-data="{ mobileOpen: false }" class="lg:hidden">
  <!-- Mobile header. -->
  <header class="top-0 left-0 z-50 fixed flex justify-between items-center bg-white px-6 py-4 border-gray-100 border-b w-full">
    <?php if ($isHome): ?>
      <h1 class="m-0">
        <a href="<?= h(resolve_url('/')) ?>" class="block font-serif font-bold text-xl uppercase tracking-widest">
          <?php if ($logo): ?>
            <?= get_image_html(resolve_url(grinds_url_to_view($logo)), ['alt' => h($siteName), 'class' => 'w-auto max-h-8', 'loading' => 'eager']) ?>
          <?php else: ?>
            <?= h($siteName) ?>
          <?php endif; ?>
        </a>
      </h1>
    <?php else: ?>
      <a href="<?= h(resolve_url('/')) ?>" class="block font-serif font-bold text-xl uppercase tracking-widest">
        <?php if ($logo): ?>
          <?= get_image_html(resolve_url(grinds_url_to_view($logo)), ['alt' => h($siteName), 'class' => 'w-auto max-h-8', 'loading' => 'eager']) ?>
        <?php else: ?>
          <?= h($siteName) ?>
        <?php endif; ?>
      </a>
    <?php endif; ?>
    <button @click="mobileOpen = !mobileOpen" class="focus:outline-none text-photo-black">
      <span x-show="!mobileOpen"><?= theme_t('Menu') ?></span>
      <span x-show="mobileOpen" x-cloak>&times; <?= theme_t('Close') ?></span>
    </button>
  </header>

  <!-- Mobile overlay. -->
  <div x-show="mobileOpen" class="z-40 fixed inset-0 bg-white px-6 pt-20 pb-10 overflow-y-auto"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-cloak>
    <nav class="flex flex-col gap-6 text-center">
      <?php if (empty($menus)): ?>
        <a href="<?= h(resolve_url('/')) ?>" class="text-xl font-serif hover:text-gray-500 transition <?= $is_active_menu(resolve_url('/')) ? 'text-black underline decoration-1 underline-offset-4' : 'text-gray-600' ?>"><?= theme_t('home', 'HOME') ?></a>
        <?php
        global $pdo;
        $cats = [];
        if (isset($pdo) && $pdo instanceof PDO) {
          try {
            $cats = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
          } catch (Exception $e) {
          }
        }
        foreach ($cats as $c):
          $catUrl = resolve_url('category/' . $c['slug']);
        ?>
          <a href="<?= h($catUrl) ?>" class="text-xl font-serif hover:text-gray-500 transition <?= $is_active_menu($catUrl) ? 'text-black underline decoration-1 underline-offset-4' : 'text-gray-600' ?>"><?= h($c['name']) ?></a>
        <?php endforeach; ?>
      <?php else: ?>
        <?php foreach ($menus as $menu): ?>
          <a href="<?= h($menu['url']) ?>" class="text-xl font-serif hover:text-gray-500 transition <?= $is_active_menu($menu['url']) ? 'text-black underline decoration-1 underline-offset-4' : 'text-gray-600' ?>" <?= $menu['is_external'] ? 'target="_blank" rel="noopener"' : '' ?>>
            <?= h($menu['label']) ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </nav>
  </div>
</div>

<!-- Desktop sidebar. -->
<aside class="hidden top-0 left-0 z-50 fixed lg:flex flex-col bg-white px-10 py-12 border-gray-100 border-r w-80 h-screen overflow-y-auto custom-scrollbar">
  <div class="mb-12">
    <?php if ($isHome): ?>
      <h1 class="m-0">
        <a href="<?= h(resolve_url('/')) ?>" class="block mb-2 font-serif font-bold text-2xl uppercase tracking-widest">
          <?php if ($logo): ?>
            <?= get_image_html(resolve_url(grinds_url_to_view($logo)), ['alt' => h($siteName), 'class' => 'max-w-[150px]', 'loading' => 'eager']) ?>
          <?php else: ?>
            <?= h($siteName) ?>
          <?php endif; ?>
        </a>
      </h1>
    <?php else: ?>
      <a href="<?= h(resolve_url('/')) ?>" class="block mb-2 font-serif font-bold text-2xl uppercase tracking-widest">
        <?php if ($logo): ?>
          <?= get_image_html(resolve_url(grinds_url_to_view($logo)), ['alt' => h($siteName), 'class' => 'max-w-[150px]', 'loading' => 'eager']) ?>
        <?php else: ?>
          <?= h($siteName) ?>
        <?php endif; ?>
      </a>
    <?php endif; ?>
    <p class="font-sans text-gray-400 text-xs leading-relaxed">
      <?= h(get_option('site_description')) ?>
    </p>
  </div>

  <nav class="flex flex-col flex-1 gap-4">
    <?php if (empty($menus)): ?>
      <a href="<?= h(resolve_url('/')) ?>" class="font-bold text-black hover:text-gray-500 text-sm tracking-wide transition"><?= theme_t('home', 'HOME') ?></a>
      <!-- Display categories. -->
      <?php
      global $pdo;
      $cats = [];
      if (isset($pdo) && $pdo instanceof PDO) {
        try {
          $cats = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
        } catch (Exception $e) {
        }
      }
      foreach ($cats as $c):
        $catUrl = resolve_url('category/' . $c['slug']);
      ?>
        <a href="<?= h($catUrl) ?>" class="text-sm tracking-wide hover:text-gray-500 transition <?= $is_active_menu($catUrl) ? 'font-bold text-black' : 'text-gray-500' ?>"><?= h($c['name']) ?></a>
      <?php endforeach; ?>
    <?php else: ?>
      <?php foreach ($menus as $menu): ?>
        <a href="<?= h($menu['url']) ?>" class="text-sm tracking-wide uppercase hover:text-gray-500 transition <?= $is_active_menu($menu['url']) ? 'font-bold text-black border-l-2 border-black pl-3 -ml-3.5' : 'text-gray-500' ?>" <?= $menu['is_external'] ? 'target="_blank" rel="noopener"' : '' ?>>
          <?= h($menu['label']) ?>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </nav>

  <div class="mt-auto pt-10">
    <!-- Banners -->
    <?php if (function_exists('display_banners')) display_banners('sidebar', $ctx ?? []); ?>

    <!-- Search -->
    <form action="<?= h(resolve_url('/')) ?>" method="get" class="relative mb-8">
      <input type="text" name="q" placeholder="<?= h(theme_t('search_placeholder')) ?>" class="bg-transparent py-1 pr-6 border-gray-300 focus:border-black border-b focus:outline-none w-full text-sm transition placeholder-gray-300">
      <button type="submit" class="right-0 bottom-1 absolute text-gray-400 hover:text-black transition-colors" aria-label="<?= h(theme_t('search')) ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
      </button>
    </form>

    <div class="font-sans text-[10px] text-gray-400">
      <?php if ($copyright = get_option('footer_copyright')): ?>
        <?= h($copyright) ?>
      <?php elseif ($footerText = get_option('site_footer_text')): ?>
        <?= h($footerText) ?>
      <?php else: ?>
        <?= sprintf(theme_t('copyright'), date('Y'), h($siteName)) ?>
      <?php endif; ?>
    </div>
  </div>
</aside>
