<?php

/** Password Protect Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="space-y-3 bg-theme-bg/40 p-4 pb-6 mb-4 border border-theme-warning/50 rounded-theme relative overflow-visible" x-init="if(typeof block.data.password === 'undefined') block.data.password = ''; if(typeof block.data.message === 'undefined') block.data.message = '';">
    <div class="absolute top-0 left-0 w-1 h-full bg-theme-warning"></div>
    <div class="flex items-center gap-2 text-theme-warning mb-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-lock-closed"></use>
        </svg>
        <span class="font-bold text-sm"><?= _t('blk_password_protect') ?></span>
    </div>
    <p class="text-xs text-theme-text/70 mb-3"><?= _t('desc_password_protect_note') ?></p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block opacity-70 mb-1 font-bold text-[10px] text-theme-text"><?= _t('lbl_password') ?></label>
            <div class="relative" x-data="{ show: false }">
                <input :type="show ? 'text' : 'password'" x-model="block.data.password" :id="'block-' + block.id + '-password'" class="w-full text-xs form-control-sm font-mono pr-8" placeholder="<?= _t('ph_password') ?>">
                <button type="button" @click="show = !show" class="absolute right-0 inset-y-0 px-2 flex items-center opacity-50 hover:opacity-100 focus:outline-none text-theme-text">
                    <svg x-show="!show" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
                    </svg>
                    <svg x-show="show" x-cloak class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye-slash"></use>
                    </svg>
                </button>
            </div>
        </div>
        <div>
            <label class="block opacity-70 mb-1 font-bold text-[10px] text-theme-text"><?= _t('lbl_unlock_msg') ?></label>
            <input type="text" x-model="block.data.message" :id="'block-' + block.id + '-message'" class="w-full text-xs form-control-sm" placeholder="<?= _t('ph_unlock_msg') ?>">
        </div>
    </div>

    <!-- Visual separator indicator -->
    <div class="absolute -bottom-3 left-1/2 -translate-x-1/2 bg-theme-warning text-theme-bg px-3 py-1 rounded-full text-[10px] font-bold shadow-theme flex items-center gap-1 z-10 whitespace-nowrap">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-down"></use>
        </svg>
        <span><?= _t('msg_password_protect_below') ?></span>
    </div>
</div>
