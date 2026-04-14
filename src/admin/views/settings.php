<?php if (!defined('GRINDS_APP')) exit;

/**
 * settings.php
 *
 * Renders the user interface for managing system settings.
 */

// Format data for JS.
foreach ($tabs as $k => $v) {
  $tabs[$k]['label'] = _t($v['label']);
}

// Check for updates
$hasUpdate = false;
$latestVersion = get_option('latest_version');
if ($latestVersion && version_compare($latestVersion, CMS_VERSION, '>')) {
  $hasUpdate = true;
}
?>

<script src="<?= grinds_asset_url('assets/js/admin_settings.js') ?>"></script>
<script src="<?= grinds_asset_url('assets/js/admin_form_unsaved.js') ?>"></script>
<script>
  window.grindsSettingsData = {
    activeTab: <?= json_encode($init_tab) ?>,
    tabs: <?= json_encode($tabs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '{}' ?>,
    hasUpdate: <?= json_encode($hasUpdate) ?>
  };
</script>

<div class="w-full"
  x-data='{
        activeTab: window.grindsSettingsData.activeTab,
        mobileMenuOpen: false,
        tabs: window.grindsSettingsData.tabs,
        hasUpdate: window.grindsSettingsData.hasUpdate,

        changeTab(key) {
            // Check for unsaved changes before allowing tab switch
            if (document.title.startsWith("* ")) {
                const msg = window.grindsTranslations?.confirm_reset || "You have unsaved changes. Discard them and switch tabs?";
                if (!confirm(msg)) {
                    return;
                }
                window.grindsBypassUnload = true;
                window.location.href = "?tab=" + key;
                return;
            }

            this.activeTab = key;
            this.mobileMenuOpen = false;
            const url = new URL(window.location);
            url.searchParams.set("tab", key);
            window.history.replaceState({}, "", url);
            window.scrollTo({ top: 0, behavior: "smooth" });
        }
  }'>

  <!-- Page header. -->
  <div class="flex sm:flex-row flex-col justify-between sm:items-center gap-4 mb-6">
    <div>
      <h2 class="flex items-center gap-2 font-bold text-theme-text text-xl whitespace-nowrap">
        <?php if (count($tabs) > 1): ?>
          <svg class="w-6 h-6 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-cog-6-tooth"></use>
          </svg>
          <?= _t('menu_settings') ?>
        <?php else: ?>
          <svg class="w-6 h-6 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-user-circle"></use>
          </svg>
          <?= _t('st_profile_title') ?>
        <?php endif; ?>
      </h2>
      <p class="opacity-60 mt-1 ml-8 text-theme-text text-sm">
        <?= (count($tabs) > 1) ? _t('st_settings_desc') : _t('st_profile_desc') ?>
      </p>
    </div>
  </div>

  <!-- Content area. -->
  <div class="flex lg:flex-row flex-col items-start gap-8">

    <!-- Mobile navigation. -->
    <div class="lg:hidden z-30 relative w-full" x-show="Object.keys(tabs).length > 1">
      <label class="block opacity-60 mb-2 font-bold text-theme-text text-xs uppercase tracking-wider"><?= _t('lbl_menu') ?></label>
      <button type="button"
        @click="mobileMenuOpen = !mobileMenuOpen"
        class="flex justify-between items-center bg-theme-surface active:bg-theme-bg shadow-theme px-4 py-3 border border-theme-border rounded-theme w-full font-bold text-theme-text transition-colors">
        <div class="flex items-center gap-3">
          <template x-if="tabs[activeTab]">
            <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use :href='<?= json_encode(grinds_asset_url('assets/img/sprite.svg') . '#', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?> + tabs[activeTab].icon'></use>
            </svg>
          </template>
          <span x-text='tabs[activeTab] ? tabs[activeTab].label : <?= json_encode(_t('lbl_select_option'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>'></span>
        </div>
        <svg class="opacity-50 w-5 h-5 text-theme-text transition-transform duration-200"
          :class="mobileMenuOpen ? 'rotate-180' : ''"
          fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
        </svg>
      </button>

      <div x-show="mobileMenuOpen"
        @click.outside="mobileMenuOpen = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95 -translate-y-2"
        x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
        x-transition:leave-end="transform opacity-0 scale-95 -translate-y-2"
        class="top-full left-0 absolute bg-theme-surface shadow-theme mt-2 border border-theme-border rounded-theme w-full max-h-96 overflow-hidden overflow-y-auto"
        style="display: none;" x-cloak>

        <template x-for="(tab, key) in tabs" :key="key">
          <button type="button"
            @click="changeTab(key)"
            class="flex items-center hover:bg-theme-bg px-4 py-3 border-theme-border last:border-0 border-b w-full font-bold text-sm text-left transition-colors"
            :class="activeTab === key ? 'text-theme-primary bg-theme-primary/5' : 'text-theme-text'">

            <svg class="flex-shrink-0 mr-3 w-5 h-5"
              :class="activeTab === key ? 'text-theme-primary' : 'text-theme-text opacity-50'"
              fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use :href='<?= json_encode(grinds_asset_url('assets/img/sprite.svg') . '#', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?> + tab.icon'></use>
            </svg>
            <span x-text="tab.label"></span>

            <template x-if="key === 'update' && hasUpdate">
              <span class="ml-2 flex w-2 h-2 rounded-full shrink-0 animate-pulse" :class="activeTab === 'update' ? 'bg-theme-on-primary' : 'bg-theme-danger'"></span>
            </template>

            <svg x-show="activeTab === key" class="ml-auto w-4 h-4 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check"></use>
            </svg>
          </button>
        </template>
      </div>
    </div>

    <!-- Desktop navigation. -->
    <?php if (count($tabs) > 1): ?>
      <nav class="hidden lg:block lg:top-6 lg:sticky flex-shrink-0 space-y-1 w-64">
        <?php foreach ($tabs as $k => $item): ?>
          <button type="button"
            @click='changeTab(<?= json_encode($k, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>)'
            :class='activeTab === <?= json_encode($k, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>
            ? " bg-theme-primary text-theme-on-primary shadow-theme ring-1 ring-black/5"
            : "text-theme-text hover:bg-theme-surface hover:text-theme-primary opacity-70 hover:opacity-100"'
            class="group flex items-center px-4 py-3 rounded-theme w-full font-bold text-sm transition-all duration-200">

            <svg class="flex-shrink-0 mr-3 w-5 h-5 transition-colors"
              :class=' activeTab===<?= json_encode($k, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?> ? "text-theme-on-primary" : "text-theme-text opacity-50 group-hover:text-theme-primary"'
              fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= h(grinds_asset_url('assets/img/sprite.svg') . '#' . $item['icon']) ?>"></use>
            </svg>

            <?= $item['label'] ?>

            <?php if ($k === 'update' && $hasUpdate): ?>
              <span class="ml-2 flex w-2 h-2 rounded-full shrink-0 animate-pulse" :class="activeTab === 'update' ? 'bg-theme-on-primary' : 'bg-theme-danger'"></span>
            <?php endif; ?>

            <svg x-show=' activeTab===<?= json_encode($k, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>' class="opacity-50 ml-auto w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-right"></use>
            </svg>
          </button>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

    <!-- Tab content area. -->
    <div class="flex-1 w-full min-w-0">

      <!-- General settings. -->
      <?php if (current_user_can('manage_settings')): ?>
        <div x-show="activeTab === 'general'" x-cloak>
          <?php include __DIR__ . '/settings/general.php'; ?>
        </div>

        <div x-show="activeTab === 'display'" x-cloak>
          <?php include __DIR__ . '/settings/display.php'; ?>
        </div>

        <div x-show="activeTab === 'theme'" x-cloak>
          <?php include __DIR__ . '/settings/theme.php'; ?>
        </div>
      <?php endif; ?>

      <!-- Profile settings. -->
      <div x-show="activeTab === 'profile'" x-cloak>
        <?php include __DIR__ . '/settings/profile.php'; ?>
      </div>

      <?php if (current_user_can('manage_settings')): ?>
        <div x-show="activeTab === 'users'" x-cloak>
          <?php include __DIR__ . '/settings/users.php'; ?>
        </div>

        <div x-show="activeTab === 'mail'" x-cloak>
          <?php include __DIR__ . '/settings/mail.php'; ?>
        </div>

        <div x-show="activeTab === 'security'" x-cloak>
          <?php include __DIR__ . '/settings/security.php'; ?>
        </div>

        <div x-show="activeTab === 'integration'" x-cloak>
          <?php include __DIR__ . '/settings/integration.php'; ?>
        </div>

        <div x-show="activeTab === 'backup'" x-cloak>
          <?php include __DIR__ . '/settings/backup.php'; ?>
        </div>

        <div x-show="activeTab === 'system'" x-cloak>
          <?php include __DIR__ . '/settings/system.php'; ?>
          <?php include __DIR__ . '/settings/nginx_tool.php'; ?>
        </div>

        <div x-show="activeTab === 'update'" x-cloak>
          <?php include __DIR__ . '/settings/update.php'; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="<?= grinds_asset_url('assets/js/media_manager.js') ?>"></script>
<?php include __DIR__ . '/parts/media_picker.php'; ?>
