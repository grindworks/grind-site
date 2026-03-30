<?php

/**
 * profile.php
 * Renders the interface for updating user profile.
 */
if (!defined('GRINDS_APP'))
  exit;

/** @var \PDO $pdo */

// Load current user data
if (!isset($myUser)) {
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
  $stmt->execute([$_SESSION['user_id']]);
  $myUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>
<div class="space-y-6 bg-theme-surface shadow-theme p-4 sm:p-6 border border-theme-border rounded-theme">
  <form method="post" enctype="multipart/form-data" class="warn-on-unsaved" x-data="{
    isSubmitting: false,
    avatarPreview: <?= htmlspecialchars(json_encode(get_media_url($myUser['avatar'] ?? '')), ENT_QUOTES) ?>,
    fileName: ''
  }" @submit="setTimeout(() => isSubmitting = true, 10)">
    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
    <input type="hidden" name="action" value="update_profile">

    <div class="mb-6 sm:mb-8">
      <h3 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-xl">
        <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-user-circle"></use>
        </svg>
        <?= _t('st_profile_title') ?>
      </h3>
      <p class="opacity-60 ml-0 sm:ml-7 text-theme-text text-sm leading-relaxed">
        <?= _t('st_profile_desc') ?>
      </p>
    </div>

    <?php if (empty($myUser['email'])): ?>
      <div class="flex items-start gap-3 bg-theme-warning/10 mb-6 p-4 border-theme-warning border-l-4 rounded-r-theme">
        <svg class="mt-0.5 w-5 h-5 text-theme-warning shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-exclamation-triangle"></use>
        </svg>
        <p class="font-bold text-theme-warning text-sm leading-relaxed">
          <?= _t('msg_email_warning') ?>
        </p>
      </div>
    <?php
    endif; ?>

    <div class="flex md:flex-row flex-col gap-8">

      <div class="flex flex-col items-center w-full md:w-1/3">
        <div class="relative group">
          <div
            class="relative w-32 h-32 rounded-full border-4 border-theme-surface shadow-theme overflow-hidden bg-theme-bg">
            <template x-if="avatarPreview">
              <img :src="avatarPreview" class="w-full h-full object-cover">
            </template>
            <template x-if="!avatarPreview">
              <div class="flex items-center justify-center w-full h-full bg-theme-bg text-theme-text/20">
                <svg class="w-20 h-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-user-circle"></use>
                </svg>
              </div>
            </template>

            <label
              class="absolute inset-0 flex flex-col items-center justify-center skin-modal-overlay opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-white">
              <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-camera"></use>
              </svg>
              <span class="text-[10px] font-bold uppercase tracking-wider">
                <?= _t('btn_change') ?>
              </span>
              <input type="file" name="avatar" class="sr-only" accept="image/*" @change="
                const file = $event.target.files[0];
                if (file) {
                  fileName = file.name;
                  const reader = new FileReader();
                  reader.onload = (e) => avatarPreview = e.target.result;
                  reader.readAsDataURL(file);
                  $refs.deleteInput.checked = false;
                }
              ">
            </label>
          </div>

          <div x-show="avatarPreview" class="absolute top-0 right-0 -mr-1 -mt-1"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-75"
            x-transition:enter-end="opacity-100 scale-100">
            <label
              class="flex items-center justify-center w-8 h-8 bg-theme-surface text-theme-danger border border-theme-border rounded-full shadow-theme cursor-pointer hover:bg-theme-danger hover:text-white transition-colors"
              title="<?= _t('delete') ?>">
              <input type="checkbox" name="delete_avatar" value="1" class="hidden" x-ref="deleteInput"
                @change="if($event.target.checked) { avatarPreview = ''; fileName = ''; }">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
              </svg>
            </label>
          </div>
        </div>
        <input type="hidden" name="current_avatar" value="<?= h($myUser['avatar']) ?>">
        <p class="mt-4 text-xs text-theme-text opacity-60 text-center max-w-[12rem]">
          <?= _t('st_avatar_help') ?>
        </p>
      </div>

      <div class="space-y-5 w-full md:w-2/3">
        <div class="space-y-4">
          <label class="block">
            <span class="block mb-2 font-bold text-theme-text text-sm">
              <?= _t('st_username') ?>
            </span>
            <input type="text" value="<?= h($myUser['username']) ?>"
              class="bg-theme-bg/50 text-theme-text/70 cursor-not-allowed form-control" disabled>
          </label>

          <label class="block">
            <span class="block mb-2 font-bold text-theme-text text-sm">
              <?= _t('st_email') ?>
            </span>
            <input type="email" name="email" value="<?= h($myUser['email']) ?>" class="form-control"
              placeholder="user@example.com">
          </label>
        </div>

        <hr class="my-6 border-theme-border">

        <div class="space-y-4">
          <div class="flex justify-between items-center">
            <p class="flex items-center gap-2 font-bold text-theme-text text-sm">
              <svg class="w-4 h-4 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-key"></use>
              </svg>
              <?= _t('msg_change_pass_hint') ?>
            </p>
          </div>

          <div class="gap-4 grid grid-cols-1 sm:grid-cols-2">
            <div class="block" x-data="{ show: false, pass: '', reqLength: false, reqLetter: false, reqNumber: false }">
              <label for="new_password" class="block opacity-70 mb-1 font-bold text-theme-text text-xs">
                <?= _t('st_new_password') ?>
              </label>
              <div class="relative">
                <input :type="show ? 'text' : 'password'" name="new_password" id="new_password" x-model="pass"
                  @input="reqLength = pass.length >= 8; reqLetter = /[a-zA-Z]/.test(pass); reqNumber = /[0-9]/.test(pass);"
                  class="font-mono pr-10 text-sm form-control"
                  placeholder="<?= _t('ph_pass_8_chars') ?>" autocomplete="new-password">
                <button type="button" @click="show = !show"
                  class="right-0 absolute inset-y-0 flex items-center opacity-50 hover:opacity-100 px-3 focus:outline-none text-theme-text">
                  <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
                  </svg>
                  <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye-slash"></use>
                  </svg>
                </button>
              </div>
              <ul class="mt-2 text-[10px] space-y-1" x-show="pass.length > 0" x-cloak>
                <li class="flex items-center gap-1 transition-colors" :class="reqLength ? 'text-theme-success' : 'text-theme-danger'">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="reqLength ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12'" />
                  </svg>
                  8+ Characters
                </li>
                <li class="flex items-center gap-1 transition-colors" :class="reqLetter ? 'text-theme-success' : 'text-theme-danger'">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="reqLetter ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12'" />
                  </svg>
                  Includes Letter
                </li>
                <li class="flex items-center gap-1 transition-colors" :class="reqNumber ? 'text-theme-success' : 'text-theme-danger'">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="reqNumber ? 'M5 13l4 4L19 7' : 'M6 18L18 6M6 6l12 12'" />
                  </svg>
                  Includes Number
                </li>
              </ul>
            </div>

            <div class="block" x-data="{ show: false }">
              <label for="new_password_confirm" class="block opacity-70 mb-1 font-bold text-theme-text text-xs">
                <?= _t('st_new_password_confirm') ?>
              </label>
              <div class="relative">
                <input :type="show ? 'text' : 'password'" name="new_password_confirm" id="new_password_confirm" class="font-mono pr-10 text-sm form-control"
                  placeholder="<?= _t('ph_pass_confirm') ?>" autocomplete="new-password">
                <button type="button" @click="show = !show"
                  class="right-0 absolute inset-y-0 flex items-center opacity-50 hover:opacity-100 px-3 focus:outline-none text-theme-text">
                  <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
                  </svg>
                  <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye-slash"></use>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <hr class="my-6 border-theme-border">

        <!-- User Preferences -->
        <div class="space-y-4">
          <div class="mb-4">
            <h4 class="font-bold text-theme-text text-sm flex items-center gap-2">
              <svg class="w-4 h-4 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-paint-brush"></use>
              </svg>
              <?= _t('st_admin_preferences') ?>
            </h4>
            <p class="opacity-60 text-theme-text text-xs leading-relaxed">
              <?= _t('st_admin_pref_desc') ?>
            </p>
          </div>

          <div class="gap-4 grid grid-cols-1 sm:grid-cols-2">
            <label class="block">
              <span class="block opacity-70 mb-1 font-bold text-theme-text text-xs">
                <?= _t('st_layout_admin') ?>
              </span>
              <select name="admin_layout" class="shadow-theme form-control text-sm">
                <option value="system" <?= empty($myUser['admin_layout']) || $myUser['admin_layout'] === 'system'
                                          ? 'selected' : '' ?>>
                  <?= _t('system_default') ?>
                </option>
                <?php foreach ($available_layouts as $key => $label): ?>
                  <option value="<?= h($key) ?>" <?= $myUser['admin_layout'] === $key ? 'selected' : '' ?>>
                    <?= h($label) ?>
                  </option>
                <?php
                endforeach; ?>
              </select>
            </label>

            <label class="block">
              <span class="block opacity-70 mb-1 font-bold text-theme-text text-xs">
                <?= _t('st_skin_admin') ?>
              </span>
              <select name="admin_skin" class="shadow-theme form-control text-sm">
                <option value="system" <?= empty($myUser['admin_skin']) || $myUser['admin_skin'] === 'system' ? 'selected'
                                          : '' ?>>
                  <?= _t('system_default') ?>
                </option>
                <?php foreach ($available_admin_skins as $key => $label): ?>
                  <option value="<?= h($key) ?>" <?= $myUser['admin_skin'] === $key ? 'selected' : '' ?>>
                    <?= h($label) ?>
                  </option>
                <?php
                endforeach; ?>
              </select>
            </label>
          </div>
        </div>

        <div class="bg-theme-bg/30 mt-6 border border-theme-border rounded-theme overflow-hidden">
          <div class="p-5">
            <div class="block" x-data="{ show: false }">
              <label for="current_password" class="block mb-1 font-bold text-theme-text text-xs">
                <?= _t('st_current_password') ?>
                <span class="ml-1 text-[10px] text-theme-danger">
                  <?= _t('lbl_required') ?>
                </span>
              </label>
              <div class="relative">
                <input :type="show ? 'text' : 'password'" name="current_password" id="current_password"
                  class="font-mono pr-10 border-theme-primary/30 focus:border-theme-primary text-sm form-control"
                  placeholder="********" required autocomplete="current-password">
                <button type="button" @click="show = !show"
                  class="right-0 absolute inset-y-0 flex items-center opacity-50 hover:opacity-100 px-3 focus:outline-none text-theme-text">
                  <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
                  </svg>
                  <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye-slash"></use>
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <div
            class="flex sm:flex-row flex-col justify-between items-center gap-4 bg-theme-bg/50 px-5 py-4 border-theme-border border-t">
            <p class="opacity-50 text-[10px] text-theme-text">
              <?= _t('msg_profile_save_note') ?>
            </p>
            <button type="submit" name="update_profile" value="1"
              class="flex justify-center items-center gap-2 shadow-theme px-6 py-2.5 rounded-theme w-full sm:w-auto font-bold text-sm whitespace-nowrap transition-all btn-primary"
              :disabled="isSubmitting">
              <svg class="w-4 h-4" :class="{ 'animate-spin': isSubmitting }" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-path"></use>
              </svg>
              <?= _t('update') ?>
            </button>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>
