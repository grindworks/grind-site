<?php

/** QR Code Block View */
if (!defined('GRINDS_APP'))
  exit; ?>
<div class="flex items-start gap-6 bg-theme-bg/40 p-4 border border-theme-border rounded-theme"
  x-init="if(!block.data.size) block.data.size = 150">
  <!-- Data and size inputs -->
  <div class="flex-1 space-y-3">
    <div>
      <label class="block opacity-50 mb-1 font-bold text-[10px] text-theme-text">
        <?= _t('lbl_qr_url') ?>
      </label>
      <input type="text" x-model="block.data.url" class="w-full text-xs form-control-sm"
        placeholder="<?= _t('ph_url_example') ?>">
    </div>
    <div>
      <label class="block opacity-50 mb-1 font-bold text-[10px] text-theme-text">
        <?= _t('lbl_qr_size') ?>
      </label>
      <input type="number" x-model="block.data.size" min="50" max="500" class="w-24 text-xs form-control-sm">
    </div>
  </div>

  <!-- QR preview -->
  <div class="text-center shrink-0">
    <div class="inline-block bg-theme-surface shadow-theme p-2 border border-theme-border rounded-theme">
      <!-- QR image -->
      <template x-if="block.data.url">
        <img
          :src="'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' + encodeURIComponent(block.data.url)"
          class="w-24 h-24 object-contain" alt="QR Preview">
      </template>
      <!-- Placeholder -->
      <template x-if="!block.data.url">
        <div class="flex justify-center items-center bg-theme-bg w-24 h-24 text-[10px] text-theme-text/40">
          <?= _t('msg_no_url') ?>
        </div>
      </template>
    </div>
    <div class="opacity-50 mt-1 text-[10px] text-theme-text">
      <?= _t('lbl_preview') ?>
    </div>
    <div class="opacity-50 mt-1 text-[9px] text-theme-text flex items-center justify-center gap-1">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-globe-alt"></use>
      </svg>
      <span>Powered by api.qrserver.com</span>
    </div>
  </div>
</div>
