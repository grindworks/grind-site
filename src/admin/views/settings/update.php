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

    <?php if ($status['has_update']): ?>
      <div class="bg-theme-bg/30 mb-8 p-6 sm:p-8 border border-theme-primary/30 rounded-theme text-center">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-theme-primary/10 text-theme-primary text-xs font-bold mb-6 border border-theme-primary/20">
          <span class="relative flex w-2 h-2">
            <span class="absolute inline-flex w-full h-full bg-theme-primary rounded-full opacity-75 animate-ping"></span>
            <span class="relative inline-flex w-2 h-2 bg-theme-primary rounded-full"></span>
          </span>
          <?= _t('st_update_available') ?>
        </div>

        <div class="flex items-center justify-center gap-6 sm:gap-12 mb-2">
          <div class="text-right flex-1">
            <p class="opacity-60 mb-1 font-bold text-theme-text text-xs uppercase tracking-wider">
              <?= _t('st_current_ver') ?>
            </p>
            <p class="font-mono font-bold text-theme-text text-2xl">v<?= h($status['current']) ?></p>
          </div>

          <div class="text-theme-text opacity-30 shrink-0">
            <svg class="w-6 h-6 sm:w-8 sm:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-right"></use>
            </svg>
          </div>

          <div class="text-left flex-1">
            <p class="mb-1 font-bold text-theme-primary text-xs uppercase tracking-wider">
              <?= _t('st_latest_ver') ?>
            </p>
            <p class="font-mono font-bold text-theme-primary text-2xl">v<?= h($status['remote']['version']) ?></p>
          </div>
        </div>
      </div>

      <div class="bg-theme-bg/50 p-5 sm:p-6 border border-theme-border rounded-theme mb-6">
        <h4 class="flex items-center gap-2 mb-3 font-bold text-theme-text text-sm">
          <svg class="w-4 h-4 text-theme-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-information-circle"></use>
          </svg>
          Release Notes
        </h4>
        <div class="prose prose-sm dark:prose-invert max-w-none mb-4">
          <p class="text-theme-text opacity-90 leading-relaxed whitespace-pre-wrap"><?= h($status['remote']['message']) ?></p>
        </div>
        <div class="flex items-center gap-2 pt-4 border-t border-theme-border opacity-60 text-theme-text text-xs">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-calendar"></use>
          </svg>
          <?= _t('st_release_date') ?>: <?= h($status['remote']['release_date']) ?>
        </div>
      </div>

      <div class="bg-theme-warning/10 mb-8 p-4 sm:p-5 border border-theme-warning/30 rounded-theme text-theme-warning text-sm">
        <p class="flex items-center gap-2 mb-3 font-bold">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
          </svg>
          <?= _t('attention') ?>
        </p>
        <ul class="space-y-1.5 opacity-90 list-disc list-inside ml-1 text-xs sm:text-sm">
          <li><?= _t('st_update_backup_msg') ?></li>
          <li><?= _t('st_update_overwrite_msg') ?></li>
        </ul>
      </div>

      <div class="flex flex-col sm:flex-row justify-end gap-4 items-center pt-6 border-t border-theme-border">
        <button type="button" @click="activeTab = 'backup'"
          class="shadow-theme px-6 py-2.5 rounded-theme w-full sm:w-auto text-sm btn-secondary flex items-center justify-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-server"></use>
          </svg>
          <?= _t('st_goto_backup') ?>
        </button>

        <form method="post" class="w-full sm:w-auto">
          <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
          <input type="hidden" name="action" value="perform_update">
          <button type="submit"
            onclick="return confirm(<?= htmlspecialchars(json_encode(_t('st_confirm_update')), ENT_QUOTES) ?>);"
            class="shadow-theme px-8 py-2.5 rounded-theme w-full sm:w-auto font-bold text-sm transition-all btn-primary flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-down-tray"></use>
            </svg>
            <?= _t('st_btn_update_now') ?>
          </button>
        </form>
      </div>

    <?php
    else: ?>
      <div class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 border border-theme-border rounded-theme text-center">
        <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-success/10 text-theme-success">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check-circle"></use>
          </svg>
        </div>

        <h3 class="mb-3 font-bold text-theme-success text-xl"><?= _t('st_up_to_date') ?></h3>

        <div class="flex items-center gap-2 mt-2 px-4 py-2 bg-theme-surface border border-theme-border rounded-full shadow-sm text-sm">
          <span class="opacity-60 text-theme-text font-bold"><?= _t('st_current_ver') ?></span>
          <span class="font-mono font-bold text-theme-text">v<?= h($status['current']) ?></span>
        </div>

        <div class="mt-8">
          <a href="settings.php?tab=update" class="inline-flex items-center gap-2 px-5 py-2 font-bold text-theme-text opacity-70 hover:opacity-100 bg-theme-surface border border-theme-border hover:border-theme-primary rounded-theme text-xs transition-colors shadow-sm no-underline hover:text-theme-primary">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
            </svg>
            <?= _t('st_check_again') ?>
          </a>
        </div>
      </div>
    <?php
    endif; ?>
  <?php
  endif; ?>
</div>
