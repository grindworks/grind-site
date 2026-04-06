<?php

/** HTML Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme">
  <!-- Helper text -->
  <div class="flex items-center gap-2 opacity-60 mb-2 text-theme-text text-xs">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-code-bracket"></use>
    </svg>
    <span><?= _t('help_html_block') ?></span>
  </div>
  <!-- HTML code -->
  <textarea x-model="block.data.code" :id="'block-' + block.id + '-code'" rows="6"
    class="w-full font-mono text-xs form-control-sm resize-y overflow-y-auto min-h-[5rem] max-h-[500px]"
    placeholder="<?= _t('ph_html_code') ?>"></textarea>
  <p class="mt-1 font-bold text-[10px] text-theme-warning"><?= _t('html_absolute_path_warn') ?></p>
</div>
