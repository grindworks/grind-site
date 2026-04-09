<?php

/**
 * tags.php
 *
 * Renders the user interface for managing post tags.
 */
if (!defined('GRINDS_APP')) exit;

// Define form action URL.
$formAction = 'tags.php';
$csrf_token = generate_csrf_token();
?>

<!-- Hidden form for bulk actions. -->
<?php include __DIR__ . '/parts/hidden_action_form.php'; ?>

<div class="relative flex lg:flex-row flex-col gap-8"
  x-init="if(mobileFormOpen) window.toggleScrollLock(true); $watch('mobileFormOpen', val => window.toggleScrollLock(val));"
  x-data="{
    mobileFormOpen: <?= $edit_id ? 'true' : 'false' ?>,
    isSubmitting: false
  }">

  <!-- Mobile floating action button. -->
  <?php include __DIR__ . '/parts/fab.php'; ?>

  <!-- Tag list column. -->
  <div class="order-2 lg:order-1 w-full lg:w-2/3">

    <div class="mb-4">
      <h2 class="flex items-center gap-2 font-bold text-theme-text text-xl whitespace-nowrap">
        <svg class="w-6 h-6 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-tag"></use>
        </svg>
        <?= _t('menu_tags') ?>
      </h2>
    </div>

    <!-- Bulk actions and limit selector. -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
      <!-- Search form. -->
      <form method="get" action="tags.php" class="relative w-full sm:w-auto">
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
            <th class="px-6 py-4 text-center"><?= _t('col_count') ?></th>
            <th class="px-6 py-4 text-right"><?= _t('col_action') ?></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-theme-border">
          <?php if (empty($tags)): ?>
            <tr>
              <td colspan="5" class="p-8">
                <div class="flex flex-col justify-center items-center py-12 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
                  <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-tag"></use>
                    </svg>
                  </div>
                  <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('no_data') ?></h3>
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($tags as $tag): ?>
              <tr class="hover:bg-theme-bg/50 transition-colors <?= ($edit_id == $tag['id']) ? 'bg-theme-primary/5' : '' ?>">
                <td class="px-6 py-4">
                  <input type="checkbox" name="ids[]" value="<?= $tag['id'] ?>" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary item-checkbox form-checkbox">
                </td>
                <td class="hidden xl:table-cell opacity-70 px-6 py-4 font-mono text-theme-text text-sm align-middle"><?= $tag['id'] ?></td>
                <td class="px-6 py-4 align-middle">
                  <div class="flex items-center font-bold text-theme-text text-base break-all">
                    <span class="inline-flex justify-center items-center bg-theme-primary/10 mr-2 border border-theme-primary/20 rounded-theme w-6 h-6 text-theme-primary">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-hashtag"></use>
                      </svg>
                    </span>
                    <?= h($tag['name']) ?>
                  </div>
                  <div class="opacity-50 mt-1 pl-8 font-mono text-theme-text text-xs"><?= h($tag['slug']) ?></div>
                </td>
                <td class="px-6 py-4 text-center align-middle">
                  <span class="inline-flex justify-center items-center bg-theme-bg px-2.5 py-0.5 border border-theme-border rounded-full font-bold text-theme-text text-xs">
                    <?= $tag['post_count'] ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-right align-middle whitespace-nowrap">
                  <div class="flex justify-end items-center gap-4 h-full">
                    <a href="?edit_id=<?= $tag['id'] ?>" class="flex items-center p-1 text-theme-primary hover:scale-110 transition" title="<?= h(_t('edit')) ?>" aria-label="<?= h(_t('edit')) ?>">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-pencil-square"></use>
                      </svg>
                    </a>
                    <button type="button" onclick="executeAction('delete', <?= $tag['id'] ?>)" class="flex items-center bg-transparent p-1 border-none text-theme-danger hover:scale-110 transition cursor-pointer" title="<?= h(_t('delete')) ?>" aria-label="<?= h(_t('delete')) ?>">
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
      <?php if (empty($tags)): ?>
        <div class="flex flex-col justify-center items-center py-12 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
          <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-tag"></use>
            </svg>
          </div>
          <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('no_data') ?></h3>
        </div>
      <?php else: ?>
        <div class="flex items-center gap-2 mb-3 px-2">
          <input type="checkbox" id="mobile-select-all" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary form-checkbox">
          <label for="mobile-select-all" class="text-sm font-bold text-theme-text"><?= _t('all') ?></label>
        </div>
        <?php foreach ($tags as $tag): ?>
          <div class="bg-theme-surface border border-theme-border rounded-theme shadow-theme relative overflow-hidden <?= ($edit_id == $tag['id']) ? 'ring-2 ring-theme-primary' : '' ?>">
            <div class="top-3 left-3 z-10 absolute">
              <input type="checkbox" name="ids[]" value="<?= $tag['id'] ?>" class="bg-theme-bg/90 backdrop-blur-sm border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary item-checkbox form-checkbox">
            </div>

            <div class="p-4 pl-12">
              <div class="flex justify-between items-start mb-2">
                <div class="flex items-center">
                  <span class="opacity-70 mr-2 text-theme-primary">#</span>
                  <h4 class="font-bold text-theme-text text-lg leading-tight break-all"><?= h($tag['name']) ?></h4>
                </div>
                <span class="bg-theme-bg px-2 py-1 border border-theme-border rounded-full font-bold text-theme-text text-xs"><?= $tag['post_count'] ?> <?= _t('unit_posts') ?></span>
              </div>
              <div class="opacity-60 mb-3 pl-4 font-mono text-theme-text text-xs"><?= h($tag['slug']) ?></div>

              <div class="flex justify-between items-center pt-3 border-theme-border border-t">
                <div class="opacity-50 text-theme-text text-xs"><?= _t('col_id') ?>: <?= $tag['id'] ?></div>
                <div class="flex items-center gap-4">
                  <a href="?edit_id=<?= $tag['id'] ?>" class="flex items-center gap-1 py-1 font-bold text-theme-primary text-xs hover:underline">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-pencil-square"></use>
                    </svg>
                    <?= _t('edit') ?>
                  </a>
                  <button type="button" onclick="executeAction('delete', <?= $tag['id'] ?>)" class="flex items-center gap-1 bg-transparent p-0 py-1 border-none font-bold text-theme-danger text-xs hover:underline">
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
        <input type="hidden" name="action" value="save">
        <?php if ($edit_id): ?><input type="hidden" name="target_id" value="<?= h($edit_id) ?>"><?php endif; ?>

        <div class="mb-4">
          <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('col_name') ?></label>
          <input type="text" name="name" value="<?= h($edit_data['name']) ?>" required class="form-control">
        </div>

        <div class="mb-6">
          <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('col_slug') ?></label>
          <div class="flex">
            <span class="inline-flex items-center bg-theme-bg opacity-60 px-3 border border-theme-border border-r-0 rounded-l-theme text-theme-text text-xs">/</span>
            <input type="text" name="slug" value="<?= h($edit_data['slug']) ?>"
              @blur="$el.value = $el.value.toLowerCase().trim().replace(/[\s_]+/g, '-').replace(/[^\p{L}\p{N}-]/gu, '').replace(/-+/g, '-').replace(/^-+|-+$/g, '')"
              class="rounded-l-none font-mono text-sm form-control placeholder-theme-text/30" placeholder="<?= _t('ph_auto_generate') ?>">
          </div>
        </div>

        <div class="flex gap-3">
          <?php if ($edit_id): ?>
            <a href="tags.php" class="js-skip-warning flex-1 py-2.5 rounded-theme text-sm text-center btn-secondary"><?= _t('cancel') ?></a>
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
