<?php

/**
 * media_picker.php
 * Renders the media picker modal for selecting images.
 */
if (!defined('GRINDS_APP'))
    exit;

$searchPlaceholder = (function_exists('get_option') && get_option('site_lang') === 'ja') ? '検索 (ファイル名, タイトル, Alt, タグ...)' : 'Search (Filename, Title, Alt, Tags...)';
?>
<div x-data="{
    open: false,
    callback: null,
    mediaFiles: [],
    loading: false,
    page: 1,
    hasMore: false,
    keyword: '',
    type: 'all',

    init() {
        window.addEventListener('open-media-picker', (e) => {
            this.open = true;
            this.callback = e.detail.callback;
            this.type = e.detail.type || 'all';
            this.loadMedia(1);
        });

        this.$nextTick(() => {
            // Bind intersection observer root to scroll container for reliable detection
            const container = this.$refs.scrollContainer;
            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting && this.hasMore && !this.loading) {
                    this.loadMedia(this.page + 1);
                }
            }, { root: container, rootMargin: '100px' });

            if (this.$refs.loadMoreTrigger) observer.observe(this.$refs.loadMoreTrigger);
        });
    },

    async loadMedia(p = 1) {
        this.loading = true;
        this.page = p;
        try {
            // GrindsMediaApi is guaranteed to be loaded, so we call it directly.
            const data = await GrindsMediaApi.list(p, { keyword: this.keyword, type: this.type });
            if (data && data.success) {
                if (p === 1) this.mediaFiles = data.files;
                else this.mediaFiles = [...this.mediaFiles, ...data.files];
                this.hasMore = data.has_more;
            }
        } catch (e) {
            console.error(e);
        }
        this.loading = false;
    },

    select(file) {
        if (this.callback) {
            this.callback(file);
        }
        this.open = false;
    }
}" x-effect="if (typeof window.toggleScrollLock === 'function') window.toggleScrollLock(open)" @keydown.escape.window="open = false" x-show="open" class="z-60 fixed inset-0 flex justify-center items-center p-4"
    style="display: none;" x-cloak>

    <div class="fixed inset-0 skin-modal-overlay backdrop-blur-sm" @click="open = false"></div>

    <div
        class="z-10 relative flex flex-col bg-theme-surface shadow-theme border border-theme-border rounded-theme w-full max-w-3xl h-[calc(100dvh-2rem)] md:h-[80vh]">
        <div
            class="flex justify-between items-center bg-theme-bg/50 p-4 border-theme-border border-b rounded-t-theme shrink-0">
            <h3 class="font-bold text-theme-text">
                <?= _t('title_media_library') ?>
            </h3>
            <div class="flex items-center gap-2">
                <input type="text" x-model="keyword" @input.debounce.500ms="loadMedia(1)"
                    class="bg-theme-bg px-2 py-1 border border-theme-border rounded-theme text-xs text-theme-text w-full sm:w-64"
                    placeholder="<?= $searchPlaceholder ?>">
                <button @click="open = false"
                    class="text-theme-text hover:text-theme-primary text-2xl leading-none">&times;</button>
            </div>
        </div>

        <!-- Container for modal scroll tracking -->
        <div x-ref="scrollContainer" class="flex-1 bg-theme-bg/30 p-4 overflow-y-auto custom-scrollbar">
            <div x-show="loading && page === 1" class="flex justify-center py-10">
                <svg class="w-8 h-8 text-theme-primary animate-spin" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
                </svg>
            </div>

            <div x-show="!loading && mediaFiles.length === 0" class="py-10 text-center text-theme-text opacity-50">
                <?= _t('msg_no_media') ?>
            </div>

            <div class="gap-4 grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5">
                <template x-for="file in mediaFiles" :key="file.id">
                    <div @click="select(file)"
                        class="group relative bg-theme-bg border border-theme-border hover:border-theme-primary rounded-theme hover:ring-2 hover:ring-theme-primary/50 aspect-square overflow-hidden cursor-pointer">
                        <template x-if="file.is_image">
                            <div class="w-full h-full bg-checker">
                                <img :src="file.thumbnail_url || file.url"
                                    loading="lazy" decoding="async" class="w-full h-full object-contain group-hover:scale-110 transition-transform">
                            </div>
                        </template>
                        <template x-if="!file.is_image && file.file_type && file.file_type.startsWith('video/')">
                            <div class="flex flex-col justify-center items-center bg-theme-bg/80 w-full h-full text-theme-primary">
                                <svg class="mb-1 w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-film">
                                    </use>
                                </svg>
                                <span class="font-bold text-[10px] uppercase truncate px-2"
                                    x-text="file.filename.split('.').pop()"></span>
                            </div>
                        </template>
                        <template x-if="!file.is_image && file.file_type && file.file_type.startsWith('audio/')">
                            <div
                                class="flex flex-col justify-center items-center bg-theme-bg/80 w-full h-full text-theme-primary">
                                <svg class="mb-1 w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-musical-note">
                                    </use>
                                </svg>
                                <span class="font-bold text-[10px] uppercase truncate px-2"
                                    x-text="file.filename.split('.').pop()"></span>
                            </div>
                        </template>
                        <template
                            x-if="!file.is_image && (!file.file_type || (!file.file_type.startsWith('video/') && !file.file_type.startsWith('audio/')))">
                            <div class="flex flex-col justify-center items-center w-full h-full text-theme-text/50">
                                <svg class="mb-1 w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text">
                                    </use>
                                </svg>
                                <span class="font-bold text-[10px] uppercase truncate px-2"
                                    x-text="file.filename.split('.').pop()"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <div x-show="hasMore" x-ref="loadMoreTrigger" class="mt-4 pb-8 text-center">
                <button @click="loadMedia(page + 1)" class="btn-secondary px-4 py-2 text-xs" :disabled="loading">
                    <span x-show="!loading">
                        <?= _t('load_more') ?>
                    </span>
                    <span x-show="loading">...</span>
                </button>
            </div>
        </div>
    </div>
</div>
