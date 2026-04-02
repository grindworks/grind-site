<?php

/**
 * posts_list.php
 *
 * Renders the main posts list view for the admin panel.
 */
if (!defined('GRINDS_APP')) exit;

$type = $current_type ?? 'post';
$status_filter = $_GET['status'] ?? '';
$is_trash_view = ($status_filter === 'trash');

// Load skin to get draft color
$skin = require __DIR__ . '/../load_skin.php';
$draft_bg_color = $skin['colors']['status_draft'] ?? '#f3f4f6';

$formAction = 'posts.php';
if (!empty($_SERVER['QUERY_STRING'])) {
  $formAction .= '?' . $_SERVER['QUERY_STRING'];
}
$csrf_token = generate_csrf_token();
?>

<!-- Hidden form for bulk actions. -->
<?php include __DIR__ . '/parts/hidden_action_form.php'; ?>

<!-- Mobile floating action button. -->
<?php if (!$is_trash_view): ?>
  <?php
  $href = "posts.php?action=new&type=" . h($type);
  include __DIR__ . '/parts/fab.php';
  ?>
<?php endif; ?>

<div class="flex flex-col gap-4 mb-6">
  <!-- Header section. -->
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <h2 class="flex items-center gap-2 font-bold text-theme-text text-xl shrink-0 whitespace-nowrap">
      <svg class="w-6 h-6 text-theme-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
      </svg>
      <?= _t('menu_posts') ?>
      <?php if ($is_trash_view): ?>
        <span class="bg-theme-danger/10 px-2 py-0.5 border border-theme-danger/20 rounded-theme text-theme-danger text-sm"><?= _t('st_trash') ?></span>
      <?php endif; ?>
    </h2>

    <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">

      <!-- Search form. -->
      <form method="get" action="posts.php" class="hidden sm:block relative">
        <input type="hidden" name="type" value="<?= h($type) ?>">
        <?php if ($status_filter): ?><input type="hidden" name="status" value="<?= h($status_filter) ?>"><?php endif; ?>

        <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="<?= _t('search') ?>"
          class="bg-theme-bg pl-8 border-theme-border w-32 focus:w-48 text-theme-text text-xs transition-all form-control-sm">
        <svg class="top-1/2 left-2.5 absolute opacity-50 w-3.5 h-3.5 text-theme-text -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
        </svg>
      </form>

      <?php include __DIR__ . '/parts/limit_selector.php'; ?>

      <?php if (!$is_trash_view): ?>
        <div class="hidden lg:block">
          <?php if ($type === 'template'): ?>
            <a href="posts.php?action=new&type=template" class="flex items-center gap-2 shadow-theme px-4 py-2 rounded-theme font-bold text-xs btn-secondary">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
              </svg>
              <span><?= _t('add') ?></span>
            </a>
          <?php else: ?>
            <a href="posts.php?action=new&type=<?= $type ?>" class="flex items-center gap-2 shadow-theme px-6 py-2.5 rounded-theme font-bold text-xs sm:text-sm transition-all btn-primary">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-plus"></use>
              </svg>
              <span><?= _t('create_new') ?></span>
            </a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="hidden lg:block">
          <form method="post" onsubmit='return confirm(<?= json_encode(_t('confirm_empty_trash'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>);'>
            <input type="hidden" name="csrf_token" value="<?= h(generate_csrf_token()) ?>">
            <input type="hidden" name="action" value="empty_trash">
            <button type="submit" class="flex items-center gap-2 bg-theme-danger hover:opacity-90 disabled:hover:opacity-50 shadow-theme px-6 py-2.5 rounded-theme font-bold text-white text-xs sm:text-sm transition-all disabled:cursor-not-allowed" <?= $count_trash == 0 ? 'disabled' : '' ?>>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
              </svg>
              <span><?= _t('btn_empty_trash') ?></span>
            </button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="border-theme-border border-b overflow-x-auto no-scrollbar">
    <nav class="flex space-x-1" aria-label="Tabs">
      <?php
      $tabs = [
        'post' => _t('type_post'),
        'page' => _t('type_page'),
        'template' => _t('btn_template')
      ];
      foreach ($tabs as $k => $label):
        $isActive = ($type === $k && !$is_trash_view);
      ?>
        <a href="?type=<?= $k ?>"
          class="whitespace-nowrap py-3 px-4 border-b-2 font-bold text-sm transition-colors <?= $isActive ? 'border-theme-primary text-theme-primary' : 'border-transparent text-theme-text opacity-60 hover:text-theme-text hover:border-theme-border' ?>">
          <?= $label ?>
        </a>
      <?php endforeach; ?>

      <!-- Trash tab. -->
      <a href="?status=trash"
        class="whitespace-nowrap py-3 px-4 border-b-2 font-bold text-sm transition-colors <?= $is_trash_view ? 'border-theme-danger text-theme-danger' : 'border-transparent text-theme-text opacity-40 hover:text-theme-danger hover:border-theme-danger/30' ?>">
        <svg class="inline-block mr-1 mb-0.5 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
        </svg>
        <?= _t('st_trash') ?> (<?= $count_trash ?>)
      </a>
    </nav>
  </div>

  <!-- Mobile search form. -->
  <div class="sm:hidden mb-2">
    <form method="get" action="posts.php" class="relative">
      <input type="hidden" name="type" value="<?= h($type) ?>">
      <?php if ($status_filter): ?><input type="hidden" name="status" value="<?= h($status_filter) ?>"><?php endif; ?>
      <input type="text" name="q" value="<?= h($_GET['q'] ?? '') ?>" placeholder="<?= _t('search') ?>"
        class="bg-theme-bg pl-8 border-theme-border w-full text-theme-text text-xs form-control-sm">
      <svg class="top-1/2 left-2.5 absolute opacity-50 w-3.5 h-3.5 text-theme-text -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-magnifying-glass"></use>
      </svg>
    </form>
  </div>

  <?php if ($type !== 'template' && !$is_trash_view): ?>
    <div class="flex gap-3 pb-1 overflow-x-auto text-xs no-scrollbar">
      <span class="inline-flex items-center bg-theme-success/10 px-2 py-1 border border-theme-success/20 rounded-theme text-theme-success whitespace-nowrap">
        <span class="mr-1 font-bold"><?= _t('st_published') ?>:</span> <?= $count_published ?>
      </span>
      <span class="inline-flex items-center bg-theme-text/10 px-2 py-1 border border-theme-border rounded-theme text-theme-text whitespace-nowrap">
        <span class="mr-1 font-bold"><?= _t('st_draft') ?>:</span> <?= $count_draft ?>
      </span>
    </div>
  <?php endif; ?>
</div>

<!-- Bulk action bar. -->
<div class="bg-theme-surface shadow-theme mb-6 p-4 border border-theme-border rounded-theme" x-data="{ bulkAction: '' }">
  <div class="flex lg:flex-row flex-col justify-between gap-4">

    <!-- Action selectors. -->
    <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
      <select id="bulk-action-selector" x-model="bulkAction" class="w-48 cursor-pointer form-control-sm">
        <option value=""><?= _t('lbl_bulk_actions') ?></option>
        <?php if ($is_trash_view): ?>
          <option value="restore"><?= _t('action_restore') ?></option>
          <option value="delete"><?= _t('action_delete_perm') ?></option>
        <?php else: ?>
          <option value="publish"><?= _t('action_publish') ?></option>
          <option value="draft"><?= _t('action_revert_draft') ?></option>
          <?php if ($type === 'post'): ?>
            <option value="change_category"><?= _t('change_category') ?></option>
          <?php endif; ?>
          <option value="trash"><?= _t('action_move_trash') ?></option>
        <?php endif; ?>
      </select>

      <!-- Category selector. -->
      <select id="bulk-category-selector" class="slide-in-from-left-2 w-48 animate-in duration-200 cursor-pointer form-control-sm fade-in" x-show="bulkAction === 'change_category'" style="display: none;">
        <option value=""><?= _t('lbl_select_category') ?></option>
        <?php foreach ($filter_cats as $fc): ?>
          <option value="<?= $fc['id'] ?>"><?= h($fc['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <button type="button" id="bulk-apply" class="px-3 py-1.5 text-xs whitespace-nowrap btn-secondary">
        <?= _t('apply') ?>
      </button>
    </div>

    <?php if ($type === 'post' && !$is_trash_view): ?>
      <div class="flex gap-2 w-full lg:w-auto overflow-x-auto">
        <select onchange="applyFilter('cat', this.value)" class="w-full lg:w-48 cursor-pointer form-control-sm">
          <option value=""><?= _t('lbl_category') ?>: <?= _t('all') ?></option>
          <?php foreach ($filter_cats as $fc): ?>
            <option value="<?= $fc['id'] ?>" <?= (isset($_GET['cat']) && $_GET['cat'] == $fc['id']) ? 'selected' : '' ?>><?= h($fc['name']) ?></option>
          <?php endforeach; ?>
        </select>

        <select onchange="applyFilter('status', this.value)" class="w-full lg:w-48 cursor-pointer form-control-sm">
          <option value=""><?= _t('col_status') ?>: <?= _t('all') ?></option>
          <option value="published" <?= (isset($_GET['status']) && $_GET['status'] === 'published') ? 'selected' : '' ?>><?= _t('st_published') ?></option>
          <option value="draft" <?= (isset($_GET['status']) && $_GET['status'] === 'draft') ? 'selected' : '' ?>><?= _t('st_draft') ?></option>
          <option value="reserved" <?= (isset($_GET['status']) && $_GET['status'] === 'reserved') ? 'selected' : '' ?>><?= _t('st_reserved') ?></option>
        </select>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if (empty($posts)): ?>
  <div class="flex flex-col justify-center items-center py-16 px-4 bg-theme-bg/30 border-2 border-theme-border border-dashed rounded-theme text-center">
    <div class="flex justify-center items-center w-16 h-16 mb-4 rounded-full bg-theme-surface shadow-sm text-theme-text opacity-50">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-text"></use>
      </svg>
    </div>
    <h3 class="mb-1 font-bold text-theme-text text-lg opacity-80"><?= _t('no_data') ?></h3>
  </div>
<?php else: ?>

  <!-- Desktop table view. -->
  <div class="hidden md:block bg-theme-surface shadow-theme border border-theme-border rounded-theme overflow-x-auto">
    <table class="min-w-full leading-normal">
      <thead>
        <tr class="bg-theme-bg/50 border-theme-border border-b font-bold text-theme-text/60 text-xs text-left uppercase tracking-wider">
          <th class="px-6 py-4 w-10"><input type="checkbox" id="select-all" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary form-checkbox"></th>
          <?php $sorter->renderTh('title', _t('col_title')); ?>
          <?php if ($type !== 'template' || $is_trash_view): ?><th class="px-6 py-4"><?= _t('lbl_category') ?> / <?= _t('col_type') ?></th><?php endif; ?>
          <?php if ($type !== 'template'): ?><?php $sorter->renderTh('status', _t('col_status'), 'hidden lg:table-cell'); ?><?php endif; ?>
          <?php $sorter->renderTh('updated_at', _t('col_date'), 'hidden lg:table-cell'); ?>
          <th class="px-6 py-4 text-right whitespace-nowrap"><?= _t('col_action') ?></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-theme-border">
        <?php foreach ($posts as $row):
          $isPublished = $row['is_published'];
          $isFuture = $row['is_future'];
          $catName = $row['cat_name'];
        ?>
          <tr class="group hover:bg-theme-bg/50 transition-colors" <?= ($row['status'] === 'draft') ? 'style="background-color:' . h($draft_bg_color) . '"' : '' ?>>
            <td class="px-6 py-4">
              <input type="checkbox" name="ids[]" value="<?= $row['id'] ?>" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary post-checkbox form-checkbox">
            </td>
            <td class="px-6 py-4">
              <div class="flex items-center min-w-[200px]">
                <?php if ($row['type'] !== 'template'): ?>
                  <?php if (!empty($row['thumbnail'])): ?>
                    <img src="<?= h(get_media_url($row['thumbnail'])) ?>" class="flex-shrink-0 mr-3 border border-theme-border rounded-theme w-10 h-10 object-cover" onerror='this.onerror=null;this.src=<?= json_encode(PLACEHOLDER_IMG, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;'>
                  <?php else: ?>
                    <svg class="flex-shrink-0 mr-3 border border-theme-border rounded-theme w-10 h-10 object-cover text-theme-text/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
                    </svg>
                  <?php endif; ?>
                <?php endif; ?>
                <div>
                  <?php if ($is_trash_view): ?>
                    <span class="opacity-70 font-bold text-theme-text break-words line-clamp-2" title="<?= h($row['title']) ?>"><?= h($row['title']) ?></span>
                  <?php else: ?>
                    <a href="posts.php?action=edit&id=<?= $row['id'] ?>" class="font-bold text-theme-text hover:text-theme-primary break-words line-clamp-2 transition-colors" title="<?= h($row['title']) ?>"><?= h($row['title']) ?></a>
                  <?php endif; ?>
                </div>
              </div>
            </td>

            <?php if ($type !== 'template' || $is_trash_view): ?>
              <td class="opacity-80 px-6 py-4 min-w-[120px] max-w-[200px] text-theme-text text-sm">
                <div class="flex flex-wrap gap-1">
                  <?php if ($is_trash_view): ?>
                    <span class="text-[10px] px-1.5 py-0.5 rounded-theme border font-bold <?= $row['post_type_class'] ?>"><?= $row['post_type_label'] ?></span>
                  <?php endif; ?>

                  <?php if ($row['type'] !== 'template'): ?>
                    <span class="inline-block bg-theme-bg px-2 py-1 border border-theme-border rounded-theme max-w-[140px] text-xs truncate" title="<?= h($catName) ?>">
                      <?= h($catName) ?>
                    </span>
                  <?php endif; ?>
                </div>
              </td>
              <?php if ($type !== 'template'): ?>
                <td class="hidden lg:table-cell px-6 py-4 text-sm whitespace-nowrap">
                  <?php if ($is_trash_view): ?>
                    <span class="bg-theme-danger/10 border-theme-danger/20 text-theme-danger badge"><?= _t('st_trash') ?></span>
                  <?php elseif ($isPublished && $isFuture): ?>
                    <span class="badge badge-warning"><?= _t('st_reserved') ?></span>
                  <?php elseif ($isPublished): ?>
                    <span class="badge badge-success"><?= _t('st_published') ?></span>
                  <?php else: ?>
                    <span class="badge badge-draft"><?= _t('st_draft') ?></span>
                  <?php endif; ?>
                </td>
              <?php endif; ?>
              <td class="hidden lg:table-cell opacity-70 px-6 py-4 font-mono text-theme-text text-xs whitespace-nowrap">
                <?php
                $dateVal = $is_trash_view ? $row['deleted_at'] : ($isFuture ? $row['published_at'] : $row['updated_at']);

                $dateDisplay = $dateVal ? date('Y-m-d H:i', strtotime($dateVal)) : '-';
                echo $isFuture ? "<span class='text-theme-warning font-bold'>{$dateDisplay}</span>" : $dateDisplay;
                ?>

              </td>
            <?php else: ?>
              <td class="opacity-70 px-6 py-4 font-mono text-theme-text text-xs whitespace-nowrap"><?= date('Y-m-d H:i', strtotime($row['updated_at'])) ?></td>
            <?php endif; ?>

            <td class="px-6 py-4 text-sm text-right align-middle whitespace-nowrap">
              <div class="flex justify-end items-center gap-3 h-full">
                <?php if ($is_trash_view): ?>
                  <button type="button" onclick="executeAction('restore', <?= $row['id'] ?>)" class="flex items-center bg-transparent p-1 border-none text-theme-success hover:scale-110 transition-transform cursor-pointer" title="<?= h(_t('btn_restore_post')) ?>" aria-label="<?= h(_t('btn_restore_post')) ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-uturn-left"></use>
                    </svg>
                  </button>
                  <button type="button" onclick="executeAction('delete', <?= $row['id'] ?>)" class="flex items-center bg-transparent p-1 border-none text-theme-danger hover:scale-110 transition-transform cursor-pointer" title="<?= h(_t('action_delete_perm')) ?>" aria-label="<?= h(_t('action_delete_perm')) ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-circle"></use>
                    </svg>
                  </button>
                <?php else: ?>
                  <a href="posts.php?action=edit&id=<?= $row['id'] ?>" class="flex items-center p-1 text-theme-primary hover:scale-110 transition-transform" title="<?= h(_t('edit')) ?>" aria-label="<?= h(_t('edit')) ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-pencil-square"></use>
                    </svg>
                  </a>
                  <button type="button" onclick="executeAction('duplicate', <?= $row['id'] ?>)" class="flex items-center bg-transparent p-1 border-none text-theme-warning hover:scale-110 transition-transform cursor-pointer" title="<?= h(_t('copy')) ?>" aria-label="<?= h(_t('copy')) ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-duplicate"></use>
                    </svg>
                  </button>
                  <button type="button" onclick="executeAction('trash', <?= $row['id'] ?>)" class="flex items-center bg-transparent p-1 border-none text-theme-danger hover:scale-110 transition-transform cursor-pointer" title="<?= h(_t('btn_delete_post')) ?>" aria-label="<?= h(_t('btn_delete_post')) ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                    </svg>
                  </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Mobile card view. -->
  <div class="md:hidden space-y-3">
    <div class="flex items-center gap-2 mb-3 px-2">
      <input type="checkbox" id="mobile-select-all" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary form-checkbox">
      <label for="mobile-select-all" class="text-sm font-bold text-theme-text"><?= _t('all') ?></label>
    </div>
    <?php foreach ($posts as $row):
      $isPublished = $row['is_published'];
      $isFuture = $row['is_future'];
      $catName = $row['cat_name'];
    ?>
      <div class="bg-theme-surface border border-theme-border rounded-theme overflow-hidden" x-data="{ open: false }" <?= ($row['status'] === 'draft') ? 'style="background-color:' . h($draft_bg_color) . '"' : '' ?>>
        <!-- Compact Header -->
        <div class="flex items-center gap-3 p-3 cursor-pointer" @click="open = !open">
          <div class="shrink-0" @click.stop>
            <input type="checkbox" name="ids[]" value="<?= $row['id'] ?>" class="bg-theme-bg border-theme-border rounded-sm focus:ring-0 w-5 h-5 text-theme-primary post-checkbox form-checkbox">
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex justify-between items-start gap-2">
              <h4 class="font-bold text-theme-text text-sm truncate"><?= h($row['title']) ?></h4>
              <!-- Status Badge (Small) -->
              <?php if ($is_trash_view): ?>
                <span class="shrink-0 bg-theme-danger/10 px-1.5 py-0.5 rounded-theme font-bold text-theme-danger text-[10px]"><?= _t('st_trash') ?></span>
              <?php elseif ($isPublished && $isFuture): ?>
                <span class="shrink-0 bg-theme-warning/10 px-1.5 py-0.5 rounded-theme font-bold text-theme-warning text-[10px]"><?= _t('st_reserved') ?></span>
              <?php elseif ($isPublished): ?>
                <span class="shrink-0 bg-theme-success/10 px-1.5 py-0.5 rounded-theme font-bold text-theme-success text-[10px]"><?= _t('st_published') ?></span>
              <?php else: ?>
                <span class="shrink-0 bg-theme-text/10 px-1.5 py-0.5 rounded-theme font-bold text-theme-text text-[10px]"><?= _t('st_draft') ?></span>
              <?php endif; ?>
            </div>
            <?php
            $dateVal = $is_trash_view ? $row['deleted_at'] : ($isFuture ? $row['published_at'] : $row['updated_at']);
            $dateDisplay = $dateVal ? date('Y-m-d H:i', strtotime($dateVal)) : '-';
            ?>
            <div class="flex items-center gap-2 mt-1 text-theme-text/60 text-xs">
              <span class="<?= $isFuture ? 'text-theme-warning font-bold' : '' ?>"><?= $dateDisplay ?></span>
            </div>
          </div>

          <div class="text-theme-text/40 shrink-0">
            <svg class="w-5 h-5 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-chevron-down"></use>
            </svg>
          </div>
        </div>

        <!-- Expanded Details -->
        <div x-show="open" style="display: none;" x-cloak x-collapse class="bg-theme-bg/30 p-4 border-theme-border border-t">
          <div class="flex gap-4 mb-4">
            <!-- Thumbnail -->
            <?php if ($row['type'] !== 'template'): ?>
              <div class="shrink-0">
                <?php if (!empty($row['thumbnail'])): ?>
                  <img src="<?= h(get_media_url($row['thumbnail'])) ?>" class="border border-theme-border rounded-theme w-16 h-16 object-cover">
                <?php else: ?>
                  <div class="flex justify-center items-center bg-theme-bg border border-theme-border rounded-theme w-16 h-16 text-theme-text/20">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-photo"></use>
                    </svg>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="flex-1 space-y-2 min-w-0">
              <!-- Category / Type -->
              <?php if ($type !== 'template' || $is_trash_view): ?>
                <div class="flex flex-wrap gap-1">
                  <?php if ($is_trash_view): ?>
                    <span class="px-1.5 py-0.5 rounded-theme border font-bold text-[10px] <?= $row['post_type_class'] ?>"><?= $row['post_type_label'] ?></span>
                  <?php endif; ?>
                  <?php if ($row['type'] !== 'template'): ?>
                    <span class="inline-block bg-theme-bg px-2 py-1 border border-theme-border rounded-theme text-xs truncate" title="<?= h($catName) ?>">
                      <?= h($catName) ?>
                    </span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-3 border-theme-border/50 border-t">
            <?php if ($is_trash_view): ?>
              <button type="button" onclick="executeAction('restore', <?= $row['id'] ?>)" class="flex items-center gap-1 bg-theme-success/10 px-3 py-1.5 rounded-theme font-bold text-theme-success text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-arrow-uturn-left"></use>
                </svg>
                <?= _t('btn_restore_post') ?>
              </button>
              <button type="button" onclick="executeAction('delete', <?= $row['id'] ?>)" class="flex items-center gap-1 bg-theme-danger/10 px-3 py-1.5 rounded-theme font-bold text-theme-danger text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-x-circle"></use>
                </svg>
                <?= _t('action_delete_perm') ?>
              </button>
            <?php else: ?>
              <a href="posts.php?action=edit&id=<?= $row['id'] ?>" class="flex items-center gap-1 bg-theme-primary/10 px-3 py-1.5 rounded-theme font-bold text-theme-primary text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-pencil-square"></use>
                </svg>
                <?= _t('edit') ?>
              </a>
              <button type="button" onclick="executeAction('duplicate', <?= $row['id'] ?>)" class="flex items-center gap-1 bg-theme-warning/10 px-3 py-1.5 rounded-theme font-bold text-theme-warning text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-document-duplicate"></use>
                </svg>
                <?= _t('copy') ?>
              </button>
              <button type="button" onclick="executeAction('trash', <?= $row['id'] ?>)" class="flex items-center gap-1 bg-theme-danger/10 px-3 py-1.5 rounded-theme font-bold text-theme-danger text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <use href="<?= grinds_asset_url('assets/img/sprite.svg') ?>#outline-trash"></use>
                </svg>
                <?= _t('delete') ?>
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>

<div class="flex justify-end mt-6">
  <?php if (isset($paginator)) echo $paginator->render(); ?>
</div>
