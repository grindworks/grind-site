<?php

/**
 * nginx_tool.php
 * Renders the Nginx configuration generator tool.
 */
if (!defined('GRINDS_APP'))
    exit;

$serverNameRaw = $_SERVER['SERVER_NAME'] ?? 'example.com';
// ポート番号が含まれている場合は明示的に除去し、その後不正な文字をサニタイズ
$serverName = preg_replace('/[^a-zA-Z0-9.\-_]/', '', preg_replace('/:\d+$/', '', $serverNameRaw));
$relativePath = parse_url(BASE_URL, PHP_URL_PATH) ?? '/';
$relativePath = rtrim($relativePath, '/') . '/';

// Calculate physical path
$installPath = str_replace('\\', '/', ROOT_PATH);

// Determine document root
$rootPath = $installPath;
$relTrim = trim($relativePath, '/');

if (!empty($relTrim)) {
    $needle = '/' . $relTrim;
    // Compare paths case-insensitively
    if (str_ends_with(strtolower($rootPath), strtolower($needle))) {
        $rootPath = substr($rootPath, 0, -strlen($needle));
    }
}
$rootPath = rtrim($rootPath, '/');
if (empty($rootPath)) {
    $rootPath = '/var/www/html';
}

// Guess PHP-FPM socket
$phpVer = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

// Load Nginx helper
require_once ROOT_PATH . '/lib/nginx_helper.php';

// Generate Nginx configuration
$templateConfig = str_replace(["\r\n", "\r"], "\n", grinds_get_nginx_config($serverName, $rootPath, $relativePath, '__FASTCGI_PASS__'));
?>

<div class="bg-theme-surface shadow-theme mt-6 border border-theme-border rounded-theme" x-data="{
    open: true,
    template: <?= htmlspecialchars(json_encode($templateConfig, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>,
    mode: 'sock',
    phpVer: '<?= $phpVer ?>',
    custom: '127.0.0.1:9000',
    code: '',
    copied: false,
    update() {
        let replacement = '';
        if (this.mode === 'sock') {
            replacement = 'unix:/var/run/php/php' + this.phpVer + '-fpm.sock';
        } else if (this.mode === 'tcp') {
            replacement = '127.0.0.1:9000';
        } else {
            replacement = this.custom;
        }
        this.code = this.template.replace('__FASTCGI_PASS__', replacement);
    },
    init() {
        this.update();
        this.$watch('mode', () => this.update());
        this.$watch('custom', () => this.update());
    }
}">
    <button type="button" @click="open = !open" class="flex justify-between items-center w-full p-4 sm:p-6 text-left focus:outline-none">
        <div>
            <div class="flex items-center gap-2 mb-2 font-bold text-theme-text text-lg">
                <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-server"></use>
                </svg>
                <?= _t('st_nginx_title') ?>
            </div>
            <p class="opacity-60 text-theme-text text-sm">
                <?= _t('st_nginx_desc') ?>
            </p>
            <p class="mt-2 text-theme-danger text-xs font-bold">
                <?= _t('st_nginx_fastcgi_warn') ?>
            </p>
        </div>
        <svg class="w-5 h-5 text-theme-text opacity-50 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
        </svg>
    </button>

    <div x-show="open" x-collapse class="border-t border-theme-border bg-theme-bg/30">
        <div class="p-4 border-b border-theme-border flex flex-col sm:flex-row gap-4 sm:items-center text-sm bg-theme-surface/50">
            <span class="font-bold text-theme-text">PHP-FPM:</span>
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="radio" value="sock" x-model="mode" class="text-theme-primary focus:ring-theme-primary">
                <span class="text-theme-text">UNIX Socket</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="radio" value="tcp" x-model="mode" class="text-theme-primary focus:ring-theme-primary">
                <span class="text-theme-text">TCP/IP</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="radio" value="custom" x-model="mode" class="text-theme-primary focus:ring-theme-primary">
                <span class="text-theme-text">Custom</span>
            </label>
            <div x-show="mode === 'custom'" class="flex-1" x-cloak>
                <input type="text" x-model="custom" class="form-control py-1 px-2 text-xs w-full" placeholder="e.g. 127.0.0.1:9000 or unix:/tmp/php.sock">
            </div>
        </div>
        <div class="bg-theme-warning/10 p-3 border-b border-theme-warning/20 text-theme-warning text-xs">
            <svg class="inline-block mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
            </svg>
            <?= _t('msg_nginx_path_notice') ?>
        </div>
        <div class="relative p-4">
            <textarea x-ref="codeArea" x-model="code" rows="20" class="w-full font-mono text-xs form-control bg-theme-bg text-theme-text p-4 border border-theme-border rounded-theme leading-relaxed focus:ring-theme-primary focus:border-theme-primary" spellcheck="false"></textarea>
            <div class="absolute top-6 right-6 flex gap-2">
                <button type="button" @click="$refs.codeArea.select(); navigator.clipboard.writeText(code).then(() => { copied = true; setTimeout(() => copied = false, 2000); })" class="text-xs px-3 py-1.5 rounded-theme border shadow-theme transition-colors flex items-center gap-1" :class="copied ? 'bg-theme-success text-white border-theme-success' : 'bg-theme-surface hover:bg-theme-primary hover:text-white text-theme-text border-theme-border'">
                    <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-clipboard-document"></use>
                    </svg>
                    <span x-show="!copied"><?= _t('btn_copy') ?></span>
                    <span x-show="copied" class="flex items-center gap-1" x-cloak>✅ <?= _t('copied') ?></span>
                </button>
            </div>
        </div>
    </div>
</div>
