<?php

/** TOC Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="bg-theme-bg/40 p-6 border border-theme-border border-dashed rounded text-center"
  x-init="if(typeof block.data.title === 'undefined') block.data.title = 'Contents'">
  <div class="text-theme-text opacity-50 mb-3">
    <svg class="w-8 h-8 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-list-bullet"></use>
    </svg>
    <div class="font-bold text-sm"><?= _t('blk_toc') ?></div>
  </div>

  <input type="text"
    x-model="block.data.title"
    class="form-control-sm text-center max-w-xs mx-auto"
    placeholder="<?= _t('ph_toc_title') ?>">
</div>
