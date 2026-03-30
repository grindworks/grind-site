<?php

/** Post Grid Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme" x-init="if(!block.data.limit) block.data.limit = 6; if(!block.data.columns) block.data.columns = '3'; if(!block.data.category) block.data.category = ''; if(!block.data.style) block.data.style = 'card';">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Style -->
        <div>
            <label class="block opacity-70 mb-1 font-bold text-theme-text text-[10px]"><?= _t('lbl_grid_style') ?></label>
            <select x-model="block.data.style" class="form-control-sm w-full cursor-pointer">
                <option value="card"><?= _t('opt_style_card') ?></option>
                <option value="list"><?= _t('opt_style_list') ?></option>
            </select>
        </div>

        <!-- Columns -->
        <div x-show="block.data.style === 'card'">
            <label class="block opacity-70 mb-1 font-bold text-theme-text text-[10px]"><?= _t('lbl_grid_columns') ?></label>
            <select x-model="block.data.columns" class="form-control-sm w-full cursor-pointer">
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
            </select>
        </div>

        <!-- Limit -->
        <div>
            <label class="block opacity-70 mb-1 font-bold text-theme-text text-[10px]"><?= _t('lbl_grid_limit') ?></label>
            <input type="number" x-model="block.data.limit" :id="'block-' + block.id + '-limit'" min="1" max="100" class="form-control-sm w-full">
        </div>

        <!-- Category -->
        <div>
            <label class="block opacity-70 mb-1 font-bold text-theme-text text-[10px]"><?= _t('lbl_grid_category') ?></label>
            <select x-model="block.data.category" class="form-control-sm w-full cursor-pointer">
                <option value=""><?= _t('all') ?></option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= h($cat['id']) ?>"><?= h($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Preview Placeholder -->
    <div class="mt-4 p-6 border-2 border-dashed border-theme-border rounded-theme text-center opacity-50 bg-theme-surface flex flex-col items-center justify-center">
        <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-numbered-list"></use>
        </svg>
        <p class="text-xs text-theme-text font-bold"><?= _t('msg_post_grid_preview') ?></p>
    </div>
</div>
