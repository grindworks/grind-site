<?php

/**
 * general.php
 * Renders the interface for managing general settings.
 */
if (!defined('GRINDS_APP')) exit; ?>
<div class="space-y-6 bg-theme-surface shadow-theme p-4 sm:p-6 border border-theme-border rounded-theme">
  <form method="post" enctype="multipart/form-data" x-data="{ isSubmitting: false }" @submit="isSubmitting = true">
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

    <?php $licClass = is_licensed() ? 'bg-theme-bg border-theme-success/30' : 'bg-theme-danger/5 border-theme-danger/30'; ?>
    <div class="mb-6 p-5 border rounded-theme transition-colors <?= $licClass ?>">

      <div class="flex sm:flex-row flex-col sm:justify-between sm:items-center gap-2 <?= is_licensed() ? 'mb-0' : 'mb-3' ?>">
        <label class="flex items-center gap-2 font-bold text-theme-text text-sm">
          <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-key"></use>
          </svg>
          <?= _t('lic_title') ?>
        </label>

        <?php if (is_licensed()): ?>
          <span class="inline-flex items-center bg-theme-success/10 px-3 py-1 border border-theme-success/20 rounded-full w-fit font-bold text-theme-success text-xs">
            <svg class="mr-1 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check-circle"></use>
            </svg>
            <?= _t('lic_status_active') ?>
          </span>
        <?php else: ?>
          <span class="inline-flex bg-theme-danger/10 px-3 py-1 border border-theme-danger/20 rounded-full w-fit font-bold text-theme-danger text-xs">
            <?= _t('lic_status_trial') ?>
          </span>
        <?php endif; ?>
      </div>

      <?php if (is_licensed()): ?>
        <div x-data="{ open: false }">
          <!-- Toggle button: styled as a subtle pill -->
          <button type="button" @click="open = !open"
            class="group flex items-center gap-2 mt-4 px-3 py-1.5 rounded-full border border-theme-success/20 bg-theme-success/5 hover:bg-theme-success/15 text-theme-success text-xs font-semibold transition-all duration-200 focus:outline-none">
            <svg class="w-3.5 h-3.5 transition-transform duration-300" :class="open ? 'rotate-90' : ''"
              fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-pencil-square"></use>
            </svg>
            <span x-text="open ? '<?= _t('lic_collapse') ?>' : '<?= _t('lic_edit_key') ?>'"></span>
          </button>

          <!-- Accordion content -->
          <div x-show="open"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0 scale-y-95 -translate-y-1"
            x-transition:enter-end="opacity-100 scale-y-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-y-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-y-95 -translate-y-1"
            class="mt-3 p-4 bg-theme-bg/60 border border-theme-success/20 rounded-theme backdrop-blur-sm"
            style="display:none;">
            <label class="block mb-1.5 text-[11px] font-bold tracking-widest opacity-50 uppercase"><?= _t('lic_title') ?></label>
            <div class="relative" x-data="{ showKey: false }">
              <input :type="showKey ? 'text' : 'password'" name="license_key" value="<?= h(get_option('license_key')) ?>"
                class="font-mono text-sm tracking-widest form-control pr-10"
                placeholder="<?= _t('lic_ph') ?>" autocomplete="off">
              <button type="button" @click="showKey = !showKey" class="right-0 absolute inset-y-0 flex items-center opacity-50 hover:opacity-100 px-3 focus:outline-none text-theme-text" tabindex="-1">
                <svg x-show="!showKey" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
                </svg>
                <svg x-show="showKey" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye-slash"></use>
                </svg>
              </button>
            </div>
            <p class="mt-2 opacity-50 text-theme-text text-[11px]"><?= _t('lic_success_msg') ?></p>
          </div>
        </div>

      <?php else: ?>
        <div class="relative" x-data="{ showKey: false }">
          <input :type="showKey ? 'text' : 'password'" name="license_key" value="<?= h(get_option('license_key')) ?>"
            class="font-mono text-sm tracking-wider form-control pr-10"
            placeholder="<?= _t('lic_ph') ?>" autocomplete="off">
          <button type="button" @click="showKey = !showKey" class="right-0 absolute inset-y-0 flex items-center opacity-50 hover:opacity-100 px-3 focus:outline-none text-theme-text" tabindex="-1">
            <svg x-show="!showKey" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
            </svg>
            <svg x-show="showKey" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye-slash"></use>
            </svg>
          </button>
        </div>
        <div class="mt-3 text-theme-danger text-xs leading-relaxed">
          <p class="font-bold"><?= _t('lic_warn_title') ?></p>
          <p class="opacity-80 mt-1"><?= _t('lic_warn_desc') ?></p>
          <?php $pricingUrl = (function_exists('grinds_detect_language') && grinds_detect_language() === 'ja') ? 'https://grindsite.com/ja/#pricing' : 'https://grindsite.com/#pricing'; ?>
          <a href="<?= $pricingUrl ?>" target="_blank" class="inline-flex items-center mt-2 font-bold text-theme-primary hover:underline">
            <?= _t('lic_buy_link') ?>
          </a>
        </div>
      <?php endif; ?>
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
      <button type="submit" :disabled="isSubmitting" class="flex justify-center items-center gap-2 shadow-theme px-6 py-2.5 rounded-theme w-full sm:w-auto font-bold text-sm transition-all disabled:opacity-70 disabled:cursor-not-allowed btn-primary">
        <svg x-show="isSubmitting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
        </svg>
        <span x-text="isSubmitting ? '...' : '<?= _t('btn_save_settings') ?>'"><?= _t('btn_save_settings') ?></span>
      </button>
    </div>
  </form>
</div>
