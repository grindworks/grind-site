<?php

/** Map Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme">
  <!-- Helper text -->
  <div class="flex items-center gap-2 opacity-60 mb-2 text-theme-text text-xs">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-map"></use>
    </svg>
    <span><?= _t('ph_map_iframe') ?> (Google Maps / OpenStreetMap)</span>
  </div>
  <!-- Embed code -->
  <textarea x-model="block.data.code" :id="'block-' + block.id + '-code'" rows="4" class="w-full font-mono text-xs form-control-sm" placeholder='<iframe src="https://www.google.com/maps/embed?..."></iframe>'></textarea>
  <!-- Map preview -->
  <div x-show="block.data.code" class="bg-theme-bg mt-2 border border-theme-border rounded-theme aspect-video overflow-hidden">
    <template x-if="block.data.code && block.data.code.match(/<iframe\s+[^>]*src=[\x22\x27]([^\x22\x27]+)[\x22\x27]/i)">
      <iframe :src="block.data.code.match(/<iframe\s+[^>]*src=[\x22\x27]([^\x22\x27]+)[\x22\x27]/i)[1]" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" class="w-full h-full"></iframe>
    </template>
    <template x-if="!(block.data.code && block.data.code.match(/<iframe\s+[^>]*src=[\x22\x27]([^\x22\x27]+)[\x22\x27]/i))">
      <div class='flex items-center justify-center w-full h-full text-theme-text opacity-50 text-xs'>Invalid or unsupported embed code.</div>
    </template>
  </div>
</div>
