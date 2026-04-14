<?php

/**
 * menus.php
 *
 * Renders the user interface for managing navigation menus.
 */
if (!defined('GRINDS_APP')) exit;

// Define form action URL.
$formAction = 'menus.php';
$csrf_token = generate_csrf_token();

// Calculate relative base path for menu items
$urlPath = '/';
?>

<!-- Hidden form for bulk actions. -->
<?php
$extra_inputs = '<input type="hidden" name="location" value="' . h($current_location) . '">';
include __DIR__ . '/parts/hidden_action_form.php';
?>

<!-- Form for saving orders -->
<form id="save-orders-form" method="post" action="<?= h($formAction) ?>" style="display: none;">
  <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
  <input type="hidden" name="action" value="save_orders">
  <input type="hidden" name="location" value="<?= h($current_location) ?>">
</form>

<script>
  // Filter menus by theme.
  function filterMenus(theme) {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
      const target = row.getAttribute('data-theme');
      if (theme === 'all' || target === 'all' || target === theme) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
        const cb = row.querySelector('.item-checkbox');
        if (cb) cb.checked = false;
      }
    });

    // Filter mobile cards.
    const cards = document.querySelectorAll('.md\\:hidden > div[data-theme]');
    cards.forEach(card => {
      const target = card.getAttribute('data-theme');
      if (theme === 'all' || target === 'all' || target === theme) {
        card.style.display = '';
      } else {
        card.style.display = 'none';
        const cb = card.querySelector('.item-checkbox');
        if (cb) cb.checked = false;
      }
    });
  }
</script>

<div class="relative flex lg:flex-row flex-col gap-8"
  x-data='{
    mobileFormOpen: <?= $edit_id ? 'true' : 'false' ?>,
    activeAccordion: "custom",
    inputLabel: <?= json_encode($edit_data['label'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>,
    inputUrl: <?= json_encode($edit_data['url'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>,
    isSubmitting: false,
    isMobile: window.innerWidth < 1024,
    _lockedState: false,
    fill(label, url) {
      this.inputLabel = label;
      this.inputUrl = url;
      if(window.innerWidth < 1024) {
        this.mobileFormOpen = true;
      } else {
        document.getElementById("menu-form-card").scrollIntoView({ behavior: "smooth", block: "center" });
      }
    }
  }'
  @resize.window="isMobile = window.innerWidth < 1024"
  x-effect="
    const shouldLock = (mobileFormOpen && isMobile);
    if (_lockedState !== shouldLock) {
      window.toggleScrollLock(shouldLock);
      _lockedState = shouldLock;
    }
  ">

  <!-- Mobile floating action button. -->
  <?php include __DIR__ . '/parts/fab.php'; ?>

  <!-- Menu list column. -->
  <div class="order-2 lg:order-1 w-full lg:w-2/3">

    <div class="mb-4">
      <h2 class="flex items-center gap-2 font-bold text-theme-text text-xl whitespace-nowrap">
        <svg class="w-6 h-6 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-bars-3"></use>
        </svg>
        <?= _t('menu_menus') ?>
      </h2>
    </div>

    <!-- Location tabs. -->
    <div class="mb-6">
      <div class="flex bg-theme-surface p-1 border border-theme-border rounded-theme w-full sm:w-fit">
        <a href="?location=header" class="flex-1 sm:flex-none text-center px-2 sm:px-4 py-2 rounded-theme text-sm font-bold whitespace-nowrap transition-colors <?= $current_location === 'header' ? 'bg-theme-primary text-theme-on-primary shadow-theme' : 'text-theme-text hover:bg-theme-bg' ?>">
          <?= _t('menu_loc_header') ?>
        </a>
        <a href="?location=footer" class="flex-1 sm:flex-none text-center px-2 sm:px-4 py-2 rounded-theme text-sm font-bold whitespace-nowrap transition-colors <?= $current_location === 'footer' ? 'bg-theme-primary text-theme-on-primary shadow-theme' : 'text-theme-text hover:bg-theme-bg' ?>">
          <?= _t('menu_loc_footer') ?>
        </a>
      </div>
    </div>

    <!-- Filter & Actions. -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
      <!-- Theme filter. -->
      <div class="flex items-center gap-2">
        <label class="opacity-70 font-bold text-theme-text text-xs whitespace-nowrap"><?= _t('lbl_filter') ?></label>
        <select onchange="filterMenus(this.value)" class="bg-theme-surface py-1.5 pr-8 pl-3 border-theme-border w-auto text-theme-text text-xs cursor-pointer form-control-sm">
          <?php foreach ($themes as $key => $label): ?>
            <option value="<?= h($key) ?>"><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Bulk actions. -->
      <div class="flex flex-wrap justify-end items-center gap-2 w-full sm:w-auto">
        <select id="bulk-action-selector" class="bg-theme-bg border-theme-border w-32 text-theme-text cursor-pointer form-control-sm">
          <option value=""><?= _t('lbl_bulk_actions') ?></option>
          <option value="delete"><?= _t('delete') ?></option>
        </select>
        <button type="button" id="bulk-apply" class="px-3 py-1.5 text-xs whitespace-nowrap btn-secondary">
          <?= _t('apply') ?>
        </button>

        <button type="submit" form="save-orders-form" class="ml-2 px-3 py-1.5 text-xs whitespace-nowrap btn-secondary">
          <?= _t('save_changes') ?>
        </button>
      </div>
    </div>

    <!-- Desktop table view. -->
    <div class="hidden md:block bg-theme-surface shadow-theme border border-theme-border rounded-theme overflow-x-auto">
      <table class="min-w-full leading-normal">
        <thead>
          <tr class="bg-theme-bg/50 border-theme-border border-b font-bold text-theme-text/60 text-xs text-left uppercase tracking-wider">
            <th class="px-6 py-4 w-10"><input type="checkbox" id="select-all" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary form-checkbox"></th>
            <th class="px-6 py-4"><?= _t('col_name') ?></th>
            <th class="hidden sm:table-cell px-6 py-4"><?= _t('col_url') ?></th>
            <th class="hidden lg:table-cell px-6 py-4 text-center"><?= _t('col_external') ?></th>
            <th class="hidden lg:table-cell px-6 py-4 text-center"><?= _t('col_order') ?></th>
            <th class="px-6 py-4 text-right"><?= _t('col_action') ?></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-theme-border">
          <?php if (empty($menus)): ?>
            <tr>
              <td colspan="6" class="p-8">
                <div class="flex flex-col justify-center items-center py-12 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
                  <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-bars-3"></use>
                    </svg>
                  </div>
                  <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('no_data') ?></h3>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($menus as $row):
              $targetTheme = $row['target_theme'] ?? 'all';
            ?>
              <tr data-theme="<?= h($targetTheme) ?>" class="hover:bg-theme-bg/50 transition-colors <?= ($edit_id == $row['id']) ? 'bg-theme-primary/5' : '' ?>">
                <td class="px-6 py-4">
                  <input type="checkbox" name="ids[]" value="<?= $row['id'] ?>" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary item-checkbox form-checkbox">
                </td>
                <td class="px-6 py-4 align-middle">
                  <div class="font-bold text-theme-text mb-1 break-all"><?= h($row['label']) ?></div>
                  <?php if ($targetTheme !== 'all'): ?>
                    <span class="bg-theme-info/10 px-1.5 py-0.5 border border-theme-info/30 rounded-theme font-bold text-[10px] text-theme-info">
                      <?= _t('lbl_theme_only', h(ucfirst($targetTheme))) ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td class="hidden sm:table-cell opacity-80 px-6 py-4 max-w-xs text-theme-text text-sm truncate align-middle"><?= h(grinds_url_to_view($row['url'])) ?></td>
                <td class="hidden lg:table-cell px-6 py-4 text-center align-middle">
                  <?php if ($row['is_external']): ?>
                    <span class="bg-theme-info/10 px-2 py-0.5 border border-theme-info/20 rounded-theme font-bold text-xs text-theme-info"><?= _t('yes') ?></span>
                  <?php else: ?>
                    <span class="opacity-30">-</span>
                  <?php endif; ?>
                </td>
                <td class="hidden lg:table-cell px-6 py-4 font-mono text-sm text-center align-middle">
                  <input type="number" name="orders[<?= $row['id'] ?>]" value="<?= $row['sort_order'] ?>" form="save-orders-form" class="w-16 text-center form-control-sm">
                </td>

                <td class="px-6 py-4 text-right align-middle">
                  <div class="flex justify-end items-center gap-4 h-full">
                    <a href="?location=<?= $current_location ?>&edit_id=<?= $row['id'] ?>" class="flex items-center p-1 text-theme-primary hover:scale-110 transition" title="<?= h(_t('edit')) ?>" aria-label="<?= h(_t('edit')) ?>">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-pencil-square"></use>
                      </svg>
                    </a>
                    <button type="button" onclick="executeAction('delete', <?= $row['id'] ?>)" class="flex items-center bg-transparent p-0 border-none text-theme-danger hover:scale-110 transition cursor-pointer" title="<?= h(_t('delete')) ?>" aria-label="<?= h(_t('delete')) ?>">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Mobile card view. -->
    <div class="md:hidden space-y-4">
      <?php if (empty($menus)): ?>
        <div class="flex flex-col justify-center items-center py-12 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
          <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-bars-3"></use>
            </svg>
          </div>
          <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('no_data') ?></h3>
        </div>
      <?php else: ?>
        <div class="flex items-center gap-2 mb-3 px-2">
          <input type="checkbox" id="mobile-select-all" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary form-checkbox">
          <label for="mobile-select-all" class="text-sm font-bold text-theme-text"><?= _t('all') ?></label>
        </div>
        <?php foreach ($menus as $row):
          $targetTheme = $row['target_theme'] ?? 'all';
        ?>
          <div data-theme="<?= h($targetTheme) ?>" class="bg-theme-surface border border-theme-border rounded-theme shadow-theme relative overflow-hidden <?= ($edit_id == $row['id']) ? 'ring-2 ring-theme-primary ring-offset-2 ring-offset-theme-bg' : '' ?>">
            <div class="top-3 left-3 z-10 absolute">
              <input type="checkbox" name="ids[]" value="<?= $row['id'] ?>" class="bg-theme-bg/90 backdrop-blur-sm border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary item-checkbox form-checkbox">
            </div>

            <div class="p-4 pl-12">
              <div class="flex flex-wrap justify-between items-start gap-x-2 gap-y-1 mb-2">
                <h4 class="font-bold text-theme-text text-lg leading-tight break-all"><?= h($row['label']) ?></h4>
                <?php if ($targetTheme !== 'all'): ?>
                  <span class="bg-theme-info/10 px-1.5 py-0.5 border border-theme-info/30 rounded-theme font-bold text-[10px] text-theme-info whitespace-nowrap">
                    <?= _t('lbl_theme_only', h(ucfirst($targetTheme))) ?>
                  </span>
                <?php endif; ?>
              </div>
              <div class="flex items-center gap-1.5 opacity-70 text-theme-text text-xs min-w-0">
                <span class="font-mono truncate"><?= h(grinds_url_to_view($row['url'])) ?></span>
                <?php if ($row['is_external']): ?>
                  <svg class="flex-shrink-0 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-top-right-on-square"></use>
                  </svg>
                <?php endif; ?>
              </div>

              <div class="flex justify-between items-center mt-3 pt-3 border-theme-border border-t">
                <div class="opacity-60 font-mono text-theme-text text-xs">Order: <?= $row['sort_order'] ?></div>

                <div class="flex items-center gap-4">
                  <a href="?location=<?= $current_location ?>&edit_id=<?= $row['id'] ?>" class="flex items-center gap-1 py-1 font-bold text-theme-primary text-xs hover:underline">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-pencil-square"></use>
                    </svg>
                    <?= _t('edit') ?>
                  </a>
                  <button type="button" onclick="executeAction('delete', <?= $row['id'] ?>)" class="flex items-center gap-1 bg-transparent p-0 py-1 border-none font-bold text-theme-danger text-xs hover:underline">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                    </svg>
                    <?= _t('delete') ?>
                  </button>
                </div>
              </div>
            </div>

          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </div>

  <!-- Add/Edit form column. -->
  <div
    class="order-1 lg:order-2 lg:w-1/3 transition-opacity duration-200"
    :class="mobileFormOpen
      ? 'fixed inset-0 z-50 flex items-center justify-center p-4 lg:static lg:z-auto lg:block lg:p-0'
      : 'hidden lg:block'"
    x-cloak>

    <div class="lg:hidden absolute inset-0 skin-modal-overlay backdrop-blur-sm" @click="mobileFormOpen = false"></div>

    <div id="menu-form-card" class="lg:top-6 z-10 relative lg:sticky bg-theme-surface shadow-theme p-6 border border-theme-border rounded-theme w-full lg:max-w-none max-w-lg max-h-[90vh] lg:max-h-none overflow-y-auto">

      <div class="flex justify-between items-center mb-6 pb-4 border-theme-border border-b">
        <h3 class="flex items-center gap-2 font-bold text-theme-text text-lg">
          <?php if ($edit_id): ?>
            <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-pencil-square"></use>
            </svg>
            <?= _t('edit') ?>
          <?php else: ?>
            <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus-circle"></use>
            </svg>
            <?= _t('add') ?>
          <?php endif; ?>
        </h3>
        <button @click="mobileFormOpen = false" class="lg:hidden opacity-50 hover:opacity-100 p-1 text-theme-text">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
          </svg>
        </button>
      </div>

      <!-- Item selector accordion. -->
      <div class="space-y-2 mb-6">
        <p class="opacity-60 mb-2 font-bold text-theme-text text-xs"><?= _t('menu_select_item') ?></p>

        <!-- Pages selector. -->
        <div class="border border-theme-border rounded-theme overflow-hidden">
          <button @click="activeAccordion = activeAccordion === 'pages' ? null : 'pages'" type="button" class="flex justify-between items-center bg-theme-bg hover:bg-theme-surface p-3 w-full font-bold text-theme-text text-sm transition-colors">
            <span><?= _t('menu_item_pages') ?></span>
            <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': activeAccordion === 'pages'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
            </svg>
          </button>
          <div x-show="activeAccordion === 'pages'" x-collapse class="bg-theme-surface p-3 border-theme-border border-t max-h-48 overflow-y-auto">
            <?php if (empty($pages_list)): ?>
              <p class="opacity-50 text-theme-text text-xs"><?= _t('no_pages') ?></p>
            <?php else: ?>
              <ul class="space-y-1">
                <?php foreach ($pages_list as $p): ?>
                  <li>
                    <button type="button" @click='fill(<?= json_encode($p['title'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>, <?= json_encode($urlPath . $p['slug'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>)' class="flex items-center hover:bg-theme-primary/10 px-2 py-1.5 rounded-theme w-full text-theme-text hover:text-theme-primary text-xs text-left truncate transition-colors">
                      <svg class="opacity-50 mr-2 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document"></use>
                      </svg>
                      <?= h($p['title']) ?>
                    </button>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>

        <!-- Categories selector. -->
        <div class="border border-theme-border rounded-theme overflow-hidden">
          <button @click="activeAccordion = activeAccordion === 'cats' ? null : 'cats'" type="button" class="flex justify-between items-center bg-theme-bg hover:bg-theme-surface p-3 w-full font-bold text-theme-text text-sm transition-colors">
            <span><?= _t('menu_item_cats') ?></span>
            <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': activeAccordion === 'cats'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
            </svg>
          </button>
          <div x-show="activeAccordion === 'cats'" x-collapse class="bg-theme-surface p-3 border-theme-border border-t max-h-48 overflow-y-auto">
            <?php if (empty($categories_list)): ?>
              <p class="opacity-50 text-theme-text text-xs"><?= _t('no_cats') ?></p>
            <?php else: ?>
              <ul class="space-y-1">
                <?php foreach ($categories_list as $c): ?>
                  <li>
                    <button type="button" @click='fill(<?= json_encode($c['name'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>, <?= json_encode($urlPath . 'category/' . $c['slug'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>)' class="flex items-center hover:bg-theme-primary/10 px-2 py-1.5 rounded-theme w-full text-theme-text hover:text-theme-primary text-xs text-left truncate transition-colors">
                      <svg class="opacity-50 mr-2 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-folder"></use>
                      </svg>
                      <?= h($c['name']) ?>
                    </button>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>

        <!-- Tags selector. -->
        <div class="border border-theme-border rounded-theme overflow-hidden">
          <button @click="activeAccordion = activeAccordion === 'tags' ? null : 'tags'" type="button" class="flex justify-between items-center bg-theme-bg hover:bg-theme-surface p-3 w-full font-bold text-theme-text text-sm transition-colors">
            <span><?= _t('menu_item_tags') ?></span>
            <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': activeAccordion === 'tags'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
            </svg>
          </button>
          <div x-show="activeAccordion === 'tags'" x-collapse class="bg-theme-surface p-3 border-theme-border border-t max-h-48 overflow-y-auto">
            <?php if (empty($tags_list)): ?>
              <p class="opacity-50 text-theme-text text-xs"><?= _t('no_tags') ?></p>
            <?php else: ?>
              <div class="flex flex-wrap gap-2">
                <?php foreach ($tags_list as $t): ?>
                  <button type="button" @click='fill(<?= json_encode('#' . $t['name'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>, <?= json_encode($urlPath . 'tag/' . $t['slug'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>)' class="bg-theme-bg px-2 py-1 border border-theme-border hover:border-theme-primary rounded-theme text-theme-text hover:text-theme-primary text-xs transition-colors">
                    #<?= h($t['name']) ?>
                  </button>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Add/Edit form. -->
      <form method="post" class="warn-on-unsaved" @submit="setTimeout(() => isSubmitting = true, 10)">
        <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">
        <input type="hidden" name="location" value="<?= h($current_location) ?>">
        <?php if ($edit_id): ?><input type="hidden" name="target_id" value="<?= h($edit_id) ?>"><?php endif; ?>

        <div class="mb-4">
          <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('col_name') ?></label>
          <input type="text" name="label" x-model="inputLabel" required class="form-control" placeholder="<?= _t('ph_menu_label') ?>">
        </div>
        <div class="mb-4">
          <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('col_url') ?></label>
          <input type="text" name="url" x-model="inputUrl" required class="font-mono text-xs form-control" placeholder="<?= _t('ph_menu_url') ?>">
        </div>

        <!-- Theme selector. -->
        <div class="mb-4">
          <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('lbl_target_theme') ?></label>
          <select name="target_theme" class="form-control">
            <?php foreach ($themes as $key => $label): ?>
              <option value="<?= h($key) ?>" <?= ($edit_data['target_theme'] ?? 'all') === $key ? 'selected' : '' ?>>
                <?= h($label) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <p class="opacity-50 mt-1 text-[10px] text-theme-text">
            <?= _t('help_target_theme') ?>
          </p>
        </div>

        <div class="gap-4 grid grid-cols-2 mb-6">
          <div>
            <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('col_order') ?></label>
            <input type="number" name="sort_order" value="<?= h($edit_data['sort_order']) ?>" class="form-control">
          </div>
          <div class="flex items-center pt-6">
            <label class="flex items-center cursor-pointer">
              <input type="checkbox" name="is_external" value="1" class="bg-theme-bg border-theme-border rounded w-5 h-5 text-theme-primary form-checkbox" <?= $edit_data['is_external'] ? 'checked' : '' ?>>
              <span class="ml-2 text-theme-text text-sm"><?= _t('col_external') ?></span>
            </label>
          </div>
        </div>
        <div class="flex gap-2">
          <?php if ($edit_id): ?>
            <a href="menus.php?location=<?= $current_location ?>" class="js-skip-warning flex-1 py-2.5 rounded-theme text-sm text-center btn-secondary"><?= _t('cancel') ?></a>
          <?php else: ?>
            <button type="button" @click="mobileFormOpen = false" class="lg:hidden flex-1 py-2.5 rounded-theme text-sm text-center btn-secondary"><?= _t('cancel') ?></button>
          <?php endif; ?>
          <button type="submit" class="flex flex-1 justify-center items-center gap-2 shadow-theme py-2.5 rounded-theme font-bold text-sm transition-all btn-primary" :disabled="isSubmitting">
            <?php if ($edit_id): ?>
              <svg class="w-5 h-5" :class="{'animate-spin': isSubmitting}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
              </svg>
            <?php else: ?>
              <svg x-show="!isSubmitting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus-circle"></use>
              </svg>
              <svg x-show="isSubmitting" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
              </svg>
            <?php endif; ?>
            <?= $edit_id ? _t('update') : _t('add') ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="<?= grinds_asset_url('assets/js/admin_form_unsaved.js') ?>"></script>
