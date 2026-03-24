<?php

/**
 * toast.php
 *
 * Render success and error toast notifications.
 */
if (!defined('GRINDS_APP')) exit;

?>

<?php if (!empty($message)): ?>
  <!-- Success toast -->
  <div x-data="{
         show: true,
         init() { setTimeout(() => this.show = false, 4000) }
       }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 translate-y-4 scale-95"
    class="right-4 bottom-4 z-[100] fixed w-full max-w-sm cursor-pointer"
    @click="show = false"
    x-cloak>

    <div class="flex items-start bg-theme-surface shadow-theme p-4 border-theme-success border-l-4 rounded-r-theme ring-1 ring-black/5">
      <div class="flex-shrink-0">
        <svg class="w-6 h-6 text-theme-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-check-circle"></use>
        </svg>
      </div>
      <div class="flex-1 ml-3 pt-0.5 w-0">
        <p class="font-bold text-theme-text text-sm"><?= _t('success') ?></p>
        <p class="opacity-80 mt-1 text-theme-text text-sm"><?= h($message) ?></p>
      </div>
      <div class="flex flex-shrink-0 ml-4">
        <button class="inline-flex bg-transparent opacity-40 hover:opacity-100 rounded-theme focus:outline-none text-theme-text">
          <span class="sr-only">Close</span>
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
          </svg>
        </button>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <!-- Error toast -->
  <div x-data="{ show: true }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    class="right-4 bottom-4 z-[100] fixed w-full max-w-sm cursor-pointer"
    x-cloak>

    <div class="flex items-start bg-theme-surface shadow-theme p-4 border-theme-danger border-l-4 rounded-r-theme ring-1 ring-black/5">
      <div class="flex-shrink-0">
        <svg class="w-6 h-6 text-theme-danger" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-circle"></use>
        </svg>
      </div>
      <div class="flex-1 ml-3 pt-0.5 w-0">
        <p class="font-bold text-theme-danger text-sm"><?= _t('error') ?></p>
        <p class="opacity-80 mt-1 text-theme-text text-sm"><?= h($error) ?></p>
      </div>
      <div class="flex flex-shrink-0 ml-4">
        <button @click="show = false" class="inline-flex bg-transparent opacity-40 hover:opacity-100 rounded-theme focus:outline-none text-theme-text">
          <span class="sr-only">Close</span>
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-mark"></use>
          </svg>
        </button>
      </div>
    </div>
  </div>
<?php endif; ?>
