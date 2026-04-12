<?php

/**
 * topbar.php
 *
 * Render the admin layout with a top navigation bar.
 */
require_once __DIR__ . '/header.php';

$statusDot = match ($sysStatus['status']) {
  'danger' => 'bg-theme-danger',
  'warning' => 'bg-theme-warning',
  default => 'bg-theme-success',
};
$statusLabel = strtoupper($sysStatus['status']);

/** @var string $alpineSearchData */
?>

<body class="flex flex-col bg-theme-bg h-[100dvh] overflow-hidden antialiased" x-data="alpineSearchData"
  @keydown.window.prevent.cmd.k="searchOpen = true; reset(); $refs.searchInput.focus();"
  @keydown.window.prevent.ctrl.k="searchOpen = true; reset(); $refs.searchInput.focus();"
  @keydown.window.escape="searchOpen = false">

  <?php require __DIR__ . '/../views/parts/alert_installer.php'; ?>

  <!-- Main wrapper -->
  <div class="relative flex flex-col flex-1 overflow-hidden">

    <!-- Top header area -->
    <div class="z-40 relative flex flex-col bg-theme-surface shadow-theme border-theme-border border-b shrink-0">

      <!-- Upper row: Logo and tools -->
      <div class="z-50 relative border-theme-border/40 border-b h-14">
        <!-- Header -->
        <div class="px-4 sm:px-6 w-full h-full">
          <div class="flex justify-between items-center h-full">

            <!-- Logo -->
            <div class="flex flex-shrink-0 items-center mr-8">
              <?php if (isset($_GET['action']) || isset($_GET['id'])): ?>
                <a href="<?= h(basename($_SERVER['SCRIPT_NAME'])) ?>" onclick="if(history.length > 1) { history.back(); return false; }" class="md:hidden hover:bg-theme-bg mr-2 p-1.5 rounded-theme text-theme-text transition-colors" aria-label="Back">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-left"></use>
                  </svg>
                </a>
              <?php endif; ?>
              <?php $adminLogo = get_option('admin_logo'); ?>
              <a href="index.php" class="flex items-center gap-2">
                <?php if ($adminLogo):
                  $siteNameAlt = get_option('admin_title') ?: get_option('site_name') ?: CMS_NAME;
                  $logo_style = $is_dark_mode ? 'style="filter: drop-shadow(0 0 3px rgba(255,255,255,0.4));"' : ''; ?>
                  <img src="<?= h(resolve_url($adminLogo)) ?>" alt="<?= h($siteNameAlt) ?>" class="w-auto h-8 object-contain" <?= $logo_style ?>>
                  <?php if (get_option('admin_show_site_name')): ?>
                    <span class="font-bold text-theme-text text-lg uppercase tracking-tighter">
                      <?= h(get_option('admin_title') ?: get_option('site_name') ?: CMS_NAME) ?>
                    </span>
                  <?php
                  endif; ?>
                <?php
                else: ?>
                  <h1
                    class="flex items-center gap-2 font-bold text-theme-text text-lg uppercase tracking-tighter w-full min-w-0">
                    <span class="block truncate"
                      title="<?= h(get_option('admin_title') ?: get_option('site_name') ?: CMS_NAME) ?>">
                      <?= h(get_option('admin_title') ?: get_option('site_name') ?: CMS_NAME) ?>
                    </span>
                  </h1>
                <?php
                endif; ?>
              </a>
            </div>

            <!-- Right-side tools -->
            <div class="hidden md:flex items-center space-x-3">
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
              <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" @click.outside="open = false"
                  class="flex items-center gap-1.5 hover:bg-theme-bg px-3 py-1.5 border border-transparent hover:border-theme-border rounded-theme text-theme-text/60 hover:text-theme-primary transition-all"
                  title="<?= _t('create_new') ?>">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
                  </svg>
                  <span class="font-bold text-xs">
                    <?= _t('new') ?>
                  </span>
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
                    <div
                      class="flex-shrink-0 flex justify-center items-center rounded-theme w-8 h-8 bg-theme-bg text-theme-secondary group-hover:bg-theme-primary group-hover:text-theme-on-primary transition-colors">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
                      </svg>
                    </div>
                    <span>
                      <?= _t('type_post') ?>
                    </span>
                  </a>
                  <a href="posts.php?action=new&type=page"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-theme text-sm font-medium whitespace-nowrap text-theme-text hover:bg-theme-bg hover:text-theme-primary transition-all duration-200">
                    <div
                      class="flex-shrink-0 flex justify-center items-center rounded-theme w-8 h-8 bg-theme-bg text-theme-secondary group-hover:bg-theme-primary group-hover:text-theme-on-primary transition-colors">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document"></use>
                      </svg>
                    </div>
                    <span>
                      <?= _t('type_page') ?>
                    </span>
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
                  <a href="media.php?action=upload"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-theme text-sm font-medium whitespace-nowrap text-theme-text hover:bg-theme-bg hover:text-theme-primary transition-all duration-200">
                    <div
                      class="flex-shrink-0 flex justify-center items-center rounded-theme w-8 h-8 bg-theme-bg text-theme-secondary group-hover:bg-theme-primary group-hover:text-theme-on-primary transition-colors">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
                      </svg>
                    </div>
                    <span>
                      <?= _t('cat_media') ?>
                    </span>
                  </a>
                  <a href="users.php?action=new"
                    class="group flex items-center gap-3 px-3 py-2.5 rounded-theme text-sm font-medium whitespace-nowrap text-theme-text hover:bg-theme-bg hover:text-theme-primary transition-all duration-200">
                    <div
                      class="flex-shrink-0 flex justify-center items-center rounded-theme w-8 h-8 bg-theme-bg text-theme-secondary group-hover:bg-theme-primary group-hover:text-theme-on-primary transition-colors">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-user-plus"></use>
                      </svg>
                    </div>
                    <span>
                      <?= _t('lbl_user') ?>
                    </span>
                  </a>
                </div>
              </div>

              <!-- Search box -->
              <div class="group relative"
                @click="searchOpen = true; reset(); $refs.searchInput.focus();">
                <div
                  class="flex items-center bg-theme-bg px-3 py-1.5 border border-theme-border hover:border-theme-primary/50 rounded-theme transition-colors cursor-pointer">
                  <svg class="mr-2 w-4 h-4 text-theme-text/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
                  </svg>
                  <span class="mr-4 text-theme-text/50 text-xs">
                    <?= _t('search') ?>
                  </span>
                  <span
                    class="bg-theme-surface px-1.5 py-0.5 border border-theme-border rounded-theme font-mono text-[10px] text-theme-text/40"
                    x-text="shortcutLabel"></span>
                </div>
              </div>

              <div class="mx-2 bg-theme-border w-px h-6"></div>

              <?php if (function_exists('do_action')) do_action('grinds_admin_toolbar'); ?>

              <!-- Cache clear button -->
              <?php
              $show_label = true;
              $btn_class = 'flex items-center gap-1.5 hover:bg-theme-bg px-2 py-1.5 rounded-theme text-theme-text/60 hover:text-theme-primary transition-colors';
              require __DIR__ . '/../views/parts/cache_clear_button.php';
              ?>

              <!-- View site button -->
              <a href="<?= h(resolve_url('/')) ?>" target="_blank"
                class="flex items-center gap-1.5 hover:bg-theme-bg px-2 py-1.5 rounded-theme text-theme-text/60 hover:text-theme-primary transition-colors"
                title="<?= _t('view_site') ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-top-right-on-square"></use>
                </svg>
                <span class="hidden lg:inline font-bold text-xs whitespace-nowrap">
                  <?= _t('view_site') ?>
                </span>
              </a>

              <div class="relative ml-2" x-data="{ userMenuOpen: false }">
                <!-- Trigger avatar -->
                <button @click="userMenuOpen = !userMenuOpen" class="flex items-center focus:outline-none"
                  title="<?= _t('st_profile_title') ?>" aria-label="<?= _t('st_profile_title') ?>">
                  <div
                    class="flex justify-center items-center bg-theme-primary/10 border border-theme-primary/20 rounded-full hover:ring-2 hover:ring-theme-primary/50 w-8 h-8 overflow-hidden font-bold text-theme-primary text-xs transition-all">
                    <?php if (!empty($currentUser['avatar'])): ?>
                      <img src="<?= h(resolve_url($currentUser['avatar'])) ?>" alt="User"
                        class="w-full h-full object-cover">
                    <?php
                    else: ?>
                      <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
                    <?php
                    endif; ?>
                  </div>
                </button>

                <!-- Dropdown menu -->
                <div x-show="userMenuOpen" @click.outside="userMenuOpen = false"
                  x-transition:enter="transition ease-out duration-100"
                  x-transition:enter-start="transform opacity-0 scale-95"
                  x-transition:enter-end="transform opacity-100 scale-100"
                  x-transition:leave="transition ease-in duration-75"
                  x-transition:leave-start="transform opacity-100 scale-100"
                  x-transition:leave-end="transform opacity-0 scale-95"
                  class="right-0 z-50 absolute bg-theme-surface shadow-theme mt-2 border border-theme-border rounded-theme w-48 overflow-hidden"
                  style="display: none;" x-cloak>

                  <!-- User info header -->
                  <div class="bg-theme-bg/50 px-4 py-3 border-theme-border border-b">
                    <p class="opacity-60 text-theme-text text-xs">
                      <?= _t('st_username') ?>
                    </p>
                    <p class="font-bold text-theme-text text-sm truncate">
                      <?= h($currentUser['username']) ?>
                    </p>
                  </div>

                  <!-- Menu items -->
                  <?php require __DIR__ . '/../views/parts/user_dropdown.php'; ?>
                </div>
              </div>
            </div>

            <!-- Mobile toggle buttons -->
            <div class="md:hidden flex items-center gap-2 -mr-2">
              <button @click="searchOpen = true; reset(); $refs.searchInput.focus();"
                class="p-2 text-theme-text/60 hover:text-theme-primary" aria-label="<?= _t('search') ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
                </svg>
              </button>
              <button @click="mobileOpen = !mobileOpen" type="button"
                class="p-2 focus:outline-none text-theme-text hover:text-theme-primary">
                <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-bars-3"></use>
                </svg>
                <svg x-show="mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  style="display:none;">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Lower row: Navigation menu -->
      <div class="hidden md:block bg-theme-surface border-b border-theme-border/50">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
          <div class="flex items-center gap-x-4 gap-y-2 py-2">
            <?php
            $visibleLimit = 8;
            $itemCount = 0;
            $hasDropdown = false;
            foreach ($admin_menu as $key => $item):
              if ($key === 'settings' && !current_user_can('manage_settings')) {
                continue;
              }
              $navStyle = $skin['nav_style'] ?? 'underline';
              $isActive = (isset($current_page) && $current_page === $key);

              // Determine menu item classes
              if ($navStyle === 'pill') {
                // Pill style
                $baseClass = "relative flex items-center px-3 py-2 rounded-theme text-xs font-medium transition-all duration-200 whitespace-nowrap group";
                if ($isActive) {
                  $menuClass = "$baseClass bg-theme-primary text-theme-on-primary shadow-theme";
                  $iconClass = "text-theme-on-primary";
                } else {
                  $menuClass = "$baseClass text-theme-text opacity-70 hover:opacity-100 hover:bg-theme-bg/50 hover:text-theme-primary";
                  $iconClass = "opacity-70 group-hover:opacity-100 group-hover:text-theme-primary";
                }
                $indicator = '';
              } else {
                // Underline style
                $baseClass = "relative flex items-center py-2 text-sm font-medium transition-colors whitespace-nowrap group";
                if ($isActive) {
                  $menuClass = "$baseClass text-theme-primary";
                  $iconClass = "text-theme-primary";
                  $indicator = '<span class="bottom-0 left-0 absolute bg-theme-primary rounded-full w-full h-[2px]"></span>';
                } else {
                  $menuClass = "$baseClass text-theme-text/60 hover:text-theme-primary";
                  $iconClass = "opacity-70 group-hover:opacity-100";
                  $indicator = '<span class="bottom-0 left-0 absolute bg-theme-primary/0 group-hover:bg-theme-primary/30 rounded-full w-full h-[2px] transition-colors"></span>';
                }
              }

              $itemCount++;

              if ($itemCount === $visibleLimit + 1):
                $hasDropdown = true;
            ?>
                <div class="relative" x-data="{ moreOpen: false }">
                  <button @click="moreOpen = !moreOpen" @click.outside="moreOpen = false"
                    class="<?= $baseClass ?> px-3 text-theme-text/60 hover:text-theme-primary gap-1"
                    :class="{ 'text-theme-primary': moreOpen }">
                    <span>
                      <?= _t('more') ?>
                    </span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
                    </svg>
                  </button>
                  <div x-show="moreOpen" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-2"
                    class="right-0 z-50 absolute bg-theme-surface shadow-theme mt-2 p-2 border border-theme-border rounded-theme w-max min-w-[14rem]"
                    style="display: none;" x-cloak>
                  <?php
                endif;

                if ($itemCount > $visibleLimit): ?>
                    <a href="<?= $item['url'] ?>"
                      class="group flex items-center gap-3 px-3 py-2.5 rounded-theme text-sm font-medium whitespace-nowrap transition-all duration-200 <?= $isActive ? 'bg-theme-primary text-theme-on-primary shadow-theme' : 'text-theme-text hover:bg-theme-bg hover:text-theme-primary' ?>">
                      <div
                        class="flex-shrink-0 flex justify-center items-center rounded-theme w-8 h-8 transition-colors <?= $isActive ? 'bg-theme-surface text-theme-primary' : 'bg-theme-bg text-theme-secondary group-hover:bg-theme-primary group-hover:text-theme-on-primary' ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <use href="<?= h(grinds_asset_url('assets/img/sprite.svg') . '#' . $item['icon']) ?>"></use>
                        </svg>
                      </div>
                      <span>
                        <?= h($item['label']) ?>
                      </span>
                    </a>
                  <?php
                else: ?>
                    <a href="<?= $item['url'] ?>" class="<?= $menuClass ?>" title="<?= h($item['label']) ?>">
                      <svg class="w-4 h-4 mr-2 transition-transform group-hover:scale-110 <?= $iconClass ?>" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= h(grinds_asset_url('assets/img/sprite.svg') . '#' . $item['icon']) ?>"></use>
                      </svg>
                      <span>
                        <?= h($item['label']) ?>
                      </span>
                      <?= $indicator ?>
                    </a>
                  <?php
                endif;
              endforeach;

              if ($hasDropdown): ?>
                  </div>
                </div>
              <?php
              endif; ?>
          </div>
        </div>
      </div>

    </div>

    <!-- Breadcrumb header -->
    <header class="bg-theme-bg pt-6 pb-2 shrink-0">
      <!-- Main content container -->
      <div class="mx-auto px-4 sm:px-6 lg:px-8 max-w-7xl">
        <nav class="flex items-center font-medium text-theme-text/60 text-sm">
          <a href="index.php" class="flex items-center gap-1 hover:text-theme-text transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-home"></use>
            </svg>
            <?= _t('home') ?>
          </a>
          <svg class="mx-2 w-4 h-4 text-theme-text/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-right"></use>
          </svg>
          <span class="opacity-100 font-bold text-theme-text">
            <?= h($page_title ?? '') ?>
          </span>
        </nav>
      </div>
    </header>

    <main class="flex-1 bg-theme-bg scroll-smooth" :class="mobileOpen ? 'overflow-hidden' : 'overflow-y-auto'">
      <div class="mx-auto sm:px-6 lg:px-8 pt-6 pb-24 md:pb-8 max-w-7xl">
        <div class="px-4 sm:px-0">
          <?= $content ?? '' ?>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer class="hidden md:block z-20 mt-auto relative flex-shrink-0 bg-theme-bg border-theme-border border-t font-mono text-[11px] text-theme-text/60">
      <div class="flex justify-between items-center px-4 sm:px-6 w-full h-12 whitespace-nowrap">
        <div class="flex items-center gap-6">
          <?php if (current_user_can('manage_settings')): ?>
            <a href="settings.php?tab=system"
              class="flex items-center gap-2 hover:text-theme-text transition-colors cursor-pointer"
              title="<?= h($sysStatus['msg']) ?>">
              <span class="relative flex w-2 h-2"><span
                  class="<?= $statusDot === 'bg-theme-success' ? 'animate-ping-slow' : 'animate-ping' ?> absolute inline-flex h-full w-full rounded-full opacity-75 <?= $statusDot ?>"></span><span
                  class="relative inline-flex rounded-full h-2 w-2 <?= $statusDot ?>"></span></span>
              <span class="font-bold tracking-wide">
                <?= $statusLabel ?>
              </span>
            </a>
            <?php if (!in_array($licStatus, ['pro', 'agency'])): ?>
              <span class="opacity-30">|</span>
              <a href="settings.php?tab=general" class="flex items-center gap-2 hover:text-theme-text transition-colors"
                title="<?= _t('license') ?>">
                <span class="relative flex w-2 h-2"><span
                    class="inline-flex relative bg-theme-info rounded-full w-2 h-2"></span></span>
                <span class="font-bold text-theme-info tracking-wide">
                  <?= _t('trial') ?>
                </span>
              </a>
            <?php
            endif; ?>
            <span class="opacity-30">|</span>
            <?php if (in_array($licStatus, ['pro', 'agency'])): ?>
              <span class="flex items-center gap-1.5 opacity-70">GrindSite
                <span
                  class="inline-flex items-center px-1 py-px bg-theme-success/20 text-theme-success text-[9px] font-bold rounded-theme tracking-wide">PRO</span>
                <span class="font-mono text-[10px] whitespace-nowrap">v<?= h(CMS_VERSION) ?></span>
              </span>
              <?php if (!empty($hasUpdate)): ?>
                <a href="settings.php?tab=update"
                  class="flex items-center gap-1 ml-3 text-theme-primary font-bold text-xs hover:underline animate-pulse"
                  title="<?= _t('st_update_available') ?>">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                  </svg>
                  Update
                </a>
              <?php
              endif; ?>
            <?php
            else: ?>
              <span class="flex items-center gap-1 opacity-70">GrindSite
                <span class="font-mono text-[10px] whitespace-nowrap">v<?= h(CMS_VERSION) ?></span>
              </span>
              <?php if (!empty($hasUpdate)): ?>
                <a href="https://github.com/grindworks/grind-site/releases" target="_blank"
                  class="flex items-center gap-1 ml-3 text-theme-warning font-bold text-xs hover:underline"
                  title="Manual Update Required">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-top-right-on-square"></use>
                  </svg>
                  Update (Manual)
                </a>
              <?php
              endif; ?>
            <?php
            endif; ?>
          <?php endif; ?>
        </div>
        <div class="flex items-center gap-6">
          <span class="opacity-70">
            <?= h(get_option('site_footer_text') ?: CMS_NAME) ?>
          </span>
        </div>
      </div>
    </footer>

    <!-- Mobile menu -->
    <div x-show="mobileOpen" x-transition:enter="transition ease-out duration-200"
      x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
      x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
      x-transition:leave-end="opacity-0 translate-y-4"
      class="md:hidden z-50 fixed inset-0 flex flex-col bg-theme-surface" style="display: none; top: 3.5rem;" x-cloak>

      <?php require __DIR__ . '/../views/parts/mobile_menu.php'; ?>
    </div>

  </div><!-- End main wrapper -->

  <?php require_once __DIR__ . '/footer.php'; ?>
