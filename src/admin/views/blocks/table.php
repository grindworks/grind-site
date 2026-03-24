<?php

/** Table Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme" x-init="if(!block.data.content) block.data.content = [['', ''], ['', '']]; if(block.data.withHeadings === undefined) block.data.withHeadings = true">
  <!-- Options -->
  <div class="flex items-center gap-4 mb-3 text-theme-text text-xs">
    <label class="flex items-center cursor-pointer select-none">
      <input type="checkbox" x-model="block.data.withHeadings" class="mr-2 bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-5 h-5 text-theme-primary form-checkbox">
      <?= _t('table_headings') ?>
    </label>
  </div>
  <!-- Data grid -->
  <div class="bg-theme-bg p-1 border border-theme-border rounded-theme overflow-x-auto">
    <table class="min-w-full text-sm border-collapse">
      <tbody>
        <!-- Loop rows -->
        <template x-for="(row, rowIndex) in block.data.content">
          <tr>
            <!-- Loop cells -->
            <template x-for="(cell, colIndex) in row">
              <td class="relative p-1 border border-theme-border" :class="{'bg-theme-surface font-bold': block.data.withHeadings && rowIndex === 0}">
                <textarea rows="1" x-model="block.data.content[rowIndex][colIndex]" class="bg-transparent px-2 py-1 border-none rounded-theme focus:ring-1 focus:ring-theme-primary w-full text-theme-text text-xs placeholder-theme-text/20 resize-none overflow-hidden" oninput="this.style.height = 'auto'; this.style.height = this.scrollHeight + 'px'"></textarea>
              </td>
            </template>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
  <!-- Control buttons -->
  <div class="flex gap-2 mt-2">
    <button type="button" @click="addTableRow(index)" class="flex items-center gap-1 shadow-theme px-2 py-1 text-[10px] btn-secondary">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
      </svg>
      <?= _t('add_row') ?>
    </button>
    <button type="button" @click="addTableCol(index)" class="flex items-center gap-1 shadow-theme px-2 py-1 text-[10px] btn-secondary">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-right"></use>
      </svg>
      <?= _t('add_col') ?>
    </button>
    <button type="button" x-show="block.data.content.length > 1" @click="removeTableRow(index)" class="hover:bg-theme-danger/10 ml-auto px-2 py-1 border-theme-danger/30 text-[10px] text-theme-danger btn-secondary"><?= _t('btn_del_row') ?></button>
    <button type="button" x-show="block.data.content[0].length > 1" @click="removeTableCol(index)" class="hover:bg-theme-danger/10 px-2 py-1 border-theme-danger/30 text-[10px] text-theme-danger btn-secondary"><?= _t('btn_del_col') ?></button>
  </div>
</div>
