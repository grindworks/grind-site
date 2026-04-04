<?php

/**
 * banners.php
 *
 * Renders the user interface for managing promotional banners.
 */
if (!defined('GRINDS_APP')) exit;



$formAction = 'banners.php';
$csrf_token = generate_csrf_token();
?>

<!-- Hidden form for bulk actions. -->
<?php include __DIR__ . '/parts/hidden_action_form.php'; ?>

<div class="relative flex lg:flex-row flex-col gap-8"
  x-effect="window.toggleScrollLock(mobileFormOpen || guideOpen)"
  x-data="{
    mobileFormOpen: <?= $edit_id ? 'true' : 'false' ?>,
    guideOpen: false
  }">

  <!-- Mobile floating action button. -->
  <?php include __DIR__ . '/parts/fab.php'; ?>

  <!-- Banner list column. -->
  <div class="space-y-4 order-2 lg:order-1 w-full lg:w-2/3">

    <div class="mb-4">
      <h2 class="flex items-center gap-2 font-bold text-theme-text text-xl whitespace-nowrap">
        <svg class="w-6 h-6 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-megaphone"></use>
        </svg>
        <?= _t('menu_banners') ?>
      </h2>
    </div>

    <div class="flex sm:flex-row flex-col justify-between items-center gap-4 mb-4">
      <!-- Guide button. -->
      <button type="button" @click="guideOpen = true" class="flex items-center bg-theme-primary/10 hover:bg-theme-primary px-3 py-1.5 rounded-full font-bold text-theme-primary hover:text-theme-on-primary text-xs transition-colors">
        <svg class="mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-information-circle"></use>
        </svg>
        <span class="hidden sm:inline"><?= _t('btn_placement_guide') ?></span>
        <span class="sm:hidden"><?= _t('btn_placement_guide') ?></span>
      </button>

      <!-- Bulk actions. -->
      <div class="flex justify-between sm:justify-end items-center gap-2 w-full sm:w-auto">
        <div class="flex items-center gap-2">
          <select id="bulk-action-selector" class="w-32 cursor-pointer form-control-sm">
            <option value=""><?= _t('lbl_bulk_actions') ?></option>
            <option value="delete"><?= _t('delete') ?></option>
          </select>
          <button type="button" id="bulk-apply" class="px-3 py-1.5 text-xs whitespace-nowrap btn-secondary">
            <?= _t('apply') ?>
          </button>
        </div>
        <div class="hidden sm:block">
          <?php include __DIR__ . '/parts/limit_selector.php'; ?>
        </div>
      </div>
    </div>

    <!-- Desktop table view. -->
    <div class="hidden md:block bg-theme-surface shadow-theme border border-theme-border rounded-theme overflow-x-auto">
      <table class="min-w-full leading-normal">
        <thead>
          <tr class="bg-theme-bg/50 border-theme-border border-b font-bold text-theme-text/60 text-xs text-left uppercase tracking-wider">
            <th class="px-6 py-4 w-10"><input type="checkbox" id="select-all" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary form-checkbox"></th>
            <th class="px-6 py-4"><?= _t('col_thumb') ?></th>
            <?php $sorter->renderTh('position', _t('col_location') . ' / ' . _t('col_link')); ?>
            <?php $sorter->renderTh('is_active', _t('col_status')); ?>
            <th class="px-6 py-4 text-right"><?= _t('col_action') ?></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-theme-border">
          <?php if (empty($banners)): ?>
            <tr>
              <td colspan="5" class="p-8">
                <div class="flex flex-col justify-center items-center py-12 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
                  <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-megaphone"></use>
                    </svg>
                  </div>
                  <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('no_data') ?></h3>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($banners as $row): ?>
              <tr class="hover:bg-theme-bg/50 transition-colors <?= ($edit_id == $row['id']) ? 'bg-theme-primary/5' : '' ?>">
                <td class="px-6 py-4">
                  <input type="checkbox" name="ids[]" value="<?= $row['id'] ?>" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary item-checkbox form-checkbox">
                </td>
                <td class="px-6 py-4 align-middle">
                  <?php if (($row['type'] ?? 'image') === 'image' && $row['image_url']): ?>
                    <div class="group relative bg-checker border border-theme-border rounded-theme w-24 h-14 overflow-hidden">
                      <img src="<?= h(get_media_url($row['image_url'])) ?>" class="w-full h-full object-cover">
                    </div>
                  <?php elseif (($row['type'] ?? 'image') === 'html'): ?>
                    <div class="flex justify-center items-center bg-theme-bg opacity-50 border border-theme-border rounded-theme w-24 h-14 font-mono text-theme-text text-xs">
                      HTML
                    </div>
                  <?php else: ?>
                    <span class="opacity-50 text-theme-text text-xs"><?= _t('no_img') ?></span>
                  <?php endif; ?>
                </td>
                <td class="px-6 py-4 align-middle">
                  <div class="mb-1 font-bold text-theme-primary text-xs uppercase tracking-wide">
                    <?= h($pos_labels[$row['position']] ?? $row['position']) ?>
                  </div>
                  <!-- Target badge. -->
                  <div class="mb-1">
                    <?php if (($row['target_type'] ?? 'all') === 'home'): ?>
                      <span class="bg-theme-info/10 px-1.5 py-0.5 border border-theme-info/20 rounded-theme font-bold text-[10px] text-theme-info"><?= _t('cond_home') ?></span>
                    <?php elseif (($row['target_type'] ?? 'all') === 'category'): ?>
                      <span class="bg-theme-warning/10 px-1.5 py-0.5 border border-theme-warning/20 rounded-theme font-bold text-[10px] text-theme-warning"><?= _t('lbl_category') ?>: <?= $row['target_id'] ?></span>
                    <?php elseif (($row['target_type'] ?? 'all') === 'page'): ?>
                      <span class="bg-theme-danger/10 px-1.5 py-0.5 border border-theme-danger/20 rounded-theme font-bold text-[10px] text-theme-danger">ID: <?= $row['target_id'] ?></span>
                    <?php else: ?>
                      <span class="bg-theme-text/5 px-1.5 py-0.5 border border-theme-border rounded-theme text-[10px] text-theme-text/50"><?= _t('cond_all') ?></span>
                    <?php endif; ?>
                  </div>

                  <div class="mb-1 max-w-xs text-theme-text text-sm truncate">
                    <?php if (($row['type'] ?? 'image') === 'image'): ?>
                      <a href="<?= h($row['link_url']) ?>" target="_blank" class="flex items-center gap-1 hover:underline">
                        <?= h($row['link_url']) ?>
                        <svg class="opacity-50 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-top-right-on-square"></use>
                        </svg>
                      </a>
                    <?php else: ?>
                      <span class="opacity-50 font-mono text-xs">&lt;<?= _t('lbl_html_code') ?>&gt;</span>
                    <?php endif; ?>
                  </div>
                  <div class="opacity-50 font-mono text-theme-text text-xs"><?= _t('col_order') ?>: <?= $row['sort_order'] ?></div>
                </td>
                <td class="px-6 py-4 text-center align-middle">
                  <?php if ($row['is_active']): ?>
                    <span class="inline-flex items-center bg-theme-success/10 px-2 py-0.5 border border-theme-success/20 rounded-theme font-bold text-theme-success text-xs"><?= _t('lbl_on') ?></span>
                  <?php else: ?>
                    <span class="inline-flex items-center bg-theme-text/10 px-2 py-0.5 border border-theme-border rounded-theme font-bold text-theme-text/50 text-xs"><?= _t('lbl_off') ?></span>
                  <?php endif; ?>
                </td>

                <td class="px-6 py-4 text-right align-middle whitespace-nowrap">
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
      <?php if (empty($banners)): ?>
        <div class="flex flex-col justify-center items-center py-12 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
          <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-megaphone"></use>
            </svg>
          </div>
          <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('no_data') ?></h3>
        </div>
      <?php else: ?>
        <div class="flex items-center gap-2 mb-3 px-2">
          <input type="checkbox" id="mobile-select-all" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary form-checkbox">
          <label for="mobile-select-all" class="text-sm font-bold text-theme-text"><?= _t('all') ?></label>
        </div>
        <?php foreach ($banners as $row): ?>
          <div class="bg-theme-surface border border-theme-border rounded-theme shadow-theme overflow-hidden relative <?= ($edit_id == $row['id']) ? 'ring-2 ring-theme-primary' : '' ?>">
            <div class="top-3 left-3 z-10 absolute">
              <input type="checkbox" name="ids[]" value="<?= $row['id'] ?>" class="bg-theme-bg/90 backdrop-blur-sm border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary item-checkbox form-checkbox">
            </div>

            <?php if (($row['type'] ?? 'image') === 'image' && $row['image_url']): ?>
              <div class="group relative bg-checker border-theme-border border-b w-full h-32">
                <img src="<?= h(get_media_url($row['image_url'])) ?>" class="w-full h-full object-cover">
              <?php elseif (($row['type'] ?? 'image') === 'html'): ?>
                <div class="group relative flex justify-center items-center bg-theme-bg opacity-50 border-theme-border border-b w-full h-32 font-mono text-theme-text text-sm">
                  &lt;HTML /&gt;
                <?php else: ?>
                  <div class="group relative flex justify-center items-center bg-theme-bg opacity-30 border-theme-border border-b w-full h-32 text-theme-text text-xs">
                    <?= _t('no_img') ?>
                  <?php endif; ?>
                  <div class="top-2 right-2 absolute">
                    <?php if ($row['is_active']): ?>
                      <span class="bg-theme-success shadow-theme px-2 py-1 rounded-theme font-bold text-[10px] text-white"><?= _t('lbl_active') ?></span>
                    <?php else: ?>
                      <span class="bg-theme-text/10 shadow-theme px-2 py-1 border border-theme-border rounded-theme font-bold text-[10px] text-theme-text"><?= _t('lbl_off') ?></span>
                    <?php endif; ?>
                  </div>
                  </div>

                  <div class="p-4">
                    <div class="flex justify-between items-start mb-2">
                      <div>
                        <div class="mb-1 font-bold text-theme-primary text-xs uppercase"><?= h($pos_labels[$row['position']] ?? $row['position']) ?></div>
                        <div class="flex flex-wrap gap-1 mb-1">
                          <?php if (($row['target_type'] ?? 'all') === 'home'): ?>
                            <span class="bg-theme-info/10 px-1.5 py-0.5 border border-theme-info/20 rounded-theme font-bold text-[10px] text-theme-info"><?= _t('cond_home') ?></span>
                          <?php elseif (($row['target_type'] ?? 'all') === 'category'): ?>
                            <span class="bg-theme-warning/10 px-1.5 py-0.5 border border-theme-warning/20 rounded-theme font-bold text-[10px] text-theme-warning"><?= _t('lbl_category') ?>: <?= $row['target_id'] ?></span>
                          <?php elseif (($row['target_type'] ?? 'all') === 'page'): ?>
                            <span class="bg-theme-danger/10 px-1.5 py-0.5 border border-theme-danger/20 rounded-theme font-bold text-[10px] text-theme-danger">ID: <?= $row['target_id'] ?></span>
                          <?php else: ?>
                            <span class="bg-theme-text/5 px-1.5 py-0.5 border border-theme-border rounded-theme text-[10px] text-theme-text/50"><?= _t('cond_all') ?></span>
                          <?php endif; ?>
                        </div>
                        <?php if (($row['type'] ?? 'image') === 'image'): ?>
                          <a href="<?= h($row['link_url']) ?>" target="_blank" class="flex items-center gap-1 text-theme-text text-sm hover:underline break-all leading-tight">
                            <?= h($row['link_url']) ?>
                            <svg class="opacity-50 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-top-right-on-square"></use>
                            </svg>
                          </a>
                        <?php else: ?>
                          <div class="opacity-50 max-w-[200px] font-mono text-xs truncate"><?= h($row['html_code']) ?></div>
                        <?php endif; ?>
                      </div>
                    </div>

                    <div class="flex justify-between items-center mt-4 pt-3 border-theme-border border-t">
                      <div class="opacity-50 font-mono text-theme-text text-xs"><?= _t('col_order') ?>: <?= $row['sort_order'] ?></div>
                      <div class="flex items-center gap-4">
                        <a href="?edit_id=<?= $row['id'] ?>" class="flex items-center gap-1 font-bold text-theme-primary text-xs hover:underline">
                          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-pencil-square"></use>
                          </svg>
                          <?= _t('edit') ?>
                        </a>

                        <button type="button" onclick="executeAction('delete', <?= $row['id'] ?>)" class="flex items-center gap-1 bg-transparent p-0 border-none font-bold text-theme-danger text-xs hover:underline">
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

              <div class="flex sm:flex-row flex-col justify-between items-center gap-4 mt-4">
                <div class="sm:hidden flex justify-center w-full">
                  <?php include __DIR__ . '/parts/limit_selector.php'; ?>
                </div>
                <div class="flex justify-center sm:justify-end w-full sm:w-auto">
                  <?php if (isset($paginator)) echo $paginator->render(); ?>
                </div>
              </div>
          </div>

          <!-- Add/Edit form column. -->
          <div
            class="order-1 lg:order-2 lg:w-1/3 transition-opacity duration-200"
            :class="mobileFormOpen ? 'fixed inset-0 z-50 flex items-center justify-center p-4 lg:static lg:z-auto lg:block lg:p-0' : 'hidden lg:block'"
            x-cloak>

            <div class="lg:hidden absolute inset-0 skin-modal-overlay backdrop-blur-sm" @click="mobileFormOpen = false"></div>

            <div class="lg:top-6 z-10 relative lg:sticky bg-theme-surface shadow-theme p-6 border border-theme-border rounded-theme w-full lg:max-w-none max-w-lg max-h-[90vh] lg:max-h-none overflow-y-auto">

              <!-- Form header. -->
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

              <form method="post" enctype="multipart/form-data" class="warn-on-unsaved" x-data='{
        type: <?= json_encode($edit_data['type'] ?? 'image', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>,
        imageWidth: <?= (int)($edit_data['image_width'] ?? 100) ?>,
        isSubmitting: false
      }' @submit.prevent="
        setTimeout(() => isSubmitting = true, 10);
        if (type === 'html') {
          const textarea = $el.querySelector('textarea[name=\'html_code\']');
          if (textarea && textarea.value) {
            const encoded = btoa(encodeURIComponent(textarea.value).replace(/%([0-9A-F]{2})/g, (m, p1) => String.fromCharCode('0x' + p1)));
            textarea.value = encoded;
            const flag = document.createElement('input');
            flag.type = 'hidden';
            flag.name = 'html_code_is_base64';
            flag.value = '1';
            $el.appendChild(flag);
          }
        }
        $el.submit();
      ">
                <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                <input type="hidden" name="action" value="save">

                <?php if ($edit_id): ?>
                  <input type="hidden" name="target_id" value="<?= h($edit_id) ?>">
                  <input type="hidden" name="current_image" value="<?= h($edit_data['image_url']) ?>">
                <?php endif; ?>

                <!-- Position selector. -->
                <label class="block mb-4">
                  <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('col_location') ?></span>
                  <select name="position" class="form-control">
                    <?php foreach ($banner_positions as $key => $label): ?>
                      <option value="<?= $key ?>" <?= ($edit_data['position'] == $key) ? 'selected' : '' ?>>
                        <?= h($pos_labels[$key] ?? $label) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="button" @click="guideOpen = true" class="flex items-center mt-2 text-theme-primary text-xs hover:underline">
                    <svg class="mr-1 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-information-circle"></use>
                    </svg>
                    <?= _t('btn_placement_guide') ?>
                  </button>
                </label>

                <!-- Type selector -->
                <label class="block mb-4">
                  <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('lbl_banner_type') ?></span>
                  <select name="type" x-model="type" class="form-control">
                    <option value="image"><?= _t('type_image') ?></option>
                    <option value="html"><?= _t('type_html') ?></option>
                  </select>
                </label>

                <!-- Target Theme selector -->
                <label class="block mb-4">
                  <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('lbl_target_theme') ?? '対象テーマ' ?></span>
                  <select name="target_theme" class="form-control">
                    <?php foreach ($themes as $theme_key => $theme_name): ?>
                      <option value="<?= h($theme_key) ?>" <?= (($edit_data['target_theme'] ?? 'all') === $theme_key) ? 'selected' : '' ?>>
                        <?= h($theme_name) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </label>

                <!-- Display condition selector. -->
                <div class="bg-theme-bg mb-4 p-4 border border-theme-border rounded-theme"
                  x-data='{ targetType: <?= json_encode($edit_data['target_type'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?> }'>
                  <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('lbl_display_condition') ?></label>

                  <select name="target_type" x-model="targetType" class="mb-3 form-control">
                    <option value="all"><?= _t('cond_all') ?></option>
                    <option value="home"><?= _t('cond_home') ?></option>
                    <option value="category"><?= _t('cond_category') ?></option>
                    <option value="page"><?= _t('cond_page') ?></option>
                  </select>

                  <div x-show="targetType === 'category'" class="mt-2">
                    <label class="block opacity-70 mb-1 font-bold text-theme-text text-xs"><?= _t('lbl_select_category') ?></label>
                    <select name="target_cat_id" class="text-sm form-control">
                      <option value="0"><?= _t('lbl_select_option') ?></option>
                      <?php foreach ($cats_list as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($edit_data['target_type'] === 'category' && $edit_data['target_id'] == $c['id']) ? 'selected' : '' ?>>
                          <?= h($c['name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>

                  <div x-show="targetType === 'page'" class="mt-2">
                    <label class="block opacity-70 mb-1 font-bold text-theme-text text-xs"><?= _t('lbl_select_post') ?></label>
                    <select name="target_post_id" class="text-sm form-control">
                      <option value="0"><?= _t('lbl_select_option') ?></option>
                      <?php foreach ($posts_list as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($edit_data['target_type'] === 'page' && $edit_data['target_id'] == $p['id']) ? 'selected' : '' ?>>
                          [<?= ($p['type'] === 'page') ? _t('type_page') : _t('type_post') ?>] <?= h($p['title']) ?> (ID: <?= $p['id'] ?>)
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <p class="opacity-50 mt-1 text-[10px] text-theme-text"><?= _t('msg_shows_recent') ?></p>
                  </div>
                </div>

                <!-- Image uploader. -->
                <div class="mb-4" x-show="type === 'image'">
                  <?php
                  $label = _t('lbl_eye_catch');
                  $name = 'image';
                  $value = $edit_data['image_url'] ?? '';
                  $input_style = 'box';
                  $preview_class = 'max-w-full h-32 object-contain';
                  $preview_attrs = ':style="\'width: \' + imageWidth + \'%\'"';
                  $extra_attrs = '';
                  include __DIR__ . '/parts/image_uploader.php';
                  ?>
                </div>

                <!-- Image Width input. -->
                <label class="block mb-4" x-show="type === 'image'">
                  <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('lbl_image_width') ?></span>
                  <div class="flex items-center gap-4">
                    <input type="range" name="image_width" min="10" max="100" step="5" x-model="imageWidth" class="bg-theme-border rounded-theme w-full h-2 accent-theme-primary appearance-none cursor-pointer">
                    <span class="w-12 font-mono text-sm text-right"><span x-text="imageWidth"></span>%</span>
                  </div>
                </label>

                <!-- Link URL input. -->
                <label class="block mb-4" x-show="type === 'image'">
                  <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('col_link') ?></span>
                  <input type="text" name="link_url" value="<?= h($edit_data['link_url']) ?>" class="form-control" placeholder="https://...">
                </label>

                <!-- HTML Code input. -->
                <label class="block mb-4" x-show="type === 'html'">
                  <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('lbl_html_code') ?></span>
                  <textarea name="html_code" rows="6" class="font-mono text-xs form-control" placeholder="<a href...><img src...></a>"><?= h(grinds_url_to_view($edit_data['html_code'] ?? '')) ?></textarea>
                </label>

                <!-- Sort order and status. -->
                <div class="gap-4 grid grid-cols-2 mb-8">
                  <label class="block">
                    <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('col_order') ?></span>
                    <input type="number" name="sort_order" value="<?= h($edit_data['sort_order']) ?>" class="form-control">
                  </label>
                  <div>
                    <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('col_status') ?></label>
                    <div class="flex items-center h-[50px]">
                      <label class="group inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="bg-theme-bg border-theme-border rounded focus:ring-theme-primary/20 w-5 h-5 text-theme-primary form-checkbox" <?= $edit_data['is_active'] ? 'checked' : '' ?>>
                        <span class="ml-2 font-medium text-theme-text group-hover:text-theme-primary transition-colors"><?= _t('lbl_active') ?></span>
                      </label>
                    </div>
                  </div>
                </div>

                <!-- Action buttons. -->
                <div class="flex gap-3">
                  <?php if ($edit_id): ?>
                    <a href="banners.php" class="js-skip-warning flex-1 py-2.5 rounded-theme text-sm text-center btn-secondary"><?= _t('cancel') ?></a>
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

          <!-- Placement guide modal. -->
          <div x-show="guideOpen" class="z-50 fixed inset-0 flex justify-center items-center p-4" style="display: none;">
            <!-- Backdrop. -->
            <div class="fixed inset-0 skin-modal-overlay backdrop-blur-sm" @click="guideOpen = false"></div>
            <div class="z-10 relative bg-theme-surface shadow-theme p-6 border border-theme-border rounded-theme w-full max-w-2xl max-h-[90vh] overflow-y-auto">
              <div class="flex justify-between items-center mb-6">
                <h3 class="font-bold text-theme-text text-xl"><?= _t('btn_placement_guide') ?></h3>
                <button @click="guideOpen = false" class="opacity-50 hover:opacity-100 text-theme-text text-2xl">&times;</button>
              </div>

              <div class="bg-theme-info/10 opacity-80 mb-4 p-3 border border-theme-info/20 rounded-theme text-theme-text text-xs leading-relaxed">
                <?= _t('banner_guide_note') ?>
              </div>

              <?php
              // Check for theme-specific guide image
              $activeTheme = get_option('site_theme', 'default');
              $guideImageRel = '/theme/' . $activeTheme . '/banner_guide.png';
              $guideImageAbs = ROOT_PATH . $guideImageRel;
              $hasGuideImage = file_exists($guideImageAbs);
              ?>

              <?php if ($hasGuideImage): ?>
                <div class="text-center">
                  <img src="<?= h(resolve_url($guideImageRel)) ?>" alt="Banner Placement Guide" class="shadow-theme mx-auto border border-theme-border rounded-theme max-w-full h-auto">
                  <p class="opacity-60 mt-2 text-theme-text text-xs">Theme: <?= h(ucfirst($activeTheme)) ?></p>
                </div>
              <?php else: ?>
                <!-- Visual guide (Default). -->
                <div class="space-y-4 bg-theme-bg p-4 border-2 border-theme-border border-dashed rounded-theme font-bold text-xs text-center">
                  <!-- Header position. -->
                  <div class="bg-theme-info/10 p-3 border border-theme-info/30 rounded-theme text-theme-info">
                    <?= _t('pos_header_top') ?>
                  </div>

                  <div class="flex md:flex-row flex-col gap-4">
                    <!-- Main content position. -->
                    <div class="flex-1 space-y-4 bg-theme-surface p-4 border border-theme-border rounded-theme">
                      <div class="opacity-50 mb-2 text-theme-text"><?= _t('main_content') ?></div>
                      <div class="bg-theme-success/10 p-3 border border-theme-success/30 rounded-theme text-theme-success">
                        <?= _t('pos_content_top') ?>
                      </div>
                      <div class="flex justify-center items-center bg-theme-bg/50 opacity-30 border-2 border-theme-border/50 border-dashed rounded-theme h-20 text-theme-text">
                        <?= _t('article_body') ?>
                      </div>
                      <div class="bg-theme-success/10 p-3 border border-theme-success/30 rounded-theme text-theme-success">
                        <?= _t('pos_content_bottom') ?>
                      </div>
                    </div>

                    <!-- Sidebar position. -->
                    <div class="flex flex-col justify-center items-center space-y-4 bg-theme-bg/50 opacity-50 p-4 border border-theme-border rounded-theme md:w-1/3 text-theme-text">
                      <div class="mb-2 font-bold"><?= _t('lbl_sidebar') ?></div>
                      <div class="text-[10px] leading-tight">
                        <?= _t('menu_widgets') ?><br>
                        (<?= _t('managed_widgets') ?>)
                      </div>
                    </div>
                  </div>

                  <!-- Footer position. -->
                  <div class="bg-theme-primary/10 p-3 border border-theme-primary/30 rounded-theme text-theme-primary">
                    <?= _t('pos_footer') ?>
                  </div>
                </div>
              <?php endif; ?>

              <div class="mt-6 text-center">
                <button @click="guideOpen = false" class="px-8 py-2 rounded-theme btn-primary"><?= _t('view') ?> OK</button>
              </div>
            </div>
          </div>

    </div>

    <script src="<?= grinds_asset_url('assets/js/admin_form_unsaved.js') ?>"></script>
    <script src="<?= grinds_asset_url('assets/js/media_manager.js') ?>"></script>
    <?php include __DIR__ . '/parts/media_picker.php'; ?>
