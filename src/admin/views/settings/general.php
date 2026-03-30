<?php

/**
 * general.php
 * Renders the interface for managing general settings.
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

    <style>
      @keyframes shimmer {
        0% {
          background-position: 200% 0;
        }

        100% {
          background-position: -200% 0;
        }
      }
    </style>
    <?php
    $is_pro = is_licensed();
    $licStatus = function_exists('get_license_status') ? get_license_status() : 'unregistered';
    $is_agency = ($licStatus === 'agency');
    ?>

    <?php if ($is_pro): ?>
      <!-- Render premium license card -->
      <div class="relative mb-6 overflow-hidden rounded-theme shadow-theme border border-theme-border transition-all duration-500 hover:shadow-lg group bg-theme-surface">

        <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-1000 pointer-events-none z-10"
          style="background: linear-gradient(105deg, transparent 20%, rgba(255,255,255,0.15) 25%, transparent 30%); background-size: 200% 200%; animation: shimmer 3s infinite linear;"></div>

        <div class="absolute -right-12 -top-12 w-48 h-48 rounded-full blur-3xl opacity-10 pointer-events-none transition-colors duration-1000 <?= $is_agency ? 'bg-theme-secondary' : 'bg-theme-primary' ?>"></div>
        <div class="absolute -left-12 -bottom-12 w-40 h-40 rounded-full blur-3xl opacity-5 pointer-events-none bg-theme-text"></div>

        <div class="relative z-20 p-6 sm:p-8">
          <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-8">
            <div class="flex items-center gap-4">
              <div class="flex items-center justify-center w-12 h-12 rounded-full shadow-md <?= $is_agency ? 'bg-theme-secondary text-theme-on-secondary' : 'bg-theme-primary text-theme-on-primary' ?> transform group-hover:scale-105 transition-transform duration-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-shield-check"></use>
                </svg>
              </div>
              <div>
                <h4 class="font-bold text-theme-text text-xl tracking-wide uppercase flex items-center gap-2">
                  GrindSite <?= $is_agency ? 'Agency' : 'Pro' ?>
                </h4>
                <p class="text-[10px] text-theme-text opacity-50 font-mono tracking-widest uppercase mt-0.5">Commercial License</p>
              </div>
            </div>

            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-theme-success/10 border border-theme-success/20 rounded-theme font-bold text-theme-success text-xs backdrop-blur-sm">
              <span class="relative flex w-2 h-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-theme-success opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-theme-success"></span>
              </span>
              <?= _t('lic_status_active') ?>
            </div>
          </div>

          <!-- Render license key toggle -->
          <div x-data="{ open: false }">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-theme-bg/50 p-4 border border-theme-border/50 rounded-theme">
              <div class="font-mono text-theme-text opacity-70 tracking-[0.2em] text-sm sm:text-base flex-1">
                •••• •••• •••• <?= substr(get_option('license_key'), -4) ?: 'XXXX' ?>
              </div>
              <button type="button" @click="open = !open"
                class="flex items-center justify-center gap-2 px-5 py-2 rounded-theme bg-theme-surface border border-theme-border shadow-sm text-theme-text text-xs font-bold hover:bg-theme-bg hover:text-theme-primary transition-all focus:outline-none shrink-0">
                <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-pencil-square"></use>
                </svg>
                <span x-text="open ? '<?= _t('lic_collapse') ?>' : '<?= _t('lic_edit_key') ?>'"></span>
              </button>
            </div>

            <!-- Render license key edit form -->
            <div x-show="open"
              x-transition:enter="transition ease-out duration-250"
              x-transition:enter-start="opacity-0 scale-y-95 -translate-y-1"
              x-transition:enter-end="opacity-100 scale-y-100 translate-y-0"
              x-transition:leave="transition ease-in duration-150"
              x-transition:leave-start="opacity-100 scale-y-100 translate-y-0"
              x-transition:leave-end="opacity-0 scale-y-95 -translate-y-1"
              class="mt-4" style="display: none;">
              <div class="p-5 bg-theme-surface border border-theme-border rounded-theme shadow-inner">
                <label class="block mb-2 text-xs font-bold text-theme-text opacity-70 uppercase tracking-widest"><?= _t('lic_title') ?></label>
                <div class="relative" x-data="{ showKey: false }">
                  <input :type="showKey ? 'text' : 'password'" name="license_key" value="<?= h(get_option('license_key')) ?>"
                    class="font-mono text-sm tracking-widest form-control pr-12 bg-theme-bg focus:bg-theme-surface transition-colors"
                    placeholder="<?= _t('lic_ph') ?>" autocomplete="off">
                  <button type="button" @click="showKey = !showKey" class="absolute right-0 inset-y-0 px-4 flex items-center text-theme-text opacity-40 hover:opacity-100 hover:text-theme-primary focus:outline-none transition-colors">
                    <svg x-show="!showKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
                    </svg>
                    <svg x-show="showKey" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye-slash"></use>
                    </svg>
                  </button>
                </div>
                <p class="mt-3 opacity-60 text-theme-text text-[11px] flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5 text-theme-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check-circle"></use>
                  </svg>
                  <?= _t('lic_success_msg') ?>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

    <?php else: ?>
      <!-- Render trial license card -->
      <div class="relative mb-6 overflow-hidden rounded-theme border border-theme-info/30 bg-theme-info/5 transition-all hover:bg-theme-info/10 shadow-sm">
        <div class="relative p-6 sm:p-8">
          <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-3">
              <div class="flex items-center justify-center w-12 h-12 rounded-full bg-theme-info/20 text-theme-info shadow-inner">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-key"></use>
                </svg>
              </div>
              <div>
                <h4 class="font-bold text-theme-info text-xl tracking-wide uppercase flex items-center gap-2">
                  GrindSite Free
                </h4>
                <p class="text-[10px] text-theme-info opacity-70 font-mono tracking-widest uppercase mt-0.5">Evaluation License</p>
              </div>
            </div>

            <span class="inline-flex items-center px-4 py-1.5 bg-theme-info/10 border border-theme-info/30 rounded-theme font-bold text-theme-info text-xs">
              <?= _t('lic_status_trial') ?>
            </span>
          </div>

          <div class="mb-5 p-5 bg-theme-surface/50 backdrop-blur-sm border border-theme-info/20 rounded-theme">
            <label class="block mb-2 text-xs font-bold text-theme-info opacity-80 uppercase tracking-widest flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-lock-closed"></use>
              </svg>
              <?= _t('lic_title') ?>
            </label>
            <div class="relative" x-data="{ showKey: false }">
              <input :type="showKey ? 'text' : 'password'" name="license_key" value="<?= h(get_option('license_key')) ?>"
                class="font-mono text-sm tracking-wider form-control pr-12 border-theme-info/40 focus:border-theme-info focus:ring-theme-info/20 bg-theme-surface"
                placeholder="<?= _t('lic_ph') ?>" autocomplete="off">
              <button type="button" @click="showKey = !showKey" class="absolute right-0 inset-y-0 px-4 flex items-center text-theme-text opacity-40 hover:opacity-100 hover:text-theme-info focus:outline-none transition-colors">
                <svg x-show="!showKey" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
                </svg>
                <svg x-show="showKey" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye-slash"></use>
                </svg>
              </button>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 pt-5 border-t border-theme-info/20">
            <div class="text-theme-info text-sm leading-relaxed flex-1">
              <p class="font-bold flex items-center gap-1">
                <svg class="w-4 h-4 mb-0.5 text-theme-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-information-circle"></use>
                </svg>
                <?= _t('lic_warn_title') ?>
              </p>
              <p class="opacity-80 mt-1 text-xs"><?= _t('lic_warn_desc') ?></p>
            </div>
            <?php $pricingUrl = (function_exists('grinds_detect_language') && grinds_detect_language() === 'ja') ? 'https://grindsite.com/ja/#pricing' : 'https://grindsite.com/#pricing'; ?>
            <a href="<?= $pricingUrl ?>" target="_blank" class="inline-flex items-center justify-center whitespace-nowrap px-6 py-3 bg-theme-info/10 hover:bg-theme-info/20 border border-theme-info/30 text-theme-info rounded-theme font-bold text-sm transition-all shrink-0">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-top-right-on-square"></use>
              </svg>
              <?= _t('lic_buy_link') ?>
            </a>
          </div>
        </div>
      </div>
    <?php endif; ?>


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
      <button type="submit" :disabled="isSubmitting" class="flex justify-center items-center gap-2 shadow-theme px-6 py-2.5 rounded-theme w-full sm:w-auto font-bold text-sm transition-all disabled:opacity-70 disabled:cursor-not-allowed btn-primary">
        <svg x-show="isSubmitting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
        </svg>
        <span x-text="isSubmitting ? '...' : '<?= _t('btn_save_settings') ?>'"><?= _t('btn_save_settings') ?></span>
      </button>
    </div>
  </form>
</div>
