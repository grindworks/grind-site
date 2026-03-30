<?php

/**
 * display.php
 * Renders the interface for managing display settings.
 */
if (!defined('GRINDS_APP')) exit; ?>
<div class="space-y-6 bg-theme-surface shadow-theme p-4 sm:p-6 border border-theme-border rounded-theme">
  <form method="post" class="warn-on-unsaved" x-data="{ isSubmitting: false }" @submit="setTimeout(() => isSubmitting = true, 10)">
    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
    <input type="hidden" name="action" value="update_display">

    <div class="mb-6 sm:mb-8">
      <h3 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-xl">
        <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-tv"></use>
        </svg>
        <?= _t('tab_display') ?>
      </h3>
      <p class="opacity-60 ml-0 sm:ml-7 text-theme-text text-sm leading-relaxed"><?= _t('st_display_desc') ?></p>
    </div>

    <div class="gap-6 grid grid-cols-1 md:grid-cols-2">
      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_per_page') ?></span>
        <input type="number" name="posts_per_page" value="<?= h($opt['per_page']) ?>" min="1" max="100" class="form-control">
      </label>
      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_title_fmt') ?></span>
        <select name="title_format" class="form-control">
          <option value="{page_title} | {site_name}" <?= $opt['title_fmt'] == '{page_title} | {site_name}' ? 'selected' : '' ?>>{page_title} | {site_name}</option>
          <option value="{page_title} - {site_name}" <?= $opt['title_fmt'] == '{page_title} - {site_name}' ? 'selected' : '' ?>>{page_title} - {site_name}</option>
          <option value="{page_title}" <?= $opt['title_fmt'] == '{page_title}' ? 'selected' : '' ?>>{page_title}</option>
          <option value="{site_name} | {page_title}" <?= $opt['title_fmt'] == '{site_name} | {page_title}' ? 'selected' : '' ?>>{site_name} | {page_title}</option>
        </select>
      </label>
      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_date_fmt') ?></span>
        <select name="date_format" class="form-control">
          <option value="Y-m-d" <?= $opt['date_fmt'] === 'Y-m-d' ? 'selected' : '' ?>>Y-m-d</option>
          <option value="Y/m/d" <?= $opt['date_fmt'] === 'Y/m/d' ? 'selected' : '' ?>>Y/m/d</option>
          <option value="F j, Y" <?= $opt['date_fmt'] === 'F j, Y' ? 'selected' : '' ?>>F j, Y</option>
          <option value="Y年m月d日" <?= $opt['date_fmt'] === 'Y年m月d日' ? 'selected' : '' ?>>Y年m月d日</option>
        </select>
      </label>
      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_lang') ?></span>
        <select name="site_lang" class="form-control">
          <?php
          $langDir = ROOT_PATH . '/lib/lang/';
          if (is_dir($langDir)):
            foreach (glob($langDir . '*.php') as $file):
              $code = basename($file, '.php');
              $label = match (strtolower($code)) {
                'en' => _t('lang_en') ?: 'English',
                'ja' => _t('lang_ja') ?: 'Japanese',
                'de' => 'Deutsch',
                'es' => 'Español',
                'fr' => 'Français',
                'it' => 'Italiano',
                'pt' => 'Português',
                'pt-br', 'pt_br' => 'Português (Brasil)',
                'nl' => 'Nederlands',
                'ru' => 'Русский',
                'tr' => 'Türkçe',
                'zh', 'zh-cn', 'zh_cn' => '中文 (简体)',
                'zh-tw', 'zh_tw' => '中文 (繁體)',
                'ko' => '한국어',
                'vi' => 'Tiếng Việt',
                'th' => 'ไทย',
                'ar' => 'العربية',
                default => strtoupper($code)
              };
          ?>
              <option value="<?= h($code) ?>" <?= $opt['lang'] === $code ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php
            endforeach;
          endif; ?>
        </select>
      </label>
      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_timezone') ?></span>
        <select name="timezone" class="form-control">
          <?php foreach (DateTimeZone::listIdentifiers() as $tz): ?>
            <option value="<?= $tz ?>" <?= $opt['timezone'] === $tz ? 'selected' : '' ?>><?= $tz ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <div class="flex justify-end mt-8 pt-6 border-theme-border border-t">
      <button type="submit" :disabled="isSubmitting" class="flex justify-center items-center gap-2 shadow-theme px-6 py-2.5 rounded-theme w-full sm:w-auto font-bold text-sm transition-all disabled:opacity-70 disabled:cursor-not-allowed btn-primary">
        <svg x-show="isSubmitting" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
        </svg>
        <span x-text="isSubmitting ? '...' : '<?= _t('btn_save_settings') ?>'"><?= _t('btn_save_settings') ?></span>
      </button>
    </div>
  </form>
</div>
