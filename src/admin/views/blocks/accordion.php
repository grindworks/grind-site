<?php

/** Accordion Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="space-y-3" x-init="if(!block.data.items) block.data.items = [{id: generateId(), title:'', content:''}]; block.data.items.forEach(i => { if(!i.id) i.id = generateId(); })">
  <!-- Loop items -->
  <template x-for="(item, i) in block.data.items" :key="item.id">
    <div class="group/item relative bg-theme-bg/40 mb-2 p-4 border border-theme-border rounded-theme">
      <!-- Delete item -->
      <button type="button" @click="if(!item.title && !item.content || confirm(window.grindsTranslations?.confirm_delete || 'Are you sure?')) block.data.items.splice(i, 1)" class="top-2 right-2 absolute hover:bg-theme-danger/10 p-1 rounded-theme text-theme-text/40 hover:text-theme-danger transition-colors" title="<?= h(_t('delete')) ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
        </svg>
      </button>
      <div class="space-y-3 pr-8">
        <!-- Question input -->
        <div>
          <label class="block opacity-50 mb-1 font-bold text-[10px] text-theme-text"><?= _t('lbl_question') ?></label>
          <input type="text" x-model="item.title" :id="'block-' + block.id + '-item-' + i + '-title'" class="w-full font-bold form-control-sm" placeholder="<?= _t('ph_question') ?>">
        </div>
        <!-- Answer input -->
        <div>
          <label class="block opacity-50 mb-1 font-bold text-[10px] text-theme-text"><?= _t('lbl_answer') ?></label>
          <textarea x-model="item.content" :id="'block-' + block.id + '-item-' + i + '-content'" rows="2"
            x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' })"
            @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
            class="w-full text-xs form-control-sm overflow-hidden resize-none" placeholder="<?= _t('ph_answer') ?>"></textarea>
        </div>
      </div>
    </div>
  </template>

  <!-- Add item button -->
  <button type="button" @click="block.data.items.push({id: generateId(), title:'', content:''}); $nextTick(() => { $el.closest('.space-y-3').querySelectorAll('input[type=text]')[block.data.items.length-1]?.focus() })" class="py-2 border-2 hover:border-theme-primary/50 border-dashed w-full text-xs btn-secondary">+ <?= _t('btn_add_item') ?></button>
</div>
