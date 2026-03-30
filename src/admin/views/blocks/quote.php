<?php

/** Quote Block View */
if (!defined('GRINDS_APP'))
  exit; ?>
<div class="bg-theme-bg/40 p-4 rounded-r-theme border-l-4 border-theme-border">
  <!-- Quote content -->
  <textarea x-model="block.data.text" :id="'block-' + block.id + '-text'" rows="2"
    x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' })"
    @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
    class="w-full bg-transparent border-none focus:ring-0 p-0 text-base italic resize-none overflow-hidden placeholder-theme-text/30 text-theme-text"
    placeholder="<?= _t('ph_quote') ?>"></textarea>
  <!-- Citation input -->
  <div class="mt-2 flex gap-2">
    <input type="text" x-model="block.data.cite" :id="'block-' + block.id + '-cite'"
      class="w-1/3 bg-transparent border-none focus:ring-0 p-0 text-xs text-theme-text opacity-60 text-right"
      placeholder="<?= _t('ph_quote_source') ?>">
    <input type="url" x-model="block.data.citeUrl" :id="'block-' + block.id + '-citeUrl'"
      @blur="block.data.citeUrl = normalizeUrl(block.data.citeUrl)" class="w-2/3 bg-transparent border-b border-theme-border focus:ring-0 p-0 text-xs text-theme-text opacity-60"
      placeholder="<?= _t('ph_quote_url') ?>">
  </div>
</div>
