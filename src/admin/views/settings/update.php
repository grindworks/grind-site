<?php

/**
 * update.php
 * Renders the update interface.
 */
if (!defined('GRINDS_APP'))
  exit;

// Check for updates if on update tab
if (($init_tab ?? '') === 'update') {
  require_once __DIR__ . '/../../../lib/updater.php';
  $updater = new GrindsUpdater($pdo);
  $status = $updater->check();
} else {
  $status = null;
}
?>

<div class="space-y-6 bg-theme-surface shadow-theme p-4 sm:p-6 border border-theme-border rounded-theme">

  <div class="mb-6 sm:mb-8">
    <h3 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-xl">
      <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
      </svg>
      <?= _t('st_update_title') ?>
    </h3>
    <p class="opacity-60 ml-0 sm:ml-7 text-theme-text text-sm leading-relaxed">
      <?= _t('st_update_desc') ?>
    </p>
  </div>

  <?php if ($status === null): ?>
    <div class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
      <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
        </svg>
      </div>
      <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('st_update_check_needed') ?></h3>
      <div class="mt-6">
        <a href="settings.php?tab=update"
          class="inline-block shadow-theme px-6 py-2.5 rounded-theme font-bold text-sm transition-all btn-primary no-underline">
          <?= _t('st_check_updates') ?>
        </a>
      </div>
    </div>
  <?php
  else: ?>

    <div
      class="flex sm:flex-row flex-col justify-between sm:items-center gap-4 bg-theme-bg mb-8 p-4 border border-theme-border rounded-theme">
      <div>
        <p class="opacity-60 mb-1 font-bold text-theme-text text-xs uppercase tracking-wider">
          <?= _t('st_current_ver') ?>
        </p>
        <p class="font-mono font-bold text-theme-text text-2xl">v
          <?= h($status['current']) ?>
        </p>
      </div>

      <?php if ($status['has_update']): ?>
        <div class="text-left sm:text-right">
          <p class="mb-1 font-bold text-theme-primary text-xs uppercase tracking-wider">
            <?= _t('st_latest_ver') ?>
          </p>
          <p class="font-mono font-bold text-theme-primary text-2xl">v
            <?= h($status['remote']['version']) ?>
          </p>
        </div>
      <?php
      else: ?>
        <div class="flex items-center font-bold text-theme-success">
          <svg class="mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check"></use>
          </svg>
          <?= _t('st_up_to_date') ?>
        </div>
      <?php
      endif; ?>
    </div>

    <?php if ($status['has_update']): ?>
      <div class="bg-theme-info/10 mb-6 p-4 border-theme-info border-l-4 rounded-r-theme">
        <h4 class="mb-2 font-bold text-theme-info">
          <?= _t('st_update_available') ?>
        </h4>
        <p class="opacity-80 mb-2 text-theme-text text-sm leading-relaxed">
          <?= h($status['remote']['message']) ?>
        </p>
        <p class="opacity-60 text-theme-text text-xs">
          <?= _t('st_release_date') ?>:
          <?= h($status['remote']['release_date']) ?>
        </p>
      </div>

      <div class="bg-theme-warning/10 mb-6 p-4 border border-theme-warning/30 rounded-theme text-theme-warning text-sm">
        <p class="flex items-center mb-2 font-bold">
          <svg class="mr-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
          </svg>
          <?= _t('attention') ?>
        </p>
        <ul class="space-y-1 opacity-90 list-disc list-inside">
          <li>
            <?= _t('st_update_backup_msg') ?>
          </li>
          <li>
            <?= _t('st_update_overwrite_msg') ?>
          </li>
        </ul>
      </div>

      <div class="flex sm:flex-row flex-col justify-end gap-4">
        <button type="button" @click="activeTab = 'backup'"
          class="shadow-theme px-6 py-2.5 rounded-theme w-full sm:w-auto text-sm btn-secondary">
          <?= _t('st_goto_backup') ?>
        </button>

        <form method="post" class="w-full sm:w-auto">
          <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
          <input type="hidden" name="action" value="perform_update">
          <button type="submit"
            onclick="return confirm(<?= htmlspecialchars(json_encode(_t('st_confirm_update')), ENT_QUOTES) ?>);"
            class="shadow-theme px-6 py-2.5 rounded-theme w-full sm:w-auto font-bold text-sm animate-pulse transition-all btn-primary">
            <?= _t('st_btn_update_now') ?>
          </button>
        </form>
      </div>

    <?php
    else: ?>
      <div class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
        <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check-circle"></use>
          </svg>
        </div>
        <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('st_no_update') ?></h3>
        <div class="mt-2">
          <a href="settings.php?tab=update" class="font-bold text-theme-primary text-xs hover:underline cursor-pointer">
            <?= _t('st_check_again') ?>
          </a>
        </div>
      </div>
    <?php
    endif; ?>
  <?php
  endif; ?>
</div>
