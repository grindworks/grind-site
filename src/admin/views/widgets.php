<?php

/**
 * widgets.php
 *
 * Renders the user interface for managing sidebar widgets.
 */
if (!defined('GRINDS_APP')) exit;

// Define form action URL.
$formAction = 'widgets.php';
$csrf_token = generate_csrf_token();
?>

<!-- Hidden form for bulk actions. -->
<?php include __DIR__ . '/parts/hidden_action_form.php'; ?>

<script>
  function filterWidgets(theme) {
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

    const headerCb = document.getElementById('select-all');
    if (headerCb) headerCb.checked = false;
  }
</script>

<div class="relative flex lg:flex-row flex-col gap-8"
  x-effect="window.toggleScrollLock(mobileFormOpen)"
  x-data='{
    mobileFormOpen: <?= $edit_id ? 'true' : 'false' ?>,
    selectedType: <?= json_encode($edit_data['type'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>,
    isSubmitting: false
  }'>

  <!-- Mobile floating action button. -->
  <?php include __DIR__ . '/parts/fab.php'; ?>

  <!-- Widget list column. -->
  <div class="order-2 lg:order-1 w-full lg:w-2/3">

    <div class="mb-4">
      <h2 class="flex items-center gap-2 font-bold text-theme-text text-xl whitespace-nowrap">
        <svg class="w-6 h-6 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-squares-plus"></use>
        </svg>
        <?= _t('menu_widgets') ?>
      </h2>
    </div>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 w-full sm:w-auto">
        <!-- Search form. -->
        <form method="get" action="widgets.php" class="relative w-full sm:w-auto">
          <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="<?= _t('search') ?>"
            class="bg-theme-bg pl-8 border-theme-border w-full sm:w-48 focus:w-64 text-theme-text text-xs transition-all form-control-sm">
          <svg class="top-1/2 left-2.5 absolute opacity-50 w-3.5 h-3.5 text-theme-text -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
          </svg>
        </form>

        <!-- Theme filter. -->
        <div class="flex items-center gap-2 w-full sm:w-auto">
          <label class="opacity-70 font-bold text-theme-text text-xs whitespace-nowrap"><?= _t('lbl_filter') ?></label>
          <select onchange="filterWidgets(this.value)" class="bg-theme-surface py-1.5 pr-8 pl-3 border-theme-border w-full sm:w-auto text-theme-text text-xs cursor-pointer form-control-sm">
            <?php foreach ($themes as $key => $label): ?>
              <option value="<?= h($key) ?>"><?= h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Bulk actions. -->
      <div class="flex flex-wrap justify-between sm:justify-end items-center gap-2 w-full sm:w-auto">
        <div class="flex items-center gap-2">
          <select id="bulk-action-selector" class="bg-theme-bg border-theme-border w-32 text-theme-text cursor-pointer form-control-sm">
            <option value=""><?= _t('lbl_bulk_actions') ?></option>
            <option value="delete"><?= _t('delete') ?></option>
          </select>
          <button type="button" id="bulk-apply" class="px-3 py-1.5 text-xs whitespace-nowrap btn-secondary">
            <?= _t('apply') ?>
          </button>
        </div>
      </div>
    </div>

    <!-- Desktop table view. -->
    <div class="hidden md:block bg-theme-surface shadow-theme border border-theme-border rounded-theme overflow-x-auto">
      <table class="min-w-full leading-normal">
        <thead>
          <tr class="bg-theme-bg/50 border-theme-border border-b font-bold text-theme-text/60 text-xs text-left uppercase tracking-wider">
            <th class="px-6 py-4 w-10"><input type="checkbox" id="select-all" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary form-checkbox"></th>
            <th class="px-6 py-4"><?= _t('widget_type') ?></th>
            <th class="px-6 py-4"><?= _t('lbl_title') ?></th>
            <th class="px-6 py-4 text-center"><?= _t('col_order') ?></th>
            <th class="px-6 py-4 text-center"><?= _t('col_status') ?></th>
            <th class="px-6 py-4 text-right"><?= _t('col_action') ?></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-theme-border">
          <?php if (empty($widgets)): ?>
            <tr>
              <td colspan="6" class="p-8">
                <div class="flex flex-col justify-center items-center py-12 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
                  <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-squares-plus"></use>
                    </svg>
                  </div>
                  <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('no_data') ?></h3>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($widgets as $row):
              $targetTheme = $row['target_theme'] ?? 'all';
            ?>
              <tr data-theme="<?= h($targetTheme) ?>" class="hover:bg-theme-bg/50 transition-colors <?= ($edit_id == $row['id']) ? 'bg-theme-primary/5' : '' ?>">
                <td class="px-6 py-4">
                  <input type="checkbox" name="ids[]" value="<?= $row['id'] ?>" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary item-checkbox form-checkbox">
                </td>
                <td class="px-6 py-4 align-middle">
                  <span class="bg-theme-bg px-2 py-1 border border-theme-border rounded-theme font-bold text-theme-text text-xs whitespace-nowrap">
                    <?= h($widget_types[$row['type']] ?? $row['type']) ?>
                  </span>
                </td>
                <td class="px-6 py-4 align-middle">
                  <div class="font-bold text-theme-text break-all"><?= h($row['title']) ?></div>
                  <!-- Theme badge. -->
                  <?php if ($targetTheme !== 'all'): ?>
                    <span class="inline-block bg-theme-info/10 mt-1 px-1.5 py-0.5 border border-theme-info/30 rounded-theme font-bold text-[10px] text-theme-info">
                      <?= _t('lbl_theme_only', h(ucfirst($targetTheme))) ?>
                    </span>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 font-mono text-theme-text text-sm text-center align-middle"><?= $row['sort_order'] ?></td>
                <td class="px-6 py-4 text-center align-middle">
                  <?php if ($row['is_active']): ?>
                    <span class="inline-flex items-center bg-theme-success/10 px-2 py-0.5 border border-theme-success/20 rounded-theme font-bold text-theme-success text-xs"><?= _t('lbl_on') ?></span>
                  <?php else: ?>
                    <span class="inline-flex items-center bg-theme-text/10 opacity-50 px-2 py-0.5 border border-theme-border rounded-theme font-bold text-theme-text text-xs"><?= _t('lbl_off') ?></span>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-right align-middle">
                  <div class="flex justify-end items-center gap-4 h-full">
                    <a href="?edit_id=<?= $row['id'] ?>" class="flex items-center p-1 text-theme-primary hover:scale-110 transition" title="<?= h(_t('edit')) ?>" aria-label="<?= h(_t('edit')) ?>">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-pencil-square"></use>
                      </svg>
                    </a>
                    <button type="button" onclick="executeAction('delete', <?= $row['id'] ?>)" class="flex items-center bg-transparent p-1 border-none text-theme-danger hover:scale-110 transition cursor-pointer" title="<?= h(_t('delete')) ?>" aria-label="<?= h(_t('delete')) ?>">
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
      <?php if (empty($widgets)): ?>
        <div class="flex flex-col justify-center items-center py-12 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
          <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-squares-plus"></use>
            </svg>
          </div>
          <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('no_data') ?></h3>
        </div>
      <?php else: ?>
        <div class="flex items-center gap-2 mb-3 px-2">
          <input type="checkbox" id="mobile-select-all" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary form-checkbox">
          <label for="mobile-select-all" class="text-sm font-bold text-theme-text"><?= _t('all') ?></label>
        </div>
        <?php foreach ($widgets as $row):
          $targetTheme = $row['target_theme'] ?? 'all';
        ?>
          <div data-theme="<?= h($targetTheme) ?>" class="bg-theme-surface border border-theme-border rounded-theme shadow-theme relative overflow-hidden <?= ($edit_id == $row['id']) ? 'ring-2 ring-theme-primary' : '' ?>">
            <div class="top-3 left-3 z-10 absolute">
              <input type="checkbox" name="ids[]" value="<?= $row['id'] ?>" class="bg-theme-bg/90 backdrop-blur-sm border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary item-checkbox form-checkbox">
            </div>

            <div class="p-4 pl-12">
              <div class="flex justify-between items-start mb-2">
                <div>
                  <span class="bg-theme-bg opacity-70 px-2 py-1 border border-theme-border rounded-theme font-bold text-[10px] text-theme-text whitespace-nowrap">
                    <?= h($widget_types[$row['type']] ?? $row['type']) ?>
                  </span>
                  <h4 class="mt-1 font-bold text-theme-text text-lg leading-tight break-all"><?= h($row['title']) ?></h4>
                  <?php if ($targetTheme !== 'all'): ?>
                    <span class="inline-block bg-theme-info/10 mt-1 px-1.5 py-0.5 border border-theme-info/30 rounded-theme font-bold text-[10px] text-theme-info">
                      <?= _t('lbl_theme_only', h(ucfirst($targetTheme))) ?>
                    </span>
                  <?php endif; ?>
                </div>
                <div class="flex flex-col items-end gap-1">
                  <?php if ($row['is_active']): ?>
                    <span class="bg-theme-success/10 px-1.5 py-0.5 border border-theme-success/20 rounded-theme font-bold text-[10px] text-theme-success"><?= _t('lbl_on') ?></span>
                  <?php else: ?>
                    <span class="bg-theme-text/10 opacity-50 px-1.5 py-0.5 border border-theme-border rounded-theme font-bold text-[10px] text-theme-text"><?= _t('lbl_off') ?></span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="flex justify-between items-center mt-3 pt-3 border-theme-border border-t">
                <div class="opacity-50 font-mono text-theme-text text-xs"><?= _t('col_order') ?>: <?= $row['sort_order'] ?></div>
                <div class="flex items-center gap-4">
                  <a href="?edit_id=<?= $row['id'] ?>" class="flex items-center gap-1 py-1 font-bold text-theme-primary text-xs hover:underline">
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
  <div class="order-1 lg:order-2 lg:w-1/3 transition-opacity duration-200"
    :class="mobileFormOpen
      ? 'fixed inset-0 z-50 flex items-center justify-center p-4 lg:static lg:z-auto lg:block lg:p-0'
      : 'hidden lg:block'"
    x-cloak>

    <div class="lg:hidden absolute inset-0 skin-modal-overlay backdrop-blur-sm" @click="mobileFormOpen = false"></div>

    <div class="lg:top-6 z-10 relative lg:sticky bg-theme-surface shadow-theme p-6 border border-theme-border rounded-theme w-full lg:max-w-none max-w-lg max-h-[90vh] overflow-y-auto">

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
        <button @click="mobileFormOpen = false" class="lg:hidden opacity-50 hover:opacity-100 p-1 text-theme-text"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
          </svg></button>
      </div>

      <form method="post" enctype="multipart/form-data" class="warn-on-unsaved" @submit.prevent="
        setTimeout(() => isSubmitting = true, 10);
        if (selectedType === 'html') {
          const textarea = $el.querySelector('textarea[name=\'content\']');
          if (textarea && textarea.value) {
            const encoded = btoa(encodeURIComponent(textarea.value).replace(/%([0-9A-F]{2})/g, (m, p1) => String.fromCharCode('0x' + p1)));
            textarea.value = encoded;
            const flag = document.createElement('input');
            flag.type = 'hidden';
            flag.name = 'content_is_base64';
            flag.value = '1';
            $el.appendChild(flag);
          }
        }
        $el.submit();
      ">
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
        <input type="hidden" name="action" value="save">
        <?php if ($edit_id): ?><input type="hidden" name="target_id" value="<?= h($edit_id) ?>"><?php endif; ?>

        <div class="mb-4">
          <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('widget_type') ?></label>
          <select name="type" x-model="selectedType" class="form-control">
            <?php foreach ($widget_types as $key => $label): ?>
              <option value="<?= h($key) ?>"><?= h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-4">
          <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('lbl_title') ?></label>
          <input type="text" name="title" value="<?= h($edit_data['title']) ?>" class="form-control" required>
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

        <!-- Dynamic fields. -->

        <!-- Profile widget fields. -->
        <div x-show="selectedType === 'profile'" class="space-y-4 mt-4 pt-4 border-theme-border border-t">
          <div>
            <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('wg_name') ?></label>
            <input type="text" name="profile_name" value="<?= h($edit_data['settings']['name'] ?? '') ?>" class="form-control">
          </div>
          <?php
          $label = _t('wg_image');
          $name = 'profile_image';
          $value = $edit_data['settings']['image'] ?? '';
          $current_value_input_name = 'current_profile_image';
          $input_style = 'box';
          $preview_class = 'w-16 h-16 object-cover rounded-full border border-theme-border';
          $extra_attrs = ':disabled="selectedType !== \'profile\'"';
          include __DIR__ . '/parts/image_uploader.php';
          ?>
          <div>
            <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('wg_text') ?></label>
            <textarea name="profile_text" rows="4" class="form-control"><?= h(grinds_url_to_view(($edit_data['settings']['text'] ?? '') ?: $edit_data['content'])) ?></textarea>
          </div>
        </div>

        <!-- HTML widget field. -->
        <div x-show="selectedType === 'html'" class="space-y-4 mt-4 pt-4 border-theme-border border-t">
          <div>
            <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('wg_html_content') ?></label>
            <textarea name="content" rows="8" class="font-mono text-xs form-control" placeholder="<div>...</div>"><?= h(grinds_url_to_view($edit_data['content'])) ?></textarea>
          </div>
        </div>

        <!-- Recent posts fields. -->
        <div x-show="selectedType === 'recent'" class="space-y-4 mt-4 pt-4 border-theme-border border-t">
          <div>
            <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('wg_limit') ?></label>
            <input type="number" name="limit" value="<?= h($edit_data['settings']['limit'] ?? 5) ?>" class="form-control">
          </div>
        </div>

        <!-- Banner widget fields. -->
        <div x-show="selectedType === 'banner'" class="space-y-4 mt-4 pt-4 border-theme-border border-t">
          <?php
          $label = _t('wg_image');
          $name = 'banner_image';
          $value = $edit_data['settings']['image'] ?? '';
          $current_value_input_name = 'current_banner_image';
          $input_style = 'box';
          $preview_class = 'max-w-full h-32 object-contain';
          $extra_attrs = ':disabled="selectedType !== \'banner\'"';
          include __DIR__ . '/parts/image_uploader.php';
          ?>
          <div>
            <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('wg_title') ?></label>
            <input type="text" name="banner_title" value="<?= h($edit_data['settings']['title'] ?? '') ?>" class="form-control">
          </div>
          <div>
            <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('wg_link') ?></label>
            <input type="text" name="banner_link" value="<?= h(grinds_url_to_view($edit_data['settings']['link'] ?? '')) ?>" class="form-control" placeholder="https://...">
          </div>
          <div>
            <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('wg_alt') ?></label>
            <input type="text" name="banner_alt" value="<?= h($edit_data['settings']['alt'] ?? '') ?>" class="form-control">
          </div>
        </div>

        <!-- Common fields. -->
        <div class="gap-4 grid grid-cols-2 mt-6 pt-4 border-theme-border border-t">
          <div>
            <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('col_order') ?></label>
            <input type="number" name="sort_order" value="<?= h($edit_data['sort_order']) ?>" class="form-control">
          </div>
          <div class="flex items-center pt-6">
            <label class="flex items-center cursor-pointer">
              <input type="checkbox" name="is_active" value="1" class="bg-theme-bg border-theme-border w-5 h-5 text-theme-primary form-checkbox" <?= $edit_data['is_active'] ? 'checked' : '' ?>>
              <span class="ml-2 font-bold text-theme-text text-sm"><?= _t('lbl_active') ?></span>
            </label>
          </div>
        </div>

        <div class="flex gap-3 mt-8">
          <?php if ($edit_id): ?>
            <a href="widgets.php" class="js-skip-warning flex-1 py-2.5 rounded-theme text-sm text-center btn-secondary"><?= _t('cancel') ?></a>
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
<script src="<?= grinds_asset_url('assets/js/media_manager.js') ?>"></script>
<?php include __DIR__ . '/parts/media_picker.php'; ?>
