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
    <table class="min-w-full text-sm border-collapse" x-data="{ dragIdx: null, dropIdx: null }">
      <tbody @dragleave.prevent="dropIdx = null">
        <!-- Loop rows -->
        <template x-for="(row, rowIndex) in block.data.content" :key="rowIndex">
          <tr :class="{'border-t-2 border-theme-primary': dropIdx === rowIndex && dragIdx !== null && dragIdx > rowIndex, 'border-b-2 border-theme-primary': dropIdx === rowIndex && dragIdx !== null && dragIdx < rowIndex}"
            @dragover.prevent="dropIdx = rowIndex"
            @drop.prevent="if(dragIdx !== null && dragIdx !== rowIndex) { let newArr = [...block.data.content]; let temp = newArr[dragIdx]; newArr.splice(dragIdx, 1); newArr.splice(rowIndex, 0, temp); block.data.content = newArr; } dragIdx = null; dropIdx = null;">
            <!-- Controls (Drag handle & Move buttons) -->
            <td class="w-8 p-1 border border-theme-border text-center align-middle" :class="{'bg-theme-surface': block.data.withHeadings && rowIndex === 0}">
              <div class="flex flex-col items-center justify-center w-full h-full gap-1 opacity-50 hover:opacity-100 transition-opacity">
                <!-- Move up -->
                <button type="button" @click.prevent="if(rowIndex > 0) { let newArr = [...block.data.content]; let temp = newArr[rowIndex]; newArr[rowIndex] = newArr[rowIndex - 1]; newArr[rowIndex - 1] = temp; block.data.content = newArr; }" x-show="rowIndex > 0" class="p-1 text-theme-text hover:text-theme-primary transition-colors" title="<?= h(_t('btn_move_up') ?? 'Move Up') ?>">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-up"></use>
                  </svg>
                </button>
                <!-- Drag handle (Hidden on mobile, visible on PC) -->
                <button type="button" draggable="true"
                  @dragstart="dragIdx = rowIndex; $event.dataTransfer.effectAllowed='move';"
                  @dragend="dragIdx = null; dropIdx = null;"
                  class="cursor-grab active:cursor-grabbing hidden sm:block p-1 text-theme-text hover:text-theme-primary transition-colors" title="<?= h(_t('drag_to_reorder') ?? 'Drag to reorder') ?>">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                  </svg>
                </button>
                <!-- Move down -->
                <button type="button" @click.prevent="if(rowIndex < block.data.content.length - 1) { let newArr = [...block.data.content]; let temp = newArr[rowIndex]; newArr[rowIndex] = newArr[rowIndex + 1]; newArr[rowIndex + 1] = temp; block.data.content = newArr; }" x-show="rowIndex < block.data.content.length - 1" class="p-1 text-theme-text hover:text-theme-primary transition-colors" title="<?= h(_t('btn_move_down') ?? 'Move Down') ?>">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
                  </svg>
                </button>
                <!-- Delete row -->
                <button type="button" @click.prevent="if(block.data.content.length > 1 && confirm(window.grindsTranslations?.confirm_delete || 'Are you sure?')) { block.data.content.splice(rowIndex, 1); }" x-show="block.data.content.length > 1" class="p-1 text-theme-text hover:text-theme-danger transition-colors" title="<?= h(_t('btn_del_row') ?? 'Delete Row') ?>">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
                  </svg>
                </button>
              </div>
            </td>

            <!-- Loop cells -->
            <template x-for="(cell, colIndex) in row" :key="colIndex">
              <td class="relative p-1 border border-theme-border group/cell" :class="{'bg-theme-surface font-bold': block.data.withHeadings && rowIndex === 0}">
                <!-- Delete col (visible on hover for the first row) -->
                <button type="button" x-show="rowIndex === 0 && block.data.content[0].length > 1" @click.prevent="if(confirm(window.grindsTranslations?.confirm_delete || 'Are you sure?')) { block.data.content.forEach(r => r.splice(colIndex, 1)); }" class="absolute -top-2.5 -right-2.5 z-10 p-1 bg-theme-surface border border-theme-border rounded-full text-theme-text hover:text-theme-danger transition-opacity opacity-0 group-hover/cell:opacity-100 shadow-theme" title="<?= h(_t('btn_del_col') ?? 'Delete Column') ?>">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
                  </svg>
                </button>
                <textarea rows="1" x-model="block.data.content[rowIndex][colIndex]" :id="'block-' + block.id + '-cell-' + rowIndex + '-' + colIndex"
                  class="bg-transparent px-2 py-1 border-none rounded-theme focus:ring-1 focus:ring-theme-primary w-full text-theme-text text-xs placeholder-theme-text/20 resize-none overflow-hidden"
                  x-init="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' })"
                  @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"></textarea>
              </td>
            </template>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
  <!-- Control buttons -->
  <div class="flex gap-2 mt-2">
    <button type="button" @click="addTableRow(index); $nextTick(() => { const trs = $el.closest('[x-init]').querySelectorAll('tbody tr'); if(trs.length) { const ta = trs[trs.length-1].querySelector('textarea'); if(ta) ta.focus(); } })" class="flex items-center gap-1 shadow-theme px-2 py-1 text-[10px] btn-secondary">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
      </svg>
      <?= _t('add_row') ?>
    </button>
    <button type="button" @click="addTableCol(index); $nextTick(() => { const trs = $el.closest('[x-init]').querySelectorAll('tbody tr'); if(trs.length) { const tas = trs[0].querySelectorAll('textarea'); if(tas.length) tas[tas.length-1].focus(); } })" class="flex items-center gap-1 shadow-theme px-2 py-1 text-[10px] btn-secondary">
      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-right"></use>
      </svg>
      <?= _t('add_col') ?>
    </button>
  </div>
</div>
