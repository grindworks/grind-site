<?php

/**
 * footer.php
 *
 * Render the admin footer, search modal, and toast notifications.
 */
if (!defined('GRINDS_APP')) exit;

?>
<div class="z-50 relative transition-opacity duration-300" role="dialog" aria-modal="true" :class="searchOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'" x-cloak>

  <!-- Backdrop -->
  <div class="fixed inset-0 backdrop-blur-sm transition-opacity skin-modal-overlay"></div>

  <!-- Modal container -->
  <div class="z-10 fixed inset-0 overflow-y-auto"
    @click="searchOpen = false"
    @keydown.down.prevent="move(1)"
    @keydown.up.prevent="move(-1)">

    <div class="flex justify-center items-start p-4 sm:p-6 pt-20 sm:pt-24 w-full min-h-full text-center">

      <div @click.stop
        class="relative bg-theme-surface shadow-theme mx-auto border border-theme-border rounded-theme w-full max-w-3xl overflow-hidden text-left transition-all duration-300 transform"
        :class="searchOpen ? 'opacity-100 scale-100 translate-y-0' : 'opacity-0 scale-95 translate-y-4'">

        <div class="relative flex items-center bg-theme-surface border-theme-border border-b">
          <svg class="top-1/2 left-4 absolute opacity-50 w-6 h-6 text-theme-text -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
          </svg>

          <!-- Auto-focus input -->
          <input type="text" x-model="searchQuery" x-ref="searchInput"
            @focus="selectedIndex = -1"
            @keydown.enter.prevent="
              if (selectedIndex >= 0 && selectedIndex < filteredItems.length) {
                document.getElementById('search-item-' + selectedIndex).click();
              } else if (selectedIndex === filteredItems.length) {
                document.getElementById('search-footer-create').click();
              } else if (filteredItems.length > 0) {
                document.getElementById('search-item-0').click();
              }
            "
            class="bg-transparent pr-14 pl-14 border-none focus:outline-none focus:ring-0 w-full h-16 font-medium text-theme-text text-lg transition-colors placeholder-theme-text/40"
            placeholder="<?= _t('search') ?>">

          <button @click="searchOpen = false" class="top-1/2 right-4 absolute hover:bg-theme-bg opacity-50 hover:opacity-100 px-2 py-1 border border-theme-border rounded-theme font-bold text-[10px] text-theme-text transition -translate-y-1/2">ESC</button>
        </div>

        <ul class="space-y-1 bg-theme-bg/50 p-2 max-h-[60vh] overflow-y-auto text-sm scroll-py-2" x-show="filteredItems.length > 0">
          <template x-for="(item, index) in filteredItems" :key="item.url">
            <li>
              <a :href="item.url === 'logout.php' ? '#' : item.url"
                :id="'search-item-' + index"
                @click="if(item.url === 'logout.php') { $event.preventDefault(); const f = document.createElement('form'); f.method = 'POST'; f.action = 'logout.php'; const c = document.createElement('input'); c.type = 'hidden'; c.name = 'csrf_token'; c.value = window.grindsCsrfToken; f.appendChild(c); document.body.appendChild(f); f.submit(); }"
                @mouseenter="selectedIndex = index"
                @focus="selectedIndex = index"
                :class="{ 'bg-theme-primary text-theme-on-primary': selectedIndex === index, 'text-theme-text hover:bg-theme-bg': selectedIndex !== index }"
                class="group flex items-center px-4 py-3 rounded-theme outline-none transition-colors duration-100 cursor-pointer select-none">

                <div class="flex-shrink-0 mr-3 transition-colors" :class="{ 'text-theme-on-primary': selectedIndex === index, 'text-theme-text opacity-50': selectedIndex !== index }">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use :href="<?= htmlspecialchars(json_encode(grinds_asset_url('assets/img/sprite.svg') . '#'), ENT_QUOTES) ?> + item.icon"></use>
                  </svg>
                </div>

                <span x-text="item.title" class="flex-1 font-bold truncate"></span>

                <span x-text="item.type"
                  :class="{ 'text-theme-on-primary opacity-80 border-theme-on-primary/30': selectedIndex === index, 'text-theme-text opacity-40 border-theme-border': selectedIndex !== index }"
                  class="flex-none shadow-theme ml-3 px-2 py-0.5 border rounded-full font-bold text-[10px] uppercase tracking-wide"></span>
              </a>
            </li>
          </template>
        </ul>

        <div x-show="filteredItems.length === 0 && searchQuery !== ''" class="bg-theme-bg/50 px-6 sm:px-14 py-14 text-sm text-center">
          <div class="flex justify-center items-center bg-theme-bg opacity-50 mx-auto border border-theme-border rounded-full w-12 h-12 text-theme-text">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
            </svg>
          </div>
          <p class="mt-4 font-semibold text-theme-text"><?= _t('no_data') ?></p>
        </div>

        <div class="flex justify-between items-center p-4 border-theme-border border-t rounded-b-theme transition-colors duration-100"
          :class="{ 'bg-theme-bg': selectedIndex === filteredItems.length, 'bg-theme-surface': selectedIndex !== filteredItems.length }">

          <div class="flex gap-2">
            <a href="posts.php?action=new"
              id="search-footer-create"
              @mouseenter="selectedIndex = filteredItems.length"
              @focus="selectedIndex = filteredItems.length"
              :class="{
                  'border-theme-primary text-theme-primary ring-1 ring-theme-primary ring-offset-1': selectedIndex === filteredItems.length,
                  'border-theme-border text-theme-text hover:border-theme-primary hover:text-theme-primary': selectedIndex !== filteredItems.length
              }"
              class="flex items-center bg-theme-bg px-3 py-1.5 border rounded-theme outline-none font-bold text-xs transition-all">

              <svg class="mr-1.5 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
              </svg>
              <?= _t('create_new') ?>
            </a>
          </div>

          <div class="opacity-40 font-bold text-[10px] text-theme-text uppercase tracking-widest">
            GrindSite
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/toast.php'; ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Trigger auto-backup on dashboard only to prevent excessive requests
    const path = window.location.pathname;
    if (path.endsWith('/admin/') || path.endsWith('/index.php')) {
      fetch('api/auto_backup.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          csrf_token: "<?= generate_csrf_token() ?>"
        })
      }).catch(() => {});

      // Trigger asynchronous garbage collection
      fetch('api/run_gc.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          csrf_token: "<?= generate_csrf_token() ?>"
        })
      }).catch(() => {});
    }

    // Global Heartbeat to prevent session timeout on long-running pages (Settings, Menus, etc.)
    setInterval(() => {
      // Only run if not already handled by the editor's specific heartbeat
      if (typeof window.grindsPostContent === 'undefined') {
        fetch('api/heartbeat.php', {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        }).catch(() => {});
      }
    }, 15 * 60 * 1000); // 15 minutes
  });
</script>
</body>

</html>
