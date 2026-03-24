<?php
ob_start();

/**
 * check_unused_uploads.php
 *
 * Scan unused uploaded files.
 */
require_once __DIR__ . '/bootstrap.php';

// Relax resource limits
if (function_exists('grinds_set_high_load_mode')) {
  grinds_set_high_load_mode();
}

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) {
  exit;
}

// Check permissions
if (!current_user_can('manage_tools')) {
  if (isset($_GET['action'])) {
    json_response(['success' => false, 'error' => 'Access Denied'], 403);
  }
  redirect('admin/index.php');
}

$params = Routing::getParams();

// Merge POST action for API calls
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $params['action'] = Routing::getString($_POST, 'action');
}

// Handle API request
if (isset($params['action'])) {
  ini_set('display_errors', 0);
  error_reporting(0);
  $action = $params['action'];

  try {
    // ---------------------------------------------------------
    // Scan database
    // ---------------------------------------------------------
    if ($action === 'scan_db_batch') {
      $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
      $limit  = isset($params['limit']) ? (int)$params['limit'] : 100;

      $result = FileManager::scanDatabaseForFiles($pdo, $offset, $limit);

      json_response(array_merge(['success' => true], $result));
    }

    // ---------------------------------------------------------
    // Scan theme files
    // ---------------------------------------------------------
    if ($action === 'scan_theme_files') {
      $themeDir = ROOT_PATH . '/theme';
      $used_files = [];

      if (is_dir($themeDir)) {
        $files = FileManager::scanDirectory($themeDir, [
          'include_exts' => ['php', 'css', 'json', 'html'],
          'exclude_dirs' => ['node_modules', 'vendor', '.git', 'dist', 'build']
        ]);
        foreach ($files as $filePath) {
          // メモリ枯渇やCPUスパイクを防ぐため、1MB（1048576バイト）を超える巨大ファイルはスキップ
          if (@filesize($filePath) <= 1048576) {
            $content = @file_get_contents($filePath);
            if ($content) {
              FileManager::extractPathsFromContent($content, $used_files);
            }
          }
        }
      }

      // Scan admin skins
      $skinsDir = ROOT_PATH . '/admin/skins';
      if (is_dir($skinsDir)) {
        $files = FileManager::scanDirectory($skinsDir, [
          'include_exts' => ['json']
        ]);
        foreach ($files as $filePath) {
          // メモリ枯渇やCPUスパイクを防ぐため、1MB（1048576バイト）を超える巨大ファイルはスキップ
          if (@filesize($filePath) <= 1048576) {
            $content = @file_get_contents($filePath);
            if ($content) {
              FileManager::extractPathsFromContent($content, $used_files);
            }
          }
        }
      }

      json_response([
        'success' => true,
        'files' => array_values(array_unique(array_filter($used_files)))
      ]);
    }

    // ---------------------------------------------------------
    // Get local files
    // ---------------------------------------------------------
    if ($action === 'get_local_files') {
      $upload_dir = ROOT_PATH . '/assets/uploads';
      $files = [];

      if (is_dir($upload_dir)) {
        $foundFiles = FileManager::scanDirectory($upload_dir, [
          'exclude_files' => ['.htaccess'],
          'exclude_dirs' => ['node_modules', 'vendor', '.git', '_trash']
        ]);
        $rootPathStr = str_replace('\\', '/', ROOT_PATH);

        foreach ($foundFiles as $filePath) {
          $relPath = $filePath;
          if (stripos($filePath, $rootPathStr) === 0) {
            $relPath = substr($filePath, strlen($rootPathStr));
          }
          $relPath = ltrim($relPath, '/');
          $files[] = [
            'path' => $relPath,
            'size' => filesize($filePath),
            'date' => date('Y-m-d H:i', filemtime($filePath)),
          ];
        }
      }
      json_response(['success' => true, 'files' => $files]);
    }

    // ---------------------------------------------------------
    // Delete files
    // ---------------------------------------------------------
    if ($action === 'delete_files') {
      $files = json_decode(Routing::getString($_POST, 'files', '[]'), true);
      if (!is_array($files)) {
        $files = [];
      }
      $count = 0;
      $uploadsDir = realpath(ROOT_PATH . '/assets/uploads');
      $trashDir = $uploadsDir . '/_trash';

      if (!is_dir($trashDir)) {
        mkdir($trashDir, 0775, true);
        $htaccessRules = "Require all denied\n";
        file_put_contents($trashDir . '/.htaccess', $htaccessRules);
      }

      foreach ($files as $relPath) {
        $relPath = str_replace('\\', '/', $relPath);
        $relPath = preg_replace('/\.+[\/\\\\]+/', '', $relPath);
        $fullPath = ROOT_PATH . '/' . ltrim($relPath, '/');
        $realUploadsDir = rtrim($uploadsDir, '/\\') . DIRECTORY_SEPARATOR;
        $rp = realpath($fullPath);
        if ($rp && file_exists($fullPath) && stripos($rp, $realUploadsDir) === 0) {
          $flatName = str_replace(['/', '\\'], '_', str_replace('assets/uploads/', '', $relPath));
          $trashPath = $trashDir . '/' . $flatName;

          if (file_exists($trashPath)) $trashPath = $trashDir . '/' . time() . '_' . $flatName;

          if (@rename($fullPath, $trashPath)) {
            $count++;

            // Move derivative files (WebP, thumbnails) to trash as well
            $derivatives = FileManager::getDerivativePaths($fullPath);
            foreach ($derivatives as $derivPath) {
              if (file_exists($derivPath)) {
                $derivRelPath = ltrim(str_replace(ROOT_PATH, '', $derivPath), '/\\');
                $derivFlatName = str_replace(['/', '\\'], '_', str_replace('assets/uploads/', '', $derivRelPath));
                $derivTrashPath = $trashDir . '/' . $derivFlatName;
                if (file_exists($derivTrashPath)) $derivTrashPath = $trashDir . '/' . time() . '_' . $derivFlatName;
                @rename($derivPath, $derivTrashPath);
              }
            }

            // Remove from media table to prevent zombie records
            $stmtDelMedia = $pdo->prepare("DELETE FROM media WHERE filepath = ?");
            $stmtDelMedia->execute([ltrim($relPath, '/')]);
          }
        }
      }

      if ($count > 0) {
        grinds_clear_media_cache();
      }
      json_response(['success' => true, 'count' => $count]);
    }

    json_response(['success' => false, 'error' => 'Invalid action'], 400);
  } catch (Exception $e) {
    json_response(['success' => false, 'error' => $e->getMessage()], 500);
  }
}

// Render view
$page_title = _t('mt_unused_uploads');
$current_page = 'unused_uploads';
?>
<div class="space-y-6" x-data="unusedCheckerWithMeta(unusedConfig)">

  <!-- Header -->
  <div class="flex sm:flex-row flex-col justify-between sm:items-center gap-4">
    <div>
      <h2 class="flex items-center gap-2 font-bold text-theme-text text-xl">
        <svg class="w-6 h-6 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
        </svg>
        <?= _t('mt_unused_uploads') ?>
      </h2>
      <p class="opacity-60 mt-1 ml-8 text-theme-text text-sm">
        <span x-text="statusMsg || <?= h(json_encode(_t('mt_unused_uploads_desc'))) ?>"></span>
      </p>
    </div>
    <button @click="startScan()" :disabled="scanning" class="flex items-center gap-2 disabled:opacity-70 shadow-theme px-6 py-2.5 rounded-theme font-bold text-sm transition-all disabled:cursor-not-allowed btn-primary">
      <svg x-show="!scanning" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
      </svg>
      <svg x-show="scanning" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
      </svg>
      <span x-text="scanning ? <?= h(json_encode(_t('btn_scanning'))) ?> : (scanned ? <?= h(json_encode(_t('btn_rescan'))) ?> : <?= h(json_encode(_t('btn_scan'))) ?>)"></span>
    </button>
  </div>

  <!-- Main Card -->
  <div class="bg-theme-surface shadow-theme p-6 border border-theme-border rounded-theme">
    <!-- Progress Bar -->
    <div x-show="scanning" class="bg-theme-bg mb-6 rounded-full w-full h-2.5" x-cloak>
      <div class="bg-theme-primary rounded-full h-2.5 transition-all duration-300" :style="'width: ' + progress + '%'"></div>
    </div>

    <!-- Info Box -->
    <div class="bg-theme-danger/10 mb-6 p-4 border border-theme-danger/30 rounded-theme">
      <h4 class="flex items-center gap-2 mb-1 font-bold text-theme-danger text-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
        </svg>
        <?= _t('warning') ?>
      </h4>
      <p class="opacity-90 text-theme-danger text-xs leading-relaxed">
        <?= _t('mt_unused_uploads_caution') ?>
      </p>
      <p class="opacity-90 mt-2 text-theme-danger text-xs leading-relaxed">
        ※ <?= _t('mt_unused_dynamic_note') ?>
      </p>
    </div>

    <!-- Initial State -->
    <div x-show="!scanning && !scanned" class="space-y-6" x-cloak>
      <div class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
        <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
          </svg>
        </div>
        <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('link_checker_init_title') ?></h3>
        <p class="text-sm text-theme-text opacity-60 mt-1"><?= _t('unused_init_desc') ?></p>
      </div>
    </div>

    <!-- Results List -->
    <div x-show="scanned && unusedFiles.length > 0" class="space-y-4" x-cloak>

      <!-- Controls -->
      <div class="flex justify-between items-center gap-4">
        <div class="flex items-center gap-2">
          <button @click="toggleAll()" class="btn-secondary px-3 py-1 text-xs">
            <span x-text="selectedFiles.length === unusedFiles.length ? '<?= _t('lbl_select_all') ?> (OFF)' : '<?= _t('lbl_select_all') ?>'"></span>
          </button>
          <button @click="deleteSelected()" :disabled="selectedFiles.length === 0 || deleting" class="btn-danger px-3 py-1 text-xs disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1">
            <svg x-show="deleting" class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
            </svg>
            <svg x-show="!deleting" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
            </svg>
            <?= _t('btn_delete_selected') ?>
            <span x-show="selectedFiles.length > 0" x-text="'(' + selectedFiles.length + ')'"></span>
          </button>
        </div>

        <select x-model="sortBy" @change="sortFiles()" class="bg-theme-bg border-theme-border rounded-theme text-xs text-theme-text py-1 pl-2 pr-8 focus:ring-theme-primary focus:border-theme-primary">
          <option value="date_desc"><?= _t('sort_date_newest') ?></option>
          <option value="date_asc"><?= _t('sort_date_oldest') ?></option>
          <option value="size_desc"><?= _t('sort_size_largest') ?></option>
          <option value="size_asc"><?= _t('sort_size_smallest') ?></option>
          <option value="name_asc"><?= _t('sort_name_asc') ?></option>
        </select>
      </div>

      <!-- List Header -->
      <div class="flex justify-between items-center bg-theme-bg p-3 border border-theme-border rounded-theme">
        <span class="opacity-70 font-bold text-theme-text text-xs">
          <?= str_replace('%s', '<span x-text="unusedFiles.length"></span>', _t('mt_files_found_count')) ?>
        </span>
      </div>

      <div class="bg-theme-bg p-4 border border-theme-border rounded-theme h-96 overflow-hidden overflow-y-auto font-mono text-theme-text text-xs custom-scrollbar">
        <template x-for="file in unusedFiles" :key="file.path">
          <div class="flex items-center gap-3 hover:bg-theme-surface/50 px-2 py-2 border-theme-border/50 last:border-0 border-b rounded-theme transition-colors">
            <div class="shrink-0">
              <input type="checkbox" :value="file.path" x-model="selectedFiles" class="form-checkbox bg-theme-bg border-theme-border rounded text-theme-primary focus:ring-theme-primary w-4 h-4 cursor-pointer">
            </div>
            <div class="flex justify-center items-center bg-theme-surface border border-theme-border rounded-theme w-10 h-10 overflow-hidden shrink-0" x-data="{ imgError: false }">
              <img :src="baseUrl + '/' + file.path" class="w-full h-full object-cover" loading="lazy" x-show="!imgError" @error="imgError = true">
              <div class="opacity-50 text-[9px] text-theme-text uppercase font-bold" x-show="imgError" x-cloak x-text="file.path.split('.').pop()"></div>
            </div>
            <div class="flex-1 min-w-0">
              <div class="break-all select-all font-medium text-theme-text" x-text="file.path"></div>
              <div class="flex gap-3 opacity-60 mt-0.5 text-[10px] text-theme-text">
                <span x-text="file.date"></span>
                <span x-text="formatSize(file.size)"></span>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

    <!-- No Unused Files -->
    <div x-show="scanned && unusedFiles.length === 0" class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center" x-cloak>
      <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-success opacity-80">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check-circle"></use>
        </svg>
      </div>
      <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('great') ?></h3>
      <p class="text-sm text-theme-text opacity-60 mt-1"><?= _t('mt_no_unused_files') ?></p>
    </div>

  </div>
</div>

<script>
  const unusedConfig = {
    baseUrl: <?= json_encode(BASE_URL) ?>,
    csrfToken: <?= json_encode(generate_csrf_token()) ?>,
    lang: {
      init: <?= json_encode(_t('js_loading')) ?>,
      scanDB: <?= json_encode(_t('js_scan_db')) ?>,
      scanTheme: <?= json_encode(_t('js_scan_theme')) ?>,
      scanLocal: <?= json_encode(_t('js_scan_local')) ?>,
      comparing: <?= json_encode(_t('js_comparing')) ?>,
      found: <?= json_encode(_t('mt_unused_files_found')) ?>,
      complete: <?= json_encode(_t('msg_scan_complete')) ?>,
      error: <?= json_encode(_t('js_unknown_error')) ?>,
      networkError: <?= json_encode(_t('js_network_error')) ?>,
      confirmDelete: <?= json_encode(_t('msg_confirm_delete_selected')) ?>,
      deleted: <?= json_encode(_t('msg_deleted')) ?>
    }
  };

  document.addEventListener('alpine:init', () => {
    Alpine.data('unusedCheckerWithMeta', (config) => ({
      scanning: false,
      scanned: false,
      progress: 0,
      statusMsg: '',
      unusedFiles: [],
      selectedFiles: [],
      deleting: false,
      sortBy: 'date_desc',
      baseUrl: config.baseUrl,

      async startScan() {
        this.scanning = true;
        this.scanned = false;
        this.progress = 0;
        this.unusedFiles = [];
        this.selectedFiles = [];
        this.statusMsg = config.lang.init;

        try {
          // 1. Scan DB
          this.statusMsg = config.lang.scanDB;
          const dbFiles = new Set();
          let offset = 0;
          let hasMore = true;
          while (hasMore) {
            const res = await fetch(`?action=scan_db_batch&offset=${offset}&limit=500`);
            const data = await res.json();
            if (!data.success) throw new Error(data.error);
            data.files.forEach(f => dbFiles.add(f));
            offset = data.next_offset;
            hasMore = data.has_more;
            if (this.progress < 40) this.progress += 5;
          }
          this.progress = 40;

          // 2. Scan Theme
          this.statusMsg = config.lang.scanTheme;
          const resTheme = await fetch(`?action=scan_theme_files`);
          const dataTheme = await resTheme.json();
          if (!dataTheme.success) throw new Error(dataTheme.error);
          dataTheme.files.forEach(f => dbFiles.add(f));
          this.progress = 60;

          // 3. Scan Local
          this.statusMsg = config.lang.scanLocal;
          const resLocal = await fetch(`?action=get_local_files`);
          const dataLocal = await resLocal.json();
          if (!dataLocal.success) throw new Error(dataLocal.error);
          this.progress = 80;

          // 4. Compare
          this.statusMsg = config.lang.comparing;
          this.unusedFiles = dataLocal.files.filter(fileObj => !dbFiles.has(fileObj.path));

          this.progress = 100;
          this.sortFiles();
          this.statusMsg = config.lang.complete;
          this.scanned = true;
        } catch (e) {
          console.error(e);
          this.statusMsg = config.lang.error;
          showToast(e.message, 'error');
        } finally {
          this.scanning = false;
        }
      },

      sortFiles() {
        const [key, order] = this.sortBy.split('_');
        this.unusedFiles.sort((a, b) => {
          let valA = a[key === 'name' ? 'path' : key];
          let valB = b[key === 'name' ? 'path' : key];

          if (valA < valB) return order === 'asc' ? -1 : 1;
          if (valA > valB) return order === 'asc' ? 1 : -1;
          return 0;
        });
        this.unusedFiles = [...this.unusedFiles];
      },

      toggleAll() {
        if (this.selectedFiles.length === this.unusedFiles.length) {
          this.selectedFiles = [];
        } else {
          this.selectedFiles = this.unusedFiles.map(f => f.path);
        }
      },

      async deleteSelected() {
        if (!confirm(config.lang.confirmDelete)) return;

        this.deleting = true;
        try {
          const formData = new FormData();
          formData.append('action', 'delete_files');
          formData.append('files', JSON.stringify(this.selectedFiles));
          formData.append('csrf_token', config.csrfToken);

          const res = await fetch('check_unused_uploads.php', {
            method: 'POST',
            body: formData
          });
          const data = await res.json();

          if (data.success) {
            this.unusedFiles = this.unusedFiles.filter(f => !this.selectedFiles.includes(f.path));
            this.selectedFiles = [];
            showToast(config.lang.deleted + ' (' + data.count + ')');
          } else {
            showToast(data.error || config.lang.error, 'error');
          }
        } catch (e) {
          showToast(config.lang.networkError, 'error');
        } finally {
          this.deleting = false;
        }
      },

      formatSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
      }
    }));
  });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout/loader.php';
?>
