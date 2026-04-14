<?php

/**
 * media.php
 *
 * Renders the media library interface for managing files.
 */
if (!defined('GRINDS_APP'))
  exit;

// Load skin data.
if (!isset($skin) || !is_array($skin)) {
  $skin = require __DIR__ . '/../load_skin.php';
}

?>

<script>
  window.grindsCsrfToken = <?= json_encode(generate_csrf_token()) ?>;
  window.grindsUploadMax = <?= grinds_get_max_upload_size() ?>;
  window.grindsTranslations = {
    ...window.grindsTranslations,
    file_too_large: <?= json_encode(_t('js_file_too_large'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
    upload_failed: <?= json_encode(_t('js_upload_failed'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
    confirm_delete: <?= json_encode(_t('confirm_delete'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
    confirm_bulk_delete: <?= json_encode(_t('msg_confirm_delete_n'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
    confirm_delete_media: <?= json_encode(_t('confirm_delete_media'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
    confirm_force_delete: <?= json_encode(_t('confirm_force_delete'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
    delete_error: <?= json_encode(_t('js_delete_error'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
    uploading: <?= json_encode(_t('uploading'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
    uploading_wait: <?= json_encode(_t('msg_uploading_wait'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
    msg_delete_skipped: <?= json_encode(_t('msg_delete_skipped'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
    copied: <?= json_encode(_t('copied'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
    saved: <?= json_encode(_t('msg_saved'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
    error: <?= json_encode(_t('error'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
    filter_images: <?= json_encode(_t('filter_images'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
    Video: <?= json_encode(_t('Video'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
    Audio: <?= json_encode(_t('Audio'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>,
    filter_docs: <?= json_encode(_t('filter_docs'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>
  };
</script>
<script src="<?= grinds_asset_url('assets/js/media_manager.js') ?>"></script>

<div class="flex flex-col gap-4 mb-6" x-data="{ ...mediaManager(), dragCount: 0 }" x-init="init(); $watch('detailModalOpen', val => window.toggleScrollLock(val));"
  @dragenter.prevent="dragCount++; isDragging = true"
  @dragleave.prevent="dragCount--; if (dragCount === 0) isDragging = false"
  @dragover.prevent=""
  @drop.prevent="dragCount = 0; handleDrop($event)"
  @keydown.window.escape="detailModalOpen = false" @keydown.window.arrow-left="if(detailModalOpen) prevFile()"
  @keydown.window.arrow-right="if(detailModalOpen) nextFile()">

  <!-- Page Header -->
  <div class="flex sm:flex-row flex-col justify-between items-start sm:items-center gap-4">
    <h2 class="flex items-center gap-2 font-bold text-theme-text text-xl whitespace-nowrap shrink-0">
      <svg class="w-6 h-6 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
      </svg>
      <?= _t('title_media_library') ?>
    </h2>

    <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
      <!-- Upload button -->
      <label
        class="hidden sm:flex items-center gap-2 shadow-theme px-6 py-2.5 rounded-theme font-bold text-sm transition-all cursor-pointer btn-primary shrink-0"
        :class="isUploading ? 'opacity-50 cursor-not-allowed' : ''">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-up-tray"></use>
        </svg>
        <span x-text='isUploading ? trans.uploading : <?= json_encode(_t('upload'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>'></span>
        <input type="file" multiple class="sr-only" @change="uploadFiles($event)" :disabled="isUploading">
      </label>
    </div>
  </div>

  <!-- Toolbar Area -->
  <div class="top-0 z-20 sticky flex flex-col gap-3 bg-theme-surface shadow-theme p-4 border border-theme-border rounded-theme">

    <!-- Controls Row: Sort, Filter, View -->
    <div class="flex flex-wrap justify-between items-center gap-2">

      <div class="flex flex-wrap items-center gap-2">
        <!-- Search form -->
        <div class="relative order-first sm:order-none mb-2 sm:mb-0 w-full sm:w-auto">
          <div
            class="flex flex-wrap items-center gap-1 p-1 pl-8 focus-within:ring-1 focus-within:ring-theme-primary w-full sm:w-auto h-auto min-h-[34px] cursor-text form-control-sm"
            @click="$refs.searchInput.focus()">
            <svg class="top-1/2 left-2.5 absolute opacity-50 w-3.5 h-3.5 text-theme-text -translate-y-1/2" fill="none"
              stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
            </svg>
            <template x-for="(word, index) in searchKeywords" :key="index">
              <span
                class="flex items-center gap-1 bg-theme-primary/10 px-1.5 py-0.5 border border-theme-primary/20 rounded-theme text-[10px] text-theme-primary">
                <span x-text="word"></span>
                <button type="button" @click.stop="removeSearchKeyword(index)"
                  class="-m-1 p-1 focus:outline-none hover:text-theme-danger"
                  aria-label="<?= h(_t('remove')) ?>">&times;</button>
              </span>
            </template>
            <input type="text" x-ref="searchInput" x-model="searchInput"
              @keydown.enter.prevent="if(!$event.isComposing) addSearchKeyword()"
              @keydown.backspace="if(searchInput === '' && searchKeywords.length > 0) removeSearchKeyword(searchKeywords.length - 1)"
              @input.debounce.500ms="search()"
              class="flex-1 bg-transparent p-1 border-none outline-none min-w-[200px] h-6 text-theme-text text-xs placeholder-theme-text/40"
              placeholder="<?= _t('ph_search_media_extended') ?? 'Search (Filename, Title, Alt, Tags...)' ?>">
          </div>
        </div>

        <!-- Sort Order -->
        <select x-model="sort" @change="search()" class="w-auto h-[34px] cursor-pointer form-control-sm">
          <option value="newest">
            <?= _t('sort_newest') ?>
          </option>
          <option value="oldest">
            <?= _t('sort_oldest') ?>
          </option>
          <option value="name_asc">
            <?= _t('sort_name_asc') ?>
          </option>
          <option value="name_desc">
            <?= _t('sort_name_desc') ?>
          </option>
        </select>

        <!-- Filter Toggle -->
        <button @click="showFilters = !showFilters"
          class="relative flex items-center gap-1.5 px-3 py-2 rounded-theme h-[34px] text-theme-text text-xs btn-secondary"
          :class="showFilters ? 'bg-theme-bg' : ''" title="<?= _t('lbl_filters') ?>"
          aria-label="<?= _t('lbl_filters') ?>">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-adjustments-vertical"></use>
          </svg>
          <?= _t('lbl_filters') ?>
          <span x-show="isFiltered"
            class="top-0 right-0 absolute bg-theme-primary border-2 border-theme-surface rounded-full w-2.5 h-2.5"></span>
        </button>

        <!-- Mobile Upload button (visible below sm) -->
        <label
          class="sm:hidden flex items-center gap-1.5 px-3 py-2 rounded-theme h-[34px] font-bold text-xs transition-all cursor-pointer btn-primary shrink-0"
          :class="isUploading ? 'opacity-50 cursor-not-allowed' : ''">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-up-tray"></use>
          </svg>
          <span x-text='isUploading ? trans.uploading : <?= json_encode(_t('upload'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>'></span>
          <input type="file" multiple class="sr-only" @change="uploadFiles($event)" :disabled="isUploading">
        </label>
      </div>

      <!-- Right: View Controls & Item Count -->
      <div class="flex items-center gap-3">
        <!-- Grid Size Slider -->
        <div class="hidden md:flex items-center gap-2" x-show="viewMode === 'grid'">
          <svg class="opacity-40 w-3 h-3 text-theme-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
          </svg>
          <input type="range" min="2" max="8" x-model="gridCols"
            class="bg-theme-border rounded-theme w-20 h-1 accent-theme-primary appearance-none cursor-pointer">
          <svg class="opacity-40 w-4 h-4 text-theme-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-squares-2x2"></use>
          </svg>
        </div>

        <div class="hidden sm:block bg-theme-border w-px h-4"></div>

        <span class="opacity-50 text-theme-text text-xs"><span x-text="files.length"></span>
          <?= _t('lbl_items_shown') ?>
        </span>

        <div class="hidden sm:block bg-theme-border w-px h-4"></div>

        <!-- View Toggles -->
        <div class="flex bg-theme-bg p-0.5 border border-theme-border rounded-theme shrink-0">
          <button @click="viewMode = 'grid'"
            :class="viewMode === 'grid' ? 'bg-theme-surface shadow-theme text-theme-primary' : 'text-theme-text opacity-50 hover:opacity-100'"
            class="p-1 rounded-theme transition-all" title="<?= h(_t('view_grid')) ?>"
            aria-label="<?= h(_t('view_grid')) ?>">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-squares-2x2"></use>
            </svg>
          </button>
          <button @click="viewMode = 'list'"
            :class="viewMode === 'list' ? 'bg-theme-surface shadow-theme text-theme-primary' : 'text-theme-text opacity-50 hover:opacity-100'"
            class="p-1 rounded-theme transition-all" title="<?= h(_t('view_list')) ?>"
            aria-label="<?= h(_t('view_list')) ?>">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-bars-3"></use>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Collapsible Filters -->
    <div x-show="showFilters" x-collapse
      class="gap-3 grid grid-cols-2 sm:grid-cols-3 pt-3 border-theme-border border-t">
      <!-- Type Filter -->
      <select x-model="typeFilter" @change="search()" class="w-auto cursor-pointer form-control-sm">
        <option value="all">
          <?= _t('filter_all_types') ?>
        </option>
        <option value="image">
          <?= _t('filter_images') ?>
        </option>
        <option value="video">
          <?= _t('Video') ?>
        </option>
        <option value="audio">
          <?= _t('Audio') ?>
        </option>
        <option value="document">
          <?= _t('filter_docs') ?>
        </option>
      </select>

      <!-- Extension Filter -->
      <select x-model="extFilter" @change="search()" class="w-auto cursor-pointer form-control-sm">
        <option value="">
          <?= _t('filter_all_ext') ?>
        </option>
        <option value="jpg">JPG</option>
        <option value="png">PNG</option>
        <option value="gif">GIF</option>
        <option value="webp">WebP</option>
        <option value="svg">SVG</option>
        <option value="pdf">PDF</option>
        <option value="docx">DOCX</option>
        <option value="xlsx">XLSX</option>
        <option value="pptx">PPTX</option>
        <option value="txt">TXT</option>
        <option value="csv">CSV</option>
        <option value="md">MD</option>
        <option value="json">JSON</option>
        <option value="xml">XML</option>
        <option value="zip">ZIP</option>
        <option value="mp4">MP4</option>
        <option value="webm">WebM</option>
        <option value="mp3">MP3</option>
        <option value="wav">WAV</option>
        <option value="m4a">M4A</option>
      </select>

      <!-- Date Filter -->
      <select x-model="dateFilter" @change="search()" class="w-auto cursor-pointer form-control-sm">
        <option value="">
          <?= _t('filter_all_dates') ?>
        </option>
        <?php foreach ($mediaMonths as $m): ?>
          <option value="<?= h($m) ?>">
            <?= h($m) ?>
          </option>
        <?php
        endforeach; ?>
      </select>
    </div>

    <!-- Active Filters Display (Chips) -->
    <div x-show="activeFilters.length > 0" class="flex flex-wrap items-center gap-2 pt-3 border-theme-border border-t" style="display: none;" x-transition x-cloak>
      <span class="opacity-70 font-bold text-theme-text text-xs">
        <?= _t('active_filters') ?? 'Filter:' ?>
      </span>
      <template x-for="filter in activeFilters" :key="filter.type">
        <span class="flex items-center gap-1 bg-theme-primary/10 px-2 py-0.5 border border-theme-primary/20 rounded-theme font-bold text-theme-primary text-xs">
          <span x-text="filter.label"></span>
          <button type="button" @click="clearFilter(filter.type)" class="-mr-1 p-0.5 focus:outline-none hover:text-theme-danger" aria-label="<?= h(_t('remove') ?? 'Remove') ?>">&times;</button>
        </span>
      </template>
      <button type="button" @click="clearFilter('all')" class="opacity-60 hover:opacity-100 ml-1 text-theme-text text-xs hover:underline">
        <?= _t('clear_all') ?? 'Clear all' ?>
      </button>
    </div>

    <!-- Select All + Bulk Actions -->
    <div class="flex flex-wrap justify-between items-center gap-3 pt-3 border-theme-border border-t">
      <label class="flex items-center gap-2 cursor-pointer select-none">
        <input type="checkbox"
          class="bg-theme-bg border-theme-border rounded-sm w-5 h-5 text-theme-primary form-checkbox"
          :checked="isAllSelected" @change="toggleSelectAll()">
        <span class="opacity-70 font-bold text-theme-text text-xs">
          <?= _t('all') ?>
        </span>
      </label>

      <!-- Bulk Delete (visible only when items selected) -->
      <div x-show="selectedIds.length > 0" class="flex items-center gap-2" x-transition>
        <button @click="bulkDelete()"
          class="flex items-center gap-1.5 hover:bg-theme-danger px-3 py-1.5 border-theme-danger rounded-theme font-bold text-theme-danger hover:text-white text-xs transition-colors btn-secondary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
          </svg>
          <?= _t('delete') ?> (<span x-text="selectedIds.length"></span>)
        </button>
        <button @click="selectedIds = []" class="opacity-60 text-theme-text text-xs hover:underline">
          <?= _t('cancel') ?>
        </button>
      </div>
    </div>
  </div>

  <!-- Main Content Area -->
  <div
    class="relative flex flex-col flex-1 bg-theme-surface shadow-theme border border-theme-border rounded-theme min-h-[500px]">

    <!-- Drag & Drop Overlay -->
    <div x-show="isDragging"
      class="z-50 absolute inset-0 flex flex-col justify-center items-center bg-theme-primary/10 backdrop-blur-sm border-4 border-theme-primary border-dashed rounded-theme text-theme-primary pointer-events-none"
      style="display: none;" x-transition>
      <p class="font-bold text-2xl pointer-events-none">
        <?= _t('media_drop_hint') ?>
      </p>
      <p class="opacity-80 mt-2 font-bold text-sm pointer-events-none" x-show="window.grindsUploadMax">
        <span x-text="'Max: ' + Math.max(1, Math.floor(window.grindsUploadMax / 1048576)) + 'MB'"></span>
      </p>
    </div>

    <!-- Loading Indicator -->
    <div x-show="loading" class="z-40 absolute inset-0 flex justify-center bg-theme-surface/80 pt-20" x-transition>
      <svg class="w-8 h-8 text-theme-primary animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
      </svg>
    </div>

    <!-- Upload Overlay -->
    <div x-show="isUploading"
      class="z-50 absolute inset-0 flex flex-col justify-center items-center bg-theme-surface/90" style="display: none;"
      x-transition>
      <svg class="mb-4 w-12 h-12 text-theme-primary animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
      </svg>
      <p class="font-bold text-theme-text text-lg">
        <span x-text="trans.uploading"></span> <span x-show="uploadProgress" x-text="'(' + uploadProgress + ')'"></span>
      </p>

      <!-- Progress Bar -->
      <div class="bg-theme-bg mt-4 border border-theme-border/50 rounded-full w-64 h-2 overflow-hidden" x-show="uploadProgressPercent > 0">
        <div class="bg-theme-primary h-full transition-all duration-200 ease-out" :style="'width: ' + uploadProgressPercent + '%'"></div>
      </div>
      <p x-show="uploadProgressPercent > 0" class="mt-1 font-bold text-theme-primary text-xs" x-text="uploadProgressPercent + '%'"></p>

      <p class="opacity-60 mt-4 text-theme-text text-sm" x-text="trans.uploading_wait"></p>
      <p class="opacity-40 mt-1 text-[10px] text-theme-text"><?= _t('msg_upload_may_take_time') ?? '* Depending on your environment, it may take a few minutes.' ?></p>
    </div>

    <!-- Grid View Area -->
    <div x-show="viewMode === 'grid'" class="p-4" x-transition:enter.opacity.duration.300ms
      @click.self="selectedIds = []">
      <div class="gap-4 grid" :class="gridClasses" @click.self="selectedIds = []">
        <template x-for="(file, index) in files" :key="file.id">
          <div
            class="group relative bg-checker shadow-theme hover:shadow-theme border rounded-theme aspect-square overflow-hidden transition-all duration-200 cursor-pointer"
            :class="selectedIds.includes(file.id) ? 'border-theme-primary ring-2 ring-theme-primary ring-offset-1' : 'border-theme-border hover:border-theme-primary'"
            @click="openDetail(file)">

            <div class="relative w-full h-full">
              <template x-if="file.is_image">
                <div class="bg-checker w-full h-full">
                  <img :src="file.thumbnail_url || file.url"
                    class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-500"
                    loading="lazy">
                </div>
              </template>
              <template x-if="!file.is_image && file.file_type && file.file_type.startsWith('video/')">
                <div class="flex flex-col justify-center items-center bg-theme-bg/80 p-2 w-full h-full text-theme-primary">
                  <svg class="mb-1 w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-film"></use>
                  </svg>
                  <span class="w-full font-bold text-[10px] text-theme-text text-center truncate" x-text="file.metadata?.original_name || file.filename"></span>
                  <span class="opacity-50 mt-0.5 text-[9px] uppercase" x-text="file.filename.split('.').pop()"></span>
                </div>
              </template>
              <template x-if="!file.is_image && file.file_type && file.file_type.startsWith('audio/')">
                <div class="flex flex-col justify-center items-center bg-theme-bg/80 p-2 w-full h-full text-theme-primary">
                  <svg class="mb-1 w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-musical-note"></use>
                  </svg>
                  <span class="w-full font-bold text-[10px] text-theme-text text-center truncate" x-text="file.metadata?.original_name || file.filename"></span>
                  <span class="opacity-50 mt-0.5 text-[9px] uppercase" x-text="file.filename.split('.').pop()"></span>
                </div>
              </template>
              <template
                x-if="!file.is_image && (!file.file_type || (!file.file_type.startsWith('video/') && !file.file_type.startsWith('audio/')))">
                <div class="flex flex-col justify-center items-center bg-theme-bg/80 p-2 w-full h-full text-theme-primary">
                  <svg class="mb-1 w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
                  </svg>
                  <span class="w-full font-bold text-[10px] text-theme-text text-center truncate" x-text="file.metadata?.original_name || file.filename"></span>
                  <span class="opacity-50 mt-0.5 text-[9px] uppercase" x-text="file.filename.split('.').pop()"></span>
                </div>
              </template>
            </div>

            <div class="top-0 left-0 z-10 absolute p-2"
              @click.stop="toggleSelect(file.id); $event.shiftKey && selectRange(index)">
              <div class="flex justify-center items-center shadow-theme border rounded-theme w-5 h-5 transition-colors"
                :class="selectedIds.includes(file.id) ? 'bg-theme-primary border-theme-primary text-theme-on-primary' : 'bg-theme-surface border-theme-border text-transparent hover:border-theme-primary'">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check"></use>
                </svg>
              </div>
            </div>

            <div class="right-1 bottom-1 absolute flex flex-col items-end gap-1 pointer-events-none">
              <template x-if="file.metadata?.is_ai">
                <span
                  class="bg-theme-primary/90 shadow-theme px-1.5 py-0.5 border border-white/20 rounded-theme font-bold text-[9px] text-theme-on-primary">AI</span>
              </template>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- List View Area -->
    <div x-show="viewMode === 'list'" class="overflow-x-auto" x-transition:enter.opacity.duration.300ms
      style="display:none;">
      <table class="min-w-full text-left leading-normal">
        <thead
          class="bg-theme-bg/50 border-theme-border border-b font-bold text-theme-text/60 text-xs uppercase tracking-wider">
          <tr>
            <th class="px-6 py-4 w-10"></th>
            <th class="px-6 py-4 w-16">
              <?= _t('col_preview') ?>
            </th>
            <th class="px-6 py-4">
              <?= _t('col_filename') ?>
            </th>
            <th class="px-6 py-4 w-24">
              <?= _t('col_type') ?>
            </th>
            <th class="px-6 py-4 w-24 text-right">
              <?= _t('col_size') ?>
            </th>
            <th class="px-6 py-4 w-32 text-right">
              <?= _t('col_date') ?>
            </th>
            <th class="px-6 py-4 w-16">
              <?= _t('col_action') ?>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-theme-border text-sm">
          <template x-for="(file, index) in files" :key="file.id">
            <tr class="hover:bg-theme-bg/50 transition-colors cursor-pointer"
              :class="selectedIds.includes(file.id) ? 'bg-theme-primary/5' : ''" @click="openDetail(file)">
              <td class="px-6 py-4" @click.stop="toggleSelect(file.id); $event.shiftKey && selectRange(index)">
                <div class="flex justify-center items-center border rounded-theme w-5 h-5"
                  :class="selectedIds.includes(file.id) ? 'bg-theme-primary border-theme-primary text-theme-on-primary' : 'bg-theme-surface border-theme-border text-transparent'">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check"></use>
                  </svg>
                </div>
              </td>
              <td class="px-6 py-4">
                <div
                  class="flex justify-center items-center bg-checker border border-theme-border rounded-theme w-10 h-10 overflow-hidden">
                  <template x-if="file.is_image">
                    <div class="bg-checker w-full h-full"><img :src="file.thumbnail_url || file.url"
                        class="w-full h-full object-contain" loading="lazy"></div>
                  </template>
                  <template x-if="!file.is_image && file.file_type && file.file_type.startsWith('video/')">
                    <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-film"></use>
                    </svg>
                  </template>
                  <template
                    x-if="!file.is_image && (!file.file_type || (!file.file_type.startsWith('video/') && !file.file_type.startsWith('audio/')))">
                    <span class="px-1 font-bold text-[9px] text-theme-primary truncate uppercase"
                      x-text="file.filename.split('.').pop()"></span>
                  </template>
                  <template x-if="!file.is_image && file.file_type && file.file_type.startsWith('audio/')">
                    <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-musical-note"></use>
                    </svg>
                  </template>
                </div>
              </td>
              <td class="px-6 py-4 max-w-xs font-medium text-theme-text truncate">
                <div class="truncate" x-text="file.metadata?.original_name || file.filename"></div>
                <div class="opacity-40 font-mono text-[10px] text-theme-text truncate" x-text="file.url"></div>
              </td>
              <td class="opacity-70 px-6 py-4 text-theme-text text-xs"
                x-text="file.filename.split('.').pop().toUpperCase()"></td>
              <td class="opacity-70 px-6 py-4 font-mono text-theme-text text-xs text-right"
                x-text="formatSize(file.file_size)"></td>
              <td class="opacity-70 px-6 py-4 font-mono text-theme-text text-xs text-right"
                x-text="file.uploaded_at ? file.uploaded_at.substring(0, 10) : ''"></td>
              <td class="px-6 py-4 text-center">
                <div class="flex justify-center items-center gap-2">
                  <button @click.stop="openDetail(file)"
                    class="flex justify-center items-center p-2 text-theme-primary hover:scale-110 transition-transform"
                    aria-label="<?= h(_t('view')) ?>"><svg class="w-4 h-4" fill="none" stroke="currentColor"
                      viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
                    </svg></button>
                  <button @click.stop="deleteFile(file)"
                    class="flex justify-center items-center p-2 text-theme-danger hover:scale-110 transition-transform"
                    title="<?= h(_t('delete')) ?>"><svg class="w-4 h-4" fill="none" stroke="currentColor"
                      viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                    </svg></button>
                </div>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div x-show="!loading && files.length === 0"
      class="flex flex-col flex-1 justify-center items-center bg-theme-bg/30 px-4 py-16 border-2 border-theme-border border-dashed rounded-theme text-center" x-cloak>
      <div class="flex justify-center items-center bg-theme-surface opacity-50 shadow-theme mb-4 rounded-full w-16 h-16 text-theme-text">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-up-tray"></use>
        </svg>
      </div>
      <h3 class="mb-1 font-bold text-theme-text text-lg">
        <?= _t('msg_no_media') ?>
      </h3>
      <p class="opacity-60 text-theme-text text-sm">
        <?= _t('media_drop_hint') ?>
      </p>
      <p class="opacity-40 mt-1 font-mono text-[10px] text-theme-text" x-show="window.grindsUploadMax">
        <span x-text="'Max: ' + Math.max(1, Math.floor(window.grindsUploadMax / 1048576)) + 'MB'"></span>
      </p>
    </div>

    <!-- Pagination Controls (Responsive) -->
    <div
      class="flex sm:flex-row flex-col justify-between items-center gap-4 sm:gap-0 bg-theme-bg/10 mt-auto p-4 border-theme-border border-t">
      <div class="flex justify-center sm:justify-start items-center gap-2 w-full sm:w-auto">
        <label class="opacity-70 font-bold text-theme-text text-xs">
          <?= _t('lbl_items_per_page') ?>
        </label>
        <select x-model="limit" @change="search()" class="bg-theme-surface py-1 w-20 text-xs form-control-sm">
          <option value="20">20</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>

      <div class="flex justify-center sm:justify-end items-center gap-2 w-full sm:w-auto">
        <button @click="changePage(page - 1)" :disabled="page <= 1"
          class="flex items-center gap-1 disabled:opacity-50 px-3 py-1.5 text-xs btn-secondary">
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-left"></use>
          </svg>
          <?= _t('prev') ?>
        </button>
        <span class="opacity-70 font-bold text-xs">
          <?= _t('lbl_page') ?> <span x-text="page"></span> / <span x-text="Math.ceil(total / limit) || 1"></span>
        </span>
        <button @click="changePage(page + 1)" :disabled="page * limit >= total"
          class="flex items-center gap-1 disabled:opacity-50 px-3 py-1.5 text-xs btn-secondary">
          <?= _t('next') ?>
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-right"></use>
          </svg>
        </button>
      </div>
    </div>
  </div>

  <!-- File Detail Modal -->
  <template x-teleport="body">
    <div x-show="detailModalOpen" class="z-50 fixed inset-0 flex justify-center items-center p-4" style="display: none;"
      x-cloak>
      <div class="fixed inset-0 backdrop-blur-sm transition-opacity skin-modal-overlay"
        @click="detailModalOpen = false"></div>

      <!-- Modal Navigation and Content -->
      <div class="z-10 relative flex justify-center items-center gap-4 w-full h-full pointer-events-none">

        <button @click="prevFile()"
          class="hidden md:flex justify-center items-center opacity-80 hover:opacity-100 p-4 rounded-full pointer-events-auto btn-secondary shrink-0"
          title="<?= h(_t('prev')) ?>" aria-label="<?= h(_t('prev')) ?>">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-left"></use>
          </svg>
        </button>

        <div
          class="flex md:flex-row flex-col bg-theme-surface shadow-theme border border-theme-border rounded-theme w-full max-w-6xl max-h-[90vh] md:max-h-[95vh] overflow-hidden pointer-events-auto">

          <!-- Preview Area -->
          <div
            class="group relative flex flex-col justify-center items-center bg-checker bg-theme-bg/50 p-4 md:p-8 border-theme-border md:border-r border-b md:border-b-0 w-full md:w-2/3 overflow-hidden shrink-0">
            <template x-if="activeFile">
              <div class="flex justify-center items-center w-full h-full">
                <template x-if="activeFile.is_image">
                  <img :src="activeFile.url"
                    class="shadow-theme rounded-theme max-w-full max-h-[40vh] md:max-h-[80vh] object-contain">
                </template>
                <template
                  x-if="!activeFile.is_image && activeFile.file_type && activeFile.file_type.startsWith('video/')">
                  <video controls
                    class="shadow-theme rounded-theme max-w-full max-h-[40vh] md:max-h-[80vh] object-contain">
                    <source :src="activeFile.url" :type="activeFile.file_type">
                  </video>
                </template>
                <template
                  x-if="!activeFile.is_image && activeFile.file_type && activeFile.file_type.startsWith('audio/')">
                  <audio controls class="shadow-theme rounded-theme w-full max-w-md">
                    <source :src="activeFile.url" :type="activeFile.file_type">
                  </audio>
                </template>
                <template
                  x-if="!activeFile.is_image && (!activeFile.file_type || (!activeFile.file_type.startsWith('video/') && !activeFile.file_type.startsWith('audio/')))">
                  <div class="opacity-50 text-center">
                    <svg class="mx-auto mb-4 w-32 h-32 text-theme-text" fill="none" stroke="currentColor"
                      viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
                    </svg>
                    <div class="px-4 font-mono font-bold text-theme-text text-2xl break-all"
                      x-text="activeFile.metadata?.original_name || activeFile.filename"></div>
                  </div>
                </template>
              </div>
            </template>
          </div>

          <!-- File Info Sidebar -->
          <div class="flex flex-col flex-1 bg-theme-surface w-full md:w-1/3 md:min-w-[320px] min-h-0">
            <div class="flex justify-between items-center bg-theme-bg/20 p-4 md:p-6 border-theme-border border-b">
              <div class="pr-4 min-w-0">
                <h3 class="font-bold text-theme-text text-lg truncate"
                  x-text="activeFile?.metadata?.original_name || activeFile?.filename"></h3>
                <p class="opacity-50 mt-1 font-mono text-theme-text text-xs truncate" x-text="activeFile?.file_type"></p>
                <div class="flex flex-wrap items-center gap-2 opacity-70 mt-2 font-mono text-[10px] text-theme-text">
                  <template x-if="activeFile?.metadata?.width && activeFile?.metadata?.height">
                    <span class="bg-theme-bg px-1.5 py-0.5 border border-theme-border rounded" x-text="activeFile?.metadata?.width + ' × ' + activeFile?.metadata?.height + ' px'"></span>
                  </template>
                  <span class="bg-theme-bg px-1.5 py-0.5 border border-theme-border rounded" x-text="formatSize(activeFile?.file_size)"></span>
                  <span class="bg-theme-bg px-1.5 py-0.5 border border-theme-border rounded" x-text="activeFile?.uploaded_at ? activeFile.uploaded_at.substring(0, 16) : ''"></span>
                </div>
              </div>
              <button @click="detailModalOpen = false" class="flex justify-center items-center opacity-40 hover:opacity-100 p-2 text-theme-text"
                aria-label="<?= h(_t('close_menu')) ?>"><svg class="w-6 h-6" fill="none" stroke="currentColor"
                  viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
                </svg></button>
            </div>

            <div class="flex-1 space-y-4 md:space-y-6 p-4 md:p-6 overflow-y-auto">
              <!-- Public URL -->
              <div>
                <label class="block opacity-70 mb-1 font-bold text-theme-text text-xs">
                  <?= _t('lbl_public_url') ?>
                </label>
                <div class="flex shadow-theme">
                  <input type="text" readonly :value="activeFile?.url"
                    class="flex-1 bg-theme-bg rounded-r-none font-mono text-[10px] select-all form-control-sm"
                    @click="$event.target.select()">
                  <button @click="copyUrl(activeFile.url)" class="px-3 py-1 border-l-0 rounded-l-none btn-secondary"
                    title="<?= h(_t('btn_copy')) ?>" aria-label="<?= h(_t('btn_copy')) ?>"><svg class="w-4 h-4"
                      fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-clipboard-document"></use>
                    </svg></button>
                </div>
              </div>

              <!-- Tags -->
              <div>
                <label class="block opacity-70 mb-1 font-bold text-theme-text text-xs">
                  <?= _t('lbl_tags') ?>
                </label>
                <div
                  class="flex flex-wrap items-center gap-1.5 bg-theme-bg p-1.5 border border-theme-border focus-within:ring-1 focus-within:ring-theme-primary w-full h-auto min-h-[38px] cursor-text form-control-sm"
                  @click="$refs.tagInput.focus()">
                  <template x-for="(tag, index) in metaForm.tags" :key="index">
                    <span
                      class="flex items-center gap-1 bg-theme-primary/20 px-2 py-0.5 border border-theme-primary/30 rounded-theme text-theme-primary text-xs">
                      <span x-text="tag"></span>
                      <button type="button" @click.stop="removeTag(index)"
                        class="-m-1 p-1 focus:outline-none hover:text-theme-danger"
                        aria-label="<?= h(_t('remove')) ?>">&times;</button>
                    </span>
                  </template>
                  <div class="relative flex-1 min-w-[60px]">
                    <input type="text" x-ref="tagInput" x-model="tagInput"
                      @input="filterTagSuggestions(); showTagSuggestions = true"
                      @keydown.enter.prevent="if(!$event.isComposing) addTag()"
                      @keydown.backspace="if(tagInput === '' && metaForm.tags.length > 0) removeTag(metaForm.tags.length - 1)"
                      @focus="fetchSuggestions().then(() => { filterTagSuggestions(); showTagSuggestions = true; })"
                      @click.stop="showTagSuggestions = true" @click.outside="showTagSuggestions = false"
                      class="bg-transparent p-0 border-none outline-none w-full h-6 text-theme-text text-xs placeholder-theme-text/40"
                      placeholder="<?= _t('add_tag') ?>">
                    <div x-show="showTagSuggestions && tagSuggestions.length > 0"
                      class="top-full left-0 z-50 absolute bg-theme-surface shadow-theme mt-1 border border-theme-border rounded-theme w-48 max-h-40 overflow-y-auto">
                      <template x-for="suggestion in tagSuggestions">
                        <button type="button" @click="addTag(suggestion)"
                          class="block hover:bg-theme-bg px-3 py-2 w-full text-theme-text text-xs text-left transition-colors"
                          x-text="suggestion"></button>
                      </template>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Metadata Edit Form -->
              <div class="space-y-4">
                <div x-show="activeFile?.is_image">
                  <label class="block opacity-70 mb-1 font-bold text-theme-text text-xs"><?= _t('lbl_alt_text') ?></label>
                  <input type="text" x-model="metaForm.alt" class="w-full form-control-sm">
                </div>
                <div>
                  <label class="block opacity-70 mb-1 font-bold text-theme-text text-xs"><?= _t('lbl_title') ?></label>
                  <input type="text" x-model="metaForm.title" class="w-full form-control-sm">
                </div>
                <hr class="my-4 border-theme-border">
                <div>
                  <label class="block opacity-70 mb-1 font-bold text-theme-text text-xs">
                    <?= _t('lbl_license_status') ?>
                  </label>
                  <select x-model="metaForm.license" class="w-full form-control-sm">
                    <option value="unknown">
                      <?= _t('opt_unknown') ?>
                    </option>
                    <option value="owned">
                      <?= _t('opt_owned') ?>
                    </option>
                    <option value="free">
                      <?= _t('opt_free') ?>
                    </option>
                  </select>
                </div>
                <div>
                  <label class="block opacity-70 mb-1 font-bold text-theme-text text-xs"><?= _t('lbl_license_url') ?></label>
                  <input type="url" x-model="metaForm.license_url" class="w-full form-control-sm" placeholder="https://creativecommons.org/...">
                </div>
                <div>
                  <label class="block opacity-70 mb-1 font-bold text-theme-text text-xs"><?= _t('lbl_acquire_license_page') ?></label>
                  <input type="url" x-model="metaForm.acquire_license_page" class="w-full form-control-sm" placeholder="https://yoursite.com/contact">
                </div>
                <div>
                  <label class="block opacity-70 mb-1 font-bold text-theme-text text-xs">
                    <?= _t('lbl_credit') ?>
                  </label>
                  <input type="text" x-model="metaForm.credit" class="w-full form-control-sm">
                </div>

                <div class="pt-4 border-theme-border border-t">
                  <div class="flex justify-between items-center mb-2">
                    <span class="opacity-70 font-bold text-theme-text text-xs">
                      <?= _t('lbl_ai_generation') ?>
                    </span>
                    <label class="flex items-center cursor-pointer"><input type="checkbox" x-model="metaForm.is_ai"
                        class="bg-theme-bg mr-2 rounded w-5 h-5 text-theme-primary form-checkbox"><span
                        class="font-bold text-theme-primary text-xs">
                        <?= _t('lbl_ai_generated') ?>
                      </span></label>
                  </div>
                  <div x-show="metaForm.is_ai" x-collapse>
                    <textarea x-model="metaForm.prompt" rows="4"
                      class="bg-theme-bg border-theme-border w-full font-mono text-[10px] text-theme-text leading-relaxed form-control-sm"
                      placeholder="<?= _t('ph_prompt') ?>"></textarea>
                  </div>
                </div>

                <!-- Usage Info -->
                <div class="pt-4 border-theme-border border-t">
                  <div class="flex justify-between items-center mb-2">
                    <span class="opacity-70 font-bold text-theme-text text-xs">
                      <?= _t('lbl_media_usage') ?? 'Used In' ?>
                    </span>
                    <div x-show="isFetchingUsage">
                      <svg class="w-4 h-4 text-theme-primary animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                      </svg>
                    </div>
                  </div>
                  <div x-show="!isFetchingUsage && fileUsageList.length === 0" class="opacity-50 text-[10px] text-theme-text italic">
                    <?= _t('msg_media_not_used') ?? 'Not currently in use.' ?>
                  </div>
                  <ul x-show="!isFetchingUsage && fileUsageList.length > 0" class="space-y-1.5 mt-2 pr-1 max-h-32 overflow-y-auto custom-scrollbar">
                    <template x-for="usage in fileUsageList" :key="usage.type + '-' + usage.id">
                      <li class="flex items-center gap-2 bg-theme-surface shadow-sm px-2 py-1.5 border border-theme-border rounded-theme text-[10px] text-theme-text">
                        <span class="opacity-80 min-w-10 font-bold text-theme-primary text-center uppercase shrink-0" x-text="usage.type"></span>
                        <div class="bg-theme-border w-px h-3 shrink-0"></div>
                        <template x-if="usage.edit_url">
                          <a :href="usage.edit_url" target="_blank" class="flex-1 font-mono font-medium hover:text-theme-primary truncate transition-colors" x-text="usage.title" :title="usage.title"></a>
                        </template>
                        <template x-if="!usage.edit_url">
                          <span class="flex-1 font-mono font-medium truncate" x-text="usage.title" :title="usage.title"></span>
                        </template>
                        <template x-if="usage.edit_url">
                          <a :href="usage.edit_url" target="_blank" class="opacity-40 hover:opacity-100 text-theme-text hover:text-theme-primary transition-colors shrink-0" aria-label="<?= h(_t('edit')) ?>">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-top-right-on-square"></use>
                            </svg>
                          </a>
                        </template>
                      </li>
                    </template>
                  </ul>
                </div>

              </div>
            </div>

            <div class="flex justify-between items-center bg-theme-bg/30 p-4 md:p-6 border-theme-border border-t">
              <button @click="deleteFile(activeFile)"
                class="flex items-center gap-1 font-bold text-theme-danger text-xs hover:underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                </svg>
                <?= _t('delete') ?>
              </button>
              <button @click="saveMetadata()" class="shadow-theme px-6 py-2.5 rounded-theme text-xs btn-primary">
                <?= _t('btn_save_changes') ?>
              </button>
            </div>
          </div>
        </div>

        <button @click="nextFile()"
          class="hidden md:flex justify-center items-center opacity-80 hover:opacity-100 p-4 rounded-full pointer-events-auto btn-secondary shrink-0"
          title="<?= h(_t('next')) ?>" aria-label="<?= h(_t('next')) ?>">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-right"></use>
          </svg>
        </button>
      </div>

    </div>
  </template>

</div>
