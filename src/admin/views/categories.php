<?php

/**
 * categories.php
 *
 * Renders the user interface for managing post categories.
 */
if (!defined('GRINDS_APP')) exit;

// Prepare categories list for JS
$jsCategories = array_map(function ($c) {
  return ['id' => $c['id'], 'name' => $c['name']];
}, $allCategories);

// Define form action URL.
$formAction = 'categories.php';
$csrf_token = generate_csrf_token();
?>

<!-- Hidden form for bulk actions. -->
<?php include __DIR__ . '/parts/hidden_action_form.php'; ?>

<div class="relative flex lg:flex-row flex-col gap-8"
  x-data='{
    mobileFormOpen: <?= $edit_id ? 'true' : 'false' ?>,
    isSubmitting: false,
    deleteModalOpen: false,
    deleteTarget: null,
    reassignId: "",
    categories: <?= json_encode($jsCategories, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>,
    isMobile: window.innerWidth < 1024,
    _lockedState: false,

    openDeleteModal(id, count, name) {
      this.deleteTarget = { id: id, count: count, name: name };
      this.reassignId = "";
      if (count > 0) {
        const others = this.categories.filter(c => c.id != id);
        if (others.length > 0) this.reassignId = others[0].id;
      }
      this.deleteModalOpen = true;
    },

    confirmDelete() {
      if (!this.deleteTarget) return;

      const form = document.getElementById("unified-action-form");
      const actionInput=document.getElementById("form-action-input");

      form.querySelectorAll(".dynamic-input").forEach(el=> el.remove());

      actionInput.value = "delete";

      const inputId = document.createElement("input");
      inputId.type = "hidden";
      inputId.name = "ids[]";
      inputId.value = this.deleteTarget.id;
      inputId.className = "dynamic-input";
      form.appendChild(inputId);

      const inputReassign = document.createElement("input");
      inputReassign.type = "hidden";
      inputReassign.name = "reassign_id";
      inputReassign.value = this.reassignId;
      inputReassign.className = "dynamic-input";
      form.appendChild(inputReassign);

      form.submit();
    }
  }'
  @resize.window="isMobile = window.innerWidth < 1024"
  x-effect="
    const shouldLock = (mobileFormOpen && isMobile) || deleteModalOpen;
    if (_lockedState !== shouldLock) {
      window.toggleScrollLock(shouldLock);
      _lockedState = shouldLock;
    }
  ">

  <!-- Mobile floating action button. -->
  <?php include __DIR__ . '/parts/fab.php'; ?>

  <!-- Category list column. -->
  <div class="order-2 lg:order-1 w-full lg:w-2/3">

    <div class="mb-4">
      <h2 class="flex items-center gap-2 font-bold text-theme-text text-xl whitespace-nowrap">
        <svg class="w-6 h-6 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-rectangle-stack"></use>
        </svg>
        <?= _t('menu_categories') ?>
      </h2>
    </div>

    <!-- Bulk actions and limit selector. -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
      <!-- Search form. -->
      <form method="get" action="categories.php" class="relative w-full sm:w-auto">
        <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="<?= _t('search') ?>"
          class="bg-theme-bg pl-8 border-theme-border w-full sm:w-48 focus:w-64 text-theme-text text-xs transition-all form-control-sm">
        <svg class="top-1/2 left-2.5 absolute opacity-50 w-3.5 h-3.5 text-theme-text -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
        </svg>
      </form>

      <div class="flex justify-end items-center gap-4 w-full sm:w-auto">
        <div class="flex items-center gap-2">
          <select id="bulk-action-selector" class="bg-theme-bg border-theme-border w-32 text-theme-text cursor-pointer form-control-sm">
            <option value=""><?= _t('lbl_bulk_actions') ?></option>
            <option value="delete"><?= _t('delete') ?></option>
          </select>
          <button type="button" id="bulk-apply" class="px-3 py-1.5 text-xs whitespace-nowrap btn-secondary">
            <?= _t('apply') ?>
          </button>
        </div>
        <?php include __DIR__ . '/parts/limit_selector.php'; ?>
      </div>
    </div>

    <!-- Desktop table view. -->
    <div class="hidden md:block bg-theme-surface shadow-theme border border-theme-border rounded-theme overflow-x-auto">
      <table class="min-w-full leading-normal">
        <thead>
          <tr class="bg-theme-bg/50 border-theme-border border-b font-bold text-theme-text/60 text-xs text-left uppercase tracking-wider">
            <th class="px-6 py-4 w-10"><input type="checkbox" id="select-all" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary form-checkbox"></th>
            <?php $sorter->renderTh('id', _t('col_id'), 'hidden xl:table-cell'); ?>
            <?php $sorter->renderTh('name', _t('col_name')); ?>
            <?php $sorter->renderTh('sort_order', _t('col_order'), 'hidden lg:table-cell'); ?>
            <th class="px-6 py-4 text-center"><?= _t('col_count') ?></th>
            <th class="px-6 py-4 text-right"><?= _t('col_action') ?></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-theme-border">
          <?php foreach ($categories as $cat):
            $count = $cat['post_count'];
            $isDefault = ($cat['slug'] === 'uncategorized');
            $canDelete = !$isDefault;
          ?>
            <tr class="hover:bg-theme-bg/50 transition-colors <?= ($edit_id == $cat['id']) ? 'bg-theme-primary/5' : '' ?>">
              <td class="px-6 py-4">
                <input type="checkbox" name="ids[]" value="<?= $cat['id'] ?>" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary item-checkbox form-checkbox" <?= $canDelete ? '' : 'disabled' ?>>
              </td>
              <td class="hidden xl:table-cell opacity-70 px-6 py-4 font-mono text-theme-text text-sm align-middle"><?= $cat['id'] ?></td>
              <td class="px-6 py-4 align-middle">
                <div class="mb-1 font-bold text-theme-text text-base break-all"><?= h($cat['name']) ?></div>
                <div class="flex flex-wrap items-center gap-2">
                  <span class="bg-theme-bg opacity-50 px-1.5 py-0.5 border border-theme-border rounded-theme font-mono text-theme-text text-xs"><?= h($cat['slug']) ?></span>
                  <?php if ($isDefault): ?><span class="bg-theme-primary/10 px-1.5 py-0.5 border border-theme-primary/20 rounded-theme font-bold text-[10px] text-theme-primary"><?= _t('default') ?></span><?php endif; ?>
                  <?php if (!empty($cat['category_theme'])): ?>
                    <span class="bg-theme-info/10 px-1.5 py-0.5 border border-theme-info/20 rounded-theme font-bold text-[10px] text-theme-info">
                      <?= _t('tab_theme') ?>: <?= h($cat['category_theme']) ?>
                    </span>
                  <?php endif; ?>
                </div>
              </td>
              <td class="hidden lg:table-cell px-6 py-4 font-mono text-theme-text text-sm text-center align-middle"><?= $cat['sort_order'] ?></td>
              <td class="px-6 py-4 text-center align-middle">
                <span class="inline-flex justify-center items-center bg-theme-bg px-2.5 py-0.5 border border-theme-border rounded-full font-bold text-theme-text text-xs"><?= $count ?></span>
              </td>
              <td class="px-6 py-4 text-sm text-right align-middle whitespace-nowrap">
                <div class="flex justify-end items-center gap-4 h-full">
                  <a href="?edit_id=<?= $cat['id'] ?>" class="flex items-center p-1 text-theme-primary hover:scale-110 transition" title="<?= h(_t('edit')) ?>" aria-label="<?= h(_t('edit')) ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-pencil-square"></use>
                    </svg>
                  </a>
                  <?php if (!$canDelete): ?>
                    <span class="opacity-20 p-1 text-theme-text cursor-not-allowed">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                      </svg>
                    </span>
                  <?php else: ?>
                    <button type="button" @click='openDeleteModal(<?= $cat['id'] ?>, <?= $count ?>, <?= json_encode($cat['name'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>)' class="flex items-center bg-transparent p-1 border-none text-theme-danger hover:scale-110 transition cursor-pointer" title="<?= h(_t('delete')) ?>" aria-label="<?= h(_t('delete')) ?>">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                      </svg>
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Mobile card view. -->
    <div class="md:hidden space-y-4">
      <?php if (empty($categories)): ?>
        <div class="flex flex-col justify-center items-center py-12 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
          <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-rectangle-stack"></use>
            </svg>
          </div>
          <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('no_data') ?></h3>
        </div>
      <?php else: ?>
        <div class="flex items-center gap-2 mb-3 px-2">
          <input type="checkbox" id="mobile-select-all" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary form-checkbox">
          <label for="mobile-select-all" class="text-sm font-bold text-theme-text"><?= _t('all') ?></label>
        </div>
        <?php foreach ($categories as $cat):
          $count = $cat['post_count'];
          $isDefault = ($cat['slug'] === 'uncategorized');
          $canDelete = !$isDefault;
        ?>
          <div class="bg-theme-surface border border-theme-border rounded-theme shadow-theme relative overflow-hidden <?= ($edit_id == $cat['id']) ? 'ring-2 ring-theme-primary ring-offset-2 ring-offset-theme-bg' : '' ?>">
            <div class="top-3 left-3 z-10 absolute">
              <input type="checkbox" name="ids[]" value="<?= $cat['id'] ?>" class="bg-theme-bg/90 backdrop-blur-sm border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary item-checkbox form-checkbox" <?= $canDelete ? '' : 'disabled' ?>>
            </div>

            <div class="p-4 pl-12">
              <div class="flex justify-between items-start mb-2">
                <div>
                  <h4 class="font-bold text-theme-text text-lg leading-tight break-all"><?= h($cat['name']) ?></h4>
                  <div class="flex flex-wrap items-center gap-2 mt-1 font-mono text-theme-text text-xs">
                    <span class="bg-theme-bg opacity-50 px-1.5 py-0.5 border border-theme-border rounded-theme"><?= h($cat['slug']) ?></span>
                    <?php if ($isDefault): ?><span class="bg-theme-primary/10 px-1.5 py-0.5 border border-theme-primary/20 rounded-theme font-bold text-[10px] text-theme-primary"><?= _t('default') ?></span><?php endif; ?>
                    <?php if (!empty($cat['category_theme'])): ?>
                      <span class="bg-theme-info/10 px-1.5 py-0.5 border border-theme-info/20 rounded-theme font-bold text-[10px] text-theme-info">
                        <?= _t('tab_theme') ?>: <?= h($cat['category_theme']) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="flex flex-col items-end gap-1">
                  <span class="bg-theme-bg px-2 py-1 border border-theme-border rounded-full font-bold text-theme-text text-xs"><?= $count ?> <?= _t('unit_posts') ?></span>
                </div>
              </div>

              <div class="flex justify-between items-center mt-3 pt-3 border-theme-border border-t">
                <div class="opacity-50 text-theme-text text-xs"><?= _t('col_order') ?>: <?= $cat['sort_order'] ?></div>
                <div class="flex items-center gap-4">
                  <a href="?edit_id=<?= $cat['id'] ?>" class="flex items-center py-1 font-bold text-theme-primary text-xs hover:underline">
                    <svg class="mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-pencil-square"></use>
                    </svg>
                    <?= _t('edit') ?>
                  </a>
                  <?php if (!$canDelete): ?>
                    <span class="flex items-center opacity-30 text-theme-text text-xs cursor-not-allowed">
                      <svg class="mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                      </svg>
                      <?= _t('delete') ?>
                    </span>
                  <?php else: ?>
                    <button type="button" @click='openDeleteModal(<?= $cat['id'] ?>, <?= $count ?>, <?= json_encode($cat['name'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>)' class="flex items-center bg-transparent p-0 py-1 border-none font-bold text-theme-danger text-xs hover:underline">
                      <svg class="mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                      </svg>
                      <?= _t('delete') ?>
                    </button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div class="flex justify-end mt-4">
      <?php if (isset($paginator)) echo $paginator->render(); ?>
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
        <button @click="mobileFormOpen = false" class="lg:hidden opacity-50 hover:opacity-100 p-1 text-theme-text transition-opacity">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
          </svg>
        </button>
      </div>

      <form method="post" class="warn-on-unsaved" @submit="setTimeout(() => isSubmitting = true, 10)">
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
        <?php if ($edit_id): ?><input type="hidden" name="target_id" value="<?= h($edit_id) ?>"><?php endif; ?>

        <div class="mb-4">
          <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('col_name') ?></label>
          <input type="text" name="name" value="<?= h($edit_data['name']) ?>" required class="form-control">
        </div>

        <div class="mb-4">
          <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('col_slug') ?></label>
          <div class="flex">
            <span class="inline-flex items-center bg-theme-bg opacity-60 px-3 border border-theme-border border-r-0 rounded-l-theme text-theme-text text-xs">/</span>
            <input type="text" name="slug" value="<?= h($edit_data['slug']) ?>"
              @blur="if (!$el.readOnly) $el.value = $el.value.toLowerCase().trim().replace(/[\s_]+/g, '-').replace(/[^\p{L}\p{N}-]/gu, '').replace(/-+/g, '-').replace(/^-+|-+$/g, '')"
              class="rounded-l-none font-mono text-sm form-control placeholder-theme-text/30" placeholder="<?= _t('ph_auto_generate') ?>" <?= ($edit_data['slug'] === 'uncategorized') ? 'readonly' : '' ?>>
          </div>
        </div>

        <!-- Category theme selector. -->
        <div class="mb-4">
          <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('lbl_category_theme') ?></label>
          <select name="category_theme" class="form-control">
            <option value="" <?= empty($edit_data['category_theme']) ? 'selected' : '' ?>>
              <?= _t('theme_default') ?>
            </option>
            <?php foreach ($available_themes as $dir => $name): ?>
              <option value="<?= h($dir) ?>" <?= ($edit_data['category_theme'] ?? '') === $dir ? 'selected' : '' ?>>
                <?= h($name) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <p class="opacity-50 mt-1 text-[10px] text-theme-text">
            <?= _t('help_category_theme') ?>
          </p>
        </div>

        <!-- Custom Fields (Meta Data) -->
        <?php
        $themeForMeta = !empty($edit_data['category_theme']) ? $edit_data['category_theme'] : null;
        $customFields = function_exists('grinds_get_theme_custom_fields') ? grinds_get_theme_custom_fields('category', $themeForMeta) : [];
        $catMetaData = json_decode($edit_data['meta_data'] ?? '{}', true);
        if (!is_array($catMetaData)) $catMetaData = [];
        ?>
        <?php if (!empty($customFields)): ?>
          <div class="bg-theme-surface mb-6 p-4 border border-theme-border rounded-theme" x-data="{ openMeta: true }">
            <button type="button" @click="openMeta = !openMeta" class="flex justify-between items-center w-full text-left focus:outline-none">
              <span class="block opacity-70 font-bold text-theme-text text-xs cursor-pointer"><?= function_exists('_t') ? _t('Custom Fields') ?? 'Custom Fields' : 'Custom Fields' ?></span>
              <svg class="opacity-50 w-4 h-4 text-theme-text transition-transform" :class="openMeta ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
              </svg>
            </button>
            <div x-show="openMeta" x-collapse>
              <div class="space-y-4 mt-4 pt-4 border-theme-border border-t">
                <?php foreach ($customFields as $cf):
                  $cfName = $cf['name'] ?? '';
                  $cfLabel = $cf['label'] ?? $cfName;
                  $cfType = $cf['type'] ?? 'text';
                  $cfVal = $catMetaData[$cfName] ?? '';
                ?>
                  <div>
                    <?php if ($cfType !== 'checkbox'): ?>
                      <label class="block opacity-60 mb-1 font-bold text-theme-text text-xs"><?= h(function_exists('_t') ? _t($cfLabel) : $cfLabel) ?></label>
                    <?php endif; ?>

                    <?php if ($cfType === 'textarea'): ?>
                      <textarea name="meta_data[<?= h($cfName) ?>]" rows="3" class="text-xs form-control"><?= h($cfVal) ?></textarea>

                    <?php elseif ($cfType === 'image'): ?>
                      <div x-data="{
                                            previewUrl: <?= htmlspecialchars(json_encode(get_media_url($cfVal), JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>,
                                            isDeleted: false
                                        }"
                        @set-meta-image-<?= h($cfName) ?>.window="previewUrl = $event.detail.url; isDeleted = false; document.getElementById('meta_data_<?= h($cfName) ?>_input').value = $event.detail.url;">

                        <div class="mb-2 bg-checker border border-theme-border rounded-theme w-full h-32 overflow-hidden flex items-center justify-center" x-show="previewUrl && !isDeleted">
                          <img :src="previewUrl" class="w-full h-full object-contain">
                        </div>

                        <div class="flex flex-col gap-2">
                          <button type="button" @click="window.dispatchEvent(new CustomEvent('open-media-picker', { detail: { type: 'image', callback: (file) => { window.dispatchEvent(new CustomEvent('set-meta-image-<?= h($cfName) ?>', { detail: { url: file.url } })); } } }));" class="flex justify-center items-center hover:bg-theme-bg px-4 py-2 border border-theme-border rounded-theme w-full text-xs text-center transition-colors btn-secondary">
                            <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
                            </svg>
                            <span><?= h(function_exists('_t') ? _t('btn_select_library') ?? 'Select File' : 'Select File') ?></span>
                          </button>
                          <label class="px-3 py-1 rounded-theme w-full text-xs text-center cursor-pointer btn-secondary">
                            <?= h(function_exists('_t') ? _t('select_file') ?? 'Upload' : 'Upload') ?>
                            <input type="file" name="meta_data_<?= h($cfName) ?>" accept="image/*" class="hidden" @change="
                                if (typeof isUploading !== 'undefined') isUploading = true;
                                const file = $event.target.files[0]; if(file){ if(previewUrl && previewUrl.startsWith('blob:')) URL.revokeObjectURL(previewUrl); previewUrl = URL.createObjectURL(file); isDeleted = false; }
                                setTimeout(() => { if (typeof isUploading !== 'undefined') isUploading = false; }, 1000);
                            ">
                          </label>

                          <?php if (!empty($cfVal)): ?>
                            <label class="flex justify-center items-center bg-theme-danger/10 px-2 py-1 border border-theme-danger/30 rounded-theme text-theme-danger transition-colors cursor-pointer" :class="{'bg-theme-danger text-white border-theme-danger': isDeleted}" title="<?= h(function_exists('_t') ? _t('delete') ?? 'Delete' : 'Delete') ?>">
                              <input type="checkbox" name="delete_meta_data_<?= h($cfName) ?>" value="1" class="hidden" x-model="isDeleted">
                              <span x-show="!isDeleted">&times; <?= h(function_exists('_t') ? _t('delete') ?? 'Delete' : 'Delete') ?></span>
                              <span x-show="isDeleted" class="font-bold text-[10px]"><?= h(function_exists('_t') ? _t('btn_restore') ?? 'Restore' : 'Restore') ?></span>
                            </label>
                          <?php endif; ?>

                          <input type="hidden" name="current_meta_data[<?= h($cfName) ?>]" value="<?= h($cfVal) ?>">
                          <!-- Update name attribute to _url format to ensure correct backend processing -->
                          <input type="hidden" name="meta_data_<?= h($cfName) ?>_url" id="meta_data_<?= h($cfName) ?>_input" value="<?= h($cfVal) ?>" :disabled="isDeleted">
                        </div>
                      </div>

                    <?php elseif ($cfType === 'date'): ?>
                      <input type="date" name="meta_data[<?= h($cfName) ?>]" value="<?= h($cfVal) ?>" class="text-xs form-control">

                    <?php elseif ($cfType === 'select' && isset($cf['options']) && is_array($cf['options'])): ?>
                      <select name="meta_data[<?= h($cfName) ?>]" class="text-xs form-control cursor-pointer">
                        <option value=""><?= function_exists('_t') ? _t('lbl_select_option') ?? 'Select...' : 'Select...' ?></option>
                        <?php foreach ($cf['options'] as $optVal => $optLabel):
                          if (is_numeric($optVal)) {
                            $optVal = $optLabel;
                          } // Handle unkeyed arrays
                        ?>
                          <option value="<?= h($optVal) ?>" <?= ($cfVal === (string)$optVal) ? 'selected' : '' ?>><?= h(function_exists('_t') ? _t($optLabel) : $optLabel) ?></option>
                        <?php endforeach; ?>
                      </select>

                    <?php elseif ($cfType === 'checkbox'): ?>
                      <label class="flex items-center cursor-pointer mt-1">
                        <input type="hidden" name="meta_data[<?= h($cfName) ?>]" value="0">
                        <input type="checkbox" name="meta_data[<?= h($cfName) ?>]" value="1" class="bg-theme-bg border-theme-border rounded focus:ring-theme-primary/20 w-5 h-5 text-theme-primary form-checkbox" <?= (string)$cfVal === '1' ? 'checked' : '' ?>>
                        <span class="ml-2 text-theme-text text-sm font-bold opacity-80"><?= h(function_exists('_t') ? _t($cfLabel) : $cfLabel) ?></span>
                      </label>

                    <?php else: ?>
                      <input type="text" name="meta_data[<?= h($cfName) ?>]" value="<?= h($cfVal) ?>" class="text-xs form-control">
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <div class="mb-8">
          <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('col_order') ?></label>
          <input type="number" name="sort_order" value="<?= h($edit_data['sort_order']) ?>" class="form-control">
        </div>

        <!-- Action buttons. -->
        <div class="flex gap-3">
          <?php if ($edit_id): ?>
            <a href="categories.php" class="js-skip-warning flex-1 py-2.5 rounded-theme text-sm text-center btn-secondary"><?= _t('cancel') ?></a>
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

  <!-- Delete Confirmation Modal -->
  <template x-teleport="body">
    <div x-show="deleteModalOpen" class="z-60 fixed inset-0 flex justify-center items-center p-4" style="display: none;" x-cloak>
      <div class="fixed inset-0 skin-modal-overlay backdrop-blur-sm" @click="deleteModalOpen = false"></div>
      <div class="z-10 relative bg-theme-surface shadow-theme p-6 border border-theme-border rounded-theme w-full max-w-md max-h-[90vh] overflow-y-auto custom-scrollbar">
        <h3 class="mb-4 font-bold text-theme-text text-lg"><?= _t('confirm_delete') ?></h3>

        <p class="mb-4 text-theme-text text-sm">
          <span class="font-bold" x-text="deleteTarget?.name"></span>
          <span x-show="deleteTarget?.count > 0">
            <span class="opacity-70"><?= _t('msg_cat_has_posts') ?></span>
            <span class="font-bold text-theme-primary" x-text="deleteTarget?.count"></span>
            <span class="opacity-70"><?= _t('unit_posts') ?>.</span>
          </span>
        </p>

        <div class="mb-6" x-show="deleteTarget?.count > 0">
          <label class="block opacity-70 mb-2 font-bold text-theme-text text-xs"><?= _t('lbl_reassign_posts') ?></label>
          <select x-model="reassignId" class="text-sm form-control">
            <template x-for="cat in categories.filter(c => c.id != deleteTarget?.id)" :key="cat.id">
              <option :value="cat.id" x-text="cat.name"></option>
            </template>
          </select>
        </div>

        <div class="flex justify-end gap-3">
          <button @click="deleteModalOpen = false" class="px-4 py-2 rounded-theme text-sm btn-secondary"><?= _t('cancel') ?></button>
          <button @click="confirmDelete()" class="bg-theme-danger shadow-theme px-4 py-2 border-theme-danger rounded-theme text-white text-sm btn-primary"><?= _t('delete') ?></button>
        </div>
      </div>
    </div>
  </template>

  <script src="<?= grinds_asset_url('assets/js/admin_form_unsaved.js') ?>"></script>
  <script src="<?= grinds_asset_url('assets/js/media_manager.js') ?>"></script>
  <?php include __DIR__ . '/parts/media_picker.php'; ?>

</div>
