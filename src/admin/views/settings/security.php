<?php

/**
 * security.php
 * Renders the interface for managing security settings.
 */
if (!defined('GRINDS_APP')) exit; ?>

<form method="post" action="settings.php" class="warn-on-unsaved" x-data="{ isSubmitting: false }" @submit="setTimeout(() => isSubmitting = true, 10)">
  <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
  <input type="hidden" name="action" value="update_security">

  <div class="space-y-6 bg-theme-surface shadow-theme p-4 sm:p-6 border border-theme-border rounded-theme">
    <div class="mb-6 sm:mb-8">
      <h3 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-xl">
        <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-shield-check"></use>
        </svg>
        <?= _t('tab_security') ?>
      </h3>
      <p class="opacity-60 ml-0 sm:ml-7 text-theme-text text-sm leading-relaxed"><?= _t('st_security_desc') ?></p>
    </div>

    <div class="gap-6 grid grid-cols-1 md:grid-cols-2">
      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_sess_timeout') ?></span>
        <input type="number" name="session_timeout" value="<?= h($opt['sess_timeout']) ?>" min="1" class="form-control">
      </label>

      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_max_attempts') ?></span>
        <input type="number" name="security_max_attempts" value="<?= h($opt['sec_attempts']) ?>" min="1" class="form-control">
      </label>

      <label class="block">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_lockout_time') ?></span>
        <input type="number" name="security_lockout_time" value="<?= h($opt['sec_time']) ?>" min="1" class="form-control">
      </label>
    </div>

    <div class="pt-6 border-theme-border border-t">
      <label class="flex items-start cursor-pointer group">
        <div class="flex items-center h-5">
          <input type="checkbox" name="secure_preview_mode" value="1" class="bg-theme-bg border-theme-border rounded focus:ring-theme-primary/20 w-5 h-5 text-theme-primary form-checkbox" <?= $opt['secure_preview'] ? 'checked' : '' ?>>
        </div>
        <div class="ml-3 text-sm">
          <span class="font-bold text-theme-text group-hover:text-theme-primary transition-colors"><?= _t('st_secure_preview') ?></span>
          <p class="text-theme-text opacity-60 text-xs mt-1 leading-relaxed"><?= _t('st_secure_preview_desc') ?></p>
        </div>
      </label>

      <div class="block mt-6 pl-0 sm:pl-8" x-data="{ showPass: false }">
        <label for="preview_shared_password" class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_preview_password') ?></label>
        <div class="relative w-full sm:w-48">
          <input :type="showPass ? 'text' : 'password'" name="preview_shared_password" id="preview_shared_password" value="<?= h($opt['preview_password'] ?? '') ?>" class="form-control w-full pr-10 font-mono" placeholder="1234" autocomplete="new-password">
          <button type="button" @click="showPass = !showPass" class="right-0 absolute inset-y-0 flex items-center opacity-50 hover:opacity-100 px-3 focus:outline-none text-theme-text" tabindex="-1">
            <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
            </svg>
            <svg x-show="showPass" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye-slash"></use>
            </svg>
          </button>
        </div>
        <p class="text-theme-text opacity-60 text-xs mt-1 leading-relaxed"><?= _t('st_preview_password_desc') ?></p>
      </div>

      <label class="block mt-8">
        <span class="block mb-2 font-bold text-theme-text text-sm"><?= _t('st_iframe_domains') ?></span>
        <textarea name="iframe_allowed_domains" rows="3" class="form-control font-mono text-xs w-full" placeholder="example.com&#10;my-video-server.net"><?= h($opt['iframe_domains'] ?? '') ?></textarea>
        <p class="text-theme-text opacity-60 text-xs mt-1 leading-relaxed"><?= _t('st_iframe_domains_desc') ?></p>
      </label>
    </div>

    <div class="flex justify-end mt-8 pt-6 border-theme-border border-t">
      <button type="submit" :disabled="isSubmitting" class="relative flex justify-center items-center shadow-theme px-6 py-2.5 rounded-theme w-full sm:w-auto font-bold text-sm transition-all disabled:opacity-70 disabled:cursor-not-allowed btn-primary overflow-hidden">
        <div class="flex items-center gap-2 transition-opacity duration-200" :class="isSubmitting ? 'opacity-0' : 'opacity-100'">
          <span><?= _t('btn_save_settings') ?></span>
        </div>
        <div class="absolute inset-0 flex items-center justify-center transition-opacity duration-200" :class="isSubmitting ? 'opacity-100' : 'opacity-0 pointer-events-none'">
          <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
          </svg>
        </div>
      </button>
    </div>
  </div>
</form>

<div class="bg-theme-surface shadow-theme mt-6 p-4 sm:p-6 border border-theme-border rounded-theme" x-data="{ user: 'admin', pass: '', result: '' }">
  <h4 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-lg">
    <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-lock-closed"></use>
    </svg>
    <?= _t('st_htpasswd_title') ?>
  </h4>
  <p class="opacity-60 mb-6 text-theme-text text-sm">
    <?= _t('st_htpasswd_desc') ?>
  </p>

  <div class="gap-4 grid grid-cols-1 md:grid-cols-2">
    <div class="block">
      <label for="htpasswd_user" class="block mb-2 font-bold text-theme-text text-sm"><?= _t('username') ?></label>
      <input type="text" id="htpasswd_user" x-model="user" class="form-control">
    </div>
    <div class="block">
      <label for="htpasswd_pass" class="block mb-2 font-bold text-theme-text text-sm"><?= _t('password') ?></label>
      <div class="flex gap-2" x-data="{ showPass: false }">
        <div class="relative w-full">
          <input :type="showPass ? 'text' : 'password'" id="htpasswd_pass" x-model="pass" class="font-mono form-control w-full pr-10" placeholder="<?= _t('password') ?>" autocomplete="new-password">
          <button type="button" @click="showPass = !showPass" class="right-0 absolute inset-y-0 flex items-center opacity-50 hover:opacity-100 px-3 focus:outline-none text-theme-text" tabindex="-1">
            <svg x-show="!showPass" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
            </svg>
            <svg x-show="showPass" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye-slash"></use>
            </svg>
          </button>
        </div>
        <button type="button" @click="
            if(!user || !pass) { window.showToast(<?= htmlspecialchars(json_encode(_t('username_and_password_required'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>, 'error'); return; }
            fetch('settings.php?tab=security', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=generate_htpasswd&user=' + encodeURIComponent(user) + '&pass=' + encodeURIComponent(pass) + '&csrf_token=' + encodeURIComponent(window.grindsCsrfToken)
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) result = data.entry;
                else window.showToast(data.error, 'error');
            });
        " class="shadow-theme px-4 py-2 rounded-theme text-sm whitespace-nowrap btn-primary"><?= _t('btn_generate') ?></button>
      </div>
    </div>
  </div>

  <div x-show="result" class="mt-4" x-cloak>
    <label class="block opacity-70 mb-1 font-bold text-theme-text text-xs"><?= _t('lbl_result') ?></label>
    <div class="flex sm:flex-row flex-col gap-2">
      <div class="flex flex-1 w-full">
        <input type="text" readonly :value="result" class="flex-1 min-w-0 rounded-r-none font-mono text-xs form-control select-all">
        <button @click="navigator.clipboard.writeText(result); window.showToast(<?= htmlspecialchars(json_encode(_t('copied'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>)" class="bg-theme-bg hover:bg-theme-surface px-3 border border-l-0 border-theme-border rounded-r-theme text-theme-text text-xs whitespace-nowrap transition-colors"><?= _t('btn_copy') ?></button>
      </div>
      <button type="button" @click="
          if(!confirm(<?= htmlspecialchars(json_encode(_t('confirm_save_htpasswd'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>)) return;
          fetch('settings.php?tab=security', {
              method: 'POST',
              headers: {'Content-Type': 'application/x-www-form-urlencoded'},
              body: 'action=save_htpasswd&content=' + encodeURIComponent(result) + '&csrf_token=' + encodeURIComponent(window.grindsCsrfToken)
          })
          .then(r => r.json())
          .then(data => {
              if(data.success) window.showToast(<?= htmlspecialchars(json_encode(_t('msg_saved'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>);
              else window.showToast(data.error, 'error');
          });
      " class="shadow-theme px-4 py-2 rounded-theme text-xs w-full sm:w-auto whitespace-nowrap btn-secondary">
        <?= _t('btn_save_file') ?>
      </button>
    </div>

    <div class="mt-4 pt-4 border-theme-border border-t">
      <p class="opacity-80 mb-2 text-theme-text text-xs"><?= _t('st_htpasswd_howto') ?></p>
      <div class="bg-theme-danger/10 mb-2 p-2 border border-theme-danger rounded-theme font-bold text-theme-danger text-[10px]">
        <?= _t('st_htpasswd_path_warn') ?>
      </div>
      <pre class="bg-theme-bg p-3 border border-theme-border rounded-theme font-mono text-[10px] text-theme-text select-all overflow-x-auto custom-scrollbar"># Basic Authentication
AuthType Basic
AuthName "Admin Area"
# NOTE: Update this path if you move the site
AuthUserFile <?= h(str_replace('\\', '/', ROOT_PATH)) ?>/.htpasswd
Require valid-user</pre>
    </div>
  </div>
</div>
