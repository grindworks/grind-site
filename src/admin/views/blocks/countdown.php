<?php

/** Countdown Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="p-4 bg-theme-bg/40 rounded-theme border border-theme-border flex flex-col gap-3">
  <!-- Deadline input -->
  <div class="flex gap-4">
    <div class="flex-1">
      <label class="block text-[10px] font-bold text-theme-text opacity-50 mb-1"><?= _t('lbl_deadline') ?></label>
      <input type="text" x-model="block.data.deadline" :id="'block-' + block.id + '-deadline'" x-init="if(typeof flatpickr !== 'undefined') flatpickr($el, {enableTime: true, dateFormat: 'Y-m-d H:i', time_24hr: true, locale: window.grindsLang === 'ja' ? 'ja' : 'en', onChange: function(selectedDates, dateStr) { $el.value = dateStr; $el.dispatchEvent(new Event('input', { bubbles: true })); }})" class="form-control-sm w-full font-mono text-xs cursor-pointer" placeholder="YYYY-MM-DD HH:MM">
    </div>
  </div>
  <!-- Finish message -->
  <div>
    <label class="block text-[10px] font-bold text-theme-text opacity-50 mb-1"><?= _t('lbl_finish_msg') ?></label>
    <input type="text" x-model="block.data.message" :id="'block-' + block.id + '-message'" class="form-control-sm w-full text-xs" placeholder="<?= _t('ph_countdown_msg') ?>">
  </div>
</div>
