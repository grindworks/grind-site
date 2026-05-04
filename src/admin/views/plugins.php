<?php

/**
 * plugins.php
 * Renders the interface for managing plugins.
 *
 * @var bool $isWritable
 * @var array $plugins
 */
if (!defined('GRINDS_APP')) exit; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex sm:flex-row flex-col justify-between sm:items-center gap-4 mb-6">
        <div>
            <h2 class="flex items-center gap-2 font-bold text-theme-text text-xl whitespace-nowrap">
                <svg class="w-6 h-6 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-puzzle-piece"></use>
                </svg>
                <?= _t('plugins_title') ?>
            </h2>
            <p class="opacity-60 mt-1 ml-8 text-theme-text text-sm">
                <?= _t('plugins_desc') ?>
            </p>
        </div>
    </div>

    <?php if (!$isWritable): ?>
        <div class="bg-theme-warning/10 p-4 border border-theme-warning/30 rounded-theme text-theme-warning text-sm flex items-start gap-3">
            <svg class="mt-0.5 w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
            </svg>
            <p class="font-bold"><?= _t('plugins_dir_readonly') ?></p>
        </div>
    <?php endif; ?>

    <!-- Plugin addition and modification guide for users -->
    <div class="bg-theme-info/10 p-4 border border-theme-info/20 rounded-theme text-theme-text text-sm flex items-start gap-3">
        <svg class="mt-0.5 w-5 h-5 text-theme-info shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-information-circle"></use>
        </svg>
        <div>
            <p class="font-bold text-theme-info"><?= _t('plugins_guide_title') ?></p>
            <p class="mt-1 opacity-80 text-xs leading-relaxed"><?= _t('plugins_guide_desc') ?></p>
        </div>
    </div>

    <div class="bg-theme-surface shadow-theme border border-theme-border rounded-theme overflow-hidden">
        <?php if (empty($plugins)): ?>
            <div class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 text-center">
                <svg class="w-16 h-16 mb-4 text-theme-text opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-puzzle-piece"></use>
                </svg>
                <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('no_data') ?></h3>
            </div>
        <?php else: ?>
            <table class="min-w-full leading-normal">
                <thead>
                    <tr class="bg-theme-bg/50 border-theme-border border-b font-bold text-theme-text/60 text-xs text-left uppercase tracking-wider">
                        <th class="px-6 py-4 w-12 text-center"><?= _t('plugin_status') ?></th>
                        <th class="px-6 py-4"><?= _t('plugin_name') ?></th>
                        <th class="px-6 py-4 hidden md:table-cell"><?= _t('plugin_desc') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-theme-border">
                    <?php foreach ($plugins as $plugin): ?>
                        <tr class="<?= $plugin['is_active'] ? 'bg-theme-surface' : 'bg-theme-bg/30 opacity-70' ?> transition-colors hover:bg-theme-bg/50">
                            <td class="px-6 py-4 align-middle text-center">
                                <form method="post" action="plugins.php" class="inline m-0 p-0">
                                    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="plugin_file" value="<?= h($plugin['file']) ?>">
                                    <input type="hidden" name="target_state" value="<?= $plugin['is_active'] ? 'disable' : 'enable' ?>">

                                    <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-theme-primary focus:ring-offset-2 <?= $plugin['is_active'] ? 'bg-theme-primary' : 'bg-theme-border' ?>" <?= !$isWritable ? 'disabled' : '' ?> title="<?= h($plugin['is_active'] ? _t('btn_disable') : _t('btn_enable')) ?>">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform <?= $plugin['is_active'] ? 'translate-x-6' : 'translate-x-1' ?>"></span>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                <div class="font-bold text-theme-text text-sm mb-1"><?= h($plugin['name']) ?></div>
                                <div class="flex items-center gap-2 text-[10px] font-mono text-theme-text/60">
                                    <span><?= h($plugin['file']) ?></span>
                                    <?php if (!empty($plugin['version'])): ?>
                                        <span class="bg-theme-bg px-1.5 py-0.5 border border-theme-border rounded">v<?= h($plugin['version']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Display error if the plugin is quarantined -->
                                <?php
                                $errorFile = dirname($plugin['path']) . '/.' . $plugin['file'] . '.error';
                                if (!$plugin['is_active'] && file_exists($errorFile)):
                                    $errContent = @file_get_contents($errorFile);
                                    $err = $errContent ? json_decode($errContent, true) : null;
                                    if (is_array($err) && !empty($err['message'])):
                                ?>
                                        <div class="mt-3 p-3 bg-theme-danger/5 border border-theme-danger/20 rounded-theme text-theme-danger text-xs max-w-lg">
                                            <p class="font-bold flex items-center gap-1.5 mb-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
                                                </svg>
                                                <?= _t('plugins_quarantined_title') ?>
                                            </p>
                                            <p class="opacity-80 mb-2 leading-relaxed"><?= _t('plugins_quarantined_desc') ?></p>
                                            <div class="p-2 bg-theme-surface rounded border border-theme-danger/20 font-mono text-[10px] break-all shadow-inner">
                                                <span class="font-bold text-theme-danger/70"><?= _t('plugin_error_label') ?></span><br>
                                                <?= h($err['message']) ?> (Line: <?= h($err['line'] ?? '?') ?>)
                                            </div>
                                        </div>
                                <?php
                                    endif;
                                endif;
                                ?>

                                <div class="md:hidden mt-2 text-xs text-theme-text/80 leading-relaxed">
                                    <?= nl2br(h($plugin['description'])) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle hidden md:table-cell">
                                <div class="text-sm text-theme-text/80 leading-relaxed mb-2">
                                    <?= nl2br(h($plugin['description'])) ?>
                                </div>
                                <?php if (!empty($plugin['author'])): ?>
                                    <div class="text-xs text-theme-text/50">
                                        By <span class="font-bold"><?= h($plugin['author']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
