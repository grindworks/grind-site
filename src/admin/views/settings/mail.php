<?php

/**
 * mail.php
 * Renders the interface for configuring SMTP settings.
 */
if (!defined('GRINDS_APP')) exit; ?>
<div class="space-y-6 bg-theme-surface shadow-theme p-4 sm:p-6 border border-theme-border rounded-theme">
  <form method="post" x-data="{ isSubmitting: false }" @submit="setTimeout(() => isSubmitting = true, 10)">
    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
    <input type="hidden" name="action" value="update_mail">

    <div class="mb-6 sm:mb-8">
      <h3 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-xl">
        <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-envelope"></use>
        </svg>
        <?= _t('st_smtp_title') ?>
      </h3>
      <p class="opacity-60 ml-0 sm:ml-7 text-theme-text text-sm leading-relaxed"><?= _t('st_mail_desc') ?></p>
    </div>

    <div class="gap-6 grid grid-cols-1 md:grid-cols-2 mb-6">
      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_smtp_from') ?></span>
        <input type="text" name="smtp_from" value="<?= h($opt['smtp_from']) ?>" class="form-control" placeholder="noreply@example.com">
        <p class="opacity-60 mt-1 text-theme-text text-xs"><?= _t('st_smtp_from_h') ?></p>
      </label>
      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_smtp_admin') ?></span>
        <input type="text" name="smtp_admin_email" value="<?= h($opt['smtp_admin']) ?>" class="form-control" placeholder="admin@example.com">
        <p class="opacity-60 mt-1 text-theme-text text-xs"><?= _t('st_smtp_admin_h') ?></p>
      </label>
    </div>

    <div class="gap-6 grid grid-cols-1 md:grid-cols-2 mb-6">
      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_smtp_host') ?></span>
        <input type="text" name="smtp_host" value="<?= h($opt['smtp_host']) ?>" class="form-control" placeholder="smtp.gmail.com">
        <p class="opacity-60 mt-1 text-theme-text text-xs"><?= _t('st_smtp_host_h') ?></p>
      </label>
      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_smtp_user') ?></span>
        <input type="text" name="smtp_user" value="<?= h($opt['smtp_user']) ?>" class="form-control">
        <p class="opacity-60 mt-1 text-theme-text text-xs"><?= _t('st_smtp_user_h') ?></p>
      </label>
    </div>

    <div class="block mb-6" x-data="{ show: false }">
      <label for="smtp_pass" class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_smtp_pass') ?></label>
      <div class="relative">
        <input :type="show ? 'text' : 'password'" name="smtp_pass" id="smtp_pass" value="" class="font-mono form-control pr-10" placeholder="<?= !empty($opt['smtp_pass']) ? '********' : '' ?>" autocomplete="new-password">
        <button type="button" @click="show = !show" class="right-0 absolute inset-y-0 flex items-center opacity-50 hover:opacity-100 px-3 focus:outline-none text-theme-text" tabindex="-1">
          <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
          </svg>
          <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye-slash"></use>
          </svg>
        </button>
      </div>
      <p class="opacity-60 mt-1 text-theme-text text-xs"><?= _t('st_smtp_pass_h') ?></p>
    </div>

    <div class="gap-6 grid grid-cols-1 md:grid-cols-2 mb-8">
      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_smtp_port') ?></span>
        <input type="number" name="smtp_port" value="<?= h($opt['smtp_port']) ?>" class="form-control" placeholder="587">
        <p class="opacity-60 mt-1 text-theme-text text-xs"><?= _t('st_smtp_port_h') ?></p>
      </label>
      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_smtp_enc') ?></span>
        <select name="smtp_encryption" class="form-control">
          <option value="tls" <?= $opt['smtp_enc'] === 'tls' ? 'selected' : '' ?>>TLS</option>
          <option value="ssl" <?= $opt['smtp_enc'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
          <option value="none" <?= $opt['smtp_enc'] === 'none' ? 'selected' : '' ?>>None</option>
        </select>
      </label>
    </div>

    <div class="flex sm:flex-row flex-col justify-between items-center gap-4 pt-6 border-theme-border border-t">
      <button type="submit" name="send_test_mail" value="1" :disabled="isSubmitting" class="shadow-theme px-4 py-2 rounded-theme w-full sm:w-auto text-xs font-bold transition-all disabled:opacity-70 disabled:cursor-not-allowed btn-secondary">
        <?= _t('send_test') ?>
      </button>

      <button type="submit" :disabled="isSubmitting" class="flex justify-center items-center gap-2 shadow-theme px-6 py-2.5 rounded-theme w-full sm:w-auto font-bold text-sm transition-all disabled:opacity-70 disabled:cursor-not-allowed btn-primary">
        <svg x-show="isSubmitting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
        </svg>
        <span x-text="isSubmitting ? '...' : '<?= _t('btn_save_settings') ?>'"><?= _t('btn_save_settings') ?></span>
      </button>
    </div>
  </form>
</div>
