<?php

/**
 * integration.php
 * Renders the interface for managing integrations.
 */
if (!defined('GRINDS_APP')) exit;

$shareButtonsJson = get_option('share_buttons', '[]');
$shareButtons = json_decode($shareButtonsJson, true);

$defaultButtons = function_exists('get_default_share_buttons') ? get_default_share_buttons() : [];

if (!is_array($shareButtons) || empty($shareButtons)) {
  $shareButtons = $defaultButtons;
} else {
  // Merge default buttons
  $existingIds = array_column($shareButtons, 'id');
  foreach ($defaultButtons as $defaultBtn) {
    if (!in_array($defaultBtn['id'], $existingIds)) {
      $shareButtons[] = $defaultBtn;
    }
  }
}
?>

<div class="space-y-6 bg-theme-surface shadow-theme p-4 sm:p-6 border border-theme-border rounded-theme"
  x-data="{
        buttons: <?= htmlspecialchars(json_encode($shareButtons, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>,
        isSubmitting: false,

        moveUp(index) {
            if (index === 0) return;
            const item = this.buttons.splice(index, 1)[0];
            this.buttons.splice(index - 1, 0, item);
        },

        moveDown(index) {
            if (index === this.buttons.length - 1) return;
            const item = this.buttons.splice(index, 1)[0];
            this.buttons.splice(index + 1, 0, item);
        },

        add() {
            this.buttons.push({
                id: 'custom' + Date.now(),
                name: <?= htmlspecialchars(json_encode(_t('st_sns_new')), ENT_QUOTES) ?>,
                url: '',
                icon: 'outline-link',
                color: '#888888',
                enabled: true
            });
        },
        remove(index) {
            if (confirm(<?= htmlspecialchars(json_encode(_t('confirm_delete')), ENT_QUOTES) ?>)) {
                this.buttons.splice(index, 1);
            }
        }
     }">

  <form method="post" class="warn-on-unsaved" @submit="setTimeout(() => isSubmitting = true, 10)">
    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
    <input type="hidden" name="action" value="update_integration">

    <div class="mb-6 sm:mb-8">
      <h3 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-xl">
        <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chart-bar-square"></use>
        </svg>
        <?= _t('tab_integration') ?>
      </h3>
      <p class="opacity-60 ml-0 sm:ml-7 text-theme-text text-sm leading-relaxed"><?= _t('st_integration_desc') ?></p>
    </div>

    <div>
      <h4 class="flex items-center gap-2 mb-6 font-bold text-theme-text text-lg">
        <svg class="w-5 h-5 text-theme-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chart-bar"></use>
        </svg>
        <?= _t('st_ga_title') ?>
      </h4>
      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_ga_id') ?></span>
        <input type="text" name="google_analytics_id" value="<?= h($opt['ga_id']) ?>" class="form-control font-mono" placeholder="G-..." pattern="G-[a-zA-Z0-9]+" title="GA4 ID must start with G- followed by alphanumeric characters">
      </label>
    </div>

    <hr class="border-theme-border my-8">

    <div>
      <h4 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-lg">
        <svg class="w-5 h-5 text-theme-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-user-circle"></use>
        </svg>
        <?= _t('st_official_sns_title') ?>
      </h4>
      <p class="opacity-60 mb-4 text-theme-text text-xs">
        <?= _t('st_official_sns_desc') ?>
      </p>
      <textarea name="official_social_links" rows="3" class="form-control font-mono text-xs" placeholder="https://x.com/your_profile&#10;https://youtube.com/@your_channel"><?= h(get_option('official_social_links')) ?></textarea>
    </div>

    <hr class="border-theme-border my-8">

    <div>
      <h4 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-lg">
        <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-share"></use>
        </svg>
        <?= _t('st_sns_share_title') ?>
      </h4>
      <p class="opacity-60 mb-6 text-theme-text text-sm"><?= _t('st_sns_share_desc') ?></p>

      <div class="space-y-3">
        <template x-for="(btn, index) in buttons" :key="btn.id">
          <div class="flex items-center gap-3 bg-theme-bg/50 p-3 border border-theme-border rounded-theme transition-all hover:bg-theme-bg">

            <div class="flex flex-col gap-1 pr-2 border-r border-theme-border/50">
              <button type="button" @click="moveUp(index)" :disabled="index === 0"
                class="p-0.5 rounded-theme hover:bg-theme-surface text-theme-text disabled:opacity-20 disabled:cursor-not-allowed transition-colors" title="<?= h(_t('move_up')) ?>">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-up"></use>
                </svg>
              </button>
              <button type="button" @click="moveDown(index)" :disabled="index === buttons.length - 1"
                class="p-0.5 rounded-theme hover:bg-theme-surface text-theme-text disabled:opacity-20 disabled:cursor-not-allowed transition-colors" title="<?= h(_t('move_down')) ?>">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
                </svg>
              </button>
            </div>

            <div class="flex items-center gap-2">
              <div class="w-8 h-8 rounded-theme flex items-center justify-center text-white shadow-theme border border-theme-border shrink-0" :style="`background-color: ${btn.color}`">
                <svg class="w-5 h-5">
                  <use :href="<?= htmlspecialchars(json_encode(grinds_asset_url('assets/img/sprite.svg') . '#'), ENT_QUOTES) ?> + btn.icon"></use>
                </svg>
              </div>
              <div class="flex flex-col gap-1">
                <input type="color" x-model="btn.color" class="w-[70px] h-5 p-0 border-none rounded-theme cursor-pointer bg-transparent" title="Color">
                <select x-model="btn.icon" class="w-[70px] text-[10px] form-control-sm px-1 py-0 h-5 bg-transparent border-theme-border/50">
                  <option value="outline-link">Link</option>
                  <option value="outline-share">Share</option>
                  <option value="outline-envelope">Mail</option>
                  <option value="icon-twitter-x">X</option>
                  <option value="icon-facebook">Facebook</option>
                  <option value="icon-line">LINE</option>
                  <option value="icon-instagram">Instagram</option>
                  <option value="icon-discord">Discord</option>
                  <option value="icon-youtube">YouTube</option>
                  <option value="icon-tiktok">TikTok</option>
                  <option value="icon-pinterest">Pinterest</option>
                  <option value="icon-linkedin">LinkedIn</option>
                  <option value="icon-github">GitHub</option>
                  <option value="icon-threads">Threads</option>
                  <option value="icon-twitch">Twitch</option>
                </select>
              </div>
            </div>

            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3">
              <input type="text" x-model="btn.name" class="form-control-sm" placeholder="<?= _t('st_sns_name') ?>">
              <div class="md:col-span-2">
                <input type="text" x-model="btn.url" class="form-control-sm font-mono text-xs w-full" placeholder="<?= _t('st_sns_url') ?>">
              </div>
            </div>

            <div class="flex items-center gap-2 pl-2 border-l border-theme-border/50">
              <label class="flex items-center cursor-pointer" title="<?= h(_t('lbl_active')) ?>">
                <input type="checkbox" x-model="btn.enabled" class="bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-5 h-5 text-theme-primary form-checkbox">
              </label>
              <button type="button" @click="remove(index)" class="text-theme-danger p-2 hover:bg-theme-danger/10 rounded-full transition-colors" title="<?= h(_t('delete')) ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                </svg>
              </button>
            </div>
          </div>
        </template>
      </div>

      <input type="hidden" name="share_buttons" :value="JSON.stringify(buttons)">

      <div class="mt-4">
        <button type="button" @click="add()" class="flex items-center gap-2 shadow-theme px-4 py-2 rounded-theme text-xs font-bold btn-secondary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
          </svg>
          <?= _t('st_sns_add') ?>
        </button>
      </div>
    </div>

    <hr class="border-theme-border my-8">

    <div>
      <h4 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-lg">
        <svg class="w-5 h-5 text-theme-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-code-bracket"></use>
        </svg>
        <?= _t('st_custom_scripts') ?>
      </h4>
      <p class="opacity-60 mb-6 bg-theme-bg p-3 border border-theme-border rounded-theme text-theme-text text-xs leading-relaxed">
        <?= _t('st_script_desc') ?>
      </p>

      <div class="space-y-4">
        <label class="block">
          <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_head_script') ?></span>
          <textarea name="custom_head_scripts" rows="5" class="form-control font-mono text-xs" placeholder="<script>...</script>"><?= h($opt['head_scripts']) ?></textarea>
        </label>
        <label class="block">
          <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_footer_script') ?></span>
          <textarea name="custom_footer_scripts" rows="5" class="form-control font-mono text-xs" placeholder="<script>...</script>"><?= h($opt['footer_scripts']) ?></textarea>
        </label>
      </div>
    </div>

    <div class="mt-8 flex justify-end pt-6 border-t border-theme-border">
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
