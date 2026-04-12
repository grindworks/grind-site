<?php

/**
 * sidebar.php
 *
 * Render the admin layout with a sidebar navigation.
 */
require_once __DIR__ . '/header.php';

// Active menu item class
$activeClass = 'bg-theme-primary border-theme-primary text-theme-on-primary font-bold shadow-theme';

?>

<style>
  .skin-sidebar-text {
    color: rgb(var(--color-sidebar-text) / var(--color-sidebar-text-alpha, 1)) !important;
  }

  .skin-sidebar-muted {
    color: rgb(var(--color-sidebar-text) / calc(var(--color-sidebar-text-alpha, 1) * 0.6)) !important;
  }

  .skin-sidebar-muted:hover {
    color: rgb(var(--color-sidebar-text) / var(--color-sidebar-text-alpha, 1)) !important;
  }

  .skin-sidebar-border {
    border-color: rgb(var(--color-sidebar-border, var(--color-sidebar-text)) / var(--color-sidebar-border-alpha, calc(var(--color-sidebar-text-alpha, 1) * 0.1))) !important;
  }

  .skin-sidebar-input {
    background-color: rgb(var(--color-sidebar-input-bg, var(--color-sidebar-text)) / var(--color-sidebar-input-bg-alpha, calc(var(--color-sidebar-text-alpha, 1) * 0.05))) !important;
    border-color: rgb(var(--color-sidebar-input-border, var(--color-sidebar-border, var(--color-sidebar-text))) / var(--color-sidebar-input-border-alpha, var(--color-sidebar-border-alpha, calc(var(--color-sidebar-text-alpha, 1) * 0.1)))) !important;
  }

  .skin-sidebar-item:hover {
    background-color: rgb(var(--color-sidebar-hover-bg) / var(--color-sidebar-hover-bg-alpha, 1)) !important;
    color: rgb(var(--color-sidebar-hover-text) / var(--color-sidebar-hover-text-alpha, 1)) !important;
  }

  .skin-sidebar-item.active {
    background-color: rgb(var(--color-sidebar-active-bg) / var(--color-sidebar-active-bg-alpha, 1)) !important;
    color: rgb(var(--color-sidebar-active-text) / var(--color-sidebar-active-text-alpha, 1)) !important;
  }
</style>

<body class="flex flex-col h-[100dvh] antialiased" :class="searchOpen ? 'overflow-hidden' : ''"
  x-data="alpineSearchData"
  @keydown.window.prevent.cmd.k="searchOpen = true; reset(); $refs.searchInput.focus();"
  @keydown.window.prevent.ctrl.k="searchOpen = true; reset(); $refs.searchInput.focus();"
  @keydown.window.escape="searchOpen = false">

  <?php require __DIR__ . '/../views/parts/alert_installer.php'; ?>

  <div class="relative flex flex-1 overflow-hidden" x-data="{ sidebarOpen: false }" x-init="$watch('sidebarOpen', val => window.toggleScrollLock(val))">

    <!-- Mobile overlay -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false"
      x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0"
      x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300"
      x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
      class="md:hidden z-40 absolute inset-0 backdrop-blur-sm skin-modal-overlay" style="display: none;"></div>

    <!-- Sidebar navigation -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
      class="left-0 z-50 absolute md:relative inset-y-0 flex flex-col bg-theme-sidebar shadow-theme md:shadow-none skin-sidebar-border border-r w-80 md:w-72 max-w-[85vw] transition-transform -translate-x-full md:translate-x-0 duration-300 ease-in-out skin-sidebar-text">

      <!-- Sidebar header -->
      <div class="flex justify-between items-center px-6 skin-sidebar-border border-b h-16 shrink-0">
        <?php $adminLogo = get_option('admin_logo'); ?>
        <a href="index.php" class="flex items-center gap-2">
          <?php if ($adminLogo):
            $logo_style = $is_dark_mode ? 'style="filter: drop-shadow(0 0 3px rgba(255,255,255,0.4));"' : ''; ?>
            <img src="<?= h(resolve_url($adminLogo)) ?>" alt="Admin Logo" class="w-auto h-8 object-contain" <?= $logo_style ?>>
            <?php if (get_option('admin_show_site_name')): ?>
              <span class="font-bold text-theme-primary text-lg uppercase tracking-tighter">
                <?= h(get_option('admin_title') ?: get_option('site_name') ?: CMS_NAME) ?>
              </span>
            <?php
            endif; ?>
          <?php
          else: ?>
            <h1 class="font-bold text-xl uppercase tracking-tighter w-full min-w-0">
              <span class="text-theme-primary block truncate"
                title="<?= h(get_option('admin_title') ?: get_option('site_name') ?: CMS_NAME) ?>">
                <?= h(get_option('admin_title') ?: get_option('site_name') ?: CMS_NAME) ?>
              </span>
            </h1>
          <?php
          endif; ?>
        </a>
        <button @click="sidebarOpen = false" class="md:hidden hover:bg-white/10 p-1 rounded-theme transition-colors"
          title="<?= _t('close_menu') ?>" aria-label="<?= _t('close_menu') ?>">
          <svg class="opacity-70 w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
          </svg>
        </button>
      </div>

      <!-- Search button -->
      <div class="px-4 pt-4 pb-2 shrink-0">
        <button @click="searchOpen = true; sidebarOpen = false; reset(); $refs.searchInput.focus();"
          class="group flex justify-between items-center shadow-theme px-3 py-2.5 border skin-sidebar-border rounded-theme w-full text-sm transition-colors skin-sidebar-input skin-sidebar-muted"
          title="<?= _t('search') ?>">
          <div class="flex items-center"><svg class="opacity-70 mr-3 w-4 h-4" fill="none" stroke="currentColor"
              viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
            </svg>
            <span class="font-medium">
              <?= _t('search') ?>
            </span>
          </div>
          <div class="flex items-center"><kbd
              class="hidden sm:inline-block opacity-60 px-1.5 py-0.5 border skin-sidebar-border rounded-theme font-sans font-medium text-[10px] skin-sidebar-input"
              x-text="shortcutLabel"></kbd></div>
        </button>
      </div>

      <!-- Main navigation -->
      <nav class="flex-1 space-y-1 px-3 py-3 overflow-y-auto custom-scrollbar">
        <?php foreach ($admin_menu as $key => $item):
          $isActive = (isset($current_page) && $current_page === $key); ?>
          <a href="<?= $item['url'] ?>"
            class="flex items-center px-4 py-3.5 rounded-theme transition-all group border skin-sidebar-item <?= $isActive ? 'active ' . $activeClass : 'border-transparent skin-sidebar-muted' ?>">
            <svg
              class="w-5 h-5 mr-3 transition-colors <?= $isActive ? 'opacity-100' : 'opacity-70 group-hover:opacity-100' ?>"
              fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= h(grinds_asset_url('assets/img/sprite.svg') . '#' . $item['icon']) ?>"></use>
            </svg>
            <span class="font-bold text-sm">
              <?= h($item['label']) ?>
            </span>
          </a>
        <?php
        endforeach; ?>
      </nav>

      <!-- Sidebar footer -->
      <div class="mt-auto space-y-4 bg-theme-bg/10 p-4 skin-sidebar-border border-t shrink-0">
        <?php require __DIR__ . '/../views/parts/sidebar_footer.php'; ?>
      </div>
    </aside>

    <!-- Main content -->
    <div class="flex flex-col flex-1 overflow-hidden" :class="sidebarOpen ? 'overflow-hidden' : ''">
      <header
        class="top-0 z-30 sticky flex justify-between items-center bg-theme-surface shadow-theme px-4 sm:px-6 border-theme-border border-b h-16">
        <div class="flex items-center">
          <button @click="sidebarOpen = !sidebarOpen"
            class="md:hidden hover:bg-theme-bg mr-4 p-2 rounded-theme focus:outline-none text-theme-text transition-colors"
            title="<?= _t('toggle_sidebar') ?>" aria-label="<?= _t('toggle_sidebar') ?>">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-bars-3"></use>
            </svg>
          </button>
          <?php if (isset($_GET['action']) || isset($_GET['id'])): ?>
            <a href="<?= h(basename($_SERVER['SCRIPT_NAME'])) ?>" onclick="if(history.length > 1) { history.back(); return false; }" class="md:hidden hover:bg-theme-bg mr-2 p-2 rounded-theme focus:outline-none text-theme-text transition-colors" aria-label="Back">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-left"></use>
              </svg>
            </a>
          <?php endif; ?>
          <nav class="hidden sm:flex items-center font-medium text-theme-text/60 text-sm">
            <a href="index.php" class="flex items-center gap-1 hover:text-theme-text transition-colors">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-home"></use>
              </svg>
              <?= _t('home') ?>
            </a>
            <svg class="mx-2 w-4 h-4 text-theme-text/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-right"></use>
            </svg>
            <span class="font-bold text-theme-text">
              <?= h($page_title ?? '') ?>
            </span>
          </nav>
          <span class="sm:hidden font-bold text-theme-text">
            <?= h($page_title ?? '') ?>
          </span>
        </div>

        <div class="flex items-center space-x-3">
          <?php
          $activeTheme = function_exists('get_option') ? get_option('site_theme', 'default') : 'default';
          $themeJsonPath = defined('ROOT_PATH') ? ROOT_PATH . '/theme/' . $activeTheme . '/theme.json' : '';
          $customPostTypes = [];
          if ($themeJsonPath && file_exists($themeJsonPath)) {
            $tData = json_decode(file_get_contents($themeJsonPath), true);
            if (!empty($tData['post_types'])) {
              foreach ($tData['post_types'] as $ptKey => $ptConfig) {
                if ($ptKey !== 'post' && $ptKey !== 'page') {
                  $customPostTypes[$ptKey] = $ptConfig;
                }
              }
            }
          }
          ?>
          <!-- Quick Create Dropdown -->
          <div class="relative hidden sm:block" x-data="{ open: false }">
            <button @click="open = !open" @click.outside="open = false"
              class="flex items-center gap-1.5 hover:bg-theme-bg px-3 py-1.5 border border-transparent hover:border-theme-border rounded-theme text-theme-text/60 hover:text-theme-primary transition-all"
              title="<?= _t('create_new') ?>">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
              </svg>
              <span class="font-bold text-xs"><?= _t('new') ?></span>
            </button>
            <div x-show="open" x-transition:enter="transition ease-out duration-100"
              x-transition:enter-start="transform opacity-0 scale-95"
              x-transition:enter-end="transform opacity-100 scale-100"
              x-transition:leave="transition ease-in duration-75"
              x-transition:leave-start="transform opacity-100 scale-100"
              x-transition:leave-end="transform opacity-0 scale-95"
              class="right-0 z-50 absolute bg-theme-surface shadow-theme mt-2 p-2 border border-theme-border rounded-theme w-max min-w-[12rem]"
              style="display: none;" x-cloak>
              <a href="posts.php?action=new"
                class="group flex items-center gap-3 px-3 py-2.5 rounded-theme text-sm font-medium whitespace-nowrap text-theme-text hover:bg-theme-bg hover:text-theme-primary transition-all duration-200">
                <div class="flex-shrink-0 flex justify-center items-center rounded-theme w-8 h-8 bg-theme-bg text-theme-secondary group-hover:bg-theme-primary group-hover:text-theme-on-primary transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
                  </svg>
                </div>
                <span><?= _t('type_post') ?></span>
              </a>
              <a href="posts.php?action=new&type=page"
                class="group flex items-center gap-3 px-3 py-2.5 rounded-theme text-sm font-medium whitespace-nowrap text-theme-text hover:bg-theme-bg hover:text-theme-primary transition-all duration-200">
                <div class="flex-shrink-0 flex justify-center items-center rounded-theme w-8 h-8 bg-theme-bg text-theme-secondary group-hover:bg-theme-primary group-hover:text-theme-on-primary transition-colors">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document"></use>
                  </svg>
                </div>
                <span><?= _t('type_page') ?></span>
              </a>
              <?php foreach ($customPostTypes as $ptKey => $ptConfig):
                $ptIcon = $ptConfig['icon'] ?? 'outline-document-text';
                $ptLabel = $ptConfig['label'] ?? ucfirst($ptKey);
              ?>
                <a href="posts.php?action=new&type=<?= h($ptKey) ?>" class="group flex items-center gap-3 px-3 py-2.5 rounded-theme text-sm font-medium whitespace-nowrap text-theme-text hover:bg-theme-bg hover:text-theme-primary transition-all duration-200">
                  <div class="flex-shrink-0 flex justify-center items-center rounded-theme w-8 h-8 bg-theme-bg text-theme-secondary group-hover:bg-theme-primary group-hover:text-theme-on-primary transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= h(grinds_asset_url('assets/img/sprite.svg') . '#' . $ptIcon) ?>"></use>
                    </svg>
                  </div>
                  <span><?= h($ptLabel) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </div>

          <?php if (function_exists('do_action')) do_action('grinds_admin_toolbar'); ?>
          <!-- Cache clear button -->
          <?php
          $wrapper_class = 'hidden sm:block';
          require __DIR__ . '/../views/parts/cache_clear_button.php';
          ?>

          <a href="<?= h(resolve_url('/')) ?>" target="_blank"
            class="group flex items-center bg-theme-primary/10 hover:bg-theme-primary shadow-theme px-3 py-1.5 rounded-full font-bold text-theme-primary hover:text-theme-on-primary text-xs transition-all"
            title="<?= _t('view_site') ?>">
            <svg class="mr-1.5 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-top-right-on-square"></use>
            </svg>
            <span class="hidden sm:inline">
              <?= _t('view_site') ?>
            </span>
            <span class="sm:hidden">
              <?= _t('view_site') ?>
            </span>
          </a>
        </div>
      </header>

      <main class="flex-1 bg-theme-bg overflow-x-hidden scroll-smooth" :class="sidebarOpen ? 'overflow-hidden' : 'overflow-y-auto'">
        <div class="mx-auto px-4 sm:px-6 py-8 pb-24 md:pb-8 max-w-7xl container">
          <?= $content ?? '' ?>
        </div>
      </main>
    </div>
  </div>

  <?php require_once __DIR__ . '/footer.php'; ?>
