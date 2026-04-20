<?php

/** Internal Link Card Block View */
if (!defined('GRINDS_APP'))
  exit; ?>
<div class="bg-theme-bg/40 p-4 border border-theme-border rounded-theme relative transition-all" :class="{ 'z-50 ring-1 ring-theme-primary': searching }" x-data="{
       searching: false,
       loading: false,
       results: [],
       keyword: '',
       init() {
         if (!this.block.data.id) this.block.data.id = '';
         // Try to resolve title from preloaded data
         let found = null;
         if (this.block.data.id && window.grindsLinkablePages) {
           found = window.grindsLinkablePages.find(p => String(p.id) === String(this.block.data.id));
         }

         if (found) {
           this.keyword = found.title;
           if (!this.block.data.titleCache) this.block.data.titleCache = found.title;
         } else if (this.block.data.titleCache) {
           this.keyword = this.block.data.titleCache;
         } else if (this.block.data.id) {
           this.keyword = 'ID: ' + this.block.data.id;
           this.fetchTitle(this.block.data.id);
         }
       },
       async performSearch() {
         if (!this.keyword) {
           this.results = [];
           this.searching = false;
           return;
         }
         this.loading = true;
         this.searching = true;
         try {
           const baseUrl = (window.grindsBaseUrl || '').replace(/\/$/, '');
           const res = await fetch(`${baseUrl}/admin/api/post_search.php?q=${encodeURIComponent(this.keyword)}`);
           if (res.status === 401) {
             window.location.reload();
             this.results = [];
           } else if (res.ok) {
             this.results = await res.json();
           } else {
             this.results = [];
           }
         } catch (e) {
           console.error('Search failed', e);
           this.results = [];
         } finally {
           this.loading = false;
         }
       },
       async fetchTitle(id) {
         this.loading = true;
         try {
           const baseUrl = (window.grindsBaseUrl || '').replace(/\/$/, '');
           const res = await fetch(`${baseUrl}/admin/api/post_search.php?q=id:${encodeURIComponent(id)}`);
           if (res.status === 401) {
             window.location.reload();
           } else if (res.ok) {
             const data = await res.json();
             const list = Array.isArray(data) ? data : (data.data || []);
             const match = list.find(p => String(p.id) === String(id));
             if (match) {
               this.keyword = match.title;
               this.block.data.titleCache = match.title;
             }
           }
         } catch (e) {
           console.error('Title fetch failed', e);
         } finally {
           this.loading = false;
         }
       },
       selectItem(item) {
         this.block.data.id = String(item.id);
         this.block.data.titleCache = item.title;
         this.keyword = item.title;
         // Close suggestions reliably on next tick to prevent reopening from auto-filled keyword
         this.$nextTick(() => {
             this.searching = false;
             this.results = [];
         });
       }
     }" x-init="init()" @click.outside="searching = false">

  <div class="flex justify-between items-center mb-1">
    <label class="block opacity-50 font-bold text-[10px] text-theme-text">
      <?= _t('lbl_select_post') ?>
    </label>
    <div x-show="block.data.id" class="opacity-70 text-theme-text text-[10px]">
      <?= _t('lbl_selected_id') ?>: <span x-text="block.data.id" class="font-mono font-bold text-theme-primary"></span>
    </div>
  </div>

  <div class="relative">
    <div class="relative">
      <input type="text" x-model="keyword" :id="'block-' + block.id + '-keyword'" @input.debounce.300ms="performSearch()"
        @focus="if(keyword && results.length > 0) searching = true" class="w-full text-xs form-control-sm pr-8"
        placeholder="<?= _t('ph_type_to_search') ?>...">
      <div x-show="loading" class="absolute right-2 top-1/2 -translate-y-1/2 text-theme-text opacity-50" x-cloak>
        <svg class="animate-spin h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
        </svg>
      </div>
    </div>

    <div x-show="searching && keyword" x-transition:enter="transition ease-out duration-100"
      x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
      class="absolute left-0 right-0 mt-1 bg-theme-surface border border-theme-border shadow-theme rounded-theme max-h-48 overflow-y-auto z-50"
      x-cloak>
      <template x-if="results.length === 0 && !loading">
        <div class="px-3 py-2 text-[10px] text-theme-text opacity-50 text-center">
          <?= _t('msg_no_results') ?>
        </div>
      </template>
      <template x-for="p in results" :key="p.id">
        <button type="button" @click="selectItem(p)"
          class="w-full text-left px-3 py-2 text-xs hover:bg-theme-bg transition-colors flex justify-between items-center border-b border-theme-border/30 last:border-0 group">
          <div class="flex flex-col overflow-hidden">
            <span x-text="p.title"
              class="truncate font-medium text-theme-text group-hover:text-theme-primary transition-colors"></span>
            <span class="text-[9px] opacity-50 flex gap-1">
              <span x-text="p.type === 'page' ? 'PAGE' : 'POST'" class="uppercase"></span>
              <span x-show="p.date">&bull;</span>
              <span x-text="p.date"></span>
            </span>
          </div>
          <span x-text="'ID:' + p.id"
            class="text-[9px] font-mono opacity-40 shrink-0 bg-theme-bg px-1 rounded-theme border border-theme-border"></span>
        </button>
      </template>
    </div>
  </div>
</div>
