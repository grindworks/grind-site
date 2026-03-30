<?php

/** Math Equation Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="space-y-3 bg-theme-bg/40 p-4 border border-theme-border rounded-theme" x-init="if(!block.data.code) block.data.code = ''; if(!block.data.display) block.data.display = 'block'">

  <div class="flex items-center gap-4 mb-2 border-b border-theme-border/50 pb-3">
    <div class="flex items-center justify-center w-10 h-10 bg-theme-surface rounded-full shadow-theme border border-theme-border text-theme-primary shrink-0">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-beaker"></use>
      </svg>
    </div>
    <div>
      <label class="block opacity-70 mb-1 font-bold text-theme-text text-[10px]"><?= _t('lbl_math_display') ?></label>
      <select x-model="block.data.display" class="form-control-sm w-48 cursor-pointer">
        <option value="block"><?= _t('opt_math_block') ?></option>
        <option value="inline"><?= _t('opt_math_inline') ?></option>
      </select>
    </div>
  </div>

  <div>
    <label class="block opacity-70 mb-1 font-bold text-theme-text text-[10px]"><?= _t('lbl_math_code') ?></label>
    <textarea x-model="block.data.code" :id="'block-' + block.id + '-code'" rows="4" class="w-full font-mono text-xs form-control-sm" placeholder="<?= _t('ph_math_code') ?>"></textarea>
  </div>

  <div class="mt-2 space-y-1">
    <div class="opacity-50 text-[10px] text-theme-text flex items-center gap-1">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-information-circle"></use>
      </svg>
      <?= _t('msg_math_notice') ?>
    </div>
    <div class="opacity-75 text-[10px] text-orange-500/90 flex items-center gap-1">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
      </svg>
      <span><?= _t('msg_math_cdn_notice') ?></span>
    </div>
  </div>
</div>
