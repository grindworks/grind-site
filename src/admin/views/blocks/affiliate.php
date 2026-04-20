<?php

/** Affiliate Block View (Editor UI) */
if (!defined('GRINDS_APP')) exit;
?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme shadow-sm transition-colors"
    x-init="
    if(!block.data.platform) block.data.platform = 'amazon';
    if(typeof block.data.title === 'undefined') block.data.title = '';
    if(typeof block.data.productId === 'undefined') block.data.productId = '';
    if(!block.data.region) block.data.region = 'co.jp';
    if(typeof block.data.url === 'undefined') block.data.url = '';
    if(typeof block.data.image === 'undefined') block.data.image = '';
  "
    x-data="{
    // Smart Paste & Extract
    handleSmartInput(e) {
      const val = e.target.value.trim();
      if (!val) return;

      if (block.data.platform === 'amazon') {
        // Extract ASIN from Amazon URL
        const asinMatch = val.match(/(?:dp|o|asin|product)\/([a-zA-Z0-9]{10})/i) || val.match(/\/([a-zA-Z0-9]{10})(?:[/?]|$)/i);
        if (asinMatch && asinMatch[1] && asinMatch[1].length === 10) {
          block.data.productId = asinMatch[1];

          // Extract Region (co.jp, com, etc.)
          const domainMatch = val.match(/amazon\.([a-z.]+)\//i);
          if (domainMatch) {
             block.data.region = domainMatch[1];
          }

          if (typeof window.showToast === 'function') {
            window.showToast('<?= _t('msg_asin_extracted') ?>: ' + block.data.productId, 'success');
          }
        } else {
          block.data.productId = val;
        }
      }
    }
  }">

    <!-- Header: Platform Tabs -->
    <div class="flex items-center gap-3 mb-4 pb-3 border-b border-theme-border/50">
        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-theme-surface border border-theme-border text-theme-primary shrink-0 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-shopping-bag"></use>
            </svg>
        </div>
        <div class="flex bg-theme-surface p-1 border border-theme-border rounded-theme shadow-inner">
            <button type="button" @click="block.data.platform = 'amazon'" :class="block.data.platform === 'amazon' ? 'bg-[#FF9900] text-black shadow-theme' : 'text-theme-text hover:bg-theme-bg'" class="px-4 py-1.5 text-xs font-bold rounded-theme transition-all">Amazon</button>
            <button type="button" @click="block.data.platform = 'rakuten'" :class="block.data.platform === 'rakuten' ? 'bg-[#BF0000] text-white shadow-theme' : 'text-theme-text hover:bg-theme-bg'" class="px-4 py-1.5 text-xs font-bold rounded-theme transition-all">Rakuten</button>
            <button type="button" @click="block.data.platform = 'ebay'" :class="block.data.platform === 'ebay' ? 'bg-[#E53238] text-white shadow-theme' : 'text-theme-text hover:bg-theme-bg'" class="px-4 py-1.5 text-xs font-bold rounded-theme transition-all">eBay</button>
        </div>
    </div>

    <!-- Amazon Form -->
    <div x-show="block.data.platform === 'amazon'" class="space-y-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <label class="block opacity-70 mb-1 font-bold text-theme-text text-[10px] uppercase tracking-wider"><?= _t('lbl_asin_url') ?></label>
                <div class="relative">
                    <input type="text" x-model="block.data.productId" @change="handleSmartInput" class="w-full form-control-sm font-mono text-xs pl-8 border-theme-primary/30 focus:border-theme-primary transition-colors shadow-inner" placeholder="B000000000 or Paste Amazon URL...">
                    <svg class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-theme-primary/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-link"></use>
                    </svg>
                </div>
                <p class="opacity-50 mt-1.5 text-[9px] text-theme-text leading-tight font-bold"><?= _t('desc_asin_extract') ?></p>
            </div>
            <div class="w-full sm:w-28 shrink-0">
                <label class="block opacity-70 mb-1 font-bold text-theme-text text-[10px] uppercase tracking-wider"><?= _t('lbl_region') ?></label>
                <input type="text" x-model="block.data.region" class="w-full form-control-sm text-xs text-center font-bold" placeholder="co.jp">
            </div>
        </div>
        <div>
            <label class="block opacity-70 mb-1 font-bold text-theme-text text-[10px] uppercase tracking-wider"><?= _t('lbl_title') ?> (Optional)</label>
            <input type="text" x-model="block.data.title" class="w-full form-control-sm text-xs" placeholder="<?= h(_t('ph_affiliate_title_amazon')) ?>">
        </div>
    </div>

    <!-- Rakuten / eBay Form -->
    <div x-show="block.data.platform !== 'amazon'" x-cloak class="space-y-4">
        <div class="flex flex-col sm:flex-row gap-5 items-start">
            <!-- Left: Image Uploader/Preview -->
            <div class="w-full sm:w-32 shrink-0 text-center">
                <label class="block opacity-70 mb-1 font-bold text-theme-text text-[10px] uppercase tracking-wider text-left"><?= _t('lbl_product_image') ?></label>
                <div class="group relative bg-checker border border-theme-border rounded-theme aspect-square overflow-hidden cursor-pointer shadow-sm" @click="openMediaLibrary(block.id, null, 'image')">
                    <img :src="resolvePreviewUrl(block.data.image)" x-show="block.data.image" class="w-full h-full object-cover">
                    <div x-show="!block.data.image" class="flex justify-center items-center w-full h-full text-theme-text/20">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
                        </svg>
                    </div>
                    <div class="absolute inset-0 flex justify-center items-center skin-modal-overlay opacity-0 group-hover:opacity-100 text-[10px] font-bold text-white transition-opacity duration-200"><?= _t('btn_change') ?></div>
                </div>
                <input type="text" x-model="block.data.image" :id="'block-' + block.id + '-image'" @blur="block.data.image = normalizeUrl(block.data.image)" class="mt-2 w-full text-[9px] font-mono form-control-sm text-center opacity-70" placeholder="https://...">
            </div>

            <!-- Right: URL & Title -->
            <div class="flex-1 space-y-4 w-full">
                <div>
                    <label class="block opacity-70 mb-1 font-bold text-theme-text text-[10px] uppercase tracking-wider"><?= _t('lbl_product_url') ?></label>
                    <div class="relative">
                        <input type="text" x-model="block.data.url" class="w-full form-control-sm font-mono text-xs pl-8" placeholder="https://...">
                        <svg class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-theme-text/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-link"></use>
                        </svg>
                    </div>
                </div>
                <div>
                    <label class="block opacity-70 mb-1 font-bold text-theme-text text-[10px] uppercase tracking-wider"><?= _t('lbl_title') ?></label>
                    <input type="text" x-model="block.data.title" class="w-full form-control-sm text-sm font-bold" placeholder="<?= h(_t('ph_affiliate_title')) ?>">
                </div>
            </div>
        </div>
    </div>

</div>
