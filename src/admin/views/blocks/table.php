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
            @dragover.prevent.stop="dropIdx = rowIndex"
            @drop.prevent.stop="
              if(dragIdx !== null && dragIdx !== rowIndex) {
                  const draggedItem = block.data.content.splice(dragIdx, 1)[0];
                  const insertPos = (dragIdx < rowIndex) ? rowIndex - 1 : rowIndex;
                  block.data.content.splice(insertPos, 0, draggedItem);
              }
              dragIdx = null; dropIdx = null;
            ">
            <!-- Controls (Drag handle & Move buttons) -->
            <td class="w-8 p-1 border border-theme-border text-center align-middle" :class="{'bg-theme-surface': block.data.withHeadings && rowIndex === 0}">
              <div class="flex flex-col items-center justify-center w-full h-full gap-1 opacity-50 hover:opacity-100 transition-opacity">
                <!-- Move up -->
                <button type="button" @click.prevent="if(rowIndex > 0) [block.data.content[rowIndex - 1], block.data.content[rowIndex]] = [block.data.content[rowIndex], block.data.content[rowIndex - 1]]" x-show="rowIndex > 0" class="p-1 text-theme-text hover:text-theme-primary transition-colors" title="<?= h(_t('btn_move_up') ?? 'Move Up') ?>">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-up"></use>
                  </svg>
                </button>
                <!-- Drag handle (Hidden on mobile, visible on PC) -->
                <button type="button" draggable="true"
                  @dragstart.stop="dragIdx = rowIndex; $event.dataTransfer.effectAllowed='move';"
                  @dragend.stop="dragIdx = null; dropIdx = null;"
                  class="cursor-grab active:cursor-grabbing hidden sm:block p-1 text-theme-text hover:text-theme-primary transition-colors" title="<?= h(_t('drag_to_reorder') ?? 'Drag to reorder') ?>">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                  </svg>
                </button>
                <!-- Move down -->
                <button type="button" @click.prevent="if(rowIndex < block.data.content.length - 1) [block.data.content[rowIndex], block.data.content[rowIndex + 1]] = [block.data.content[rowIndex + 1], block.data.content[rowIndex]]" x-show="rowIndex < block.data.content.length - 1" class="p-1 text-theme-text hover:text-theme-primary transition-colors" title="<?= h(_t('btn_move_down') ?? 'Move Down') ?>">
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
                <!-- Column Controls (visible on hover for the first row) -->
                <div x-show="rowIndex === 0" class="absolute -top-3 left-1/2 -translate-x-1/2 z-10 flex items-center bg-theme-surface border border-theme-border rounded shadow-theme opacity-0 group-hover/cell:opacity-100 group-focus-within/cell:opacity-100 transition-opacity">
                  <!-- Move Left -->
                  <button type="button" @click.prevent="if(colIndex > 0) block.data.content.forEach(r => [r[colIndex - 1], r[colIndex]] = [r[colIndex], r[colIndex - 1]])" x-show="colIndex > 0" class="p-1 text-theme-text hover:text-theme-primary transition-colors" title="<?= h(_t('btn_move_left') ?? 'Move Left') ?>">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-left"></use>
                    </svg>
                  </button>
                  <!-- Move Right -->
                  <button type="button" @click.prevent="if(colIndex < block.data.content[0].length - 1) block.data.content.forEach(r => [r[colIndex], r[colIndex + 1]] = [r[colIndex + 1], r[colIndex]])" x-show="colIndex < block.data.content[0].length - 1" class="p-1 text-theme-text hover:text-theme-primary transition-colors" title="<?= h(_t('btn_move_right') ?? 'Move Right') ?>">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-right"></use>
                    </svg>
                  </button>
                  <!-- Delete col -->
                  <button type="button" x-show="block.data.content[0].length > 1" @click.prevent="if(confirm(window.grindsTranslations?.confirm_delete || 'Are you sure?')) { let newArr = [...block.data.content]; newArr.forEach(r => r.splice(colIndex, 1)); block.data.content = newArr; }" class="p-1 text-theme-text hover:text-theme-danger transition-colors" title="<?= h(_t('btn_del_col') ?? 'Delete Column') ?>">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
                    </svg>
                  </button>
                </div>
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
