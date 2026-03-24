<?php

/** Search Box Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="flex items-center gap-3 bg-theme-bg/40 p-4 border border-theme-border rounded-theme">
  <!-- Search icon -->
  <div class="opacity-50 text-theme-text shrink-0">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
    </svg>
  </div>
  <!-- Placeholder input -->
  <input type="text" x-model="block.data.placeholder" class="w-full text-sm form-control-sm" placeholder="<?= _t('ph_search_box') ?>">
</div>
