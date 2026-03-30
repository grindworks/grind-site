<?php

/** Social Share Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme" x-init="if(!block.data.align) block.data.align = 'center'; if(!block.data.text) block.data.text = '';">

  <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 mb-4 pb-3 border-b border-theme-border/50">
    <div class="flex items-center justify-center w-10 h-10 bg-theme-surface rounded-full shadow-theme border border-theme-border text-theme-primary shrink-0">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-share"></use>
      </svg>
    </div>

    <div class="flex-1 w-full">
      <label class="block opacity-70 mb-1 font-bold text-theme-text text-[10px]"><?= _t('lbl_share_text') ?></label>
      <input type="text" x-model="block.data.text" :id="'block-' + block.id + '-text'" class="form-control-sm w-full" placeholder="<?= _t('ph_share_text') ?>">
    </div>

    <div class="shrink-0 w-full sm:w-auto">
      <label class="block opacity-70 mb-1 font-bold text-theme-text text-[10px]"><?= _t('lbl_share_align') ?></label>
      <select x-model="block.data.align" class="form-control-sm w-full sm:w-32 cursor-pointer">
        <option value="left"><?= _t('align_left') ?></option>
        <option value="center"><?= _t('align_center') ?></option>
        <option value="right"><?= _t('align_right') ?></option>
      </select>
    </div>
  </div>

  <!-- Preview: reflects actual share button settings -->
  <?php
  $previewButtonsJson = get_option('share_buttons', '[]');
  $previewButtons = json_decode($previewButtonsJson, true);
  if (!is_array($previewButtons) || empty($previewButtons)) {
    $previewButtons = function_exists('get_default_share_buttons') ? get_default_share_buttons() : [];
  }
  $previewEnabled = array_filter($previewButtons, fn($b) => !empty($b['enabled']));
  $spriteUrl = grinds_asset_url('assets/img/sprite.svg');
  ?>
  <div class="mt-2 p-6 border border-theme-border rounded-theme opacity-80 bg-theme-surface flex flex-col justify-center transition-all"
    :class="{ 'items-start text-left': block.data.align === 'left', 'items-center text-center': block.data.align === 'center', 'items-end text-right': block.data.align === 'right' }">

    <span x-show="block.data.text" class="mb-3 font-bold text-sm text-theme-text" x-text="block.data.text"></span>

    <div class="flex flex-wrap gap-2" :class="{ 'justify-start': block.data.align === 'left', 'justify-center': block.data.align === 'center', 'justify-end': block.data.align === 'right' }">
      <?php if (!empty($previewEnabled)): ?>
        <?php foreach ($previewEnabled as $btn): ?>
          <div class="w-8 h-8 rounded-full shadow-theme text-white flex items-center justify-center" style="background-color: <?= h($btn['color']) ?>">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
              <use href="<?= $spriteUrl ?>#<?= h($btn['icon']) ?>"></use>
            </svg>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <span class="text-[11px] text-theme-text/40"><?= _t('msg_share_preview') ?></span>
      <?php endif; ?>
    </div>

    <p class="mt-4 text-[10px] text-theme-text/50 font-bold"><?= _t('msg_share_preview') ?></p>
  </div>

</div>
