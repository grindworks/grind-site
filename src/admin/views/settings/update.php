<?php

/**
 * update.php
 * Renders the update interface with fully asynchronous step-by-step execution.
 */
if (!defined('GRINDS_APP'))
  exit;

// Check for updates if on update tab
if (($init_tab ?? '') === 'update') {
  require_once ROOT_PATH . '/lib/updater.php';
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
    <div x-data="{ isChecking: false }">
      <div x-show="!isChecking" class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
        <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
          </svg>
        </div>
        <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('st_update_check_needed') ?></h3>

        <div class="flex items-center gap-2 mt-2 px-4 py-2 bg-theme-surface border border-theme-border rounded-full shadow-sm text-sm">
          <span class="opacity-60 text-theme-text font-bold"><?= _t('st_current_ver') ?></span>
          <span class="font-mono font-bold text-theme-text">v<?= h(defined('CMS_VERSION') ? CMS_VERSION : 'Unknown') ?></span>
        </div>

        <div class="mt-6">
          <button @click="isChecking = true; setTimeout(() => window.location.href = 'settings.php?tab=update', 1600)"
            class="inline-block shadow-theme px-6 py-2.5 rounded-theme font-bold text-sm transition-all btn-primary no-underline">
            <?= _t('st_check_updates') ?>
          </button>
        </div>
      </div>

      <div x-show="isChecking" style="display: none;" class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
        <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-primary">
          <svg class="w-8 h-8 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
          </svg>
        </div>
        <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('st_checking_updates') ?></h3>

        <div class="flex items-center gap-2 mt-2 px-4 py-2 opacity-50 text-sm">
          <span class="opacity-60 text-theme-text font-bold"><?= _t('st_current_ver') ?></span>
          <span class="font-mono font-bold text-theme-text">v<?= h(defined('CMS_VERSION') ? CMS_VERSION : 'Unknown') ?></span>
        </div>
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

      <!-- Asynchronous Update Controller -->
      <div class="w-full" x-data="updateController()">
        <div class="mb-6 bg-theme-bg/30 p-4 sm:p-5 border border-theme-border rounded-theme">
          <label class="flex items-start cursor-pointer group" :class="isProcessing ? 'opacity-50 pointer-events-none' : ''">
            <input type="checkbox" x-model="skipThemeSkin" class="mt-0.5 bg-theme-surface border-theme-border rounded focus:ring-theme-primary/20 w-5 h-5 text-theme-primary form-checkbox shrink-0 transition-shadow">
            <div class="ml-3">
              <span class="block text-theme-text text-sm font-bold group-hover:text-theme-primary transition-colors"><?= _t('st_update_skip_theme_skin') ?></span>
              <span class="block mt-1.5 text-theme-text opacity-70 text-xs leading-relaxed"><?= _t('st_update_skip_theme_skin_desc') ?></span>
            </div>
          </label>
        </div>

        <!-- Progress Modal UI (Visible during update) -->
        <div x-show="isProcessing" x-collapse class="mb-6">
          <div class="bg-theme-surface border border-theme-border rounded-theme p-6 shadow-theme">
            <h4 class="font-bold text-theme-primary text-lg mb-4 flex items-center gap-2">
              <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!isComplete && !hasError">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
              </svg>
              <svg class="w-5 h-5 text-theme-success" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="isComplete" style="display:none;">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check-circle"></use>
              </svg>
              <svg class="w-5 h-5 text-theme-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="hasError" style="display:none;">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
              </svg>
              <span x-text="statusTitle"></span>
            </h4>

            <div class="space-y-3">
              <template x-for="(step, index) in steps" :key="step.id">
                <div class="flex items-center justify-between text-sm" :class="step.status === 'pending' ? 'opacity-40' : (step.status === 'active' ? 'text-theme-primary font-bold' : (step.status === 'error' ? 'text-theme-danger font-bold' : 'text-theme-success font-medium'))">
                  <div class="flex items-center gap-2">
                    <span x-show="step.status === 'pending'" class="w-4 h-4 rounded-full border-2 border-theme-text/40"></span>
                    <svg x-show="step.status === 'active'" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                    </svg>
                    <svg x-show="step.status === 'done'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check-circle"></use>
                    </svg>
                    <svg x-show="step.status === 'error'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-circle"></use>
                    </svg>
                    <span x-text="step.label"></span>
                  </div>
                </div>
              </template>
            </div>

            <div x-show="errorMessage" class="mt-4 p-3 bg-theme-danger/10 border border-theme-danger/30 text-theme-danger text-xs rounded-theme" x-text="errorMessage" style="display:none;"></div>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-end gap-4 items-center pt-6 border-t border-theme-border">
          <button type="button" @click="activeTab = 'backup'" :disabled="isProcessing"
            class="shadow-theme px-6 py-2.5 rounded-theme w-full sm:w-auto text-sm btn-secondary flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-server"></use>
            </svg>
            <?= _t('st_goto_backup') ?>
          </button>

          <button type="button" @click="startUpdate()" :disabled="isProcessing || isComplete"
            class="shadow-theme px-8 py-2.5 rounded-theme w-full sm:w-auto font-bold text-sm transition-all btn-primary flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed hover:-translate-y-0.5">
            <svg x-show="!isProcessing && !isComplete" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-down-tray"></use>
            </svg>
            <svg x-show="isProcessing" x-cloak class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
            </svg>
            <svg x-show="isComplete" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check"></use>
            </svg>
            <span x-text="isComplete ? <?= htmlspecialchars(json_encode(_t('js_done') ?? 'Done!'), ENT_QUOTES) ?> : (isProcessing ? <?= htmlspecialchars(json_encode(_t('ssg_btn_generating') ?? 'Updating...'), ENT_QUOTES) ?> : <?= htmlspecialchars(json_encode(_t('st_btn_update_now')), ENT_QUOTES) ?>)"></span>
          </button>
        </div>
      </div>

      <script>
        document.addEventListener('alpine:init', () => {
          Alpine.data('updateController', () => ({
            skipThemeSkin: true,
            isProcessing: false,
            isComplete: false,
            hasError: false,
            statusTitle: 'Preparing update...',
            errorMessage: '',
            sourceDir: '',
            downloadUrl: '',
            expectedHash: '',
            steps: [{
                id: 'init',
                label: 'Initialization & Checks',
                status: 'pending'
              },
              {
                id: 'download',
                label: 'Downloading Package',
                status: 'pending'
              },
              {
                id: 'extract',
                label: 'Extracting Archive',
                status: 'pending'
              },
              {
                id: 'dry_run',
                label: 'Pre-flight Permission Checks',
                status: 'pending'
              },
              {
                id: 'backup',
                label: 'Backing Up Current Core',
                status: 'pending'
              },
              {
                id: 'apply',
                label: 'Applying New Files',
                status: 'pending'
              },
              {
                id: 'cleanup',
                label: 'Cleaning Up',
                status: 'pending'
              }
            ],

            async startUpdate() {
              if (!confirm(<?= htmlspecialchars(json_encode(_t('st_confirm_update')), ENT_QUOTES) ?>)) return;

              this.isProcessing = true;
              this.isComplete = false;
              this.hasError = false;
              this.errorMessage = '';
              this.statusTitle = 'Updating System...';
              this.steps.forEach(s => s.status = 'pending');

              // Block navigation
              window.onbeforeunload = () => "Update in progress. Do not close this window.";

              try {
                // 1. Init
                this.setStepStatus('init', 'active');
                const initRes = await this.callApi('init');
                this.downloadUrl = initRes.url;
                this.expectedHash = initRes.sha256;
                this.setStepStatus('init', 'done');

                // 2. Download
                this.setStepStatus('download', 'active');
                await this.callApi('download', {
                  url: this.downloadUrl,
                  sha256: this.expectedHash
                });
                this.setStepStatus('download', 'done');

                // 3. Extract
                this.setStepStatus('extract', 'active');
                const extractRes = await this.callApi('extract');
                this.sourceDir = extractRes.source_dir;
                this.setStepStatus('extract', 'done');

                // 4. Dry Run (Permissions check)
                this.setStepStatus('dry_run', 'active');
                await this.callApi('dry_run', {
                  source_dir: this.sourceDir,
                  skip_theme_skin: this.skipThemeSkin ? 1 : 0
                });
                this.setStepStatus('dry_run', 'done');

                // 5. Backup current core
                this.setStepStatus('backup', 'active');
                await this.callApi('backup', {
                  source_dir: this.sourceDir,
                  skip_theme_skin: this.skipThemeSkin ? 1 : 0
                });
                this.setStepStatus('backup', 'done');

                // 6. Apply update
                this.setStepStatus('apply', 'active');
                await this.callApi('apply', {
                  source_dir: this.sourceDir,
                  skip_theme_skin: this.skipThemeSkin ? 1 : 0
                });
                this.setStepStatus('apply', 'done');

                // 7. Cleanup
                this.setStepStatus('cleanup', 'active');
                await this.callApi('cleanup');
                this.setStepStatus('cleanup', 'done');

                this.statusTitle = <?= htmlspecialchars(json_encode(_t('msg_update_success') ?? 'Update Complete!'), ENT_QUOTES) ?>.replace('%s', initRes.version);
                this.isComplete = true;

                // Reload after success
                setTimeout(() => {
                  window.onbeforeunload = null;
                  window.location.href = 'settings.php?tab=update';
                }, 2000);

              } catch (e) {
                this.hasError = true;
                this.statusTitle = 'Update Failed';
                this.errorMessage = e.message;
                // Mark active step as error
                const activeStep = this.steps.find(s => s.status === 'active');
                if (activeStep) activeStep.status = 'error';

                // Attempt emergency cleanup
                try {
                  await this.callApi('cleanup');
                } catch (err) {}

                window.onbeforeunload = null;
              }
            },

            setStepStatus(id, status) {
              const step = this.steps.find(s => s.id === id);
              if (step) step.status = status;
            },

            async callApi(step, data = {}) {
              const formData = new FormData();
              formData.append('csrf_token', '<?= h(generate_csrf_token()) ?>');
              formData.append('step', step);
              formData.append('data', JSON.stringify(data));

              const baseUrl = (window.grindsBaseUrl || '').replace(/\/$/, '');
              const res = await fetch(`${baseUrl}/admin/api/update_process.php`, {
                method: 'POST',
                body: formData,
                headers: {
                  'X-Requested-With': 'XMLHttpRequest'
                }
              });

              if (!res.ok) {
                let msg = `Server Error (${res.status})`;
                try {
                  const errData = await res.json();
                  if (errData.error) msg = errData.error;
                } catch (e) {}
                throw new Error(msg);
              }

              const result = await res.json();
              if (!result.success) {
                throw new Error(result.error || 'Unknown error');
              }
              return result;
            }
          }));
        });
      </script>

    <?php
    else: ?>
      <div x-data="{ isChecking: false }">
        <div x-show="!isChecking" class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 border border-theme-border rounded-theme text-center">
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
            <button @click="isChecking = true; setTimeout(() => window.location.href = 'settings.php?tab=update', 1600)" class="inline-flex items-center gap-2 px-5 py-2 font-bold text-theme-text opacity-70 hover:opacity-100 bg-theme-surface border border-theme-border hover:border-theme-primary rounded-theme text-xs transition-colors shadow-sm no-underline hover:text-theme-primary cursor-pointer">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
              </svg>
              <?= _t('st_check_again') ?>
            </button>
          </div>
        </div>

        <div x-show="isChecking" style="display: none;" class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 border border-theme-border rounded-theme text-center">
          <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-primary">
            <svg class="w-8 h-8 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
            </svg>
          </div>

          <h3 class="mb-3 font-bold text-theme-text text-xl"><?= _t('st_checking_updates') ?></h3>

          <div class="flex items-center gap-2 mt-2 px-4 py-2 opacity-0 text-sm">
            <span class="opacity-60 text-theme-text font-bold"><?= _t('st_current_ver') ?></span>
            <span class="font-mono font-bold text-theme-text">v<?= h($status['current']) ?></span>
          </div>

          <div class="mt-8 opacity-0">
            <span class="inline-flex items-center gap-2 px-5 py-2 text-xs">...</span>
          </div>
        </div>
      </div>
    <?php
    endif; ?>
  <?php
  endif; ?>
</div>
