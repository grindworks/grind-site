<?php

/**
 * migration_checklist.php
 *
 * Post-migration checklist and tools.
 */
require_once __DIR__ . '/bootstrap.php';

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) {
    exit;
}

// Check permissions
if (!current_user_can('manage_tools')) {
    redirect('admin/index.php');
}

// Check RewriteBase
$rewriteStatus = 'ok';
$rewriteMsg = _t('mig_msg_rewrite_ok');
$rwCheck = grinds_check_rewrite_base();

if ($rwCheck['status'] === 'error') {
    $rewriteStatus = 'warning';
    $rewriteMsg = _t('mig_msg_rewrite_fail') . " (Current: {$rwCheck['configured']}, Expected: {$rwCheck['detected']})";
}

$scanTarget = Routing::getString($_GET, 'scan_target');

$page_title = _t('mig_check_title');
$current_page = 'migration_check';

// Translations for JS health check
$jsRewriteWarningMsg = _t('st_doctor_error_desc');

ob_start();
require_once __DIR__ . '/layout/toast.php';
?>

<div class="space-y-6" x-data="migrationChecklist()">

    <!-- Header -->
    <div class="flex items-center gap-4">
        <div>
            <h2 class="flex items-center gap-2 font-bold text-theme-text text-xl">
                <svg class="w-6 h-6 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-wrench-screwdriver"></use>
                </svg>
                <?= _t('mig_check_title') ?>
            </h2>
            <p class="opacity-60 mt-1 ml-8 text-theme-text text-sm"><?= _t('mig_check_desc') ?></p>
        </div>
    </div>

    <?php if (file_exists(ROOT_PATH . '/.htpasswd') || file_exists(__DIR__ . '/.htpasswd')): ?>
        <!-- Basic Auth Warning -->
        <div class="bg-theme-warning/10 p-4 border border-theme-warning/30 rounded-theme text-theme-warning text-sm">
            <p class="font-bold">⚠️ <?= _t('mig_warn_basic_auth_title') ?></p>
            <p class="mt-1"><?= _t('mig_warn_basic_auth_desc') ?></p>
        </div>
    <?php endif; ?>

    <!-- Main Layout: 2 Columns -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: Individual Tools -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Manual Check Tools -->
            <div class="bg-theme-surface shadow-theme border border-theme-border rounded-theme overflow-hidden">
                <div class="p-5 bg-theme-bg/50 border-b border-theme-border">
                    <h3 class="font-bold text-theme-text text-sm flex items-center gap-2">
                        <svg class="w-4 h-4 text-theme-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-adjustments-horizontal"></use>
                        </svg>
                        <?= _t('mig_manual_check') ?>
                    </h3>
                </div>
                <div class="divide-y divide-theme-border/50">

                    <!-- 2. Database Optimization -->
                    <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-theme-surface/50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-theme-primary/10 text-theme-primary shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-circle-stack"></use>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-theme-text text-base">2. <?= _t('mig_item_db') ?></h3>
                                <p class="text-xs text-theme-text opacity-60 mt-0.5"><?= _t('st_opt_db_desc') ?></p>
                            </div>
                        </div>
                        <button @click="optimizeDb()" :disabled="optimizing" class="w-full sm:w-auto btn-secondary px-4 py-2 text-sm min-w-[120px] shrink-0 flex justify-center items-center gap-2">
                            <svg x-show="!optimizing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-play"></use>
                            </svg>
                            <svg x-show="optimizing" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                            </svg>
                            <span x-show="!optimizing"><?= _t('mig_btn_optimize') ?></span>
                            <span x-show="optimizing" x-cloak><?= _t('js_processing') ?></span>
                        </button>
                    </div>

                    <!-- 3. Rebuild Search Index -->
                    <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-theme-surface/50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-theme-primary/10 text-theme-primary shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-theme-text text-base">3. <?= _t('mig_item_index') ?></h3>
                                <p class="text-xs text-theme-text opacity-60 mt-0.5"><?= _t('st_search_index_desc') ?></p>
                            </div>
                        </div>
                        <button @click="rebuildIndex()" :disabled="rebuilding" class="w-full sm:w-auto btn-secondary px-4 py-2 text-sm min-w-[120px] shrink-0 flex justify-center items-center gap-2">
                            <svg x-show="!rebuilding" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-play"></use>
                            </svg>
                            <svg x-show="rebuilding" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                            </svg>
                            <span x-show="!rebuilding"><?= _t('mig_btn_rebuild') ?></span>
                            <span x-show="rebuilding" x-cloak x-text="rebuildProgress + '%'"></span>
                        </button>
                    </div>

                    <!-- 4. Clear Cache -->
                    <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-theme-surface/50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-theme-primary/10 text-theme-primary shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-theme-text text-base">4. <?= _t('mig_item_cache') ?></h3>
                                <p class="text-xs text-theme-text opacity-60 mt-0.5"><?= _t('st_clear_cache_desc') ?></p>
                            </div>
                        </div>
                        <button @click="clearCache()" :disabled="clearing" class="w-full sm:w-auto btn-secondary px-4 py-2 text-sm min-w-[120px] shrink-0 flex justify-center items-center gap-2">
                            <svg x-show="!clearing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-play"></use>
                            </svg>
                            <svg x-show="clearing" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                            </svg>
                            <span x-show="!clearing"><?= _t('mig_btn_clear') ?></span>
                            <span x-show="clearing" x-cloak><?= _t('js_processing') ?></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 5. Old Domain Link Check (Manual Only) -->
            <div class="bg-theme-surface shadow-theme rounded-theme overflow-hidden border border-theme-danger/20 relative">
                <div class="bg-theme-danger/5 px-6 py-4 border-b border-theme-danger/10 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full bg-theme-danger/10 text-theme-danger">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-link"></use>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-theme-text text-lg">5. <?= _t('mig_item_links') ?></h3>
                            <p class="text-xs text-theme-text opacity-60 mt-0.5"><?= _t('mig_item_links_desc') ?></p>
                        </div>
                    </div>
                    <div class="shrink-0 inline-flex items-center justify-center gap-1.5 bg-theme-danger/10 border border-theme-danger/20 px-3 py-1.5 rounded-theme text-theme-danger">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
                        </svg>
                        <span class="font-bold text-xs uppercase tracking-wider">
                            <?= _t('st_dangerous') ?>
                        </span>
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex flex-col sm:flex-row gap-4 items-end">
                        <div class="flex-1 w-full">
                            <label class="block text-xs font-bold text-theme-text opacity-70 mb-2"><?= _t('st_target_url_label') ?></label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-theme-text opacity-40">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-globe-alt"></use>
                                    </svg>
                                </span>
                                <input type="text" x-model="scanTarget" x-ref="targetUrl" class="form-control w-full pl-10 h-[42px]" placeholder="http://localhost/old-site">
                            </div>
                        </div>
                        <button @click="scanLinks()" :disabled="scanning" class="btn-secondary px-6 h-[42px] min-w-[120px] flex justify-center items-center gap-2 w-full sm:w-auto">
                            <svg x-show="!scanning" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
                            </svg>
                            <svg x-show="scanning" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                            </svg>
                            <span x-show="!scanning"><?= _t('mig_btn_scan') ?></span>
                            <span x-show="scanning" x-cloak><?= _t('btn_scanning') ?></span>
                        </button>
                    </div>

                    <!-- Scan Results -->
                    <div x-show="scanResults !== null" class="mt-6" x-transition.opacity x-cloak>
                        <template x-if="scanResults && scanResults.length === 0">
                            <div class="flex items-center gap-3 p-4 bg-theme-success/10 text-theme-success rounded-theme border border-theme-success/20">
                                <div class="bg-theme-success/20 p-1.5 rounded-full shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check-circle"></use>
                                    </svg>
                                </div>
                                <div class="text-sm font-medium">
                                    <?= _t('st_doctor_scan_ok') ?>
                                </div>
                            </div>
                        </template>
                        <template x-if="scanResults && scanResults.length > 0">
                            <div class="space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 bg-theme-warning/10 text-theme-warning rounded-theme border border-theme-warning/20">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-theme-warning/20 p-1.5 rounded-full shrink-0 text-theme-warning">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-circle"></use>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold"><?= _t('st_doctor_scan_warn') ?></p>
                                            <p class="text-xs opacity-80 mt-0.5"><span x-text="scanResults.length"></span> items.</p>
                                        </div>
                                    </div>
                                    <button @click="fixLinks()" :disabled="fixing" class="btn-danger px-5 py-2 text-sm shadow-theme flex justify-center items-center gap-2 shrink-0 whitespace-nowrap w-full sm:w-auto">
                                        <svg x-show="!fixing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-wrench"></use>
                                        </svg>
                                        <svg x-show="fixing" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                                        </svg>
                                        <span x-show="!fixing"><?= _t('btn_fix_links') ?></span>
                                        <span x-show="fixing" x-cloak><?= _t('btn_fixing') ?></span>
                                    </button>
                                </div>
                                <div class="border border-theme-border rounded-theme overflow-hidden bg-theme-bg">
                                    <div class="max-h-60 overflow-y-auto overflow-x-auto custom-scrollbar">
                                        <table class="w-full text-left text-sm whitespace-nowrap">
                                            <thead class="bg-theme-surface border-b border-theme-border sticky top-0 z-10">
                                                <tr>
                                                    <th class="px-4 py-2 font-bold text-theme-text opacity-70 text-xs w-24">ID</th>
                                                    <th class="px-4 py-2 font-bold text-theme-text opacity-70 text-xs">Title / Label</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-theme-border/50">
                                                <template x-for="item in scanResults" :key="item.id">
                                                    <tr class="hover:bg-theme-surface/50 transition-colors">
                                                        <td class="px-4 py-2 font-mono text-xs text-theme-text opacity-60" x-text="item.id"></td>
                                                        <td class="px-4 py-2 text-theme-text font-medium text-xs" x-text="item.title"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

        </div>

        <!-- Right Column: Run All & Status -->
        <div class="space-y-6">

            <!-- Run All Card -->
            <div class="bg-theme-surface shadow-theme border border-theme-primary/30 rounded-theme overflow-hidden">
                <div class="p-6 bg-theme-primary/5 border-b border-theme-primary/20">
                    <h3 class="flex items-center gap-2 font-bold text-theme-primary text-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-rocket-launch"></use>
                        </svg>
                        <?= _t('mig_btn_run_all') ?>
                    </h3>
                </div>
                <div class="p-6 space-y-4">
                    <p class="opacity-80 text-theme-text text-sm leading-relaxed">
                        <?= _t('mig_confirm_run_all') ?>
                    </p>
                    <button @click="runAll()" :disabled="runningAll" class="w-full flex items-center justify-center gap-2 shadow-theme px-6 py-3.5 rounded-theme font-bold transition-all btn-primary transform hover:-translate-y-0.5 disabled:opacity-70 disabled:cursor-not-allowed disabled:transform-none">
                        <svg x-show="!runningAll" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-play-circle"></use>
                        </svg>
                        <svg x-show="runningAll" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                        </svg>
                        <span x-text="runningAll ? <?= htmlspecialchars(json_encode(_t('js_processing')), ENT_QUOTES) ?> : <?= htmlspecialchars(json_encode(_t('mig_btn_run_all')), ENT_QUOTES) ?>"></span>
                    </button>
                </div>
            </div>

            <!-- Status Card (RewriteBase) -->
            <div class="bg-theme-surface shadow-theme border border-theme-border rounded-theme p-5">
                <h3 class="font-bold text-theme-text text-sm mb-4 flex items-center gap-2 uppercase tracking-wider">
                    <svg class="w-4 h-4 text-theme-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-information-circle"></use>
                    </svg>
                    <?= _t('system') ?> <?= _t('lbl_status_label') ?>
                </h3>

                <div class="space-y-3">
                    <div class="flex items-start gap-3 bg-theme-bg/50 p-3 rounded-theme border border-theme-border/50">
                        <div class="mt-0.5 shrink-0">
                            <?php if ($rewriteStatus === 'ok'): ?>
                                <svg class="w-5 h-5 text-theme-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check-circle"></use>
                                </svg>
                            <?php else: ?>
                                <svg class="w-5 h-5 text-theme-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-circle"></use>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <span class="block font-bold text-theme-text text-xs mb-1">1. <?= _t('mig_item_rewrite') ?></span>
                            <p class="text-[11px] leading-tight <?= $rewriteStatus === 'ok' ? 'text-theme-success opacity-80' : 'text-theme-danger' ?>">
                                <span x-show="!rewriteErrorDetected"><?= h($rewriteMsg) ?></span>
                                <span x-show="rewriteErrorDetected" class="text-theme-danger font-bold" x-cloak>
                                    <?= $jsRewriteWarningMsg ?>
                                </span>
                            </p>
                            <p class="text-[10px] mt-2 opacity-70 text-theme-text leading-tight">
                                <?= _t('mig_notice_rewrite') ?>
                            </p>
                            <?php if ($rewriteStatus !== 'ok'): ?>
                                <form method="post" action="settings.php" class="mt-3">
                                    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                    <input type="hidden" name="action" value="fix_rewrite_base">
                                    <button type="submit" class="w-full btn-primary px-3 py-1.5 text-xs shadow-theme flex justify-center items-center gap-1.5 transition-transform hover:-translate-y-0.5">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-wrench"></use>
                                        </svg>
                                        <?= _t('mig_btn_fix') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('migrationChecklist', () => ({
            scanTarget: <?= json_encode($scanTarget) ?>,
            scanning: false,
            fixing: false,
            scanResults: null,
            rebuilding: false,
            rebuildProgress: 0,
            clearing: false,
            optimizing: false,
            runningAll: false,
            rewriteErrorDetected: false,

            init() {
                // URL Rewrite Health Check
                fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/robots.txt', {
                        method: 'HEAD',
                        cache: 'no-store'
                    })
                    .then(response => {
                        if (response.status === 404 || response.status === 500) {
                            this.rewriteErrorDetected = true;
                        }
                    })
                    .catch(e => console.error('Health check failed', e));
            },

            async runAll() {
                if (!confirm(<?= json_encode(_t('mig_confirm_run_all')) ?>)) return;
                this.runningAll = true;

                try {
                    // 1. Fix RewriteBase
                    await this.postSettings('fix_rewrite_base');

                    // 2. Optimize DB
                    await this.postSettings('optimize_db');

                    // 3. Rebuild Index
                    await this.rebuildIndex(true);

                    // 4. Clear Cache
                    await this.clearCache(true);

                    showToast(<?= json_encode(_t('msg_migration_ready')) ?>);

                    // Force secure logout via POST
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = 'logout.php';
                    const i = document.createElement('input');
                    i.type = 'hidden';
                    i.name = 'csrf_token';
                    i.value = <?= json_encode(generate_csrf_token()) ?>;
                    f.appendChild(i);
                    document.body.appendChild(f);
                    f.submit();
                } catch (e) {
                    showToast('Error: ' + e.message, 'error');
                    this.runningAll = false;
                } finally {}
            },

            async postSettings(action) {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('ajax_mode', '1');
                formData.append('csrf_token', <?= json_encode(generate_csrf_token()) ?>);
                const res = await fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/settings.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.error || 'Unknown error');
            },

            async optimizeDb() {
                if (!confirm(<?= json_encode(_t('confirm_optimize_db')) ?>)) return;
                this.optimizing = true;
                try {
                    await this.postSettings('optimize_db');
                    showToast(<?= json_encode(_t('msg_opt_db_success')) ?>);
                } catch (e) {
                    showToast('Error: ' + e.message, 'error');
                } finally {
                    this.optimizing = false;
                }
            },

            async scanLinks() {
                if (!this.scanTarget || this.scanTarget.trim() === '') {
                    showToast(<?= json_encode(_t('err_required')) ?>, 'error');
                    this.$refs.targetUrl.focus();
                    return;
                }
                this.scanning = true;
                this.scanResults = null;
                const formData = new FormData();
                formData.append('action', 'scan_hardcoded_links');
                formData.append('target_url', this.scanTarget);
                formData.append('ajax_mode', '1');
                formData.append('csrf_token', <?= json_encode(generate_csrf_token()) ?>);
                try {
                    const res = await fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/settings.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) this.scanResults = data.data;
                    else showToast('Error: ' + data.error, 'error');
                } catch (e) {
                    showToast('Error: ' + e.message, 'error');
                } finally {
                    this.scanning = false;
                }
            },

            async fixLinks() {
                if (!confirm(<?= json_encode(_t('confirm_continue')) ?>)) return;
                this.fixing = true;
                const formData = new FormData();
                formData.append('action', 'replace_hardcoded_links');
                formData.append('target_url', this.scanTarget);
                formData.append('ajax_mode', '1');
                formData.append('csrf_token', <?= json_encode(generate_csrf_token()) ?>);
                try {
                    const res = await fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/settings.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        showToast(<?= json_encode(_t('msg_fix_complete')) ?>.replace(/%[sd]/, data.count));
                        this.scanResults = [];
                        // Clear cache to prevent showing old URLs after replacement
                        await this.clearCache(true);
                    } else showToast('Error: ' + data.error, 'error');
                } catch (e) {
                    showToast('Error: ' + e.message, 'error');
                } finally {
                    this.fixing = false;
                }
            },

            async rebuildIndex(silent = false) {
                if (!silent && !confirm(<?= json_encode(_t('st_search_index_confirm')) ?>)) return;
                this.rebuilding = true;
                this.rebuildProgress = 0;
                let offset = 0;
                let hasMore = true;
                while (hasMore) {
                    const res = await fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/api/rebuild_index.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            csrf_token: <?= json_encode(generate_csrf_token()) ?>,
                            offset: offset
                        })
                    });
                    const data = await res.json();
                    if (!data.success) throw new Error(data.error);
                    this.rebuildProgress = data.percentage;
                    offset = data.next_offset;
                    hasMore = data.has_more;
                }
                if (!silent) showToast(<?= json_encode(_t('js_index_done')) ?>);
                this.rebuilding = false;
            },

            async clearCache(silent = false) {
                this.clearing = true;
                const res = await fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/api/clear_cache.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        csrf_token: <?= json_encode(generate_csrf_token()) ?>
                    })
                });
                const data = await res.json();
                if (data.success) {
                    if (!silent) showToast(<?= json_encode(_t('msg_cache_cleared')) ?>);
                } else showToast('Error: ' + data.error, 'error');
                this.clearing = false;
            }
        }));
    });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout/loader.php';
