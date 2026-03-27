<?php

/**
 * users.php
 * Renders the interface for managing users.
 */
if (!defined('GRINDS_APP'))
  exit;

// Retrieve editor permissions
$perms = json_decode(get_option('editor_permissions', '[]'), true);
if (!is_array($perms))
  $perms = [];

/** @var \PDO $pdo */

// Load user list
if (!isset($userList)) {
  $stmt = $pdo->query("SELECT * FROM users ORDER BY id ASC");
  $userList = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="space-y-6 bg-theme-surface shadow-theme p-4 sm:p-6 border border-theme-border rounded-theme">

  <div class="flex sm:flex-row flex-col justify-between sm:items-center gap-4 mb-6 sm:mb-8">
    <div>
      <h3 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-xl">
        <svg class="w-5 h-5 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-users"></use>
        </svg>
        <?= _t('st_user_management') ?>
      </h3>
      <p class="opacity-60 ml-0 sm:ml-7 text-theme-text text-sm leading-relaxed">
        <?= _t('st_users_desc') ?>
      </p>
    </div>
    <div>
      <button type="button" @click="$dispatch('open-user-modal', { mode: 'add' })"
        class="flex justify-center items-center gap-2 shadow-theme px-6 py-2.5 rounded-theme w-full sm:w-auto font-bold text-sm whitespace-nowrap transition-all btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
        </svg>
        <span>
          <?= _t('st_add_user') ?>
        </span>
      </button>
    </div>
  </div>

  <div class="hidden md:block border border-theme-border rounded-theme overflow-x-auto">
    <table class="min-w-full leading-normal">
      <thead
        class="bg-theme-bg/50 border-theme-border border-b font-bold text-theme-text/60 text-xs text-left uppercase tracking-wider">
        <tr>
          <th class="px-6 py-4">
            <?= _t('col_id') ?>
          </th>
          <th class="px-6 py-4">
            <?= _t('st_username') ?>
          </th>
          <th class="px-6 py-4">
            <?= _t('lbl_role') ?>
          </th>
          <th class="px-6 py-4">
            <?= _t('st_email') ?>
          </th>
          <th class="px-6 py-4 text-right">
            <?= _t('col_action') ?>
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-theme-border">
        <?php foreach ($userList as $u):
          $uRole = $u['role'] ?? 'admin';
          $roleClass = $uRole === 'admin' ? 'bg-theme-danger/10 text-theme-danger border-theme-danger/20' : 'bg-theme-info/10 text-theme-info border-theme-info/20';
        ?>
          <tr class="hover:bg-theme-bg/50 transition-colors">
            <td class="opacity-70 px-6 py-4 font-mono text-theme-text text-sm">
              <?= $u['id'] ?>
            </td>
            <td class="px-6 py-4 font-bold text-theme-text text-sm">
              <div class="flex items-center gap-2 whitespace-nowrap">
                <div
                  class="flex justify-center items-center bg-theme-primary/10 border border-theme-primary/20 rounded-full w-6 h-6 font-bold text-theme-primary text-xs">
                  <?= strtoupper(substr($u['username'], 0, 1)) ?>
                </div>
                <?= h($u['username']) ?>
                <?php if ($u['id'] == $_SESSION['user_id']): ?>
                  <span
                    class="bg-theme-success/10 px-1.5 py-0.5 border border-theme-success/20 rounded-theme font-bold text-[10px] text-theme-success">
                    <?= _t('st_myself') ?>
                  </span>
                <?php
                endif; ?>
              </div>
            </td>
            <td class="px-6 py-4 text-sm">
              <span class="px-2 py-0.5 rounded-theme text-[10px] font-bold border <?= $roleClass ?> whitespace-nowrap">
                <?= $uRole === 'admin' ? _t('role_admin') : _t('role_editor') ?>
              </span>
            </td>
            <td class="opacity-80 px-6 py-4 text-theme-text text-sm">
              <?= h($u['email']) ?: '<span class="opacity-40 text-xs">-</span>' ?>
            </td>
            <td class="px-6 py-4 text-sm text-right align-middle whitespace-nowrap">
              <div class="flex justify-end items-center gap-4 h-full">
                <button type="button" @click="$dispatch('open-user-modal', {
                      mode: 'edit',
                      id: <?= htmlspecialchars(json_encode($u['id']), ENT_QUOTES) ?>,
                      username: <?= htmlspecialchars(json_encode($u['username']), ENT_QUOTES) ?>,
                      email: <?= htmlspecialchars(json_encode($u['email']), ENT_QUOTES) ?>,
                      role: <?= htmlspecialchars(json_encode($uRole), ENT_QUOTES) ?>
                  })"
                  class="inline-flex items-center gap-1 bg-transparent p-0 border-none font-bold text-theme-primary text-xs hover:underline cursor-pointer">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-pencil-square"></use>
                  </svg>
                  <?= _t('edit') ?>
                </button>

                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                  <form method="post" style="display:inline-flex;">
                    <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="delete_user" value="<?= $u['id'] ?>">
                    <button type="submit"
                      onclick="return confirm(<?= htmlspecialchars(json_encode(_t('st_confirm_user_del')), ENT_QUOTES) ?>)"
                      class="inline-flex items-center gap-1 bg-transparent p-0 border-none font-bold text-theme-danger text-xs hover:underline cursor-pointer">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                      </svg>
                      <?= _t('delete') ?>
                    </button>
                  </form>
                <?php
                else: ?>
                  <span class="inline-flex items-center gap-1 opacity-20 text-theme-text text-xs cursor-not-allowed"
                    title="Cannot delete yourself">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                    </svg>
                    <?= _t('delete') ?>
                  </span>
                <?php
                endif; ?>
              </div>
            </td>
          </tr>
        <?php
        endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="md:hidden space-y-3">
    <?php foreach ($userList as $u):
      $uRole = $u['role'] ?? 'admin';
    ?>
      <div class="bg-theme-bg/20 shadow-theme p-4 border border-theme-border rounded-theme">
        <div class="flex justify-between items-start mb-2">
          <div class="flex items-center gap-2">
            <div
              class="flex justify-center items-center bg-theme-primary/10 border border-theme-primary/20 rounded-full w-8 h-8 font-bold text-theme-primary text-sm">
              <?= strtoupper(substr($u['username'], 0, 1)) ?>
            </div>
            <div>
              <div class="flex items-center gap-2 font-bold text-theme-text text-sm">
                <?= h($u['username']) ?>
                <?php if ($u['id'] == $_SESSION['user_id']): ?>
                  <span
                    class="bg-theme-success/10 px-1.5 py-0.5 border border-theme-success/20 rounded font-bold text-[10px] text-theme-success">
                    <?= _t('st_myself') ?>
                  </span>
                <?php
                endif; ?>
              </div>
              <div class="opacity-60 font-mono text-theme-text text-xs">
                ID:
                <?= $u['id'] ?> • <span class="font-bold uppercase">
                  <?= $uRole === 'admin' ? _t('role_admin') : _t('role_editor') ?>
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="opacity-80 mb-3 pb-3 pl-10 border-theme-border/50 border-b text-theme-text text-sm">
          <?= h($u['email']) ?: '<span class="opacity-40">' . _t('lbl_no_email') . '</span>' ?>
        </div>

        <div class="flex justify-end gap-4 pl-10">
          <button type="button" @click="$dispatch('open-user-modal', {
                mode: 'edit',
                id: <?= htmlspecialchars(json_encode($u['id']), ENT_QUOTES) ?>,
                username: <?= htmlspecialchars(json_encode($u['username']), ENT_QUOTES) ?>,
                email: <?= htmlspecialchars(json_encode($u['email']), ENT_QUOTES) ?>,
                role: <?= htmlspecialchars(json_encode($uRole), ENT_QUOTES) ?>
            })"
            class="flex items-center gap-1 bg-transparent p-0 border-none font-bold text-theme-primary text-xs hover:underline cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-pencil-square"></use>
            </svg>
            <?= _t('edit') ?>
          </button>

          <?php if ($u['id'] != $_SESSION['user_id']): ?>
            <form method="post" style="display:inline-flex;">
              <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
              <input type="hidden" name="action" value="delete_user">
              <input type="hidden" name="delete_user" value="<?= $u['id'] ?>">
              <button type="submit"
                onclick="return confirm(<?= htmlspecialchars(json_encode(_t('st_confirm_user_del')), ENT_QUOTES) ?>)"
                class="flex items-center gap-1 bg-transparent p-0 border-none font-bold text-theme-danger text-xs hover:underline cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                </svg>
                <?= _t('delete') ?>
              </button>
            </form>
          <?php
          else: ?>
            <span class="flex items-center gap-1 opacity-20 text-theme-text text-xs cursor-not-allowed">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
              </svg>
              <?= _t('delete') ?>
            </span>
          <?php
          endif; ?>
        </div>
      </div>
    <?php
    endforeach; ?>
  </div>

  <hr class="border-theme-border">

  <div class="bg-theme-bg/30 p-6 border border-theme-border rounded-theme">
    <h4 class="flex items-center gap-2 mb-2 font-bold text-theme-text text-lg">
      <svg class="w-5 h-5 text-theme-info" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-key"></use>
      </svg>
      <?= _t('st_editor_perms') ?>
    </h4>
    <p class="opacity-60 mb-6 text-theme-text text-sm">
      <?= _t('st_editor_perms_desc') ?>
    </p>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
      <input type="hidden" name="action" value="update_permissions">

      <div class="gap-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 mb-6">

        <label
          class="flex items-center gap-3 bg-theme-surface p-3 border border-theme-border hover:border-theme-primary rounded-theme transition-colors cursor-pointer">
          <input type="checkbox" name="perms[]" value="manage_categories"
            class="flex-shrink-0 bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-4 h-4 text-theme-primary form-checkbox"
            <?= in_array('manage_categories', $perms) ? 'checked' : '' ?>>
          <div class="flex items-center gap-2 min-w-0">
            <svg class="flex-shrink-0 w-4 h-4 text-theme-primary/70" fill="none" stroke="currentColor"
              viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-rectangle-stack"></use>
            </svg>
            <div class="min-w-0">
              <div class="font-bold text-theme-text text-sm leading-tight">
                <?= _t('menu_categories') ?> /
                <?= _t('menu_tags') ?>
              </div>
              <div class="opacity-50 text-theme-text text-xs leading-tight truncate">
                <?= _t('perm_manage_categories') ?>
              </div>
            </div>
          </div>
        </label>

        <label
          class="flex items-center gap-3 bg-theme-surface p-3 border border-theme-border hover:border-theme-primary rounded-theme transition-colors cursor-pointer">
          <input type="checkbox" name="perms[]" value="manage_menus"
            class="flex-shrink-0 bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-4 h-4 text-theme-primary form-checkbox"
            <?= in_array('manage_menus', $perms) ? 'checked' : '' ?>>
          <div class="flex items-center gap-2 min-w-0">
            <svg class="flex-shrink-0 w-4 h-4 text-theme-primary/70" fill="none" stroke="currentColor"
              viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-bars-3"></use>
            </svg>
            <div class="min-w-0">
              <div class="font-bold text-theme-text text-sm leading-tight">
                <?= _t('menu_menus') ?>
              </div>
              <div class="opacity-50 text-theme-text text-xs leading-tight truncate">
                <?= _t('perm_manage_menus') ?>
              </div>
            </div>
          </div>
        </label>

        <label
          class="flex items-center gap-3 bg-theme-surface p-3 border border-theme-border hover:border-theme-primary rounded-theme transition-colors cursor-pointer">
          <input type="checkbox" name="perms[]" value="manage_widgets"
            class="flex-shrink-0 bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-4 h-4 text-theme-primary form-checkbox"
            <?= in_array('manage_widgets', $perms) ? 'checked' : '' ?>>
          <div class="flex items-center gap-2 min-w-0">
            <svg class="flex-shrink-0 w-4 h-4 text-theme-primary/70" fill="none" stroke="currentColor"
              viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-squares-plus"></use>
            </svg>
            <div class="min-w-0">
              <div class="font-bold text-theme-text text-sm leading-tight">
                <?= _t('menu_widgets') ?>
              </div>
              <div class="opacity-50 text-theme-text text-xs leading-tight truncate">
                <?= _t('perm_manage_widgets') ?>
              </div>
            </div>
          </div>
        </label>

        <label
          class="flex items-center gap-3 bg-theme-surface p-3 border border-theme-border hover:border-theme-primary rounded-theme transition-colors cursor-pointer">
          <input type="checkbox" name="perms[]" value="manage_banners"
            class="flex-shrink-0 bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-4 h-4 text-theme-primary form-checkbox"
            <?= in_array('manage_banners', $perms) ? 'checked' : '' ?>>
          <div class="flex items-center gap-2 min-w-0">
            <svg class="flex-shrink-0 w-4 h-4 text-theme-primary/70" fill="none" stroke="currentColor"
              viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-megaphone"></use>
            </svg>
            <div class="min-w-0">
              <div class="font-bold text-theme-text text-sm leading-tight">
                <?= _t('menu_banners') ?>
              </div>
              <div class="opacity-50 text-theme-text text-xs leading-tight truncate">
                <?= _t('perm_manage_banners') ?>
              </div>
            </div>
          </div>
        </label>

        <label
          class="flex items-center gap-3 bg-theme-surface p-3 border border-theme-border hover:border-theme-primary rounded-theme transition-colors cursor-pointer">
          <input type="checkbox" name="perms[]" value="manage_tools"
            class="flex-shrink-0 bg-theme-bg border-theme-border rounded focus:ring-theme-primary w-4 h-4 text-theme-primary form-checkbox"
            <?= in_array('manage_tools', $perms) ? 'checked' : '' ?>>
          <div class="flex items-center gap-2 min-w-0">
            <svg class="flex-shrink-0 w-4 h-4 text-theme-primary/70" fill="none" stroke="currentColor"
              viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-wrench-screwdriver"></use>
            </svg>
            <div class="min-w-0">
              <div class="font-bold text-theme-text text-sm leading-tight">
                <?= _t('perm_manage_tools') ?>
              </div>
              <div class="opacity-50 text-theme-text text-xs leading-tight">
                <?= _t('ssg_title') ?> /
                <?= _t('menu_migration_check') ?> /
                <?= _t('menu_link_checker') ?> /
                <?= _t('mt_unused_uploads') ?>
              </div>
            </div>
          </div>
        </label>

        <label
          class="flex items-center gap-3 bg-theme-surface p-3 border border-theme-border hover:border-theme-danger rounded-theme transition-colors cursor-pointer">
          <input type="checkbox" name="perms[]" value="manage_settings"
            class="flex-shrink-0 bg-theme-bg border-theme-danger/30 rounded focus:ring-theme-danger w-4 h-4 text-theme-danger form-checkbox"
            <?= in_array('manage_settings', $perms) ? 'checked' : '' ?>>
          <div class="flex items-center gap-2 min-w-0">
            <svg class="flex-shrink-0 w-4 h-4 text-theme-danger/70" fill="none" stroke="currentColor"
              viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-cog-6-tooth"></use>
            </svg>
            <div class="min-w-0">
              <div class="font-bold text-theme-text text-sm leading-tight">
                <?= _t('menu_settings') ?>
              </div>
              <div class="opacity-50 text-theme-text text-xs leading-tight truncate">
                <?= _t('perm_manage_settings') ?>
              </div>
            </div>
          </div>
        </label>

      </div>

      <div class="text-right">
        <button type="submit" class="shadow-theme px-6 py-2.5 rounded-theme font-bold text-sm transition-all btn-primary">
          <?= _t('btn_save_perms') ?>
        </button>
      </div>
    </form>
  </div>

  <template x-teleport="body">
    <div x-data="{
        isOpen: false,
        mode: 'add',
        targetId: '',
        username: '',
        email: '',
        password: '',
        passwordConfirm: '',
        role: 'editor'
      }" x-effect="document.body.style.overflow = isOpen ? 'hidden' : ''"
      @open-user-modal.window="
        isOpen = true;
        mode = $event.detail.mode;
        targetId = $event.detail.id || '';
        username = $event.detail.username || '';
        email = $event.detail.email || '';
        role = $event.detail.role || 'editor';
        password = '';
        passwordConfirm = '';
      " x-show="isOpen" class="z-50 fixed inset-0 flex justify-center items-center p-4" style="display: none;" x-cloak>

      <div class="fixed inset-0 skin-modal-overlay backdrop-blur-sm transition-opacity" @click="isOpen = false"></div>

      <form method="post" enctype="multipart/form-data"
        class="z-10 relative flex flex-col bg-theme-surface shadow-theme border border-theme-border rounded-theme w-full max-w-md max-h-[90vh] overflow-hidden transition-all transform">
        <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
        <input type="hidden" name="action" :value="mode === 'add' ? 'add_user' : 'edit_user'">
        <input type="hidden" name="target_id" :value="targetId">

        <div class="flex justify-between items-center bg-theme-bg px-6 py-4 border-theme-border border-b shrink-0">
          <h3 class="font-bold text-theme-text text-lg"
            x-text="mode === 'add' ? <?= htmlspecialchars(json_encode(_t('st_add_user')), ENT_QUOTES) ?> : <?= htmlspecialchars(json_encode(_t('st_edit_user')), ENT_QUOTES) ?>">
          </h3>
          <button type="button" @click="isOpen = false"
            class="opacity-50 hover:opacity-100 text-theme-text text-2xl leading-none">&times;</button>
        </div>

        <div class="p-6 overflow-y-auto custom-scrollbar">
          <div class="space-y-4">
            <label class="block">
              <span class="block opacity-70 mb-1 font-bold text-theme-text text-xs">
                <?= _t('st_username') ?> <span class="text-theme-danger" x-show="mode === 'add'">*</span>
              </span>
              <input type="text" name="new_username" x-model="username" class="text-sm form-control"
                :required="mode === 'add'" :readonly="mode === 'edit'"
                :class="mode === 'edit' ? 'bg-theme-bg opacity-70 cursor-not-allowed' : ''">
            </label>

            <label class="block">
              <span class="block opacity-70 mb-1 font-bold text-theme-text text-xs">
                <?= _t('lbl_role') ?>
              </span>
              <select name="role" x-model="role" class="text-sm cursor-pointer form-control">
                <option value="editor">
                  <?= _t('role_desc_editor') ?>
                </option>
                <option value="admin">
                  <?= _t('role_desc_admin') ?>
                </option>
              </select>
            </label>

            <label class="block">
              <span class="block opacity-70 mb-1 font-bold text-theme-text text-xs">
                <?= _t('st_email') ?> <span class="text-theme-danger">*</span>
              </span>
              <input type="email" name="new_email" x-model="email" class="text-sm form-control"
                placeholder="user@example.com" :required="mode === 'add'">
            </label>

            <hr class="border-theme-border">

            <div x-show="mode === 'edit'" class="bg-theme-info/10 mb-2 p-2 rounded-theme text-theme-info text-xs">
              <?= _t('msg_edit_pass_hint') ?>
            </div>

            <div class="space-y-4">
              <div class="block" x-data="{ show: false }">
                <label for="password" class="block opacity-70 mb-1 font-bold text-theme-text text-xs">
                  <?= _t('st_password') ?>
                  <span x-show="mode === 'add'" class="text-theme-danger">*</span>
                </label>
                <div class="relative">
                  <input :type="show ? 'text' : 'password'" name="password" id="password" x-model="password"
                    class="font-mono pr-10 text-sm form-control" placeholder="<?= _t('ph_pass_8_chars') ?>"
                    :required="mode === 'add'" autocomplete="new-password">
                  <button type="button" @click="show = !show"
                    class="right-0 absolute inset-y-0 flex items-center opacity-50 hover:opacity-100 px-3 focus:outline-none text-theme-text"
                    tabindex="-1">
                    <svg x-show="!show" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye"></use>
                    </svg>
                    <svg x-show="show" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-eye-slash"></use>
                    </svg>
                  </button>
                </div>
              </div>

              <div class="block" x-data="{ show: false }">
                <label for="password_confirm" class="block opacity-70 mb-1 font-bold text-theme-text text-xs">
                  <?= _t('st_password_confirm') ?>
                  <span x-show="mode === 'add'" class="text-theme-danger">*</span>
                </label>
                <div class="relative">
                  <input :type="show ? 'text' : 'password'" name="password_confirm" id="password_confirm" x-model="passwordConfirm"
                    class="font-mono pr-10 text-sm form-control" placeholder="<?= _t('ph_pass_confirm') ?>"
                    :required="mode === 'add'" autocomplete="new-password">
                  <button type="button" @click="show = !show"
                    class="right-0 absolute inset-y-0 flex items-center opacity-50 hover:opacity-100 px-3 focus:outline-none text-theme-text"
                    tabindex="-1">
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

            <div x-show="mode === 'edit'">
              <hr class="border-theme-border mb-4">
              <div class="block" x-data="{ show: false }">
                <label for="current_password_user_modal" class="block mb-1 font-bold text-theme-text text-xs">
                  <?= _t('st_current_password') ?>
                  <span class="ml-1 text-[10px] text-theme-danger">
                    <?= _t('lbl_required') ?>
                  </span>
                </label>
                <div class="relative">
                  <input :type="show ? 'text' : 'password'" name="current_password" id="current_password_user_modal"
                    class="font-mono pr-10 border-theme-primary/30 focus:border-theme-primary text-sm form-control"
                    placeholder="********" :required="mode === 'edit'" autocomplete="current-password">
                  <button type="button" @click="show = !show"
                    class="right-0 absolute inset-y-0 flex items-center opacity-50 hover:opacity-100 px-3 focus:outline-none text-theme-text"
                    tabindex="-1">
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

          <div class="flex justify-end gap-3 mt-8">
            <button type="button" @click="isOpen = false" class="px-4 py-2.5 rounded-theme text-sm btn-secondary">
              <?= _t('cancel') ?>
            </button>
            <button type="submit"
              class="shadow-theme px-6 py-2.5 rounded-theme font-bold text-sm transition-all btn-primary"
              x-text="mode === 'add' ? <?= htmlspecialchars(json_encode(_t('add')), ENT_QUOTES) ?> : <?= htmlspecialchars(json_encode(_t('update')), ENT_QUOTES) ?>"></button>
          </div>
        </div>
      </form>
    </div>
  </template>
</div>
