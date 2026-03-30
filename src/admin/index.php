<?php

/**
 * index.php
 *
 * Display admin dashboard.
 */
require_once __DIR__ . '/bootstrap.php';

/** @var PDO $pdo */
if (!isset($pdo) || !($pdo instanceof PDO)) {
    exit;
}

// Process alerts
$alerts = [];

if (current_user_can('manage_settings')) {
    // Check RewriteBase
    $rwCheck = grinds_check_rewrite_base();
    if ($rwCheck['status'] === 'error') {
        $alerts[] = [
            'type' => 'danger',
            'title' => _t('alert_rewritebase_title'),
            'msg' => _t('alert_rewritebase_mismatch_msg', h($rwCheck['configured']), h($rwCheck['detected'])),
            'link' => 'migration_checklist.php',
            'link_text' => _t('alert_migration_btn')
        ];
    }

    if (isset($_SESSION['config_url_mismatch'])) {
        $mis = $_SESSION['config_url_mismatch'];
        $alerts[] = [
            'type' => 'danger',
            'title' => _t('alert_config_mismatch_title'),
            'msg' => _t('alert_config_mismatch_msg', h($mis['config']), h($mis['actual'])),
            'link' => 'settings.php?tab=system',
            'link_text' => _t('view_details')
        ];
    }

    // Check migration status
    if (isset($_SESSION['migration_alert'])) {
        $mig = $_SESSION['migration_alert'];
        $alerts[] = [
            'id' => 'migration',
            'type' => 'warning',
            'title' => _t('alert_migration_title'),
            'msg' => _t('alert_migration_msg', h($mig['old'])),
            'link' => 'migration_checklist.php?scan_target=' . urlencode($mig['old']),
            'link_text' => _t('alert_migration_btn'),
            'dismissable' => true
        ];
        unset($_SESSION['migration_alert']);
    }

    // Check backup warning
    if (!empty($_SESSION['backup_skipped_warning'])) {
        $alerts[] = [
            'id' => 'backup',
            'type' => 'warning',
            'title' => _t('alert_backup_skipped_title'),
            'msg' => _t('alert_backup_skipped_msg'),
            'link' => 'settings.php?tab=backup',
            'link_text' => _t('tab_backup'),
            'dismissable' => true
        ];
        unset($_SESSION['backup_skipped_warning']);
    }

    // Check debug mode
    if ((defined('DEBUG_MODE') && DEBUG_MODE) || get_option('debug_mode')) {
        $alerts[] = [
            'type' => 'danger',
            'title' => _t('alert_security_title'),
            'msg' => _t('alert_security_msg'),
            'link' => 'settings.php?tab=system',
            'link_text' => _t('menu_settings')
        ];
    }

    // Check system health
    $healthChecks = check_system_health();
    foreach ($healthChecks as $chk) {
        if ($chk['label'] === _t('chk_install_file')) {
            $alerts[] = [
                'type' => $chk['status'],
                'title' => $chk['label'],
                'msg' => $chk['msg'],
                'link' => 'settings.php?tab=system',
                'link_text' => _t('btn_delete_now')
            ];
        } elseif (in_array($chk['label'], [_t('alert_danger_files_title'), _t('alert_warning_files_title')])) {
            $alerts[] = [
                'type' => $chk['status'],
                'title' => $chk['label'],
                'msg' => $chk['msg'],
                'action_html' => '<div class="mt-3">
                    <form method="POST" action="settings.php?tab=system" class="inline">
                        <input type="hidden" name="csrf_token" value="' . h(generate_csrf_token()) . '">
                        <input type="hidden" name="action" value="clear_all_cache">
                        <button type="submit" class="text-xs bg-theme-bg/50 hover:bg-theme-bg border border-theme-border text-theme-text px-3 py-1.5 rounded-theme transition-colors inline-flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . h(grinds_asset_url('assets/img/sprite.svg')) . '#outline-arrow-path"></use></svg>' . _t('btn_rescan') . '</button>
                    </form>
                </div>'
            ];
        } elseif (in_array($chk['status'], ['danger', 'warning'])) {
            // Catch unhandled danger/warning alerts
            $alerts[] = [
                'type' => $chk['status'],
                'title' => $chk['label'],
                'msg' => $chk['msg'] ?: _t('st_action_required'),
                'link' => 'settings.php?tab=system',
                'link_text' => _t('view_details')
            ];
        }
    }

    // Clear file status cache before checking sizes
    clearstatcache();

    // Check log size
    $logFile = ROOT_PATH . '/data/logs/error.log';
    if (file_exists($logFile) && filesize($logFile) > 2 * 1024 * 1024) {
        $size = round(filesize($logFile) / 1024 / 1024, 2) . 'MB';
        $alerts[] = [
            'type' => 'warning',
            'title' => _t('alert_log_title'),
            'msg' => _t('alert_log_msg', $size),
            'link' => 'settings.php?tab=system',
            'link_text' => _t('tab_system')
        ];
    }

    // Check backup size
    $backupDir = ROOT_PATH . '/data/backups';
    $backupFiles = glob($backupDir . '/*.db');
    $totalBackupSize = 0;
    foreach ($backupFiles as $f) {
        if (is_file($f)) $totalBackupSize += filesize($f);
    }
    if ($totalBackupSize > 100 * 1024 * 1024) {
        $size = round($totalBackupSize / 1024 / 1024, 2) . 'MB';
        $alerts[] = [
            'type' => 'warning',
            'title' => _t('alert_backup_title'),
            'msg' => _t('alert_backup_msg', $size),
            'link' => 'settings.php?tab=backup',
            'link_text' => _t('tab_backup')
        ];
    }

    // Check DB size
    if (defined('DB_FILE') && file_exists(DB_FILE)) {
        $dbSize = filesize(DB_FILE);
        $limit = 50 * 1024 * 1024;
        if ($dbSize > $limit) {
            $sizeMb = round($dbSize / 1024 / 1024, 1);
            $alerts[] = [
                'type' => 'warning',
                'title' => _t('alert_db_size_title'),
                'msg' => _t('alert_db_size_msg', $sizeMb),
                'link' => 'settings.php?tab=system',
                'link_text' => _t('tab_system')
            ];
        }
    }

    // Validate configuration
    if (empty(get_option('smtp_host'))) {
        $alerts[] = ['type' => 'warning', 'title' => _t('alert_mail_title'), 'msg' => _t('alert_mail_msg'), 'link' => 'settings.php?tab=mail', 'link_text' => _t('tab_mail')];
    }
    if (empty(get_option('google_analytics_id'))) {
        $alerts[] = ['type' => 'warning', 'title' => _t('alert_ga_title'), 'msg' => _t('alert_ga_msg'), 'link' => 'settings.php?tab=integration', 'link_text' => _t('tab_integration')];
    }

    // Check Nginx configuration
    $nginxConfirmedFile = ROOT_PATH . '/data/.nginx_confirmed';
    if (stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'nginx') !== false && !file_exists($nginxConfirmedFile)) {
        $alerts[] = [
            'id' => 'nginx',
            'type' => 'info',
            'title' => _t('nginx_detect_title'),
            'msg' => _t('nginx_detect_desc'),
            'link' => 'settings.php?tab=system',
            'link_text' => _t('alert_nginx_btn'),
            'dismissable' => true
        ];
    }

    // Sort alerts by severity
    usort($alerts, function ($a, $b) {
        $severity = ['danger' => 1, 'warning' => 2, 'info' => 3, 'success' => 4];
        $sa = $severity[$a['type'] ?? 'info'] ?? 99;
        $sb = $severity[$b['type'] ?? 'info'] ?? 99;
        return $sa <=> $sb;
    });
}

// Fetch statistics
$now = date('Y-m-d H:i:s');

// Get post counts
$repo = new PostRepository($pdo);
$cnt_total_post = $repo->count(['type' => 'post']);
$cnt_total_page = $repo->count(['type' => 'page']);
$cnt_pub_post   = $repo->count(['status' => 'published', 'type' => 'post']);
$cnt_pub_page   = $repo->count(['status' => 'published', 'type' => 'page']);
$cnt_res_post   = $repo->count(['status' => 'reserved', 'type' => 'post']);
$cnt_res_page   = $repo->count(['status' => 'reserved', 'type' => 'page']);
$cnt_draft_post = $repo->count(['status' => 'draft', 'type' => 'post']);
$cnt_draft_page = $repo->count(['status' => 'draft', 'type' => 'page']);

$stats = [
    'total_posts' => $cnt_total_post + $cnt_total_page,
    'published'   => $cnt_pub_post + $cnt_pub_page,
    'reserved'    => $cnt_res_post + $cnt_res_page,
    'drafts'      => $cnt_draft_post + $cnt_draft_page,
];

// Fetch recent activity
$recent_updates = $repo->fetch([], 5, 0, 'p.updated_at DESC');

// Fetch scheduled posts
$scheduled_posts = $repo->fetch(['status' => 'reserved'], 5, 0, 'p.published_at ASC');

// Fetch drafts
$draft_posts = $repo->fetch(['status' => 'draft'], 5, 0, 'p.updated_at DESC');

// Prepare chart data
$period_days = 30;
$statsData = $repo->getDailyPostCounts($period_days);

$daily_counts = $statsData['daily'];
$current_total = $statsData['total_before'];

// Build chart datasets
$chart_labels = [];
$data_activity = [];
$data_growth   = [];
$period_new_posts = 0;

for ($i = $period_days - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $count = isset($daily_counts[$d]) ? (int)$daily_counts[$d] : 0;

    $chart_labels[] = date('n/j', strtotime($d));
    $data_activity[] = $count;

    $current_total += $count;
    $data_growth[] = $current_total;

    $period_new_posts += $count;
}

$js_labels = json_encode($chart_labels);
$js_activity = json_encode($data_activity);
$js_growth = json_encode($data_growth);

// Render view
$page_title = _t('menu_dashboard');
$current_page = 'dashboard';
$params = Routing::getParams();
$is_fresh_install = isset($params['installed']);

// Set JS translations
$isJa = (get_option('site_lang') === 'ja');
$jsRewriteWarningTitle = $isJa ? '⚠️ URLリライトエラー検知' : '⚠️ URL Rewrite Error Detected';
$jsRewriteWarningMsg = $isJa
    ? '現在、記事ページが 404 エラーで表示できない状態になっている可能性があります。<br><code>src/.htaccess</code> を開き、<code># RewriteBase</code> の先頭の <code>#</code> を削除（コメントアウトを解除）して保存してください。'
    : 'Articles might currently be returning 404 errors.<br>Please open <code>src/.htaccess</code> and remove the <code>#</code> at the beginning of the <code># RewriteBase</code> line to uncomment it.';

ob_start();
?>
<?php if (file_exists(ROOT_PATH . '/assets/js/vendor/chart.min.js')): ?>
    <script src="<?= grinds_asset_url('assets/js/vendor/chart.min.js') ?>"></script>
<?php else: ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php endif; ?>

<?php if ($is_fresh_install): ?>
    <template x-teleport="body">
        <div x-data="{ show: true }" x-show="show" class="z-50 fixed inset-0 flex justify-center items-center px-4"
            style="display: none;" x-cloak>
            <div class="fixed inset-0 skin-modal-overlay backdrop-blur-sm transition-opacity" @click="show = false"></div>
            <div
                class="z-10 relative bg-theme-surface shadow-theme border border-theme-border rounded-theme w-full max-w-md overflow-hidden transition-all transform">
                <div class="p-8 text-center">
                    <div
                        class="flex justify-center items-center bg-theme-success/10 mx-auto mb-6 rounded-full ring-1 ring-theme-success/20 w-16 h-16">
                        <svg class="w-8 h-8 text-theme-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check"></use>
                        </svg>
                    </div>
                    <h2 class="mb-2 font-bold text-theme-text text-2xl">
                        <?= _t('setup_complete_title') ?>
                    </h2>
                    <p class="opacity-60 mb-8 text-theme-text text-sm leading-relaxed">
                        <?= _t('setup_complete_msg') ?>
                    </p>

                    <?php
                    $installPath = parse_url(BASE_URL, PHP_URL_PATH);
                    if ($installPath && $installPath !== '/' && $installPath !== ''):
                        $isJa = (get_option('site_lang') === 'ja');
                    ?>
                        <div
                            class="bg-theme-warning/10 mb-6 p-4 border border-theme-warning/20 rounded-theme text-theme-text text-xs text-left">
                            <p class="flex items-center gap-1 mb-1 font-bold text-theme-warning">
                                <svg class="inline mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
                                </svg>
                                <?= $isJa ? 'サブディレクトリ運用の注意点' : 'Subdirectory Installation Note' ?>
                            </p>
                            <p class="opacity-80 mb-2 leading-relaxed">
                                <?= $isJa
                                    ? '検索エンジンはサブディレクトリ内の robots.txt を自動検出しません。ドメインルートの robots.txt に以下を追加してください。'
                                    : 'Search engines do not automatically detect robots.txt in subdirectories. Please add the following to your domain root robots.txt:' ?>
                            </p>
                            <code
                                class="block bg-theme-bg mt-2 p-2 border border-theme-border rounded-theme font-mono text-[10px] select-all">Sitemap: <?= h(resolve_url('sitemap.xml')) ?></code>
                        </div>
                    <?php endif; ?>

                    <button @click="show = false" class="shadow-theme py-3 w-full btn-primary">
                        <?= _t('setup_complete_btn') ?>
                    </button>
                </div>
            </div>
        </div>
    </template>
<?php endif; ?>

<!-- Header -->
<div class="flex sm:flex-row flex-col justify-between sm:items-center gap-4 mb-6">
    <div>
        <h2 class="flex items-center gap-2 font-bold text-theme-text text-xl">
            <svg class="w-6 h-6 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-home"></use>
            </svg>
            <?= _t('menu_dashboard') ?>
        </h2>
        <p class="opacity-60 mt-1 ml-8 text-theme-text text-sm">
            <?= _t('dash_desc') ?>
        </p>
    </div>
    <div class="hidden md:block">
        <a href="posts.php?action=new"
            class="flex items-center gap-2 shadow-theme px-6 py-2.5 rounded-theme hover:scale-[1.02] transition-all btn-primary transform">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
            </svg>
            <span>
                <?= _t('create_new') ?>
            </span>
        </a>
    </div>
</div>

<!-- Alerts -->
<?php if (current_user_can('manage_settings')): ?>
    <?php if (!empty($alerts)): ?>
        <div class="space-y-4 mb-8">
            <?php foreach ($alerts as $alert):
                $type = $alert['type'] ?? 'info';
                // Set alert colors
                $classes = match ($type) {
                    'danger'  => 'bg-theme-danger/10 text-theme-danger border-theme-danger/20',
                    'warning' => 'bg-theme-warning/10 text-theme-warning border-theme-warning/20',
                    'info'    => 'bg-theme-info/10 text-theme-info border-theme-info/20',
                    'success' => 'bg-theme-success/10 text-theme-success border-theme-success/20',
                    default   => 'bg-theme-info/10 text-theme-info border-theme-info/20'
                };
                $isDismissable = !empty($alert['dismissable']);
            ?>
                <div class="p-4 border-l-4 rounded-r-theme shadow-theme transition-all border <?= $classes ?> relative"
                    style="border-left-color: currentColor;" x-data="{ open: true }" x-show="open" x-transition.duration.300ms>

                    <?php if ($isDismissable): ?>
                        <?php $alertId = $alert['id'] ?? 'unknown'; ?>
                        <button @click="open = false; fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/api/dismiss_alert.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'csrf_token=' + <?= h(json_encode(generate_csrf_token())) ?> + '&alert_id=' + encodeURIComponent('<?= h($alertId) ?>')                    })"
                            class="top-2 right-2 absolute opacity-50 hover:opacity-100 p-1 transition-opacity"><span class="sr-only">
                                <?= _t('btn_dismiss') ?>
                            </span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
                            </svg>
                        </button>
                    <?php endif; ?>

                    <p class="mb-1 font-bold text-sm">
                        <?= $alert['title'] ?>
                    </p>
                    <p class="opacity-90 text-sm">
                        <?= strip_tags($alert['msg'], '<a><br><strong><code><span>') ?>
                        <?php if (!empty($alert['link'])): ?>
                            <a href="<?= $alert['link'] ?>" class="hover:opacity-75 ml-1 font-bold underline">
                                <?= h($alert['link_text']) ?>
                            </a>
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($alert['action_html'])): ?>
                        <?= $alert['action_html'] ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div
            class="bg-theme-surface shadow-theme mb-8 p-5 border border-theme-border rounded-theme flex items-center justify-between transition-all hover:shadow-theme">
            <div class="flex items-center gap-4">
                <div
                    class="flex justify-center items-center bg-theme-success/10 border border-theme-success/20 rounded-full w-12 h-12 text-theme-success">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-shield-check"></use>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-theme-text text-lg">
                        <?= _t('dash_system_healthy') ?>
                    </h3>
                    <p class="opacity-60 text-theme-text text-sm">
                        <?= _t('dash_no_warnings') ?>
                    </p>
                </div>
            </div>
            <div class="hidden sm:block text-right">
                <p class="text-xs font-mono opacity-40 text-theme-text">GrindSite v
                    <?= defined('CMS_VERSION') ? CMS_VERSION : '' ?>
                </p>
                <p class="text-xs font-mono opacity-40 text-theme-text">PHP
                    <?= phpversion() ?>
                </p>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Stats Cards (4 Columns) -->
<div class="gap-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 mb-8">

    <!-- 1. Total Posts -->
    <a href="<?= ($cnt_total_post === 0 && $cnt_total_page > 0) ? 'posts.php?type=page' : 'posts.php' ?>"
        class="block bg-theme-surface shadow-theme hover:shadow-theme p-5 border border-theme-border rounded-theme transition-shadow">
        <div class="flex items-center gap-4">
            <div
                class="flex flex-shrink-0 justify-center items-center bg-theme-primary/10 border border-theme-primary/20 rounded-full w-12 h-12 text-theme-primary">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
                </svg>
            </div>
            <div>
                <p class="opacity-60 font-medium text-theme-text text-sm">
                    <?= _t('stat_total_pages') ?>
                </p>
                <p class="font-bold text-theme-text text-2xl">
                    <?= number_format($stats['total_posts']) ?>
                </p>
            </div>
        </div>
    </a>

    <!-- 2. Published -->
    <a href="<?= ($cnt_pub_post === 0 && $cnt_pub_page > 0) ? 'posts.php?status=published&type=page' : 'posts.php?status=published' ?>"
        class="block bg-theme-surface shadow-theme hover:shadow-theme p-5 border border-theme-border rounded-theme transition-shadow">
        <div class="flex items-center gap-4">
            <div
                class="flex flex-shrink-0 justify-center items-center bg-theme-success/10 border border-theme-success/20 rounded-full w-12 h-12 text-theme-success">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check-circle"></use>
                </svg>
            </div>
            <div>
                <p class="opacity-60 font-medium text-theme-text text-sm">
                    <?= _t('stat_published') ?>
                </p>
                <p class="font-bold text-theme-text text-2xl">
                    <?= number_format($stats['published']) ?>
                </p>
            </div>
        </div>
    </a>

    <!-- 3. Scheduled (Reserved) -->
    <a href="<?= ($cnt_res_post === 0 && $cnt_res_page > 0) ? 'posts.php?status=reserved&type=page' : 'posts.php?status=reserved' ?>"
        class="block bg-theme-surface shadow-theme hover:shadow-theme p-5 border border-theme-border rounded-theme transition-shadow">
        <div class="flex items-center gap-4">
            <div
                class="flex flex-shrink-0 justify-center items-center bg-theme-info/10 border border-theme-info/20 rounded-full w-12 h-12 text-theme-info">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-clock"></use>
                </svg>
            </div>
            <div>
                <p class="opacity-60 font-medium text-theme-text text-sm">
                    <?= _t('stat_reserved') ?>
                </p>
                <p class="font-bold text-theme-text text-2xl">
                    <?= number_format($stats['reserved']) ?>
                </p>
            </div>
        </div>
    </a>

    <!-- 4. Drafts -->
    <a href="<?= ($cnt_draft_post === 0 && $cnt_draft_page > 0) ? 'posts.php?status=draft&type=page' : 'posts.php?status=draft' ?>"
        class="block bg-theme-surface shadow-theme hover:shadow-theme p-5 border border-theme-border rounded-theme transition-shadow">
        <div class="flex items-center gap-4">
            <div
                class="flex flex-shrink-0 justify-center items-center bg-theme-warning/10 border border-theme-warning/20 rounded-full w-12 h-12 text-theme-warning">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
                </svg>
            </div>
            <div>
                <p class="opacity-60 font-medium text-theme-text text-sm">
                    <?= _t('stat_drafts') ?>
                </p>
                <p class="font-bold text-theme-text text-2xl">
                    <?= number_format($stats['drafts']) ?>
                </p>
            </div>
        </div>
    </a>
</div>

<!-- Hybrid Chart (Site Growth) -->
<div class="mb-8">
    <div class="bg-theme-surface shadow-theme border border-theme-border rounded-theme overflow-hidden">
        <div class="flex justify-between items-center bg-theme-bg/30 p-5 border-theme-border border-b">
            <h2 class="flex items-center gap-2 font-bold text-theme-text text-lg">
                <svg class="w-5 h-5 text-theme-primary" fill="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-trending-up"></use>
                </svg>
                <?= _t('dash_growth_title') ?>
            </h2>
            <div class="opacity-60 font-mono text-theme-text text-xs">
                <?= _t('dash_last_30_days') ?>
            </div>
        </div>
        <div class="relative p-4">
            <div class="relative w-full h-72">
                <canvas id="growthChart"></canvas>
            </div>
        </div>
        <div class="bg-theme-bg/10 p-3 border-theme-border border-t text-center">
            <span class="opacity-60 font-bold text-theme-text text-xs">
                <?= _t('dash_new_posts') ?>:
            </span>
            <span class="ml-2 font-bold text-theme-primary text-lg">+
                <?= $period_new_posts ?>
            </span>
        </div>
    </div>
</div>

<!-- Recent Updates List -->
<div class="bg-theme-surface shadow-theme mb-8 border border-theme-border rounded-theme overflow-hidden">
    <div class="bg-theme-bg/30 p-5 border-theme-border border-b">
        <h2 class="font-bold text-theme-text text-lg">
            <?= _t('dash_recent_updates') ?>
        </h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-theme-text text-sm leading-normal">
            <tbody class="divide-y divide-theme-border">
                <?php foreach ($recent_updates as $post): ?>
                    <tr class="hover:bg-theme-bg/30">
                        <td class="px-5 py-3 font-bold">
                            <div class="flex items-center gap-2">
                                <span
                                    class="bg-theme-bg opacity-70 px-1.5 py-0.5 border border-theme-border rounded-theme text-[10px] uppercase">
                                    <?= $post['type'] ?>
                                </span>
                                <a href="posts.php?action=edit&id=<?= $post['id'] ?>"
                                    class="hover:text-theme-primary transition-colors">
                                    <?= h($post['title']) ?>
                                </a>
                            </div>
                        </td>
                        <td class="opacity-60 px-5 py-3 font-mono text-xs text-right">
                            <?= date('Y-m-d H:i', strtotime($post['updated_at'])) ?>
                        </td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            <a href="posts.php?action=edit&id=<?= $post['id'] ?>"
                                class="font-bold text-theme-primary text-xs hover:underline">
                                <?= _t('edit') ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recent_updates)): ?>
                    <tr>
                        <td colspan="3" class="p-8">
                            <div class="flex flex-col justify-center items-center py-12 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
                                <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
                                    </svg>
                                </div>
                                <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('no_data') ?></h3>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Scheduled & Drafts Grid -->
<div class="gap-6 grid grid-cols-1 lg:grid-cols-2 mb-8">

    <!-- Scheduled Posts -->
    <div class="bg-theme-surface shadow-theme border border-theme-border rounded-theme overflow-hidden">
        <div class="flex justify-between items-center bg-theme-bg/30 p-5 border-theme-border border-b">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-theme-text text-lg">
                    <?= _t('stat_reserved') ?>
                </h2>
                <span class="bg-theme-bg px-2 py-0.5 border border-theme-border rounded-theme font-mono text-xs font-bold opacity-70"><?= number_format($stats['reserved']) ?></span>
            </div>
            <a href="<?= ($cnt_res_post === 0 && $cnt_res_page > 0) ? 'posts.php?status=reserved&type=page' : 'posts.php?status=reserved' ?>" class="flex items-center gap-1 font-bold text-theme-primary text-xs hover:underline">
                View All
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-theme-text text-sm leading-normal">
                <tbody class="divide-y divide-theme-border">
                    <?php foreach ($scheduled_posts as $post): ?>
                        <tr class="hover:bg-theme-bg/30">
                            <td class="px-5 py-3 font-bold">
                                <a href="posts.php?action=edit&id=<?= $post['id'] ?>" class="hover:text-theme-primary transition-colors">
                                    <?= h($post['title']) ?>
                                </a>
                            </td>
                            <td class="opacity-60 px-5 py-3 font-mono text-xs text-right">
                                <?= date('Y-m-d H:i', strtotime($post['published_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($scheduled_posts)): ?>
                        <tr>
                            <td colspan="2" class="p-6">
                                <div class="flex flex-col justify-center items-center py-10 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
                                    <div class="flex justify-center items-center w-12 h-12 mb-3 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-clock"></use>
                                        </svg>
                                    </div>
                                    <h3 class="mb-1 font-bold text-theme-text text-base opacity-80"><?= _t('no_data') ?></h3>
                                    <p class="text-xs text-theme-text opacity-50 mt-1"><?= _t('dash_no_scheduled') ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Drafts -->
    <div class="bg-theme-surface shadow-theme border border-theme-border rounded-theme overflow-hidden">
        <div class="flex justify-between items-center bg-theme-bg/30 p-5 border-theme-border border-b">
            <div class="flex items-center gap-3">
                <h2 class="font-bold text-theme-text text-lg">
                    <?= _t('stat_drafts') ?>
                </h2>
                <span class="bg-theme-bg px-2 py-0.5 border border-theme-border rounded-theme font-mono text-xs font-bold opacity-70"><?= number_format($stats['drafts']) ?></span>
            </div>
            <a href="<?= ($cnt_draft_post === 0 && $cnt_draft_page > 0) ? 'posts.php?status=draft&type=page' : 'posts.php?status=draft' ?>" class="flex items-center gap-1 font-bold text-theme-primary text-xs hover:underline">
                View All
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-theme-text text-sm leading-normal">
                <tbody class="divide-y divide-theme-border">
                    <?php foreach ($draft_posts as $post): ?>
                        <tr class="hover:bg-theme-bg/30">
                            <td class="px-5 py-3 font-bold">
                                <a href="posts.php?action=edit&id=<?= $post['id'] ?>" class="hover:text-theme-primary transition-colors">
                                    <?= h($post['title']) ?>
                                </a>
                            </td>
                            <td class="opacity-60 px-5 py-3 font-mono text-xs text-right">
                                <?= date('Y-m-d H:i', strtotime($post['updated_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($draft_posts)): ?>
                        <tr>
                            <td colspan="2" class="p-6">
                                <div class="flex flex-col justify-center items-center py-10 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
                                    <div class="flex justify-center items-center w-12 h-12 mb-3 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
                                        </svg>
                                    </div>
                                    <h3 class="mb-1 font-bold text-theme-text text-base opacity-80"><?= _t('no_data') ?></h3>
                                    <p class="text-xs text-theme-text opacity-50 mt-1"><?= _t('dash_no_drafts') ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Check URL rewrite health
        fetch((window.grindsBaseUrl || '').replace(/\/$/, '') + '/robots.txt', {
                method: 'HEAD',
                cache: 'no-store'
            })
            .then(response => {
                if (response.status === 404 || response.status === 500) {
                    const container = document.querySelector('.space-y-4.mb-8') || document.querySelector('.gap-6.grid');
                    if (container) {
                        const alertHtml = `
                            <div class="mb-8 p-4 border-l-4 rounded-r-theme shadow-theme transition-all border bg-theme-danger/10 text-theme-danger border-theme-danger/20 relative" style="border-left-color: currentColor;">
                                <p class="mb-1 font-bold text-sm">${<?= json_encode($jsRewriteWarningTitle) ?>}</p>
                                <p class="opacity-90 text-sm">
                                    ${<?= json_encode($jsRewriteWarningMsg) ?>}
                                </p>
                            </div>
                        `;
                        container.insertAdjacentHTML('beforebegin', alertHtml);
                    }
                }
            })
            .catch(e => console.error('Health check failed', e));

        // Read CSS variables
        const getThemeColor = (varName) => {
            const el = document.body;
            const rgb = getComputedStyle(el).getPropertyValue(varName).trim();
            return `rgb(${rgb.replace(/ /g, ',')})`;
        };
        const getRgba = (varName, alpha = 1) => {
            const rgb = getComputedStyle(document.body).getPropertyValue(varName).trim();
            return `rgba(${rgb.replace(/ /g, ',')}, ${alpha})`;
        };

        // Define chart colors
        const primaryColor = getThemeColor('--color-primary') || '#2563eb';
        const successColor = getThemeColor('--color-success') || '#22c55e';
        const textColor = getThemeColor('--color-text') || '#334155';
        const borderColor = getRgba('--color-border', 0.5) || '#e2e8f0';
        const bgPrimary = getRgba('--color-primary', 0.3);
        const bgSuccess = getRgba('--color-success', 0.1);

        // Initialize growth chart
        const ctxGrowth = document.getElementById('growthChart')?.getContext('2d');
        if (ctxGrowth) {
            new Chart(ctxGrowth, {
                data: {
                    labels: <?= $js_labels ?>,
                    datasets: [{
                            type: 'bar',
                            label: <?= json_encode(_t('dash_new_posts')) ?>,
                            data: <?= $js_activity ?>,
                            backgroundColor: bgPrimary,
                            borderColor: primaryColor,
                            borderWidth: 1,
                            borderRadius: 2,
                            order: 2,
                            yAxisID: 'y'
                        },
                        {
                            type: 'line',
                            label: <?= json_encode(_t('stat_total_pages')) ?>,
                            data: <?= $js_growth ?>,
                            borderColor: successColor,
                            backgroundColor: bgSuccess,
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            fill: true,
                            tension: 0.3,
                            order: 1,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: getThemeColor('--color-surface'),
                            titleColor: textColor,
                            bodyColor: textColor,
                            borderColor: borderColor,
                            borderWidth: 1,
                            padding: 10,
                            cornerRadius: 6,
                            titleFont: {
                                family: 'inherit'
                            },
                            bodyFont: {
                                family: 'inherit'
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxTicksLimit: 8,
                                color: textColor,
                                opacity: 0.6,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true,
                            grid: {
                                color: borderColor
                            },
                            ticks: {
                                stepSize: 1,
                                precision: 0,
                                color: textColor,
                                opacity: 0.6
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: false,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout/loader.php';
?>
