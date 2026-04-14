<?php

/**
 * backup.php
 * Renders the interface for managing backups.
 */
if (!defined('GRINDS_APP'))
  exit;

// Load backup list
if (!isset($backups)) {
  $backups = [];
  $backupDir = ROOT_PATH . '/data/backups';
  if (is_dir($backupDir)) {
    clearstatcache();
    foreach (glob($backupDir . '/*.db') as $file) {
      $name = basename($file);
      $size = filesize($file);
      $sizeStr = ($size >= 1048576) ? round($size / 1048576, 2) . ' MB' : round($size / 1024, 2) . ' KB';

      $isAuto = str_starts_with($name, 'auto_login_');
      $note = '';

      if (!$isAuto && preg_match('/^grinds_backup_\d{8}_\d{6}_(.+)\.db$/', $name, $matches)) {
        $note = $matches[1];
      }

      $backups[] = [
        'name' => $name,
        'size' => $sizeStr,
        'date' => date('Y-m-d H:i:s', filemtime($file)),
        'is_auto' => $isAuto,
        'note' => $note
      ];
    }
    usort($backups, function ($a, $b) {
      return strtotime($b['date']) <=> strtotime($a['date']);
    });
  }
}
?>
<div class="space-y-6 bg-theme-surface shadow-theme p-4 sm:p-6 border border-theme-border rounded-theme">

  <div class="mb-6 sm:mb-8">
    <h3 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-xl">
      <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-server"></use>
      </svg>
      <?= _t('st_backup_title') ?>
    </h3>
    <p class="opacity-60 ml-0 sm:ml-7 text-theme-text text-sm leading-relaxed">
      <?= _t('st_backup_desc') ?>
    </p>
  </div>

  <?php
  $migConfig = [
    'csrfToken' => generate_csrf_token(),
    'trans' => [
      'init' => _t('st_backup_init'),
      'archiving' => _t('st_backup_archiving'),
      'finalizing' => _t('st_backup_finalizing'),
      'complete' => _t('st_backup_complete')
    ]
  ];
  ?>
  <div class="bg-theme-primary/5 shadow-theme flex flex-col border border-theme-primary/20 rounded-theme overflow-hidden"
    x-data="{
      ...migrationExporter(<?= htmlspecialchars(json_encode($migConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>),
      isMarkdown: false,
      async startMarkdown() {
          this.processing = true;
          this.isMarkdown = true;
          this.progress = 0;
          this.statusMsg = <?= htmlspecialchars(json_encode(_t('ssg_btn_generating') ?: 'Generating...'), ENT_QUOTES, 'UTF-8') ?>;

          let interval = setInterval(() => {
              if (this.progress < 90) this.progress += 10;
          }, 500);

          try {
              const formData = new FormData();
              formData.append('csrf_token', window.grindsCsrfToken || document.querySelector('input[name=\'csrf_token\']').value);

              const response = await fetch('api/export_markdown.php', {
                  method: 'POST',
                  body: formData
              });

              clearInterval(interval);

              if (!response.ok) {
                  throw new Error('Export failed: ' + response.statusText);
              }

              this.progress = 100;
              this.statusMsg = <?= htmlspecialchars(json_encode(_t('js_done') ?: 'Done!'), ENT_QUOTES, 'UTF-8') ?>;

              const blob = await response.blob();
              const url = window.URL.createObjectURL(blob);
              const a = document.createElement('a');
              a.href = url;

              const disposition = response.headers.get('Content-Disposition');
              let filename = 'markdown_export.zip';
              if (disposition && disposition.indexOf('attachment') !== -1) {
                  const matches = /filename[^;=\n]*=((['\x22]).*?\2|[^;\n]*)/.exec(disposition);
                  if (matches != null && matches[1]) {
                      filename = matches[1].replace(/['\x22]/g, '');
                  }
              }

              a.download = filename;
              document.body.appendChild(a);
              a.click();
              window.URL.revokeObjectURL(url);
              document.body.removeChild(a);

              setTimeout(() => {
                  this.processing = false;
              }, 2000);

          } catch (e) {
              clearInterval(interval);
              this.processing = false;
              if (typeof window.ToastManager !== 'undefined') {
                  window.ToastManager.show({ type: 'error', message: e.message });
              } else {
                  alert(e.message);
              }
          }
      }
    }">
    <div class="flex flex-col divide-y divide-theme-primary/10">
      <!-- Full Backup Section -->
      <div class="flex md:flex-row flex-col justify-between items-start gap-6 p-6">
        <div class="flex-1">
          <h4 class="flex items-center gap-2 mb-2 font-bold text-theme-primary text-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-archive-box"></use>
            </svg>
            <?= _t('st_full_backup_title') ?>
          </h4>
          <p class="opacity-80 ml-0 sm:ml-8 text-theme-text text-sm leading-relaxed">
            <?= _t('st_full_backup_desc') ?>
          </p>

          <div x-show="processing && !isMarkdown" class="mt-4 ml-0 sm:ml-8" x-cloak>
            <div class="flex justify-between mb-1 font-bold text-theme-primary text-xs">
              <span x-text="statusMsg"></span>
              <span x-text="progress + '%'"></span>
            </div>
            <div class="bg-theme-bg border border-theme-primary/20 rounded-full w-full h-2 overflow-hidden">
              <div class="relative bg-theme-primary rounded-full h-2 overflow-hidden transition-all duration-300"
                :style="'width: ' + progress + '%'">
                <div class="absolute inset-0 bg-white/20 animate-[shimmer_2s_infinite]"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="w-full md:w-auto shrink-0 mt-2 md:mt-0">
          <button type="button" @click="isMarkdown = false; startExport()" :disabled="processing"
            class="relative flex justify-center items-center shadow-theme px-6 py-2.5 rounded-theme w-full sm:w-auto font-bold transition-all disabled:opacity-70 disabled:cursor-not-allowed btn-primary overflow-hidden">
            <div class="flex items-center gap-2 transition-opacity duration-200" :class="processing && !isMarkdown ? 'opacity-0' : 'opacity-100'">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-down-tray"></use>
              </svg>
              <span><?= h(_t('btn_download_full')) ?></span>
            </div>
            <div class="absolute inset-0 flex items-center justify-center gap-2 transition-opacity duration-200" :class="processing && !isMarkdown ? 'opacity-100' : 'opacity-0 pointer-events-none'">
              <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
              </svg>
              <span><?= h(_t('ssg_btn_generating')) ?></span>
            </div>
          </button>
        </div>
      </div>

      <!-- Markdown Export Section -->
      <div class="flex md:flex-row flex-col justify-between items-start gap-6 p-6 bg-theme-surface/50">
        <div class="flex-1">
          <h4 class="flex items-center gap-2 mb-2 font-bold text-theme-primary text-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
            </svg>
            <?= function_exists('_t') ? (_t('btn_export_markdown') ?: 'Export as Markdown') : 'Export as Markdown' ?>
          </h4>
          <p class="opacity-80 ml-0 sm:ml-8 text-theme-text text-sm leading-relaxed">
            <?= function_exists('_t') ? _t('st_markdown_export_desc') : '* For migrating to other CMS. Cannot be used to restore data.' ?>
          </p>

          <div x-show="processing && isMarkdown" class="mt-4 ml-0 sm:ml-8" x-cloak>
            <div class="flex justify-between mb-1 font-bold text-theme-primary text-xs">
              <span x-text="statusMsg"></span>
              <span x-text="progress + '%'"></span>
            </div>
            <div class="bg-theme-bg border border-theme-primary/20 rounded-full w-full h-2 overflow-hidden">
              <div class="relative bg-theme-primary rounded-full h-2 overflow-hidden transition-all duration-300"
                :style="'width: ' + progress + '%'">
                <div class="absolute inset-0 bg-white/20 animate-[shimmer_2s_infinite]"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="w-full md:w-auto shrink-0 mt-2 md:mt-0">
          <form method="post" action="api/export_markdown.php" target="_blank" class="w-full sm:w-auto" @submit.prevent="isMarkdown = true; startMarkdown()">
            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
            <button type="submit" :disabled="processing" class="relative flex justify-center items-center shadow-theme px-6 py-2.5 rounded-theme w-full sm:w-auto font-bold transition-all disabled:opacity-70 disabled:cursor-not-allowed btn-secondary hover:text-theme-primary hover:border-theme-primary overflow-hidden">
              <div class="flex items-center gap-2 transition-opacity duration-200" :class="processing && isMarkdown ? 'opacity-0' : 'opacity-100'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-down-tray"></use>
                </svg>
                <span><?= function_exists('_t') ? (_t('btn_download') ?: 'Download') : 'Download' ?></span>
              </div>
              <div class="absolute inset-0 flex items-center justify-center gap-2 transition-opacity duration-200" :class="processing && isMarkdown ? 'opacity-100' : 'opacity-0 pointer-events-none'">
                <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                </svg>
                <span><?= h(_t('ssg_btn_generating')) ?></span>
              </div>
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- ZIP Encryption Settings -->
    <div class="bg-theme-primary/10 px-6 py-4 border-t border-theme-primary/20">
      <form method="post" action="settings.php?tab=backup" class="flex flex-col md:flex-row md:items-start gap-4 warn-on-unsaved">
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
        <input type="hidden" name="action" value="update_backup_settings">

        <div class="flex-1 w-full" x-data="{ showZipPass: false }">
          <label class="block">
            <span class="flex items-center gap-2 opacity-80 mb-2 font-bold text-theme-primary text-xs">
              <?= function_exists('_t') ? _t('st_backup_zip_password') : 'ZIP Encryption Password (AES-256)' ?>
              <?php if (!empty($opt['backup_zip_password'])): ?>
                <span class="bg-theme-success/10 text-theme-success px-2 py-0.5 border border-theme-success/20 rounded-full font-bold text-[10px] flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-shield-check"></use>
                  </svg>
                  <?= function_exists('_t') ? _t('lbl_active') : 'Active' ?>
                </span>
              <?php endif; ?>
            </span>
            <div class="relative w-full md:w-1/2">
              <input :type="showZipPass ? 'text' : 'password'" name="backup_zip_password" value="<?= h($opt['backup_zip_password'] ?? '') ?>" class="w-full font-mono text-sm pr-10 form-control border-theme-primary/30 focus:border-theme-primary bg-theme-surface" autocomplete="new-password" placeholder="<?= function_exists('_t') ? _t('ph_backup_zip_password') : 'Leave empty for no encryption' ?>">
              <button type="button" @click="showZipPass = !showZipPass" class="absolute right-0 inset-y-0 px-3 flex items-center text-theme-primary opacity-50 hover:opacity-100 focus:outline-none">
                <svg x-show="!showZipPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
                </svg>
                <svg x-show="showZipPass" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye-slash"></use>
                </svg>
              </button>
            </div>
            <p class="opacity-70 mt-1.5 text-[10px] text-theme-primary leading-tight font-bold">
              <?= function_exists('_t') ? _t('help_backup_zip_password') : '⚠️ If set, Full Backup (Migration) ZIP files will be encrypted. Do not lose this password!' ?>
            </p>
          </label>
        </div>
        <button type="submit" class="mt-0 md:mt-6 shadow-theme px-6 py-2 h-[42px] rounded-theme font-bold text-xs transition-all btn-primary whitespace-nowrap">
          <?= function_exists('_t') ? _t('save') : 'Save' ?>
        </button>
      </form>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Database Auto Backup Settings -->
    <div class="bg-theme-bg/30 p-5 border border-theme-border rounded-theme flex flex-col">
      <div class="mb-6">
        <h4 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-lg">
          <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-adjustments-horizontal"></use>
          </svg>
          <?= _t('st_backup_settings_title') ?>
        </h4>
        <p class="opacity-60 ml-0 sm:ml-7 text-theme-text text-sm leading-relaxed">
          <?= _t('st_backup_settings_desc') ?>
        </p>
      </div>

      <form method="post" action="settings.php?tab=backup" class="warn-on-unsaved flex flex-col h-full" x-data="{
        currentLimit: <?= (int)$opt['bk_limit'] ?>,
        checkLimit(e) {
          const newLimit = parseInt(this.$el.querySelector('[name=backup_retention_limit]').value);
          if (newLimit < this.currentLimit) {
            if (!confirm(<?= htmlspecialchars(json_encode(_t('confirm_reduce_backup_limit')), ENT_QUOTES) ?>)) e.preventDefault();
          }
        }
      }" @submit="checkLimit($event)">
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
        <input type="hidden" name="action" value="update_backup_settings">

        <div class="items-start gap-5 grid grid-cols-1 flex-1 mb-6">
          <label class="block">
            <span class="block opacity-70 mb-2 font-bold text-theme-text text-xs">
              <?= _t('st_login_backup_freq') ?>
            </span>
            <select name="login_backup_frequency" class="w-full text-sm cursor-pointer form-control">
              <option value="10" <?= ($opt['login_bk_freq'] ?? 10) == 10 ? 'selected' : '' ?>>
                <?= _t('opt_1_in_10_rec') ?> (1/10)
              </option>
              <option value="5" <?= ($opt['login_bk_freq'] ?? 10) == 5 ? 'selected' : '' ?>>
                <?= _t('opt_1_in_5') ?> (1/5)
              </option>
              <option value="1" <?= ($opt['login_bk_freq'] ?? 10) == 1 ? 'selected' : '' ?>>
                <?= _t('opt_every_time') ?> (1/1)
              </option>
              <option value="0" <?= ($opt['login_bk_freq'] ?? 10) == 0 ? 'selected' : '' ?>>
                <?= _t('opt_disabled') ?>
              </option>
            </select>
            <p class="opacity-50 mt-1 text-[10px] text-theme-text leading-tight">
              <?= _t('help_login_backup_freq') ?>
            </p>
          </label>
          <label class="block">
            <span class="block opacity-70 mb-2 font-bold text-theme-text text-xs">
              <?= _t('lbl_auto_delete') ?>
            </span>
            <select name="backup_retention_limit" class="w-full text-sm cursor-pointer form-control">
              <option value="5" <?= $opt['bk_limit'] == 5 ? 'selected' : '' ?>>
                <?= _t('opt_recent_n_files', 5) ?>
              </option>
              <option value="10" <?= $opt['bk_limit'] == 10 ? 'selected' : '' ?>>
                <?= _t('opt_recent_n_files', 10) ?>
              </option>
              <option value="15" <?= $opt['bk_limit'] == 15 ? 'selected' : '' ?>>
                <?= _t('opt_recent_n_files', 15) ?>
              </option>
              <option value="20" <?= $opt['bk_limit'] == 20 ? 'selected' : '' ?>>
                <?= _t('opt_recent_n_files', 20) ?>
              </option>
              <option value="30" <?= $opt['bk_limit'] == 30 ? 'selected' : '' ?>>
                <?= _t('opt_recent_n_files', 30) ?>
              </option>
            </select>
            <p class="opacity-50 mt-1 text-[10px] text-theme-text leading-tight">
              <?= _t('help_backup_retention') ?>
            </p>
          </label>
          <label class="block">
            <span class="block opacity-70 mb-2 font-bold text-theme-text text-xs">
              <?= _t('st_auto_backup_limit') ?: 'Auto Backup Limit' ?>
            </span>
            <select name="auto_backup_limit_mb" class="w-full text-sm cursor-pointer form-control">
              <option value="50" <?= ($opt['auto_backup_limit_mb'] ?? 50) == 50 ? 'selected' : '' ?>>50 MB</option>
              <option value="100" <?= ($opt['auto_backup_limit_mb'] ?? 50) == 100 ? 'selected' : '' ?>>100 MB</option>
              <option value="500" <?= ($opt['auto_backup_limit_mb'] ?? 50) == 500 ? 'selected' : '' ?>>500 MB</option>
              <option value="1000" <?= ($opt['auto_backup_limit_mb'] ?? 50) == 1000 ? 'selected' : '' ?>>1 GB</option>
              <option value="0" <?= ($opt['auto_backup_limit_mb'] ?? 50) == 0 ? 'selected' : '' ?>>
                <?= _t('opt_unlimited') ?: 'Unlimited' ?>
              </option>
            </select>
            <p class="opacity-50 mt-1 text-[10px] text-theme-text leading-tight">
              <?= _t('help_auto_backup_limit') ?: 'Skip auto backup if DB size exceeds this limit.' ?>
            </p>
          </label>
        </div>
        <div class="pt-5 border-t border-theme-border text-right mt-auto">
          <button type="submit" class="shadow-theme px-6 py-2.5 rounded-theme font-bold text-sm transition-all btn-primary w-full sm:w-auto">
            <?= _t('btn_save_settings') ?>
          </button>
        </div>
      </form>
    </div>

    <!-- Database Manual Backup -->
    <div class="bg-theme-bg/30 p-5 border border-theme-border rounded-theme flex flex-col">
      <div class="mb-6">
        <h4 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-lg">
          <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-camera"></use>
          </svg>
          <?= _t('st_manual_backup_title') ?>
        </h4>
        <p class="opacity-60 ml-0 sm:ml-7 text-theme-text text-sm leading-relaxed">
          <?= _t('st_manual_backup_desc') ?>
        </p>
      </div>
      <form method="post" class="flex flex-col gap-5 warn-on-unsaved h-full" action="settings.php?tab=backup">
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
        <input type="hidden" name="action" value="create_backup">

        <div class="flex flex-col flex-1 gap-5">
          <label class="block">
            <span class="block opacity-70 mb-2 font-bold text-theme-text text-xs"><?= _t('lbl_backup_note') ?></span>
            <div class="relative">
              <input type="text" name="backup_note" class="pl-9 w-full text-sm form-control"
                placeholder="<?= h(_t('ph_backup_note')) ?>" pattern="[a-zA-Z0-9\-_]+"
                title="<?= h(_t('help_backup_note')) ?>">
              <div class="top-1/2 left-3 absolute opacity-40 text-theme-text -translate-y-1/2 pointer-events-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-tag"></use>
                </svg>
              </div>
            </div>
          </label>

          <div class="flex items-start gap-3 bg-theme-warning/10 mt-2 p-3 border border-theme-warning/20 rounded-theme">
            <svg class="mt-0.5 w-5 h-5 text-theme-warning shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
            </svg>
            <div class="opacity-80 text-theme-text text-xs leading-relaxed">
              <strong class="block mb-1 font-bold text-theme-warning">
                <?= _t('manual_backup_warn_title') ?>
              </strong>
              <?= _t('manual_backup_warn_desc') ?>
            </div>
          </div>

          <div class="mt-auto pt-5 border-t border-theme-border text-right">
            <button type="submit" class="flex justify-center items-center gap-2 shadow-theme px-6 py-2.5 rounded-theme w-full sm:w-auto text-sm whitespace-nowrap btn-secondary">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
              </svg>
              <span><?= _t('btn_create_backup') ?></span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Config Backup Notice -->
  <div class="flex items-start gap-3 bg-theme-info/10 p-4 border border-theme-info/20 rounded-theme text-theme-info">
    <svg class="mt-0.5 w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-information-circle"></use>
    </svg>
    <div class="text-xs leading-relaxed opacity-90">
      <strong class="block mb-1 font-bold">
        <?= _t('st_backup_important_title') ?>
      </strong>
      <?= _t('st_backup_important_desc') ?>
    </div>
  </div>

  <?php if (empty($backups)): ?>
    <div class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
      <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-server"></use>
        </svg>
      </div>
      <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('msg_no_backup') ?></h3>
    </div>
  <?php
  else: ?>

    <div class="space-y-3">
      <?php foreach ($backups as $bk):
        // Check backup size
        $sizeRaw = (float)filter_var($bk['size'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $sizeUnit = str_contains((string)$bk['size'], 'MB') ? 'MB' : 'KB';
        $isHeavy = ($sizeUnit === 'MB' && $sizeRaw > 10);
      ?>
        <div
          class="group flex md:flex-row flex-col justify-between md:items-center bg-theme-bg/10 hover:bg-theme-surface hover:shadow-theme p-3 border border-theme-border rounded-theme transition-all">

          <div class="flex items-start gap-4 mb-3 md:mb-0">
            <div
              class="p-2 rounded-full shrink-0 <?= $bk['is_auto'] ? 'bg-theme-text/5 text-theme-text/50' : 'bg-theme-primary/10 text-theme-primary' ?>">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-server"></use>
              </svg>
            </div>

            <div>
              <div class="flex flex-wrap items-center gap-2">
                <span class="font-bold text-theme-text text-sm">
                  <?= h($bk['date']) ?>
                </span>
                <?php if ($bk['is_auto']): ?>
                  <span
                    class="bg-theme-text/10 px-1.5 py-0.5 border border-theme-border rounded-theme font-bold text-[10px] text-theme-text/60">
                    <?= _t('lbl_auto_backup') ?>
                  </span>
                <?php
                elseif (!empty($bk['note'])): ?>
                  <span
                    class="bg-theme-primary/10 px-1.5 py-0.5 border border-theme-primary/20 rounded-theme font-bold text-[10px] text-theme-primary">
                    <svg class="inline-block -mt-0.5 mr-1 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-tag"></use>
                    </svg>
                    <?= h($bk['note']) ?>
                  </span>
                <?php
                else: ?>
                  <span
                    class="bg-theme-surface px-1.5 py-0.5 border border-theme-border rounded-theme font-bold text-[10px] text-theme-text/60">
                    <?= _t('lbl_manual_backup') ?>
                  </span>
                <?php
                endif; ?>

                <span class="opacity-40 font-mono text-[10px] text-theme-text break-all">
                  <?= h($bk['name']) ?>
                </span>
              </div>

              <div class="flex items-center gap-4 text-sm">
                <span
                  class="text-xs font-mono <?= $isHeavy ? 'text-theme-warning font-bold' : 'text-theme-text opacity-60' ?>">
                  <?= h($bk['size']) ?>
                </span>
              </div>
            </div>
          </div>

          <div class="flex justify-end items-center gap-2 pt-3 md:pt-0 border-theme-border/50 md:border-0 border-t">
            <form method="post">
              <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
              <input type="hidden" name="action" value="download_backup">
              <input type="hidden" name="download_backup" value="<?= h($bk['name']) ?>">
              <button type="submit" class="group/btn flex items-center gap-1.5 px-3 py-1 h-8 text-[10px] btn-secondary"
                title="<?= h(_t('download')) ?>">
                <svg class="w-3 h-3 text-theme-text group-hover/btn:text-theme-primary transition-colors" fill="none"
                  stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-down-tray"></use>
                </svg>
                <span class="font-bold text-theme-text group-hover/btn:text-theme-primary transition-colors">DL</span>
              </button>
            </form>

            <form method="post"
              onsubmit="return confirm(<?= htmlspecialchars(json_encode(_t('confirm_restore'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>);">
              <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
              <input type="hidden" name="action" value="restore_backup">
              <input type="hidden" name="restore_backup" value="<?= h($bk['name']) ?>">
              <button type="submit"
                class="flex items-center gap-1.5 hover:bg-theme-warning/10 px-3 py-1 border-theme-warning/30 h-8 text-[10px] text-theme-warning btn-secondary"
                title="<?= h(_t('btn_restore')) ?>">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                </svg>
                <span class="font-bold">
                  <?= _t('btn_restore') ?>
                </span>
              </button>
            </form>

            <form method="post"
              onsubmit="return confirm(<?= htmlspecialchars(json_encode(_t('confirm_delete'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>);">
              <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
              <input type="hidden" name="action" value="delete_backup">
              <input type="hidden" name="delete_backup" value="<?= h($bk['name']) ?>">
              <button type="submit"
                class="flex justify-center items-center hover:bg-theme-danger/10 px-3 py-1 border-theme-danger/30 w-8 h-8 text-[10px] text-theme-danger btn-secondary"
                title="<?= h(_t('delete')) ?>">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                </svg>
              </button>
            </form>
          </div>

        </div>
      <?php
      endforeach; ?>
    </div>

    <div class="flex items-start gap-3 bg-theme-warning/5 mt-6 p-4 border border-theme-warning/20 rounded-theme">
      <svg class="mt-0.5 w-5 h-5 text-theme-warning shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
      </svg>
      <p class="text-theme-warning text-xs leading-relaxed">
        <strong class="font-bold">
          <?= _t('attention') ?>:
        </strong>
        <?= _t('st_backup_restore_warn') ?>
      </p>
    </div>
  <?php
  endif; ?>
</div>
