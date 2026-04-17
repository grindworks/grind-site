<?php

/** HTML Block View */
if (!defined('GRINDS_APP')) exit;

$isAmazonEnabled = file_exists(ROOT_PATH . '/plugins/amazon_affiliate.php');
?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme"
  x-init="if(block.previewMode === undefined) block.previewMode = false; if(block.previewHtml === undefined) block.previewHtml = '';">

  <!-- Helper text and Action Buttons -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
    <div class="flex items-center gap-2 opacity-60 text-theme-text text-xs">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-code-bracket"></use>
      </svg>
      <span x-show="!block.previewMode"><?= _t('help_html_block') ?></span>
      <span x-show="block.previewMode" x-cloak class="font-bold text-theme-primary">Live Preview Mode</span>
    </div>

    <!-- Block Tools -->
    <div class="flex items-center gap-2 shrink-0">

      <!-- Preview Toggle Button -->
      <button type="button" @click="previewHtmlBlock(index)"
        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 border rounded-theme text-[10px] font-bold transition-colors"
        :class="block.previewMode ? 'bg-theme-primary text-theme-on-primary border-theme-primary shadow-theme' : 'bg-theme-surface text-theme-text border-theme-border hover:bg-theme-bg/50'"
        title="Toggle Live Preview">
        <svg x-show="!block.previewMode" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
        </svg>
        <svg x-show="block.previewMode" x-cloak class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-code-bracket-square"></use>
        </svg>
        <span x-text="block.previewMode ? 'Edit Code' : 'Preview'"></span>
      </button>

      <div class="w-px h-4 bg-theme-border mx-1"></div>

      <!-- Amazon Button (Only visible in edit mode) -->
      <button type="button" x-show="!block.previewMode" @click="
        <?php if ($isAmazonEnabled): ?>
        const el = document.getElementById('block-' + block.id + '-code');
        const text = block.data.code || '';
        const appendText = (text ? '\n' : '') + '[amazon id=\'ASIN\' title=\'商品名\']';
        block.data.code = text + appendText;
        $nextTick(() => {
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.focus();
            const startPos = text.length + (text ? 1 : 0) + 12;
            el.setSelectionRange(startPos, startPos + 4);
        });
        <?php else: ?>
        alert(window.grindsLang === 'ja' ? 'この機能を使用するには、Amazonアフィリエイトプラグイン (amazon_affiliate.php) を有効にしてください。' : 'Please enable the Amazon Affiliate plugin (amazon_affiliate.php) to use this feature.');
        <?php endif; ?>
      " class="inline-flex items-center gap-1.5 px-2.5 py-1.5 hover:bg-theme-bg/50 border border-theme-border rounded-theme text-theme-text text-[10px] font-bold transition-colors" title="Insert Amazon Shortcode">
        <svg class="w-3.5 h-3.5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-shopping-bag"></use>
        </svg>
        Amazon
      </button>

      <?php if (function_exists('do_action')) do_action('grinds_html_block_tools'); ?>
    </div>
  </div>

  <!-- HTML Code Editor Mode -->
  <div x-show="!block.previewMode">
    <textarea x-model="block.data.code" :id="'block-' + block.id + '-code'" rows="6"
      class="w-full font-mono text-xs form-control-sm resize-y overflow-y-auto min-h-[5rem] max-h-[500px]"
      @keydown.escape="$el.blur()"
      @keydown.tab.prevent="handleCodeIndent($event, index)"
      placeholder="<?= _t('ph_html_code') ?>"></textarea>
    <p class="mt-1 font-bold text-[10px] text-theme-warning"><?= _t('html_absolute_path_warn') ?></p>
  </div>

  <!-- Live Preview Mode -->
  <div x-show="block.previewMode" x-cloak class="relative bg-theme-surface border border-theme-border rounded-theme p-4 min-h-[5rem] overflow-hidden">
    <!-- The rendered HTML content (including resolved shortcodes) -->
    <div class="pointer-events-none" x-html="block.previewHtml"></div>

    <!-- Overlay to prevent accidental clicks inside the preview -->
    <div class="absolute inset-0 z-10 cursor-pointer" @click="previewHtmlBlock(index)" title="Click to Edit Code"></div>
  </div>
</div>
