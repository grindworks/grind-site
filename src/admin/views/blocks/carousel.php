<?php

/** Carousel Block View */
if (!defined('GRINDS_APP')) exit; ?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme" x-init="if(!block.data.images) block.data.images = []; if(block.data.autoplay === undefined) block.data.autoplay = true; $watch('block.data.images', v => v && v.forEach(i => { if(!i.id) i.id = Math.random().toString(36).substr(2, 9) })); block.data.images.forEach(i => { if(!i.id) i.id = Math.random().toString(36).substr(2, 9) })" x-data="{ isUploading: false }">
    <!-- Options -->
    <div class="flex justify-between items-center mb-3">
        <span class="flex items-center gap-1 opacity-70 font-bold text-theme-text text-xs">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-film"></use>
            </svg>
            <?= _t('lbl_slides') ?>
        </span>
        <!-- Autoplay toggle -->
        <label class="flex items-center cursor-pointer">
            <input type="checkbox" x-model="block.data.autoplay" class="mr-2 bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-5 h-5 text-theme-primary form-checkbox">
            <span class="text-theme-text text-xs"><?= _t('lbl_autoplay') ?></span>
        </label>
    </div>

    <!-- Slides container -->
    <div class="flex gap-2 pb-2 overflow-x-auto custom-scrollbar">
        <!-- Loop slides -->
        <template x-for="(img, i) in block.data.images" :key="img.id">
            <div class="group relative bg-theme-bg/40 border border-theme-border rounded-theme w-32 h-32 overflow-hidden shrink-0">
                <img :src="resolvePreviewUrl(img.url)" class="w-full h-full object-cover" @error="$el.src = <?= htmlspecialchars(json_encode(PLACEHOLDER_IMG), ENT_QUOTES) ?>">
                <!-- Delete slide -->
                <button type="button" @click="block.data.images.splice(i, 1)" class="top-1 right-1 absolute bg-theme-surface/90 md:opacity-0 opacity-100 group-hover:opacity-100 shadow-theme p-2 min-w-[32px] min-h-[32px] flex items-center justify-center border border-theme-border rounded-full text-theme-danger transition-opacity">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
                    </svg>
                </button>
                <!-- Slide number -->
                <div class="bottom-1 left-1 absolute bg-theme-text/80 px-1.5 rounded-theme text-[10px] text-theme-bg" x-text="i+1"></div>
            </div>
        </template>

        <!-- Add from library -->
        <button type="button" @click="openMediaLibrary(index, 'add', 'url')" class="flex flex-col justify-center items-center bg-theme-bg opacity-50 border-2 border-theme-border hover:border-theme-primary/50 border-dashed rounded-theme w-32 h-32 text-theme-text hover:text-theme-primary transition-colors shrink-0" title="<?= h(_t('btn_select_library')) ?>">
            <svg class="mb-1 w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
            </svg>
            <span class="font-bold text-[9px]"><?= _t('title_media_library') ?></span>
        </button>

        <!-- Upload images -->
        <label class="flex flex-col justify-center items-center bg-theme-bg opacity-50 border-2 border-theme-border hover:border-theme-primary/50 border-dashed rounded-theme w-32 h-32 text-theme-text hover:text-theme-primary transition-colors cursor-pointer shrink-0" title="<?= h(_t('upload')) ?>" :class="{'opacity-25 cursor-not-allowed': isUploading}">
            <input type="file" multiple accept="image/*" class="hidden" @change="isUploading = true; await uploadGalleryImages($event, index); isUploading = false" :disabled="isUploading">
            <svg x-show="!isUploading" class="mb-1 w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-up-tray"></use>
            </svg>
            <svg x-show="isUploading" class="mb-1 w-8 h-8 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
            </svg>
            <span class="font-bold text-[9px]" x-text="isUploading ? '...' : '<?= _t('upload') ?>'"></span>
        </label>
    </div>
    <!-- Scroll helper -->
    <div class="opacity-40 mt-1 text-[10px] text-theme-text text-center"><?= _t('msg_swipe_scroll') ?></div>
</div>
