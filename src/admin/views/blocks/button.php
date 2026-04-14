<?php

/** Button Block View */
if (!defined('GRINDS_APP')) exit;

// Load color configuration
$btn_colors = $block_config['library']['design']['items']['button']['colors'] ?? [];
?>
<div class="flex items-start gap-3 bg-theme-bg/40 p-4 border border-theme-border rounded-theme"
  x-init="if(!block.data.color) block.data.color = 'primary'; if(block.data.external === undefined) block.data.external = true"
  x-data="{
    colors: <?= htmlspecialchars(json_encode($btn_colors), ENT_QUOTES) ?>,
    searchResults: [],
    searching: false,
    async searchContent(query) {
      if (!query) {
        this.searchResults = [];
        return;
      }
      try {
        const res = await fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/api/post_search.php?q=' + encodeURIComponent(query));
        if (res.ok) {
          this.searchResults = await res.json();
          return;
        }
      } catch (e) {
        const q = query.toLowerCase();
        this.searchResults = (window.grindsLinkablePages || [])
          .filter(p => p.title.toLowerCase().includes(q) || p.slug.toLowerCase().includes(q))
          .slice(0, 10).map(p => ({ title: p.title, url: '/' + p.slug, type: p.type }));
      }
    }
  }" @click.outside="searching = false">
  <!-- Text and URL inputs -->
  <div class="flex-1 space-y-2">
    <div class="flex gap-2">
      <input type="text" x-model="block.data.text" :id="'block-' + block.id + '-text'" class="flex-1 font-bold form-control-sm" placeholder="<?= _t('ph_btn_text') ?>">
      <!-- Style preview -->
      <div class="flex justify-center items-center px-3 py-1 border rounded-theme font-bold text-xs transition-colors shrink-0"
        :class="(colors[block.data.color] || {}).class"
        :style="(colors[block.data.color] || {}).style">
        <?= _t('preview') ?>
      </div>
    </div>
    <div class="relative">
      <input type="text" x-model="block.data.url" :id="'block-' + block.id + '-url'" @focus="searching = true" @input.debounce.300ms="searchContent($event.target.value)" @blur="setTimeout(() => { block.data.url = normalizeUrl(block.data.url) }, 200)" class="w-full font-mono text-xs form-control-sm" placeholder="<?= _t('ph_btn_url') ?> (<?= _t('ph_type_to_search') ?>)">
      <div x-show="searching && searchResults.length > 0" x-cloak
        class="absolute left-0 right-0 z-50 bg-theme-surface shadow-theme mt-1 border border-theme-border rounded-theme max-h-40 overflow-y-auto">
        <template x-for="result in searchResults">
          <button type="button" class="flex justify-between items-center hover:bg-theme-bg px-3 py-2 w-full text-xs text-left truncate transition-colors"
            @click="block.data.url = result.url; searching = false; searchResults = [];">
            <span x-text="result.title" class="font-medium text-theme-text"></span>
            <span x-text="window.grindsTranslations?.['type_' + result.type] || result.type" class="opacity-50 ml-2 px-1 border border-theme-border rounded-theme text-[9px] uppercase"></span>
          </button>
        </template>
      </div>
    </div>
    <p class="opacity-50 ml-1 text-[10px] text-theme-text"><?= _t('help_relative_path') ?></p>
  </div>
  <!-- Color and link options -->
  <div class="space-y-2 w-36 shrink-0">
    <select x-model="block.data.color" class="text-xs cursor-pointer form-control-sm">
      <?php foreach ($btn_colors as $key => $details): ?>
        <option value="<?= h($key) ?>"><?= h($details['label']) ?></option>
      <?php endforeach; ?>
    </select>
    <label class="flex items-center gap-2 text-theme-text text-xs cursor-pointer select-none">
      <input type="checkbox" x-model="block.data.external" class="bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-5 h-5 text-theme-primary form-checkbox">
      <?= _t('lbl_new_tab') ?>
    </label>
  </div>
</div>
