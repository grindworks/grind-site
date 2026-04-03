<?php

/**
 * posts_form.php
 *
 * Renders the user interface for creating and editing posts and pages.
 * Fixed: Localized editor controls (Expand/Collapse All, Reset).
 */
if (!defined('GRINDS_APP'))
    exit;

// Load editor configuration.
$block_config = require __DIR__ . '/../config/editor_blocks.php';
$block_library = $block_config['library'];
$quick_blocks = $block_config['quick_blocks'];

// Flatten block library for JS.
$all_blocks_flat = [];
foreach ($block_library as $cat) {
    foreach ($cat['items'] as $key => $val) {
        $all_blocks_flat[$key] = $val;
    }
}

// Prepare post data.
$current_published_at = $post['published_at'] ?? date('Y-m-d H:i');
$isFuture = false;
try {
    $isFuture = (!empty($current_published_at) && new DateTime($current_published_at) > new DateTime());
} catch (Exception $e) {
    $isFuture = false;
}
$rawContent = $post['content'] ?? '';

// Ensure 'blocks' structure
$contentObject = ['blocks' => []];
if (is_string($rawContent)) {
    if (function_exists('grinds_url_to_view')) {
        $rawContent = grinds_url_to_view($rawContent);
    }

    $decoded = json_decode($rawContent, true);

    // If JSON decoding fails specifically due to UTF-8 errors, try to salvage it
    if (json_last_error() === JSON_ERROR_UTF8 && function_exists('mb_scrub')) {
        $scrubbedContent = mb_scrub($rawContent, 'UTF-8');
        $decoded = json_decode($scrubbedContent, true);
    }

    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $contentObject = $decoded;
    }
}
if (!isset($contentObject['blocks']) || !is_array($contentObject['blocks'])) {
    $contentObject['blocks'] = [];
}

// JSON encode for JS
$jsonContent = json_encode(
    $contentObject,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_PARTIAL_OUTPUT_ON_ERROR
);

$encodingError = null;
if ($jsonContent === false) {
    $encodingError = json_last_error_msg();
    $jsonContent = '{"blocks":[]}';
}

// Encode settings
$heroConfig = json_decode($post['hero_settings'] ?? '{}', true);
if (!is_array($heroConfig))
    $heroConfig = [];

if (empty($heroConfig['buttons'])) {
    $heroConfig['buttons'] = [];
    if (!empty($heroConfig['btn_text'])) {
        $heroConfig['buttons'][] = ['text' => $heroConfig['btn_text'], 'url' => $heroConfig['btn_url'] ?? '', 'style' => 'primary'];
    }
}
$jsHeroSettings = json_encode($heroConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

// JS Variables
$js_translations = [
    'template_saved' => _t('tpl_saved'),
    'confirm_publish_preview' => _t('confirm_publish_preview'),
    'ph_link_url' => _t('ph_link_url'),
    'tpl_confirm_load' => _t('tpl_confirm_load'),
    'def_plan_basic' => _t('def_plan_basic'),
    'def_price_zero' => _t('def_price_zero'),
    'confirm_discard_draft' => _t('confirm_discard_draft'),
    'blk_h2' => _t('blk_h2'),
    'blk_h3' => _t('blk_h3'),
    'blk_h4' => _t('blk_h4'),
    'blk_h5' => _t('blk_h5'),
    'blk_h6' => _t('blk_h6'),
    'type_post' => _t('type_post'),
    'type_page' => _t('type_page'),
    'js_preview_loading' => _t('js_preview_loading'),
    'js_preview_error' => _t('js_preview_error'),
    'js_preview_net_err' => _t('js_preview_net_err'),
    'js_code_snippet' => _t('js_code_snippet'),
    'js_images_count' => _t('js_images_count'),
    'js_warn_absolute_path' => _t('js_warn_absolute_path'),
    'js_warn_script_tag' => _t('js_warn_script_tag'),
    'warn_post_too_large' => _t('warn_post_too_large'),
    'confirm_continue' => _t('confirm_continue'),
    'ai_paste_empty' => _t('ai_paste_empty'),
    'ai_paste_no_markdown' => _t('ai_paste_no_markdown'),
    'ai_paste_error' => _t('ai_paste_error'),
    'ai_paste_confirm' => _t('ai_paste_confirm'),
    'action_publish' => _t('action_publish'),
    'update' => _t('update'),
    'action_schedule' => _t('action_schedule'),
    'draft_restored' => _t('msg_draft_restored'),
    'fetch_failed' => _t('js_fetch_failed'),
    'err_conflict_confirm' => _t('err_conflict_confirm'),
    'system_error' => _t('js_system_error'),
    'error' => _t('error') . ': %s',
];

// Ensure required variables exist to prevent warnings
$linkablePages = $linkablePages ?? [];
$categories = $categories ?? [];

// Limit preloaded pages to 20 to reduce payload size.
// Full search is handled via API (post_search.php).
$jsLinkablePages = json_encode(array_slice($linkablePages, 0, 20), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
$jsTranslations = json_encode($js_translations, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
$jsBlockLibrary = json_encode($block_library, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

$layout_setting = get_option('admin_layout', 'sidebar');
$toolbar_top_class = ($layout_setting === 'topbar') ? 'top-20' : 'top-4';

$render_context = [
    'pdo' => $pdo,
    'linkablePages' => $linkablePages,
    'block_config' => $block_config,
    'categories' => $categories,
    'fixContentPaths' => function ($text) {
        if (class_exists('BlockRenderer')) {
            $renderer = new BlockRenderer();
            return $renderer->sanitizeText((string)$text);
        }
        return strip_tags((string)$text, '<b><strong><i><em><u><s><br><a><span><code>');
    },
    'headingLevels' => [
        ['value' => 'h2', 'label' => _t('blk_h2')],
        ['value' => 'h3', 'label' => _t('blk_h3')],
        ['value' => 'h4', 'label' => _t('blk_h4')],
        ['value' => 'h5', 'label' => _t('blk_h5')],
        ['value' => 'h6', 'label' => _t('blk_h6')],
    ]
];

$render_block_safe = function ($__file__, $block_type, $ctx) {
    extract($ctx);
    $index = 'index';
    ob_start();
    try {
        if (!file_exists($__file__))
            throw new Exception("File not found");
        include $__file__;
        return ['status' => 'success', 'html' => ob_get_clean()];
    } catch (Throwable $e) {
        ob_end_clean();
        return ['status' => 'error', 'msg' => $e->getMessage()];
    }
};

$loaded_block_types = [];
$parsedBase = parse_url(BASE_URL);
$basePath = rtrim($parsedBase['path'] ?? '/', '/') . '/';
?>

<!-- Data Islands -->
<script id="grinds-post-content" type="application/json">
    <?= $jsonContent ?>
</script>
<script id="grinds-linkable-pages" type="application/json">
    <?= $jsLinkablePages ?>
</script>
<script id="grinds-translations" type="application/json">
    <?= $jsTranslations ?>
</script>
<script id="grinds-block-library" type="application/json">
    <?= $jsBlockLibrary ?>
</script>
<script id="grinds-hero-settings" type="application/json">
    <?= $jsHeroSettings ?>
</script>

<script>
    function getJsonData(id) {
        const el = document.getElementById(id);
        if (!el || !el.textContent) return null;
        try {
            return JSON.parse(el.textContent);
        } catch (e) {
            console.error('Failed to parse JSON data for ' + id, e);
            return null;
        }
    }

    window.grindsPostContent = getJsonData('grinds-post-content');
    window.grindsLinkablePages = getJsonData('grinds-linkable-pages') || [];
    window.grindsTranslations = {
        ...window.grindsTranslations,
        ...(getJsonData('grinds-translations') || {})
    };
    window.grindsBlockLibrary = getJsonData('grinds-block-library') || {};
    window.grindsHeroSettings = getJsonData('grinds-hero-settings') || {
        buttons: []
    };
    window.grindsBaseUrl = <?= json_encode(rtrim(BASE_URL, '/') . '/') ?>;
    window.grindsBasePath = <?= json_encode($basePath) ?>;
    window.grindsUserId = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
    window.grindsCsrfToken = <?= json_encode(generate_csrf_token()) ?>;
    <?php
    $defaultLang = 'en';
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && str_contains(strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']), 'ja')) {
        $defaultLang = 'ja';
    }
    ?>
    window.grindsLang = <?= json_encode(get_option('site_lang', $defaultLang)) ?>;
    window.grindsEditorDebounce = <?= (int)get_option('editor_debounce_time', 1000) ?>;
    window.grindsPlaceholderImg = <?= json_encode(PLACEHOLDER_IMG) ?>;
    window.grindsUploadMax = <?= grinds_get_max_upload_size() ?>;
</script>

<script src="<?= grinds_asset_url('assets/js/media_manager.js') ?>"></script>
<script src="<?= grinds_asset_url('assets/js/admin_editor.js') ?>"></script>

<div x-data='{
    ...blockEditor(window.grindsPostContent, {
        seoTitle: <?= json_encode($post['title'] ?? "", JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        seoDesc: <?= json_encode($post['description'] ?? "", JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        seoImage: <?= json_encode(get_media_url($post['thumbnail'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        postStatus: <?= json_encode(($post['status'] ?? '') === 'published' ? 'published' : 'draft', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
        siteDomain: <?= json_encode(parse_url(BASE_URL, PHP_URL_HOST) ?? "localhost", JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
    }),
    draggingIndex: null,
    dropTargetIndex: null,
    handleDragStart(index, event) {
        this.draggingIndex = index;
        event.dataTransfer.effectAllowed = "move";
    },
    handleDragOver(index) {
        if (index === this.draggingIndex) return;
        this.dropTargetIndex = index;
    },
    handleDragLeave() {
        this.dropTargetIndex = null;
    },
    handleDrop(index) {
        if (this.draggingIndex === null || this.draggingIndex === index) return;
        this.moveBlockTo(this.draggingIndex, index, false);
        this.handleDragEnd();
    },
    handleDragEnd() {
        this.draggingIndex = null;
        this.dropTargetIndex = null;
    }
}'
    x-effect="window.toggleScrollLock(inserterOpen || templateModalOpen)"
    @announce.window="document.getElementById('a11y-live-region').textContent = $event.detail"
    :class="inserterOpen || templateModalOpen ? 'pointer-events-none' : ''">

    <!-- Accessibility Live Region for Screen Readers -->
    <div id="a11y-live-region" class="sr-only" aria-live="polite" aria-atomic="true"></div>

    <?php if ($encodingError): ?>
        <div class="bg-theme-danger/10 shadow-theme mb-6 p-4 border-theme-danger border-l-4 rounded-r-theme text-theme-danger">
            <p class="font-bold"><?= _t('err_content_load') ?></p>
            <p class="text-sm"><?= _t('err_content_decode', h($encodingError)) ?></p>
        </div>
    <?php
    endif; ?>

    <!-- Auto-save Recovery Bar -->
    <div x-show="draftRecoveryOpen" x-transition class="flex justify-between items-center bg-theme-warning/10 shadow-theme mb-6 p-4 border-theme-warning border-l-4 rounded-r-theme" x-cloak>
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-theme-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
            </svg>
            <div>
                <p class="font-bold text-theme-warning text-sm"><?= _t('msg_draft_found_title') ?></p>
                <p class="opacity-80 text-theme-text text-xs"><?= _t('msg_draft_found_desc') ?></p>
            </div>
        </div>
        <div class="flex gap-2">
            <button type="button" @click="restoreDraft()" class="bg-theme-warning hover:opacity-90 shadow-theme px-3 py-1.5 rounded-theme font-bold text-white text-xs transition"><?= _t('btn_restore_draft') ?></button>
            <button type="button" @click="discardDraft()" class="hover:bg-theme-warning/10 px-3 py-1.5 border border-theme-warning/30 rounded-theme font-bold text-theme-warning text-xs transition"><?= _t('btn_discard_draft') ?></button>
        </div>
    </div>

    <div class="flex justify-between items-center mb-6">
        <h2 class="flex items-center gap-2 font-bold text-theme-text text-xl whitespace-nowrap">
            <svg class="w-6 h-6 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
            </svg>
            <?= $action === 'new' ? _t('post_title_new') : _t('post_title_edit') ?>
        </h2>

        <!-- Mobile-only top save button -->
        <button type="button"
            @click="postStatus = 'published'; document.getElementById('post-form').requestSubmit();"
            :disabled="isSubmitting || isUploading"
            class="lg:hidden bg-theme-primary hover:opacity-90 shadow-sm px-4 py-2 rounded-theme font-bold text-white text-xs transition transform hover:-translate-y-0.5 disabled:opacity-50">
            <?= $isFuture ? _t('action_schedule') : ($action === 'new' ? _t('action_publish') : _t('update')) ?>
        </button>

        <!-- Auto-save Indicator -->
        <div class="hidden sm:flex items-center opacity-50 font-mono text-theme-text text-xs transition-opacity duration-500" x-show="lastAutoSaved" x-transition.opacity.duration.500ms x-cloak>
            <svg class="mr-1.5 w-3 h-3 text-theme-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check"></use>
            </svg>
            <span x-text='<?= json_encode(_t('js_auto_saved'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>.replace("%s", lastAutoSaved)'></span>
        </div>
    </div>

    <!-- Main Form -->
    <form id="post-form" method="post" enctype="multipart/form-data" class="items-start gap-8 grid grid-cols-1 lg:grid-cols-3" @submit="setTimeout(() => isSubmitting = true, 10);">
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
        <!-- Flag to inform controller of save mode. -->
        <input type="hidden" name="save_post_mode" value="1">
        <input type="hidden" name="id" value="<?= $post['id'] ?? '' ?>">
        <input type="hidden" name="content_is_base64" value="0">
        <input type="hidden" name="original_updated_at" value="<?= h($post['updated_at'] ?? '') ?>">
        <input type="hidden" name="original_version" value="<?= h($post['version'] ?? 0) ?>">

        <!-- Left Column -->
        <div class="space-y-6 lg:col-span-2">
            <div class="space-y-6 bg-theme-surface shadow-theme p-6 border border-theme-border rounded-theme">
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="font-bold text-theme-text text-sm"><?= _t('lbl_title') ?></label>
                        <span class="text-xs opacity-60" :class="seoTitle.length > (window.grindsLang === 'ja' ? 35 : 60) ? 'text-theme-danger font-bold' : ''"><span x-text="seoTitle.length"></span> / <span x-text="window.grindsLang === 'ja' ? '35' : '60'"></span></span>
                    </div>
                    <input type="text" name="title" x-model="seoTitle" required class="text-lg form-control" placeholder="<?= _t('lbl_title') ?>">
                </div>

                <div>
                    <label class="block mb-2 font-bold text-theme-text text-sm"><?= _t('lbl_slug') ?></label>
                    <div class="flex">
                        <span class="inline-flex items-center bg-theme-bg opacity-70 px-3 border border-theme-border border-r-0 rounded-l-theme text-theme-text text-sm"><?= h(resolve_url('/')) ?></span>
                        <input type="text" name="slug" value="<?= h($post['slug'] ?? '') ?>"
                            @blur="$el.value = $el.value.toLowerCase().trim().replace(/[\s_]+/g, '-').replace(/[^\p{L}\p{N}-]/gu, '').replace(/-+/g, '-').replace(/^-+|-+$/g, '')"
                            class="rounded-l-none font-mono form-control" placeholder="<?= _t('ph_url_slug') ?>">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="font-bold text-theme-text text-sm"><?= _t('lbl_desc') ?></label>
                        <span class="text-xs opacity-60" :class="seoDesc.length > (window.grindsLang === 'ja' ? 120 : 160) ? 'text-theme-danger font-bold' : ''"><span x-text="seoDesc.length"></span> / <span x-text="window.grindsLang === 'ja' ? '120' : '160'"></span></span>
                    </div>
                    <textarea name="description" x-model="seoDesc" rows="2" class="text-sm form-control"></textarea>
                </div>
            </div>

            <!-- Block Editor -->
            <div class="relative space-y-4">

                <div class="flex justify-between items-center mb-2">
                    <label class="block font-bold text-theme-text text-sm"><?= _t('lbl_content') ?></label>

                    <!-- Global controls. -->
                    <div class="flex gap-2">
                        <button type="button" @click="undo()" :disabled="history.length === 0" class="p-2 rounded-theme hover:bg-theme-bg opacity-60 disabled:opacity-20 text-theme-text hover:text-theme-primary transition-colors" title="<?= h(_t('undo')) ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                        </button>
                        <button type="button" @click="redo()" :disabled="future.length === 0" class="p-2 rounded-theme hover:bg-theme-bg opacity-60 disabled:opacity-20 text-theme-text hover:text-theme-primary transition-colors" title="<?= h(_t('redo')) ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10h-10a8 8 0 00-8 8v2M21 10l-6 6m6-6l-6-6" />
                            </svg>
                        </button>
                        <span class="text-theme-border">|</span>
                        <button type="button" @click="expandAll()" class="opacity-60 text-[10px] text-theme-text hover:text-theme-primary transition-colors"><?= _t('btn_expand_all') ?></button>
                        <span class="text-theme-border">|</span>
                        <button type="button" @click="collapseAll()" class="opacity-60 text-[10px] text-theme-text hover:text-theme-primary transition-colors"><?= _t('btn_collapse_all') ?></button>
                        <span class="text-theme-border">|</span>
                        <button type="button" @click="resetContent()" class="opacity-60 hover:opacity-100 text-[10px] text-theme-danger hover:text-theme-danger transition-colors" title="<?= h(_t('confirm_reset')) ?>"><?= _t('reset') ?></button>

                        <button type="button" @click="openTemplateModal()" class="flex items-center gap-1 hover:bg-theme-primary/10 ml-2 px-3 py-1.5 border border-theme-primary/20 rounded-full font-bold text-theme-primary text-xs transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-duplicate"></use>
                            </svg>
                            <?= _t('btn_template') ?>
                        </button>
                    </div>
                </div>

                <!-- Toolbar -->
                <div class="sticky <?= $toolbar_top_class ?> z-30 transition-all mb-4">
                    <div class="flex items-center gap-1 bg-theme-surface shadow-theme mx-auto sm:mx-0 p-1.5 border border-theme-border w-max max-w-full overflow-x-auto no-scrollbar" style="border-radius: var(--add-block-radius);">
                        <?php foreach ($quick_blocks as $key):
                            $btn = $all_blocks_flat[$key] ?? null;
                            if (!$btn)
                                continue;
                        ?>
                            <button type="button" @click.prevent='addBlock(<?= json_encode($key, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>); $dispatch("announce", "Block added: " + <?= json_encode($btn['label'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);' class="hover:bg-theme-bg p-2 rounded-full text-theme-text hover:text-theme-primary transition-colors" title="<?= h($btn['label']) ?>" aria-label="<?= h($btn['label']) ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= h(grinds_asset_url('assets/img/sprite.svg') . '#' . $btn['icon']) ?>"></use>
                                </svg>
                            </button>
                        <?php
                        endforeach; ?>
                        <div class="mx-1 bg-theme-border w-px h-5"></div>
                        <button type="button" @click="openInserter()" class="flex items-center gap-2 bg-theme-primary hover:opacity-90 shadow-theme px-3 py-2 text-theme-on-primary transition-all" style="border-radius: var(--add-block-radius);">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
                            </svg>
                            <span class="font-bold text-xs whitespace-nowrap"><?= _t('add') ?></span>
                        </button>
                    </div>


                </div>

                <!-- Blocks -->
                <div class="space-y-4 min-h-[200px]" aria-live="polite" aria-relevant="additions removals">
                    <template x-for="(block, index) in blocks" :key="block.id">
                        <div :id="'block-wrapper-' + block.id"
                            class="group relative bg-theme-surface hover:shadow-theme p-4 border border-theme-border rounded-theme transition-all"
                            :class="{
                                'py-2': block.collapsed,
                                'opacity-50': draggingIndex === index,
                                '!border-theme-primary ring-2 ring-theme-primary/50': dropTargetIndex === index && draggingIndex !== index
                            }"
                            @dragover.prevent="handleDragOver(index)"
                            @dragleave.prevent="handleDragLeave()"
                            @drop.prevent="handleDrop(index)">

                            <div class="flex items-center gap-3">
                                <!-- Drag Handle -->
                                <div draggable="true" @dragstart.stop="handleDragStart(index, $event)" @dragend.stop="handleDragEnd()" class="cursor-move p-1 text-theme-text/40 hover:text-theme-text transition-colors" title="<?= _t('drag_to_reorder') ?>">
                                    <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </div>

                                <!-- Order Input -->
                                <div class="shrink-0" @click.stop>
                                    <input type="number" :value="index + 1"
                                        @change="const val = parseInt($event.target.value); if (isNaN(val)) { $event.target.value = index + 1; return; } moveBlockTo(index, val - 1); $dispatch('announce', 'Block moved to position ' + val);"
                                        @keydown.enter.prevent="$event.target.blur()"
                                        class="bg-theme-bg shadow-theme py-1 border border-theme-border focus:border-theme-primary rounded-theme focus:ring-theme-primary w-12 font-bold text-theme-text text-sm text-center appearance-none [-moz-appearance:textfield]"
                                        min="1" :max="blocks.length" title="<?= _t('change_order') ?>">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <!-- Block controls. -->
                                    <?php include __DIR__ . '/parts/block_controls.php'; ?>
                                </div>
                            </div>

                            <!-- Render dynamic blocks. -->
                            <div x-show="!block.collapsed" x-collapse>
                                <?php
                                foreach ($all_blocks_flat as $type => $info) {
                                    if (!preg_match('/^[a-z0-9_]+$/', $type))
                                        continue;

                                    $file = __DIR__ . '/blocks/' . $type . '.php';

                                    // Render block.
                                    $result = $render_block_safe($file, $type, $render_context);

                                    if ($result['status'] === 'success') {
                                        echo '<template x-if="block.type === \'' . $type . '\'">';
                                        echo '<div class="block-scope-' . h($type) . ' pt-6">';

                                        echo $result['html'];
                                        echo '</div>';
                                        echo '</template>';
                                        $loaded_block_types[] = $type;
                                    } elseif ($result['msg'] !== 'File not found') {
                                        echo '<template x-if="block.type === \'' . $type . '\'">';
                                        echo '<div class="flex items-start gap-3 bg-theme-danger/10 p-4 border border-theme-danger/20 rounded-theme text-theme-danger">';
                                        echo '  <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><use href="' . grinds_asset_url('assets/img/sprite.svg') . '#outline-exclamation-circle"></use></svg>';
                                        echo '  <div>';
                                        echo '    <p class="font-bold text-sm">' . _t('err_block_crash') . ': ' . h($type) . '</p>';
                                        echo '  </div>';
                                        echo '</div>';
                                        echo '</template>';
                                        $loaded_block_types[] = $type;
                                    }
                                }
                                $jsLoadedTypes = json_encode($loaded_block_types, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                                ?>

                                <!-- Unknown block fallback. -->
                                <template x-if='!<?= $jsLoadedTypes ?>.includes(block.type)'>
                                    <div class="flex items-center gap-4 bg-theme-bg/50 mt-6 p-4 border border-theme-border border-dashed rounded-theme">
                                        <div class="flex justify-center items-center bg-theme-surface border border-theme-border rounded-full w-10 h-10 text-theme-text/40">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-question-mark-circle"></use>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-theme-text text-sm">
                                                <?= _t('msg_unknown_block') ?>:
                                                <span x-text="block.type" class="bg-theme-bg px-1.5 py-0.5 border border-theme-border rounded-theme font-mono text-xs"></span>
                                            </p>
                                            <p class="mt-1 text-theme-text/60 text-xs"><?= _t('msg_unknown_block_desc') ?></p>
                                            <details class="mt-2">
                                                <summary class="text-theme-primary text-xs hover:underline cursor-pointer select-none"><?= _t('lbl_show_raw_data') ?></summary>
                                                <pre class="bg-theme-bg mt-2 p-2 border border-theme-border rounded-theme overflow-x-auto font-mono text-[10px] text-theme-text" x-text="JSON.stringify(block.data, null, 2)"></pre>
                                            </details>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Empty state. -->
                    <div x-show="blocks.length === 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-theme-bg/30 opacity-50 hover:opacity-100 py-16 border-2 border-theme-border hover:border-theme-primary/50 border-dashed rounded-theme text-theme-text text-center transition-all cursor-pointer" @click="addBlock('paragraph')">
                            <div class="flex flex-col items-center">
                                <svg class="opacity-50 mb-3 w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
                                </svg>
                                <span class="font-bold text-sm"><?= _t('msg_click_to_start') ?></span>
                            </div>
                        </div>

                        <?php
                        $aiConfig = $skin['ai_paste'] ?? [
                            'title' => _t('ai_paste'),
                            'description' => _t('ai_paste_desc'),
                            'bg_start' => 'rgb(var(--color-surface) / var(--color-surface-alpha, 1))',
                            'bg_end' => 'rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.05))',
                            'border' => 'rgb(var(--color-primary) / calc(var(--color-primary-alpha, 1) * 0.2))',
                            'border_style' => 'dashed',
                            'border_hover' => 'rgb(var(--color-primary) / var(--color-primary-alpha, 1))',
                            'icon_color' => 'rgb(var(--color-primary) / var(--color-primary-alpha, 1))',
                            'text_gradient_start' => 'rgb(var(--color-primary) / var(--color-primary-alpha, 1))',
                            'text_gradient_end' => 'rgb(var(--color-primary) / var(--color-primary-alpha, 1))',
                            'icon' => 'outline-sparkles'
                        ];
                        ?> <div class="group relative flex flex-col justify-center items-center gap-3 py-16 border-2 rounded-theme text-center transition-all overflow-hidden"
                            x-data="{
                                isSecureContext: window.isSecureContext,
                                aiBgStart: '<?= $aiConfig['bg_start'] ?>',
                                aiBgEnd: '<?= $aiConfig['bg_end'] ?>',
                                aiBorder: '<?= $aiConfig['border'] ?>',
                                aiBorderStyle: '<?= $aiConfig['border_style'] ?>',
                                aiBorderHover: '<?= $aiConfig['border_hover'] ?>',
                                currentBg: '<?= $aiConfig['bg_start'] ?>',
                                currentBorder: '<?= $aiConfig['border'] ?>',
                            }"
                            :class="{ 'opacity-50 cursor-not-allowed': !isSecureContext, 'cursor-pointer': isSecureContext }"
                            :title="!isSecureContext ? '<?= _t('err_https_required') ?>' : ''"
                            :style="`background-color: ${currentBg}; border-color: ${currentBorder}; border-style: ${aiBorderStyle};`"
                            @mouseover="if (isSecureContext) { currentBorder = aiBorderHover; currentBg = aiBgEnd; }"
                            @mouseout="if (isSecureContext) { currentBorder = aiBorder; currentBg = aiBgStart; }"
                            @click="if (!isSecureContext) {
                                alert('<?= _t('err_https_required') ?>');
                                return;
                            }
                            if (!navigator.clipboard || !navigator.clipboard.readText) {
                                alert(window.grindsTranslations.ai_paste_error || 'Clipboard API is not supported in this environment.');
                                return;
                            }
                            navigator.clipboard.readText().then(text => {
                                if (!text) {
                                    alert(window.grindsTranslations.ai_paste_empty);
                                    return;
                                }
                                if (!this.processMarkdownPaste(text)) {
                                    alert(window.grindsTranslations.ai_paste_no_markdown);
                                }
                            }).catch(err => {
                                console.error('Failed to read clipboard', err);
                                alert(window.grindsTranslations.ai_paste_error);
                            })">
                            <div class="relative group-hover:scale-110 transition-transform duration-300">
                                <div class="absolute inset-0 rounded-full blur opacity-20 group-hover:opacity-40 transition-opacity"
                                    style="background: linear-gradient(to right, <?= $aiConfig['text_gradient_start'] ?>, <?= $aiConfig['text_gradient_end'] ?>)"></div>
                                <div class="relative p-4 rounded-full shadow-theme border transition-colors"
                                    style="background: linear-gradient(to bottom right, <?= $aiConfig['bg_start'] ?>, <?= $aiConfig['bg_end'] ?>); border-color: <?= $aiConfig['border'] ?>;">
                                    <svg class="w-8 h-8 drop-shadow-sm" style="color: <?= $aiConfig['icon_color'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#<?= $aiConfig['icon'] ?>"></use>
                                    </svg>
                                </div>
                            </div>

                            <div class="relative z-10">
                                <p class="bg-clip-text font-bold text-lg text-transparent"
                                    style="background-image: linear-gradient(to right, <?= $aiConfig['text_gradient_start'] ?>, <?= $aiConfig['text_gradient_end'] ?>)">
                                    <?= h($aiConfig['title']) ?>
                                </p>
                                <p class="opacity-60 text-theme-text text-xs"><?= h($aiConfig['description']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fix: Use ID for JS access, remove Alpine binding to prevent conflicts on submit -->
                <input type="hidden" name="content" id="grinds_content_input" value="">
            </div>
        </div>

        <!-- Right Column (Settings) -->
        <div class="lg:top-6 lg:sticky space-y-6">
            <!-- Publish Box -->
            <div class="bg-theme-surface shadow-theme border border-theme-border rounded-theme overflow-hidden">
                <?php
                $status = $post['status'] ?? 'new';
                // Default (New)
                $stColor = 'text-theme-primary';
                $stDot = 'bg-theme-primary';
                $stLabel = _t('st_new');
                $stIcon = 'outline-plus-circle';

                if ($status === 'published') {
                    if ($isFuture) {
                        $stColor = 'text-theme-warning';
                        $stDot = 'bg-theme-warning';
                        $stLabel = _t('st_reserved');
                        $stIcon = 'outline-clock';
                    } else {
                        $stColor = 'text-theme-success';
                        $stDot = 'bg-theme-success animate-pulse';
                        $stLabel = _t('st_published');
                        $stIcon = 'outline-globe-alt';
                    }
                } elseif ($status === 'draft') {
                    $stColor = 'text-theme-text opacity-50';
                    $stDot = 'bg-theme-text opacity-30';
                    $stLabel = _t('st_draft');
                    $stIcon = 'outline-document';
                }
                ?>
                <div class="flex justify-between items-center px-5 py-4 border-theme-border border-b">
                    <div class="flex items-center gap-3">
                        <div class="flex justify-center items-center bg-theme-bg shadow-theme border border-theme-border rounded-full w-10 h-10">
                            <svg class="w-5 h-5 <?= $stColor ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') . '#' . $stIcon ?>"></use>
                            </svg>
                        </div>
                        <div>
                            <div class="opacity-40 mb-1.5 font-bold text-[10px] text-theme-text uppercase leading-none tracking-wider"><?= _t('lbl_status') ?></div>
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full <?= $stDot ?>"></span>
                                <span class="font-bold text-theme-text text-base leading-none"><?= $stLabel ?></span>
                            </div>
                        </div>
                    </div>
                    <?php if (($post['status'] ?? '') === 'published'): ?>
                        <a href="<?= h(site_url($post['slug'])) ?>" target="_blank" class="group flex items-center gap-2 hover:bg-theme-primary shadow-theme hover:shadow-theme px-3 py-1.5 border border-theme-primary/20 rounded-full font-bold text-theme-primary hover:text-theme-on-primary text-xs transition-all">
                            <span><?= _t('view_page') ?></span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-top-right-on-square"></use>
                            </svg>
                        </a>
                    <?php
                    endif; ?>
                </div>

                <div class="space-y-6 p-5">
                    <!-- Status hidden field: Updated by buttons via JS -->
                    <input type="hidden" name="status" x-model="postStatus">

                    <!-- Date Picker -->
                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <label class="opacity-70 font-bold text-theme-text text-xs"><?= _t('lbl_date') ?></label>
                        </div>

                        <div class="group relative">
                            <input type="text" name="published_at" id="published_at"
                                value="<?= !empty($post['published_at']) ? date('Y-m-d H:i', strtotime((string)$post['published_at'])) : date('Y-m-d H:i') ?>"
                                class="bg-theme-bg pl-9 group-hover:border-theme-primary/50 font-mono text-sm transition-colors cursor-pointer form-control">
                            <div class="top-1/2 left-3 absolute opacity-40 text-theme-text group-hover:text-theme-primary transition-colors -translate-y-1/2 pointer-events-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-calendar"></use>
                                </svg>
                            </div>
                        </div>

                        <div id="scheduled-message" class="<?= $isFuture ? '' : 'hidden' ?> mt-2 flex items-center gap-1.5 text-xs text-theme-warning font-bold bg-theme-warning/5 p-2 rounded-theme border border-theme-warning/20">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-clock"></use>
                            </svg>
                            <?= _t('msg_scheduled') ?>
                        </div>
                    </div>

                    <div class="gap-3 grid grid-cols-2">
                        <!-- Draft Button -->
                        <button type="submit"
                            @click="postStatus = 'draft'"
                            class="group flex-row justify-center items-center gap-2 hover:bg-theme-text/5 px-4 py-2.5 hover:border-theme-text/20 transition-all btn-secondary"
                            :disabled="isSubmitting || isUploading"
                            title="<?= h(_t('st_draft')) ?>">
                            <svg class="opacity-50 group-hover:opacity-100 w-4 h-4 text-theme-text transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-down-on-square"></use>
                            </svg>
                            <span class="opacity-70 group-hover:opacity-100 font-bold text-theme-text text-xs"><?= _t('st_draft') ?></span>
                        </button>

                        <button type="button"
                            @click="if(document.getElementById('post-form').reportValidity()) saveDraftAndPreview()"
                            :disabled="isSaving || isSubmitting || isUploading"
                            class="group relative flex-row justify-center items-center gap-2 hover:bg-theme-text/5 px-4 py-2.5 hover:border-theme-text/20 overflow-hidden transition-all btn-secondary"
                            title="<?= h(_t('preview')) ?>">

                            <div class="flex flex-row items-center gap-2" x-show="!isSaving">
                                <svg class="opacity-50 group-hover:opacity-100 w-4 h-4 text-theme-text transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
                                </svg>
                                <span class="opacity-70 group-hover:opacity-100 font-bold text-theme-text text-xs"><?= _t('preview') ?></span>
                            </div>

                            <div x-show="isSaving" class="absolute inset-0 flex justify-center items-center bg-theme-bg/80 backdrop-blur-[1px]" style="display: none;">
                                <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                                </svg>
                            </div>
                        </button>
                    </div>

                    <!-- Main Action (Publish/Update) -->
                    <button type="submit"
                        @click="postStatus = 'published'"
                        class="group relative shadow-theme py-2.5 w-full overflow-hidden transition-all btn-primary"
                        :disabled="isSubmitting || isUploading">

                        <div class="z-10 relative flex justify-center items-center gap-2 font-bold text-sm">
                            <span x-show="!isSubmitting" class="flex items-center gap-2">
                                <?php if (($post['status'] ?? '') === 'published'): ?>
                                    <input type="hidden" id="main-action-is-update" value="1">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                                    </svg>
                                    <span id="main-action-label"><?= $isFuture ? _t('action_schedule') : _t('update') ?></span>
                                <?php
                                else: ?>
                                    <input type="hidden" id="main-action-is-update" value="0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-globe-alt"></use>
                                    </svg>
                                    <span id="main-action-label"><?= $isFuture ? _t('action_schedule') : _t('action_publish') ?></span>
                                <?php
                                endif; ?>
                            </span>

                            <span x-show="isSubmitting" class="flex items-center gap-2" style="display: none;">
                                <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                                </svg>
                                <?= _t('js_loading') ?>
                            </span>
                        </div>
                    </button>

                    <!-- Footer Action (Delete) -->
                    <?php if ($action === 'edit'): ?>
                        <div class="mt-2 pt-4 border-theme-border border-t text-center">
                            <button type="button" onclick='movePostToTrash(<?= json_encode($post['id'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                class="flex justify-center items-center gap-1 opacity-40 hover:opacity-100 mx-auto font-bold text-theme-text hover:text-theme-danger text-xs transition-all">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                                </svg>
                                <?= _t('action_move_trash') ?>
                            </button>
                        </div>
                    <?php
                    endif; ?>
                </div>
            </div>

            <!-- Categories and tags. -->
            <div class="bg-theme-surface shadow-theme p-5 border border-theme-border rounded-theme" x-data='{ postType: <?= json_encode($post['type'] ?? 'post', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> }'>
                <div class="mb-4">
                    <label class="block opacity-70 mb-1 font-bold text-theme-text text-xs"><?= _t('col_type') ?></label>
                    <?php if (($post['type'] ?? '') === 'template'): ?>
                        <input type="text" value="<?= _t('btn_template') ?>" class="bg-theme-bg opacity-70 text-sm form-control" readonly>
                        <input type="hidden" name="type" value="template">
                    <?php
                    else: ?>
                        <select name="type" x-model="postType" class="text-sm form-control">
                            <option value="post"><?= _t('type_post') ?></option>
                            <option value="page"><?= _t('type_page') ?></option>
                        </select>
                    <?php
                    endif; ?>
                </div>
                <div class="mb-4" x-show="postType === 'post'" x-transition>
                    <label class="block opacity-70 mb-1 font-bold text-theme-text text-xs"><?= _t('lbl_category') ?></label>
                    <select name="category_id" class="text-sm form-control">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($post['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                        <?php
                        endforeach; ?>
                    </select>
                </div>
                <div x-data="{
                    tags: <?= htmlspecialchars(json_encode(array_values(array_filter(array_map('trim', explode(',', $currentTags ?? ''))))), ENT_QUOTES, 'UTF-8') ?>,
                    tagInput: '',
                    addTag() { this.tagInput.split(/[,、]/).forEach(t => { const val = t.trim(); if (val && !this.tags.includes(val)) this.tags.push(val); }); this.tagInput = ''; },
                    removeTag(index) { this.tags.splice(index, 1); }
                }">
                    <label class="block opacity-70 mb-1 font-bold text-theme-text text-xs"><?= _t('lbl_tags') ?></label>
                    <div class="flex flex-wrap items-center gap-1.5 bg-theme-bg px-3 py-2 border border-theme-border focus-within:ring-1 focus-within:ring-theme-primary w-full min-h-[42px] cursor-text rounded-theme transition-colors" @click="$refs.tagInput.focus()">
                        <template x-for="(tag, index) in tags" :key="index">
                            <span class="flex items-center gap-1 bg-theme-primary/10 px-2 py-0.5 border border-theme-primary/20 rounded-theme text-theme-primary text-xs font-bold">
                                <span x-text="tag"></span>
                                <button type="button" @click.stop="removeTag(index)" class="focus:outline-none hover:text-theme-danger p-0.5 -mr-1" aria-label='<?= h(_t('remove')) ?>'>&times;</button>
                            </span>
                        </template>
                        <input type="text" x-ref="tagInput" x-model="tagInput"
                            @keydown.enter.prevent="if(!$event.isComposing) addTag()"
                            @keydown.backspace="if(!$event.isComposing && tagInput === '' && tags.length > 0) removeTag(tags.length - 1)"
                            @blur="if(tagInput.trim() !== '') addTag()"
                            class="flex-1 bg-transparent p-0 border-none outline-none focus:ring-0 min-w-[60px] text-theme-text text-sm placeholder-theme-text/40" placeholder="<?= _t('ph_tags') ?>">
                    </div>
                    <input type="hidden" name="tags" :value="tags.join(',')">
                    <p class="opacity-50 mt-1 text-[10px] text-theme-text">
                        <?= _t('help_tags_comma') ?>
                    </p>
                </div>
            </div>

            <!-- Display Options -->
            <div class="bg-theme-surface shadow-theme p-5 border border-theme-border rounded-theme">
                <h3 class="block opacity-70 mb-3 pb-2 border-theme-border border-b font-bold text-theme-text text-xs"><?= _t('lbl_display_options') ?></h3>

                <!-- Page theme selector. -->
                <div class="mb-4">
                    <label class="block opacity-60 mb-1 font-bold text-theme-text text-xs">
                        <?= _t('lbl_page_theme') ?>
                    </label>
                    <select name="page_theme" class="text-sm form-control">
                        <option value="" <?= empty($post['page_theme']) ? 'selected' : '' ?>><?= _t('theme_default') ?></option>
                        <?php foreach ($available_themes as $dir => $name): ?>
                            <option value="<?= h($dir) ?>" <?= ($post['page_theme'] ?? '') === $dir ? 'selected' : '' ?>><?= h($name) ?></option>
                        <?php
                        endforeach; ?>
                    </select>
                    <p class="opacity-50 mt-1 text-[10px] text-theme-text">
                        <?= _t('help_page_theme') ?>
                    </p>
                </div>

                <div class="space-y-3 mb-6">
                    <label class="group flex items-center cursor-pointer">
                        <input type="checkbox" name="show_category" value="1" class="bg-theme-bg border-theme-border rounded w-5 h-5 text-theme-primary form-checkbox" <?= (!isset($post['show_category']) || $post['show_category']) ? 'checked' : '' ?>>
                        <span class="ml-2 text-theme-text group-hover:text-theme-primary text-sm transition-colors"><?= _t('lbl_show_category') ?></span>
                    </label>
                    <label class="group flex items-center cursor-pointer">
                        <input type="checkbox" name="show_date" value="1" class="bg-theme-bg border-theme-border rounded w-5 h-5 text-theme-primary form-checkbox" <?= (!isset($post['show_date']) || $post['show_date']) ? 'checked' : '' ?>>
                        <span class="ml-2 text-theme-text group-hover:text-theme-primary text-sm transition-colors"><?= _t('lbl_show_date') ?></span>
                    </label>
                    <label class="group flex items-center cursor-pointer">
                        <input type="checkbox" name="show_share_buttons" value="1" class="bg-theme-bg border-theme-border rounded w-5 h-5 text-theme-primary form-checkbox" <?= (!isset($post['show_share_buttons']) || $post['show_share_buttons']) ? 'checked' : '' ?>>
                        <span class="ml-2 text-theme-text group-hover:text-theme-primary text-sm transition-colors"><?= _t('lbl_show_share_buttons') ?></span>
                    </label>
                </div>

                <!-- TOC settings. -->
                <h3 class="block opacity-70 mb-3 pb-2 border-theme-border border-b font-bold text-theme-text text-xs"><?= _t('lbl_toc') ?></h3>
                <div class="mb-4">
                    <label class="group inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="show_toc" value="1" class="bg-theme-bg border-theme-border rounded focus:ring-theme-primary/20 w-5 h-5 text-theme-primary form-checkbox" <?= !empty($post['show_toc']) ? 'checked' : '' ?>>
                        <span class="ml-2 text-theme-text group-hover:text-theme-primary text-sm transition-colors"><?= _t('lbl_toc_show') ?></span>
                    </label>
                </div>
                <div class="mb-6">
                    <label class="block opacity-60 mb-1 font-bold text-theme-text text-xs"><?= _t('lbl_toc_title') ?></label>
                    <input type="text" name="toc_title" value="<?= h($post['toc_title'] ?? _t('ph_toc_title')) ?>" class="text-sm form-control" placeholder="<?= _t('ph_toc_title') ?>">
                </div>

                <!-- SEO & Thumbnail -->
                <h3 class="block opacity-70 mb-3 pb-2 border-theme-border border-b font-bold text-theme-text text-xs"><?= _t('lbl_seo') ?></h3>
                <div class="mb-4">
                    <label class="block opacity-60 mb-1 font-bold text-theme-text text-xs"><?= _t('lbl_seo_author') ?></label>
                    <input type="text" name="seo_author" value="<?= h($heroConfig['seo_author'] ?? '') ?>" class="text-sm form-control" placeholder="<?= _t('ph_seo_author') ?>">
                </div>
                <div class="mb-6">
                    <?php
                    $label = _t('lbl_social_card');
                    $name = 'thumbnail';
                    $value = $post['thumbnail'] ?? '';
                    $current_value_input_name = 'current_thumbnail';
                    $delete_name = 'delete_thumbnail';
                    $input_style = 'box';
                    $preview_class = 'w-full h-32 object-cover';
                    include __DIR__ . '/parts/image_uploader.php';
                    ?>
                </div>

                <!-- Hero Section (Collapsible) -->
                <div class="bg-theme-surface mb-6 p-4 border border-theme-border rounded-theme" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="flex justify-between items-center w-full text-left">
                        <span class="block opacity-70 font-bold text-theme-text text-xs cursor-pointer"><?= _t('lbl_hero') ?></span>
                        <svg class="opacity-50 w-4 h-4 text-theme-text transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
                        </svg>
                    </button>

                    <div x-show="open" x-collapse class="space-y-4 mt-4">
                        <!-- Hero image. -->
                        <div x-data='{ previewUrl: <?= json_encode(get_media_url($post['hero_image'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, isDeleted: false }'
                            @set-hero-image.window="if(!$event.detail.mobile) { previewUrl = $event.detail.url; isDeleted = false; document.getElementById('hero_image_url_input').value = $event.detail.url; }">
                            <label class="block opacity-60 mb-1 font-bold text-theme-text text-xs"><?= _t('hero_img') ?></label>
                            <div class="mb-2 bg-checker border border-theme-border rounded-theme w-full h-32 overflow-hidden" x-show="previewUrl && !isDeleted">
                                <img :src="previewUrl" class="w-full h-full object-cover">
                            </div>
                            <div class="flex flex-col gap-2">
                                <button type="button" @click="openLibrary('hero_image')" class="flex justify-center items-center hover:bg-theme-bg px-4 py-2 border border-theme-border rounded-theme w-full text-xs text-center transition-colors btn-secondary">
                                    <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
                                    </svg>
                                    <span><?= h(_t('btn_select_library')) ?></span>
                                </button>
                                <label class="px-3 py-1 rounded-theme w-full text-xs text-center cursor-pointer btn-secondary">
                                    <?= h(_t('select_file')) ?>
                                    <input type="file" name="hero_image" accept="image/*" class="sr-only" @change="const file = $event.target.files[0]; if(file){ const r = new FileReader(); r.onload = (e) => previewUrl = e.target.result; r.readAsDataURL(file); isDeleted = false; }">
                                </label>
                                <?php if (!empty($post['hero_image'])): ?>
                                    <label class="flex justify-center items-center bg-theme-danger/10 px-2 border border-theme-danger/30 rounded-theme text-theme-danger transition-colors cursor-pointer"
                                        :class="{'bg-theme-danger text-white border-theme-danger': isDeleted}"
                                        title="<?= h(_t('delete')) ?>">
                                        <input type="checkbox" name="delete_hero_image" value="1" class="hidden" x-model="isDeleted">
                                        <span x-show="!isDeleted">&times;</span>
                                        <span x-show="isDeleted" class="font-bold text-[10px]"><?= h(_t('btn_restore')) ?></span>
                                    </label>
                                    <input type="hidden" name="current_hero_image" value="<?= h($post['hero_image']) ?>">
                                <?php
                                endif; ?>
                                <input type="hidden" name="hero_image_url" id="hero_image_url_input">
                            </div>
                            <p x-show="isDeleted" class="mt-2 font-bold text-theme-danger text-xs"><?= _t('msg_deleted') ?></p>
                        </div>

                        <div x-data='{ previewUrl: <?= json_encode(get_media_url($heroConfig['mobile_image'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, isDeleted: false }'
                            class="pt-4 border-theme-border border-t"
                            @set-hero-image.window="if($event.detail.mobile) { previewUrl = $event.detail.url; isDeleted = false; document.getElementById('hero_image_mobile_url_input').value = $event.detail.url; }">
                            <label class="block opacity-60 mb-1 font-bold text-theme-text text-xs">
                                <svg class="inline mr-1 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-device-phone-mobile"></use>
                                </svg>
                                <?= _t('hero_img_mobile') ?>
                            </label>
                            <div class="mb-2 mx-auto bg-checker border border-theme-border rounded-theme w-24 h-32 overflow-hidden" x-show="previewUrl && !isDeleted">
                                <img :src="previewUrl" class="w-full h-full object-cover">
                            </div>
                            <div class="flex flex-col gap-2">
                                <button type="button" @click="openLibrary('hero_image_mobile')" class="flex justify-center items-center hover:bg-theme-bg px-4 py-2 border border-theme-border rounded-theme w-full text-xs text-center transition-colors btn-secondary">
                                    <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
                                    </svg>
                                    <span><?= h(_t('btn_select_library')) ?></span>
                                </button>
                                <label class="px-3 py-1 rounded-theme w-full text-xs text-center cursor-pointer btn-secondary">
                                    <?= h(_t('select_file')) ?>
                                    <input type="file" name="hero_image_mobile" accept="image/*" class="sr-only" @change="const file = $event.target.files[0]; if(file){ const r = new FileReader(); r.onload = (e) => previewUrl = e.target.result; r.readAsDataURL(file); isDeleted = false; }">
                                </label>
                                <?php if (!empty($heroConfig['mobile_image'])): ?>
                                    <label class="flex justify-center items-center bg-theme-danger/10 px-2 border border-theme-danger/30 rounded-theme text-theme-danger transition-colors cursor-pointer"
                                        :class="{'bg-theme-danger text-white border-theme-danger': isDeleted}"
                                        title="<?= h(_t('delete')) ?>">
                                        <input type="checkbox" name="delete_hero_image_mobile" value="1" class="hidden" x-model="isDeleted">
                                        <span x-show="!isDeleted">&times;</span>
                                        <span x-show="isDeleted" class="font-bold text-[10px]"><?= h(_t('btn_restore')) ?></span>
                                    </label>
                                    <input type="hidden" name="current_hero_image_mobile" value="<?= h($heroConfig['mobile_image']) ?>">
                                <?php
                                endif; ?>
                                <input type="hidden" name="hero_image_mobile_url" id="hero_image_mobile_url_input">
                            </div>
                        </div>

                        <!-- Hero layout. -->
                        <div>
                            <label class="block opacity-60 mb-1 font-bold text-theme-text text-xs"><?= _t('hero_layout') ?></label>
                            <select name="hero_layout" class="py-1 text-xs form-control">
                                <option value="standard" <?= ($heroConfig['layout'] ?? '') === 'standard' ? 'selected' : '' ?>><?= _t('hero_layout_std') ?></option>
                                <option value="wide" <?= ($heroConfig['layout'] ?? '') === 'wide' ? 'selected' : '' ?>><?= _t('hero_layout_wide') ?></option>
                                <option value="fullscreen" <?= ($heroConfig['layout'] ?? '') === 'fullscreen' ? 'selected' : '' ?>><?= _t('hero_layout_full') ?></option>
                            </select>
                        </div>

                        <!-- Hero text fields. -->
                        <div>
                            <label class="block opacity-60 mb-1 font-bold text-theme-text text-xs"><?= _t('hero_title') ?></label>
                            <input type="text" name="hero_title" value="<?= h($heroConfig['title'] ?? '') ?>" class="text-xs form-control">
                        </div>
                        <div>
                            <label class="block opacity-60 mb-1 font-bold text-theme-text text-xs"><?= _t('hero_sub') ?></label>
                            <textarea name="hero_subtext" rows="2" class="text-xs form-control"><?= h($heroConfig['subtext'] ?? '') ?></textarea>
                        </div>

                        <!-- Hero overlay. -->
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="hero_overlay" value="1" class="bg-theme-bg border-theme-border w-5 h-5 text-theme-primary form-checkbox" <?= !empty($heroConfig['overlay']) ? 'checked' : '' ?>>
                            <span class="ml-2 text-theme-text text-xs"><?= _t('hero_overlay') ?></span>
                        </label>

                        <label class="flex items-center mt-2 cursor-pointer">
                            <input type="checkbox" name="hero_fixed_bg" value="1" class="bg-theme-bg border-theme-border w-5 h-5 text-theme-primary form-checkbox" <?= !empty($heroConfig['fixed_bg']) ? 'checked' : '' ?>>
                            <span class="ml-2 text-theme-text text-xs"><?= _t('hero_fixed_bg') ?></span>
                        </label>

                        <!-- Hero button settings. -->
                        <div class="mt-2 pt-4 border-theme-border border-t"
                            x-data="heroSettings">

                            <div class="flex justify-between items-center opacity-70 mb-2 font-bold text-theme-text text-xs">
                                <span><?= _t('hero_cta') ?></span>
                                <button type="button" @click="addBtn()" class="flex items-center bg-transparent border-none text-[10px] text-theme-primary hover:underline cursor-pointer">
                                    <svg class="mr-1 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
                                    </svg>
                                    <?= _t('hero_btn_add') ?>
                                </button>
                            </div>

                            <!-- Hidden input for buttons. -->
                            <input type="hidden" name="hero_buttons_json" :value="JSON.stringify(buttons)">

                            <div class="space-y-4">
                                <template x-for="(btn, index) in buttons" :key="index">
                                    <div class="group relative bg-theme-bg/50 p-3 border border-theme-border rounded-theme">
                                        <!-- Delete button. -->
                                        <button type="button" @click="removeBtn(index)" class="top-1 right-1 p-2 flex items-center justify-center absolute opacity-30 hover:opacity-100 text-theme-text hover:text-theme-danger" title="<?= h(_t('hero_btn_remove')) ?>" aria-label="<?= h(_t('hero_btn_remove')) ?>">
                                            &times;
                                        </button>

                                        <div class="space-y-2 pr-4">
                                            <!-- Button text and style. -->
                                            <div class="flex gap-2">
                                                <input type="text" x-model="btn.text" class="flex-1 text-xs form-control" placeholder="<?= _t('hero_btn_text') ?>">
                                                <select x-model="btn.style" class="px-1 w-24 text-xs cursor-pointer form-control shrink-0">
                                                    <option value="primary"><?= _t('btn_style_primary') ?></option>
                                                    <option value="secondary"><?= _t('btn_style_secondary') ?></option>
                                                    <option value="white"><?= _t('btn_style_white') ?></option>
                                                    <option value="outline"><?= _t('btn_style_outline') ?></option>
                                                </select>
                                            </div>

                                            <!-- Button URL and page selector. -->
                                            <div class="flex flex-col gap-1">
                                                <input type="text" x-model="btn.url" class="font-mono text-xs form-control" placeholder="<?= _t('ph_https_path') ?>">

                                                <div class="relative" @click.outside="searchingIndex = null">
                                                    <input type="text"
                                                        class="opacity-70 py-1 text-[10px] text-theme-text form-control"
                                                        placeholder="<?= _t('hero_btn_select') ?> (<?= _t('ph_type_to_search') ?>)"
                                                        @focus="searchingIndex = index"
                                                        @input.debounce.300ms="searchContent($event.target.value)">

                                                    <div x-show="searchingIndex === index && searchResults.length > 0"
                                                        class="right-0 left-0 z-10 absolute bg-theme-surface shadow-theme mt-1 border border-theme-border rounded-theme max-h-40 overflow-y-auto">
                                                        <template x-for="result in searchResults">
                                                            <button type="button"
                                                                class="flex justify-between items-center hover:bg-theme-bg px-2 py-1 w-full text-[10px] text-left truncate"
                                                                @click="selectPage(index, result.url)">
                                                                <span x-text="result.title"></span>
                                                                <span x-text="window.grindsTranslations['type_' + result.type] || result.type" class="opacity-50 ml-2 px-1 border border-theme-border rounded-theme text-[9px] uppercase"></span>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <div x-show="buttons.length === 0" class="opacity-40 py-2 border border-theme-border border-dashed rounded-theme text-theme-text text-xs text-center">
                                    <?= h(_t('hero_no_buttons')) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SNS Preview -->
                <div class="mb-6">
                    <h3 class="block opacity-70 mb-2 font-bold text-theme-text text-xs"><?= _t('lbl_sns_preview') ?></h3>
                    <div class="mb-6 bg-theme-surface shadow-theme mx-auto border border-theme-border rounded-theme max-w-sm overflow-hidden">
                        <div class="flex justify-center items-center bg-checker aspect-video">
                            <img :src="seoImage" x-show="seoImage" class="w-full h-full object-cover" @error='$el.src = <?= json_encode(PLACEHOLDER_IMG, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                            <svg x-show="!seoImage" class="w-10 h-10 text-theme-text/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
                            </svg>
                        </div>
                        <!-- Preview Text -->
                        <div class="p-3 text-sm">
                            <span class="opacity-60 text-theme-text text-xs truncate" x-text="siteDomain"></span>
                            <p class="my-1 font-bold text-theme-text truncate" x-text='seoTitle || <?= json_encode(_t('lbl_title'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>'></p>
                            <p class="opacity-80 text-theme-text text-xs line-clamp-2" x-text='seoDesc || <?= json_encode(_t('lbl_desc'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>'></p>
                        </div>
                    </div>

                    <!-- Robots meta tags. -->
                    <div>
                        <h3 class="block opacity-70 mb-2 font-bold text-theme-text text-xs"><?= _t('lbl_robots') ?></h3>
                        <div class="space-y-3">
                            <label class="group flex items-center cursor-pointer">
                                <input type="checkbox" name="is_noindex" value="1" class="bg-theme-bg border-theme-border rounded focus:ring-theme-primary/20 w-5 h-5 text-theme-primary form-checkbox" <?= !empty($post['is_noindex']) ? 'checked' : '' ?>>
                                <span class="ml-2 text-theme-text group-hover:text-theme-primary text-sm transition-colors"><?= _t('lbl_noindex') ?></span>
                            </label>
                            <label class="group flex items-center cursor-pointer">
                                <input type="checkbox" name="is_nofollow" value="1" class="bg-theme-bg border-theme-border rounded focus:ring-theme-primary/20 w-5 h-5 text-theme-primary form-checkbox" <?= !empty($post['is_nofollow']) ? 'checked' : '' ?>>
                                <span class="ml-2 text-theme-text group-hover:text-theme-primary text-sm transition-colors"><?= _t('lbl_nofollow') ?></span>
                            </label>
                            <label class="group flex items-center cursor-pointer">
                                <input type="checkbox" name="is_noarchive" value="1" class="bg-theme-bg border-theme-border rounded focus:ring-theme-primary/20 w-5 h-5 text-theme-primary form-checkbox" <?= !empty($post['is_noarchive']) ? 'checked' : '' ?>>
                                <span class="ml-2 text-theme-text group-hover:text-theme-primary text-sm transition-colors"><?= _t('lbl_noarchive') ?></span>
                            </label>
                            <label class="group flex items-center cursor-pointer">
                                <input type="checkbox" name="is_hide_rss" value="1" class="bg-theme-bg border-theme-border rounded focus:ring-theme-primary/20 w-5 h-5 text-theme-primary form-checkbox" <?= !empty($post['is_hide_rss']) ? 'checked' : '' ?>>
                                <span class="ml-2 text-theme-text group-hover:text-theme-primary text-sm transition-colors"><?= _t('lbl_hide_rss') ?></span>
                            </label>
                            <label class="group flex items-center cursor-pointer">
                                <input type="checkbox" name="is_hide_llms" value="1" class="bg-theme-bg border-theme-border rounded focus:ring-theme-primary/20 w-5 h-5 text-theme-primary form-checkbox" <?= !empty($post['is_hide_llms']) ? 'checked' : '' ?>>
                                <span class="ml-2 text-theme-text group-hover:text-theme-primary text-sm transition-colors"><?= _t('lbl_hide_llms') ?></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
    </form>

    <!-- Mobile Floating Action Bar -->
    <div x-data="{ keyboardOpen: false }"
        @focusin.window="if(['INPUT', 'TEXTAREA', 'SELECT', 'CONTENTEDITABLE'].includes($event.target.tagName) || $event.target.isContentEditable) keyboardOpen = true"
        @focusout.window="keyboardOpen = false"
        :class="(keyboardOpen || inserterOpen || templateModalOpen || (typeof mediaModalOpen !== 'undefined' && mediaModalOpen)) ? 'translate-y-full opacity-0 pointer-events-none' : 'translate-y-0 opacity-100'"
        class="transition-all duration-200 lg:hidden fixed bottom-0 left-0 right-0 bg-theme-surface/95 backdrop-blur-md border-t border-theme-border p-3 z-[60] flex justify-between gap-2 shadow-[0_-4px_10px_rgba(0,0,0,0.05)] pb-[calc(0.75rem+env(safe-area-inset-bottom))]">
        <button type="button" @click="if(document.getElementById('post-form').reportValidity()) saveDraftAndPreview()" :disabled="isSaving || isSubmitting || isUploading" class="flex-1 py-3 bg-theme-bg border border-theme-border text-theme-text rounded-theme font-bold text-sm flex items-center justify-center gap-1 shadow-sm transition-colors hover:bg-theme-surface disabled:opacity-50 disabled:cursor-not-allowed">
            <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
            </svg>
            <?= _t('preview') ?>
        </button>

        <button type="button" @click="postStatus = 'draft'; document.getElementById('post-form').requestSubmit();" :disabled="isSubmitting || isUploading" class="flex-1 py-3 bg-theme-bg border border-theme-border text-theme-text rounded-theme font-bold text-sm flex items-center justify-center gap-1 shadow-sm transition-colors hover:bg-theme-surface disabled:opacity-50 disabled:cursor-not-allowed">
            <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-down-on-square"></use>
            </svg>
            <?= _t('st_draft') ?>
        </button>

        <button type="button" @click="postStatus = 'published'; document.getElementById('post-form').requestSubmit();" :disabled="isSubmitting || isUploading" class="flex-1 py-3 bg-theme-primary text-theme-on-primary rounded-theme font-bold text-sm flex items-center justify-center gap-1 shadow-theme transition-all hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed">
            <?php if (($post['status'] ?? '') === 'published'): ?>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                </svg>
                <?= _t('update') ?>
            <?php else: ?>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-globe-alt"></use>
                </svg>
                <?= _t('action_publish') ?>
            <?php endif; ?>
        </button>
    </div>

    <!-- Inserter Modal -->
    <template x-teleport="body">
        <div x-show="inserterOpen" class="z-70 fixed inset-0 flex justify-center items-center p-4" style="display: none;" @keydown.escape.window="inserterOpen = false" x-cloak>
            <div class="fixed inset-0 skin-modal-overlay backdrop-blur-sm transition-opacity" @click="inserterOpen = false"></div>
            <div class="z-10 relative flex flex-col bg-theme-surface shadow-theme border border-theme-border rounded-theme w-full max-w-2xl max-h-[80vh] overflow-hidden animate-in duration-200 fade-in zoom-in">
                <div class="bg-theme-bg/50 p-4 border-theme-border border-b">
                    <div class="relative">
                        <svg class="top-1/2 left-3 absolute opacity-50 w-5 h-5 text-theme-text -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
                        </svg>
                        <input x-ref="blockSearch" x-model="blockSearchTerm" type="text" class="bg-theme-surface py-3 pr-4 pl-10 border border-theme-border focus:border-theme-primary rounded-theme outline-none focus:ring-2 focus:ring-theme-primary/50 w-full text-theme-text text-sm transition-all placeholder-theme-text/50" placeholder="<?= _t('ph_search_blocks') ?>">
                        <button @click="inserterOpen = false" class="top-1/2 right-3 absolute bg-theme-bg opacity-50 px-1.5 py-0.5 border border-theme-border rounded-theme text-theme-text text-xs -translate-y-1/2">ESC</button>
                    </div>
                </div>
                <div class="flex-1 p-4 overflow-y-auto custom-scrollbar">
                    <template x-for="(cat, catKey) in blockLibrary" :key="catKey">
                        <div class="mb-6" x-show="!blockSearchTerm || JSON.stringify(cat).toLowerCase().includes(blockSearchTerm.toLowerCase())">
                            <h4 class="opacity-50 mb-3 ml-1 font-bold text-theme-text text-xs uppercase tracking-wider" x-text="cat.label"></h4>
                            <div class="gap-3 grid grid-cols-2 sm:grid-cols-3">
                                <template x-for="(item, key) in cat.items" :key="key">
                                    <button type="button" x-show="(!blockSearchTerm || item.label.toLowerCase().includes(blockSearchTerm.toLowerCase()) || key.includes(blockSearchTerm.toLowerCase())) && (key !== 'password_protect' || !blocks.some(b => b.type === 'password_protect'))" @click="addBlock(key); $dispatch('announce', 'Block added: ' + item.label); inserterOpen = false; blockSearchTerm = ''" class="group flex flex-col items-center bg-theme-bg/30 hover:bg-theme-surface hover:shadow-theme p-4 border border-theme-border hover:border-theme-primary rounded-theme h-full text-center transition-all">
                                        <div class="flex justify-center items-center bg-theme-surface mb-2 border border-theme-border rounded-full w-10 h-10 group-hover:text-theme-primary group-hover:scale-110 transition-transform">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <use :href='<?= json_encode(grinds_asset_url('assets/img/sprite.svg') . '#', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?> + item.icon'></use>
                                            </svg>
                                        </div>
                                        <span class="mb-1 font-bold text-theme-text group-hover:text-theme-primary text-sm" x-text="item.label"></span>
                                        <span class="opacity-60 text-[10px] text-theme-text leading-tight" x-text="item.desc"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>

    <!-- Template Modal -->
    <template x-teleport="body">
        <div x-show="templateModalOpen" class="z-60 fixed inset-0 flex justify-center items-center p-4" style="display: none;" x-cloak>
            <div class="fixed inset-0 skin-modal-overlay backdrop-blur-sm" @click="templateModalOpen = false"></div>
            <div class="z-10 relative bg-theme-surface shadow-theme p-6 border border-theme-border rounded-theme w-full max-w-md max-h-[90vh] overflow-y-auto custom-scrollbar">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-theme-text"><?= _t('tpl_manager') ?></h3>
                    <button type="button" @click="templateModalOpen = false" class="opacity-50 hover:opacity-100 p-2 flex items-center justify-center text-theme-text text-2xl leading-none">&times;</button>
                </div>
                <div class="flex mb-4 border-theme-border border-b">
                    <button @click="tplTab = 'load'" :class="{'border-b-2 border-theme-primary text-theme-primary': tplTab === 'load', 'text-theme-text opacity-60': tplTab !== 'load'}" class="flex-1 pb-2 font-bold text-sm"><?= _t('tpl_load') ?></button>
                    <button @click="tplTab = 'save'" :class="{'border-b-2 border-theme-primary text-theme-primary': tplTab === 'save', 'text-theme-text opacity-60': tplTab !== 'save'}" class="flex-1 pb-2 font-bold text-sm"><?= _t('tpl_save') ?></button>
                </div>
                <div x-show="tplTab === 'load'">
                    <div x-show="templates.length === 0" class="flex flex-col justify-center items-center py-10 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
                        <div class="flex justify-center items-center w-12 h-12 mb-3 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-duplicate"></use>
                            </svg>
                        </div>
                        <h3 class="font-bold text-theme-text text-sm opacity-80"><?= _t('tpl_no_templates') ?></h3>
                    </div>
                    <ul class="space-y-2 max-h-60 overflow-y-auto">
                        <template x-for="tpl in templates" :key="tpl.id">
                            <li>
                                <button type="button" @click="loadTemplate(tpl.id)" class="group flex justify-between items-center hover:bg-theme-bg px-3 py-2 border border-theme-border rounded-theme w-full hover:text-theme-primary text-sm text-left transition">
                                    <span x-text="tpl.title"></span>
                                    <span class="bg-theme-primary/10 opacity-0 group-hover:opacity-100 px-2 py-0.5 rounded-theme text-[10px] text-theme-primary transition"><?= _t('tpl_insert') ?></span>
                                </button>
                            </li>
                        </template>
                    </ul>
                </div>
                <div x-show="tplTab === 'save'">
                    <div class="mb-4">
                        <label class="block mb-2 font-bold text-theme-text text-xs"><?= _t('tpl_name') ?></label>
                        <input type="text" x-model="newTemplateName" @keydown.enter.prevent class="text-sm form-control" placeholder="<?= _t('tpl_ph_name') ?>">
                    </div>
                    <button type="button" @click.prevent.stop="saveCurrentAsTemplate()" class="py-2 w-full text-sm btn-primary" :disabled="!newTemplateName"><?= _t('tpl_save_btn') ?></button>
                </div>
            </div>
        </div>
    </template>

</div><!-- End Alpine scope. -->

<script>
    // Async Link Checker on Save
    document.addEventListener('DOMContentLoaded', () => {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('saved')) {
            const postId = <?= $post['id'] ?? 0 ?>;
            if (postId) {
                fetch('link_checker.php?action=scan&type=posts&id=' + postId)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.broken_links.length > 0) {
                            const count = data.broken_links.length;
                            const msg = <?= json_encode(_t('msg_published_broken_links')) ?>.replace('%d', count);
                            window.ToastManager.show({
                                type: 'warning',
                                duration: 10000,
                                position: 'bottom-24', // Positioned higher to avoid overlap with 'Saved' toast
                                customHtml: `
                                    <p class="text-sm font-bold text-theme-warning"><?= _t('warning') ?></p>
                                    <p class="mt-1 text-sm text-theme-text opacity-80">${msg}</p>
                                    <div class="mt-2">
                                        <a href="link_checker.php" class="text-xs font-bold text-theme-primary hover:underline"><?= _t('view_details') ?></a>
                                    </div>
                                `
                            });
                        }
                    })
                    .catch(err => console.error('Link check failed:', err));
            }
        }
    });

    function movePostToTrash(id) {
        if (confirm(<?= json_encode(_t('confirm_delete')) ?>)) {
            window.grindsBypassUnload = true;
            const f = document.createElement('form');
            f.method = 'POST';
            const addInput = (name, value) => {
                const i = document.createElement('input');
                i.type = 'hidden';
                i.name = name;
                i.value = value;
                f.appendChild(i);
            };
            addInput('csrf_token', window.grindsCsrfToken);
            addInput('bulk_action', 'trash');
            addInput('target_id', id);
            document.body.appendChild(f);
            f.submit();
        }
    }
</script>

<?php include __DIR__ . '/parts/media_picker.php'; ?>
