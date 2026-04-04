<?php

/**
 * image_uploader.php
 * Renders an image upload component with preview.
 */
if (!defined('GRINDS_APP'))
    exit;

// Set default values
$label = $label ?? '';
$name = $name ?? 'image';
$value = $value ?? '';
$current_value_input_name = $current_value_input_name ?? 'current_' . $name;
$delete_name = $delete_name ?? 'delete_' . $name;
$accept = $accept ?? 'image/*';
$note = $note ?? '';
$input_style = $input_style ?? 'box';
$preview_class = $preview_class ?? 'w-full h-40 object-cover rounded-theme border border-theme-border';
$preview_bg_class = $preview_bg_class ?? 'bg-checker';
$preview_container_class = $preview_container_class ?? 'mb-2';
$preview_attrs = $preview_attrs ?? '';
$extra_attrs = $extra_attrs ?? '';

// Resolve preview URL
$previewUrl = get_media_url($value);
?>

<div x-data="{
    previewUrl: '<?= h($previewUrl) ?>',
    isDeleted: false,
    handleFile(e) {
        const file = e.target.files[0];
        if (file) {
            // Revoke old URL to prevent memory leaks
            if (this.previewUrl && this.previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(this.previewUrl);
            }
            this.previewUrl = URL.createObjectURL(file);
            this.isDeleted = false;
            // Clear URL input when file is selected
            if($refs.urlInput) $refs.urlInput.value = '';
        }
    },
    openPicker() {
        window.dispatchEvent(new CustomEvent('open-media-picker', {
            detail: {
                callback: (file) => {
                    this.previewUrl = file.url;
                    this.isDeleted = false;
                    if($refs.urlInput) $refs.urlInput.value = file.url;
                    // Clear file input
                    if($refs.fileInput) $refs.fileInput.value = '';
                }
            }
        }));
    }
}" class="w-full">

    <?php if ($label): ?>
        <label class="block mb-2 font-bold text-theme-text text-sm">
            <?= h($label) ?>
        </label>
    <?php
    endif; ?>

    <div class="flex flex-col gap-2">
        <div class="<?= $preview_container_class ?> relative group" x-show="previewUrl && !isDeleted">
            <img :src="previewUrl" class="<?= $preview_class ?> shadow-theme <?= $preview_bg_class ?>"
                alt="<?= _t('lbl_preview') ?>" <?= $preview_attrs ?>>
            <button type="button" @click="isDeleted = true; $refs.urlInput.value=''; $refs.fileInput.value='';"
                class="absolute top-2 right-2 bg-theme-surface/90 hover:bg-theme-danger text-theme-text hover:text-white p-1.5 rounded-full shadow-theme transition-colors"
                title="<?= h(_t('delete')) ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                </svg>
            </button>
        </div>

        <?php if ($input_style === 'box'): ?>
            <div x-show="!previewUrl && !isDeleted"
                class="<?= $preview_class ?> <?= $preview_bg_class ?> bg-clip-content flex items-center justify-center text-theme-text/20 border-theme-border border-dashed border-2 p-1">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
                </svg>
            </div>
        <?php
        endif; ?>

        <div class="flex flex-col gap-2">
            <input type="hidden" name="<?= h($name) ?>_url" x-ref="urlInput" value="">

            <button type="button" @click="openPicker()"
                class="flex justify-center items-center hover:bg-theme-bg px-4 py-2 border border-theme-border rounded-theme w-full text-xs text-center transition-colors btn-secondary">
                <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
                </svg>
                <span>
                    <?= _t('btn_select_library') ?>
                </span>
            </button>

            <label
                class="flex justify-center items-center hover:bg-theme-bg px-4 py-2 border border-theme-border rounded-theme w-full text-xs text-center transition-colors cursor-pointer btn-secondary">
                <svg class="mr-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-up-tray"></use>
                </svg>
                <span>
                    <?= _t('upload') ?>
                </span>
                <input type="file" x-ref="fileInput" name="<?= h($name) ?>" accept="<?= h($accept) ?>" class="hidden"
                    @change="handleFile" <?= $extra_attrs ?>>
            </label>

            <?php if (!empty($value)): ?>
                <div>
                    <input type="checkbox" name="<?= h($delete_name) ?>" value="1" class="hidden" x-model="isDeleted">
                    <input type="hidden" name="<?= h($current_value_input_name) ?>" value="<?= h($value) ?>"
                        :disabled="isDeleted">
                </div>

                <button type="button" @click="isDeleted = false" x-show="isDeleted"
                    class="w-full text-xs font-bold text-theme-primary hover:underline flex justify-center items-center gap-1 py-2"
                    style="display: none;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-uturn-left"></use>
                    </svg>
                    <?= _t('btn_restore') ?>
                </button>
            <?php
            endif; ?>
        </div>

        <?php if ($note): ?>
            <p class="text-xs text-theme-text opacity-60 mt-1">
                <?= h($note) ?>
            </p>
        <?php
        endif; ?>
        <p class="text-[10px] text-theme-text opacity-40 mt-0.5 font-mono" x-show="window.grindsUploadMax" x-cloak>
            <span x-text="'Max: ' + Math.max(1, Math.floor(window.grindsUploadMax / 1048576)) + 'MB'"></span>
        </p>
    </div>
</div>
<?php
unset($label, $name, $value, $current_value_input_name, $delete_name, $accept, $note, $input_style, $preview_class, $preview_bg_class, $preview_container_class, $preview_attrs, $extra_attrs, $previewUrl);
?>
