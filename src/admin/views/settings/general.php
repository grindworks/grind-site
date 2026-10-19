<?php

/**
 * general.php
 * Renders the interface for managing general settings.
 *
 * @var array $opt
 */
if (!defined('GRINDS_APP')) exit; ?>
<div class="space-y-6 bg-theme-surface shadow-theme p-4 sm:p-6 border border-theme-border rounded-theme">
  <form method="post" enctype="multipart/form-data" class="warn-on-unsaved" x-data="{ isSubmitting: false }" @submit="setTimeout(() => isSubmitting = true, 10)">
    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
    <input type="hidden" name="action" value="update_general">

    <div class="mb-6 sm:mb-8">
      <h3 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-xl">
        <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-cog-6-tooth"></use>
        </svg>
        <?= _t('tab_general') ?>
      </h3>
      <p class="opacity-60 ml-0 sm:ml-7 text-theme-text text-sm leading-relaxed"><?= _t('st_general_desc') ?></p>
    </div>

    <div class="gap-6 grid">
      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_site_title') ?></span>
        <input type="text" name="site_name" value="<?= h($opt['name']) ?>" class="form-control">
      </label>

      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm">
          <?= _t('st_admin_title') ?> <span class="opacity-50 font-normal text-xs"><?= _t('lbl_optional') ?></span>
        </span>
        <input type="text" name="admin_title" value="<?= h($opt['admin_title']) ?>" class="form-control" placeholder="<?= h($opt['name']) ?>">
        <p class="opacity-50 mt-2 text-theme-text text-xs">
          <?= _t('st_admin_title_desc') ?>
        </p>
      </label>

      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_site_desc') ?></span>
        <textarea name="site_description" rows="3" class="form-control"><?= h($opt['desc']) ?></textarea>
      </label>

      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_footer') ?></span>
        <input type="text" name="site_footer_text" value="<?= h($opt['footer']) ?>" class="form-control">
      </label>

      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_editor_debounce') ?></span>
        <input type="number" name="editor_debounce_time" value="<?= h($opt['editor_debounce_time'] ?? 1000) ?>" min="100" step="100" class="form-control w-full">
        <p class="opacity-60 mt-1 text-theme-text text-xs"><?= _t('st_editor_debounce_desc') ?></p>
      </label>

      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_media_max_width') ?></span>
        <input type="number" name="media_max_width" value="<?= h($opt['media_max_width']) ?>" class="form-control" min="100" max="10000">
      </label>

      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_media_quality') ?></span>
        <input type="number" name="media_quality" value="<?= h($opt['media_quality']) ?>" class="form-control" min="1" max="100">
      </label>

      <div class="bg-theme-bg mt-2 p-4 border border-theme-border rounded-theme">
        <label class="flex items-start gap-3 cursor-pointer">
          <input type="checkbox" name="site_noindex" value="1" class="mt-1 bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-5 h-5 text-theme-primary form-checkbox shrink-0" <?= get_option('site_noindex') ? 'checked' : '' ?>>
          <div>
            <span class="block font-bold text-theme-text text-sm"><?= _t('st_site_noindex') ?></span>
            <span class="block opacity-60 mt-1 text-theme-text text-xs"><?= _t('st_site_noindex_desc') ?></span>
          </div>
        </label>
        <hr class="border-theme-border my-3">
        <label class="flex items-start gap-3 cursor-pointer">
          <input type="checkbox" name="site_block_ai" value="1" class="mt-1 bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-5 h-5 text-theme-primary form-checkbox shrink-0" <?= get_option('site_block_ai') ? 'checked' : '' ?>>
          <div>
            <span class="block font-bold text-theme-text text-sm"><?= _t('st_site_block_ai') ?></span>
            <span class="block opacity-60 mt-1 text-theme-text text-xs"><?= _t('st_site_block_ai_desc') ?></span>
          </div>
        </label>
      </div>

      <hr class="border-theme-border my-2">

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="flex flex-col bg-theme-bg/50 p-5 border border-theme-border rounded-theme h-full">
          <?php
          $label = _t('st_admin_logo');
          $name = 'admin_logo';
          $value = $opt['admin_logo'];
          $note = _t('st_admin_logo_desc');
          $delete_name = 'delete_admin_logo';
          $input_style = 'box';
          $preview_class = 'h-20 w-auto object-contain';
          $preview_bg_class = 'bg-checker';
          include __DIR__ . '/../parts/image_uploader.php';
          ?>
          <div class="space-y-3 mt-2 pt-4 border-theme-border border-t">
            <label class="flex items-center cursor-pointer">
              <input type="checkbox" name="admin_show_logo_login" value="1" <?= ($opt['show_logo_login'] === false || (string)$opt['show_logo_login'] === '1') ? 'checked' : '' ?> class="bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-4 h-4 text-theme-primary form-checkbox">
              <span class="ml-2 text-theme-text text-xs"><?= _t('lbl_show_logo_login') ?></span>
            </label>
            <label class="flex items-center cursor-pointer">
              <input type="checkbox" name="admin_show_site_name" value="1" <?= $opt['show_site_name'] ? 'checked' : '' ?> class="bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-4 h-4 text-theme-primary form-checkbox">
              <span class="ml-2 text-theme-text text-xs"><?= _t('lbl_show_site_name_with_logo') ?></span>
            </label>
          </div>
        </div>

        <div class="flex flex-col bg-theme-bg/50 p-5 border border-theme-border rounded-theme h-full">
          <?php
          $label = _t('st_favicon');
          $name = 'site_favicon';
          $value = $opt['favicon'];
          $accept = '.ico,.png';
          $delete_name = 'delete_favicon';
          $input_style = 'box';
          $preview_class = 'w-10 h-10 object-contain';
          $preview_bg_class = 'bg-checker';
          $note = '';
          include __DIR__ . '/../parts/image_uploader.php';
          ?>
        </div>

        <div class="flex flex-col bg-theme-bg/50 p-5 border border-theme-border rounded-theme h-full">
          <?php
          $label = _t('st_ogp');
          $name = 'site_ogp_image';
          $value = $opt['ogp_img'];
          $note = _t('st_ogp_desc');
          $delete_name = 'delete_ogp_image';
          $input_style = 'box';
          $preview_class = 'w-full h-32 object-cover';
          $preview_bg_class = 'bg-checker';
          include __DIR__ . '/../parts/image_uploader.php';
          ?>
        </div>
      </div>
    </div>

    <div class="flex justify-end mt-8 pt-6 border-theme-border border-t">
      <button type="submit" :disabled="isSubmitting" class="relative flex justify-center items-center shadow-theme px-6 py-2.5 rounded-theme w-full sm:w-auto font-bold text-sm transition-all disabled:opacity-70 disabled:cursor-not-allowed btn-primary overflow-hidden">
        <div class="flex items-center gap-2 transition-opacity duration-200" :class="isSubmitting ? 'opacity-0' : 'opacity-100'">
          <span><?= _t('btn_save_settings') ?></span>
        </div>
        <div class="absolute inset-0 flex items-center justify-center transition-opacity duration-200" :class="isSubmitting ? 'opacity-100' : 'opacity-0 pointer-events-none'">
          <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
          </svg>
        </div>
      </button>
    </div>
  </form>
</div>
