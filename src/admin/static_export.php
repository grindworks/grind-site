<?php

/**
 * static_export.php
 *
 * Static Site Generator interface.
 */
require_once __DIR__ . '/bootstrap.php';

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) {
  exit;
}

$page_title = _t('ssg_title');
$current_page = 'static_export';

// Check zip extension
$hasZip = class_exists('ZipArchive');

// Get config
$lastExport = get_option('last_ssg_export');
$lastExportDisplay = $lastExport ? date('Y-m-d H:i:s', strtotime($lastExport)) : _t('ssg_never');
$savedBaseUrl = get_option('ssg_base_url', '');
$savedFormEndpoint = get_option('ssg_form_endpoint', '');
$savedMaxResults = get_option('ssg_max_results', 1000);
$savedSearchScope = get_option('ssg_search_scope', 'title_body');
$savedSearchChunkSize = get_option('ssg_search_chunk_size', 500);

// JS translations
$jsTrans = [
  'btn_start'      => _t('ssg_btn_start'),
  'btn_generating' => _t('ssg_btn_generating'),
  'status_init'    => _t('ssg_status_init'),
  'status_pages'   => _t('ssg_status_pages'),
  'status_assets'  => _t('ssg_status_assets'),
  'status_zip'     => _t('ssg_status_zip'),
  'status_done'    => _t('ssg_status_done'),
  'err_failed'     => _t('js_error'),
];

// 404 notice
$msg404 = _t('ssg_msg_404_setup');
ob_start();
?>

<div class="space-y-8" x-data="staticExporter()">

  <!-- Page header -->
  <div class="flex md:flex-row flex-col justify-between md:items-center gap-4">
    <div>
      <h2 class="flex items-center gap-2 font-bold text-theme-text text-xl">
        <svg class="w-6 h-6 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-cube-transparent"></use>
        </svg>
        <?= _t('ssg_title') ?>
      </h2>
      <p class="opacity-60 mt-1 ml-8 text-theme-text text-sm">
        <?= _t('ssg_desc') ?>
      </p>
    </div>
  </div>

  <?php if (!$hasZip): ?>
    <div class="flex items-start gap-4 bg-theme-danger/10 p-6 border border-theme-danger/20 rounded-theme text-theme-danger">
      <svg class="mt-0.5 w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
      </svg>
      <div>
        <h3 class="mb-1 font-bold text-lg"><?= _t('ssg_err_zip_title') ?></h3>
        <p class="opacity-90"><?= _t('ssg_err_zip') ?></p>
      </div>
    </div>
  <?php else: ?>

    <!-- Info section -->
    <div class="bg-theme-surface shadow-theme p-8 border border-theme-border rounded-theme">
      <div class="gap-10 grid grid-cols-1 lg:grid-cols-2">

        <!-- Description -->
        <div>
          <h3 class="flex items-center gap-2 mb-4 font-bold text-theme-text text-lg">
            <svg class="w-5 h-5 text-theme-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-information-circle"></use>
            </svg>
            <?= _t('ssg_about_title') ?>
          </h3>
          <p class="opacity-80 mb-8 text-theme-text text-sm leading-relaxed">
            <?= _t('ssg_intro') ?>
          </p>

          <div class="gap-4 grid grid-cols-3">
            <div class="bg-theme-bg p-3 border border-theme-border rounded-theme text-center">
              <div class="flex justify-center mb-2 text-theme-success">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-bolt"></use>
                </svg>
              </div>
              <p class="font-bold text-theme-text text-xs"><?= _t('ssg_feat_fast') ?></p>
            </div>
            <div class="bg-theme-bg p-3 border border-theme-border rounded-theme text-center">
              <div class="flex justify-center mb-2 text-theme-success">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check"></use>
                </svg>
              </div>
              <p class="font-bold text-theme-text text-xs"><?= _t('ssg_feat_portable') ?></p>
            </div>
            <div class="bg-theme-bg p-3 border border-theme-border rounded-theme text-center">
              <div class="flex justify-center mb-2 text-theme-success">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-circle-stack"></use>
                </svg>
              </div>
              <p class="font-bold text-theme-text text-xs"><?= _t('ssg_feat_nodb') ?></p>
            </div>
          </div>
        </div>

        <!-- Limitations -->
        <div class="bg-theme-warning/5 p-6 border border-theme-warning/20 rounded-theme">
          <h4 class="flex items-center gap-2 mb-4 font-bold text-theme-warning">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
            </svg>
            <?= _t('ssg_limit_title') ?>
          </h4>
          <ul class="space-y-4">
            <li class="flex gap-3 opacity-90 text-theme-text text-sm">
              <span class="font-bold text-theme-warning shrink-0">•</span>
              <span><?= _t('ssg_limit_search') ?></span>
            </li>
            <li class="flex gap-3 opacity-90 text-theme-text text-sm">
              <span class="font-bold text-theme-warning shrink-0">•</span>
              <span><?= _t('ssg_limit_form') ?></span>
            </li>
            <li class="flex gap-3 opacity-90 text-theme-text text-sm">
              <span class="font-bold text-theme-warning shrink-0">•</span>
              <span><?= _t('ssg_limit_link') ?></span>
            </li>
            <li class="flex gap-3 opacity-90 text-theme-text text-sm">
              <span class="font-bold text-theme-warning shrink-0">•</span>
              <span><?= _t('ssg_limit_js_img') ?></span>
            </li>
            <li class="flex gap-3 opacity-90 text-theme-text text-sm">
              <span class="font-bold text-theme-warning shrink-0">•</span>
              <span><?= _t('ssg_limit_warning') ?></span>
            </li>
          </ul>
        </div>

      </div>
    </div>

    <!-- Configuration -->
    <div class="gap-6 grid grid-cols-1 lg:grid-cols-2">

      <!-- Basic settings -->
      <div class="bg-theme-surface shadow-theme p-6 border border-theme-border rounded-theme">
        <h3 class="flex items-center gap-2 mb-6 font-bold text-theme-text text-lg">
          <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-adjustments-horizontal"></use>
          </svg>
          <?= _t('ssg_config_title') ?>
        </h3>

        <div class="space-y-6">
          <div>
            <label class="block mb-2 font-bold text-theme-text text-sm">
              <?= _t('ssg_label_url') ?>
            </label>
            <div class="relative">
              <span class="top-1/2 left-3 absolute opacity-40 text-theme-text -translate-y-1/2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-link"></use>
                </svg>
              </span>
              <input type="url" x-model="config.baseUrl" class="pl-10 form-control" placeholder="https://example.com">
            </div>
            <p class="opacity-60 mt-2 ml-1 text-theme-text text-xs">
              <?= _t('ssg_desc_url') ?>
            </p>
          </div>

          <div>
            <label class="block mb-2 font-bold text-theme-text text-sm">
              <?= _t('ssg_label_form') ?>
            </label>
            <div class="relative">
              <span class="top-1/2 left-3 absolute opacity-40 text-theme-text -translate-y-1/2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-envelope"></use>
                </svg>
              </span>
              <input type="url" x-model="config.formEndpoint" class="pl-10 form-control" placeholder="https://formspree.io/f/...">
            </div>
            <p class="opacity-60 mt-1 ml-1 text-theme-text text-xs">
              <?= _t('ssg_desc_form') ?>
            </p>
          </div>

          <div>
            <label class="block mb-2 font-bold text-theme-text text-sm">
              <?= _t('ssg_label_search_scope') ?>
            </label>
            <select x-model="config.searchScope" class="form-control">
              <option value="title_body"><?= _t('ssg_scope_title_body') ?></option>
              <option value="title_only"><?= _t('ssg_scope_title_only') ?></option>
            </select>
            <p class="opacity-60 mt-1 ml-1 text-theme-text text-xs">
              <?= _t('ssg_desc_search_scope') ?>
            </p>
          </div>

          <div>
            <label class="block mb-2 font-bold text-theme-text text-sm">
              <?= _t('ssg_label_max_results') ?>
            </label>
            <input type="number" x-model="config.maxResults" class="form-control" placeholder="1000">
            <p class="opacity-60 mt-1 ml-1 text-theme-text text-xs">
              <?= _t('ssg_desc_max_results') ?>
            </p>
          </div>

          <div>
            <label class="block mb-2 font-bold text-theme-text text-sm">
              <?= _t('ssg_label_chunk_size') ?>
            </label>
            <input type="number" x-model="config.searchChunkSize" class="form-control" placeholder="500">
            <p class="opacity-60 mt-1 ml-1 text-theme-text text-xs">
              <?= _t('ssg_desc_chunk_size') ?>
            </p>
          </div>
        </div>
      </div>

      <!-- Export mode -->
      <div class="flex flex-col bg-theme-surface shadow-theme p-6 border border-theme-border rounded-theme">
        <h3 class="flex items-center gap-2 mb-6 font-bold text-theme-text text-lg">
          <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
          </svg>
          <?= _t('ssg_mode_title') ?>
        </h3>

        <div class="flex flex-col flex-1 gap-4">
          <!-- Full export -->
          <label class="relative flex items-start hover:shadow-theme p-4 border-2 rounded-theme transition-all cursor-pointer"
            :class="config.mode === 'full' ? 'border-theme-primary bg-theme-primary/5' : 'border-theme-border bg-theme-bg/30'">
            <div class="flex items-center h-5">
              <input type="radio" name="export_mode" value="full" x-model="config.mode" class="border-theme-border focus:ring-theme-primary w-5 h-5 text-theme-primary form-radio">
            </div>
            <div class="ml-3">
              <span class="block font-bold text-theme-text text-sm" :class="config.mode === 'full' ? 'text-theme-primary' : ''">
                <?= _t('ssg_mode_full') ?>
              </span>
              <span class="block opacity-60 mt-1 text-theme-text text-xs">
                <?= _t('ssg_mode_full_desc') ?>
              </span>
            </div>
          </label>

          <!-- Diff export -->
          <label class="relative flex items-start hover:shadow-theme p-4 border-2 rounded-theme transition-all"
            :class="[
              config.mode === 'diff' ? 'border-theme-primary bg-theme-primary/5' : 'border-theme-border bg-theme-bg/30',
              <?= $lastExport ? 'false' : 'true' ?> ? 'opacity-50 cursor-not-allowed bg-theme-bg' : 'cursor-pointer'
            ]">
            <div class="flex items-center h-5">
              <input type="radio" name="export_mode" value="diff" x-model="config.mode" class="border-theme-border focus:ring-theme-primary w-5 h-5 text-theme-primary form-radio" <?= $lastExport ? '' : 'disabled' ?>>
            </div>
            <div class="ml-3">
              <div class="flex items-center gap-2">
                <span class="block font-bold text-theme-text text-sm" :class="config.mode === 'diff' ? 'text-theme-primary' : ''">
                  <?= _t('ssg_mode_diff') ?>
                </span>
                <?php if ($lastExport): ?>
                  <span class="bg-theme-success/10 px-1.5 py-0.5 border border-theme-success/20 rounded font-bold text-[10px] text-theme-success"><?= _t('ready') ?></span>
                <?php else: ?>
                  <span class="bg-theme-bg opacity-70 px-1.5 py-0.5 border border-theme-border rounded-theme text-[10px]"><?= _t('ssg_first_run') ?></span>
                <?php endif; ?>
              </div>
              <span class="block opacity-60 mt-1 text-theme-text text-xs">
                <?php if ($lastExport): ?>
                  <?php $lastExportHtml = '<span class="bg-theme-bg px-1 rounded-theme font-mono">' . h($lastExportDisplay) . '</span>'; ?>
                  <?= _t('ssg_mode_diff_desc', $lastExportHtml) ?>
                <?php else: ?>
                  * <?= _t('msg_rebuild_required') ?>
                <?php endif; ?>
              </span>
            </div>
          </label>
        </div>

        <div class="mt-6 pt-6 border-theme-border border-t">
          <div class="flex justify-between items-center">
            <div class="opacity-60 pr-4 text-theme-text text-xs">
              <template x-if="config.mode === 'diff'">
                <span class="flex items-center gap-1">
                  <svg class="w-3 h-3 text-theme-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-information-circle"></use>
                  </svg>
                  <?= _t('ssg_diff_notice') ?>
                </span>
              </template>
            </div>
            <button @click="startExport()" :disabled="processing" class="group flex items-center gap-2 disabled:opacity-50 shadow-theme px-6 py-2.5 font-bold transition-all disabled:cursor-not-allowed btn-primary shrink-0">
              <span class="relative flex w-3 h-3" x-show="processing">
                <svg x-show="processing" x-cloak class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                </svg>
              </span>
              <svg x-show="!processing" class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-play-circle"></use>
              </svg>
              <span x-text="processing ? trans.btn_generating : trans.btn_start"></span>
            </button>
          </div>
        </div>
      </div>

    </div>

  <?php endif; ?>

  <!-- Processing modal -->
  <template x-teleport="body">
    <div x-show="processing" class="z-50 fixed inset-0 flex justify-center items-center p-4" style="display: none;" x-cloak>
      <div class="fixed inset-0 skin-modal-overlay backdrop-blur-sm transition-opacity"></div>
      <div class="z-10 relative bg-theme-surface shadow-theme p-8 border border-theme-border rounded-theme w-full max-w-md text-center max-h-[90vh] overflow-y-auto custom-scrollbar scale-100 transition-all transform">

        <!-- Stack icon -->
        <div class="flex flex-col justify-center items-center gap-2 mb-6">
          <svg class="opacity-50 w-10 h-10 text-theme-primary animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
          </svg>
          <span class="font-mono font-bold text-theme-primary text-xl" x-text="progress + '%'"></span>
        </div>
        <!-- End stack -->

        <h3 class="mb-2 font-bold text-theme-text text-xl" x-text="trans.btn_generating"></h3>
        <p class="bg-theme-bg opacity-60 mb-6 py-2 border border-theme-border rounded-theme font-mono text-theme-text text-sm" x-text="statusMsg"></p>

        <div class="bg-theme-bg mb-2 border border-theme-border rounded-full w-full h-2 overflow-hidden">
          <div class="relative bg-theme-primary rounded-full h-2 overflow-hidden transition-all duration-300" :style="'width: ' + progress + '%'">
            <div class="absolute inset-0 bg-white/20 animate-[shimmer_2s_infinite]"></div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <!-- Success modal -->
  <template x-teleport="body">
    <div x-show="downloadUrl" class="z-50 fixed inset-0 flex justify-center items-center p-4" style="display: none;" x-cloak>
      <div class="fixed inset-0 skin-modal-overlay backdrop-blur-sm transition-opacity" @click="closeModal()"></div>
      <div class="z-10 relative bg-theme-surface shadow-theme p-8 border border-theme-border rounded-theme w-full max-w-md text-center max-h-[90vh] overflow-y-auto custom-scrollbar transition-all transform">
        <div class="flex justify-center items-center bg-theme-success/10 mx-auto mb-6 rounded-full ring-8 ring-theme-success/5 w-16 h-16 text-theme-success animate-bounce">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check"></use>
          </svg>
        </div>
        <h3 class="mb-2 font-bold text-theme-text text-2xl"><?= _t('ssg_modal_title') ?></h3>
        <p class="opacity-60 mb-8 text-theme-text text-sm leading-relaxed">
          <?= _t('ssg_modal_desc') ?>
        </p>

        <div class="bg-theme-info/10 opacity-90 mb-6 p-4 border border-theme-info/20 rounded-theme text-theme-text text-xs text-left leading-relaxed">
          <div class="flex items-start gap-2 mb-3 font-bold">
            <svg class="mt-0.5 w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-information-circle"></use>
            </svg>
            <div><?= $msg404 ?></div>
          </div>
          <div class="gap-2 grid grid-cols-1 sm:grid-cols-2">
            <div class="bg-theme-bg/50 p-2 border border-theme-info/10 rounded-theme">
              <span class="block opacity-70 mb-1 font-bold text-[10px]">Apache (.htaccess)</span>
              <code class="block bg-theme-surface px-1 rounded-theme font-mono text-[10px] select-all">ErrorDocument 404 /404.html</code>
            </div>
            <div class="bg-theme-bg/50 p-2 border border-theme-info/10 rounded-theme">
              <span class="block opacity-70 mb-1 font-bold text-[10px]">Nginx (nginx.conf)</span>
              <code class="block bg-theme-surface px-1 rounded-theme font-mono text-[10px] select-all">error_page 404 /404.html;</code>
            </div>
          </div>
        </div>

        <button type="button" @click="executeDownload()" :disabled="isDownloading" class="flex justify-center items-center gap-2 shadow-theme mb-4 py-4 rounded-theme w-full font-bold text-center transition-transform btn-primary disabled:opacity-70 disabled:cursor-not-allowed" :class="isDownloading ? 'scale-100' : 'hover:scale-[1.02]'">
          <svg x-show="!isDownloading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-down-tray"></use>
          </svg>
          <svg x-show="isDownloading" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;" x-cloak>
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
          </svg>
          <span x-text="isDownloading ? 'Downloading...' : <?= htmlspecialchars(json_encode(_t('ssg_btn_download'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>"></span>
        </button>
        <button @click="closeModal()" class="opacity-40 hover:opacity-100 font-bold text-theme-text text-sm hover:underline transition-all"><?= _t('ssg_btn_close') ?></button>
      </div>
    </div>
  </template>

</div>

<script>
  document.addEventListener('alpine:init', () => {
    Alpine.data('staticExporter', () => ({
      processing: false,
      progress: 0,
      statusMsg: '',
      downloadUrl: null,
      buildId: null,
      isDownloading: false,
      config: {
        baseUrl: <?= json_encode($savedBaseUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        formEndpoint: <?= json_encode($savedFormEndpoint, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        mode: <?= json_encode(Routing::getString($_GET, 'mode', ($lastExport ? 'diff' : 'full')), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        searchScope: <?= json_encode($savedSearchScope, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        maxResults: <?= (int)$savedMaxResults ?>,
        searchChunkSize: <?= (int)$savedSearchChunkSize ?>
      },
      // Load translations
      trans: <?= json_encode($jsTrans, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,

      async startExport() {
        if (this.processing) return;

        // Reset states
        this.processing = true;
        this.progress = 0;
        this.downloadUrl = null;
        this.buildId = null;
        this.statusMsg = this.trans.status_init;

        try {
          // Init export
          const initRes = await this.callApi('init', {
            baseUrl: this.config.baseUrl,
            formEndpoint: this.config.formEndpoint,
            mode: this.config.mode,
            searchScope: this.config.searchScope,
            maxResults: this.config.maxResults,
            searchChunkSize: this.config.searchChunkSize
          });
          const pages = initRes.pages;
          this.buildId = initRes.build_id || initRes.buildId;
          const total = pages.length;

          if (total === 0) {
            // Handle empty update
            this.progress = 50;
          }

          // Generate pages
          let current = 0;
          // Reduce batch size to prevent FastCGI timeouts on heavy pages
          const batchSize = 5;

          if (total > 0) {
            for (let i = 0; i < total; i += batchSize) {
              const batch = pages.slice(i, i + batchSize);

              this.statusMsg = this.trans.status_pages
                .replace('%d', Math.min(current + batch.length, total))
                .replace('%d', total);

              this.progress = Math.min(80, Math.round((current / total) * 80));

              await this.callApi('generate_pages', {
                pages: batch
              });

              current += batch.length;
            }
          }

          // Scan & Copy assets
          this.statusMsg = this.trans.status_assets;
          await this.callApi('scan_assets');

          let assetOffset = 0;
          let assetDone = false;
          while (!assetDone) {
            const copyRes = await this.callApi('copy_assets', {
              offset: assetOffset,
              limit: 100
            });
            assetOffset = copyRes.next_offset;
            assetDone = copyRes.done;
          }

          // Generate assets
          this.statusMsg = this.trans.status_assets;
          this.progress = 85;

          let genAssetsDone = false;
          let searchOffset = 0;
          let chunkIndex = 0;
          let manifest = {
            files: []
          };

          while (!genAssetsDone) {
            const genRes = await this.callApi('generate_assets', {
              mode: this.config.mode,
              search_offset: searchOffset,
              chunk_index: chunkIndex,
              manifest: manifest
            });

            if (genRes.in_progress) {
              searchOffset = genRes.search_offset;
              chunkIndex = genRes.chunk_index;
              manifest = genRes.manifest;
              // Increment progress bar slightly
              this.progress = Math.min(94, this.progress + 1);
            } else {
              genAssetsDone = true;
            }
          }
          this.progress = 95;

          // Finalize zip
          this.statusMsg = this.trans.status_zip;
          const zipRes = await this.callApi('finalize');

          this.progress = 100;
          this.statusMsg = this.trans.status_done;

          // Delay for UX
          setTimeout(() => {
            this.processing = false;
            this.downloadUrl = zipRes.url;
          }, 500);

        } catch (e) {
          alert(this.trans.err_failed.replace('%s', e.message));
          this.processing = false;
        }
      },

      executeDownload() {
        if (!this.downloadUrl || this.isDownloading) return;

        this.isDownloading = true;

        const urlObj = new URL(this.downloadUrl, window.location.href);
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = urlObj.pathname;
        form.style.display = 'none';

        urlObj.searchParams.forEach((value, key) => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = key;
          input.value = value;
          form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        setTimeout(() => {
          this.downloadUrl = null;
          this.isDownloading = false;
        }, 3000);
      },

      async closeModal() {
        this.downloadUrl = null;
        if (this.buildId) {
          try {
            // Send cleanup request in the background (fire-and-forget)
            const formData = new FormData();
            formData.append('csrf_token', <?= json_encode(generate_csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
            formData.append('build_id', this.buildId);
            formData.append('action', 'cleanup');

            fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/api/ssg_process.php', {
              method: 'POST',
              body: formData,
              keepalive: true
            });
          } catch (e) {}
        }
      },

      async callApi(step, data = {}) {
        const formData = new FormData();
        formData.append('csrf_token', <?= json_encode(generate_csrf_token(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
        formData.append('step', step);

        // Attach build ID
        if (this.buildId) {
          data.build_id = this.buildId;
        }

        formData.append('data', JSON.stringify(data));

        const res = await fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/api/ssg_process.php', {
          method: 'POST',
          body: formData
        });

        if (res.status === 401) {
          alert(<?= json_encode(_t('err_session_expired')) ?>);
          window.location.href = 'login.php?redirect_to=' + encodeURIComponent(window.location.href);
          throw new Error('Session expired');
        }

        if (!res.ok) throw new Error(`Server Error: ${res.status}`);

        const text = await res.text();
        let json;
        try {
          json = JSON.parse(text);
        } catch (e) {
          console.error("Invalid JSON Response:", text);
          throw new Error("Invalid server response.");
        }

        if (!json.success) throw new Error(json.error);
        return json;
      }
    }));
  });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout/loader.php';
?>
