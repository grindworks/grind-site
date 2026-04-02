<?php

/** Embed Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="space-y-3 bg-theme-bg/40 p-4 border border-theme-border rounded-theme" x-init="if(!block.data.align) block.data.align = 'center'">
  <div class="flex gap-2">
    <!-- Alignment selector -->
    <select x-model="block.data.align" class="w-auto text-xs cursor-pointer form-control-sm shrink-0">
      <option value="left"><?= _t('align_left') ?></option>
      <option value="center"><?= _t('align_center') ?></option>
      <option value="right"><?= _t('align_right') ?></option>
    </select>
    <!-- URL input -->
    <input type="text" x-model="block.data.url" :id="'block-' + block.id + '-url'" class="flex-1 text-xs form-control-sm" placeholder="<?= _t('ph_embed_url') ?>">
  </div>
  <!-- Helper text -->
  <div class="opacity-50 ml-1 text-[10px] text-theme-text">
    <?= _t('help_embed_url') ?>
  </div>
  <!-- Preview -->
  <template x-if="block.data.url">
    <div class="mt-3 bg-theme-surface border border-theme-border rounded-theme overflow-hidden aspect-video relative flex items-center justify-center"
      x-html="
            let u = block.data.url;
            let safeU = typeof escapeHtml === 'function' ? escapeHtml(u) : u.replace(/[&<>'&quot;]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','\'':'&#39;','&quot;':'&quot;'}[c] || c));
            let yt = u.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^&?\/ ]{11})/i);
            if (yt) return '<iframe class=\'absolute inset-0 w-full h-full\' src=\'https://www.youtube-nocookie.com/embed/' + yt[1] + '\' frameborder=\'0\' allowfullscreen></iframe>';
            return '<div class=\'text-xs text-theme-text opacity-50\'>External Content: <a href=\'' + safeU + '\' target=\'_blank\' class=\'underline text-theme-primary\'>' + safeU + '</a></div>';
         ">
    </div>
  </template>
</div>
